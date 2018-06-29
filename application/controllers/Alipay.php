<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Alipay/APY 支付宝类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Alipay extends CI_Controller
	{
	    // 支付宝API公共URL
	    private $gateway_url = 'https://openapi.alipay.com/gateway.do';

        // 公共参数
        private $params = array(
            'app_id' => ALIPAY_APP_ID,
            'method' => '', // 在业务方法中赋值
                //'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => '', // 在构造方法中赋值
            'version' => '1.0',
            'biz_content' => '', // 请求参数的集合字符串，除公共参数外所有请求参数都通过该参数传递
        );

		// 待签名/待验签字符串
		private $sign_string = '';
		
		// 支付参数字符串
		private $payment_string = '';

        // 仅部分方法适用构造函数
		public function manual_construct()
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

			$this->params['timestamp'] = date('Y-m-d H:i:s');
		} // end manual_construct

        // 仅部分方法适用解构函数
		public function manual_destruct()
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
        }

		/**
		 * APY3 获取支付宝支付所需参数
         *
         * https://docs.open.alipay.com/api_1/alipay.trade.app.pay/
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

			// 获取订单信息备用
            $order = $this->get_order_detail($order_id);
            $order_data = array(
				'body' => SITE_NAME. ($type === 'order'? '商品订单': '充值订单'),
                'total_fee' => $order['total'], // 待付款金额
			);

            // API
            $gateway_url = $this->gateway_url;

			// 公共参数
			$params = $this->params;
			$params['method'] = 'alipay.trade.app.pay';
            $params['notify_url'] = base_url('alipay/notify');

			// 参与签名的参数
			$out_trade_no = date('YmdHis').'_'. $type.'_'. $order_id; // 拼装订单号，64个字符以内
			$subject = $order_data['body']. ' 编号'. $order_id;
			$body = $order_data['body']. $out_trade_no;
			$request_params = array(
				'out_trade_no' => $out_trade_no,
				'total_amount' => $order_data['total_fee'],
				'subject' => $subject,
				'body' => $body,
				'product_code' => 'QUICK_MSECURITY_PAY', // 固定值
			);
			$params['biz_content'] = json_encode($request_params, JSON_UNESCAPED_UNICODE); // 订单信息

			// 生成签名，并拼合不参与签名的参数到请求参数
			$this->sign_string_generate($params); // 生成待签名及支付参数字符串
			$sign = $this->sign_generate(); // 生成签名

            $params['sign'] = $sign; // 已生成的签名
			$params['string_to_sign'] = $this->sign_string; // 待签名字符串
            $params['subject'] = $subject; // 订单名称
			$params['payment_string'] = $this->payment_string.'&sign='. urlencode($sign); // 含签名的所有参数字符串

			if ( !empty($params)):
				$this->result['status'] = 200;
				$this->result['content'] = $params;
			else:
				$this->result['status'] = 400;
				$this->result['content'] = '支付宝支付参数获取失败';
			endif;

            // 手动析构函数
            $this->manual_destruct();
		} // end create
		
		/**
		 * 4 接收订单通知并更新相关信息
		 */
		public function notify()
		{
            // 计算得出通知验证结果
            $result = $this->sign_verify($_POST);

            // 验证成功
            if ($result):
                // 交易状态
                $trade_status = $_POST['trade_status'];

                if ($trade_status == 'TRADE_FINISHED'): // 即时到帐交易确认支付后返回这个状态
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序

                    //注意：
                    //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                elseif ($trade_status == 'TRADE_SUCCESS'): // 付款完成后，支付宝系统发送该交易状态通知
                    // 获取基本订单信息及支付信息
                    list($order_prefix, $type, $order_id) = preg_split('/_/', $_POST['out_trade_no']); // 分解出防冗余下单订单前缀、订单类型（商品、券码、服务等）、订单号等
                    $data_to_edit['payment_type'] = '支付宝'; // 支付方式
                    $data_to_edit['payment_account'] = $_POST['buyer_logon_id']; // 付款账号；支付宝账号
                    $data_to_edit['payment_id'] = $_POST['trade_no']; // 支付流水号；支付宝订单号
                    $data_to_edit['total_payed'] = $_POST['receipt_amount']; // 已支付金额

                    // 更新订单信息
                    $this->order_update($data_to_edit, $type, $order_id);

                    // 发送短信通知（调试用）
                    $sms_mobile = '17664073966';
                    $sms_content = $type. '订单 '. $order_id. ' 已通过支付宝付款 '. $data_to_edit['total_payed']. ' 元';
                    @$this->sms_send($sms_mobile, $sms_content);
                endif;

                echo 'success'; // 请不要修改或删除

            // 验证失败
            else:
                echo 'fail';

            endif;
        } // end notify

        /**
         * 5 退款
         *
         * https://docs.open.alipay.com/api_1/alipay.trade.refund/
         */
        public function refund()
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
                //var_dump($order);
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

                elseif ( $order['payment_type'] !== '支付宝' ):
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

                // 公共参数
                $this->params['method'] = 'alipay.trade.refund';

                // 参与签名的参数
                $request_params = array(
                    'out_request_no' => date('YmdHis').'_orderrefund_'.$order_id, // 退款单号
                    'trade_no' => $order['payment_id'],
                    'refund_amount' => ($total_to_refund === 'all')? $order['total_payed']: $total_to_refund, // 全额或部分退款
                );
                $this->params['biz_content'] = json_encode($request_params, JSON_UNESCAPED_UNICODE); // 订单信息

                // 生成签名，并拼合不参与签名的参数到请求参数
                $this->sign_string_generate($this->params); // 生成待签名及支付参数字符串
                $this->params['sign'] = $this->sign_generate(); // 生成签名

                // 拼合请求URL
                $api_url = $this->gateway_url.'?';
                foreach ($this->params as $key => $value):
                    $api_url .= $key.'='. urlencode($value).'&';
                endforeach;

                // 发送请求
                $result = $this->curl($api_url);
                $result = json_decode($result, TRUE);
                $result = $result['alipay_trade_refund_response']; // 暂时只取用交易退款响应部分

                // 处理退款结果
                if ($result['msg'] === 'Success'):
                    $this->result['status'] = 200;
                    $this->result['content'] = '退款成功';

                else:
                    $this->result['status'] = 424;

                    if (isset($result['msg'])):
                        $this->result['content'] = $result['sub_msg'];

                    else:
                        $this->result['content'] = '退款失败';

                    endif;

                endif;

            endif;

            // 手动析构函数
            $this->manual_destruct();
        } // end refund

        private function curl($url)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {

                throw new Exception(curl_error($ch), 0);
            } else {
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (200 !== $httpStatusCode) {
                    throw new Exception($reponse, $httpStatusCode);
                }
            }

            curl_close($ch);
            return $result;
        }

        /**
         * 获取订单信息
         *
         * @param int/varchar $order_id 订单ID
         * @return array $result 订单信息
         */
        private function get_order_detail($order_id)
        {
            $this->switch_model('order', 'order_id');
            $this->db->select('total, total_payed, total_refund, payment_type, payment_id, status');
            $result = $this->basic_model->find('order_id', $order_id);

            return $result;
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
                    $this->stocks_update($order_id);
            endswitch;

            // 更新订单信息
            $this->switch_model($type, 'order_id');
            $this->basic_model->edit($order_id, $data_to_edit);
        } // end order_update

        /**
         * 更新实物订单相关商品/规格的库存值
         * TODO 执行结果验证
         * @param int/string $order_id 相关订单ID
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
            // 为短信内容添加后缀签名
            $content .= '【'. SITE_NAME. '】';

            $this->load->library('luosimao');
            @$result = $this->luosimao->send($mobile, $content);
        } // end sms_send

        /**
         * 生成待签名及支付字符串
         *
         * @param $params
         */
		private function sign_string_generate($params)
		{
			$params = array_filter($params); // 清理空元素
			ksort($params); // 按数组键名升序排序

			foreach ($params as $key => $value):
				$this->sign_string .= '&'. $key. '='. $value;
				$this->payment_string .= '&'. $key. '='. urlencode($value);
			endforeach;

			// 清理冗余“&”
			$this->sign_string = trim($this->sign_string, '&');
			$this->payment_string = trim($this->payment_string, '&');

			// 取消字符转义
			if (get_magic_quotes_gpc()):
				$this->sign_string = stripcslashes($this->sign_string);
			endif;
		} // end sign_string_generate

        /**
         * 生成待验证签名字符串
         *
         * @param $params
         */
        private function design_string_generate($params)
        {
            // 部分参数不参与签名
            unset($params['sign']);
            unset($params['sign_type']);

            $params = array_filter($params); // 清理空元素
            ksort($params); // 按数组键名升序排序

            foreach ($params as $key => $value):
                $this->sign_string .= '&'. $key. '='. $value;
            endforeach;

            // 清理冗余“&”
            $this->sign_string = trim($this->sign_string, '&');

            // 取消字符转义
            if (get_magic_quotes_gpc()):
                $this->sign_string = stripcslashes($this->sign_string);
            endif;
        } // end design_string_generate

        /**
         * 生成RSA2签名
         *
         * @return string 签名字符串
         */
		private function sign_generate()
		{
			$priKey = ALIPAY_KEY_PRIVATE;
			$res = "-----BEGIN RSA PRIVATE KEY-----\n".
				wordwrap($priKey, 64, "\n", true).
				"\n-----END RSA PRIVATE KEY-----";
			($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

		    openssl_sign($this->sign_string, $sign, $res, OPENSSL_ALGO_SHA256);

			// base64编码
		    $sign = base64_encode($sign);
		    return $sign;
		} // end sign_generate

		/**
		 * 验证RSA2签名
         *
         * @param array $params 接收到的异步回调内容
         * @return boolean $result 签名是否正确
		 */
		private function sign_verify($params)
		{
            // 获取签名
		    $sign = $params['sign'];

            // 生成待签名字符串
            $this->design_string_generate($params);

		    // 生成测试日志
            $this->log_this($params);

			$priKey = ALIPAY_KEY_PUBLIC;
			$res = "-----BEGIN PUBLIC KEY-----\n".
				wordwrap($priKey, 64, "\n", true).
				"\n-----END PUBLIC KEY-----";
			($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

			$result = (bool)openssl_verify($this->sign_string, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

			return $result;
		} // end sign_verify

        /**
         * 异步通知接收日志
         *
         * @param $params
         */
        private function log_this($params)
        {
            // 输出原始报文
            $origin_params = $params;
            $origin_data = '';
            foreach ($origin_params as $name => $value):
                $origin_data .= '&'.$name.'='.$value;
            endforeach;

            $data = $this->sign_string; // 待签名字符串
            $data .= "\n\n". $params['sign']; // 签名值
            $data .= "\n\n". trim($origin_data, '&'); // 原始报文

            // 写入日志
            $this->load->helper('file');
            $url = 'alipay.txt'; // 位于项目根目录下
            write_file($url, $data, 'w+');
        } // end log_this

	} // end class Alipay

/* End of file Alipay.php */
/* Location: ./application/controllers/Alipay.php */
