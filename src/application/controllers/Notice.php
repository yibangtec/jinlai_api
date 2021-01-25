<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Notice/NTC 系统通知类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Notice extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'receiver_type', 'user_id', 'biz_id', 'article_id', 'title', 'excerpt', 'url_image', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
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
			'time_create',
		);

		/**
		 * 可作为查询结果返回的字段名
         *
         * 应删除time_create等需在MY_Controller通过names_return_for_admin等类属性声明的字段名
		 */
		protected $names_to_return = array(
			'notice_id', 'receiver_type', 'user_id', 'biz_id', 'article_id', 'title', 'excerpt', 'url_image', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'creator_id',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'notice'; // 这里……
			$this->id_name = 'notice_id'; // 这里……

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
			if ($this->app_type !== 'admin' && !isset($condition['time_delete'])):
                $condition['receiver_type'] = $this->app_type;
			    $condition['time_delete'] = 'NULL'; // 若非管理端请求，则默认不获取已删除项

                unset($condition['user_id']);
            endif;

			// 排序条件
            $order_by['time_create'] = 'DESC';
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

			// 限制需返回的字段名
            $this->db->select( implode(',', $this->names_to_return) );

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                // 限制可返回的字段
                if ($this->app_type === 'client'):
                    // 获取全局通知或指定发给当前用户
                    unset($condition['user_id']);
                    $this->db->group_start()->where("user_id IS NULL")->or_where('user_id', $this->input->post('user_id'))->group_end();
               elseif ($this->app_type === 'biz'):
                    // 获取全局通知或指定发给当前商家
                    unset($condition['biz_id']);
                    $this->db->group_start()->where("biz_id IS NULL")->or_where('biz_id', $this->input->post('biz_id'))->group_end();
                else:
                    $this->names_to_return = array_merge($this->names_to_return, $this->names_return_for_admin);
                endif;
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

            // 检查必要的可选参数是否已传入
			$article_id = empty($this->input->post('article_id'))? NULL: $this->input->post('article_id');
            $title = empty($this->input->post('title'))? NULL: $this->input->post('title');
            if (empty($article_id) && empty($title)):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '相关文章ID与标题不可同时留空';
                exit();
            endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference、
			$this->form_validation->set_rules('receiver_type', '目标客户端类型', 'trim|in_list[admin,biz,client]');
			$this->form_validation->set_rules('user_id', '目标用户ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('biz_id', '目标商家ID', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('article_id', '相关文章ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('title', '标题', 'trim|max_length[30]');
			$this->form_validation->set_rules('excerpt', '摘要', 'trim|max_length[100]');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
                // 若指定了相关文章，则获取文章相应数据
                if ($article_id !== NULL):
                    $item = $this->get_item('article', 'article_id', $article_id, FALSE);
                    if ( empty($item) ):
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '待发送项不存在或已删除';
                        exit();
                    endif;
                endif;

				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $creator_id,
                    'time_create' => time(),

                    'receiver_type' => empty($this->input->post('receiver_type'))? 'client': $this->input->post('receiver_type'),
                    'article_id' => $article_id,

                    // 赋值特定字段值，若已赋值，则覆盖
                    'title' => (empty($title) && !empty($item))? $item['title']: $title,
                    'excerpt' => (empty($this->input->post('excerpt')) && !empty($item))? $item['excerpt']: $this->input->post('excerpt'),
                    'url_image' => (empty($this->input->post('url_image')) && !empty($item))? $item['url_images']: $this->input->post('url_image'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'user_id', 'biz_id',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				$result = $this->basic_model->create(array_filter($data_to_create), TRUE);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $result;
					$this->result['content']['message'] = '创建成功';

					$data_to_create[$this->id_name] = $result;

                    // 待发送消息
                    $message = array(
                        'controller' => $this->table_name,
                        'function' => 'index',
                        'params' => $data_to_create
                    );
                    // 推送方式
                    $push_type = empty($this->input->post('push_type'))? 'notification': $this->input->post('push_type');
                    $this->push_send($message, $push_type);

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建失败';

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
            $this->common_edit_bulk(TRUE, 'delete,restore,revoke');

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
                    case 'revoke':
                        $data_to_edit['time_revoke'] = date('Y-m-d H:i:s');
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

	} // end class Notice

/* End of file Notice.php */
/* Location: ./application/controllers/Notice.php */
