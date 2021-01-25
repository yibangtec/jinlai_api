<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Sku/SKU 商品规格类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Sku extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'biz_id', 'item_id', 'url_image', 'name_first', 'name_second', 'name_third', 'tag_price', 'price', 'stocks', 'weight_net', 'weight_gross', 'weight_volume',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'sku_id', 'biz_id', 'item_id', 'url_image', 'name_first', 'name_second', 'name_third', 'tag_price', 'price', 'stocks', 'weight_net', 'weight_gross', 'weight_volume',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
		    'user_id',
            'biz_id', 'item_id', 'name_first', 'price',
        );

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'url_image', 'name_first', 'name_second', 'name_third', 'tag_price', 'price', 'stocks', 'weight_net', 'weight_gross', 'weight_volume',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
		    'user_id', 'id',
            'name_first', 'price',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'sku'; // 这里……
			$this->id_name = 'sku_id'; // 这里……

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
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

            // 生成筛选条件
            $condition = $this->condition_generate();
			
			// 排序条件
            // 若请求不来自管理端，按规格名称排列
            if ($this->app_type === 'admin'):
                $order_by['time_create'] = 'DESC';
            else:
                $order_by['name_first'] = 'ASC';
                $order_by['name_second'] = 'ASC';
                $order_by['name_third'] = 'ASC';
            endif;

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 限制可返回的字段
                if ($this->app_type === 'client'):
                    $condition['time_delete'] = 'NULL';
                    $this->names_to_return = array_diff($this->names_to_return, $this->names_return_for_admin);
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
			$type_allowed = array('biz'); // 客户端类型
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
			$this->form_validation->set_rules('item_id', '所属商品ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('url_image', '图片', 'trim|max_length[255]');
            $this->form_validation->set_rules('name_first', '名称第一部分', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('name_second', '名称第二部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('name_third', '名称第三部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('price', '价格（元）', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|is_natural_no_zero|less_than_equal_to[65535]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

                    'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
                    'stocks' => empty($this->input->post('stocks'))? 0: $this->input->post('stocks'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'biz_id', 'item_id', 'url_image', 'name_first', 'name_second', 'name_third', 'price', 'weight_net', 'weight_gross', 'weight_volume',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				$result = $this->basic_model->create($data_to_create, TRUE);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $result;
					$this->result['content']['message'] = '创建成功';

                    // 更新所属商品相关信息
                    $this->update_item_for_sku(NULL, $item_id);

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
			$type_allowed = array('biz'); // 客户端类型
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
			$this->form_validation->set_rules('url_image', '图片', 'trim|max_length[255]');
            $this->form_validation->set_rules('name_first', '名称第一部分', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('name_second', '名称第二部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('name_third', '名称第三部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('price', '价格（元）', 'trim|required|greater_than[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|is_natural_no_zero|less_than_equal_to[65535]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

                    'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
                    'stocks' => empty($this->input->post('stocks'))? 0: $this->input->post('stocks'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'url_image', 'name_first', 'name_second', 'name_third', 'price', 'weight_net', 'weight_gross', 'weight_volume',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['id'] = $id;
                    $this->result['content']['message'] = '编辑成功';

                    // 更新所属商品相关信息
                    $this->update_item_for_sku($id);

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
			$type_allowed = array('biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

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
			$this->form_validation->set_rules('url_image', '图片', 'trim|max_length[255]');
            $this->form_validation->set_rules('name_first', '名称第一部分', 'trim|required|max_length[15]');
            $this->form_validation->set_rules('name_second', '名称第二部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('name_third', '名称第三部分', 'trim|max_length[15]');
            $this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('price', '价格（元）', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|greater_than_equal_to[0]|less_than_equal_to[65535]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');

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

                    // 更新所属商品相关信息
                    $this->update_item_for_sku($id);

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
            $type_allowed = array('biz'); // 客户端类型
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
            $this->common_edit_bulk(TRUE, 'delete,restore,suspend,publish');

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
                    case 'delete': // 删除
                        $data_to_edit['time_delete'] = date('Y-m-d H:i:s');
                        break;
                    case 'restore': // 恢复
                        $data_to_edit['time_delete'] = NULL;
                        break;
                endswitch;

                // 依次操作数据并输出操作结果
                // 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
                $ids = explode(',', $ids);

                // 商家仅可操作自己的数据
                if ($this->app_type === 'biz')
                    $this->db->where('biz_id', $this->input->post('biz_id'));

                // 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
                $this->result['status'] = 200;
                foreach ($ids as $id):
                    $result = $this->basic_model->edit($id, $data_to_edit);
                    if ($result === FALSE):
                        $this->result['status'] = 434;
                        $this->result['content']['row_failed'][] = $id;
                    endif;

                    // 更新所属商品相关信息
                    $this->update_item_for_sku($id);
                endforeach;

                // 添加全部操作成功后的提示
                if ($this->result['status'] = 200)
                    $this->result['content']['message'] = '全部操作成功';

            endif;
        } // end edit_bulk

        /**
         * 以下为工具方法
         */

	} // end Class Sku

/* End of file Sku.php */
/* Location: ./application/controllers/Sku.php */
