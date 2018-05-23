<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Ornament_biz/ORN 店铺装修类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Ornament_biz extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'biz_id', 'name', 'template_id', 'vi_color_first', 'vi_color_second',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'ornament_id', 'biz_id', 'name', 'vi_color_first', 'vi_color_second', 'main_figure_url', 'member_logo_url', 'member_figure_url', 'member_thumb_url', 'home_json', 'home_html', 'template_id', 'home_slides', 'home_m0_ids', 'home_m1_ace_url', 'home_m1_ace_id', 'home_m1_ids', 'home_m2_ace_url', 'home_m2_ace_id', 'home_m2_ids', 'home_m3_ace_url', 'home_m3_ace_id', 'home_m3_ids',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id', 'biz_id', 'name',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'name', 'vi_color_first', 'vi_color_second', 'main_figure_url', 'member_logo_url', 'member_figure_url', 'member_thumb_url', 'home_json', 'home_html', 'template_id', 'home_slides', 'home_m0_ids', 'home_m1_ace_url', 'home_m1_ace_id', 'home_m1_ids', 'home_m2_ace_url', 'home_m2_ace_id', 'home_m2_ids', 'home_m3_ace_url', 'home_m3_ace_id', 'home_m3_ids',
		);

        /**
         * 编辑单行时必要的字段名
         */
        protected $names_edit_required = array(
            'user_id', 'id', 'name',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'ornament_biz'; // 这里……
			$this->id_name = 'ornament_id'; // 这里……

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
			$order_by = NULL;

            // 限制可返回的字段
            if ($this->app_type === 'client'):
                $condition['time_delete'] = 'NULL';
                $this->names_to_return = array_diff($this->names_to_return, $this->names_return_for_admin);
            endif;
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
            $this->form_validation->set_rules('name', '方案名称', 'trim|required|max_length[30]');
            $this->form_validation->set_rules('vi_color_first', '第一识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('vi_color_second', '第二识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('main_figure_url', '主形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_logo_url', '会员卡LOGO', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_figure_url', '会员卡封图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_thumb_url', '会员卡列表图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_json', '首页JSON格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('home_html', '首页HTML格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('template_id', '装修模板', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_slides', '顶部模块轮播图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m0_ids', '顶部模块陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_url', '模块一形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_id', '模块一首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m1_ids', '模块一陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_url', '模块二形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_id', '模块二首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m2_ids', '模块二陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_url', '模块三形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_id', '模块三首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m3_ids', '模块三陈列商品', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,
                    'vi_color_first' => strtolower( $this->input->post('vi_color_first') ),
                    'vi_color_second' => strtolower( $this->input->post('vi_color_second') ),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'biz_id', 'name', 'main_figure_url', 'member_logo_url', 'member_figure_url', 'member_thumb_url', 'home_json', 'home_html', 'template_id', 'home_slides', 'home_m0_ids', 'home_m1_ace_url', 'home_m1_ace_id', 'home_m1_ids', 'home_m2_ace_url', 'home_m2_ace_id', 'home_m2_ids', 'home_m3_ace_url', 'home_m3_ace_id', 'home_m3_ids',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				$created_id = $this->basic_model->create($data_to_create, TRUE);
				if ($created_id !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $created_id;
					$this->result['content']['message'] = '创建成功';

					// 若为商家端，检查当前商家是否已有使用中的店铺装修；若无，则设置刚创建的装修方案为默认装修
                    if ($this->app_type === 'biz'):
                        $this->default_this($biz_id, $created_id);
                    endif;

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
            $type_allowed = array('admin', 'biz',); // 客户端类型
            $this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_required;
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
            $this->form_validation->set_rules('name', '方案名称', 'trim|required|max_length[30]');
            $this->form_validation->set_rules('vi_color_first', '第一识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('vi_color_second', '第二识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('main_figure_url', '主形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_logo_url', '会员卡LOGO', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_figure_url', '会员卡封图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_thumb_url', '会员卡列表图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_json', '首页JSON格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('home_html', '首页HTML格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('template_id', '装修模板', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_slides', '顶部模块轮播图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m0_ids', '顶部模块陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_url', '模块一形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_id', '模块一首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m1_ids', '模块一陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_url', '模块二形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_id', '模块二首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m2_ids', '模块二陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_url', '模块三形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_id', '模块三首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m3_ids', '模块三陈列商品', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
                    'vi_color_first' => strtolower( $this->input->post('vi_color_first') ),
                    'vi_color_second' => strtolower( $this->input->post('vi_color_second') ),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'name', 'main_figure_url', 'member_logo_url', 'member_figure_url', 'member_thumb_url', 'home_json', 'home_html', 'template_id', 'home_slides', 'home_m0_ids', 'home_m1_ace_url', 'home_m1_ace_id', 'home_m1_ids', 'home_m2_ace_url', 'home_m2_ace_id', 'home_m2_ids', 'home_m3_ace_url', 'home_m3_ace_id', 'home_m3_ids',
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
            $type_allowed = array('admin', 'biz',); // 客户端类型
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
			$this->form_validation->set_rules('name', '方案名称', 'trim|max_length[30]');
            $this->form_validation->set_rules('vi_color_first', '第一识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('vi_color_second', '第二识别色', 'trim|min_length[3]|max_length[6]');
            $this->form_validation->set_rules('main_figure_url', '主形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_logo_url', '会员卡LOGO', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_figure_url', '会员卡封图', 'trim|max_length[255]');
            $this->form_validation->set_rules('member_thumb_url', '会员卡列表图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_json', '首页JSON格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('home_html', '首页HTML格式内容', 'trim|max_length[20000]');
            $this->form_validation->set_rules('template_id', '装修模板', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_slides', '顶部模块轮播图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m0_ids', '顶部模块陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_url', '模块一形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m1_ace_id', '模块一首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m1_ids', '模块一陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_url', '模块二形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m2_ace_id', '模块二首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m2_ids', '模块二陈列商品', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_url', '模块三形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('home_m3_ace_id', '模块三首推商品', 'trim|max_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('home_m3_ids', '模块三陈列商品', 'trim|max_length[255]');

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
            $type_allowed = array('admin', 'biz',); // 客户端类型
            $this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_bulk_required;
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

        /**
         * 设置特定装修方案为特定商家的默认装修方案
         *
         * @param string $biz_id
         * @param string $id
         */
        public function default_this($biz_id, $id)
        {
            $this->switch_model('biz', 'biz_id');
            $biz = $this->basic_model->select_by_id($biz_id);

            if ( !empty($biz) && empty($biz['ornament_id']) ):
                $data_to_edit['ornament_id'] = $id;
                $result = $this->basic_model->edit($biz_id, $data_to_edit);
                if ($result !== FALSE):
                    $this->result['content']['message'] .= '，已设为默认店铺装修方案';
                endif;

            endif;
        } // default_this

	} // end class Ornament_biz

/* End of file Ornament_biz.php */
/* Location: ./application/controllers/Ornament_biz.php */
