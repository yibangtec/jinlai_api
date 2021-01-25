<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Item_category/ITK 平台商品分类类
	 *
	 * 系统级商品分类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Item_category extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
            'ancestor_id', 'parent_id', 'nature', 'level', 'name',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'category_id', 'ancestor_id', 'parent_id', 'nature', 'level', 'name', 'description', 'url_name', 'url_image', 'url_image_index', 'url_image_detail', 'item_id',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
		    'user_id',
            'name',
        );

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
            'ancestor_id', 'parent_id', 'name', 'description', 'url_name', 'url_image', 'url_image_index', 'url_image_detail', 'item_id',
        );

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
		    'user_id', 'id',
            'name',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'item_category'; // 这里……
			$this->id_name = 'category_id'; // 这里……

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

			// 获取列表；默认不获取已删除项
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
            $order_by = NULL;
            if ($this->app_type === 'client'):
                $order_by['nature'] = $order_by['ancestor_id'] = $order_by['parent_id'] = $order_by['level'] = 'ASC';
                //$order_by['time_edit'] = 'DESC'; // 临时性调序，主要用于演示视频生成
            endif;

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 限制可返回的字段
                if ($this->app_type === 'client'):
                    $condition['time_delete'] = 'NULL';
                    $this->names_to_return = array_diff($this->names_to_return, $this->names_return_for_admin);
                endif;

                // 仅获取有商品在售的分类
                if ($this->input->post('available') != 1):
                    $this->db->select( implode(',', $this->names_to_return) );
                    $items = $this->basic_model->select($condition, $order_by);
                else:
                    $this->load->model('item_category_model');
                    $this->switch_model('item', 'item_id');
                    $items = $this->item_category_model->select_available($condition);
                endif;
            else:
                $items = $this->basic_model->select_by_ids($ids);
            endif;

			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;
				$this->result['count'] = count($items);

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
			$url_name = $this->input->post('url_name');
			if ( empty($id) && empty($url_name) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取特定项；默认可获取已删除项
			if ( !empty($url_name) ):
				$item = $this->basic_model->find('url_name', $url_name);
			else:
				$item = $this->basic_model->select_by_id($id);
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
			$type_allowed = array('admin'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
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
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
            $this->form_validation->set_rules('parent_id', '所属上级分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('nature', '商品性质', 'trim|in_list[商品,服务]');
            $this->form_validation->set_rules('name', '名称', 'trim|required|max_length[30]');
            $this->form_validation->set_rules('description', '描述', 'trim|max_length[100]');
            $this->form_validation->set_rules('url_name', '自定义域名', 'trim|alpha_dash|max_length[30]');
            $this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('url_image_index', '列表页形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('url_image_detail', '详情页形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('item_id', '主推商品ID', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 若指定所属分类，获取并相关信息
                $parent_id = $this->input->post('parent_id');
                if ( ! empty($parent_id)):
                    $parent_category = $this->get_item('item_category', 'category_id', $parent_id, FALSE);
                    $ancestor_id = $parent_category['parent_id']; // 根据所属上级分类设置所属顶级分类
                endif;

				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

					'url_name' => empty($this->input->post('url_name'))? NULL: strtolower($this->input->post('url_name')),

                    'ancestor_id' => isset($ancestor_id)? $ancestor_id: NULL,
                    'parent_id' => empty($parent_id)? NULL: $parent_id,
                    'nature' => empty($parent_category)? $this->input->post('nature'): $parent_category['nature'],
                    'level' => empty($parent_category)? 1: ($parent_category['level'] + 1),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'name', 'url_image', 'url_image_index', 'url_image_detail', 'item_id', 'description',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

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
			$type_allowed = array('admin'); // 客户端类型
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
            $this->form_validation->set_rules('parent_id', '所属分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('nature', '商品性质', 'trim|in_list[商品,服务]');
            $this->form_validation->set_rules('name', '名称', 'trim|required|max_length[30]');
            $this->form_validation->set_rules('description', '描述', 'trim|max_length[100]');
            $this->form_validation->set_rules('url_name', '自定义域名', 'trim|alpha_dash|max_length[30]');
            $this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('url_image_index', '列表页形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('url_image_detail', '详情页形象图', 'trim|max_length[255]');
            $this->form_validation->set_rules('item_id', '主推商品ID', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 若指定所属分类，获取并相关信息
                $parent_id = $this->input->post('parent_id');
                if ( ! empty($parent_id)):
                    $parent_category = $this->get_item('item_category', 'category_id', $parent_id, FALSE);
                    $ancestor_id = empty($parent_category['parent_id'])? $parent_category['category_id']: $parent_category['parent_id']; // 根据所属上级分类设置所属顶级分类
                endif;

                // 级别
                $level = empty($parent_category)? 1: ($parent_category['level'] + 1);

				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

                    'url_name' => empty($this->input->post('url_name'))? NULL: strtolower($this->input->post('url_name')),

                    'ancestor_id' => isset($ancestor_id)? $ancestor_id: NULL,
                    'parent_id' => empty($parent_id)? NULL: $parent_id,
                    'nature' => empty($parent_category)? $this->input->post('nature'): $parent_category['nature'],
                    'level' => $level,
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'name', 'url_image', 'url_image_index', 'url_image_detail', 'item_id', 'description',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
					$this->result['status'] = 200;
                    $this->result['content']['id'] = $id;
                    $this->result['content']['message'] = '编辑成功';

                    // 若当前不是顶级分类，则更新所有下属分类的所属顶级分类
                    if ( ! empty($parent_id)):
                        $level += 1;
                        $query = $this->db->query("UPDATE `item_category` SET `level` = $level, `ancestor_id` = $ancestor_id WHERE `parent_id` = $id");
                    endif;

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
			$type_allowed = array('admin'); // 客户端类型
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
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array('operator_id');
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 根据待执行的操作赋值待编辑数据
				switch ( $this->input->post('operation') ):
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
         * 以下为工具类方法
         */

	} // end class Item_category

/* End of file Item_category.php */
/* Location: ./application/controllers/Item_category.php */
