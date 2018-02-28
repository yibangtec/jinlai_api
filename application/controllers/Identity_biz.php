<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Identity_biz/IDB 企业认证类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Identity_biz extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'biz_id', 'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'nation', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'identity_id', 'biz_id', 'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'nation', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
            'biz_id', 'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
            'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'nation', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
            'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'identity_biz'; // 这里……
			$this->id_name = 'identity_id'; // 这里……

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
				${$param} = $this->input->post($param);
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
			if ( !isset($id) ):
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
			$type_allowed = array('biz',); // 客户端类型
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
			$this->form_validation->set_rules('biz_id', '所属商家ID', 'trim|required');
			$this->form_validation->set_rules('name', '主体名称', 'trim|required');
            $this->form_validation->set_rules('fullname_owner', '法人姓名', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('fullname_auth', '经办人姓名', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('code_license', '工商注册号', 'trim|required|min_length[15]|max_length[18]|is_unique['.$this->table_name.'.code_license]');
            $this->form_validation->set_rules('code_ssn_owner', '法人身份证号', 'trim|required|exact_length[18]|is_unique['.$this->table_name.'.code_ssn_owner]');
            $this->form_validation->set_rules('code_ssn_auth', '经办人身份证号', 'trim|required|exact_length[18]|is_unique['.$this->table_name.'.code_ssn_auth]');
			$this->form_validation->set_rules('url_image_license', '营业执照', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('url_image_owner_ssn', '法人身份证', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_ssn', '经办人身份证', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_doc', '经办人授权书', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('url_verify_photo', '经办人持身份证照片', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('nation', '国家', 'trim');
			$this->form_validation->set_rules('province', '省', 'trim|required');
			$this->form_validation->set_rules('city', '市', 'trim|required');
			$this->form_validation->set_rules('county', '区', 'trim|required');
			$this->form_validation->set_rules('street', '具体地址', 'trim|required');
			$this->form_validation->set_rules('bank_name', '开户行名称', 'trim|required|max_length[20]');
			$this->form_validation->set_rules('bank_account', '开户行账号', 'trim|required|max_length[30]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,
					//'name' => $this->input->post('name'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
				    'biz_id', 'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'nation', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account',
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
            $type_allowed = array('biz',); // 客户端类型
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
            $this->form_validation->set_rules('name', '主体名称', 'trim|required');
            $this->form_validation->set_rules('fullname_owner', '法人姓名', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('fullname_auth', '经办人姓名', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('code_license', '工商注册号', 'trim|required|min_length[15]|max_length[18]|is_unique['.$this->table_name.'.code_license]');
            $this->form_validation->set_rules('code_ssn_owner', '法人身份证号', 'trim|required|exact_length[18]|is_unique['.$this->table_name.'.code_ssn_owner]');
            $this->form_validation->set_rules('code_ssn_auth', '经办人身份证号', 'trim|required|exact_length[18]|is_unique['.$this->table_name.'.code_ssn_auth]');
            $this->form_validation->set_rules('url_image_license', '营业执照', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('url_image_owner_ssn', '法人身份证', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('url_image_auth_ssn', '经办人身份证', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('url_image_auth_doc', '经办人授权书', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('url_verify_photo', '经办人持身份证照片', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('nation', '国家', 'trim');
            $this->form_validation->set_rules('province', '省', 'trim|required');
            $this->form_validation->set_rules('city', '市', 'trim|required');
            $this->form_validation->set_rules('county', '区', 'trim|required');
            $this->form_validation->set_rules('street', '具体地址', 'trim|required');
            $this->form_validation->set_rules('bank_name', '开户行名称', 'trim|required|max_length[20]');
            $this->form_validation->set_rules('bank_account', '开户行账号', 'trim|required|max_length[30]');

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
                    'name', 'fullname_owner', 'fullname_auth', 'code_license', 'code_ssn_owner', 'code_ssn_auth', 'url_image_license', 'url_image_owner_ssn', 'url_image_auth_ssn', 'url_image_auth_doc', 'url_verify_photo', 'nation', 'province', 'city', 'county', 'street', 'bank_name', 'bank_account',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = $this->input->post($name);

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
            $type_allowed = array('admin',); // 客户端类型
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

        /*
         * 以下为工具方法
         */

	} // end class Identity_biz

/* End of file Identity_biz.php */
/* Location: ./application/controllers/Identity_biz.php */
