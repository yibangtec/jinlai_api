<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Refund 退款/售后类
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
			'user_id', 'biz_id', 'record_id', 'type', 'cargo_status', 'reason', 'description', 'url_images', 'total_applied', 'total_approved', 'status', 'time_create', 'time_cancel', 'time_close', 'time_refuse', 'time_accept', 'time_refund', 'time_edit', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
		    'user_id', 'record_id', 'type', 'cargo_status', 'reason',
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
			$order_by = NULL;

			// 获取列表；默认可获取已删除项
            $this->load->model('refund_model');
			$items = $this->refund_model->select($condition, $order_by);
			if ( !empty($items) ):
                // 获取涉及退款的订单商品
				$this->switch_model('order_items', 'item_id');
				for ($i=0;$i<count($items);$i++):
                    $this->db->select('item_id, name, item_image, slogan, sku_id, sku_name, sku_image, price, count, single_total');
                    $items[$i]['order_item'] = $this->basic_model->select_by_id($items[$i]['record_id']);
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
				// 获取涉及退款的订单商品
				$this->switch_model('order_items', 'record_id');
                $this->db->select('item_id, name, item_image, slogan, sku_id, sku_name, sku_image, price, count, single_total');
                $item['order_item'] = $this->basic_model->select_by_id($item['record_id']);

				// 获取用户信息
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
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('record_id', '订单商品ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('type', '类型', 'trim|required');
			$this->form_validation->set_rules('cargo_status', '货物状态', 'trim|required');
			$this->form_validation->set_rules('reason', '原因', 'trim|required');
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
					$this->result['content']['error']['message'] = '未获取到可匹配的订单商品信息';

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
                        $data_to_create[$name] = $this->input->post($name);

                    $result = $this->basic_model->create($data_to_create, TRUE);
                    if ($result !== FALSE):
                        $this->result['status'] = 200;
                        $this->result['content']['id'] = $result;
                        $this->result['content']['message'] = '创建成功';

                        // 更新相应订单商品为退款中状态
                        $this->switch_model('order_items', 'record_id');
                        $data_to_edit = array(
                            'refund_status' => '退款中',
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

	} // end class Refund

/* End of file Refund.php */
/* Location: ./application/controllers/Refund.php */
