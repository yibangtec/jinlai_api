<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Order/ODR 商品订单类
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
			'biz_id', 'biz_name', 'biz_url_logo', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'freight', 'discount_reprice', 'repricer_id', 'total', 'credit_id', 'credit_payed', 'total_payed', 'total_refund', 'fullname', 'code_ssn', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'deliver_method', 'deliver_biz', 'waybill_id', 'refund_status', 'refund_id', 'invoice_status', 'invoice_id', 'time_create', 'time_create_start', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'order_id', 'biz_id', 'biz_name', 'biz_url_logo', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'freight', 'discount_reprice', 'repricer_id', 'total', 'credit_id', 'credit_payed', 'total_payed', 'total_refund', 'fullname', 'code_ssn', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'deliver_method', 'deliver_biz', 'waybill_id', 'code_string', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'status', 'refund_status', 'refund_id', 'invoice_status', 'invoice_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id', 'address_id',
		);

		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation',
		);

		// 订单信息（订单创建）
		protected $order_data = array();

		// 订单相关商品信息（订单创建）
		protected $order_items = array();
		
		// 订单收货地址信息（订单创建）
		protected $order_address = array();

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'order'; // 这里……
			$this->id_name = 'order_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;
			// 遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'time_create'):
						$condition['time_create >'] = $this->input->post($sorter);
					elseif ($sorter === 'time_create_end'):
						$condition['time_create <'] = $this->input->post($sorter);
					else:
						$condition[$sorter] = $this->input->post($sorter);
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
			// 遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'time_create'):
						$condition['time_create >'] = $this->input->post($sorter);
					elseif ($sorter === 'time_create_end'):
						$condition['time_create <'] = $this->input->post($sorter);
					else:
						$condition[$sorter] = $this->input->post($sorter);
					endif;
				endif;
			endforeach;
			
			// 排序条件
			$order_by['time_create'] = 'DESC';

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

				if ($this->app_type === 'client'):
					$this->result['content']['operations'] = $this->operations_for_client($item['status']);
				elseif ($this->app_type === 'biz'):
					$this->result['content']['operations'] = $this->operations_for_biz($item['status']);
				endif;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

		/**
		 * 3 创建
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

			// 检查是否单品及购物车信息均未传入
			$item_id = $this->input->post('item_id');
			$cart_string = $this->input->post('cart_string');
			if ( !empty($item_id) ):
				// 若为单品订单，尝试获取待下单SKU及数量
				$sku_id = $this->input->post('sku_id');
				$count = $this->input->post('count')? $this->input->post('count'): 1;

			elseif ( empty($cart_string) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
				exit();
			endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('user_ip', '用户下单IP地址', 'trim');
			$this->form_validation->set_rules('address_id', '收件地址', 'trim|required|is_natural_no_zero');
			// 仅购物车订单涉及以下字段
			$this->form_validation->set_rules('cart_string', '订单商品信息', 'trim|max_length[255]');
			// 仅单品订单涉及以下字段
			$this->form_validation->set_rules('item_id', '商品ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('sku_id', '规格ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('count', '份数', 'trim|is_natural_no_zero|less_than_equal_to[99]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			// 获取收货地址信息
			elseif ($this->get_address($address_id, $user_id) === FALSE):
				$this->result['status'] = 411;
				$this->result['content']['error']['message'] = '收货地址不可用';

			// 尝试生成订单数据
			elseif ($this->generate_order_data() === FALSE):
				$this->result['status'] = 411;
				$this->result['content']['error']['message'] = $this->order_data['content']['error']['message'];

			else:
				// 生成全局订单数据
				$common_meta = array(
					'time_create' => time(),

					'user_id' => $user_id,
					'user_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'), // 优先检查请求是否来自APP
				);
				// 合并通用订单数据及收货地址数据
				$common_meta = array_merge($common_meta, $this->order_address);
				unset($this->order_address); // 释放内存资源，下同

				// 计算待生成子订单总数，即订单相关商家数
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
					$this->reset_model();// 重置数据库参数

					$result = $this->basic_model->create($data_to_create, TRUE);
					if ($result !== FALSE):
						$order_id = $result; // 获取被创建的订单号
						$this->result['status'] = 200;
						$this->result['content']['ids'][] = $order_id;
						$this->result['content']['message'] = '创建成功';

						// 创建订单商品
						$this->switch_model('order_items', 'record_id');
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
				
				// 转换已创建订单ID数组为CSV字符串
				$this->result['content']['ids'] = implode($this->result['content']['ids'], ',');

			endif;
		} // end create

		// 生成订单数据
		private function generate_order_data()
		{
			// 只要传入了商品ID，即视为单品订单
			$item_id = $this->input->post('item_id'); // 获取商品ID
			if ( !empty($item_id) ):
				$item_id = $this->input->post('item_id'); // 获取商品ID
				$sku_id = empty($this->input->post('sku_id'))? NULL: $this->input->post('sku_id'); // 获取规格ID（若有）
				$count = empty($this->input->post('count'))? 1: $this->input->post('count'); // 获取数量

				$this->generate_single_item($item_id, $sku_id, $count);

			// 生成多品订单
			elseif ( !empty($this->input->post('cart_string')) ):
				// 初始化商品信息数组
				$items_to_create = array();

				// 拆分各商品信息
				$cart_items = $this->explode_csv( $this->input->post('cart_string') );
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

				// 生成订单单品信息
				foreach ($items_to_create as $item_to_create):
					$this->generate_single_item($item_to_create['item_id'], $item_to_create['sku_id'], $item_to_create['count']);
				endforeach;

			else:
				return FALSE;

			endif;
		} // generate_order_data

		/**
		 * 生成订单单品信息
		 *
		 * @params varchar/int $item_id 商品ID；商家ID需要从商品资料中获取
		 * @params varchar/int $sku_id 规格ID
		 * @params int $count 份数；默认为1，后续需核对每单最低限量
		 */
		private function generate_single_item($item_id, $sku_id = NULL, $count = 1)
		{
			// 获取商品信息
			$this->switch_model('item', 'item_id');
			$item = $this->basic_model->select_by_id($item_id);

			// 获取规格信息
			if ( !empty($sku_id) ):
				$this->switch_model('sku', 'sku_id');
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
			$order_item['single_total'] = $order_item['price'] * $order_item['count']; // 计算当前商品应付金额
			$order_items[] = array_filter($order_item); // 去掉空数组元素

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
			
			//TODO 计算商家优惠活动折抵
			//TODO 计算商家优惠券折抵
			//TODO 计算商家运费

			// 若当前商家没有待创建订单，新建待创建订单
			if ($need_to_create === TRUE):
				// 获取需要写入订单信息的商家信息
				$this->switch_model('biz', 'biz_id');
				$biz = $this->basic_model->select_by_id($item['biz_id']);

				$this->order_data[] = array(
					'biz_id' => $order_item['biz_id'],
					'biz_name' => $biz['brief_name'],
					'biz_url_logo' => $biz['url_logo'],
					'subtotal' => $order_item['single_total'],

					//'promotion_id' => $item['promotion_id'], // 营销活动ID
					//'discount_promotion' => $discount_promotion, // 营销活动折抵金额

					//'coupon_id' => $item['coupon_id'], // 优惠券ID
					//'discount_coupon' => $discount_coupon, // 优惠券折抵金额

					//'freight' => $freight, // 运费
					'total' => $order_item['single_total'],
					'order_items' => $order_items,
					'note_user' => $this->input->post('note_user')[$order_item['biz_id']], // 用户留言
				);
			endif;
		} // end generate_single_item

		/**
		 * 6 编辑多行数据特定字段
		 *
		 * 修改多行数据的单一字段值
		 */
		public function edit_bulk()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

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
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[cancel,note,reprice,refuse,accept,deliver,confirm,delete,restore]');
			$this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');
			// 用户确认订单、商家改价时需要输入密码
			if ($operation === 'confirm' || $operation === 'reprice')
				$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

			// 商家备注时需验证字段
			if ($operation === 'note')
				$this->form_validation->set_rules('note_stuff', '员工备注', 'trim|required|max_length[255]');

			// 商家改价时需验证字段
			if ($operation === 'reprice')
				$this->form_validation->set_rules('discount_reprice', '改价折扣金额（元）', 'trim|required|greater_than[0.01]|less_than_equal_to[99999.99]');

			// 商家发货时需验证字段
			if ($operation === 'deliver'):
				$this->form_validation->set_rules('deliver_method', '发货方式', 'trim|required|max_length[30]');
				$this->form_validation->set_rules('deliver_biz', '物流服务商', 'trim|max_length[30]');
				$this->form_validation->set_rules('waybill_id', '物流运单号', 'trim|max_length[30]alpha_numeric');
			endif;

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit['operator_id'] = $user_id;

				// 根据待执行的操作赋值待编辑数据
				switch ($operation):
					case 'cancel': // 用户取消
						$data_to_edit = array_merge($data_to_edit, $this->operation_cancel());
						break;

					case 'note': // 商家备注
						$data_to_edit = array_merge($data_to_edit, $this->operation_note());
						break;
					case 'reprice': // 商家改价
						$data_to_edit = array_merge($data_to_edit, $this->operation_reprice());
						$data_to_edit['repricer_id'] = $user_id;
						break;

					case 'refuse': // 商家拒单
						$data_to_edit = array_merge($data_to_edit, $this->operation_refuse());
						break;
					case 'accept': // 商家接单
						$data_to_edit = array_merge($data_to_edit, $this->operation_accept());
						break;

					case 'deliver': // 商家发货
						$data_to_edit = array_merge($data_to_edit, $this->operation_deliver());
						break;

					case 'confirm': // 用户收货
						$data_to_edit = array_merge($data_to_edit, $this->operation_confirm());
						break;

					case 'delete': // 用户删除待支付、已取消、已拒绝、待评价、已完成订单
						$data_to_edit['time_delete'] = date('Y-m-d H:i:s');
						break;
					case 'restore': // 仅限用户
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
		 * 7 预下单
		 *
		 * 获取订单格式的商品信息，为下单页准备
		 */
		public function prepare()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = array('user_id', 'cart_string');
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 获取可用地址信息
			$conditions = array(
				'user_id' => $user_id,
				'time_delete' => 'NULL',
			);
			$this->switch_model('address', 'address_id');
			$this->db->select('brief, fullname, mobile, nation, province, city, county, street, longitude, latitude, zipcode');
			$addresses = $this->basic_model->select($conditions, NULL);
			$this->reset_model();

			// 获取商品信息
			$this->cart_decode($cart_string);
			$order_data = $this->order_data;

			$this->result['status'] = 200;
			$this->result['content']['addresses'] = $addresses;
			$this->result['content']['order_data'] = $this->order_data;
		} // end prepare

		/**
		 * 8 商家验证
		 *
		 * 根据验证码对卡券类订单进行核销
		 */
		public function valid()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = array('biz_id', 'user_id', 'code_string');
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
			$this->form_validation->set_rules('biz_id', '所属商家ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('code_string', '卡券验证码', 'trim|required|integer|exact_length[12]');

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取订单信息
				$data_to_search = array(
					'biz_id' => $biz_id,
					'code_string' => $code_string,
					'status' => '待使用',
					'time_pay' => 'IS NOT NULL',
					'payment_type' => 'IS NOT NULL',
					'payment_account' => 'IS NOT NULL',
					'payment_id' => 'IS NOT NULL',
				);
				$order = $this->basic_model->match($data_to_search);

				if ( empty($order) ):
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '验证码无效';
					exit();

				else:
					$data_to_edit = array(
						'operator_id' => $user_id,

						'time_confirm' => time(),
						'status' => '待评价',
					);

					$result = $this->basic_model->edit($order['order_id'], $data_to_edit);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content']['message'] = '验证成功';

					else:
						$this->result['status'] = 434;
						$this->result['content']['error']['message'] = '验证失败';

					endif;
				endif;

			endif;
		} // end valid

		// 获取特定地址信息
		private function get_address($id, $user_id)
		{
			// 从API服务器获取相应列表信息
			$conditions = array(
				'address_id' => $id,
				'user_id' => $user_id,
				'time_delete' => NULL,
			);

			$this->switch_model('address', 'address_id');
			$result = $this->basic_model->match($conditions);
			$this->reset_model();

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
		 * 用户取消
		 *
		 * time_cancel、status
		 */
		private function operation_cancel()
		{
			$data_to_edit['time_cancel'] = time();
			$data_to_edit['status'] = '已取消';
			return $data_to_edit;
		} // end operation_cancel

		/**
		 * 商家备注
		 *
		 * note_stuff
		 */
		private function operation_note()
		{
			$data_to_edit['note_stuff'] = $this->input->post('note_stuff');
			return $data_to_edit;
		} // end operation_note

		/**
		 * 商家改价
		 *
		 * 需要验证密码
		 * discount_reprice、repricer_id
		 */
		private function operation_reprice()
		{
			if ($this->operator_check() !== TRUE):
				$this->result['status'] = 453;
				$this->result['content']['error']['message'] = '与该ID及类型对应的操作者不存在，或操作密码错误/未传入密码';
				exit();
			else:
				$data_to_edit['discount_reprice'] = $this->input->post('discount_reprice');
				return $data_to_edit;
			endif;
		} // end operation_reprice

		/**
		 * 商家拒单
		 *
		 * time_refuse、status
		 */
		private function operation_refuse()
		{
			$data_to_edit['time_refuse'] = time();
			$data_to_edit['status'] = '已拒绝';
			return $data_to_edit;
		} // end operation_refuse

		/**
		 * 商家接单
		 *
		 * time_accept、status
		 */
		private function operation_accept()
		{
			$data_to_edit['time_accept'] = time();
			$data_to_edit['status'] = '待发货';
			return $data_to_edit;
		} // end operation_accept
		
		/**
		 * 商家发货
		 *
		 * time_deliver、deliver_method、deliver_biz、waybill_id、status
		 */
		private function operation_deliver()
		{
			$data_to_edit['time_deliver'] = time();
			$data_to_edit['deliver_method'] = $this->input->post('deliver_method'); // 发货方式
			$data_to_edit['deliver_biz'] = $this->input->post('deliver_biz'); // 物流服务商
			$data_to_edit['waybill_id'] = $this->input->post('waybill_id'); // 物流运单号；deliver_method为自行配送或用户自提时可留空
			$data_to_edit['status'] = '待收货';
			return $data_to_edit;
		} // end operation_deliver

		/**
		 * 用户收货
		 *
		 * 需要验证密码
		 * time_confirm、status
		 */
		private function operation_confirm()
		{
			if ($this->operator_check() !== TRUE):
				$this->result['status'] = 453;
				$this->result['content']['error']['message'] = '与该ID及类型对应的操作者不存在，或操作密码错误/未传入密码';
				exit();
			else:
				$data_to_edit['time_confirm'] = time();
				$data_to_edit['status'] = '待评价';
				return $data_to_edit;
			endif;
		} // end operation_confirm
		
		// 根据订单状态，获取客户端可用操作
	    private function operations_for_client($status)
		{
			switch ($status):
				case '待付款':
					$operations = array('cancel', 'pay',);
					break;
				case '待接单':
				case '待发货':
					$operations = array('refund',);
					break;
				case '待收货':
					$operations = array('refund', 'confirm',);
					break;
				case '待使用':
					$operations = array('refund',);
					break;
				case '待评价':
					$operations = array('refund', 'comment',);
					break;
				case '已完成':
					$operations = array('refund', 'delete',);
					break;
				case '已取消':
				case '已关闭':
				case '已拒绝':
				case '已退款':
					$operations = array('delete',);
					break;
			endswitch;
			
			return $operations;
	    } // end operations_for_client

		// 根据订单状态，获取商家端可用操作
	    private function operations_for_biz($status)
		{
			switch ($status):
				case '待付款':
					$operations = array('reprice',);
					break;
				case '待接单':
					$operations = array('refuse', 'accept',);
					break;
				case '待发货':
					$operations = array('deliver',);
					break;
				case '待使用':
					$operations = array('refund',);
					break;
			endswitch;
			
			$operations[] = 'note'; // 商家可以对任何状态的订单添加备注
			return $operations;
	    } // end operations_for_biz

	} // end class Order

/* End of file Order.php */
/* Location: ./application/controllers/Order.php */
