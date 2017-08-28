<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * TODO Schedule 类
	 *
	 * 计划任务
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Schedule extends CI_Controller
	{
		/* 主要相关表名 */
		public $table_name;

		/* 主要相关表的主键名*/
		public $id_name;
		
		// 短信后缀签名
		protected $suffix = '【进来商城】';
		// 接收短信的手机号
		protected $mobile;
		// 批量接收短信的手机号，CSV格式
		protected $mobile_list;
		// 批量发送短信的预订时间
		protected $time = NULL;
		// 短信内容
		protected $content;

		public function __construct()
		{
			parent::__construct();

			// 向类属性赋值
			$this->table_name = 'table'; // 和这里……
			$this->id_name = 'table_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * 截止3.1.3为止，CI_Controller类无析构函数，所以无需继承相应方法
		 */
		public function __destruct()
		{
			// 调试信息输出开关
			// $this->output->enable_profiler(TRUE);
		}

		// 路由
		public function index()
		{
			$this->hour();
		}

		/**
		 * 每小时
		 *
		 * 发送报时短信
		 */
		public function hour()
		{
			$this->mobile = '17664073966';
			$this->content = '现在时间 '. date('Y-m-d H:i:s');
			// 为短信内容添加后缀签名
			$this->content .= $this->suffix;

			// 发送短信
			$this->load->library('luosimao');
			$result = $this->luosimao->send($this->mobile, $this->content);

			// 解析发送结果
			$result_array = json_decode($result);

			// 根据短信发送结果进行相关操作
			if ($result_array->error == 0):
				// 保存短信内容
				//$this->save();

			else:
				// 获取错误码相应的文本提示
				$error_message = $this->luosimao->error_text($result_array);
				var_dump($error_message);

				//$this->result['status'] = 400;
				//$this->result['content']['error']['message'] = $error_message;

			endif;
		} // end minute

		/**
		 * 详情页
		 */
		public function detail()
		{
			// 检查是否已传入必要参数
			$id = $this->input->get_post('id')? $this->input->get_post('id'): NULL;
			if ( empty($id) )
				redirect(base_url('error/code_404'));

			// 页面信息
			$data = array(
				'title' => $this->class_name_cn. '详情',
				'class' => $this->class_name.' '. $this->class_name.'-detail',
			);

			// 将需要显示的数据传到视图以备使用
			$data['data_to_display'] = $this->data_to_display;

			// Go Basic！
			$this->basic->detail($data, 'title'); // 当传入第二个参数时，将使用相应的字段值与上方传入的$data['title']进行拼接；如想直接使用该字段作为页面的title，则$data['title']设为NULL即可；拼接的位置等更多功能可见model/basic_model.php
		} // end detail

		/**
		 * 回收站
		 */
		public function trash()
		{
			// 操作可能需要检查操作权限
			$role_allowed = array('管理员', '经理'); // 角色要求
			$min_level = 30; // 级别要求
			$this->basic->permission_check($role_allowed, $min_level);

			// 页面信息
			$data = array(
				'title' => $this->class_name_cn. '回收站',
				'class' => $this->class_name.' '. $this->class_name.'-trash',
			);

			// 将需要显示的数据传到视图以备使用
			$data['data_to_display'] = $this->data_to_display;
			
			// 筛选条件
			$condition = NULL;
			//$condition['name'] = 'value';
			
			// 排序条件
			$order_by['time_delete'] = 'DESC';
			//$order_by['name'] = 'value';
			
			// Go Basic！
			$this->basic->trash($data, $condition, $order_by);
		} // end trash

		/**
		 * 创建
		 */
		public function create()
		{
			// 操作可能需要检查操作权限
			$role_allowed = array('管理员', '经理'); // 角色要求
			$min_level = 30; // 级别要求
			$this->basic->permission_check($role_allowed, $min_level);

			// 页面信息
			$data = array(
				'title' => '创建'.$this->class_name_cn,
				'class' => $this->class_name.' '. $this->class_name.'-create',
			);

			// (可选) 检查是否已传入必要参数，例如创建某项目所属的页面
			$id = $this->input->get_post('project_id')? $this->input->get_post('project_id'): NULL;
			if ( empty($id) )
				redirect(base_url('error/code_404'));
			//（可选）获取项目数据
			$data['project'] = $this->basic->get_by_id($id, 'project', 'project_id');

			// 待验证的表单项
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('name', '名称', 'trim|required');
			$this->form_validation->set_rules('description', '说明', 'trim|required');
			$this->form_validation->set_rules('sample', '其它', 'trim');
			// 以下为常见格式验证示例，确认适用后可复用
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|is_natural|exact_length[11]');
			$this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
			$this->form_validation->set_rules('avatar', '头像URL', 'trim|valid_url');
			$this->form_validation->set_rules('role', '角色', 'trim');
			$this->form_validation->set_rules('level', '等级', 'trim|is_natural|max_length[2]');
			$this->form_validation->set_rules('project_id', '所属项目ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('user_id', '指定用户ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('code', '序号', 'trim|alpha_numeric|required');

			// 需要存入数据库的信息
			// 不建议直接用$this->input->post/get/post_get等方法直接在此处赋值，向数组赋值前处理会保持最大的灵活性以应对图片上传等场景
			$data_to_create = array(
				'name' => $this->input->post('name'),
				'description' => $this->input->post('description'),
				'sample' => $this->input->post('sample'),
			);

			// Go Basic!
			$this->basic->create($data, $data_to_create);
		} // end create

		/**
		 * 编辑单行
		 */
		public function edit()
		{
			// 操作可能需要检查操作权限
			$role_allowed = array('管理员', '经理'); // 角色要求
			$min_level = 30; // 级别要求
			$this->basic->permission_check($role_allowed, $min_level);

			// 页面信息
			$data = array(
				'title' => '编辑'.$this->class_name_cn,
				'class' => $this->class_name.' '. $this->class_name.'-edit',
			);

			// 待验证的表单项
			$this->form_validation->set_rules('name', '名称', 'trim|required');
			$this->form_validation->set_rules('description', '说明', 'trim|required');
			$this->form_validation->set_rules('sample', '其它', 'trim');

			// 需要编辑的信息
			$data_to_edit = array(
				'name' => $this->input->post('name'),
				'description' => $this->input->post('description'),
				'sample' => $this->input->post('sample'),
			);

			// Go Basic!
			$this->basic->edit($data, $data_to_edit, $view_file_name = NULL); // 可以自定义视图文件名
		} // end edit

		/**
		 * 删除单行或多行项目
		 *
		 * 一般用于发货、退款、存为草稿、上架、下架、删除、恢复等状态变化，请根据需要修改方法名，例如deliver、refund、delete、restore、draft等
		 */
		public function delete()
		{
			// 操作可能需要检查操作权限
			$role_allowed = array('管理员', '经理'); // 角色要求
			$min_level = 30; // 级别要求
			$this->basic->permission_check($role_allowed, $min_level);

			$op_name = '删除'; // 操作的名称
			$op_view = 'delete'; // 视图文件名

			// 页面信息
			$data = array(
				'title' => $op_name. $this->class_name_cn,
				'class' => $this->class_name.' '. $this->class_name.'-'. $op_view,
			);
			
			// 将需要显示的数据传到视图以备使用
			$data['data_to_display'] = $this->data_to_display;

			// 待验证的表单项
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

			// 需要存入数据库的信息
			$data_to_edit = array(
				'time_delete' => date('Y-m-d H:i:s'), // 批量删除
				// 'time_delete' => NULL, // 批量恢复
				// 'name' => 'value', // 批量修改其它数据
				// 'name' => 'value', // 多行可批量修改多个字段
			);

			// Go Basic!
			$this->basic->bulk($data, $data_to_edit, $op_name, $op_view);
		} // end delete
		
		/**
		 * 恢复单行或多行项目
		 *
		 * 一般用于存为草稿、上架、下架、删除、恢复等状态变化，请根据需要修改方法名，例如delete、restore、draft等
		 */
		public function restore()
		{
			// 操作可能需要检查操作权限
			$role_allowed = array('管理员', '经理'); // 角色要求
			$min_level = 30; // 级别要求
			$this->basic->permission_check($role_allowed, $min_level);

			$op_name = '恢复'; // 操作的名称
			$op_view = 'restore'; // 视图文件名

			// 页面信息
			$data = array(
				'title' => $op_name. $this->class_name_cn,
				'class' => $this->class_name.' '. $this->class_name.'-'. $op_view,
			);

			// 将需要显示的数据传到视图以备使用
			$data['data_to_display'] = $this->data_to_display;

			// 待验证的表单项
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

			// 需要存入数据库的信息
			$data_to_edit = array(
				// 'time_delete' => date('y-m-d H:i:s'), // 批量删除
				'time_delete' => NULL, // 批量恢复
				// 'name' => 'value', // 批量修改其它数据
				// 'name' => 'value', // 多行可批量修改多个字段值
			);

			// Go Basic!
			$this->basic->bulk($data, $data_to_edit, $op_name, $op_view);
		} // end restore

	}

/* End of file Schedule.php */
/* Location: ./application/controllers/Schedule.php */
