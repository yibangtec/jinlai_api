<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Message/MSG 聊天消息类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Message extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'user_id', 'biz_id', 'stuff_id', 'sender_type', 'receiver_type', 'type', 'ids', 'time_create', 'time_delete', 'time_revoke', 'creator_id',
		);
		
		/**
	     * @var array 可根据最大值筛选的字段名
	     */
	    protected $max_needed = array(
	        'time_create', 'longitude', 'latitude',
	    );

	    /**
	     * @var array 可根据最小值筛选的字段名
	     */
	    protected $min_needed = array(
	        'time_create', 'longitude', 'latitude',
	    );
		
		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
			'longitude', 'latitude', 'time_create',
		);

		/**
		 * 可作为查询结果返回的字段名
         *
         * 应删除time_create等需在MY_Controller通过names_return_for_admin等类属性声明的字段名
		 */
		protected $names_to_return = array(
			'message_id', 'user_id', 'biz_id', 'stuff_id', 'sender_type', 'receiver_type', 'type', 'ids', 'title', 'excerpt', 'url_image', 'content', 'longitude', 'latitude', 'time_create', 'time_delete', 'time_revoke', 'creator_id', 
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'creator_id', 'sender_type', 'receiver_type', 'type',
		);

		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation', 'password',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'message'; // 这里……
			$this->id_name = 'message_id'; // 这里……

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

			// 排序条件
			$order_by['time_create'] = 'DESC';
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
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
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

			// 对部分类型消息，content为必要参数
            $types_require_content = array(
                'audio','image','location','text','video'
            );
            // 对部分类型消息，ids为必要参数
            $types_require_ids = array(
                'address','article','article_biz','branch','coupon_combo','coupon_template','item','order','promotion','promotion_biz'
            );
            if ( in_array($type, $types_require_content) ):
                $content = $this->input->post('content');
                if (empty($content)):
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '需传入content值';
                    exit();
                endif;

            elseif ( in_array($type, $types_require_ids) ):
                $ids = $this->input->post('ids');
                if (empty($ids)):
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '需传入ids值';
                    exit();
                endif;
            endif;

			// 对位置类消息，经纬度为必要参数
			if ($type === 'location'):
                $longitude = $this->input->post('longitude');
                $latitude = $this->input->post('latitude');
                if ( empty($longitude) || empty($latitude)):
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '位置类消息需传入经纬度';
                    exit();
                endif;
            endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->form_validation->set_rules('user_id', '收信用户ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('biz_id', '收信商家ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('stuff_id', '收信员工ID', 'trim|is_natural_no_zero');
			$this->form_validation->set_rules('sender_type', '发信端类型', 'trim|required|in_list[admin,biz,client]');
			$this->form_validation->set_rules('receiver_type', '收信端类型', 'trim|required|in_list[admin,biz,client]');
			$this->form_validation->set_rules('type', '类型', 'trim|required');
			$this->form_validation->set_rules('ids', '内容ID们', 'trim|max_length[255]');
			$this->form_validation->set_rules('title', '标题', 'trim|max_length[30]');
			$this->form_validation->set_rules('excerpt', '摘要', 'trim|max_length[100]');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|max_length[255]');
			$this->form_validation->set_rules('content', '内容', 'trim|max_length[5000]');
			$this->form_validation->set_rules('longitude', '经度', 'trim|max_length[10]');
			$this->form_validation->set_rules('latitude', '纬度', 'trim|max_length[10]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $creator_id,
                    'time_create' => time(),

                    'sender_type' => empty($this->input->post('sender_type'))? 'client': $this->input->post('sender_type'),
                    'receiver_type' => empty($this->input->post('receiver_type'))? 'biz': $this->input->post('receiver_type'),

                    'type' => empty($this->input->post('type'))? 'text': $this->input->post('type'),

                    'longitude' => empty($longitude)? NULL: $longitude,
                    'latitude' => empty($latitude)? NULL: $latitude,
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'user_id', 'biz_id', 'stuff_id', 'ids', 'title', 'excerpt', 'url_image', 'content',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = $this->input->post($name);

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
		 * 6 编辑多行数据特定字段
		 *
		 * 修改多行数据的单一字段值
		 */
		public function edit_bulk()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client',);
			$this->client_check($type_allowed);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_bulk_required;
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
			$this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore,revoke]');
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

	} // end class Message

/* End of file Message.php */
/* Location: ./application/controllers/Message.php */
