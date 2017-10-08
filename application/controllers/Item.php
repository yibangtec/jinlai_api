<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Item/ITM 商品类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Item extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'tag_price', 'price', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id', 'status',
			'time_create', 'time_delete', 'time_publish', 'time_suspend', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
			'price', 'time_publish', 'time_to_suspend', 'unit_sold',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'item_id', 'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'code_biz', 'url_image_main', 'figure_image_urls', 'figure_video_urls', 'name', 'slogan', 'description', 'tag_price', 'price', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id',
			'time_create', 'time_delete', 'time_publish', 'time_suspend', 'time_edit', 'creator_id', 'operator_id', 'note_admin', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'category_id', 'biz_id', 'url_image_main', 'name', 'price', 'stocks',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'category_biz_id', 'code_biz', 'url_image_main', 'figure_image_urls', 'figure_video_urls', 'name', 'slogan', 'description', 'tag_price', 'price', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id', 'status',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
			'url_image_main', 'name', 'price', 'stocks',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'item'; // 这里……
			$this->id_name = 'item_id'; // 这里……
			$this->names_to_return[] = 'item_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/*
		 * 类特有筛选器
		 */
		protected function advanced_sorter()
		{
			if ( !empty($this->input->post('name')) ):
				$this->db->like('name', $this->input->post('name'));
			endif;

			if ( !empty($this->input->post('price_min')) ):
				$this->db->where('price >=', $this->input->post('price_min'));
			endif;

			if ( !empty($this->input->post('price_max')) ):
				$this->db->where('price <=', $this->input->post('price_max'));
			endif;
		} // end advanced_sorter

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'start_time'):
						$condition['time_create >='] = $this->input->post($sorter);
					elseif ($sorter === 'end_time'):
						$condition['time_create <='] = $this->input->post($sorter);
					else:
						$condition[$sorter] = $this->input->post($sorter);
					endif;
				endif;
			endforeach;
			// 类特有筛选项
			$this->advanced_sorter();
			
			// 商家仅可操作自己的数据
			if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

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
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;
			
			if ($this->input->post('test_mode') === 'on'):
				$this->output->enable_profiler(TRUE);
			endif;

			// 筛选条件
			$condition = NULL;
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'start_time'):
						$condition['time_create >='] = $this->input->post($sorter);
					elseif ($sorter === 'end_time'):
						$condition['time_create <='] = $this->input->post($sorter);
					else:
						$condition[$sorter] = $this->input->post($sorter);
					endif;
				endif;
			endforeach;
			// 类特有筛选项
			$this->advanced_sorter();
			
			// 商家仅可操作自己的数据
			if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

			// 排序条件
			$order_by = NULL;
			// （可选）遍历筛选条件
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

			// 获取列表；默认可获取已删除项
			$ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID
			if ( empty($ids) ):
				// 限制可返回的字段，获取销量
				$this->db->select(
					implode(',', $this->names_to_return).
					',(SELECT SUM(`count`) FROM `order_items` WHERE `time_accepted` IS NOT NULL AND `item_id`= `item`.`item_id`) as unit_sold'
				);
				$items = $this->basic_model->select($condition, $order_by);
			else:
				// 限制可返回的字段
   				$this->db->select( implode(',', $this->names_to_return) );
				$items = $this->basic_model->select_by_ids($ids);
			endif;

			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;

				/*
				$this->db->select('ROUND( AVG(price), 2 ) as avg_price, MAX(price) as max_price, MIN(price) as min_price');
				$query = $this->db->get($this->table_name);
				$table_meta = $query->result_array();
				$this->result['table_meta'] = $table_meta;
				*/

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

			// 商家仅可操作自己的数据
			if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

			// 限制可返回的字段
			//$this->db->select( implode(',', $this->names_to_return) );
			$this->db->select(
				implode(',', $this->names_to_return).
				',(SELECT SUM(`count`) FROM `order_items` WHERE `time_accepted` IS NOT NULL AND `item_id`= `item`.`item_id`) as unit_sold'
			);

			// 获取特定项；默认可获取已删除项
			$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;
				
				// 获取该商品SKU列表
				$this->switch_model('sku', 'sku_id');
				$condition = array(
					'item_id' => $item['item_id'],
				);
				if ($this->app_type === 'client') $condition['time_delete'] = 'NULL';
				$this->result['content']['skus'] = $this->basic_model->select($condition, NULL);

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
			$this->form_validation->set_rules('category_id', '系统分类', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('brand_id', '品牌', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('biz_id', '所属商家ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('category_biz_id', '商家分类', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('code_biz', '商家自定义商品编码', 'trim|max_length[20]');
			$this->form_validation->set_rules('url_image_main', '主图', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('figure_image_urls', '形象图', 'trim|max_length[255]');
			$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|required|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|required|greater_than[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|required|greater_than_equal_to[0]|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

					'figure_image_urls' => trim($this->input->post('figure_image_urls'), ','),
					'figure_video_urls' => trim($this->input->post('figure_video_urls'), ','),
					'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
					'unit_name' => empty($this->input->post('unit_name'))? '份': $this->input->post('unit_name'),
					'quantity_max' => empty($this->input->post('quantity_max'))? '0': $this->input->post('quantity_max'),
					'quantity_min' => empty($this->input->post('quantity_min'))? '1': $this->input->post('quantity_min'),
					'discount_credit' => empty($this->input->post('discount_credit'))? '0.00': $this->input->post('discount_credit'),
					'commission_rate' => empty($this->input->post('commission_rate'))? '0.00': $this->input->post('commission_rate'),
					'time_to_publish' => empty($this->input->post('time_to_publish'))? NULL: $this->input->post('time_to_publish'),
					'time_to_suspend' => empty($this->input->post('time_to_suspend'))? NULL: $this->input->post('time_to_suspend'),
					'time_publish' => empty($this->input->post('time_to_publish'))? time(): NULL, // 若未预订上架时间，则直接上架
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'code_biz', 'url_image_main', 'name', 'slogan', 'description', 'price', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'coupon_allowed', 'promotion_id', 'freight_template_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = $this->input->post($name);

				// 若非定时上架商品，则将当前时间作为上架时间
				if ( empty($data_to_create['time_to_publish']) ) $data_to_create['time_publish'] = time();

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
			$type_allowed = array('biz'); // 客户端类型
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
			$this->form_validation->set_rules('category_biz_id', '商家分类', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('code_biz', '商家自定义商品编码', 'trim|max_length[20]');
			$this->form_validation->set_rules('url_image_main', '主图', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('figure_image_urls', '形象图', 'trim|max_length[255]');
			$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|required|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|required|greater_than[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|required|greater_than_equal_to[0]|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

					'figure_image_urls' => trim($this->input->post('figure_image_urls'), ','),
					'figure_video_urls' => trim($this->input->post('figure_video_urls'), ','),
					'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
					'unit_name' => empty($this->input->post('unit_name'))? '份': $this->input->post('unit_name'),
					'quantity_max' => empty($this->input->post('quantity_max'))? '0': $this->input->post('quantity_max'),
					'quantity_min' => empty($this->input->post('quantity_min'))? '1': $this->input->post('quantity_min'),
					'discount_credit' => empty($this->input->post('discount_credit'))? '0.00': $this->input->post('discount_credit'),
					'commission_rate' => empty($this->input->post('commission_rate'))? '0.00': $this->input->post('commission_rate'),
					'time_to_publish' => empty($this->input->post('time_to_publish'))? NULL: $this->input->post('time_to_publish'),
					'time_to_suspend' => empty($this->input->post('time_to_suspend'))? NULL: $this->input->post('time_to_suspend'),
					'time_publish' => empty($this->input->post('time_to_publish'))? time(): NULL, // 若未预订上架时间，则直接上架
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'category_biz_id', 'code_biz', 'url_image_main', 'name', 'slogan', 'description', 'price', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'coupon_allowed', 'promotion_id', 'freight_template_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = $this->input->post($name);
				
				
				// 商家仅可操作自己的数据
				if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
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
			$type_allowed = array('biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_certain_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
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
			$this->form_validation->set_rules('category_biz_id', '商家分类', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('code_biz', '商家自定义商品编码', 'trim|max_length[20]');
			$this->form_validation->set_rules('url_image_main', '主图', 'trim|max_length[255]');
			$this->form_validation->set_rules('figure_image_urls', '形象图', 'trim|max_length[255]');
			$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|greater_than[0]|less_than_equal_to[99999.99]');
			$this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|greater_than_equal_to[0]|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;
				
				// 商家仅可操作自己的数据
				if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

				// 获取ID
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
			$type_allowed = array('biz'); // 客户端类型
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_bulk_required;
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
			$this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore,suspend,publish]');
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
					case 'publish': // 上架
						$data_to_edit['time_publish'] = time();
						$data_to_edit['time_suspend'] = NULL;
						$data_to_edit['time_to_suspend'] = $data_to_edit['time_to_publish'] = NULL; // 若手动上架，则取消上下架计划
						break;
					case 'suspend': // 下架
						$data_to_edit['time_publish'] = NULL;
						$data_to_edit['time_suspend'] = time();
						$data_to_edit['time_to_suspend'] = $data_to_edit['time_to_publish'] = NULL; // 若手动下架，则取消上下架计划
						break;
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
				
				// 商家仅可操作自己的数据
				if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

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
		 * TODO 批量获取多个商品
		 */
		protected function get_bulk($ids)
		{
			$this->basic_model->select_by_ids();
		} // get_bulk

		// 检查起始时间
		protected function time_start($value)
		{
			if ( empty($value) ):
				return true;

			elseif (strlen($value) !== 10):
				return false;

			else:
				// 该时间不可早于当前时间一分钟以内
				if ($value <= time() + 60):
					return false;
				else:
					return true;
				endif;

			endif;
		} // end time_start

		// 检查结束时间
		protected function time_end($value)
		{
			if ( empty($value) ):
				return true;

			elseif (strlen($value) !== 10):
				return false;

			else:
				// 该时间不可早于当前时间一分钟以内
				if ($value <= time() + 60):
					return false;

				// 若已设置开始时间，不可早于开始时间一分钟以内
				elseif ( !empty($this->input->post('time_to_publish')) && $value <= strtotime($this->input->post('time_to_publish')) + 60):
					return false;

				else:
					return true;

				endif;

			endif;
		} // end time_end

	} // end class Item

/* End of file Item.php */
/* Location: ./application/controllers/Item.php */
