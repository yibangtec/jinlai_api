<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Vote_option/VTO 投票候选项类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Vote_option extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'vote_id', 'tag_id', 'index_id', 'name', 'description', 'url_image',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);
		
		/**
	     * @var array 可根据最大值筛选的字段名
	     */
	    protected $max_needed = array(
	        'time_create',
	    );

	    /**
	     * @var array 可根据最小值筛选的字段名
	     */
	    protected $min_needed = array(
	        'time_create',
	    );
		
		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
			'vote_id', 'tag_id', 'index_id', 'name', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'option_id', 'vote_id', 'tag_id', 'index_id', 'name', 'description', 'url_image', 'mobile', 'ballot_overall',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'vote_id', 'name', 'description', 'url_image',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
            'tag_id', 'index_id', 'name', 'description', 'url_image',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
			'name',
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
			'user_id', 'ids',
			'operation', 'password',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'vote_option'; // 这里……
			$this->id_name = 'option_id'; // 这里……

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
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 生成筛选条件
			$condition = $this->condition_generate();

            // 用户仅可查看未删除的有效项
            if ($this->app_type === 'client'):
                $condition['time_delete'] = 'NULL';
                $condition['status'] = '正常';
            endif;

			// 排序条件
			$order_by = NULL;
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

            // 限制可返回的字段
            if ($this->app_type === 'client'):
                $condition['time_delete'] = 'NULL';
                $this->names_to_return = array_diff($this->names_to_return, $this->names_return_for_admin);
            endif;
            $this->db->select( implode(',', $this->names_to_return) );

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                $items = $this->basic_model->select($condition, $order_by);
            else:
                $items = $this->basic_model->select_by_ids($ids);
            endif;

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
			if ( !isset($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
            $this->db->select( implode(',', $this->names_to_return).', (SELECT COUNT(*) FROM vote_ballot WHERE vote_option.option_id = vote_ballot.option_id) AS ballot_count');
			
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
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

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
			$this->form_validation->set_rules('vote_id', '所属投票ID', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('tag_id', '所属标签ID', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('index_id', '索引序号', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('name', '名称', 'trim|required|max_length[30]');
			$this->form_validation->set_rules('description', '描述', 'trim|max_length[100]');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');

			// 若为客户端报名，则需手机号
			if ($this->app_type === 'client')
			    $this->form_validation->set_rules('mobile', '审核联系手机号', 'trim|required|exact_length[11]|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                if ($this->app_type === 'client'):
                    // 检查所属投票活动是否存在、未结束、允许报名，且当前用户未报名
                    $vote = $this->get_vote($vote_id);
			        if (empty($vote)):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '活动不存在';
                    elseif($vote['signup_allowed'] === '否'):
                        $this->result['status'] = 424;
                        $this->result['content']['error']['message'] = '活动不开放报名';
                    elseif(time() > $vote['time_end']):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '活动已结束';

                    else:
                        // 每个用户只能为每个活动创建一个候选项
                        $user_total_options = $this->user_total_options($user_id, $vote_id);
                        if ($user_total_options !== FALSE):
                            $this->result['status'] = 424;
                            $this->result['content']['error']['message'] = '你只能为每个活动报名1个候选项';
                        endif;

                    endif;
                endif;

				// 若前述无异常，则继续进行后续业务
                if ($this->result['status'] === NULL):
                    // 需要创建的数据；逐一赋值需特别处理的字段
                    $data_to_create = array(
                        'creator_id' => $user_id,
                    );
                    // 自动生成无需特别处理的数据
                    $data_need_no_prepare = array(
                        'vote_id', 'tag_id', 'index_id', 'name', 'description', 'url_image', 'mobile',
                    );
                    foreach ($data_need_no_prepare as $name)
                        $data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

                    // 客户端创建的数据，默认为待审核状态
                    if ($this->app_type === 'client') $data_to_create['status'] = '待审核';

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

			endif;
		} // end create

		/**
		 * 4 编辑单行数据
		 */
		public function edit()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin'); // 客户端类型
			$this->client_check($type_allowed);

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
            $this->form_validation->set_rules('tag_id', '所属标签ID', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('index_id', '索引序号', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('name', '名称', 'trim|required|max_length[30]');
			$this->form_validation->set_rules('description', '描述', 'trim|max_length[100]');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
                    'tag_id', 'index_id', 'name', 'description', 'url_image',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $id;
					$this->result['content']['message'] = '编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑失败';

				endif;
			endif;
		} // end edit

        /**
		 * 6 编辑多行数据特定字段
		 *
		 * 修改多行数据的单一字段值
		 */
		public function edit_bulk()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz',); // 客户端类型
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
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore,approve,reject]');
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
                    case 'approve': // 批准的同时恢复未删除状态
                        $data_to_edit['time_delete'] = NULL;
                        $data_to_edit['status'] = '正常';
                        break;
                    case 'reject':
                        $data_to_edit['status'] = '已拒绝';
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
		 * 以下为工具类方法
		 */

        // 获取特定投票活动信息，不含已删除项
        protected function get_vote($vote_id)
        {
            $this->switch_model('vote', 'vote_id');

            $this->db->where('time_delete IS NULL');
            $result = $this->basic_model->select_by_id($vote_id);

            return (empty($result))? FALSE: $result;
        } // end get_vote

        // 获取特定用户已创建候选项数，含已删除项
        protected function user_total_options($user_id, $vote_id)
        {
            $this->switch_model('vote_option', 'option_id');
            $condition = array(
                'vote_id' => $vote_id,
                'creator_id' => $user_id,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_total_options

	} // end class Vote_option

/* End of file Vote_option.php */
/* Location: ./application/controllers/Vote_option.php */
