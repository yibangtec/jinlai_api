<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * BIZ 商家类
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
			'longitude', 'latitude', 'nation', 'province', 'city', 'county',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'name', 'brief_name', 'url_name', 'url_logo', 'slogan', 'description', 'notification',
			'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
			'fullname_owner', 'fullname_auth',
			'code_license', 'code_ssn_owner',  'code_ssn_auth',
			'bank_name', 'bank_account',
			'url_image_license', 'url_image_owner_id', 'url_image_auth_id', 'url_image_auth_doc', 'url_image_product', 'url_image_produce', 'url_image_retail',
			'longitude', 'latitude', 'nation', 'province', 'city', 'county', 'street', 'url_web', 'url_weibo', 'url_wechat',
			'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 'status',
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
			'name', 'brief_name', 'tel_public', 'tel_protected_biz',
			'fullname_owner', 'code_license', 'code_ssn_owner',
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'name', 'brief_name', 'url_name', 'url_logo', 'slogan', 'description', 'notification',
			'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
			'fullname_owner', 'fullname_auth',
			'code_license', 'code_ssn_owner', 'code_ssn_auth',
			'bank_name', 'bank_account', 'url_image_license', 'url_image_owner_id', 'url_image_auth_id', 'url_image_auth_doc', 'url_image_product', 'url_image_produce', 'url_image_retail',
			'longitude', 'latitude', 'nation', 'province', 'city', 'county', 'street', 'url_web', 'url_weibo', 'url_taobao', 'url_wechat',
		);

		/**
		 * 编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
			'tel_public', 'fullname_owner', 'code_license', 'code_ssn_owner',
		);

		/**
		 * 编辑单行特定字段时必要的字段名
		 */
		protected $names_edit_certain_required = array(
			'user_id', 'id', 'name', 'value',
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
			$this->table_name = 'biz'; // 这里……
			$this->id_name = 'biz_id';  // 还有这里，OK，这就可以了
			$this->names_to_return[] = 'biz_id';

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;
			// 客户端仅获取状态为‘正常’的商家
			if ($this->app_type === 'client')
				$condition['status'] = '正常';
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) )
					$condition[$sorter] = $this->input->post($sorter);
			endforeach;

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

			// 筛选条件
			$condition = NULL;

			// 客户端仅获取状态为‘正常’的商家
			//if ($this->app_type === 'client') $condition['status'] = '正常';

			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					$condition[$sorter] = $this->input->post($sorter);
				endif;
			endforeach;

			// 排序条件
			$order_by = NULL;

			// 限制可返回的字段
			if ($this->app_type === 'client')
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
			$url_name = $this->input->post('url_name');
			if ( empty($id) && empty($url_name) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;
			
			$this->db->select( implode(',', $this->names_to_return) );

			// 客户端仅获取状态为‘正常’的商家
			if ($this->app_type === 'client'):
				//$this->db->where('status', '正常');
			endif;

			// 获取特定项；默认可获取已删除项
			if ( !empty($url_name) ):
				$item = $this->basic_model->find('url_name', $url_name);
			else:
				$item = $this->basic_model->select_by_id($id);
			endif;
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

			// 操作可能需要检查操作权限
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

			// 检查想要创建商家的用户是否已是其它商家的员工
			if ( !empty( $this->stuff_exist($user_id) ) ):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = '该用户已是其它商家的管理员或员工，不可创建新商家';
				exit();
			endif;

			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('name', '商家全称', 'trim|required|min_length[5]|max_length[35]|is_unique[biz.name]');
			$this->form_validation->set_rules('brief_name', '简称', 'trim|required|max_length[15]|is_unique[biz.brief_name]');
			$this->form_validation->set_rules('description', '简介', 'trim|max_length[200]');
			$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|required|min_length[10]|max_length[13]|is_unique[biz.tel_public]');
			$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|required|is_natural|exact_length[11]|is_unique[biz.tel_protected_biz]');

			$this->form_validation->set_rules('fullname_owner', '法人姓名', 'trim|required|max_length[15]');
			$this->form_validation->set_rules('fullname_auth', '经办人姓名', 'trim|max_length[15]');

			$this->form_validation->set_rules('code_license', '工商注册号', 'trim|required|min_length[15]|max_length[18]|is_unique[biz.code_license]');
			$this->form_validation->set_rules('code_ssn_owner', '法人身份证号', 'trim|required|exact_length[18]|is_unique[biz.code_ssn_owner]');
			$this->form_validation->set_rules('code_ssn_auth', '经办人身份证号', 'trim|exact_length[18]|is_unique[biz.code_ssn_auth]');

			$this->form_validation->set_rules('url_image_license', '营业执照', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_owner_id', '法人身份证', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_id', '经办人身份证', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_doc', '经办人授权书', 'trim|max_length[255]');

			$this->form_validation->set_rules('bank_name', '开户行名称', 'trim|min_length[3]|max_length[20]');
			$this->form_validation->set_rules('bank_account', '开户行账号', 'trim|max_length[30]|is_unique[biz.bank_account]');

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
					'name', 'brief_name', 'tel_public', 'tel_protected_biz',
					'description', 'bank_name', 'bank_account',
					'fullname_owner', 'fullname_auth',
					'code_license', 'code_ssn_owner', 'code_ssn_auth',
					'url_image_license', 'url_image_auth_id', 'url_image_auth_doc', 'url_image_produce', 'url_image_retail',
					'url_image_owner_id', 'url_image_product',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_create[$name] = $this->input->post($name);
				// 去除biz表中没有的user_id值，该值用于稍后创建员工关系
				unset($data_to_create['user_id']);

				// 创建商家
				$biz_id = $this->basic_model->create($data_to_create, TRUE);
				if ($biz_id !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $biz_id;
					$this->result['content']['message'] = '创建商家成功，我们将尽快受理您的入驻申请';

					$mobile = $tel_protected_biz;

					// 创建员工
					$stuff_id = $this->stuff_create($user_id, $biz_id, $mobile);
					if ($stuff_id !== FALSE):
						$this->result['content']['message'] .= '，您已成为该商家的管理员';
					endif;

					// 发送商家通知短信
					$content = '恭喜您成功创建商家，我们已自动为您生成入驻申请并安排事务最少的同事进行受理，敬请稍候。';
					@$this->sms_send($mobile, $content); // 容忍发送失败

					// 发送招商经理通知短信
					$mobile = '15192098644';
					$content = '商家“'.$this->input->post('brief_name').'(商家编号'.$biz_id.')”已提交入驻申请，请尽快跟进，对方商务联系手机号为'.$tel_protected_biz.'。';
					@$this->sms_send($mobile, $content); // 容忍发送失败

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建商家失败';

				endif;
			endif;
		} // end create

		// 查找员工
		private function stuff_exist($user_id)
		{
			// 更改数据库信息
			$this->basic_model->table_name = 'stuff';
			$this->basic_model->id_name = 'stuff_id';
			
			$result = $this->basic_model->find('user_id', $user_id);

			// 还原数据库信息
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			return $result;
		} // end stuff_exist
		
		// 创建员工
		private function stuff_create($user_id, $biz_id, $mobile)
		{
			// 更改数据库信息
			$this->basic_model->table_name = 'stuff';
			$this->basic_model->id_name = 'stuff_id';

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

			// 还原数据库信息
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			return $result;
		} // end stuff_create

		// 发送短信
		private function sms_send($mobile, $content)
		{
			// 为短信内容添加后缀签名
			$content .= '【进来商城】';

			$this->load->library('luosimao');
			$result = $this->luosimao->send($mobile, $content);
		} // end test_sms

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
				${$param} = $this->input->post($param);
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			if ($this->app_type === 'admin'):
				$this->form_validation->set_rules('name', '商家名称', 'trim|required|min_length[5]|max_length[35]');
				$this->form_validation->set_rules('brief_name', '简称', 'trim|required|max_length[15]');
				$this->form_validation->set_rules('url_name', '店铺域名', 'trim|max_length[20]|alpha_dash');
				$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|required|exact_length[11]|is_natural');
			endif;
			// 载入验证规则
			$rule_path = APPPATH. 'libraries/form_rules/Biz.php';
			require($rule_path);

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,
					'url_name' => strtolower( $this->input->post('url_name') ),
					//'nation' => empty($this->input->post('nation'))? '中国': $this->input->post('nation'),
					'nation' => '中国', // 暂时只支持中国
				);

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

				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'name', 'brief_name', 'url_logo', 'slogan', 'description', 'notification',
					'tel_public', 'tel_protected_biz', 'tel_protected_fiscal', 'tel_protected_order',
					'fullname_owner', 'fullname_auth',
					'code_license', 'code_ssn_owner',  'code_ssn_auth',
					'bank_name', 'bank_account', 'url_image_license', 'url_image_owner_id', 'url_image_auth_id', 'url_image_auth_doc', 'url_image_product', 'url_image_produce', 'url_image_retail',
					'province', 'city', 'county', 'street',
					'url_web', 'url_weibo', 'url_wechat',
				);
				foreach ($data_need_no_prepare as $name)
					$data_to_edit[$name] = $this->input->post($name);

				// 根据客户端类型等条件筛选可操作的字段名
				if ($this->app_type !== 'admin'):
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
					$this->result['content']['message'] = '商家修改成功';

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
			$type_allowed = array('admin', 'biz'); // 客户端类型
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
				$this->form_validation->set_rules('name', '商家名称', 'trim|min_length[5]|max_length[35]');
				$this->form_validation->set_rules('brief_name', '商家简称', 'trim|max_length[15]');
				$this->form_validation->set_rules('url_name', '店铺域名', 'trim|max_length[20]|alpha_dash');
				$this->form_validation->set_rules('tel_protected_biz', '商务联系手机号', 'trim|exact_length[11]|is_natural');
			endif;
			$this->form_validation->set_rules('url_logo', 'LOGO', 'trim|max_length[255]');
			$this->form_validation->set_rules('slogan', '说明', 'trim|max_length[20]');
			$this->form_validation->set_rules('description', '简介', 'trim|max_length[200]');
			$this->form_validation->set_rules('notification', '公告', 'trim|max_length[100]');
			$this->form_validation->set_rules('url_web', '官方网站', 'trim|max_length[255]|valid_url');
			$this->form_validation->set_rules('url_weibo', '官方微博', 'trim|max_length[255]|valid_url');
			$this->form_validation->set_rules('url_wechat', '微信二维码', 'trim|max_length[255]');

			$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|min_length[10]|max_length[13]');
			$this->form_validation->set_rules('tel_protected_fiscal', '财务联系手机号', 'trim|exact_length[11]|is_natural');
			$this->form_validation->set_rules('tel_protected_order', '订单通知手机号', 'trim|exact_length[11]|is_natural');

			$this->form_validation->set_rules('fullname_owner', '法人姓名', 'trim|max_length[15]');
			$this->form_validation->set_rules('fullname_auth', '经办人姓名', 'trim|max_length[15]');

			$this->form_validation->set_rules('code_license', '工商注册号', 'trim|min_length[15]|max_length[18]|is_unique[biz.code_license]');
			$this->form_validation->set_rules('code_ssn_owner', '法人身份证号', 'trim|exact_length[18]|is_unique[biz.code_ssn_owner]');
			$this->form_validation->set_rules('code_ssn_auth', '经办人身份证号', 'trim|exact_length[18]|is_unique[biz.code_ssn_auth]');

			$this->form_validation->set_rules('url_image_license', '营业执照正/副本', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_owner_id', '法人身份证照片', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_id', '经办人身份证', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_auth_doc', '经办人授权书', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_product', '产品', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_produce', '工厂/产地', 'trim|max_length[255]');
			$this->form_validation->set_rules('url_image_retail', '门店/柜台', 'trim|max_length[255]');

			$this->form_validation->set_rules('bank_name', '开户行名称', 'trim|min_length[3]|max_length[20]');
			$this->form_validation->set_rules('bank_account', '开户行账号', 'trim|max_length[30]');

			$this->form_validation->set_rules('nation', '国家', 'trim|max_length[10]');
			$this->form_validation->set_rules('province', '省', 'trim|max_length[10]');
			$this->form_validation->set_rules('city', '市', 'trim|max_length[10]');
			$this->form_validation->set_rules('county', '区/县', 'trim|max_length[10]');
			$this->form_validation->set_rules('street', '具体地址；小区名、路名、门牌号等', 'trim|max_length[50]');
			$this->form_validation->set_rules('longitude', '经度', 'trim|min_length[7]|max_length[10]|decimal');
			$this->form_validation->set_rules('latitude', '纬度', 'trim|min_length[7]|max_length[10]|decimal');

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
			$this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore]');
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

	} // end class Biz

/* End of file Biz.php */
/* Location: ./application/controllers/Biz.php */
