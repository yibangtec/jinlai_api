<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Fav_biz FVB 商家收藏类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Fav_biz extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'record_id', 'user_id', 'biz_id', 'time_create', 'time_edit',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'biz_id',
		);
		
		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids',
			'operation',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'fav_biz'; // 这里……
			$this->id_name = 'record_id'; // 这里……
			$this->names_to_return[] = 'record_id'; // 还有这里，OK，这就可以了

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
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) )
					$condition[$sorter] = $this->input->post($sorter);
			endforeach;

			// 排序条件
			$order_by = NULL;

			// 获取列表；默认可获取已删除项
			$this->load->model('fav_biz_model');
			$items = $this->fav_biz_model->select($condition, $order_by);
			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end index

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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('user_id', '所属用户ID', 'trim|required');
			$this->form_validation->set_rules('biz_id', '相关商家ID', 'trim|required');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'user_id' => $this->input->post('user_id'),
					'biz_id' => $this->input->post('biz_id'),
				);

				// 检查是否有重复项
				$result = $this->basic_model->match($data_to_create);
				if ( !empty($result) ):
					// 若已关注过且被删除，则恢复
					if ($result['time_delete'] !== NULL):
						$data_to_edit['time_delete'] = NULL;
						@$result = $this->basic_model->edit($result['record_id'], $data_to_edit);

						$this->result['status'] = 200;
						$this->result['content']['id'] = $result['record_id'];
						$this->result['content']['message'] = '关注成功';
					else:
						$this->result['status'] = 414;
						$this->result['content']['error']['message'] = '已经关注过了';
					endif;
					
				else:
					$result = $this->basic_model->create($data_to_create, TRUE);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content']['id'] = $result;
						$this->result['content']['message'] = '关注成功';

					else:
						$this->result['status'] = 424;
						$this->result['content']['error']['message'] = '关注失败';

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
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

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

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
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

	} // end class Fav_biz

/* End of file Fav_biz.php */
/* Location: ./application/controllers/Fav_biz.php */
