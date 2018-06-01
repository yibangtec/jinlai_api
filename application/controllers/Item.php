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
			'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'code_biz', 'barcode', 'tag_price', 'price', 'price_min', 'price_max', 'sold_overall', 'sold_monthly', 'sold_daily', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id', 'status',
			'time_create', 'time_delete', 'time_publish', 'time_suspend', 'time_edit', 'creator_id', 'operator_id',
		);

        /**
         * @var array 可根据最大值筛选的字段名
         */
        protected $max_needed = array(
            'time_create', 'tag_price', 'price', 'stocks',
        );

        /**
         * @var array 可根据最小值筛选的字段名
         */
        protected $min_needed = array(
            'time_create', 'tag_price', 'price', 'stocks',
        );

		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
            'tag_price', 'price', 'stocks', 'time_publish', 'time_to_suspend', 'sold_overall', 'sold_monthly', 'sold_daily', 'time_create',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'item_id', 'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'code_biz', 'barcode', 'url_image_main', 'figure_image_urls', 'figure_video_urls', 'name', 'slogan', 'description', 'tag_price', 'price', 'sold_overall', 'sold_monthly', 'sold_daily', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id',
			'time_create', 'time_delete', 'time_publish', 'time_suspend', 'time_edit', 'creator_id', 'operator_id', 'note_admin', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
            'category_id', 'biz_id', 'url_image_main', 'name', 'price',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'category_biz_id', 'code_biz', 'barcode', 'url_image_main', 'figure_image_urls', 'figure_video_urls', 'name', 'slogan', 'description', 'tag_price', 'price', 'unit_name', 'weight_net', 'weight_gross', 'weight_volume', 'stocks', 'quantity_max', 'quantity_min', 'coupon_allowed', 'discount_credit', 'commission_rate', 'time_to_publish', 'time_to_suspend', 'promotion_id', 'freight_template_id', 'status',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
		    'user_id', 'id',
            'url_image_main', 'name', 'price',
        );

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'item'; // 这里……
			$this->id_name = 'item_id'; // 这里……

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
			
			// 商家仅可操作自己的数据
            if ($this->app_type === 'biz') $condition['biz_id'] = $this->input->post('biz_id');

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
            foreach ($this->names_to_order as $sorter):
                if ( !empty($this->input->post('orderby_'.$sorter)) )
                    $order_by[$sorter] = $this->input->post('orderby_'.$sorter);
            endforeach;
            $order_by['time_publish'] = 'DESC';

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 用户仅可查看未删除、已上架、库存不为0项
                if ($this->app_type === 'client'):
                    $condition['time_delete'] = 'NULL';
                    $condition['time_publish'] = 'IS NOT NULL';
                    $condition['stocks >'] = 0;
                endif;
                $this->db->select( implode(',', $this->names_to_return) );
                $items = $this->basic_model->select($condition, $order_by);
            // 限制可返回的字段
			else:
				$items = $this->basic_model->select_by_ids($ids);
			endif;

			if ( ! empty($items)):
                // 若为客户端调用，则一并返回规格
                if ($this->app_type === 'client'):
                    // 获取各项相应规格
                    $this->switch_model('sku', 'sku_id');
                    for ($i=0;$i<count($items);$i++):
                        $this->db->select('sku_id, biz_id, item_id, url_image, name_first, name_second, name_third, tag_price, price, stocks, weight_net, weight_gross, weight_volume');

                        $condition = array('item_id' => $items[$i]['item_id']);
                        $items[$i]['skus'] = $this->basic_model->select($condition, NULL);
                    endfor;
				endif;

				// 若非客户端调用，则输出相应统计信息
                /*
				if ($this->app_type !== 'client'):
					$this->reset_model(); // 重置数据库
					$this->db->select('ROUND( AVG(price), 2 ) as avg_price, MAX(price) as max_price, MIN(price) as min_price');
					$query = $this->db->get($this->table_name);
					$this->result['table_meta'] = $query->result_array();
				endif;
                */

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
            $barcode = $this->input->post('barcode');
			if ( empty($id.$barcode) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

            // 用户仅可查看未删除项
            if ($this->app_type === 'client') $this->db->where('time_delete IS NULL');

			// 限制可返回的字段
			$this->db->select(implode(',', $this->names_to_return));

			// 获取特定项；默认可获取已删除项
            if ( !empty($barcode) ):
                $item = $this->basic_model->find('barcode', $barcode);
            else:
                $item = $this->basic_model->select_by_id($id);
            endif;

			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;

                // 获取该商品相关SKU列表
                $this->switch_model('sku', 'sku_id');
                $conditions = array(
                    'item_id' => $item['item_id'],
                    'time_delete' => 'NULL',
                );
                $this->db->select('sku_id, biz_id, item_id, url_image, name_first, name_second, name_third, tag_price, price, stocks, weight_net, weight_gross, weight_volume');
                $this->result['content']['skus'] = $this->basic_model->select($conditions, NULL);
                // 若存在规格，则以各规格的库存量求和作为商品库存量
                if ( ! empty($this->result['content']['skus']) ):
                    $sku_stocks_total = 0;
                    foreach ($this->result['content']['skus'] as $sku):
                        $sku_stocks_total += $sku['stocks'];
                    endforeach;
                    $this->result['content']['stocks'] = $sku_stocks_total;
                endif;

                // 若请求来自客户端，额外获取一些信息
				if ($this->app_type === 'client'):
                    // 获取该商品所属商家基本信息、在售商品总数、被关注数、各项评分、当前商品评价列表等信息
                    $this->switch_model('biz', 'biz_id');
                    $this->db->select(
                        'brief_name, url_logo, slogan, tel_public,
                        (SELECT COUNT(*) FROM item WHERE item.biz_id = biz.biz_id AND time_publish IS NOT NULL) AS item_count,
                        (SELECT COUNT(*) FROM fav_biz WHERE fav_biz.biz_id = biz.biz_id AND time_delete IS NULL) AS fav_biz_count'
                    );
                    $this->result['content']['biz'] = $this->basic_model->select_by_id($item['biz_id']);

                        // 获取该商家商品描述评价分数
                        $this->switch_model('comment_item', 'comment_id');
                        $this->db->select('AVG(`score`) AS score_description');
                        $conditions = array(
                            'biz_id' => $item['biz_id'],
                        );
                        $result = $this->basic_model->select($conditions);
                        $this->result['content']['biz']['score_description'] = !empty($result['score_description'])? $result['score_description']: 4.5;

                        // 获取该商家服务态度、物流配送、环境分数（客户端按需取用）
                        $this->switch_model('comment_biz', 'comment_id');
                        $this->db->select('AVG(`score_service`) AS score_service, AVG(`score_deliver`) AS score_deliver, AVG(`score_environment`) AS score_environment');
                        $result = $this->basic_model->select($conditions);
                        $this->result['content']['biz']['score_service'] = !empty($result['score_service'])? $result['score_service']: 4.5;
                        $this->result['content']['biz']['score_deliver'] = !empty($result['score_deliver'])? $result['score_deliver']: 4.5;
                        $this->result['content']['biz']['score_environment'] = !empty($result['score_environment'])? $result['score_environment']: 4.5;

                    // 获取当前商家营销活动
                    $this->switch_model('promotion_biz', 'promotion_id');
                    $conditions = array(
                        'biz_id' => $item['biz_id'],
                    );
                    $this->db->select('promotion_id, type, name, time_start, time_end');
                    $this->result['content']['biz_promotions'] = $this->basic_model->select($conditions, NULL);

                    // 获取平台级营销活动
                    $this->switch_model('promotion', 'promotion_id');
                    $this->db->where('time_delete IS NULL');
                    $this->result['content']['promotions'] = $this->basic_model->select(NULL, NULL);

                    // 获取商家及平台优惠券模板信息
                    $this->switch_model('coupon_template', 'template_id');
                    $this->db->where('time_delete IS NULL');
                    $this->db->group_start()
                        ->where('biz_id IS NULL') // 平台优惠券
                        ->or_where('biz_id', $item['biz_id']) // 商家优惠券
                        ->group_end();
                    $this->result['content']['coupon_templates'] = $this->basic_model->select(NULL, NULL);

                    // 获取商品评价
                    $this->switch_model('comment_item', 'comment_id');
                    $conditions = array(
                        'item_id' => $id,
                    );
                    $this->load->model('comment_item_model');
                    $this->result['content']['comments'] = $this->comment_item_model->select($conditions, NULL);

                endif;

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
            $this->form_validation->set_rules('biz_id', '所属商家', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('brand_id', '品牌', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('category_id', '系统分类', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('category_biz_id', '商家分类', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('code_biz', '商家商品编码', 'trim|max_length[20]');
            $this->form_validation->set_rules('barcode', '商品条形码', 'trim|is_natural_no_zero|exact_length[13]');
			$this->form_validation->set_rules('url_image_main', '主图', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('figure_image_urls', '形象图', 'trim|max_length[255]');
			//$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|required|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|is_natural_no_zero|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
            $this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
            $this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
            $this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]|callback_time_start[time_to_suspend]');
            $this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]|callback_time_end[time_to_publish]');
            $this->form_validation->set_message('time_to_publish', '预定上架时间需详细到分，且不可晚于预订下架时间');
            $this->form_validation->set_message('time_to_suspend', '预定下架时间需详细到分，且不可早于预订上架时间');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 获取预定上架时间
                $time_to_publish = $this->input->post('time_to_publish');

				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

					'figure_image_urls' => trim($this->input->post('figure_image_urls'), ','),
					//'figure_video_urls' => trim($this->input->post('figure_video_urls'), ','),

					'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
                    'stocks' => empty($this->input->post('stocks'))? 0: $this->input->post('stocks'),
					'unit_name' => empty($this->input->post('unit_name'))? '份': $this->input->post('unit_name'),
                    'weight_net' => empty($this->input->post('weight_net'))? '0.00': $this->input->post('weight_net'),
                    'weight_gross' => empty($this->input->post('weight_gross'))? '0.00': $this->input->post('weight_gross'),
                    'weight_volume' => empty($this->input->post('weight_volume'))? '0.00': $this->input->post('weight_volume'),
					'quantity_max' => empty($this->input->post('quantity_max'))? '50': $this->input->post('quantity_max'),
					'quantity_min' => empty($this->input->post('quantity_min'))? 1: $this->input->post('quantity_min'),
                    'coupon_allowed' => empty($this->input->post('coupon_allowed'))? 1: $this->input->post('coupon_allowed'), // 默认允许使用优惠券
					'discount_credit' => empty($this->input->post('discount_credit'))? '0.00': $this->input->post('discount_credit'),
					'commission_rate' => empty($this->input->post('commission_rate'))? '0.00': $this->input->post('commission_rate'),
					'time_to_publish' => empty($time_to_publish)? NULL: $time_to_publish,
					'time_to_suspend' => empty($this->input->post('time_to_suspend'))? NULL: $this->input->post('time_to_suspend'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'category_id', 'brand_id', 'biz_id', 'category_biz_id', 'code_biz', 'barcode', 'url_image_main', 'name', 'slogan', 'description', 'price', 'promotion_id', 'freight_template_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 生成上架时间
                $data_to_create['time_publish'] = (empty($time_to_publish) || $time_to_publish < time())? time(): NULL;

                // 若未传入slogan，自动生成slogan
                if ( empty($this->input->post('slogan')) )
                    $data_to_create['slogan'] = $this->slogan_generate($data_to_create);

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
			$this->form_validation->set_rules('category_biz_id', '商家分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('code_biz', '商家商品编码', 'trim|max_length[20]');
            $this->form_validation->set_rules('barcode', '商品条形码', 'trim|is_natural_no_zero|exact_length[13]');
			$this->form_validation->set_rules('url_image_main', '主图', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('figure_image_urls', '形象图', 'trim|max_length[255]');
			//$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|required|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|is_natural_no_zero|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
            $this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
            $this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
            $this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]|callback_time_start[time_to_suspend]');
            $this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]|callback_time_end[time_to_publish]');
            $this->form_validation->set_message('time_to_publish', '预定上架时间需详细到分，且不可晚于预订下架时间');
            $this->form_validation->set_message('time_to_suspend', '预定下架时间需详细到分，且不可早于预订上架时间');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			//$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 获取预定上架时间
                $time_to_publish = $this->input->post('time_to_publish');

				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

					'figure_image_urls' => trim($this->input->post('figure_image_urls'), ','),
					//'figure_video_urls' => trim($this->input->post('figure_video_urls'), ','),

					'tag_price' => empty($this->input->post('tag_price'))? '0.00': $this->input->post('tag_price'),
                    'stocks' => empty($this->input->post('stocks'))? 0: $this->input->post('stocks'),
					'unit_name' => empty($this->input->post('unit_name'))? '份': $this->input->post('unit_name'),
                    'weight_net' => empty($this->input->post('weight_net'))? '0.00': $this->input->post('weight_net'),
                    'weight_gross' => empty($this->input->post('weight_gross'))? '0.00': $this->input->post('weight_gross'),
                    'weight_volume' => empty($this->input->post('weight_volume'))? '0.00': $this->input->post('weight_volume'),
					'quantity_max' => empty($this->input->post('quantity_max'))? '50': $this->input->post('quantity_max'),
					'quantity_min' => empty($this->input->post('quantity_min'))? 1: $this->input->post('quantity_min'),
                    'coupon_allowed' => empty($this->input->post('coupon_allowed'))? 1: $this->input->post('coupon_allowed'), // 默认允许使用优惠券
                    'discount_credit' => empty($this->input->post('discount_credit'))? '0.00': $this->input->post('discount_credit'),
					'commission_rate' => empty($this->input->post('commission_rate'))? '0.00': $this->input->post('commission_rate'),
					'time_to_publish' => empty($time_to_publish)? NULL: $time_to_publish,
					'time_to_suspend' => empty($this->input->post('time_to_suspend'))? NULL: $this->input->post('time_to_suspend'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'category_biz_id', 'code_biz', 'barcode', 'url_image_main', 'name', 'slogan', 'description', 'price', 'promotion_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

                // 生成上架时间
                $data_to_edit['time_publish'] = (empty($time_to_publish) || $time_to_publish < time())? time(): NULL;

                // 若未传入slogan，自动生成slogan
                if ( empty($this->input->post('slogan')) )
                    $data_to_edit['slogan'] = $this->slogan_generate($data_to_edit);
				
				// 商家仅可操作自己的数据
				if ($this->app_type === 'biz') $this->db->where('biz_id', $this->input->post('biz_id'));

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

                // value 可以为空；必要字段会在字段验证中另行检查
				if ( $param !== 'value' && !isset( ${$param} ) ):
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
			//$this->form_validation->set_rules('figure_video_urls', '形象视频', 'trim|max_length[255]');
			$this->form_validation->set_rules('name', '商品名称', 'trim|max_length[40]');
			$this->form_validation->set_rules('slogan', '商品宣传语/卖点', 'trim|max_length[30]');
			$this->form_validation->set_rules('description', '商品描述', 'trim|max_length[20000]');
			$this->form_validation->set_rules('tag_price', '标签价/原价（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('price', '商城价/现价（元）', 'trim|greater_than_equal_to[1]|less_than_equal_to[99999.99]');
            $this->form_validation->set_rules('stocks', '库存量（单位）', 'trim|is_natural_no_zero|less_than_equal_to[65535]');
			$this->form_validation->set_rules('unit_name', '销售单位', 'trim|max_length[10]');
			$this->form_validation->set_rules('weight_net', '净重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_gross', '毛重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
			$this->form_validation->set_rules('weight_volume', '体积重（KG）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999.99]');
            $this->form_validation->set_rules('quantity_max', '每单最高限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
            $this->form_validation->set_rules('quantity_min', '每单最低限量（份）', 'trim|greater_than_equal_to[0]|less_than_equal_to[50]');
			$this->form_validation->set_rules('coupon_allowed', '是否可用优惠券', 'trim|in_list[0,1]');
			$this->form_validation->set_rules('discount_credit', '积分抵扣率', 'trim|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('commission_rate', '佣金比例/提成率', 'trim|less_than_equal_to[0.5]');
            $this->form_validation->set_rules('time_to_publish', '预定上架时间', 'trim|exact_length[10]|callback_time_start[time_to_suspend]');
            $this->form_validation->set_rules('time_to_suspend', '预定下架时间', 'trim|exact_length[10]|callback_time_end[time_to_publish]');
            $this->form_validation->set_message('time_to_publish', '预定上架时间需详细到分，且不可晚于预订下架时间');
            $this->form_validation->set_message('time_to_suspend', '预定下架时间需详细到分，且不可早于预订上架时间');
			$this->form_validation->set_rules('promotion_id', '店内活动', 'trim|is_natural_no_zero');
			//$this->form_validation->set_rules('freight_template_id', '运费模板', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;
				
				// 商家仅可操作自己的数据
				if ($this->app_type === 'biz')
				    $this->db->where('biz_id', $this->input->post('biz_id'));

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
					case 'publish': // 上架
						$data_to_edit['time_publish'] = time();
						$data_to_edit['time_suspend'] = NULL;
						$data_to_edit['time_to_publish'] = NULL; // 若手动上架，则取消上架计划
						break;
					case 'suspend': // 下架
						$data_to_edit['time_publish'] = NULL;
						$data_to_edit['time_suspend'] = time();
						$data_to_edit['time_to_suspend'] = NULL; // 若手动下架，则取消下架计划
						break;
					case 'delete': // 删除
                        $data_to_edit['time_publish'] = NULL;
                        $data_to_edit['time_suspend'] = time(); // 删除商品时设置下架时间为当前时间
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

				endforeach;

				// 添加全部操作成功后的提示
				if ($this->result['status'] = 200)
					$this->result['content']['message'] = '全部操作成功';

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
            // 若传入了平台级商品分类，则同时筛选属于该分类及该分类子分类的商品
            if ( !empty($condition['category_id']) ):
                // 获取所有子类ID
                $this->switch_model('item_category', 'category_id');
                $sub_categories = $this->basic_model->select(
                    array('parent_id' => $condition['category_id']),
                    NULL,
                    TRUE
                ); // 仅返回ID
                $this->reset_model();

                // 若存在子分类，则将子分类合并入查询条件
				if ( !empty($sub_categories) ):
                    $sub_categories[] = $condition['category_id'];
                    unset($condition['category_id']);

                    $this->db->or_where_in('biz.category_id', $sub_categories);
                endif;
            endif;

            // 若传入了商家级商品分类，则同时筛选属于该分类及该分类子分类的商品
            if ( !empty($condition['category_biz_id']) ):
                // 获取所有子类ID
                $this->switch_model('item_category_biz', 'category_id');
                $sub_categories = $this->basic_model->select(
                    array('parent_id' => $condition['category_biz_id']),
                    NULL,
                    TRUE
                ); // 仅返回ID
                $this->reset_model();

                // 若存在子分类，则将子分类合并入查询条件
                if ( !empty($sub_categories) ):
                    $sub_categories[] = $condition['category_biz_id'];
                    unset($condition['category_biz_id']);
					
                    $this->db->or_where_in('category_biz_id', $sub_categories);
                endif;
            endif;

            // 若传入了商品名，模糊查询
            if ( !empty($this->input->post('name')) ):
                $this->db->like('name', $this->input->post('name'));
            endif;

            return $condition;
        } // end advanced_sorter

        /**
         * 生成slogan字段值
         *
         * @param $data_to_edit
         * @return string
         */
        public function slogan_generate($data_to_edit)
        {
            // 初始化
            $slogan = '';

            // 若有预订上下架时间，反映预订上下架时间
            if ( ! empty($data_to_edit['time_to_public']))
                $slogan .= '，'. date('Y-m-d H:i:s', $data_to_edit['time_to_public']). '开售';
            if ( ! empty($data_to_edit['time_to_suspend']))
                $slogan .= '，'. date('Y-m-d H:i:s', $data_to_edit['time_to_suspend']). '后下架';

            // 若有限购信息，反映限购信息
            if ( ! empty($this->input->post('quantity_max')) && $this->input->post('quantity_max') != 50):
                $slogan .= '，限购'. $this->input->post('quantity_max'). $data_to_edit['unit_name'];
            elseif ( ! empty($this->input->post('quantity_min')) && $this->input->post('quantity_min') != 1):
                $slogan .= '，'. $this->input->post('quantity_min'). $data_to_edit['unit_name']. '起售';
            endif;

            return trim($slogan, '，'); // 清理冗余全角逗号
        } // end slogan_generate

	} // end class Item

/* End of file Item.php */
/* Location: ./application/controllers/Item.php */
