<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Wepay/WPY 微信支付类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Wepay extends CI_Controller
	{
		// 环境参数
		protected $app_id = WEPAY_APP_ID;
		protected $mch_id = WEPAY_MCH_ID;
		protected $key = WEPAY_KEY;
		//protected $app_secret = WEPAY_APP_SECRET;
		protected $sslcert_path = WEPAY_SSLCERT_PATH;
		protected $sslkey_path = WEPAY_SSLKEY_PATH;
		protected $notify_url = WEPAY_NOTIFY_URL;
		protected $trade_type = 'APP';

		// 请求型接口属性
		public $url; // 微信支付API接口链接
		public $parameters; //请求参数，类型为关联数组
		public $response; //微信返回的响应
		public $result; //返回参数，类型为关联数组

		// 响应型接口属性
		public $data; // 接收到的数据，类型为关联数组
		public $returnParameters;// 返回给微信服务器的参数，类型为关联数组

		// 仅部分方法适用构造函数
		protected function manual_construct()
		{
	        parent::__construct();

			// 统计业务逻辑运行时间起点
			$this->benchmark->mark('start');

			// 若无任何通过POST方式传入的请求参数，提示并退出
			if ( empty($_POST) ):
				$this->result['status'] = 000;
				$this->result['content']['error']['message'] = '请求方式不正确';
				exit();
			endif;

			// 向类属性赋值
			$this->timestamp = time();
			$this->app_type = $this->input->post('app_type');
			$this->app_version = $this->input->post('app_version');
			$this->device_platform = $this->input->post('device_platform');
			$this->device_number = $this->input->post('device_number');

			// 签名有效性检查
			// 测试环境可跳过签名检查
			if ( ENVIRONMENT !== 'development' && $this->input->post('skip_sign') !== 'please' )
				$this->sign_check();
		} // end manual_construct

		// 仅部分方法适用解构函数
		protected function manual_destruct()
		{
			// 将请求参数一并返回以便调试
			$this->result['param']['get'] = $this->input->get();
			$this->result['param']['post'] = $this->input->post();

			// 返回服务器端时间信息
			$this->result['timestamp'] = time();
			$this->result['datetime'] = date('Y-m-d H:i:s');
			$this->result['timezone'] = date_default_timezone_get();

			// 统计业务逻辑运行时间终点
			$this->benchmark->mark('end');
			// 计算并输出业务逻辑运行时间（秒）
			$this->result['elapsed_time'] = $this->benchmark->elapsed_time('start', 'end');

			header("Content-type:application/json;charset=utf-8");
			$output_json = json_encode($this->result);
			echo $output_json;
		} // end manual_destruct

		// 更换所用数据库
		protected function switch_model($table_name, $id_name)
		{
            $this->db->reset_query(); // 重置查询
		    $this->basic_model->table_name = $table_name;
			$this->basic_model->id_name = $id_name;
		} // end switch_model
		
		/**
		 * 3 创建微信支付订单
		 */
		public function create()
		{
			// 手动构造函数
			$this->manual_construct();

			// 检查必要参数是否已传入
			$order_id = $this->input->post('order_id');
			if ( empty($order_id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				$this->manual_destruct();
				exit();
			endif;

            // 获取订单类型，默认为商品订单
            $type = $this->input->post('type')? $this->input->post('type'): 'order';

            /*
            // 根据订单类型和订单编号获取订单信息
            if ($type == 'daigou'):
                $this->load->model('daigou_model');
                $order = $this->daigou_model->select_by_id($order_id);
            else:
                $this->load->model('order_model');
                $order = $this->order_model->select_by_id($order_id);
            endif;
            */

             // 获取订单信息备用 out_trade_no最多32个字符串，固定长度占了21个
            //如果订单号是连续的，用|
            //不连续的只取第一个
            $orderarr = explode(',', $order_id);
            $orders   = [];
            $ammount  = 0;
  			$first = intval(current($orderarr)) - 1;
  			$last  = end($orderarr);
  			$mark  = '';
  			if (count($orderarr) >= 2 && $first + count($orderarr) == $last) {
  				foreach ($orderarr as $key => $value) {
	                $temp = $this->get_order_detail(intval($value));
	                $ammount += $temp['total'];
	                $orders[] = $temp;
	            }
	            $mark = ($first + 1). "|" . $last;;
  			} else {
  				$temp = $this->get_order_detail($order_id);
  				$ammount = $temp['total'];
  				$mark = $order_id;
  			}

            $order_data = array(
                'body' => SITE_NAME. ($type === 'order'? '商品订单': '充值订单'),
                'total_fee' => $ammount, // 待付款金额
            );

			// 重组请求参数
			
			$this->parameters['out_trade_no'] = date('YmdHis').'_order_'.$mark;
			$this->parameters['body'] = $order_data['body'];
			$this->parameters['total_fee'] = $ammount * 100; // 默认以分为货币单位

			$this->url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
		   	$this->parameters['appid'] = $this->app_id; // 公众账号ID
		   	$this->parameters['mch_id'] = $this->mch_id; // 商户号
		    $this->parameters['nonce_str'] = $this->createNoncestr(); // 随机字符串
			$this->parameters['spbill_create_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
			$current_timestamp = time();
			$this->parameters['time_start'] = date('YmdHis', $current_timestamp); // 服务器生成订单的时间
			$this->parameters['time_expire'] = date('YmdHis', $current_timestamp + 3*60);; // 订单失效的时间，默认为下单后3分钟
			$this->parameters['notify_url'] = $this->notify_url;
			$this->parameters['trade_type'] = $this->trade_type;
		    $this->parameters['sign'] = $this->getSign($this->parameters); // 根据以上参数生成的签名
		    $xml = $this->arrayToXml($this->parameters);

			$this->postXml($xml);
			$this->result = $this->xmlToArray($this->response);
			if ($this->input->post('test_mode') === 'on'):
                echo 'out_trade_no'. $this->parameters['out_trade_no'];
				//var_dump($this->result);
			endif;
			$prepay_id = $this->result['prepay_id'];
			$return_parameters['prepayid'] = $prepay_id;
			$return_parameters['appid'] = $this->app_id; // 公众账号或微信开放平台的应用ID
			$return_parameters['partnerid'] = $this->mch_id;
			$return_parameters['noncestr'] = $this->createNoncestr();
			$return_parameters['package'] = 'Sign=WXPay';
			$return_parameters['timestamp'] = $current_timestamp;
			$return_parameters['sign'] = $this->getSign($return_parameters);

			// 输出返回的json
			$this->result = array(); // 重置返回结果数组
			$this->result['status'] = 200;
			$this->result['content'] = $return_parameters;

			// 手动析构函数
			$this->manual_destruct();
		} // end unified_order

        /**
         * 4 接收订单通知并更新相关信息
         */
		public function notify()
		{
			// 存储微信通知的请求参数
			$xml = file_get_contents('php://input');
			$this->saveData($xml);

			if ($this->data['return_code'] == FALSE):
				echo '此接口仅用于接收微信推送的付款状态通知';
				exit;
			endif;

			// 验证签名，并回应微信
			// 对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
			// 微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
			// 尽可能提高通知的成功率，但微信不保证通知最终能成功。
			if ($this->checkSign() === FALSE):
				$this->setReturnParameter('return_code', 'FAIL'); //返回状态码
				$this->setReturnParameter('return_msg', '签名失败'); //返回信息
			else:
				$this->setReturnParameter('return_code', 'SUCCESS'); //设置返回码
			endif;
			$returnXml = $this->returnXml();
			echo $returnXml; // 输出返回签名验证结果

			// 根据通知参数进行相应处理
			if ($this->checkSign() === TRUE):
				if ($this->data['return_code'] === 'FAIL'):
					// 更新订单状态
					//$log_->log_result($log_name, "【通信出错】:\n". $xml. "\n");

				elseif ($this->data['result_code'] === 'FAIL'):
					// 更新订单状态
					//$log_->log_result($log_name, "【业务出错】:\n". $xml. "\n");

				else:
					// 更新订单状态
					//$log_->log_result($log_name, "【支付成功】:\n". $xml. "\n");

					// 获取基本订单信息及支付信息
					list($order_prefix, $type, $order_id) = preg_split('/_/', $this->data['out_trade_no']); // 分解出防冗余下单订单前缀、订单类型（商品、券码、服务等）、订单号等
					$data_to_edit['payment_type'] = '微信支付'; // 支付方式
					$data_to_edit['payment_account'] = $this->data['openid']; // 付款账号；微信OpenID
					$data_to_edit['payment_id'] = $this->data['transaction_id']; // 支付流水号；微信支付订单号
					$data_to_edit['total_payed'] = $this->data['total_fee'] / 100; // 将货币单位由“分”换算为“元”

					// 更新订单信息 多个订单更新 或者单个更新
                    if (strpos($order_id, '|')) {
                        list($opoid, $lastoid) = explode('|', $order_id);
                        for($oid = intval($opoid); $oid <= intval($lastoid); $oid++) {
                            $oneOrder = $this->get_order_detail($oid);
                            $data_to_edit['total_payed'] = $oneOrder['total'];
                            $this->order_update($data_to_edit, $type, $oid);
                        }
                    } else {
                        $this->order_update($data_to_edit, $type, $order_id);
                    }
					

                    // 发送短信通知（调试用）
                /*
					$sms_mobile = '17664073966';
                    $sms_content = $type. '订单 '. $order_id. ' 已通过微信支付付款 '. $data_to_edit['total_payed']. ' 元';
                    @$this->sms_send($sms_mobile, $sms_content);
                */
				endif;
			endif;
		} // end notify

        /**
         * 5 退款
         */
        public function refund()
        {
            // 检查必要参数是否已传入
            $order_id = $this->input->post('order_id');
            if ( empty($order_id) ):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未传入';
                $this->manual_destruct();
                exit();
            endif;

            // 初始化并配置表单验证库
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('', '');
            // 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
            $this->form_validation->set_rules('order_id', '所属订单号', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('total_to_refund', '待退款金额', 'trim|greater_than_equal_to[0.01]');

            // 若表单提交不成功
            if ($this->form_validation->run() === FALSE):
                $this->result['status'] = 401;
                $this->result['content']['error']['message'] = validation_errors();

            else:
                // 获取待退款金额；若不传入则全额退款
                $total_to_refund = empty($this->input->post('total_to_refund'))? 'all': $this->input->post('total_to_refund'); // 待退款金额

                // 获取订单类型，默认为商品订单
                $type = $this->input->post('type')? $this->input->post('type'): 'order';

                /*
                // 根据订单类型和订单编号获取订单信息
                if ($type == 'daigou'):
                    $this->load->model('daigou_model');
                    $order = $this->daigou_model->select_by_id($order_id);
                else:
                    $this->load->model('order_model');
                    $order = $this->order_model->select_by_id($order_id);
                endif;
                */

                // 获取订单信息备用
                $order = $this->get_order_detail($order_id);
                // 尝试获取总价格
                $total = $this->get_order_total($order['payment_id']);
                if ($order['wepay_from'] != 'APP') {
                	$this->switchweb();
                }

                if ($this->input->post('test_mode') == 'on') var_dump($order);
                if ( empty($order) ):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '未找到待退款订单';
                    $this->manual_destruct();
                    exit();

                elseif ( empty($order['payment_id']) ):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '该订单尚未付款';
                    $this->manual_destruct();
                    exit();

                elseif ( $order['total_payed'] === $order['total_refund'] ):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '该订单已全额退款';
                    $this->manual_destruct();
                    exit();

                elseif ( $order['payment_type'] !== '微信支付' ):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '该订单是通过'.$order['payment_type'].'支付的';
                    $this->manual_destruct();
                    exit();

                elseif ( $total_to_refund !== 'all' && $total_to_refund > $order['total_payed']):
                    $this->result['status'] = 424;
                    $this->result['content']['error']['message'] = '申请退款金额不可超出实际支付金额';
                    $this->manual_destruct();
                    exit();
                endif;

                // 退款API
                $this->url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

                // 重组请求参数
                $this->parameters['out_refund_no'] = date('YmdHis').'_orderrefund_'.$order_id; // 退款单号
                $this->parameters['transaction_id'] = $order['payment_id'];
                $this->parameters['total_fee'] = $total * 100; // 默认以分为货币单位
                $this->parameters['refund_fee'] = ($total_to_refund === 'all')? $order['total_payed']: $total_to_refund; // 全额或部分退款
                $this->parameters['refund_fee'] *= 100; // 默认以分为货币单位

                // 公共参数
                $this->parameters['appid'] = $this->app_id; // 公众账号ID
                $this->parameters['mch_id'] = $this->mch_id; // 商户号
                $this->parameters['nonce_str'] = $this->createNoncestr(); // 随机字符串
                // $this->parameters['notify_url'] = $this->notify_url; // (可选)异步通知URL
                $this->parameters['sign'] = $this->getSign($this->parameters); // 根据以上参数生成的签名
                if ($this->input->post('test_mode') == 'on') var_dump($this->parameters);
                $xml = $this->arrayToXml($this->parameters);

                // 发送请求
                $this->postXmlSSL($xml);
                $result = $this->xmlToArray($this->response);
                if ($this->input->post('test_mode') == 'on') var_dump($result);
                // 处理退款结果
                if ($result['result_code'] === 'SUCCESS'):
                    $this->result['status'] = 200;
                    $this->result['content'] = '退款成功';

                else:
                    $this->result['status'] = 424;
                    // var_dump($result);
                    if ($result['result_code'] === 'FAIL'):
                        $this->result['content'] = ($result['err_code'] === 'ERROR')? $result['err_code_des']: $result['err_code'];
                        // $this->result['content'] = json_encode($result);
                        // $this->result['xml'] = json_encode($this->parameters);

                    else:
                        $this->result['content'] = '退款失败';
                        $this->result['test'] =  json_encode($result);
                    endif;

                endif;

            endif;

            // 手动析构函数
            $this->manual_destruct();
        } // end refund

        /**
         * 以下为工具方法
         */

        /**
         * 获取订单信息
         *
         * @param int/varchar $order_id 订单ID
         * @return array $result 订单信息
         */
        private function get_order_detail($order_id)
        {
            $this->switch_model('order', 'order_id');
            $this->db->select('order_id, total, total_payed, total_refund, payment_type, payment_id, `status`, wepay_from');
            $result = $this->basic_model->find('order_id', intval($order_id));
            return $result;
        } // end get_order_detail


        private function get_order_total($payment_id)
        {	
            $res = $this->db->query('select sum(total) as tt from `order` where payment_id=\'' .  $payment_id . '\'');
            $total = $res->result_array();
            return isset($total[0]['tt']) ? $total[0]['tt'] : 0.0;
        } // end get_order_detail
        /**
         * 更新订单信息
         *
         * @param $data_to_edit 待更新的订单信息
         * @param $type 订单类型
         * @param $order_id 订单号
         */
		private function order_update($data_to_edit, $type, $order_id)
		{
			$current_time = time(); // 服务器接收到付款通知的时间
			$data_to_edit['time_pay'] = $current_time;

			// 根据订单类型更新相应字段值
			switch ($type):
				case 'coupon': // 券码类订单
					$data_to_edit['time_accept'] = $current_time; // 收款即接单（等待发货）
					$data_to_edit['time_deliver'] = $current_time; // 收款即发货（生成券码）
					$data_to_edit['status'] = '待使用';
					break;
				case 'cater': // 服务类订单
					$data_to_edit['status'] = '待接单';
					break;
				default: // 实物类订单
					$data_to_edit['time_accept'] = $current_time; // 收款即接单（等待发货）
					$data_to_edit['status'] = '待发货';
					//$this->stocks_update($order_id); // 当前已调整为下单减库存
			endswitch;
            $data_to_edit['note_stuff'] = $type;
			// 更新订单信息
			$this->switch_model($type, 'order_id');
			$this->basic_model->edit($order_id, $data_to_edit);

			$this->load->model('order_model');
            $this->order_model->update_status(['order_id'=>$order_id], '未消费');
		} // end order_update

        /**
         * 更新实物订单相关商品/规格的库存值
         *
         * @param $order_id 相关订单ID
         */
        protected function stocks_update($order_id)
        {
            // 获取订单相关商品数据
            $query = $this->db->query("CALL get_order_items( $order_id )");
            $order_items = $query->result_array();
            $this->db->reconnect(); // 调用存储过程后必须重新连接数据库，下同

            foreach ($order_items as $item):
                if ( empty($item['sku_id']) ):
                    $this->db->query("CALL stocks_update('item', ". $item['item_id'].','. $item['count'].')');
                else:
                    $this->db->query("CALL stocks_update('sku', ". $item['sku_id'].','. $item['count'].')');
                endif;
                $this->db->reconnect();
            endforeach;
        } // end stocks_update

        /**
         * 发送短信
         */
        protected function sms_send($mobile, $content)
        {
            $this->load->library('luosimao');
            @$result = $this->luosimao->send($mobile, $content);
        } // end sms_send

		/**
		 * 基础通用方法
         */
		public function trimString($value)
		{
			$ret = NULL;
			if (NULL != $value):
				$ret = $value;
				if (strlen($ret) == 0):
					$ret = NULL;
				endif;
			endif;
			return $ret;
		} // end trimString

		/**
		 * 	作用：产生随机字符串，不长于32位
		 */
		public function createNoncestr($length = 32)
		{
			$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
			$str = '';
			for ($i = 0; $i < $length; $i++){  
				$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
			}  
			return $str;
		} // end createNoncestr

		/**
		 * 	作用：格式化参数，签名过程需要使用
		 */
		public function formatBizQueryParaMap($paraMap, $urlencode)
		{
			$buff = '';
			ksort($paraMap);
			foreach ($paraMap as $k => $v):
			    if($urlencode)
			    {
				   $v = urlencode($v);
				}
				//$buff .= strtolower($k) . '=' . $v . '&';
				$buff .= $k . '=' . $v . '&';
			endforeach;
			$reqPar;
			if (strlen($buff) > 0):
				$reqPar = substr($buff, 0, strlen($buff)-1);
			endif;
			return $reqPar;
		} // end formatBizQueryParaMap

		/**
		 * 	作用：生成签名
		 */
		public function getSign($Obj, $method = NULL)
		{
			foreach ($Obj as $k => $v):
				$Parameters[$k] = $v;
			endforeach;
			//签名步骤一：按字典序排序参数
			ksort($Parameters);
			$String = $this->formatBizQueryParaMap($Parameters, FALSE);
			//签名步骤二：在string后加入KEY
			$String = $String. '&key='. $this->key;
			//签名步骤三：MD5加密
			$String = md5($String);
			//签名步骤四：所有字符转为大写
			$result_ = strtoupper($String);

			return $result_;
		} // end getSign

		/**
		 * 	作用：array转xml
		 */
		function arrayToXml($arr)
	    {
	        $xml = '<xml>';
	        foreach ($arr as $key => $val):
	        	if (is_numeric($val)):
	        	 	$xml.='<'.$key.'>'.$val.'</'.$key.'>';
			 	else:
	        	 	$xml.='<'.$key.'><![CDATA['.$val.']]></'.$key.'>';  
				endif;
			endforeach;
	        $xml .= '</xml>';
	        return $xml;
	    } // end arrayToXml

		/**
		 * 	作用：将xml转为array
		 */
		public function xmlToArray($xml)
		{		
	        //将XML转为array        
	        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), TRUE);		
			return $array_data;
		} // end xmlToArray

		/**
		 * 	作用：以post方式提交xml到对应的接口url
		 */
		public function postXmlCurl($xml, $url, $second = 30)
		{
	        //初始化curl
	       	$ch = curl_init();
			//设置超时
			curl_setopt($ch, CURLOPT_TIMEOUT, $second);

	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			//设置header
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			//要求结果为字符串且输出到屏幕上
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			//post提交方式
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			//运行curl
	        $data = curl_exec($ch);
			//返回结果
			if ($data):
				curl_close($ch);
				return $data;
			else:
				$error = curl_errno($ch);
				echo 'curl出错，错误码:'.$error.'<br>'; 
				echo '<a href="http://curl.haxx.se/libcurl/c/libcurl-errors.html">错误原因查询</a><br>';
				curl_close($ch);
				return FALSE;
			endif;
		} // end postXmlCurl

		/**
		 * 	作用：使用证书，以post方式提交xml到对应的接口url
		 */
		public function postXmlSSLCurl($xml, $url, $second = 30)
		{
			$ch = curl_init();
			//超时时间
			curl_setopt($ch, CURLOPT_TIMEOUT, $second);
			
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			//设置header
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			//要求结果为字符串且输出到屏幕上
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			//默认格式为PEM，可以注释
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLCERT, $this->sslcert_path);
			//默认格式为PEM，可以注释
			curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLKEY, $this->sslkey_path);
			//post提交方式
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			$data = curl_exec($ch);

            // 输出CURL请求头以便调试
            //var_dump(curl_getinfo($ch));

			//返回结果
			if ($data):
				curl_close($ch);
				return $data;
			else:
				$error = curl_errno($ch);
				echo 'curl出错，错误码:'.$error.'<br>'; 
				echo '<a href="http://curl.haxx.se/libcurl/c/libcurl-errors.html">错误原因查询</a><br>';
				curl_close($ch);
				return FALSE;
			endif;
		} // end postXmlSSLCurl

        /**
         * 请求型接口的方法
         */

		/**
		 * 	作用：设置请求参数
		 */
		public function setParameter($parameter, $parameterValue)
		{
			$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
		}
	
		/**
		 * 	作用：post请求xml
		 */
		public function postXml($xml)
		{
			$this->response = $this->postXmlCurl($xml, $this->url);
			return $this->response;
		}
	
		/**
		 * 	作用：使用证书post请求xml
		 */
		public function postXmlSSL($xml)
		{
			$this->response = $this->postXmlSSLCurl($xml,$this->url);
			return $this->response;
		}

		/**
		 * 	作用：获取结果，默认不使用证书
		 */
		public function getResult() 
		{		
			$this->postXml();
			$this->result = $this->xmlToArray($this->response);
			return $this->result;
		}

        /**
         * 响应型接口方法
         */
	 	function saveData($xml)
	 	{
	 		$this->data = $this->xmlToArray($xml);
	 	}

	 	function checkSign()
	 	{
	 		$tmpData = $this->data;
	 		unset($tmpData['sign']);
	 		$sign = $this->getSign($tmpData);//本地签名
	 		if ($this->data['sign'] == $sign):
	 			return TRUE;
			endif;
	 		return FALSE;
	 	}

	 	/**
	 	 * 获取微信的请求数据
	 	 */
	 	function getData()
	 	{		
	 		return $this->data;
	 	}

	 	/**
	 	 * 设置返回微信的xml数据
	 	 */
	 	function setReturnParameter($parameter, $parameterValue)
	 	{
	 		$this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	 	}

	 	/**
	 	 * 生成接口参数xml
	 	 */
	 	function createXml()
	 	{
	 		return $this->arrayToXml($this->returnParameters);
	 	}

	 	/**
	 	 * 将xml数据返回微信
	 	 */
	 	function returnXml()
	 	{
	 		$returnXml = $this->createXml();
	 		return $returnXml;
	 	}
		
		/**
		 * 具体业务方法
		 */
		public function index()
		{
			echo $this->createNoncestr();
		} // end index

		public function switchweb(){
			//修改为web端公众号的支付账号
			$this->app_id = 'wxba173a67df14c087';
			$this->mch_id = '1488874732';
			$this->key = 'OHLAt2qyVdNVHqWWoWoc5Q4UbpFycpH6';
			//protected $app_secret = WEPAY_APP_SECRET;
			$this->sslcert_path = './payment/wepay/public_cert/apiclient_cert.pem';
			$this->sslkey_path = './payment/wepay/public_cert/apiclient_key.pem';
		}

	} // end class Wepay

/* End of file Wepay.php */
/* Location: ./application/controllers/wepay.php */
