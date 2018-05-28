<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Vote_ballot/VTB 投票选票类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Vote_ballot extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'vote_id', 'option_id', 'user_id', 'date_create',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);
		
		/**
	     * @var array 可根据最大值筛选的字段名
	     */
	    protected $max_needed = array(
	        'time_create',
	    );

	    /**
	     * @var array 可根据最小值筛选的字段名
	     */
	    protected $min_needed = array(
	        'time_create',
	    );
		
		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
			'vote_id', 'option_id', 'user_id', 'date_create', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'ballot_id', 'vote_id', 'option_id', 'user_id', 'date_create',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
            'vote_id', 'option_id',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'vote_ballot'; // 这里……
			$this->id_name = 'ballot_id'; // 这里……

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
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

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
			$type_allowed = array('admin', 'client'); // 客户端类型
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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('vote_id', '所属投票ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('option_id', '候选项ID', 'trim|required|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			elseif ($this->app_type === 'admin'):
                // 将当前日期作为创建日期
                $date_create = date('Y-m-d');

                // 需要创建的数据；逐一赋值需特别处理的字段
                $data_to_create = array(
                    'creator_id' => $user_id,
                    'time_create' => time(),

                    'vote_id' => $vote_id,
                    'option_id' => $option_id,
                    'user_id' => $user_id,
                    'date_create' => $date_create,
                );

                $this->reset_model(); // 需要操作的数据库参数与之前不同，需重置数据库参数
                $result = $this->basic_model->create($data_to_create, TRUE);
                if ($result !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['id'] = $result;
                    $this->result['content']['message'] = '创建成功';

                else:
                    $this->result['status'] = 424;
                    $this->result['content']['error']['message'] = '创建失败';

                endif;

            else:
                // 根据投票ID检查活动是否仍在进行中，并获取投票活动详情
                $vote = $this->get_vote_pending($vote_id);
                if ($vote === FALSE):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '投票活动目前不在进行中';

                else:
                    // 切换到选票数据库
                    $this->switch_model('vote_ballot', 'ballot_id');

                    // 判断是否限制总投票数，是否已超出票数
                    $user_total_ballots = ($vote['max_user_total'] === '0')? FALSE: NULL;

                    // 获取该用户总投票数，若不为FALSE则获取当日投票数、当前选项当日投票数等
                    if ($user_total_ballots === NULL)
                        $user_total_ballots = $this->user_total_ballots($user_id, $vote_id);

                    // 判断总投票数是否超出了活动设置
                    if ($user_total_ballots !== FALSE && $user_total_ballots >= $vote['max_user_total']):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '该活动每人只可投'.$vote['max_user_total'].'票，已投'.$user_total_ballots.'票';

                    else:
                        // 将当前日期作为创建日期
                        $date_create = date('Y-m-d');

                        // 获取该用户当日投票数，若不为FALSE则获取当日对当前选项投票数
                        $user_daily_ballots = $this->user_daily_ballots($user_id, $vote_id, $date_create);
                        if ($user_daily_ballots !== FALSE)
                            $user_option_ballots = $this->user_option_ballots($user_id, $option_id, $date_create);

                        // 检查该用户当天对同一商家票数、当天总票数是否达到上限；若无异常情况，则创建选票
                        if (
                            $user_daily_ballots === FALSE
                            || ($user_daily_ballots < $vote['max_user_daily'] && $user_option_ballots < $vote['max_user_daily_each'])
                        ):
                            // 需要创建的数据；逐一赋值需特别处理的字段
                            $data_to_create = array(
                                'creator_id' => $user_id,
                                'time_create' => time(),

                                'vote_id' => $vote_id,
                                'option_id' => $option_id,
                                'user_id' => $user_id,
                                'date_create' => $date_create,
                            );

                            $this->reset_model(); // 需要操作的数据库参数与之前不同，需重置数据库参数
                            $result = $this->basic_model->create($data_to_create, TRUE);
                            if ($result !== FALSE):
                                $this->result['status'] = 200;
                                $this->result['content']['id'] = $result;
                                $this->result['content']['message'] = '创建成功';

                                // 更新当前选项总票数
                                @$this->db->query('CALL update_vote_option_ballot_overall('.$option_id.')');

                            else:
                                $this->result['status'] = 424;
                                $this->result['content']['error']['message'] = '创建失败';

                            endif;

                        else:
                            $this->result['status'] = 424;

                            if ($user_option_ballots >= $vote['max_user_daily_each']):
                                $this->result['content']['error']['message'] = '每天只可投同一选项'.$vote['max_user_daily_each'].'票，已投'.$user_option_ballots.'票';

                            elseif ($user_daily_ballots >= $vote['max_user_daily']):
                                $this->result['content']['error']['message'] = '该活动每天只可投'.$vote['max_user_daily'].'票，已投'.$user_daily_ballots.'票';

                            endif;

                        endif;

                    endif;

                endif;

			endif; // 若表单提交成功
		} // end create

        /**
         * 7 批量为多行数据创建多项数据
         */
        public function create_multiple()
        {
            // 操作可能需要检查客户端及设备信息
            $type_allowed = array('admin', 'biz',); // 客户端类型
            $this->client_check($type_allowed);

            // 管理类客户端操作可能需要检查操作权限
            //$role_allowed = array('管理员', '经理'); // 角色要求
            //$min_level = 10; // 级别要求
            //$this->permission_check($role_allowed, $min_level);

            // 检查必要参数是否已传入
            $required_params = array(
                'user_id', 'ids', 'password', 'vote_id', 'amount_min', 'amount_max',
            );
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
            $this->form_validation->set_rules('ids', '待操作数据', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
            $this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');
            $this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

            $this->form_validation->set_rules('vote_id', '所属投票ID', 'trim|required|is_natural_no_zero');

            $this->form_validation->set_rules('amount_min', '投票数量下限', 'trim|required|is_natural_no_zero|greater_than[9]|less_than[1001]|callback_amount_min');
            $this->form_validation->set_rules('amount_max', '投票数量上限', 'trim|required|is_natural_no_zero|greater_than[9]|less_than[1001]|callback_amount_max');
            $this->form_validation->set_message('amount_min', '投票数量下限不可大于上限');
            $this->form_validation->set_message('amount_max', '投票数量下限不可小于上限');

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
                // 需要创建的数据；逐一赋值需特别处理的字段
                $data_to_create = array(
                    'creator_id' => $user_id,

                    'vote_id' => $vote_id,
                    'user_id' => $user_id,
                    'date_create' => date('Y-m-d'),
                );

                // 随机数生成范围
                $random_range = $amount_max - $amount_min;

                // 依次操作数据并输出操作结果
                // 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
                $ids = explode(',', $ids);

                // 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
                $this->result['status'] = 200;
                foreach ($ids as $id):
                    $data_to_create['option_id'] = $id;
                    $data_to_create['time_create'] = time();

                    // 需要生成选票的数量
                    $needed_count = $amount_min + round((rand(0,100) * $random_range)/100);

                    // 生成相应数量的选票
                    for ($i=0; $i<$needed_count; $i++):
                        $result = $this->basic_model->create($data_to_create, TRUE);
                        if ($result === FALSE):
                            $this->result['status'] = 434;
                            $this->result['content']['row_failed'][] = $id;
                        endif;
                    endfor;

                    $this->result['content']['operated'][] = array(
                        'option_id' => $id,
                        'count' => $needed_count,
                    );

                endforeach;

                // 清除数据库缓存
                $this->db->cache_delete('vote_option', 'index');
                $this->db->cache_delete('vote_option', 'detail');

                // 添加全部操作成功后的提示
                if ($this->result['status'] = 200)
                    $this->result['content']['message'] = '全部操作成功';

            endif;
        } // end create_multiple

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
				switch ( $operation ):
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
		 * 以下为工具类方法
		 */

		// 仅获取进行中的有效投票活动信息
		protected function get_vote_pending($vote_id)
        {
            $this->switch_model('vote', 'vote_id');
            $current_timestamp = time();

            $condition = array(
                'vote_id' => $vote_id,
                'time_start <=' => $current_timestamp,
                'time_end >' => $current_timestamp,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->match($condition);

            return (empty($result))? FALSE: $result;
        } // end get_vote_pending

        // 获取当天特定用户日选票数
        protected function user_total_ballots($user_id, $vote_id)
        {
            $condition = array(
                'vote_id' => $vote_id,
                'user_id' => $user_id,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_total_ballots

        // 获取当天特定用户日选票数
        protected function user_daily_ballots($user_id, $vote_id, $date_create)
        {
            $condition = array(
                'vote_id' => $vote_id,
                'user_id' => $user_id,
                'date_create' => $date_create,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_daily_ballots

        // 获取当天特定用户特定选项日选票数
        protected function user_option_ballots($user_id, $option_id, $date_create)
        {
            $condition = array(
                'option_id' => $option_id,
                'user_id' => $user_id,
                'date_create' => $date_create,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_option_ballots

        // 检查 amount_min
        public function amount_min($value)
        {
            if ( empty($value) ):
                return true;

            else:
                // 不可大于 amount_min
                if ($this->input->post('amount_max') < $value):
                    return false;
                else:
                    return true;
                endif;

            endif;
        } // end amount_min

        // 检查 amount_max
        public function amount_max($value)
        {
            if ( empty($value) ):
                return true;

            else:
                // 不可小于 amount_min
                if ($this->input->post('amount_min') > $value):
                    return false;
                else:
                    return true;
                endif;

            endif;
        } // end amount_max

	} // end class Vote_ballot

/* End of file Vote_ballot.php */
/* Location: ./application/controllers/Vote_ballot.php */
