<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Biz/BIZ 商家类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Biz extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'category_id', 'name', 'brief_name', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'biz_id', 'identity_id', 'category_id', 'name', 'brief_name', 'url_name', 'url_logo', 'slogan', 'description', 'notification',
			'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
            'url_image_product', 'url_image_produce', 'url_image_retail',
            'freight_template_id', 'ornament_id',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
            'category_id', 'name', 'brief_name', 'tel_public', 'tel_protected_biz',
		);

        /**
         * 快速创建时必要的字段名
         */
        protected $names_quick_create_required = array(
            'user_id',
            'category_id', 'brief_name', 'tel_public',
        );

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
            'category_id', 'name', 'brief_name', 'url_name', 'url_logo', 'slogan', 'description', 'notification',
			'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
			'url_image_product', 'url_image_produce', 'url_image_retail',
            'freight_template_id', 'ornament_id',
		);

		/**
		 * 编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
            'tel_public',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'biz'; // 这里……
			$this->id_name = 'biz_id';  // 还有这里，OK，这就可以了

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
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

			// 客户端仅获取状态为‘正常’的商家
			if ($this->app_type === 'client')
				$condition['status'] = '正常';

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
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

			// 排序条件
			$order_by = NULL;

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                if ($this->app_type === 'client'):
                    $this->load->model('biz_model');
                    $items = $this->biz_model->select($condition, $order_by);
                else:
                    $items = $this->basic_model->select($condition, $order_by);
                endif;
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
                $this->result['content']['ornament'] = new stdClass(); // 若无装修方案，返回一个空对象

				// 客户端同时返回已上架商品数量
                if ($this->app_type === 'client'):
                    $this->switch_model('item', 'item_id');
                    $condition = array(
                        'biz_id' => $id,
                        'time_delete' => 'NULL',
                    );
                    $this->result['content']['item_count'] = $this->basic_model->count($condition);
                endif;

                // 客户端同时获取商家店铺装修方案（若有）
                if (($this->app_type === 'client') && !empty($item['ornament_id']) ):
                    $this->switch_model('ornament_biz', 'ornament_id');
                    $this->result['content']['ornament'] = $this->basic_model->select_by_id($item['ornament_id']);
                endif;

                // 获取当前商家可用的优惠券模板信息
                $this->switch_model('coupon_template', 'template_id');
                if ($this->app_type === 'client') $this->db->where('time_delete IS NULL');
                $this->db->where('biz_id', $item['biz_id']);
                // 仅获取未超出可用期限的优惠券
                $this->db->group_start()
                    ->where('time_end IS NULL')
                    ->or_where('time_end > NOW()')
                    ->group_end();
                $this->db->order_by('amount', 'DESC'); // 按金额降序排序
                $this->db->limit(3, 0); // 最多获取3个
                $this->result['content']['coupon_templates'] = $this->basic_model->select(NULL, NULL);

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
			$type_allowed = array('admin', 'biz'); // 客户端类型
			$this->client_check($type_allowed);

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

			// 检查想要创建商家的用户是否已是其它商家的员工
			if ( !empty( $this->stuff_exist($user_id) ) ):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = '该用户已是其它商家的管理员或员工，不可创建新商家';
				exit();
			endif;

			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
            $this->form_validation->set_rules('category_id', '主营商品类目', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('name', '商家全称', 'trim|required|min_length[5]|max_length[35]|is_unique['.$this->table_name.'.name]');
			$this->form_validation->set_rules('brief_name', '店铺名称', 'trim|required|max_length[20]|is_unique['.$this->table_name.'.brief_name]');
            $this->form_validation->set_rules('url_logo', '店铺LOGO', 'trim|max_length[255]');
			$this->form_validation->set_rules('description', '简介', 'trim|max_length[255]');
			$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|required|min_length[10]|max_length[13]|is_unique['.$this->table_name.'.tel_public]');
			$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|required|exact_length[11]|is_natural|is_unique['.$this->table_name.'.tel_protected_biz]');
			$this->form_validation->set_rules('tel_protected_fiscal', '财务联系手机号', 'trim|required|exact_length[11]|is_natural|is_unique['.$this->table_name.'.tel_protected_fiscal]');
			$this->form_validation->set_rules('url_image_product', '产品', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_produce', '工厂/产地', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_retail', '门店/柜台', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
                    'category_id', 'name', 'brief_name', 'url_logo', 'description', 'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'url_image_product', 'url_image_produce', 'url_image_retail',
                );
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);
				// 从待创建数据中去除biz表中没有的user_id值，该值用于稍后创建员工关系
				unset($data_to_create['user_id']);

				// 创建商家
				$biz_id = $this->basic_model->create($data_to_create, TRUE);
				if ($biz_id !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $biz_id;
					$this->result['content']['message'] = '创建商家成功，我们将尽快受理您的入驻申请';

                    // 创建当前用户为该商家的管理员
                    $this->admin_stuff_create($user_id, $biz_id, $tel_protected_biz);

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建商家失败';

				endif;
			endif;
		} // end create

        /**
         * 7 快速创建
         */
        public function create_quick()
        {
            // 操作可能需要检查客户端及设备信息
            $type_allowed = array('admin', 'biz'); // 客户端类型
            $this->client_check($type_allowed);

            // 检查必要参数是否已传入
            $required_params = $this->names_quick_create_required;
            foreach ($required_params as $param):
                ${$param} = trim($this->input->post($param));
                if ( empty( ${$param} ) ):
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                    exit();
                endif;
            endforeach;

            // 检查想要创建商家的用户是否已是其它商家的员工
            if ( !empty( $this->stuff_exist($user_id) ) ):
                $this->result['status'] = 401;
                $this->result['content']['error']['message'] = '该用户已是其它商家的管理员或员工，不可创建新商家';
                exit();
            endif;

            // 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('', '');
            $this->form_validation->set_rules('category_id', '主营商品类目', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('brief_name', '店铺名称', 'trim|required|max_length[20]|is_unique['.$this->table_name.'.brief_name]');
            $this->form_validation->set_rules('url_logo', '店铺LOGO', 'trim|max_length[255]');
            $this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|required|min_length[10]|max_length[13]|is_unique['.$this->table_name.'.tel_public]');

            // 若表单提交不成功
            if ($this->form_validation->run() === FALSE):
                $this->result['status'] = 401;
                $this->result['content']['error']['message'] = validation_errors();

            else:
                // 需要创建的数据；逐一赋值需特别处理的字段
                $data_to_create = array(
                    'creator_id' => $user_id,
                    'tel_public' => $tel_public,
                    'tel_protected_biz' => $tel_public,
                    'tel_protected_fiscal' => $tel_public,
                    'tel_protected_order' => $tel_public,
                );
                // 自动生成无需特别处理的数据
                $data_need_no_prepare = array(
                    'category_id', 'url_logo', 'brief_name',
                );
                foreach ($data_need_no_prepare as $name)
                    $data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);
                // 从待创建数据中去除biz表中没有的user_id值，该值用于稍后创建员工关系
                unset($data_to_create['user_id']);

                // 创建商家
                $biz_id = $this->basic_model->create($data_to_create, TRUE);
                if ($biz_id !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['id'] = $biz_id;
                    $this->result['content']['message'] = '创建商家成功，我们将尽快受理您的入驻申请';

                    // 创建当前用户为该商家的管理员
                    $this->admin_stuff_create($user_id, $biz_id, $tel_public);

                else:
                    $this->result['status'] = 424;
                    $this->result['content']['error']['message'] = '创建商家失败';

                endif;
            endif;
        } // end create_quick

		/**
		 * 4 编辑单行数据
		 */
		public function edit()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 操作可能需要检查操作权限
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

			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			if ($this->app_type === 'admin'):
                $this->form_validation->set_rules('category_id', '主营商品类目', 'trim|required|is_natural_no_zero');
				$this->form_validation->set_rules('name', '商家全称', 'trim|required|min_length[5]|max_length[35]');
				$this->form_validation->set_rules('brief_name', '店铺名称', 'trim|required|max_length[20]');
				$this->form_validation->set_rules('url_name', '店铺域名', 'trim|max_length[20]|alpha_dash');
				$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|required|exact_length[11]|is_natural');
			endif;
			// 载入验证规则
			$rule_path = APPPATH. 'libraries/form_rules/Biz.php';
			require_once($rule_path);
            $this->form_validation->set_rules('freight_template_id', '运费模板ID', 'trim');
            $this->form_validation->set_rules('ornament_id', '店铺装修ID', 'trim');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
					'url_name' => strtolower( $this->input->post('url_name') ),
				);

				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
                    'category_id', 'name', 'brief_name', 'url_logo', 'slogan', 'description', 'notification',
					'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
                    'url_image_product', 'url_image_produce', 'url_image_retail',
                    'freight_template_id', 'ornament_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 根据客户端类型等条件筛选可操作的字段名
				if ($this->app_type !== 'admin'):
                    unset($data_to_edit['category_id']);
					unset($data_to_edit['name']);
					unset($data_to_edit['brief_name']);
					unset($data_to_edit['url_name']);
					unset($data_to_edit['tel_protected_biz']);
				endif;

				// 获取ID
				$id = $this->input->post('id');
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['message'] = '商家编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '商家修改失败';

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
			$names_limited = array(
				'biz' => array(
					'name', 'brief_name', 'url_name', 'tel_protected_biz',
				),
				'admin' => array(),
			);
			if ( in_array($name, $names_limited[$this->app_type]) ):
				$this->result['status'] = 431;
				$this->result['content']['error']['message'] = '该字段不可被当前类型客户端修改';
				exit();
			endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
			if ($this->app_type === 'admin'):
                $this->form_validation->set_rules('category_id', '主营商品类目', 'trim|required|is_natural_no_zero');
				$this->form_validation->set_rules('name', '商家名称', 'trim|min_length[5]|max_length[35]');
				$this->form_validation->set_rules('brief_name', '店铺名称', 'trim|max_length[20]');
				$this->form_validation->set_rules('url_name', '店铺域名', 'trim|max_length[20]|alpha_dash');
				$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|exact_length[11]|is_natural|is_unique['.$this->table_name.'.tel_protected_biz]');
			endif;
            $this->form_validation->set_rules('url_logo', '店铺LOGO', 'trim|max_length[255]');
			$this->form_validation->set_rules('slogan', '宣传语', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '简介', 'trim|max_length[255]');
			$this->form_validation->set_rules('notification', '店铺公告', 'trim|max_length[255]');

			$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|min_length[10]|max_length[13]|is_unique['.$this->table_name.'.tel_public]');
			$this->form_validation->set_rules('tel_protected_fiscal', '财务联系手机号', 'trim|exact_length[11]|is_natural|is_unique['.$this->table_name.'.tel_protected_fiscal]');
			$this->form_validation->set_rules('tel_protected_order', '订单通知手机号', 'trim|exact_length[11]|is_natural|is_unique['.$this->table_name.'.tel_protected_order]');

			$this->form_validation->set_rules('url_image_product', '产品', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_produce', '工厂/产地', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_retail', '门店/柜台', 'trim|max_length[255]');

            $this->form_validation->set_rules('freight_template_id', '运费模板ID', 'trim');
            $this->form_validation->set_rules('ornament_id', '店铺装修ID', 'trim');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;

				// 获取ID
				$id = $this->input->post('id');
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
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
			$type_allowed = array('admin'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

            $this->common_edit_bulk(TRUE); // 此类型方法通用代码块

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
					$this->result['content'] = '全部操作成功';

			endif;
		} // end edit_bulk

        /*
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

        // 查找员工
        private function stuff_exist($user_id)
        {
            // 切换数据库
            $this->switch_model('stuff', 'stuff_id');
            $condition = array(
                'user_id' => $user_id,
                'time_delete' => 'IS NOT NULL'
            );
            $result = $this->basic_model->match($condition);
            $this->reset_model();

            return $result;
        } // end stuff_exist

        // 创建管理员
        private function admin_stuff_create($user_id, $biz_id, $mobile)
        {
            // 创建员工
            $stuff_id = $this->stuff_create($user_id, $biz_id, $mobile);
            if ($stuff_id !== FALSE):
                $this->result['content']['stuff_id'] = $stuff_id;
                $this->result['content']['message'] .= '，您已成为该商家的管理员';
            endif;

            // 发送商家通知短信
            $sms_content = '恭喜您成功创建商家，我们已为您生成入驻申请并安排事务最少的同事为您受理，敬请稍候。';
            @$this->sms_send($mobile, $sms_content); // 容忍发送失败

            // 发送招商经理通知短信
            $mobile = '15192098644';
            $sms_content = '商家“'.$this->input->post('brief_name').'(商家编号'.$biz_id.')”已提交入驻申请，请尽快跟进，对方商务联系手机号为'.$mobile.'。';
            @$this->sms_send($mobile, $sms_content); // 容忍发送失败
        } // end admin_stuff_create

        // 创建员工
        private function stuff_create($user_id, $biz_id, $mobile)
        {
            // 切换数据库
            $this->switch_model('stuff', 'stuff_id');

            // 创建员工为管理员并授予最高权限
            $data_to_create = array(
                'user_id' => $user_id,
                'biz_id' => $biz_id,
                'mobile' => $mobile,
                'role' => '管理员',
                'level' => '100',
                'creator_id' => $user_id,
            );
            $result = $this->basic_model->create($data_to_create, TRUE);

            return $result;
        } // end stuff_create

	} // end class Biz

/* End of file Biz.php */
/* Location: ./application/controllers/Biz.php */
