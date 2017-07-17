<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Order 商品订单类
	 *
	 * 以API服务形式返回数据列表、详情、创建、单行编辑、单/多行编辑（删除、恢复）等功能提供了常见功能的示例代码
	 * CodeIgniter官方网站 https://www.codeigniter.com/user_guide/
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
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
			'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
			'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
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
		}

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;
			//$condition['name'] = 'value';

			// （可选）遍历筛选条件
			foreach ($this->sorter_names as $sorter):
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
				if ( empty( ${$param} ) ):
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
			//$order_by['name'] = 'value';

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认可获取已删除项
			$items = $this->basic_model->select($condition, $order_by);
			if ( !empty($items) ):
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
				$this->result['status'] = 200;
				$this->result['content'] = $item;

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
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('order_id', '订单ID', 'trim|');
			$this->form_validation->set_rules('biz_id', '商户ID', 'trim|');
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|');
			$this->form_validation->set_rules('user_ip', '用户下单IP地址', 'trim|required');
			$this->form_validation->set_rules('subtotal', '小计（元）', 'trim|');
			$this->form_validation->set_rules('promotion_id', '营销活动ID', 'trim|required');
			$this->form_validation->set_rules('discount_promotion', '优惠活动折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('coupon_id', '优惠券ID', 'trim|required');
			$this->form_validation->set_rules('discount_coupon', '优惠券折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('credit_id', '积分流水ID', 'trim|required');
			$this->form_validation->set_rules('discount_credit', '积分折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('freight', '运费（元）', 'trim|required');
			$this->form_validation->set_rules('total', '应支付金额（元）', 'trim|');
			$this->form_validation->set_rules('discount_teller', '改价折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('teller_id', '改价操作者ID', 'trim|required');
			$this->form_validation->set_rules('total_payed', '实际支付金额（元）', 'trim|required');
			$this->form_validation->set_rules('total_refund', '实际退款金额（元）', 'trim|required');
			$this->form_validation->set_rules('addressee_fullname', '收件人全名', 'trim|');
			$this->form_validation->set_rules('addressee_mobile', '收件人手机号', 'trim|');
			$this->form_validation->set_rules('addressee_province', '收件人省份', 'trim|');
			$this->form_validation->set_rules('addressee_city', '收件人城市', 'trim|');
			$this->form_validation->set_rules('addressee_county', '收件人区/县', 'trim|');
			$this->form_validation->set_rules('addressee_address', '收件人详细地址', 'trim|');
			$this->form_validation->set_rules('payment_type', '付款方式', 'trim|required');
			$this->form_validation->set_rules('payment_account', '付款账号', 'trim|required');
			$this->form_validation->set_rules('payment_id', '付款流水号', 'trim|required');
			$this->form_validation->set_rules('note_user', '用户留言', 'trim|required');
			$this->form_validation->set_rules('note_stuff', '员工留言', 'trim|required');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|required');
			$this->form_validation->set_rules('commission', '佣金（元）', 'trim|required');
			$this->form_validation->set_rules('promoter_id', '推广者ID', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|');
			$this->form_validation->set_rules('time_cancel', '用户取消时间', 'trim|required');
			$this->form_validation->set_rules('time_expire', '自动过期时间', 'trim|required');
			$this->form_validation->set_rules('time_pay', '用户付款时间', 'trim|required');
			$this->form_validation->set_rules('time_refuse', '商家拒绝时间', 'trim|required');
			$this->form_validation->set_rules('time_deliver', '商家发货时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm', '用户确认时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm_auto', '系统确认时间', 'trim|required');
			$this->form_validation->set_rules('time_comment', '用户评价时间', 'trim|required');
			$this->form_validation->set_rules('time_refund', '商家退款时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '用户删除时间', 'trim|required');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|required');
			$this->form_validation->set_rules('invoice_status', '发票状态', 'trim|');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,
					//'name' => $this->input->post('name')),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = $this->input->post($name);

				$result = $this->basic_model->create($data_to_create, TRUE);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $result;
					$this->result['content']['message'] = '创建成功';

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建失败';

				endif;
			endif;
		} // end create

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
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('order_id', '订单ID', 'trim|');
			$this->form_validation->set_rules('biz_id', '商户ID', 'trim|');
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|');
			$this->form_validation->set_rules('user_ip', '用户下单IP地址', 'trim|required');
			$this->form_validation->set_rules('subtotal', '小计（元）', 'trim|');
			$this->form_validation->set_rules('promotion_id', '营销活动ID', 'trim|required');
			$this->form_validation->set_rules('discount_promotion', '优惠活动折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('coupon_id', '优惠券ID', 'trim|required');
			$this->form_validation->set_rules('discount_coupon', '优惠券折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('credit_id', '积分流水ID', 'trim|required');
			$this->form_validation->set_rules('discount_credit', '积分折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('freight', '运费（元）', 'trim|required');
			$this->form_validation->set_rules('total', '应支付金额（元）', 'trim|');
			$this->form_validation->set_rules('discount_teller', '改价折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('teller_id', '改价操作者ID', 'trim|required');
			$this->form_validation->set_rules('total_payed', '实际支付金额（元）', 'trim|required');
			$this->form_validation->set_rules('total_refund', '实际退款金额（元）', 'trim|required');
			$this->form_validation->set_rules('addressee_fullname', '收件人全名', 'trim|');
			$this->form_validation->set_rules('addressee_mobile', '收件人手机号', 'trim|');
			$this->form_validation->set_rules('addressee_province', '收件人省份', 'trim|');
			$this->form_validation->set_rules('addressee_city', '收件人城市', 'trim|');
			$this->form_validation->set_rules('addressee_county', '收件人区/县', 'trim|');
			$this->form_validation->set_rules('addressee_address', '收件人详细地址', 'trim|');
			$this->form_validation->set_rules('payment_type', '付款方式', 'trim|required');
			$this->form_validation->set_rules('payment_account', '付款账号', 'trim|required');
			$this->form_validation->set_rules('payment_id', '付款流水号', 'trim|required');
			$this->form_validation->set_rules('note_user', '用户留言', 'trim|required');
			$this->form_validation->set_rules('note_stuff', '员工留言', 'trim|required');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|required');
			$this->form_validation->set_rules('commission', '佣金（元）', 'trim|required');
			$this->form_validation->set_rules('promoter_id', '推广者ID', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|');
			$this->form_validation->set_rules('time_cancel', '用户取消时间', 'trim|required');
			$this->form_validation->set_rules('time_expire', '自动过期时间', 'trim|required');
			$this->form_validation->set_rules('time_pay', '用户付款时间', 'trim|required');
			$this->form_validation->set_rules('time_refuse', '商家拒绝时间', 'trim|required');
			$this->form_validation->set_rules('time_deliver', '商家发货时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm', '用户确认时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm_auto', '系统确认时间', 'trim|required');
			$this->form_validation->set_rules('time_comment', '用户评价时间', 'trim|required');
			$this->form_validation->set_rules('time_refund', '商家退款时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '用户删除时间', 'trim|required');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|required');
			$this->form_validation->set_rules('invoice_status', '发票状态', 'trim|');
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
					//'name' => $this->input->post('name')),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'order_id', 'biz_id', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'credit_id', 'discount_credit', 'freight', 'total', 'discount_teller', 'teller_id', 'total_payed', 'total_refund', 'addressee_fullname', 'addressee_mobile', 'addressee_province', 'addressee_city', 'addressee_county', 'addressee_address', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission_rate', 'commission', 'promoter_id', 'time_create', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'invoice_status',
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
				if ( $param !== 'value' && empty( ${$param} ) ): // value 可以为空；必要字段会在字段验证中另行检查
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
			$this->form_validation->set_rules('order_id', '订单ID', 'trim|');
			$this->form_validation->set_rules('biz_id', '商户ID', 'trim|');
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|');
			$this->form_validation->set_rules('user_ip', '用户下单IP地址', 'trim|required');
			$this->form_validation->set_rules('subtotal', '小计（元）', 'trim|');
			$this->form_validation->set_rules('promotion_id', '营销活动ID', 'trim|required');
			$this->form_validation->set_rules('discount_promotion', '优惠活动折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('coupon_id', '优惠券ID', 'trim|required');
			$this->form_validation->set_rules('discount_coupon', '优惠券折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('credit_id', '积分流水ID', 'trim|required');
			$this->form_validation->set_rules('discount_credit', '积分折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('freight', '运费（元）', 'trim|required');
			$this->form_validation->set_rules('total', '应支付金额（元）', 'trim|');
			$this->form_validation->set_rules('discount_teller', '改价折抵金额（元）', 'trim|required');
			$this->form_validation->set_rules('teller_id', '改价操作者ID', 'trim|required');
			$this->form_validation->set_rules('total_payed', '实际支付金额（元）', 'trim|required');
			$this->form_validation->set_rules('total_refund', '实际退款金额（元）', 'trim|required');
			$this->form_validation->set_rules('addressee_fullname', '收件人全名', 'trim|');
			$this->form_validation->set_rules('addressee_mobile', '收件人手机号', 'trim|');
			$this->form_validation->set_rules('addressee_province', '收件人省份', 'trim|');
			$this->form_validation->set_rules('addressee_city', '收件人城市', 'trim|');
			$this->form_validation->set_rules('addressee_county', '收件人区/县', 'trim|');
			$this->form_validation->set_rules('addressee_address', '收件人详细地址', 'trim|');
			$this->form_validation->set_rules('payment_type', '付款方式', 'trim|required');
			$this->form_validation->set_rules('payment_account', '付款账号', 'trim|required');
			$this->form_validation->set_rules('payment_id', '付款流水号', 'trim|required');
			$this->form_validation->set_rules('note_user', '用户留言', 'trim|required');
			$this->form_validation->set_rules('note_stuff', '员工留言', 'trim|required');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|required');
			$this->form_validation->set_rules('commission', '佣金（元）', 'trim|required');
			$this->form_validation->set_rules('promoter_id', '推广者ID', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|');
			$this->form_validation->set_rules('time_cancel', '用户取消时间', 'trim|required');
			$this->form_validation->set_rules('time_expire', '自动过期时间', 'trim|required');
			$this->form_validation->set_rules('time_pay', '用户付款时间', 'trim|required');
			$this->form_validation->set_rules('time_refuse', '商家拒绝时间', 'trim|required');
			$this->form_validation->set_rules('time_deliver', '商家发货时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm', '用户确认时间', 'trim|required');
			$this->form_validation->set_rules('time_confirm_auto', '系统确认时间', 'trim|required');
			$this->form_validation->set_rules('time_comment', '用户评价时间', 'trim|required');
			$this->form_validation->set_rules('time_refund', '商家退款时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '用户删除时间', 'trim|required');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|required');
			$this->form_validation->set_rules('invoice_status', '发票状态', 'trim|');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;

				// 获取ID
				$id = $this->input->post('id');
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
				if ( empty( ${$param} ) ):
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
		

	}

/* End of file Order.php */
/* Location: ./application/controllers/Order.php */
