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
			'content' => array(), // 返回内容
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

			// 如果已经打开测试模式，则输出调试信息
			if ($this->input->post('test_mode') === 'on')
				$this->output->enable_profiler(TRUE);
	    } // end __construct

		public function __destruct()
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
		} // end __destruct

		/**
		 * 签名有效性检查
		 *
		 * 依次检查签名的时间是否过期、参数是否完整、签名是否正确
		 */
		public function sign_check()
		{
			$this->sign_check_exits();
			$this->sign_check_time();
			$this->sign_check_params();
			$this->sign_check_string();
		} // end sign_check

		// 检查签名是否传入
		public function sign_check_exits()
		{
			$this->sign = $this->input->post('sign');

			if ( empty($this->sign) ):
				$this->result['status'] = 444;
				$this->result['content']['error']['message'] = '未传入签名';
				exit();
			endif;
		} // end sign_check_exits

		// 签名时间检查
		public function sign_check_time()
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

		// 签名参数检查
		public function sign_check_params()
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

		// 签名正确性检查
		public function sign_check_string()
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
		public function sign_generate($params)
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
		
		// 更换所用数据库
		protected function switch_model($table_name, $id_name)
		{
			$this->db->reset_query(); // 重置查询
			$this->basic_model->table_name = $table_name;
			$this->basic_model->id_name = $id_name;
		} // end switch_model
		
		// 还原所用数据库
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
		public function client_check($type_allowed, $platform_allowed = NULL, $min_version = NULL)
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
		public function permission_check($role_allowed, $min_level)
		{
			return TRUE;
		} // end permission_check

		/**
		 * 操作者有效性检查；通过操作者类型、ID、密码进行验证
		 */
		public function operator_check()
		{
			// 设置数据库参数
			$this->basic_model->table_name = 'user';
			$this->basic_model->id_name = 'user_id';

			// 尝试获取复合条件的数据
			$data_to_search = array(
				'user_id' => $this->input->post('user_id'),
				'password' => sha1($this->input->post('password')),
			);
			$result = $this->basic_model->match($data_to_search);

			// 还原原有数据库参数
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			if ( !empty($result) ):
				return TRUE;
			else:
				return FALSE;
			endif;
		} // end operator_check

		// 拆分CSV为数组
		protected function explode_csv($text, $seperator = ',')
		{
			// 清理可能存在的冗余分隔符及空字符
			$text = trim($text);
			$text = trim($text, $seperator);

			// 拆分文本为数组并清理可被转换为布尔型FALSE的数组元素（空数组、空字符、NULL、0、’0‘等）
			$array = array_filter( explode(',', $text) );

			return $array;
		} // end explode_csv

		/**
		 * 高德地图 将地址文字转换为经纬度
		 *
		 * http://lbs.amap.com/api/webservice/guide/api/georegeo
		 */
		public function amap_geocode($address, $city = '青岛')
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
		} // amap_geocode

		// 解析购物车
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
						'sku_id' => $sku_id,
						'count' => $count,
					);
				endforeach;

				// 生成单品信息
				foreach ($items_to_create as $item_to_create):
					$this->generate_single_item($item_to_create['item_id'], $item_to_create['sku_id'], $item_to_create['count']);
				endforeach;

			endif;
		} // end cart_decode

		/**
		 * TODO 生成单品订单信息
		 *
		 * @params varchar/int $item_id 商品ID；商家ID需要从商品资料中获取
		 * @params varchar/int $sku_id 规格ID
		 * @params int $count 份数；默认为1，但有每单最低限量的情况下允许传入count
		 */
		private function generate_single_item($item_id, $sku_id = NULL, $count = 1)
		{
			// 获取商品信息
			$this->basic_model->table_name = 'item';
			$this->basic_model->id_name = 'item_id';
			$item = $this->basic_model->select_by_id($item_id);

			// 获取规格信息
			if ( !empty($sku_id) ):
				$this->basic_model->table_name = 'sku';
				$this->basic_model->id_name = 'sku_id';
				$sku = $this->basic_model->select_by_id($sku_id);
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
				'count' => $count,
			);
			if ( !empty($sku) ):
				$order_sku = array(
					'sku_id' => $sku_id,
					'sku_name' => $sku['name_first']. $sku['name_second']. $sku['name_third'],
					'sku_image' => $sku['url_image'],
					'tag_price' => $sku['tag_price'],
					'price' => $sku['price'],
				);
				$order_item = array_merge($order_item, $order_sku);
			endif;
			// 生成订单商品信息
			$order_item['single_total'] = $order_item['price'] * $order_item['count']; // 计算当前商品应付金额
			$order_items[] = array_filter($order_item);

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
				// 获取需要写入订单信息的商家信息
				$this->basic_model->table_name = 'biz';
				$this->basic_model->id_name = 'biz_id';
				$biz = $this->basic_model->select_by_id($item['biz_id']);

				$this->order_data[] = array(
					'biz_id' => $order_item['biz_id'],
					'biz_name' => $biz['brief_name'],
					'biz_url_logo' => $biz['url_logo'],
					'subtotal' => $order_item['single_total'],
					'total' => $order_item['single_total'],
					'order_items' => $order_items,
				);
			endif;
		} // end generate_single_item

	} // end class MY_Controller