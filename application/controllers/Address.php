<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Address/ADR 地址类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Address extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'user_id', 'mobile', 'nation', 'province', 'city', 'county', 'longitude', 'latitude', 'zipcode', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'address_id', 'user_id', 'brief', 'fullname', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'zipcode',
            'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id', 'fullname', 'mobile', 'province', 'city', 'street',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'brief', 'fullname', 'mobile', 'nation', 'province', 'city', 'county', 'street', 'longitude', 'latitude', 'zipcode',
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id', 'fullname', 'mobile', 'province', 'city', 'street',
		);

		/**
		 * 编辑多行特定字段时必要的字段名
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'address'; // 这里……
			$this->id_name = 'address_id'; // 这里……

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

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

            // 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
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
			$type_allowed = array('client'); // 客户端类型
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
			$this->form_validation->set_rules('brief', '简称', 'trim|max_length[10]');
			$this->form_validation->set_rules('fullname', '收货人姓名', 'trim|required|min_length[2]|max_length[15]');
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|is_natural');
			$this->form_validation->set_rules('nation', '国别', 'trim');
			$this->form_validation->set_rules('province', '省', 'trim|required|max_length[10]');
			$this->form_validation->set_rules('city', '市', 'trim|required|max_length[10]');
			$this->form_validation->set_rules('county', '区/县', 'trim|max_length[10]');
			$this->form_validation->set_rules('street', '具体地址；小区名、路名、门牌号等', 'trim|required|max_length[50]');
			$this->form_validation->set_rules('longitude', '经度', 'trim|min_length[7]|max_length[10]|decimal');
			$this->form_validation->set_rules('latitude', '纬度', 'trim|min_length[7]|max_length[10]|decimal');
			$this->form_validation->set_rules('zipcode', '邮政编码', 'trim|integer|max_length[6]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

					'user_id' => $user_id,
					//'nation' => empty($this->input->post('nation'))? '中国': $this->input->post('nation'),
					'nation' => '中国', // 暂时只支持中国
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'brief', 'fullname', 'mobile', 'province', 'city', 'county', 'street', 'zipcode',
				);
				foreach ($data_need_no_prepare as $name)
                    $data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);


                // 若已传入经纬度，直接进行设置；若未设置经纬度，则通过地址（若有）借助高德地图相关API转换获取
                if ( !empty($this->input->post('longitude')) && !empty($this->input->post('latitude')) ):
                    $data_to_create['latitude'] = $this->input->post('latitude');
                    $data_to_create['longitude'] = $this->input->post('longitude');
                elseif ( !empty($this->input->post('province')) && !empty($this->input->post('city')) && !empty($this->input->post('street')) ):
                    // 拼合待转换地址（省、市、区/县（可为空）、具体地址）
                    $address = $this->input->post('province'). $this->input->post('city'). $this->input->post('county'). $this->input->post('street');
                    $location = $this->amap_geocode($address, $this->input->post('city'));
                    if ( $location !== FALSE ):
                        $data_to_create['latitude'] = $location['latitude'];
                        $data_to_create['longitude'] = $location['longitude'];
                    endif;
                endif;

				$result = $this->basic_model->create($data_to_create, TRUE);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $result;
					$this->result['content']['message'] = '创建成功';

					// 获取已创建的地址ID
					$id = $result;

					// 若当前地址需要设为默认地址
					if ( $this->input->post('default_this') == 1 ):
						$update_result = $this->default_this($id, $user_id);
						if ( $update_result !== FALSE ):
							$this->result['content']['address_id'] = $id; // 通知前端更新本地默认地址
							$this->result['content']['message'] .= '，已设置为默认地址';
						endif;
						
					else:
						// 获取当前用户的地址数量，若仅有1个未删除地址，则更新当前地址为该用户的默认地址
						$condition = array(
							'user_id' => $user_id,
							'time_delete' => 'NULL',
						);
						$address_count = $this->basic_model->count($condition);
						$this->result['content']['count'] = $address_count; // 当前用户未删除地址数
						if ( $address_count === 1):
							$update_result = $this->default_this($id, $user_id);
							if ( $update_result !== FALSE ):
								$this->result['content']['address_id'] = $id; // 通知前端更新本地默认地址
								$this->result['content']['message'] .= '，已设置为默认地址';
							endif;
						endif;
					
					endif;

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
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

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
			$this->form_validation->set_rules('brief', '简称', 'trim|max_length[10]');
			$this->form_validation->set_rules('fullname', '收货人姓名', 'trim|required|min_length[2]|max_length[15]');
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|is_natural');
			$this->form_validation->set_rules('nation', '国别', 'trim');
			$this->form_validation->set_rules('province', '省', 'trim|required|max_length[10]');
			$this->form_validation->set_rules('city', '市', 'trim|required|max_length[10]');
			$this->form_validation->set_rules('county', '区/县', 'trim|max_length[10]');
			$this->form_validation->set_rules('street', '具体地址；小区名、路名、门牌号等', 'trim|required|max_length[50]');
			$this->form_validation->set_rules('longitude', '经度', 'trim|min_length[7]|max_length[10]|decimal');
			$this->form_validation->set_rules('latitude', '纬度', 'trim|min_length[7]|max_length[10]|decimal');
			$this->form_validation->set_rules('zipcode', '邮政编码', 'trim|integer|max_length[6]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

					//'nation' => empty($this->input->post('nation'))? '中国': $this->input->post('nation'),
					'nation' => '中国', // 暂时只支持中国
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'brief', 'fullname', 'mobile', 'province', 'city', 'county', 'street', 'zipcode',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

                // 若已传入经纬度，直接进行设置；若未设置经纬度，则通过地址（若有）借助高德地图相关API转换获取
                if ( !empty($this->input->post('longitude')) && !empty($this->input->post('latitude')) ):
                    $data_to_edit['latitude'] = $this->input->post('latitude');
                    $data_to_edit['longitude'] = $this->input->post('longitude');
                elseif ( !empty($this->input->post('province')) && !empty($this->input->post('city')) && !empty($this->input->post('street')) ):
                    // 拼合待转换地址（省、市、区/县（可为空）、具体地址）
                    $address = $this->input->post('province'). $this->input->post('city'). $this->input->post('county'). $this->input->post('street');
                    $location = $this->amap_geocode($address, $this->input->post('city'));
                    if ( $location !== FALSE ):
                        $data_to_edit['latitude'] = $location['latitude'];
                        $data_to_edit['longitude'] = $location['longitude'];
                    endif;
                endif;

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
                    $this->result['status'] = 200;
                    $this->result['content']['message'] = '编辑成功';

					// 若当前地址需要设为默认地址
					if ( $this->input->post('default_this') == 1 ):
						$update_result = $this->default_this($id, $user_id);
						if ( $update_result !== FALSE ):
							$this->result['content']['address_id'] = $id; // 通知前端更新本地默认地址
							$this->result['content']['message'] .= '，已设置为默认地址';
						endif;
					
					endif;

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
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

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

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore]');
			$this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
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

        /*
         * 以下为工具方法
         */

		// 设置特定地址为默认地址
		protected function default_this($address_id, $user_id)
		{
			$this->switch_model('user', 'user_id');

			$data_to_edit = array(
				'address_id' => $address_id,
				'operator_id' => $user_id,
			);
			$result = $this->basic_model->edit($user_id, $data_to_edit);

			return $result;
		} // end default_this

	} // end class Address

/* End of file Address.php */
/* Location: ./application/controllers/Address.php */
