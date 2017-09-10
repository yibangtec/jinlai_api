<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Order/ODR 订单类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Order extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'biz_id', 'biz_name', 'biz_url_logo', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'freight', 'discount_reprice', 'repricer_id', 'total', 'credit_id', 'credit_payed', 'total_payed', 'total_refund', 'fullname', 'code_ssn', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_refund_auto', 'time_delete', 'time_edit', 'operator_id', 'status', 'refund_status', 'invoice_status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'biz_id', 'biz_name', 'biz_url_logo', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'freight', 'discount_reprice', 'repricer_id', 'total', 'credit_id', 'credit_payed', 'total_payed', 'total_refund', 'fullname', 'code_ssn', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_refund_auto', 'time_delete', 'time_edit', 'operator_id', 'status', 'refund_status', 'invoice_status'
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'address_id',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'freight', 'discount_reprice', 'repricer_id', 'total', 'total_payed', 'total_refund', 'fullname', 'mobile', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'status', 'refund_status', 'invoice_status',
		);

		/**
		 * 编辑单行特定字段时必要的字段名
		 */
		protected $names_edit_certain_required = array(
			'user_id', 'id',
			'name', 'value',
		);

		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids',
			'operation', 'password',
		);

		// 订单信息（订单创建）
		private $order_data = array();

		// 订单相关商品信息（订单创建）
		private $order_items = array();
		
		// 订单收货地址信息（订单创建）
		private $order_address = array();

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'order'; // 这里……
			$this->id_name = 'order_id'; // 这里……
			$this->names_to_return[] = 'order_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			// （可选）某些用于此类的自定义函数
		    function function_name($parameter)
			{
				//...
		    }
		}

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;

			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post_get($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'start_time'):
						$condition['time_create >='] = $this->input->post_get($sorter);
					elseif ($sorter === 'end_time'):
						$condition['time_create <='] = $this->input->post_get($sorter);
					else:
						$condition[$sorter] = $this->input->post_get($sorter);
					endif;

				endif;
			endforeach;

			// 获取列表；默认可获取已删除项
			$count = $this->basic_model->count($condition);

			if ($count !== FALSE):
				$this->result['status'] = 200;
				$this->result['content']['count'] = $count;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end count

		/**
		 * 1 列表/基本搜索
		 */
		public function index()
		{
			// 检查必要参数是否已传入
			$required_params = array();
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 筛选条件
			$condition = NULL;
			//$condition['name'] = 'value';
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) )
					$condition[$sorter] = $this->input->post($sorter);
			endforeach;
			
			// 排序条件
			$order_by = NULL;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认可获取已删除项
			$items = $this->basic_model->select($condition, $order_by);
			if ( !empty($items) ):
				$this->basic_model->table_name = 'order_items';
				$this->basic_model->id_name = 'record_id';
				for ($i=0;$i<count($items);$i++):
					// 获取订单商品
					$condition = array('order_id' => $items[$i]['order_id']);
					//var_dump($condition);
					$items[$i]['order_items'] = $this->basic_model->select($condition, NULL);
				endfor;

				$this->result['status'] = 200;
				$this->result['content'] = $items;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';
			
			endif;
		} // end index

		/**
		 * 2 详情
		 */
		public function detail()
		{
			// 检查必要参数是否已传入
			$id = $this->input->post('id');
			if ( empty($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取特定项；默认可获取已删除项
			$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				// 获取订单商品信息
				$this->basic_model->table_name = 'order_items';
				$this->basic_model->id_name = 'record_id';
				// 筛选条件
				$condition = array(
					'order_id' => $item['order_id'],
				);
				$item['order_items'] = $this->basic_model->select($condition, NULL);
				
				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

		/**
		 * 3 TODO 创建
		 */
		public function create()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			/*
			// 检查是否单品及购物车信息均未传入
			$item_id = $this->input->post('item_id');
			$cart_string = $this->input->post('cart_string');
			if ( isset($item_id) ):
				$sku_id = $this->input->post('sku_id');
				$count = $this->input->post('count')? $this->input->post('count'): 1;

			elseif ( !isset($cart_string) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
				exit();
			endif;
			*/

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required');
			$this->form_validation->set_rules('user_ip', '用户下单IP地址', 'trim');
			$this->form_validation->set_rules('address_id', '收件地址', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('note_user', '用户留言', 'trim|max_length[255]');
			// 仅单品订单涉及以下字段
			$this->form_validation->set_rules('item_id', '商品ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('sku_id', '规格ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('count', '份数', 'trim|is_natural_no_zero|less_than_equal_to[99]');
			// 仅购物车订单涉及以下字段
			$this->form_validation->set_rules('cart_string', '购物车内容', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			// 若订单数据生成失败
			elseif ($this->generate_order_data() === FALSE):
				$this->result['status'] = 411;
				$this->result['content']['error']['message'] = $this->order_data['content']['error']['message'];

			// 获取地址信息
			elseif ($this->get_address($address_id, $user_id) === FALSE):
				$this->result['status'] = 411;
				$this->result['content']['error']['message'] = '收货地址不可用';

			else:
				//var_dump($this->order_items);
				//var_dump($this->order_data);
				//var_dump($this->order_address);

				// 生成通用订单数据
				$common_meta = array(
					'user_id' => $user_id,
					'user_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'), // 优先检查请求是否来自APP
					'note_user' => $this->input->post('note_user'),
					'time_create' => time(),
				);
				// 合并通用订单数据及地址数据
				$common_meta = array_merge($common_meta, $this->order_address);
				unset($this->order_address);

				// 计算待生成订单总数，即商家数
				$bizs_count = count($this->order_data);

				// 以商家为单位生成订单
				for ($i=0; $i<$bizs_count; $i++):
					// 合并通用订单及每笔订单数据
					$data_to_create = array_merge($common_meta, $this->order_data[$i]);
					//var_dump($data_to_create);

					// 取出订单商品数据
					$order_items = $data_to_create['order_items'];
					unset($data_to_create['order_items']);
					//var_dump($order_items);

					// 创建订单
					$result = $this->basic_model->create($data_to_create, TRUE);
					if ($result !== FALSE):
						$order_id = $result; // 获取被创建的订单号
						$this->result['status'] = 200;
						$this->result['content']['id'] = $order_id;
						$this->result['content']['message'] = '创建成功';

						// 创建订单商品
						$this->basic_model->table_name = 'order_items';
						$this->basic_model->id_name = 'record_id';
						foreach ($order_items as $order_item):
							$order_item['order_id'] = $order_id;
							$order_item['user_id'] = $user_id;
							$order_item['time_create'] = time();
							$result = $this->basic_model->create($order_item, TRUE);
						endforeach;
  
					else:
						$this->result['status'] = 424;
						$this->result['content']['error']['message'] = '创建失败';

					endif;
				endfor;
			endif;
		} // end create

		// TODO 生成订单数据
		private function generate_order_data()
		{
			// 只要传入了商品ID，即视为单品订单
			$item_id = $this->input->post('item_id'); // 获取商品ID
			if ( !empty($item_id) ):
				$item_id = $this->input->post('item_id'); // 获取商品ID
				$sku_id = empty($this->input->post('sku_id'))? NULL: $this->input->post('sku_id'); // 获取规格ID（若有）
				$count = empty($this->input->post('count'))? 1: $this->input->post('count'); // 获取数量

				$this->generate_single_item($item_id, $sku_id, $count);
				// 重置数据库参数
				$this->basic_model->table_name = $this->table_name;
				$this->basic_model->id_name = $this->id_name;

			// TODO 生成多品订单
			elseif ( !empty($this->input->post('cart_string')) ):
				//$this->generate_multiple_items();
				//$items_to_create = $this->parse_cart( $this->input->post('cart_string') );
				$items_to_create = array(
					array(
						'biz_id' => 2,
						'item_id' => 6,
						'sku_id' => NULL,
						'count' => 3,
					),
				);
				
				for ($i=0;$i<count($items_to_create);$i++):
					$this->generate_single_item($items_to_create[$i]['item_id'], $items_to_create[$i]['sku_id'], $items_to_create[$i]['count']);
				endfor;

				// 重置数据库参数
				$this->basic_model->table_name = $this->table_name;
				$this->basic_model->id_name = $this->id_name;

			else:
				return FALSE;

			endif;
		}

		/**
		 * TODO 生成单品订单
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

			// 获取需要写入订单信息的商家信息
			$this->basic_model->table_name = 'biz';
			$this->basic_model->id_name = 'biz_id';
			$biz = $this->basic_model->select_by_id($item['biz_id']);

			// 获取规格信息
			if ( !empty($sku_id) ):
				$this->basic_model->table_name = 'sku';
				$this->basic_model->id_name = 'sku_id';
				$sku = $this->basic_model->select_by_id($sku_id);
			endif;

			//TODO 计算单品优惠活动折抵
			//TODO 计算单品优惠券折抵
			//TODO 计算单品运费
			// 生成订单商品信息
			$order_item = array(
				'biz_id' => $item['biz_id'],
				'item_id' => $item_id,
				'name' => $item['name'],
				'item_image' => $item['url_image_main'],
				'slogan' => $item['slogan'],
				'tag_price' => $item['tag_price'],
				'price' => $item['price'],
				'count' => $count,

				//'promotion_id' => $item['promotion_id'], // 营销活动ID
				//'discount_promotion' => $discount_promotion, // 营销活动折抵金额

				//'coupon_id' => $item['coupon_id'], // 优惠券ID
				//'discount_coupon' => $discount_coupon, // 优惠券折抵金额
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
			//$this->order_items[] = $order_item;
			$order_item['single_total'] = $order_item['price'] * $order_item['count']; // 计算当前商品应付金额
			$order_items[] = array_filter($order_item);


			//TODO 计算商家优惠活动折抵
			//TODO 计算商家优惠券折抵
			//TODO 计算商家运费
			// 生成订单信息
			$this->order_data[] = array(
				'biz_id' => $order_item['biz_id'],
				'biz_name' => $biz['brief_name'],
				'biz_url_logo' => $biz['url_logo'],
				'subtotal' => $order_item['single_total'],

				//'promotion_id' => $item['promotion_id'], // 营销活动ID
				//'discount_promotion' => $discount_promotion, // 营销活动折抵金额

				//'coupon_id' => $item['coupon_id'], // 优惠券ID
				//'discount_coupon' => $discount_coupon, // 优惠券折抵金额

				//'freight' => $freight,
				'total' => $order_item['single_total'],
				'order_items' => $order_items,
			);
		}

		/**
		 * TODO 生成多品订单
		 */
		private function generate_multiple_items()
		{
			$cart_string = $this->input->post('cart_string');

			//TODO 检查是否有相同商家的商品
			if ( !array_key_exists($item['biz_id'], $this->order_data) ):
				NULL;
			else:
				NULL;
			endif;
		}
		
		// 获取特定地址信息
		private function get_address($id, $user_id)
		{
			// 从API服务器获取相应列表信息
			$conditions = array(
				'address_id' => $id,
				'user_id' => $user_id,
				'time_delete' => NULL,
			);

			$this->basic_model->table_name = 'address';
			$this->basic_model->id_name = 'address_id';

			$result = $this->basic_model->match($conditions);

			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			if ( !empty($result) ):
				$this->order_address = array(
					'fullname' => $result['fullname'],
					'mobile' => $result['mobile'],
					'province' => $result['province'],
					'city' => $result['city'],
					'county' => $result['county'],
					'street' => $result['street'],
					'longitude' => $result['longitude'],
					'latitude' => $result['latitude'],
				);

			else:

				return FALSE;
			endif;
		} // end get_address

		/**
		 * 4 编辑单行数据
		 */
		public function edit()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('subtotal', '小计（元）', 'trim|required');
			$this->form_validation->set_rules('promotion_id', '营销活动ID', 'trim');
			$this->form_validation->set_rules('discount_promotion', '营销活动折抵金额（元）', 'trim');
			$this->form_validation->set_rules('coupon_id', '优惠券ID', 'trim');
			$this->form_validation->set_rules('discount_coupon', '优惠券折抵金额（元）', 'trim');
			$this->form_validation->set_rules('credit_id', '积分流水ID', 'trim');
			$this->form_validation->set_rules('freight', '运费（元）', 'trim');
			$this->form_validation->set_rules('discount_reprice', '改价折抵金额（元）', 'trim');
			$this->form_validation->set_rules('total', '应支付金额（元）', 'trim|required');
			$this->form_validation->set_rules('total_payed', '实际支付金额（元）', 'trim');
			$this->form_validation->set_rules('total_refund', '实际退款金额（元）', 'trim');
			$this->form_validation->set_rules('note_stuff', '员工留言', 'trim');
			// 针对特定条件的验证规则
			if ($this->app_type === '管理员'):
				// ...
			endif;

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
					//'name' => $this->input->post('name'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'freight', 'discount_reprice', 'total', 'total_payed', 'total_refund', 'fullname', 'mobile', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'note_stuff',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = $this->input->post($name);

				// 根据客户端类型等条件筛选可操作的字段名
				if ($this->app_type !== 'admin'):
					//unset($data_to_edit['name']);
				endif;

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑失败';

				endif;
			endif;
		} // end edit
		
		/**
		 * 5 编辑单行数据特定字段
		 *
		 * 修改单行数据的单一字段值
		 */
		public function edit_certain()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_certain_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( $param !== 'value' && !isset( ${$param} ) ): // value 可以为空；必要字段会在字段验证中另行检查
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 检查目标字段是否可编辑
			if ( ! in_array($name, $this->names_edit_allowed) ):
				$this->result['status'] = 431;
				$this->result['content']['error']['message'] = '该字段不可被修改';
				exit();
			endif;

			// 根据客户端类型检查是否可编辑
			/*
			$names_limited = array(
				'biz' => array('name1', 'name2', 'name3', 'name4'),
				'admin' => array('name1', 'name2', 'name3', 'name4'),
			);
			if ( in_array($name, $names_limited[$this->app_type]) ):
				$this->result['status'] = 432;
				$this->result['content']['error']['message'] = '该字段不可被当前类型的客户端修改';
				exit();
			endif;
			*/

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
			$this->form_validation->set_rules('subtotal', '小计（元）', 'trim|required');
			$this->form_validation->set_rules('promotion_id', '营销活动ID', 'trim');
			$this->form_validation->set_rules('discount_promotion', '营销活动折抵金额（元）', 'trim');
			$this->form_validation->set_rules('coupon_id', '优惠券ID', 'trim');
			$this->form_validation->set_rules('discount_coupon', '优惠券折抵金额（元）', 'trim');
			$this->form_validation->set_rules('credit_id', '积分流水ID', 'trim');
			$this->form_validation->set_rules('freight', '运费（元）', 'trim');
			$this->form_validation->set_rules('discount_reprice', '改价折抵金额（元）', 'trim');
			$this->form_validation->set_rules('total', '应支付金额（元）', 'trim|required');
			$this->form_validation->set_rules('total_payed', '实际支付金额（元）', 'trim');
			$this->form_validation->set_rules('total_refund', '实际退款金额（元）', 'trim');
			$this->form_validation->set_rules('fullname', '姓名', 'trim|required');
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required');
			$this->form_validation->set_rules('province', '省份', 'trim|required');
			$this->form_validation->set_rules('city', '城市', 'trim|required');
			$this->form_validation->set_rules('county', '区/县', 'trim|required');
			$this->form_validation->set_rules('street', '具体地址', 'trim|required');
			$this->form_validation->set_rules('longitude', '经度', 'trim');
			$this->form_validation->set_rules('latitude', '纬度', 'trim');
			$this->form_validation->set_rules('note_stuff', '员工留言', 'trim');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;

				// 获取ID
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑失败';

				endif;
			endif;
		} // end edit_certain

		/**
		 * 6 编辑多行数据特定字段
		 *
		 * 修改多行数据的单一字段值
		 */
		public function edit_bulk()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_bulk_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore]');
			$this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			elseif ($this->operator_check() !== TRUE):
				$this->result['status'] = 453;
				$this->result['content']['error']['message'] = '与该ID及类型对应的操作者不存在，或操作密码错误';
				exit();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit['operator_id'] = $user_id;

				// 根据待执行的操作赋值待编辑数据
				switch ( $operation ):
					case 'delete':
						$data_to_edit['time_delete'] = date('Y-m-d H:i:s');
						break;
					case 'restore':
						$data_to_edit['time_delete'] = NULL;
						break;
				endswitch;

				// 依次操作数据并输出操作结果
				// 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
				$ids = explode(',', $ids);

				// 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
				$this->result['status'] = 200;
				foreach ($ids as $id):
					$result = $this->basic_model->edit($id, $data_to_edit);
					if ($result === FALSE):
						$this->result['status'] = 434;
						$this->result['content']['row_failed'][] = $id;
					endif;

				endforeach;

				// 添加全部操作成功后的提示
				if ($this->result['status'] = 200)
					$this->result['content']['message'] = '全部操作成功';

			endif;
		} // end edit_bulk
		
		/**
		 * 用户取消
		 *
		 * time_cancel、status
		 */
		public function cancel()
		{
			$data_to_edit = array(
				'time_cancel' => time(),
				'status' => '已取消',
			);
		} // end cancel
		
		/**
		 * 商家拒绝
		 *
		 * time_refuse、status
		 */
		public function refuse()
		{
			$data_to_edit = array(
				'time_refuse' => time(),
				'status' => '已拒绝',
			);
		} // end refuse
		
		/**
		 * 商家接单
		 *
		 * time_accept、status
		 */
		public function accept()
		{
			$data_to_edit = array(
				'time_accept' => time(),
				'status' => '待发货',
			);
		} // end accept
		
		/**
		 * 商家发货
		 *
		 * time_deliver、status
		 */
		public function deliver()
		{
			$data_to_edit = array(
				'time_deliver' => time(),
				'status' => '待收货',
			);
		} // end deliver
		
		/**
		 * 用户确认
		 *
		 * time_confirm、status
		 */
		public function confirm()
		{
			$data_to_edit = array(
				'time_confirm' => time(),
				'status' => '待评价',
			);
			
		} // end confirm

	} // end class Order

/* End of file Order.php */
/* Location: ./application/controllers/Order.php */
