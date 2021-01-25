<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Coupon/CPN 优惠券类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Coupon extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'user_id', 'combo_id', 'template_id', 'category_id', 'biz_id', 'category_biz_id', 'item_id', 'amount', 'rate', 'min_subtotal', 'time_start', 'time_end', 'time_expire', 'order_id', 'time_used', 'time_create', 'time_delete', 'status',
		);

		public function __construct()
		{
			parent::__construct();

			// 初始化待用类属性
            $this->result['status'] = 200;
			$this->result['content']['ids'] = $this->result['content']['message'] = '';

			// 设置主要数据库信息
			$this->table_name = 'coupon'; // 这里……
			$this->id_name = 'coupon_id'; // 这里……

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

			// 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                $this->load->model('coupon_model');
                $items = $this->coupon_model->select($condition, $order_by);
                
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
			if ( !isset($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;
			
			// 获取特定项；默认可获取已删除项
            $this->load->model('coupon_model');
			$item = $this->coupon_model->select_by_id($id);
			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

		/**
		 * 3 创建（领取优惠券）
		 */
		public function create()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型；平台及商家可手动向指定用户发放优惠券
			$this->client_check($type_allowed);

			// 检查必要参数是否已传入
			$user_id = $this->input->post('user_id');
			// 必须传入combo_id或template_id
			$combo_id = $this->input->post('combo_id');
			$template_id = $this->input->post('template_id');
			if ( !empty($user_id) && !empty($template_id) ):
				// 若传入了$template_id，尝试获取$count
				$count = $this->input->post('count');
			elseif ( empty($user_id.$combo_id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
				exit();
			endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('combo_id', '优惠券包ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('template_id', '优惠券模板ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('count', '数量', 'trim|is_natural_no_zero|less_than[11]');

            $this->result['content']['error']['message'] = '';

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			// 领取优惠券包
			elseif ( !empty($combo_id) ):
				// 获取优惠券包
				$combo = $this->get_combo($combo_id, $user_id);
				if ($combo === FALSE):
					$this->result['status'] = 414;
					//$this->result['content']['error']['message'] = '该优惠券包不可领取';

				else:
					$template_ids = $combo['template_ids'];

					// 解析所含优惠券模板信息并生成相应数量的优惠券
					$template_ids = $this->explode_csv($template_ids);
					foreach ($template_ids as $template_id):
                        // 分解出优惠券模板ID及张数
                        if (strpos($template_id, '|') === FALSE):
                            $count = 1;
                        else:
                            list($template_id, $count) = preg_split('/\|/', $template_id);
                        endif;

                        // 生成相应张数的优惠券
                        for ($i=0; $i<$count; $i++):
						    $this->generate_coupon($user_id, $template_id, $combo_id);
                        endfor;
					endforeach;

				endif;

				// 清除冗余的分隔符
				$this->result['content']['ids'] = trim($this->result['content']['ids'], ',');

			// 领取优惠券模板（单种优惠券）
			else:
                // 生成相应张数的优惠券
                $count = empty($count)? 1: $count;
                for ($i=0; $i<$count; $i++):
                    $this->generate_coupon($user_id, $template_id);
                endfor;

			endif;
			
			// 清理待返回内容中的空元素
			$this->result['content'] = array_filter($this->result['content']);
		} // end create

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
         * 以下为工具方法
         */

        /**
         * 检查优惠券模板有效性
         *
         * @param int/string $template_id 优惠券模板ID
         * @param int/string $user_id 用户ID
         * @param int/string $combo_id 所属优惠券包ID
         * @return array/boolean 可用的优惠券模板信息或FALSE
         */
        protected function get_template($template_id, $user_id, $combo_id = NULL)
        {
            // 获取优惠券模板
            $this->switch_model('coupon_template', 'template_id');

            // 有效期结束时间须晚于当前时间
            $this->db->where('time_delete', NULL)
                ->group_start()
                ->where('time_end', NULL)
                ->or_where('time_end >', time())
                ->group_end();
            $template = $this->basic_model->select_by_id($template_id);

            // 还原数据库参数
            $this->reset_model();

            // 若无符合条件的优惠券，返回false
            if ( empty($template) ):
                $this->result['content']['error']['message'] = '该优惠券已不可领取';
                return FALSE;

            // 若单独被领取，检查总限量及单个用户限量
            elseif ($combo_id === NULL):
                // 重置数据库查询构造器
                $this->db->reset_query();

                // 初始化有效性
                $is_valid = TRUE;

                // 若当前模板有单个用户限量，进行检查
                if ($template['max_amount_user'] != 0):
                    // 获取当前模板已生成的模板优惠券数量
                    $condition = array(
                        'user_id' => $user_id,
                        'template_id' => $template_id,
                        'time_delete' => 'NULL',
                    );
                    $count = $this->basic_model->count($condition);
                    if ($count >= $template['max_amount_user']):
                        $this->result['content']['error']['message'] = '已经领过了，不要太贪心哦～';
                        $this->result['status'] = 414;
                        exit;
                    endif;
                endif;

                // 若当前模板有总限量，进行检查
                if ($template['max_amount'] != 0):
                    // 获取当前模板已生成的模板优惠券数量
                    $condition = array(
                        'template_id' => $template_id,
                        'time_delete' => 'NULL',
                    );
                    $count = $this->basic_model->count($condition);
                    if ($count >= $template['max_amount']):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '优惠券已经被抢光了';
                        $is_valid = FALSE;
                    endif;
                endif;

                // 若无异常，返回优惠券模板信息
                if ($is_valid === TRUE):
                    return $template;
                else:
                    return FALSE;
                endif;

            // 若作为礼包项被领取，忽视限量
            else:
                return $template;

            endif;
        } // end get_template

        /**
         * 检查优惠券包有效性
         *
         * @params int/string $combo_id 优惠券包ID
         * @params int/string $user_id 用户ID
         * @return array/boolean 可用的优惠券包信息或FALSE
         */
        protected function get_combo($combo_id, $user_id)
        {
            // 获取优惠券包
            $this->switch_model('coupon_combo', 'combo_id');

            // 开放领取的开始时间需早于当前时间，结束时间需晚于当前时间
            $this->db->where('time_delete', NULL)
                ->group_start()
                ->where('time_start', NULL)
                ->or_where('time_start <', time())
                ->group_end()

                ->group_start()
                ->where('time_end', NULL)
                ->or_where('time_end >', time())
                ->group_end();
            $combo = $this->basic_model->select_by_id($combo_id);
            //var_dump($combo);

            // 还原数据库参数
            $this->reset_model();

            // 若无符合条件的优惠券包，返回false
            if ( empty($combo) ):
                $this->result['content']['error']['message'] = '该优惠券包当前不可领取';
                return FALSE;

            // 若单独被领取，检查总限量及单个用户限量
            else:
                // 重置数据库查询构造器
                $this->db->reset_query();

                // 初始化有效性
                $is_valid = TRUE;

                // 每个优惠券包对单一用户限领一次
                $condition = array(
                    'user_id' => $user_id,
                    'combo_id' => $combo_id,
                    'time_delete' => 'NULL',
                );
                $count = $this->basic_model->count($condition);
                //var_dump($count);
                if ($count > 0):
                    $this->result['content']['error']['message'] = '这个优惠券包已经领过了';
                    $is_valid = FALSE;
                endif;

                // 若当前模板有总限量，进行检查
                if ($combo['max_amount'] != 0):
                    // 获取当前模板已生成的模板优惠券数量
                    $condition = array(
                        'combo_id' => $combo_id,
                        'time_delete' => 'NULL',
                    );
                    $count = $this->basic_model->count($condition);
                    if ($count >= $combo['max_amount']):
                        $this->result['content']['error']['message'] = '这个优惠券包已经被抢光了';
                        $is_valid = FALSE;
                    endif;
                endif;

                // 若无异常，返回优惠券模板信息
                if ($is_valid === TRUE):
                    return $combo;
                else:
                    //var_dump($this->result['content']['error']['message']);
                    return FALSE;
                endif;

            endif;
        } // end get_combo

        /**
         * 生成优惠券
         *
         * @params int/string $user_id 用户ID
         * @params array $template 优惠券模板信息
         * @params int/string $combo_id 所属优惠券包ID
         */
        protected function generate_coupon($user_id, $template_id, $combo_id = NULL)
        {
            // 获取优惠券模板信息
            $template = $this->get_template($template_id, $user_id, $combo_id);

            if ($template === FALSE):
                if ($this->result['status'] === 200):
                    $this->result['content']['message'] = '部分优惠券不可领取';

                else:
                    $this->result['status'] = 414;
                    if ($combo_id === NULL):
                        if (empty($this->result['content']['error']['message'])):
                            $this->result['content']['error']['message'] = '领取失败';
                        endif;
                    else:
                        $this->result['content']['error']['message'] .= '<li>部分优惠券领取失败</li>';
                    endif;

                endif;

            else:
                // 获取当前时间戳
                $time_now = time();

                // 计算有效期开始时间；以开始时间和当前时间中较晚者作为有效期实际开始时间
                $time_start = ($template['time_start'] < $time_now)? $time_now: $template['time_start'];

                // 计算有效期结束时间；以结束时间和最晚结束时间中较早者作为有效期实际结束时间
                $time_end_latest = $time_start + $template['period']; // 根据有效时长计算出的最晚结束时间
                $time_end = ($time_end_latest < $template['time_end'])? $time_end_latest: $template['time_end'];

                // 需要创建的数据；逐一赋值需特别处理的字段
                $data_to_create = array(
                    'user_id' => $user_id,
                    'combo_id' => $combo_id,
                    'template_id' => $template_id,
                    'biz_id' => $template['biz_id'],
                    'category_id' => $template['category_id'],
                    'category_biz_id' => $template['category_biz_id'],
                    'item_id' => $template['item_id'],
                    'name' => $template['name'],
                    'amount' => $template['amount'],
                    'rate' => $template['rate'],
                    'min_subtotal' => $template['min_subtotal'],
                    'time_start' => $time_start,
                    'time_end' => $time_end,
                );

                // 创建优惠券
                $result = $this->basic_model->create(array_filter($data_to_create), TRUE);
                if ($result !== FALSE):
                    $this->result['status'] = 200;

                    if ($combo_id === NULL):
                        $this->result['content']['id'] = $result;
                        $this->result['content']['message'] = '优惠券领取成功';
                    else:
                        $this->result['content']['ids'] .= ','.$result;
                        $this->result['content']['message'] .= '<li>“'.$template['name'].'”领取成功</li>';
                    endif;

                else:
                    if ($this->result['status'] === 200):
                        $this->result['content']['message'] = '部分优惠券领取失败';

                    else:
                        $this->result['status'] = 424;
                        if ($combo_id === NULL):
                            $this->result['content']['error']['message'] = '领取失败';
                        else:
                            $this->result['content']['error']['message'] .= '<li>“'.$template['name'].'”领取失败</li>';
                        endif;

                    endif;

                endif;

            endif;
        } // generate_coupon

	} // end class Coupon

/* End of file Coupon.php */
/* Location: ./application/controllers/Coupon.php */
