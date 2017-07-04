<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * USR 用户类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class User extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'nickname', 'lastname', 'firstname', 'gender', 'dob',
			'mobile', 'email', 'wechat_union_id', 'address_id',
			'balance_credit', 'balance_fund',
			'time_create', 'last_login_timestamp', 'last_login_ip', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'nickname', 'lastname', 'firstname', 'gender', 'dob', 'avatar',
			'mobile', 'email', 'wechat_union_id', 'address_id',
			'balance_credit', 'balance_fund',
			'time_create', 'last_login_timestamp', 'last_login_ip', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'mobile',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_allowed = array(
			'nickname', 'lastname', 'firstname', 'gender', 'dob', 'avatar',
			'mobile', 'email', 'wechat_union_id', 'address_id',
		);

		/**
		 * 编辑单行特定字段时必要的字段名
		 */
		protected $names_edit_certain_required = array(
			'user_id', 'id', 'name', 'value',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'user'; // 这里……
			$this->id_name = 'user_id'; // 这里……
			$this->names_to_return[] = 'user_id'; // 还有这里，OK，这就可以了

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
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) )
					$condition[$sorter] = $this->input->post($sorter);
			endforeach;

			// 获取列表；默认不获取已删除项
			$count = $this->basic_model->count($condition);

			if ($count !== FALSE):
				$this->result['status'] = 200;
				$this->result['content']['count'] = $count;

			else:
				$this->result['status'] = 400;
				$this->result['content'] = NULL;

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
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) )
					$condition[$sorter] = $this->input->post($sorter);
			endforeach;

			// 排序条件
			$order_by = NULL;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认不获取已删除项
			$items = $this->basic_model->select($condition, $order_by, FALSE, FALSE);
			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = NULL;
			
			endif;
		}

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
				// 不返回真实密码信息
				if ( !empty($item['password']) ) $item['password'] = 'set';

				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = NULL;

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
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|is_natural|exact_length[11]|is_unique[user.mobile]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array();
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = $this->names_create_required;
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = $this->input->post($name);

				$result = $this->basic_model->create($data_to_create);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content'] = '创建'.$this->class_name_cn.'成功';

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建'.$this->class_name_cn.'失败';

				endif;
			endif;
		} // end create

		/**
		 * 4 编辑特定字段
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
				if ( empty( ${$param} ) ):
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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
			$this->form_validation->set_rules('nickname', '昵称', 'trim|max_length[12]');
			$this->form_validation->set_rules('lastname', '姓', 'trim|max_length[9]');
			$this->form_validation->set_rules('firstname', '名', 'trim|max_length[6]');
			$this->form_validation->set_rules('gender', '性别', 'trim|max_length[1]|in_list[男,女]');
			$this->form_validation->set_rules('dob', '生日', 'trim|max_length[10]');
			$this->form_validation->set_rules('avatar', '头像图片URL', 'trim|max_length[255]');
			$this->form_validation->set_rules('mobile', '手机号', 'trim|exact_length[11]|is_natural_no_zero|is_unique[user.mobile]');
			$this->form_validation->set_rules('email', 'Email', 'trim|max_length[40]|valid_email');
			$this->form_validation->set_rules('wechat_union_id', '微信union_id', 'trim|max_length[28]');
			$this->form_validation->set_rules('address_id', '默认地址ID', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $this->input->post($value);

				// 获取ID
				$id = $this->input->post('id');
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content'] = '编辑'.$this->class_name_cn.'成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑'.$this->class_name_cn.'失败';

				endif;
			endif;
		} // end edit_certain

	}

/* End of file User.php */
/* Location: ./application/controllers/User.php */
