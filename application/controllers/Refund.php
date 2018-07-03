<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Refund/RFD 退款/售后类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Refund extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'user_id', 'biz_id', 'order_id', 'record_id', 'type', 'cargo_status', 'reason', 'description', 'url_images', 'total_applied', 'total_approved', 'status', 'time_create', 'time_cancel', 'time_close', 'time_refuse', 'time_accept', 'time_refund', 'time_edit', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
		    'user_id',
            'record_id', 'type', 'cargo_status', 'reason',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'refund'; // 这里……
			$this->id_name = 'refund_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end __construct

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
            $order_by = NULL;

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 商家端若未请求特定状态的退款，则不返回部分状态的退款
                if ($this->app_type === 'biz' && empty($this->input->post('status')))
                    $this->db->where_not_in($this->table_name.'.status', array('已取消', '已拒绝', '已关闭'));
                $this->load->model('refund_model');
			    $items = $this->refund_model->select($condition, $order_by);
            else:
                $items = $this->basic_model->select_by_ids($ids);
            endif;

			// 获取列表；默认可获取已删除项
			if ( !empty($items) ):
                if ( empty($ids) ):
                    // 获取涉及退款的订单商品
                    $this->switch_model('order_items', 'record_id');
                    $this->basic_model->limit = $this->basic_model->offset = 0;
                    for ($i=0;$i<count($items);$i++):
                        $this->db->select('record_id, order_id, item_id, name, item_image, slogan, sku_id, sku_name, sku_image, price, count, single_total, refund_status');
                        $items[$i]['order_item'] = $this->basic_model->select_by_id($items[$i]['record_id']);
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
            $record_id = $this->input->post('record_id');
			if ( !isset($id) && !isset($record_id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 获取特定项；默认可获取已删除项
            $this->load->model('refund_model');
			$item = $this->refund_model->select_by_id($id, $record_id);
			if ( !empty($item) ):
				// 获取订单商品
				$this->switch_model('order_items', 'record_id');
                $this->db->select('record_id, order_id, item_id, name, item_image, slogan, sku_id, sku_name, sku_image, price, count, single_total, refund_status');
                $item['order_item'] = $this->basic_model->select_by_id($item['record_id']);

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
			$type_allowed = array('client',); // 客户端类型
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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('record_id', '订单商品ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('type', '类型', 'trim|required');
			$this->form_validation->set_rules('cargo_status', '货物状态', 'trim|required');
			$this->form_validation->set_rules('reason', '原因', 'trim|required|in_list[无理由,退运费,未收到,不开发票]');
			$this->form_validation->set_rules('description', '补充说明', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_images', '相关图片URL', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 获取涉及退款的订单商品
                $this->switch_model('order_items', 'record_id');
                $this->db->select('order_id, biz_id, user_id, item_id, name, item_image, slogan, sku_id, sku_name, sku_image, price, count, single_total');
                $this->db->where('refund_status', '未申请');
                $this->db->where('user_id', $user_id);
                $order_item = $this->basic_model->select_by_id($record_id);
                $this->reset_model();

                $total_applied = $this->input->post('total_applied');

                // 检查是否存在涉及退款的订单商品
                if ( empty($order_item) ):
                    $this->result['status'] = 414;
					$this->result['content']['error']['message'] = '未获取到可申请退款的订单商品信息';

                // 若传入了申请退款金额，检查有效性
                elseif (!empty($total_applied) && ($total_applied > $order_item['single_total'])):
                    $this->result['status'] = 424;
                    $this->result['content']['error']['message'] = '申请的退款金额不可高于商品小计金额';

                else:
                    // 需要创建的数据；逐一赋值需特别处理的字段
                    $data_to_create = array(
                        'time_create' => time(),

                        'user_id' => $user_id,
                        'order_id' => $order_item['order_id'],
                        'biz_id' => $order_item['biz_id'],

                        'total_applied' => !empty($total_applied)? $total_applied: $order_item['single_total'],
                    );
                    // 自动生成无需特别处理的数据
                    $data_need_no_prepare = array(
                        'record_id', 'type', 'cargo_status', 'reason', 'description', 'url_images',
                    );
                    foreach ($data_need_no_prepare as $name)
                        $data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

                    $result = $this->basic_model->create($data_to_create, TRUE);
                    if ($result !== FALSE):
                        $this->result['status'] = 200;
                        $this->result['content']['id'] = $result;
                        $this->result['content']['message'] = '创建成功';

                        // 更新相应订单商品为退款中状态
                        $this->switch_model('order_items', 'record_id');
                        $data_to_edit = array(
                            'refund_status' => '待处理',
                        );
                        @$result = $this->basic_model->edit($record_id, $data_to_edit);

                    else:
                        $this->result['status'] = 424;
                        $this->result['content']['error']['message'] = '创建失败';

                    endif;
                endif;
			endif;
		} // end create

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
            $this->common_edit_bulk(TRUE, 'note,refuse,accept,confirm');
            $this->form_validation->set_rules('note_stuff', '员工备注', 'trim|max_length[255]');

            // 商家同意退款时需验证字段；非批量同意退款时可以修改同意退款金额
            if ($operation === 'accept' && strpos(trim($ids, ','), ',') === false)
                $this->form_validation->set_rules('total_approved', '同意退款金额', 'trim|required|greater_than[0.01]|less_than_equal_to[99999.99]');

            // 商家发货时需验证字段
            if ($operation === 'confirm'):
                $this->form_validation->set_rules('deliver_method', '发货方式', 'trim|required|max_length[30]');
                $this->form_validation->set_rules('deliver_biz', '服务商', 'trim|max_length[30]');
                $this->form_validation->set_rules('waybill_id', '运单号', 'trim|max_length[30]alpha_numeric');
            endif;

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
                $data_to_edit['note_stuff'] = $this->input->post('note_stuff');

				// 根据待执行的操作赋值待编辑数据
				switch ( $operation ):
                    case 'refuse': // 商家拒绝
                        $data_to_edit = array_merge($data_to_edit, $this->operation_refuse());
                        break;
                    case 'accept': // 商家同意
                        $data_to_edit = array_merge($data_to_edit, $this->operation_accept());
                        break;
                    case 'confirm': // 商家收货
                        $data_to_edit = array_merge($data_to_edit, $this->operation_confirm());
                        break;

                    case 'delete': // 用户删除
                        $data_to_edit['time_delete'] = date('Y-m-d H:i:s');
                        break;
                    case 'restore': // 用户找回
                        $data_to_edit['time_delete'] = NULL;
                        break;
				endswitch;

				// 依次操作数据并输出操作结果
				// 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
				$ids = explode(',', $ids);

				// 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
				$this->result['status'] = 200;
				foreach ($ids as $id):
                    $this->reset_model(); // 重置数据库参数
                    $current_refund = $this->basic_model->select_by_id($id);

                    // 若同意退款，需根据货物状态决定待退货还是待退款
                    if ($operation === 'accept'):
				        //$target_status = ($current_refund['cargo_status'] === '已收货')? '待退货': '待退款';
                        $target_status = '待退款'; // TODO 待ERP等可追踪货物情况系统接入或开发前，暂时默认为"待退款"
                        $data_to_edit['status'] = $target_status;
                    endif;

                    $result = $this->basic_model->edit($id, $data_to_edit);
					if ($result === FALSE):
						$this->result['status'] = 434;
						$this->result['content']['row_failed'][] = $id;

                    elseif ($operation !== 'note'):
                        $record_id = $current_refund['record_id'];

                        // 自动执行退款
                        if ($operation == 'accept'):
                            // 使用id获取退款申请信息，并获取相应待退款订单ID
                            $refund_item = $this->get_item('refund', 'refund_id', $id);
                            $order = $this->get_item('order', 'order_id', $refund_item['order_id']);
                            //var_dump($order);

                            // 获取订单信息并调用相应付款方式的退款API
                            $params = array(
                                'order_id' => $order['order_id'],
                                'total_to_refund' => $refund_item['total_approved'],
                            );

                            // 判断需调用哪个支付方式的退款API
                            if ($order['payment_type'] == '微信支付'):
                                $payment_type = 'wepay';
                            elseif ($order['payment_type'] == '支付宝'):
                                $payment_type = 'alipay';
                            endif;
                            $url = api_url($payment_type.'/refund');

                            // 向API服务器发送待创建数据
                            $this->load->library('curl');
                            $result = $this->curl->go($url, $params, 'array');
                            //var_dump($result);
                            if ($result['status'] === 200):
                                $this->result['content']['message'] .= $result['content'];
                                $this->result['content']['coupon_id'] = $result['content']['id']; // 创建后的信息ID
                                unset($params, $result); // 释放内存

                                // 更新退款详情
                                $data_to_edit = array(
                                    'status' => '已退款',
                                    'total_payed' => $refund_item['total_approved'],
                                );
                                @$this->basic_model->edit($id, $data_to_edit);
                                unset($refund_item); // 释放内存

                                // 短信通知
                                $mobile = '17664073966';
                                $content = '您的订单 '. $order['order_id']. ' 已通过'.$order['payment_type'].'退款，款项将由付款时支付渠道退回；感谢您本次选购，希望下次给您更好体验！';
                                @$this->sms_send($mobile, $content);

                                unset($order); // 释放内存

                                // 更新相应订单商品退款状态
                                $data_to_edit = array(
                                    'refund_status' => '已退款',
                                );

                            else:
                                // 若创建失败，则进行提示
                                $this->result['content']['error']['message'] .= '退款ID'.$id.'/订单ID'.$order['order_id'].'自动退款失败，请通知财务介入';

                                // 更新相应订单商品退款状态
                                $data_to_edit = array(
                                    // 'refund_status' => $target_status, // TODO 待ERP等可追踪货物情况系统接入或开发前，暂时默认为"待退款"
                                    'refund_status' => '待退款',
                                );

                            endif;
                        endif;

                        // 更新相应订单商品退款状态
                        $this->switch_model('order_items', 'record_id');
                        if ($operation === 'refuse'):
                            $data_to_edit = array(
                                'refund_status' => '已拒绝',
                            );

                        elseif ($operation === 'confirm'):
                            $data_to_edit = array(
                                'refund_status' => '待退款',
                            );

                        endif;
                        $this->basic_model->edit($record_id, $data_to_edit);

					endif;

				endforeach;

				// 添加全部操作成功后的提示
				if ($this->result['status'] = 200)
					$this->result['content']['message'] = '全部操作成功';

			endif;
		} // end edit_bulk

        /**
         * 以下为工具方法
         */

        /**
         * 商家拒绝退款
         *
         * note_stuff、time_refuse、status
         */
        private function operation_refuse()
        {
            $data_to_edit['time_refuse'] = time();
            $data_to_edit['status'] = '已拒绝';
            return $data_to_edit;
        } // end operation_refuse

        /**
         * 商家同意退款
         *
         * time_accept、status
         */
        private function operation_accept()
        {
            $data_to_edit['time_accept'] = time();

            // 非批量同意退款时可以修改同意退款金额
            if (strpos( trim($this->input->post('ids'), ','), ',' ) === false)
                $data_to_edit['total_approved'] = $this->input->post('total_approved');

            return $data_to_edit;
        } // end operation_accept

        /**
         * 商家确认收货
         *
         * time_confirm、deliver_method、deliver_biz、waybill_id、status
         */
        private function operation_confirm()
        {
            // 获取发货方式
            $deliver_method = $this->input->post('deliver_biz');

            $data_to_edit['time_confirm'] = time();
            $data_to_edit['deliver_method'] = $deliver_method;

            // 若用户自提，跳过部分信息
            if ($deliver_method !== '用户自提'):
                $data_to_edit['deliver_biz'] = $this->input->post('deliver_biz'); // 物流服务商
                $data_to_edit['waybill_id'] = $this->input->post('waybill_id'); // 物流运单号；同城配送的服务商选择自营时，运单号不是必要信息
            endif;

            return $data_to_edit;
        } // end operation_confirm

	} // end class Refund

/* End of file Refund.php */
/* Location: ./application/controllers/Refund.php */
