<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Lottery_record/LTC 抽奖记录类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Lottery_record extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'lottery_id', 'prize_id', 'user_id', 'date_create',
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
			'lottery_id', 'prize_id', 'user_id', 'date_create', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
         *
         * 应删除time_create等需在MY_Controller通过names_return_for_admin等类属性声明的字段名
		 */
		protected $names_to_return = array(
			'record_id', 'lottery_id', 'prize_id', 'user_id', 'date_create', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status', 
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
            'lottery_id',
		);

		/**
		 * 编辑多行特定字段时必要的字段名；若与MY_Controller声明的同名类属性相同，可删除此处
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation', 'password',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'lottery_record'; // 这里……
			$this->id_name = 'record_id'; // 这里……

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
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
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
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

            // 限制可返回的字段
            if ($this->app_type === 'client'):
                $condition['time_delete'] = 'NULL'; // 客户端仅可查看未删除项
            else:
                $this->names_to_return = array_merge($this->names_to_return, $this->names_return_for_admin);
            endif;
            $this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                $items = $this->basic_model->select($condition, $order_by);
            else:
                // 限制可返回的字段
                $this->db->select( implode(',', $this->names_to_return) );
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

            if ($this->app_type === 'client') $condition['time_delete'] = 'NULL';

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

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
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
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('lottery_id', '所属抽奖ID', 'trim|required');

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

                    'lottery_id' => $lottery_id,
                    'prize_id' => $this->input->post('prize_id'), // 管理端可手动指派奖项
                    'user_id' => $user_id,
                    'date_create' => $date_create,
                );

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
                // 根据ID检查活动是否仍在进行中，并获取活动详情
                $item = $this->get_lottery_pending($lottery_id);
                if ($item === FALSE):
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '活动目前不在进行中';

                else:
                    // 判断是否限制总参与数，是否已超出
                    $user_total_records = ($item['max_user_total'] === '0')? FALSE: NULL;

                    // 获取该用户总参与数，若不为FALSE则获取当日参与数等
                    if ($user_total_records === NULL)
                        $user_total_records = $this->user_total_records($user_id, $lottery_id);

                    // 判断总投票数是否超出了活动设置
                    if ($user_total_records !== FALSE && $user_total_records >= $item['max_user_total']):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '该活动每人只可参与'.$item['max_user_total'].'次，已参与'.$user_total_records.'次';

                    else:
                        // 将当前日期作为创建日期
                        $date_create = date('Y-m-d');

                        // 获取该用户当日投票数，若不为FALSE则获取当日对当前选项投票数
                        $user_daily_records = $this->user_daily_records($user_id, $lottery_id, $date_create);

                        // 检查该用户当天对同一商家票数、当天总票数是否达到上限；若无异常情况，则创建选票
                        if (
                            $user_daily_records === FALSE
                            || ($user_daily_records < $item['max_user_daily'])
                        ):
                            // 可领取的奖项
                            $prizes = $this->get_prizes_pending($lottery_id);

                            // 目标概率值
                            $target_index = rand(1,100);

                            // 当前累积概率值
                            $total_percent = 0;

                            // 当累积概率值大于目标概率值时，视为中奖，奖品信息为break时的$prize
                            for ($i=0;$i<count($prizes);$i++):
                                $prize = $prizes[$i];
                                $total_percent += ($prize['rate']*100);
                                if ($total_percent >= $target_index) break; // 中奖了
                            endfor;

                            // 需要创建的数据；逐一赋值需特别处理的字段
                            $data_to_create = array(
                                'creator_id' => $user_id,
                                'time_create' => time(),

                                'lottery_id' => $lottery_id,
                                'prize_id' => $prize['prize_id'], // TODO 动态生成此次抽奖所获奖项
                                'user_id' => $user_id,
                                'date_create' => $date_create,
                            );
                            $result = $this->basic_model->create($data_to_create, TRUE);
                            if ($result !== FALSE):
                                $this->result['status'] = 200;
                                $this->result['content']['id'] = $result;
                                $this->result['content']['prize_id'] = $prize['prize_id'];
                                $this->result['content']['message'] = '创建成功';

                                // 更新当前奖项余量
                                @$this->db->query('CALL update_lottery_prize_stocks('.$prize['prize_id'].')');

                            else:
                                $this->result['status'] = 424;
                                $this->result['content']['error']['message'] = '创建失败';

                            endif;

                        else:
                            $this->result['status'] = 424;

                            if ($user_daily_records >= $item['max_user_daily']):
                                $this->result['content']['error']['message'] = '该活动每天只可参与'.$item['max_user_daily'].'次，已参与'.$user_daily_records.'次';

                            endif;

                        endif;

                    endif;

                endif;

			endif;
		} // end create

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
         * 类特有筛选器
         *
         * @param array $condition 当前筛选条件数组
         * @return array 生成的筛选条件数组
         */
        protected function advanced_sorter($condition = array())
        {
            return $condition;
        } // end advanced_sorter

        // 仅获取进行中的有效抽奖活动信息
        protected function get_lottery_pending($id)
        {
            $this->switch_model('lottery', 'lottery_id');
            $current_timestamp = time();

            $condition = array(
                'lottery_id' => $id,
                'time_start <=' => $current_timestamp,
                'time_end >' => $current_timestamp,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->match($condition);
            $this->reset_model();

            return (empty($result))? FALSE: $result;
        } // end get_lottery_pending

        // 获取特定用户总抽奖数
        protected function user_total_records($user_id, $id)
        {
            $condition = array(
                'lottery_id' => $id,
                'user_id' => $user_id,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_total_ballots

        // 获取当天特定用户日抽奖数
        protected function user_daily_records($user_id, $id, $date_create)
        {
            $condition = array(
                'lottery_id' => $id,
                'user_id' => $user_id,
                'date_create' => $date_create,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->count($condition);

            return (empty($result))? FALSE: $result;
        } // end user_daily_ballots

        // 获取可领取的奖项
        protected function get_prizes_pending($id)
        {
            $this->switch_model('lottery_prize', 'prize_id');
            $condition = array(
                'lottery_id' => $id,
                'stocks >' => 0,
                'time_delete' => NULL,
            );
            $result = $this->basic_model->select($condition);
            $this->reset_model();

            return (empty($result))? FALSE: $result;
        } // end get_prizes_pending

	} // end class Lottery_record

/* End of file Lottery_record.php */
/* Location: ./application/controllers/Lottery_record.php */
