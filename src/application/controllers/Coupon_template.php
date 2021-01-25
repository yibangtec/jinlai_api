<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Coupon_template/CPT 优惠券模板类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Coupon_template extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'biz_id', 'category_id', 'category_biz_id', 'item_id', 'amount', 'rate', 'max_amount', 'max_amount_user', 'min_subtotal', 'period', 'time_start', 'time_end',
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
			'category_id', 'category_biz_id', 'item_id', 'name', 'description', 'amount', 'rate', 'max_amount', 'max_amount_user', 'min_subtotal', 'period', 'time_start', 'time_end',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
		    'user_id', 'id',
            'name',
        );

        // 优惠券默认有效时长
        protected $default_period = 2592000; // 30天

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'coupon_template'; // 这里……
			$this->id_name = 'template_id'; // 这里……

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
			$order_by = NULL;

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 限制可返回的字段
                if ($this->app_type === 'client'):
                    $condition['time_delete'] = 'NULL';
                    $condition['time_end >'] = time(); // 仅获取有效期内的数据
                    $condition['scope'] = 'public'; // 仅获取公开数据
                endif;
                $this->load->model('coupon_template_model');
                $items = $this->coupon_template_model->select($condition, $order_by);
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
			
			// 获取特定项；默认可获取已删除项
            $this->load->model('coupon_template_model');
			$item = $this->coupon_template_model->select_by_id($id);
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
			$type_allowed = array('admin', 'biz'); // 客户端类型
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

			// 可选的必要参数（其中之一不可为空）
            $amount = empty($this->input->post('amount'))? NULL: $this->input->post('amount');
            $rate = empty($this->input->post('rate'))? NULL: $this->input->post('rate');
			if (empty($amount.$rate)):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '可选的必要请求参数未传入';
                exit();
            endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('name', '名称', 'trim|required|max_length[20]');
			$this->form_validation->set_rules('description', '说明', 'trim|max_length[30]');
			$this->form_validation->set_rules('amount', '面值（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999]');
            $this->form_validation->set_rules('rate', '折扣率', 'trim|greater_than_equal_to[0]|less_than_equal_to[0.5]');
			$this->form_validation->set_rules('min_subtotal', '起用金额（元）', 'trim|greater_than_equal_to[1]|less_than_equal_to[9999]');
            $this->form_validation->set_rules('max_amount', '总限量', 'trim|greater_than_equal_to[0]|less_than_equal_to[999999]');
            $this->form_validation->set_rules('max_amount_user', '单个用户限量', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
            $this->form_validation->set_rules('period', '有效时长', 'trim|greater_than_equal_to[3600]|less_than_equal_to[31622400]');
			$this->form_validation->set_rules('time_start', '有效期开始时间', 'trim|exact_length[10]|integer|callback_time_start');
			$this->form_validation->set_rules('time_end', '有效期结束时间', 'trim|exact_length[10]|integer|callback_time_end');
            $this->form_validation->set_message('time_start', '领取开始时间需详细到分，且早于结束时间');
            $this->form_validation->set_message('time_end', '领取结束时间需详细到分，且晚于开始时间');
            $this->form_validation->set_rules('category_id', '限用平台商品分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('category_biz_id', '限用店内商品分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('item_id', '限用商品', 'trim|is_natural_no_zero');
			// 针对特定条件的验证规则
            if ($this->app_type === 'biz')
                $this->form_validation->set_rules('biz_id', '所属商家', 'trim|required|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
                $period = empty($this->input->post('period'))? $this->default_period: $this->input->post('period');

			    // 生成起用金额
                $min_subtotal = $this->generate_min_subtotal($amount);

				$data_to_create = array(
					'creator_id' => $user_id,

                    'amount' => empty($amount)? 0: $amount,
                    'rate' => empty($rate)? 0: $rate,

                    'min_subtotal' => $min_subtotal,
                    'max_amount' => empty($this->input->post('max_amount'))? 0: $this->input->post('max_amount'),
                    'max_amount_user' => empty($this->input->post('max_amount_user'))? 0: $this->input->post('max_amount_user'),

                    'period' => $period,
                    'time_start' => empty($this->input->post('time_start'))? time(): $this->input->post('time_start'),
					'time_end' => empty($this->input->post('time_end'))? time() + $period: $this->input->post('time_end'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'biz_id', 'category_id', 'category_biz_id', 'item_id', 'name', 'description',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);
				// 根据客户端类型等条件筛选可操作的字段名
                if ($this->app_type === 'biz'):
                    unset($data_to_create['category_id']);
                endif;

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
			$type_allowed = array('admin', 'biz'); // 客户端类型
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

            // 可选的必要参数（其中之一不可为空）
            $amount = empty($this->input->post('amount'))? NULL: $this->input->post('amount');
            $rate = empty($this->input->post('rate'))? NULL: $this->input->post('rate');
            if (empty($amount.$rate)):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '可选的必要请求参数未传入';
                exit();
            endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('name', '名称', 'trim|required|max_length[20]');
			$this->form_validation->set_rules('description', '说明', 'trim|max_length[30]');
            $this->form_validation->set_rules('amount', '面值（元）', 'trim|greater_than_equal_to[0]|less_than_equal_to[999]');
            $this->form_validation->set_rules('rate', '折扣率', 'trim|greater_than_equal_to[0]|less_than_equal_to[0.5]');
            $this->form_validation->set_rules('min_subtotal', '起用金额（元）', 'trim|greater_than_equal_to[1]|less_than_equal_to[9999]');
            $this->form_validation->set_rules('max_amount', '总限量', 'trim|greater_than_equal_to[0]|less_than_equal_to[999999]');
            $this->form_validation->set_rules('max_amount_user', '单个用户限量', 'trim|greater_than_equal_to[0]|less_than_equal_to[99]');
            $this->form_validation->set_rules('period', '有效时长', 'trim|greater_than_equal_to[3600]|less_than_equal_to[31622400]');
			$this->form_validation->set_rules('time_start', '有效期开始时间', 'trim|exact_length[10]|integer|callback_time_start');
			$this->form_validation->set_rules('time_end', '有效期结束时间', 'trim|exact_length[10]|integer|callback_time_end');
            $this->form_validation->set_message('time_start', '领取开始时间需详细到分，且早于结束时间');
            $this->form_validation->set_message('time_end', '领取结束时间需详细到分，且晚于开始时间');
            $this->form_validation->set_rules('category_id', '限用平台商品分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('category_biz_id', '限用店内商品分类', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('item_id', '限用商品', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
                $period = empty($this->input->post('period'))? $this->default_period: $this->input->post('period');

                // 生成起用金额
                $min_subtotal = $this->generate_min_subtotal($amount);

			    $data_to_edit = array(
					'operator_id' => $user_id,

                    'amount' => empty($amount)? 0: $amount,
                    'rate' => empty($rate)? 0: $rate,

                    'min_subtotal' => $min_subtotal,
                    'max_amount' => empty($this->input->post('max_amount'))? 0: $this->input->post('max_amount'),
                    'max_amount_user' => empty($this->input->post('max_amount_user'))? 0: $this->input->post('max_amount_user'),

                    'period' => $period,
                    'time_start' => empty($this->input->post('time_start'))? time(): $this->input->post('time_start'),
                    'time_end' => empty($this->input->post('time_end'))? time() + $period: $this->input->post('time_end'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'category_id', 'category_biz_id', 'item_id', 'name', 'description',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 根据客户端类型等条件筛选可操作的字段名
				if ($this->app_type === 'biz'):
					unset($data_to_edit['category_id']);
				endif;

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['id'] = $result;
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
		 * 以下为工具类方法
		 */

        /**
         * 生成起用金额
         *
         * @param int $amount 定额型优惠券抵扣额
         * @return number
         */
        protected function generate_min_subtotal($amount = 0)
        {
            // 传入入的起用金额
            $inputed = empty($this->input->post('min_subtotal'))? 1: $this->input->post('min_subtotal');

            // 系统允许的最低起用金额
            $minimum = empty($amount)? 1: ($amount+1); // 若为折扣型优惠券，则起用金额为1元，否则为面值+1元

            // 以较高者为准
            return ($minimum > $inputed)? $minimum: $inputed;
        } // end generate_min_subtotal


    } // end class Coupon_template

/* End of file Coupon_template.php */
/* Location: ./application/controllers/Coupon_template.php */
