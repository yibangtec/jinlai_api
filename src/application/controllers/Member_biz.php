<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Member_biz/MBB 会员卡类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Member_biz extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'user_id', 'biz_id', 'category_id', 'name', 'mobile', 'level',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'member_id', 'user_id', 'biz_id', 'mobile', 'level',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id', 'biz_id', 'mobile',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'mobile', 'level',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
            'mobile', 'level',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'member_biz'; // 这里……
			$this->id_name = 'member_id'; // 这里……

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
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

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
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

			// 排序条件
			$order_by = NULL;
			$condition['time_delete'] = null;
			// 获取列表；默认可获取已删除项
            $this->load->model('member_biz_model');
            $items = $this->member_biz_model->select($condition, $order_by);

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
            $user_id = $this->input->post('user_id');
            $biz_id = $this->input->post('biz_id');
			if ( !isset($id) && (!isset($user_id) && !isset($biz_id)) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );
			
			// 获取特定项；默认可获取已删除项
            if ( !empty($id) ):
			    $item = $this->basic_model->select_by_id($id);
            else:
                $data_to_search = array(
                    'user_id' => $user_id,
                    'biz_id' => $biz_id,
                );
                $item = $this->basic_model->match($data_to_search);
            endif;

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
			$type_allowed = array('biz', 'client'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

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
			$this->form_validation->set_rules('user_id', '所属用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('biz_id', '相关商家ID', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('mobile', '登记手机号', 'trim|required|exact_length[11]|is_natural_no_zero');
			$this->form_validation->set_rules('level', '会员等级', 'trim|max_length[1]|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 需要创建的数据
                $data_to_create = array(
                    'user_id' => $user_id,
                    'biz_id' => $biz_id,
                );

                // 检查是否有重复项
                $result = $this->basic_model->match($data_to_create);
                if ( !empty($result) ):
                    // 若已领取过且被删除，则恢复
                    if ($result['time_delete'] !== NULL):
                        $data_to_edit['time_delete'] = NULL;
                        @$result = $this->basic_model->edit($result[$this->id_name], $data_to_edit);

                        $this->result['status'] = 200;
                        $this->result['content']['id'] = $result[$this->id_name];
                        $this->result['content']['message'] = '恢复领取成功';
                    else:
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '已经领过会员卡了';
                    endif;

                else:
                    $data_to_create['mobile'] = $mobile;
                    $data_to_create['level'] = empty($this->input->post('level'))? 0: $this->input->post('level');
                    $data_to_create['time_create'] = time();
                    $data_to_create['creator_id'] = $user_id;

                    $result = $this->basic_model->create($data_to_create, TRUE);
                    if ($result !== FALSE):
                        $this->result['status'] = 200;
                        $this->result['content']['id'] = $result;
                        $this->result['content']['message'] = '领取成功';

                    else:
                        $this->result['status'] = 424;
                        $this->result['content']['error']['message'] = '领取失败';

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
            $type_allowed = array('biz', 'client'); // 客户端类型
            $this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_required;
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
            $this->form_validation->set_rules('mobile', '登记手机号', 'trim|required|exact_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('level', '会员等级', 'trim|required|max_length[1]|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
                    'level' => empty($this->input->post('level'))? 0: $this->input->post('level'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'mobile',
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
		 * 5 编辑单行数据特定字段
		 *
		 * 修改单行数据的单一字段值
		 */
		public function edit_certain()
		{
            // 操作可能需要检查客户端及设备信息
            $type_allowed = array('biz', 'client'); // 客户端类型
            $this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_certain_required;
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
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
            $this->form_validation->set_rules('mobile', '登记手机号', 'trim|required|exact_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('level', '会员等级', 'trim|max_length[1]|is_natural_no_zero');

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
					$this->result['content']['id'] = $id;
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
            $type_allowed = array('biz', 'client'); // 客户端类型
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
            $this->common_edit_bulk(TRUE);

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
         * 以下为工具方法
         */

        /**
         * 类特有筛选器
         *
         * @param array $condition 当前筛选条件数组
         * @return array 生成的筛选条件数组
         */
        protected function advanced_sorter($condition = array())
        {
            // 若传入了平台级商品分类，则同时筛选主营类目属于该分类及该分类子分类的商家
            if ( !empty($condition['category_id']) ):
                // 获取所有子类ID
                $this->switch_model('item_category', 'category_id');
                $sub_categories = $this->basic_model->select(
                    array('parent_id' => $condition['category_id']),
                    NULL,
                    TRUE
                ); // 仅返回ID
                $this->reset_model();

                if ( !empty($sub_categories) ):
                    $sub_categories[] = $condition['category_id'];
                    unset($condition['category_id']);

                    $this->db->or_where_in('biz.category_id', $sub_categories);

                endif;
            endif;

            // 若传入了商家名，模糊查询
            if ( !empty($this->input->post('name')) ):
                $this->db->group_start();
                $this->db->like('biz.name', $this->input->post('name'));
                $this->db->or_like('biz.brief_name', $this->input->post('name'));
                $this->db->group_end();
                unset($condition['name']);
            endif;

            return $condition;
        } // end advanced_sorter

	} // end class Member_biz

/* End of file Member_biz.php */
/* Location: ./application/controllers/Member_biz.php */
