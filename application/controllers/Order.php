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
			'biz_id', 'biz_name', 'biz_url_logo', 'user_id', 'user_ip', 'subtotal', 'promotion_id', 'discount_promotion', 'coupon_id', 'discount_coupon', 'freight', 'discount_reprice', 'repricer_id', 'total', 'credit_id', 'credit_payed', 'total_payed', 'total_refund', 'fullname', 'code_ssn', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'payment_type', 'payment_account', 'payment_id', 'note_user', 'note_stuff', 'commission', 'promoter_id', 'deliver_method', 'deliver_biz', 'waybill_id', 'invoice_status', 'invoice_id',
            'time_create', 'time_create_start', 'time_cancel', 'time_expire', 'time_pay', 'time_refuse', 'time_accept', 'time_deliver', 'time_confirm', 'time_confirm_auto', 'time_comment', 'time_refund', 'time_delete', 'time_edit', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
		    'user_id',
            'address_id',
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

		// 用户留言信息（订单创建）
		protected $note_user = array();

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
            // 生成筛选条件
            $condition = $this->condition_generate();

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
				${$param} = trim($this->input->post($param));
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

            // 生成筛选条件
            $condition = $this->condition_generate();

            // 排序条件
            $order_by['time_create'] = 'DESC';

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 商家端若未请求特定状态的订单，则不返回部分状态的订单
                if ($this->app_type === 'biz' && empty($this->input->post('status'))):
                    $this->db->where_not_in($this->table_name.'.status', array('已取消', '已拒绝', '已关闭'));
                endif;

                $this->load->model('order_model');

                // TODO 仅获取有商品在退款中（待处理、待退货、待退款）的订单
                if ($this->input->post('status') === '退款中'):
                    $items = $this->order_model->select_refunding($condition, $order_by, FALSE, $this->app_type == 'biz');

                else:
                    $items = $this->order_model->select($condition, $order_by, FALSE, $this->app_type == 'biz');

                endif;
                //$items = $this->order_model->select($condition, $order_by, FALSE, $this->app_type == 'biz');
            else:
                $items = $this->basic_model->select_by_ids($ids);
            endif;

			// 获取列表；默认可获取已删除项
			if ( !empty($items) ):
                if ( empty($ids) ):
                    // 获取订单商品记录
                    $this->switch_model('order_items', 'record_id');
                    $this->basic_model->limit = $this->basic_model->offset = 0;
                    for ($i=0;$i<count($items);$i++):
                        $condition = array('order_id' => $items[$i]['order_id']);
                        $items[$i]['order_items'] = $this->basic_model->select($condition, NULL);
                    endfor;
                endif;

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

			// 获取特定项；默认可获取已删除项
            $this->load->model('order_model');
			$item = $this->order_model->select_by_id($id);
			if ( !empty($item) ):
				// 获取订单商品信息
                $this->switch_model('order_items', 'record_id');
				$condition = array(
					'order_id' => $item['order_id'],
				);
				$item['order_items'] = $this->basic_model->select($condition, NULL);

                // 若请求并非来自客户端，一并获取用户信息
                if ($this->app_type !== 'client'):
                    $this->switch_model('user', 'user_id');
                    $this->db->select('user_id, nickname, avatar');
                    $item['user'] = $this->basic_model->select_by_id($item['user_id']);
                endif;

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
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			$this->user_id = $user_id;

			// 检查是否单品及购物车信息均未传入
			$item_id = $this->input->post('item_id');
			$cart_string = $this->input->post('cart_string');
			if ( !empty($item_id) ):
				// 若为单品订单，尝试获取待下单规格及数量
				$sku_id = $this->input->post('sku_id');
				$count = $this->input->post('count')? $this->input->post('count'): 1;

			elseif ( empty($cart_string) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
				exit();
			endif;

			// 获取用户留言信息
            $this->note_user = json_decode($this->input->post('note_user'), TRUE);

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

			// 以商家为单位生成子订单数据
			elseif ($this->generate_order_data() === FALSE):
				$this->result['status'] = 411;
				$this->result['content']['error']['message'] = $this->order_data['content']['error']['message'];

			elseif (count($this->order_data) === 0):
                $this->result['status'] = 411;
                $this->result['content']['error']['message'] = '商品库存不足，未上架，或超出限购数量';

            else:
				// 生成全局订单数据
				$common_meta = array(
					'time_create' => time(),

					'user_id' => $user_id,
					'user_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'), // 优先检查请求是否来自APP
				);
				// 合并收货地址数据
				$common_meta = array_merge($common_meta, $this->order_address);
				unset($this->order_address); // 释放内存资源，下同

                // 计算待生成子订单总数，即订单相关商家数
                $bizs_count = count($this->order_data);

                // 以商家为单位生成订单
				for ($i=0; $i<$bizs_count; $i++):
					// 合并通用订单及每笔订单数据
					$data_to_create = array_merge($common_meta, $this->order_data[$i]);

					// 取出订单商品数据，稍后存入订单商品信息表
					$order_items = $data_to_create['order_items'];
					unset($data_to_create['order_items']);

                    // 应用用户面额最高的有效商家优惠券（若有）
                    $this->load->model('coupon_model');
                    $coupon = $this->coupon_model->get_max_valid($user_id, $data_to_create['biz_id'], $data_to_create['subtotal']);
                    if ( !empty($coupon) ):
                        // 计算优惠券抵扣额
                        $discount_coupon = !empty($coupon['amount'])? $coupon['amount']: $data_to_create['subtotal']*$coupon['rate'];

                        $data_to_create['coupon_id'] = $coupon['coupon_id'];
                        $data_to_create['discount_coupon'] = $discount_coupon;
                        $data_to_create['total'] -= $discount_coupon;
                    endif;

                    // 计算运费
                    $this->switch_model('biz', 'biz_id');
                    $biz = $this->basic_model->select_by_id($data_to_create['biz_id']);
                    if ( empty($biz['freight_template_id']) ):
                        // 若商家未设置默认运费模板，免运费
                        $data_to_create['freight'] = 0;

                    else:
                        // 获取默认运费模板
                        $this->switch_model('freight_template_biz', 'template_id');
                        $freight_template = $this->basic_model->select_by_id($biz['freight_template_id']);

                        // 检查订单小计是否达到免运费标准
                        if ($data_to_create['subtotal'] >= $freight_template['exempt_subtotal']):
                            $data_to_create['freight'] = 0;

                        else:
                            // 根据运费计算方式，获取计费单位数
                            if ($freight_template['type_actual'] === '计件'):
                                $total_amount = $data_to_create['count'];

                            else:
                                switch ($freight_template['type_actual']):
                                    case '净重':
                                        $freight_type = 'weight_net';
                                        break;
                                    case '毛重':
                                        $freight_type = 'weight_gross';
                                        break;
                                    case '体积重':
                                        $freight_type = 'weight_volume';
                                        break;
                                endswitch;
                                $total_amount = $data_to_create[$freight_type];
                            endif;

                            // 检查计费单位数是否达到免运费标准
                            if ($total_amount >= $freight_template['exempt_amount']):
                                $data_to_create['freight'] = 0;
                                $freight_free = TRUE;
                            endif;

                            // 若不可免运费，开始计算运费
                            if ( ! isset($freight_free) ):
                                // 首费
                                $data_to_create['freight'] = $freight_template['fee_start'];

                                // 若超出首量，计算续量费用（每续量*续量费用）
                                $amount_to_charge = $total_amount - $freight_template['start_amount'];
                                if ($amount_to_charge > 0):
                                    // 采用进一法计算费用
                                    $data_to_create['freight'] += $freight_template['fee_unit'] * ceil($amount_to_charge / $freight_template['unit_amount']);
                                endif;

                                // 更新订单应付金额
                                $data_to_create['total'] += $data_to_create['freight'];
                            endif;

                        endif;

                    endif;
                    unset($data_to_create['count']);
                    unset($data_to_create['weight_net']);
                    unset($data_to_create['weight_gross']);
                    unset($data_to_create['weight_volume']);
                    // end 计算运费

					// 生成订单记录
					$this->reset_model();// 重置数据库参数
					$result = $this->basic_model->create($data_to_create, TRUE);
					if ($result !== FALSE):
						$order_id = $result; // 获取被创建的订单号
						$this->result['status'] = 200;
						$this->result['content']['ids'][] = $order_id;
						$this->result['content']['message'] = '创建成功';

                        // 标记优惠券为已使用
                        if ( !empty($coupon) ):
                            $this->switch_model('coupon', 'coupon_id');
                            $data_to_edit = array(
                                'order_id' => $order_id,
                                'time_used' => time(),
                            );
                            $result = $this->basic_model->edit($coupon['coupon_id'], $data_to_edit);
                        endif;

						// 创建订单商品
						$this->switch_model('order_items', 'record_id');
						foreach ($order_items as $order_item):
							$order_item['order_id'] = $order_id;
							$order_item['user_id'] = $user_id;
							$order_item['time_create'] = time();
							$result = $this->basic_model->create($order_item, TRUE);
						endforeach;

                        // 更新商品/SKU库存
                        // 若要调整成付款减库存，需要去掉各付款渠道控制器中同名方法的注释
                        @$this->stocks_update($order_id);

					else:
						$this->result['status'] = 424;
						$this->result['content']['error']['message'] = '创建失败';

					endif; // 生成订单记录
				endfor;
				
				// 转换已创建订单ID数组为CSV字符串
				$this->result['content']['ids'] = implode($this->result['content']['ids'], ',');

			endif;
		} // end create

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

       
        public function update_redis($count, $item_id, $sku_id = ''){
        	$this->init_redis();
        	if (empty($sku_id)) :
        		$old = $this->myredis->gethash("item_{$item_id}", "getall");
        		if ($old) :
        			$old['stocks'] = intval($old['stocks']) - $count;
        			$this->myredis->savehash("item_{$item_id}", $old);
        		endif;
        	else :
        		$skus = $this->myredis->getlist("sku_list_{$item_id}");
        		if (empty($skus)) :
        			return FALSE;
        		endif;
        		$index = -1;
        		foreach ($skus as $key => $value) :
        			if (intval($value['sku_id']) == intval($sku_id)) :
        				$index = $key;
        				$skus[$key]['stocks'] = intval($value['stocks']) - $count;
        				break;
        			endif;
        		endforeach;
        		if ($index >= 0) :
        			$this->myredis->setlist("sku_list_{$item_id}", $skus[$index], $index);
        		endif;
        	endif;
        	return true;
        }
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

            // 检查必要参数是否已传入
            $required_params = $this->names_edit_bulk_required;
            foreach ($required_params as $param):
                ${$param} = trim($this->input->post($param));
                if ( empty( ${$param} ) ):
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                    exit();
                endif;
            endforeach;
            // 此类型方法通用代码块
            $this->common_edit_bulk(FALSE, 'cancel,note,reprice,refuse,accept,deliver,confirm,delete,restore');

			// 用户取消订单时需要输入原因
			if ($operation === 'cancel')
                $this->form_validation->set_rules('reason_cancel', '取消原因', 'trim|required|max_length[20]');

			// 商家备注时需验证字段
			if ($operation === 'note')
				$this->form_validation->set_rules('note_stuff', '员工备注', 'trim|required|max_length[255]');

            // 商家退单时需验证字段
            if ($operation === 'refuse')
                $this->form_validation->set_rules('note_stuff', '员工备注', 'trim|max_length[255]');

			// 商家改价时需验证字段
			if ($operation === 'reprice'):
				$this->form_validation->set_rules('discount_reprice', '改价折扣金额（元）', 'trim|required|greater_than[0.01]|less_than_equal_to[99999.99]');
                $this->form_validation->set_rules('note_stuff', '员工备注', 'trim|max_length[255]');
			endif;

			// 商家发货时需验证字段
			if ($operation === 'deliver'):
				$this->form_validation->set_rules('deliver_method', '发货方式', 'trim|required|max_length[30]');
				$this->form_validation->set_rules('deliver_biz', '服务商', 'trim|max_length[30]');
				$this->form_validation->set_rules('waybill_id', '运单号', 'trim|max_length[30]alpha_numeric');
			endif;

            // 用户确认订单时需要输入密码
            if ($operation === 'confirm')
                $this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

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

					case 'refuse': // 商家退单
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
                    // 若改价，需获取原订单信息并完整计算待支付金额
                    if ($operation === 'reprice'):
                        $current_order = $this->basic_model->select_by_id($id);
                        $data_to_edit['total'] =
                            $current_order['subtotal']
                            - $current_order['discount_promotion']
                            - $current_order['discount_coupon']
                            + $current_order['freight']
                            - $data_to_edit['discount_reprice'];
                    endif;

					$result = $this->basic_model->edit($id, $data_to_edit);
					if ($result === FALSE):
						$this->result['status'] = 434;
						$this->result['content']['row_failed'][] = $id;

                    elseif ($operation === 'deliver'):
                        // 若发货成功，获取订单信息并向收件人发送短信通知
                        $current_order = $this->basic_model->select_by_id($id);

                        // 发货成功后短信提醒买家
                        $sms_mobile = $current_order['mobile'];
                        if ($data_to_edit['deliver_method'] === '用户自提'):
                            $sms_content = '商家“'. $current_order['biz_name']. '”已为您的自行提货订单'. $id. '准备好货品，请拨冗提货。';
                        else:
                            $deliver_info = $data_to_edit['deliver_biz']. (!empty($data_to_edit['waybill_id'])? $data_to_edit['waybill_id']: NULL);
                            $sms_content = '您在“'. $current_order['biz_name']. '”的订单'.$id.'已通过'. $deliver_info. '发货，请准备查收。';
                        endif;
                        @$this->sms_send($sms_mobile, $sms_content);
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
				${$param} = trim($this->input->post($param));
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 获取待下单商品信息
			$this->cart_decode($cart_string);

            // 计算待生成子订单总数，即订单相关商家数
            $bizs_count = count($this->order_data);
            // 若没有可生成的子订单（即所有商品/规格均无法下单），则不预生成订单数据
            if ($bizs_count < 1):
                $this->result['status'] = 424;
                exit;
            endif;

            // 以商家为单位预生成订单数据
            for ($i=0; $i<$bizs_count; $i++):
                // 获取用户面额最高的有效商家优惠券（若有）
                $this->load->model('coupon_model');
                $coupon = $this->coupon_model->get_max_valid($user_id, $this->order_data[$i]['biz_id'], $this->order_data[$i]['subtotal']);
                if ( ! empty($coupon) )
                    $this->order_data[$i]['total'] -= $coupon['amount'];

                // 无论是否有相应优惠券，均生成相应返回信息字段
                $this->order_data[$i]['coupon_id'] = $coupon['coupon_id'];
                $this->order_data[$i]['coupon_name'] = $coupon['name'];
                $this->order_data[$i]['discount_coupon'] = $coupon['amount'];
            endfor;

            // 获取当前用户可用地址信息
            $conditions = array(
                'user_id' => $user_id,
                'time_delete' => 'NULL',
            );
            $addresses = $this->get_items('address', 'address_id', $conditions);

			$this->result['status'] = 200;
			$this->result['content']['addresses'] = $addresses;
			$this->result['content']['order_data'] = $this->order_data;
		} // end prepare

		/**
		 * 8 商家验证
		 *
		 * TODO 根据验证码对卡券类订单进行核销
		 */
		public function valid()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = array('biz_id', 'code_string');
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
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
				);
				$item = $this->basic_model->match($data_to_search);

				// 若验证码不正确
				if ( empty($item) ):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '验证码无效';

                // 若验证码不可被使用
                elseif ($item['status'] !== '待使用'):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '验证码已使用或不可用';

                // 若验证码已过期
                elseif ($item['time_expire'] < time()):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '验证码已过期';

                else:
                    // 更新订单及验证码为已使用状态
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

        /**
         * 以下为工具方法
         */

        // 获取特定地址信息
		private function get_address($id, $user_id)
		{
			// 从数据库获取相应信息
            $this->switch_model('address', 'address_id');
			$conditions = array(
				'address_id' => $id,
				'user_id' => $user_id,
				'time_delete' => NULL,
			);
			$result = $this->basic_model->match($conditions);
			$this->reset_model();

			// 若获取成功，将地址信息写入类属性
			if ( empty($result) ):
                return FALSE;

            else:
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

			endif;
		} // end get_address

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
         * TODO 参考MY_Controller类中同名方法调整此方法，并增加对商家级、平台级营销活动的支持
         *
         * @param varchar/int $item_id 商品ID；商家ID需要从商品资料中获取
         * @param varchar/int $sku_id 规格ID
         * @param int $count 份数；默认为1，后续需核对每单最低限量
         */
        private function generate_single_item($item_id, $sku_id = NULL, $count = 1)
        {
            // 获取规格信息
            if ( !empty($sku_id) ):
                $this->switch_model('sku', 'sku_id');
                $sku = $this->basic_model->select_by_id($sku_id);
                //var_dump($sku);

                // 若未获取到规格信息，库存不足，或不可购买，则不继续其它逻辑
                if (empty($sku) || $sku['stocks'] < $count || !empty($sku['time_delete'])) return;

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
            if (empty($item) || $sku_and_item_no_stock || $item_not_published || $item['stocks'] < $count):
                // echo '该商品库存不足或未上架';
                //exit();
                return;
            endif;

            // 若超出终身限购数，则不可购买
            if ($item['limit_lifetime'] > 0):
                // 获取已购买的相关商品数量
                $query = $this->db->query("SELECT count(`count`) as bought_count FROM `order_items` WHERE `item_id` =".$item_id.' AND `user_id` ='.$this->user_id);
                $bought_count = $query->row_array()['bought_count'];

                if ($bought_count+$count > $item['limit_lifetime']):
                    /*
                    if ($this->input->post('test_mode') == 'on'):
                        echo $bought_count+$count;
                        var_dump($count);
                        echo '该商品lifetime限购'.$item['limit_lifetime'].'次';
                        exit();
                    endif;
                    */
                    return;
                endif;
            endif;

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
                'weight_net' => $item['weight_net'] * $count,
                'weight_gross' => $item['weight_gross'] * $count,
                'weight_volume' => $item['weight_volume'] * $count,
            );
            if ( !empty($sku) ):
                $order_sku = array(
                    'sku_id' => $sku_id,
                    'sku_name' => $sku['name_first']. ' '.$sku['name_second']. ' '.$sku['name_third'],
                    'sku_image' => $sku['url_image'],
                    'tag_price' => $sku['tag_price'],
                    'price' => $sku['price'],

                    // 若未填写规格重量，直接使用商品重量
                    'weight_net' => ($sku['weight_net'] === '0.00')? $order_item['weight_net']: $sku['weight_net'] * $count,
                    'weight_gross' => ($sku['weight_gross'] === '0.00')? $order_item['weight_gross']: $sku['weight_gross'] * $count,
                    'weight_volume' => ($sku['weight_volume'] === '0.00')? $order_item['weight_volume']: $sku['weight_volume'] * $count,
                );
                $order_item = array_merge($order_item, $order_sku);
            endif;

            // 计算当前商品应付金额
            $order_item['single_total'] = $order_item['price'] * $order_item['count'];

            // 测试模式下，输出订单商品信息
            if ( $this->input->post('test_mode') === 'on' ):
                var_dump($order_item);
                //exit;
            endif;

            // 保存订单商品信息；去掉空数组元素及冗余数据
            $weight_net = $order_item['weight_net'];unset($order_item['weight_net']);
            $weight_gross = $order_item['weight_gross'];unset($order_item['weight_gross']);
            $weight_volume = $order_item['weight_volume'];unset($order_item['weight_volume']);
            $order_items[] = array_filter($order_item);

            // 若当前商家已有待创建订单，更新部分订单信息及订单商品信息
            $need_to_create = TRUE;
            if ( ! empty($this->order_data) ):
                for ($i=0; $i<count($this->order_data); $i++):
                    if ( !empty($this->order_data[$i]) ):

                        if ($this->order_data[$i]['biz_id'] === $order_item['biz_id']):
                            $this->order_data[$i]['subtotal'] += $order_item['single_total'];
                            $this->order_data[$i]['count'] += $order_item['count'];
                            $this->order_data[$i]['weight_net'] += $weight_net;
                            $this->order_data[$i]['weight_gross'] += $weight_gross;
                            $this->order_data[$i]['weight_volume'] += $weight_volume;
                            $this->order_data[$i]['total'] += $order_item['single_total'];

                            $this->order_data[$i]['order_items'] = array_merge($this->order_data[$i]['order_items'], $order_items);
                            $need_to_create = FALSE; // 无需创建新商家的订单
                        endif;

                    endif;
                endfor;
            endif;

            //TODO 计算单品、商家优惠活动折抵
            //TODO 计算单品、商家优惠券折抵
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
                    'count' => $order_item['count'],
                    'weight_net' => $weight_net,
                    'weight_gross' => $weight_gross,
                    'weight_volume' => $weight_volume,

                    'total' => $order_item['single_total'],
                    'order_items' => $order_items,
                    'note_user' => $this->note_user[$order_item['biz_id']], // 用户留言
                );
            endif;
        } // end generate_single_item

		/**
		 * 用户取消
		 *
		 * time_cancel、status
		 */
		private function operation_cancel()
		{
			$data_to_edit['time_cancel'] = time();
			$data_to_edit['status'] = '已取消';
            $data_to_edit['reason_cancel'] = $this->input->post('reason_cancel');
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
		 * discount_reprice、repricer_id
		 */
		private function operation_reprice()
		{
            $data_to_edit['discount_reprice'] = $this->input->post('discount_reprice');
            $data_to_edit['note_stuff'] = $this->input->post('note_stuff');
            return $data_to_edit;
		} // end operation_reprice

		/**
		 * 商家退单
		 *
		 * note_stuff、time_refuse、status
		 */
		private function operation_refuse()
		{
            $data_to_edit['note_stuff'] = $this->input->post('note_stuff');
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
			$data_to_edit['deliver_biz'] = $this->input->post('deliver_biz'); // 物流服务商；若用户自提，不需要填写服务商
			$data_to_edit['waybill_id'] = $this->input->post('waybill_id'); // 物流运单号；用户自提，或同城配送的服务商选择自营时，不需要填写运单号
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
                $this->result['content']['error']['message'] = '密码不正确；若您忘记密码，可通过 个人中心->设置 重新设置';
				exit();
			else:
				$data_to_edit['time_confirm'] = time();
				$data_to_edit['status'] = '待评价';
				return $data_to_edit;
			endif;
		} // end operation_confirm

	} // end class Order

/* End of file Order.php */
/* Location: ./application/controllers/Order.php */
