<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * User/USR 用户类
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
			'lastname', 'firstname', 'gender', 'dob', 'level', 'promoter_id', 'last_login_timestamp', 'last_login_ip',
            'time_create', 'time_delete', 'time_edit', 'operator_id',
		);

        /**
         * @var array 可根据最大值筛选的字段名
         */
        protected $max_needed = array(
            'time_create', 'last_login_timestamp', 'level',
        );

        /**
         * @var array 可根据最小值筛选的字段名
         */
        protected $min_needed = array(
            'time_create', 'last_login_timestamp', 'level',
        );

        /**
         * 可作为排序条件的字段名
         */
        protected $names_to_order = array(
            'time_create',  'last_login_timestamp', 'level',
        );

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'user_id', 'identity_id', 'mobile', 'email', 'wechat_union_id', 'getui_id', 'nickname', 'lastname', 'firstname', 'gender', 'dob', 'avatar', 'level', 'address_id', 'promoter_id', 'last_login_timestamp', 'last_login_ip',
			'time_create', 'time_delete', 'time_edit', 'operator_id',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'nickname', 'lastname', 'firstname', 'gender', 'dob', 'avatar', 'address_id', 'getui_id',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
            'nickname',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'user'; // 这里……
			$this->id_name = 'user_id'; // 这里……

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
				if ( empty( ${$param} ) ):
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
                // 限制可返回的字段
                if ($this->app_type === 'client'):
                    $condition['time_delete'] = 'NULL';
                endif;
                $this->db->select( implode(',', $this->names_to_return) );
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
			if ( empty($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return). ',password' );
			
			// 获取特定项；默认可获取已删除项
			$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				// 不返回真实密码信息
				if ( !empty($item['password']) ) $item['password'] = 'set';

				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

		/**
		 * 4 编辑单行数据
		 */
		public function edit()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$this->client_check($type_allowed);

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
			$this->form_validation->set_rules('nickname', '昵称', 'trim|required|max_length[12]');
			$this->form_validation->set_rules('lastname', '姓氏', 'trim|max_length[9]');
			$this->form_validation->set_rules('firstname', '名', 'trim|max_length[6]');
			$this->form_validation->set_rules('gender', '性别', 'trim|in_list[男,女]');
			$this->form_validation->set_rules('dob', '出生日期', 'trim|exact_length[10]');
			$this->form_validation->set_rules('avatar', '头像', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

					'dob' => empty($this->input->post('dob'))? NULL: $this->input->post('dob'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'nickname', 'lastname', 'firstname', 'gender', 'avatar',
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
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_certain_required;
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
            $this->form_validation->set_rules('nickname', '昵称', 'trim|max_length[12]');
            $this->form_validation->set_rules('lastname', '姓氏', 'trim|max_length[9]');
            $this->form_validation->set_rules('firstname', '名', 'trim|max_length[6]');
            $this->form_validation->set_rules('gender', '性别', 'trim|in_list[男,女]');
            $this->form_validation->set_rules('dob', '出生日期', 'trim|exact_length[10]');
            $this->form_validation->set_rules('avatar', '头像', 'trim|max_length[255]');
            $this->form_validation->set_rules('address_id', '默认地址', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('getui_id', '个推ID', 'trim|max_length[100]');

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
			$type_allowed = array('admin', 'biz'); // 客户端类型
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
            $this->common_edit_bulk(TRUE, 'delete,restore,freeze,unfreeze');

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
				switch ($operation):
					case 'delete':
						$data_to_edit['time_delete'] = date('Y-m-d H:i:s');
						break;
					case 'restore':
						$data_to_edit['time_delete'] = NULL;
						break;
                    case 'unfreeze': // 解冻的同时恢复未删除状态
                        $data_to_edit['time_delete'] = NULL;
                        $data_to_edit['status'] = '正常';
                        break;
                    case 'freeze':
                        $data_to_edit['status'] = '已冻结';
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
         * 清空特定项所有相关记录
         */
        public function truncate()
        {
            // 检查必要参数是否已传入
            $user_id = $this->input->post('user_id');
            $mobile = $this->input->post('mobile');
            if (empty($user_id.$mobile)):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                exit();
            endif;

            // 获取订单相关商品数据
            $query = !empty($user_id)? $this->db->query("CALL truncate_user_by_id( $user_id )"): $this->db->query("CALL delete_user_by_mobile( $mobile )");
            var_dump($query);
        } // end truncate

        /**
         * 以下为工具方法
         */

	} // end class User

/* End of file User.php */
/* Location: ./application/controllers/User.php */
