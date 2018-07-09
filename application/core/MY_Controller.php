<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');
	
	/**
	 * MY_Controller 基础控制器类
	 *
	 * 针对API服务，对Controller类进行了扩展
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class MY_Controller extends CI_Controller
	{
        /**
         * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
         */
        protected $names_to_sort = array();

        /**
         * @var array 可根据最小值筛选的字段名
         */
        protected $min_needed = array(
            'time_create',
        );

        /**
         * @var array 可根据最大值筛选的字段名
         */
        protected $max_needed = array(
            'time_create',
        );

        /**
         * @var array 仅在管理类客户端返回的字段
         */
	    protected $names_return_for_admin = array(
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
        );

		/**
		 * 编辑单行特定字段时必要的字段名
		 */
		protected $names_edit_certain_required = array(
			'user_id', 'id', 'name', 'value',
		);

		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation', 'password',
		);
		
		// 初始化返回结果
		public $result = array(
			'status' => null, // 请求响应状态
			'content' => array(
			    'error' => array(
			        'message' => ''
                ),
            ), // 返回内容
			'param' => array(
				'get' => array(), // GET请求参数
				'post' => array(), // POST请求参数
			), // 接收到的请求参数
			'timestamp' => null, // 返回时时间戳
			'datetime' => null, // 返回时可读日期
			'timezone' => null, // 服务器本地时区
			'elapsed_time' => null, // 处理业务请求时间
		);

		/* 主要相关表名 */
		public $table_name;

		/* 主要相关表的主键名*/
		public $id_name;

		// 客户端类型
		protected $app_type;

		// 客户端版本号
		protected $app_version;

		// 设备操作系统平台ios/android；非移动客户端传空值
		protected $device_platform;

		// 设备唯一码；全小写
		protected $device_number;

		// 请求时间戳
		protected $timestamp;

		// 请求签名
		private $sign;

		public function __construct()
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

			// 检查是否已打开测试模式，
			if ($this->input->post('test_mode') === 'on'):
                $this->output->enable_profiler(TRUE); // 输出调试信息

                $this->result['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // 获取当前设备信息
			endif;
	    } // end __construct

		public function __destruct()
		{
            // 仅以JSON格式返回响应内容
		    header("Content-type:application/json;charset=utf-8");

		    // 若返回了错误信息，则标注为服务端错误信息
            if ( ! empty($this->result['content']['error']['message']))
                $this->result['content']['error']['message'] .= ' ERROR_API';

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

			// 输出响应内容
			echo json_encode($this->result);
		} // end __destruct

		/**
		 * 签名有效性检查
		 *
		 * 依次检查签名的时间是否过期、参数是否完整、签名是否正确
		 */
        protected function sign_check()
		{
			$this->sign_check_exits();
			$this->sign_check_time();
			$this->sign_check_params();
			$this->sign_check_string();
		} // end sign_check

		/**
         * 检查签名是否传入
         */
        protected function sign_check_exits()
		{
			$this->sign = $this->input->post('sign');

			if ( empty($this->sign) ):
				$this->result['status'] = 444;
				$this->result['content']['error']['message'] = '未传入签名';
				exit();
			endif;
		} // end sign_check_exits

		/**
         * 签名时间检查
         */
        protected function sign_check_time()
		{
			$timestamp_sign = $this->input->post('timestamp');

			if ( empty($timestamp_sign) ):
				$this->result['status'] = 440;
				$this->result['content']['error']['message'] = '必要的签名参数未全部传入；安全起见不做具体提示，请参考开发文档。';
				exit();

			else:
				$time_difference = ($this->timestamp - $timestamp_sign);

				// 测试阶段签名有效期为600秒，生产环境应为60秒
				if ($time_difference > 600):
					$this->result['status'] = 441;
					$this->result['content']['error']['message'] = '签名时间已超过有效区间。';
					exit();

				else:
					return TRUE;

				endif;

			endif;
		} // end sign_check_time

		/**
         * 签名参数检查
         */
        protected function sign_check_params()
		{
			// 检查需要参与签名的必要参数；
			$params_required = array(
				'app_type',
				'app_version',
				'device_platform',
				'device_number',
				'timestamp',
				'random',
			);

			// 获取传入的参数们
			$params = $_POST;

			// 检查必要参数是否已传入
			if ( array_intersect_key($params_required, array_keys($params)) !== $params_required ):
				$this->result['status'] = 440;
				$this->result['content']['error']['message'] = '必要的签名参数未全部传入；安全起见不做具体提示，请参考开发文档。';
			else:
				return TRUE;
			endif;
		} // end sign_check_params

		/**
         * 签名正确性检查
         */
        protected function sign_check_string()
		{
			// 获取传入的参数们
			$params = $_POST;
			unset($params['sign']); // sign本身不参与签名计算

			// 生成参数
			$sign = $this->sign_generate($params);

			// 对比签名是否正确
			if ($this->sign !== $sign):
				$this->result['status'] = 449;
				$this->result['content']['error']['message'] = '签名错误，请参考开发文档。';
				$this->result['content']['sign_expected'] = $sign;
				$this->result['content']['sign_offered'] = $this->sign;
				exit();

			else:
				return TRUE;

			endif;
		} // end sign_check_string

		/**
		 * 生成签名
		 */
        protected function sign_generate($params)
		{
			// 对参与签名的参数进行排序
			ksort($params);

			// 对随机字符串进行SHA1计算
			$params['random'] = SHA1( $params['random'] );

			// 拼接字符串
			$param_string = '';
			foreach ($params as $key => $value)
				$param_string .= '&'. $key.'='.$value;

			// 拼接密钥
			$param_string .= '&key='. API_TOKEN;

			// 计算字符串SHA1值并转为大写
			$sign = strtoupper( SHA1($param_string) );

			return $sign;
		} // end sign_generate
		
		/**
         * 更换所用数据库
         */
		protected function switch_model($table_name, $id_name)
		{
			$this->db->reset_query(); // 重置查询
			$this->basic_model->table_name = $table_name;
			$this->basic_model->id_name = $id_name;
		} // end switch_model
		
		/**
         * 还原所用数据库
         */
		protected function reset_model()
		{
			$this->db->reset_query(); // 重置查询
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end reset_model

		/**
		 * 客户端检查
		 *
		 * 根据客户端类型、版本号、平台等进行权限检查
		 */
        protected function client_check($type_allowed, $platform_allowed = NULL, $min_version = NULL)
		{
			if ( !in_array($this->app_type, $type_allowed) ):
				$this->result['status'] = 450;
				$this->result['content']['error']['message'] = '当前类型的客户端不可进行该操作';
				exit();

			elseif ( isset($platform_allowed) && !in_array($this->device_platform, $platform_allowed) ):
				$this->result['status'] = 451;
				$this->result['content']['error']['message'] = '当前软件平台的客户端不可进行该操作';
				exit();

			endif;

			// 若已限制最低版本，进行检查
			if ( isset($min_version) ):
				$min_version_array = explode('.', $min_version);
				$current_version_array = explode('.', $this->app_version);
				
				// 依次进行营销版本、功能版本、维护版本的版本号对比并进行提示
				for ($i=0; $i<3; $i++):
					if ($current_version_array[$i] < $min_version_array[$i]):
						$this->result['status'] = 452;
						$this->result['content']['error']['message'] = '当前版本的客户端不可进行该操作';
						exit();
					endif;
				endfor;

			else:
				return TRUE;

			endif;
		} // end client_check

		/**
		 * TODO 权限检查
		 *
		 * 对已登录用户，根据所需角色、所需等级等进行权限检查
		 */
		protected function permission_check($role_allowed, $min_level)
		{
			return TRUE;
		} // end permission_check

		/**
		 * 操作者有效性检查；通过操作者用户ID、密码进行验证
		 */
		protected function operator_check()
		{
			// 切换数据库
			$this->switch_model('user', 'user_id');

			// 尝试获取复合条件的数据
			$data_to_search = array(
				'user_id' => $this->input->post('user_id'),
				'password' => sha1($this->input->post('password')),
			);
			$result = $this->basic_model->match($data_to_search);

			// 重置数据库
			$this->reset_model();

			if ( !empty($result) ):
				return TRUE;
			else:
				return FALSE;
			endif;
		} // end operator_check

        /**
         * 生成根据筛选项
         *
         * 根据names_to_sort类属性生成筛选条件
         *
         * @param array $condition 默认筛选条件
         * @return array
         */
        protected function condition_generate($condition = array())
        {
            // 遍历筛选条件
            foreach ($this->names_to_sort as $sorter):
                if ( in_array($sorter, $this->min_needed) || in_array($sorter, $this->max_needed) || !empty($this->input->post($sorter))):

                    // 若可筛选最小值（含），尝试获取传入的相应字段值
                    if ( in_array($sorter, $this->min_needed) ) $condition[$sorter.' >='] = $this->input->post($sorter.'_min');

                    // 若可筛选最大值（含），尝试获取传入的相应字段值
                    if ( in_array($sorter, $this->max_needed) ) $condition[$sorter.' <='] = $this->input->post($sorter.'_max');

                    $condition[$sorter] = $this->input->post($sorter);

                endif;
            endforeach;

            // 清理空元素并返回筛选条件
            return array_filter($condition);
        } // end condition_generate

        /**
         * edit_bulk类型方法通用代码块
         *
         * @param bool $need_password 是否需要验证密码格式；默认FALSE
         * @param string $operations 可执行操作；默认删除delete、恢复restore
         */
        protected function common_edit_bulk($need_password = FALSE, $operations = 'delete,restore')
        {
            // 初始化并配置表单验证库
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('', '');
            $this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]');
            $this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');

            $this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list['.$operations.']');

            // 若需密码，验证密码格式
            if ($need_password === TRUE)
                $this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
        } // end common_edit_bulk

        /**
         * 拆分CSV为数组
         */
        protected function explode_csv($text, $seperator = ',')
        {
            // 清理可能存在的空字符、冗余分隔符
            $text = trim($text);
            $text = trim($text, $seperator);

            // 拆分文本为数组并清理可被转换为布尔型FALSE的数组元素（空数组、空字符、NULL、0、’0‘等）
            $array = array_filter( explode($seperator, $text) );

            return $array;
        } // end explode_csv

        /**
         * 将可读日期转为精确到分钟的Unix时间戳
         *
         * @param $time_string 'Y-m-d H:i'或'Y-m-d H:i:s'格式，例如2018-01-01 06:06:06
         * @return string
         */
        protected function strto_minute($time_string)
        {
            if (strlen($time_string) === 16):
                $timestamp = strtotime($time_string. ':00');
            else:
                $timestamp = strtotime(substr($time_string, 0, 16) .':00');
            endif;

            return $timestamp;
        } // end strto_minute

        /**
         * 检查起始时间
         *
         * 用于表单格式验证
         *
         * @param $value
         * @param string $max_time_name 不可晚于的UNIX时间戳字段值
         * @return bool
         */
        public function time_start($value, $min_time_name = 'time_end')
        {
            if ( empty($value) ):
                return true;

            // 须为UNIX时间戳
            elseif (strlen($value) !== 10):
                return false;

            // 若已设置结束时间，不可晚于该时间
            elseif (
                !empty($max_time_name)
                && !empty( $this->input->post($max_time_name) )
                && $value > $this->input->post($max_time_name)
            ):
                return false;

            else:
                return true;

            endif;
        } // end time_start

        /**
         * 检查结束时间
         *
         * 用于表单格式验证
         * @param $value
         * @param string $min_time_name 不可早于的UNIX时间戳字段值
         * @return bool
         */
        public function time_end($value, $min_time_name = 'time_start')
        {
            if ( empty($value) ):
                return true;

            elseif (strlen($value) !== 10):
                return false;

            // 若已设置开始时间，不可早于该时间
            elseif (
                !empty($min_time_name)
                && !empty( $this->input->post($min_time_name) )
                && $value < $this->input->post($min_time_name)
            ):
                return false;

            else:
                return true;

            endif;
        } // end time_end

        /**
         * 发送短信
         *
         * @param $mobile
         * @param $content
         */
		protected function sms_send($mobile, $content)
		{
			$this->load->library('luosimao');
			$result = $this->luosimao->send($mobile, $content);
			if ($this->input->post('test_mode') == 'on') var_dump($result);
		} // end sms_send

		/**
		 * 高德地图 将地址文字转换为经纬度
		 *
		 * http://lbs.amap.com/api/webservice/guide/api/georegeo
		 */
		protected function amap_geocode($address, $city = '青岛')
		{
			$api_key = AMAP_KEY_SERVER; // 高德key
			$api_url = 'http://restapi.amap.com/v3/geocode/geo?key='. $api_key. '&address='.urlencode($address). '&city='.urlencode($city);
			$params = NULL;

			// 获取经纬度信息
			$this->load->library('curl');
			$result = $this->curl->go($api_url, $params, 'array', 'get');
			if ( $result['status'] === '1'):
				$location_set = $result['geocodes'][0]['location'];

				// 拆分经纬度信息文本为数组
				$location_set = explode(',', $location_set);
				list($location['longitude'], $location['latitude']) = $location_set;

				return $location;

			else:
				return FALSE;

			endif;
		} // end amap_geocode

        /**
         * 推送消息
         *
         * @param $message
         * @param $type
         */
        protected function push_send($message, $type)
        {
            // 推送系统通知
            $this->load->library('getui');

            // 获取并记录auth_token
            $result = $this->getui->auth_sign();
            $this->getui->auth_token = $result['auth_token'];

            // 群推消息
            $result = $this->getui->push_app($message, $type);

            if ( empty($result) || $result['result'] !== 'ok'):
                $this->result['content']['push_result'] = '推送失败，原因为：'.$result['result'];
            else:
                $this->result['content']['push_task_id'] = $result['taskid'];
            endif;

        } // end push_send

		/**
         * 解析购物车
         *
         * @param string $current_cart 购物车内容
         */
		protected function cart_decode($current_cart)
		{
			// 检查购物车是否为空，若空则直接返回相应提示，否则显示购物车详情
			if ( !empty($current_cart) ):
				// 初始化商品信息数组
				$items_to_create = array();

				// 拆分各商品信息
				$cart_items = $this->explode_csv($current_cart);
				foreach ($cart_items as $cart_item):
					// 分解出item_id、sku_id、count等
					list($biz_id, $item_id, $sku_id, $count) = explode('|', $cart_item);
					$items_to_create[] = array(
						'biz_id' => $biz_id,
						'item_id' => $item_id,
						'sku_id' => ($sku_id === '0')? NULL: $sku_id,
						'count' => $count,
					);
				endforeach;

                // 无效项
                $this->result['content']['invalid_items'] = array();
                $this->result['content']['invalid_item_ids'] = '';

				// 生成订单单品信息
				foreach ($items_to_create as $item_to_create):
					$this->generate_single_item($item_to_create['item_id'], $item_to_create['sku_id'], $item_to_create['count']);
				endforeach;

			endif;
		} // end cart_decode

		/**
		 * 生成单品订单信息
		 *
		 * @param varchar/int $item_id 商品ID；商家ID需要从商品资料中获取
		 * @param varchar/int $sku_id 规格ID
		 * @param int $count 份数；默认为1，但有每单最低限量的情况下允许传入count
		 */
		private function generate_single_item($item_id, $sku_id = NULL, $count = 1)
		{
            // 获取规格信息
            if ( !empty($sku_id) ):
                $this->switch_model('sku', 'sku_id');
                $sku = $this->basic_model->select_by_id($sku_id);
                //var_dump($sku);

                // 若未获取到规格信息，或不可购买，则不继续以下逻辑
                if (empty($sku) || !empty($sku['time_delete'])):
                    $this->result['content']['invalid_items'][] = array(
                        'type' => 'sku',
                        'id' => $sku_id,
                        'message' => '规格未开售或不存在',
                    );
                    $this->result['content']['invalid_item_ids'] .= $item_id.',';
                    return;
                elseif ( empty($sku['stocks']) ):
                    $this->result['content']['invalid_items'][] = array(
                        'type' => 'sku',
                        'id' => $sku_id,
                        'message' => $sku['name_first'].$sku['name_second'].$sku['name_third'].'规格已售罄',
                    );
                    $this->result['content']['invalid_item_ids'] .= $item_id.',';
                    return;
                endif;

                // 若已获取规格信息，则以规格信息中的item_id覆盖传入的item_id
                $item_id = $sku['item_id'];
            endif;

			// 获取商品信息
            $this->switch_model('item', 'item_id');
            $item = $this->basic_model->select_by_id($item_id);
            //var_dump($item);

            // 若未获取到商品信息，或不可购买，则不继续以下逻辑
            $sku_and_item_no_stock = empty($item['stocks']) && empty($sku['stocks']);
            $item_not_published = empty($item['time_publish']) || !empty($item['time_delete']);
            if (empty($item)):
                $this->result['content']['invalid_items'][] = array(
                    'type' => 'item',
                    'id' => $item_id,
                    'message' => '商品不存在',
                );
                $this->result['content']['invalid_item_ids'] .= $item_id.',';
                return;
            elseif ($item_not_published):
                $this->result['content']['invalid_items'][] = array(
                    'type' => 'item',
                    'id' => $item_id,
                    'message' => '商品未开售',
                );
                $this->result['content']['invalid_item_ids'] .= $item_id.',';
                return;
            elseif ($sku_and_item_no_stock):
                $this->result['content']['invalid_items'][] = array(
                    'type' => 'item',
                    'id' => $item_id,
                    'message' => '商品已售罄',
                );
                $this->result['content']['invalid_item_ids'] .= $item_id.',';
                return;
            endif;

			// 生成订单商品信息
			$order_item = array(
				'biz_id' => $item['biz_id'],
				'item_id' => $item_id,
				'name' => $item['name'],
				'item_image' => $item['url_image_main'],
				'slogan' => $item['slogan'],
				'unit_name' => $item['unit_name'],
				'tag_price' => $item['tag_price'],
				'price' => $item['price'],
				'stocks' => $item['stocks'],
                'quantity_max' => $item['quantity_max'],
                'quantity_min' => $item['quantity_min'],
                'time_publish' => $item['time_publish'],
                'time_to_publish' => $item['time_to_publish'],
                'time_to_suspend' => $item['time_to_suspend'],
                'count' => $count,
			);

			// 判断当前商品/规格是否有效，并完善规格信息（若有）
			if ( !empty($sku) ):
				$order_sku = array(
					'sku_id' => $sku_id,
					'sku_name' => trim($sku['name_first']. ' '.$sku['name_second']. ' '.$sku['name_third']),
					'sku_image' => $sku['url_image'],
					'tag_price' => $sku['tag_price'],
					'price' => $sku['price'],
                    'stocks' => $sku['stocks'],

					// 无库存、已删除，或所属商品当前未上架的规格视为失效规格
                    //'valid' => ( empty($sku['stocks']) || empty($item['time_publish']) || !empty($sku['time_delete']))? FALSE: TRUE,
                    'valid' => TRUE,
				);
				$order_item = array_merge($order_item, $order_sku);
			endif;

			// 生成订单商品信息
			$order_item['single_total'] = $order_item['price'] * $order_item['count']; // 计算当前商品应付金额（单品小计）
            $order_items[] = $order_item;

			// 若当前商家已有待创建订单，更新部分订单信息及订单商品信息
			$need_to_create = TRUE;
			if ( ! empty($this->order_data) ):
				for ($i=0;$i<count($this->order_data);$i++):
					if ( !empty($this->order_data[$i]) ):

						if ($this->order_data[$i]['biz_id'] === $order_item['biz_id']):
							$this->order_data[$i]['subtotal'] += $order_item['single_total'];
							$this->order_data[$i]['total'] += $order_item['single_total'];
							$this->order_data[$i]['order_items'] = array_merge($this->order_data[$i]['order_items'], $order_items);
							$need_to_create = FALSE; // 无需新建待创建订单
						endif;

					endif;
				endfor;
			endif;

			// 若当前商家没有待创建订单，新建待创建订单
			if ($need_to_create === TRUE):
				// 获取商家信息
				$this->switch_model('biz', 'biz_id');
				$biz = $this->basic_model->select_by_id($item['biz_id']);

                // 获取商家运费模板信息
                $this->switch_model('freight_template_biz', 'template_id');
                $conditions = array(
                    'biz_id' => $order_item['biz_id'],
                    'time_delete' => 'NULL',
                );
                $freight_templates = $this->basic_model->select($conditions, NULL, FALSE, FALSE); // 不获取已删除项
                //var_dump($freight_templates);

				$this->order_data[] = array(
					'biz_id' => $order_item['biz_id'],
					'biz_name' => $biz['brief_name'],
					'biz_url_logo' => $biz['url_logo'],
					'subtotal' => $order_item['single_total'],
					'total' => $order_item['single_total'],
					'freight_templates' => $freight_templates,
					'order_items' => $order_items,
				);

			endif;
		} // end generate_single_item

        /**
         * 获取信息列表
         *
         * @param string $table_name 信息所属表名
         * @param string $table_id 信息所属表ID
         * @param array $condition 筛选条件
         * @param array $order_by 排序条件
         * @param boolean $ids_only 是否仅需返回CSV格式的主键ID
         * @return mixed
         */
        protected function get_items($table_name = 'item', $table_id = 'item_id', $condition = array(), $order_by = array(), $return_ids = FALSE, $allow_deleted = TRUE)
        {
            // 初始化数据表
            $this->switch_model($table_name, $table_id);

            $result = $this->basic_model->select($condition, $order_by, $return_ids, $allow_deleted);

            $this->reset_model();
            return $result;
        } // end get_items

        /**
         * 根据ID获取特定项，默认可返回已删除项
         *
         * @param int $id 需获取的行的ID
         * @param bool $allow_deleted 是否可返回被标注为删除状态的行；默认为TRUE
         * @return array 结果行（一维数组）
         */
        public function get_item($table_name = 'item', $table_id = 'item_id', $id, $allow_deleted = TRUE)
        {
            // 初始化数据表
            $this->switch_model($table_name, $table_id);

            $result = $this->basic_model->select_by_id($id, $allow_deleted);

            $this->reset_model();
            return $result;
        } // end select_by_id

        /**
         * 更新商品的规格相关信息
         *
         * @param $sku_id
         * @param $item_id
         */
        protected function update_item_for_sku($sku_id = NULL, $item_id = NULL)
        {
            // 获取需要更新规格相关信息的商品ID
            if ($item_id.$sku_id === NULL):
                $this->result['content']['error']['message'] .= '；规格所属商品更新失败（未传入规格相关商品信息）';
            elseif ($sku_id !== NULL):
                // 获取规格信息，以获取商品信息
                $sku = $this->get_item('sku', 'sku_id', $sku_id);
                $item_id = $sku['item_id'];
            endif;

            // 获取当前规格商品所属的所有规格摘要信息
            $sku_metas = $this->db->query("SELECT SUM(`stocks`) as 'overall_stocks', MIN(`tag_price`) as 'tag_price_max', MIN(`price`) as 'price_min', MAX(`price`) as 'price_max' FROM `sku` WHERE `item_id` = ".$item_id." AND `time_delete` IS NULL")->row_array();

            // 更新商品信息
            $data_to_edit = array(
                'stocks' => $sku_metas['overall_stocks'],
                'tag_price' => $sku_metas['tag_price_max'], // 商品的price、tag_price字段值也根据规格相关信息进行更新
                'price' => $sku_metas['price_min'],
                'sku_price_min' => $sku_metas['price_min'],
                'sku_price_max' => $sku_metas['price_max'],
            );

            // 更新商品信息
            $this->switch_model('item', 'item_id');
            $result = $this->basic_model->edit($item_id, $data_to_edit);
            if ($result !== FALSE):
                $this->result['status'] = 200;
                $this->result['content']['message'] .= '；规格所属商品更新成功';

            else:
                $this->result['status'] = 434;
                $this->result['content']['error']['message'] .= '；规格所属商品更新失败';

            endif;
        } // end update_item_for_sku

	} // end class MY_Controller

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */