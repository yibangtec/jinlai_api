<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Account/ACT 账户类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Account extends MY_Controller
	{
		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'user'; // 这里……
			$this->id_name = 'user_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end __construct

        /**
         * ACT1 短信登录/注册
         */
        public function login_sms()
        {
            // 验证短信正确性
            $this->verify_sms();

            // 获取手机号
            $mobile = $this->input->post('mobile');

            // 获取用户/检查用户是否存在
            $user_info = $this->check_mobile($mobile);

            // 准备其它登录信息
            $login_info = $this->generate_login_info();

            // 若用户不存在，创建用户并返回用户信息；若存在，返回用户信息
            if ( empty($user_info) ):
                // 创建用户
                $data_to_create['mobile'] = $mobile;
                $data_to_create = array_merge($data_to_create, $login_info);
                $result = $this->user_create($data_to_create);
                if ( !empty($result) ):
                    // 获取用户信息
                    $item = $this->basic_model->select_by_id($result);
                    // 不返回真实密码信息
                    if ( !empty($item['password']) ) $item['password'] = 'set';

                    $this->result['status'] = 200;
                    $this->result['content'] = $item;

                else:
                    $this->result['status'] = 400;
                    $this->result['content']['error']['message'] = '用户创建失败';

                endif;

            else:
                // 更新最后登录信息
                @$this->basic_model->edit($user_info['user_id'], $login_info);

                // 非客户端登录时，检查该用户是否为员工
                if ($this->app_type !== 'client') $user_info = $this->check_stuff( $user_info );

                // 不返回真实密码信息
                if ( !empty($user_info['password']) ) $user_info['password'] = 'set';

                $this->result['status'] = 200;
                $this->result['content'] = array_merge($user_info, $login_info);

            endif;
        } // end login_sms

        /**
         * ACT4 密码登录
         *
         * @params string $password 登录密码
         * @params string $mobile 手机号
         */
        public function login()
        {
            // 检查必要参数是否已传入
            $password = $this->input->post('password');
            if ( empty($password) ):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未传入';
                exit();
            endif;

            // 手机号、Email须至少传入一项
            $mobile = $this->input->post('mobile');
            $email = $this->input->post('email');
            if ( empty($mobile.$email) ):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '手机号、Email须至少传入一项';
                exit();
            endif;

            // 初始化并配置表单验证库
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('', '');
            // 待验证的表单项
            $this->form_validation->set_rules('mobile', '手机号', 'trim|exact_length[11]|is_natural_no_zero');
            $this->form_validation->set_rules('email', 'Email', 'trim|max_length[40]|valid_email');
            $this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

            // 若表单提交不成功
            if ($this->form_validation->run() === FALSE):
                $this->result['status'] = 401;
                $this->result['content']['error']['message'] = validation_errors();
                exit();

            else:
                // 获取用户/检查用户是否存在
                if ( !empty($mobile) ):
                    $user_info = $this->check_mobile($mobile);
                else:
                    $user_info = $this->check_email($email);
                endif;

                // 若用户存在，检查密码正确性
                if ( !empty($user_info) ):
                    if ($user_info['password'] === sha1($password)):
                        // 准备其它登录信息
                        $login_info = $this->generate_login_info();

                        // 更新最后登录信息
                        @$this->basic_model->edit($user_info['user_id'], $login_info);

                        // 非客户端登录时，检查该用户是否为员工
                        if ($this->app_type !== 'client') $user_info = $this->check_stuff( $user_info );

                        // 不返回真实密码信息
                        if ( !empty($user_info['password']) ) $user_info['password'] = 'set';

                        $this->result['status'] = 200;
                        $this->result['content'] = array_merge($user_info, $login_info);

                    else:
                        $this->result['status'] = 401;
                        $this->result['content']['error']['message'] = '密码错误';

                    endif;

                else:
                    $this->result['status'] = 414;
                    $this->result['content']['error']['message'] = '用户未注册';

                endif;

            endif;
        } // end login

        /**
         * ACT7 微信登录
         *
         * 使用微信UnionID免密码登录
         */
        public function login_wechat()
        {
            // 必须传入微信UnionID
            $wechat_union_id = $this->input->post('wechat_union_id');
            if ( empty($wechat_union_id) ):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '须传入微信UnionID';
                exit();
            endif;

            // 初始化并配置表单验证库
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('', '');
            // 待验证的表单项
            $this->form_validation->set_rules('wechat_union_id', '微信UnionID', 'trim|required|max_length[29]');

            // 若表单提交不成功
            if ($this->form_validation->run() === FALSE):
                $this->result['status'] = 401;
                $this->result['content']['error']['message'] = validation_errors();
                exit();

            else:
                // 获取用户/检查用户是否存在
                $user_info = $this->check_wechat($wechat_union_id);

                // 准备其它登录信息
                $login_info = $this->generate_login_info($wechat_union_id);

                // 若用户不存在，创建用户并返回用户信息；若存在，返回用户信息
                if ( empty($user_info) ):
                    // 创建用户
                    $data_to_create = array(
                        'wechat_union_id' => $this->input->post('wechat_union_id'),
                    );
                    $data_to_create = array_merge($data_to_create, $login_info);

                    $result = $this->user_create($data_to_create, 'wechat_union_id');
                    if ( !empty($result) ):
                        $this->result['status'] = 200;
                        $this->result['content'] = $this->check_wechat($wechat_union_id);

                    else:
                        $this->result['status'] = 414;
                        $this->result['content']['error']['message'] = '该微信union_id未注册为用户，请直接用手机号登录';
                    endif;

                else:
                    // 更新最后登录信息
                    @$this->basic_model->edit($user_info['user_id'], $login_info);

                    // 非客户端登录时，检查该用户是否为员工
                    if ($this->app_type !== 'client') $user_info = $this->check_stuff( $user_info );

                    // 不返回真实密码信息
                    if ( !empty($user_info['password']) ) $user_info['password'] = 'set';

                    $this->result['status'] = 200;
                    $this->result['content'] = array_merge($user_info, $login_info);

                endif;

            endif;
        } // end login_wechat

        /**
		 * ACT2 密码设置
		 */
		public function password_set()
		{
			// 检查必要参数是否已传入
			$required_params = array('user_id', 'password', 'password_confirm');
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
			// 待验证的表单项
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				$user_info = $this->basic_model->select_by_id($user_id);

				if ( empty($user_info) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '用户未注册';

				elseif ( !empty($user_info['password']) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 411;
					$this->result['content']['error']['message'] = '该用户已设置过密码，如需修改应通过“密码修改”进行操作';

				else:
					// 设置密码并更新登录信息
					$data_to_edit = array(
						'password' => sha1( $password ),
					);
					
					// 更新资料
					$result = $this->basic_model->edit($user_id, $data_to_edit);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码设置成功';

					else:
						$this->result['status'] = 434;
						$this->result['content']['error']['message'] = '密码设置失败';

					endif;
				endif;
				
			endif;
		} // end password_set

		/**
		 * ACT3 密码修改
		 */
		public function password_change()
		{
			// 检查必要参数是否已传入
			$required_params = array('user_id', 'password_current', 'password', 'password_confirm');
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
			// 待验证的表单项
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('password_current', '原密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				$user_info = $this->basic_model->select_by_id($user_id);

				if ( empty($user_info) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '用户未注册';

				elseif ( empty($user_info['password']) ):
					// 若未设置密码，则进行提示
					$this->result['status'] = 411;
					$this->result['content']['error']['message'] = '该用户未设置过密码，如需设置应通过“密码设置”进行操作';

				elseif ( $user_info['password'] !== SHA1($password_current) ):
					// 若原密码输入错误，则进行提示
					$this->result['status'] = 401;
					$this->result['content']['error']['message'] = '原密码错误';

				elseif ( $user_info['password'] === SHA1($password) ):
					// 若新密码与原密码相同，则进行提示
					$this->result['status'] = 401;
					$this->result['content']['error']['message'] = '新密码应与原密码不同';

				else:
					// 设置密码并更新登录信息
					$data_to_edit = array(
						'password' => sha1($password),
						'last_login_timestamp' => time(),
						'last_login_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'),
					);

					// 更新资料
					$result = $this->basic_model->edit($user_id, $data_to_edit);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码修改成功';

					else:
						$this->result['status'] = 434;
						$this->result['content']['error']['message'] = '密码修改失败';

					endif;
				endif;

			endif;
		} // end password_change

		/**
		 * ACT5 密码重置
		 */
		public function password_reset()
		{
			// 验证短信正确性
			$this->verify_sms();

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 待验证的表单项
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 准备最后登录信息
				$login_info['last_login_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
				$login_info['last_login_timestamp'] = time();

				// 获取用户/检查用户是否存在
				$user_info = $this->check_mobile( $this->input->post('mobile') );

				// 若用户存在，更新用户密码；若用户未注册，创建用户。
				// 同时更新最后登录信息
				if ( !empty($user_info) ):
					$login_info['password'] = sha1($this->input->post('password'));

					$result = $this->basic_model->edit($user_info['user_id'], $login_info);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码重置成功';
					else:
						$this->result['status'] = 434;
						$this->result['content'] = '密码重置失败';
					endif;

				else:
					// 创建用户
					$data_to_create = array(
						'mobile' => $this->input->post('mobile'),
						'password' => sha1( $this->input->post('password') ),
					);
					$data_to_create = array_merge($data_to_create, $login_info);

					$result = $this->user_create($data_to_create);
					if ( !empty($result) ):
						$this->result['status'] = 200;
						$this->result['content'] = '用户创建成功，请使用该手机号及密码登录';
					else:
						$this->result['status'] = 414;
						$this->result['content']['error']['message'] = '该手机号未注册为用户，请直接用手机号登录';
					endif;

				endif;

			endif;
		} // end password_reset

		/**
		 * ACT6 用户存在性
		 */
		public function user_exist()
		{
			// 手机号、Email及微信UnionID须至少传入一项
			$mobile = $this->input->post('mobile');
			$email = $this->input->post('email');
			$wechat_union_id = $this->input->post('wechat_union_id');
			if ( empty( trim($mobile.$email.$wechat_union_id) ) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '手机号、Email及微信UnionID须至少传入一项';
				exit();
			endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 待验证的表单项
			$this->form_validation->set_rules('mobile', '手机号', 'trim|exact_length[11]|is_natural_no_zero');
			$this->form_validation->set_rules('email', 'Email', 'trim|max_length[40]|valid_email');
			$this->form_validation->set_rules('wechat_union_id', '微信UnionID', 'trim|max_length[29]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				if ( !empty($mobile) ):
					$user_info = $this->check_mobile($mobile);
				elseif ( !empty($wechat_union_id) ):
					$user_info = $this->check_wechat($wechat_union_id);
				else:
					$user_info = $this->check_email($email);
				endif;

				// 返回检查结果
				if ( !empty($user_info) ):
					$this->result['status'] = 200;
					$this->result['content']['is_exist'] = TRUE;
                    $this->result['content']['password_set'] = empty($user_info['password'])? FALSE: TRUE; // 检查是否已设置密码
					$this->result['content']['status'] = $user_info['status'];

				else:
					$this->result['status'] = 414;
					$this->result['content']['is_exist'] = FALSE;
					$this->result['content']['error']['message'] = '用户不存在';
				endif;
			endif;
		} // end user_exist

        /**
         * 以下为工具方法
         */

		/**
		 * 短信验证
		 */
		private function verify_sms()
		{
			// 检查必要参数是否已传入
			$required_params = array('mobile', 'captcha', 'sms_id');
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
			// 动态设置待验证字段名及字段值
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]');
			$this->form_validation->set_rules('sms_id', '短信ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('captcha', '短信验证码', 'trim|required|exact_length[6]|is_natural_no_zero');
			
			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 筛选条件
				$condition = array(
					'type' => '1', // 仅限验证码类短信
					'sms_id' => $sms_id,
					'mobile' => $mobile,
					'captcha' => $captcha,
					'time_expire >=' => time(),
				);

				// 获取列表；默认不获取已删除项
				$this->switch_model('sms', 'sms_id');
				$items = $this->basic_model->select($condition, NULL, FALSE, FALSE);
				$this->reset_model();

				if ( empty($items) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '验证码错误或已过期';
					exit();

				endif;

			endif;
		} // end verify_sms

		/**
		 * 创建用户
		 *
		 * @params array $data_to_create 待创建的用户信息
         * @params string $name 待创建用户的注册方式，默认为手机号
		 */
		private function user_create($data_to_create, $name = 'mobile')
		{
			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $data_to_create[$name];
			$this->form_validation->set_data($data_to_validate);
			$this->form_validation->set_rules('mobile', '手机号', 'trim|exact_length[11]|is_natural_no_zero|is_unique[user.mobile]');
            $this->form_validation->set_rules('wechat_union_id', '微信UnionID', 'trim|max_length[29]|is_unique[user.wechat_union_id]');
			$this->form_validation->set_rules('email', 'Email', 'trim|max_length[40]|valid_email|is_unique[user.email]');
            $this->form_validation->set_rules('promoter_id', '推广者', 'trim|is_natural_no_zero');
            $this->form_validation->set_rules('getui_id', '个推ID', 'trim|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
                // 根据当前UNIX时间戳生成默认昵称（允许重复）、注册时间，及推广者ID（若有）
                $data_to_create['time_create'] = $data_to_create['last_login_timestamp'] = time();
                $data_to_create['nickname'] = empty($this->input->post('nickname'))? 'user'. substr($data_to_create['time_create'], 2, 8): $this->input->post('nickname');
                $data_to_create['promoter_id'] = $this->input->post('promoter_id');

				// 创建并返回行ID
				return $this->basic_model->create($data_to_create, TRUE);

			endif;
		} // end user_create

		/**
		 * 检查是否已经有以相应手机号注册的账户
		 *
		 * @params string $mobile 需要检查的手机号
		 * @params boolean $return_boolean 是否需要以布尔值形式返回
		 */
		private function check_mobile($mobile, $return_boolean = FALSE)
		{
			$data_to_search['mobile'] = $mobile;
			$result = $this->basic_model->match($data_to_search);

			if ($return_boolean === FALSE):
				return $result;
			else:
				return ( empty($result) )? FALSE: TRUE;
			endif;
		} // end check_mobile

		/**
		 * 检查是否已经有以相应Email注册的账户
		 *
		 * @params string $email 需要检查的Email
		 * @params boolean $return_boolean 是否需要以布尔值形式返回
		 */
		private function check_email($email, $return_boolean = FALSE)
		{
			$data_to_search['email'] = $email;
			$result = $this->basic_model->match($data_to_search);

			if ($return_boolean === FALSE):
				return $result;
			else:
				return ( empty($result) )? FALSE: TRUE;
			endif;
		} // end check_email
		
		/**
		 * 检查是否已经有以相应微信UnionID注册的账户
		 *
		 * @params string $wechat_union_id 需要检查的微信UnionID
		 * @params boolean $return_boolean 是否需要以布尔值形式返回
		 */
		private function check_wechat($wechat_union_id, $return_boolean = FALSE)
		{
			$data_to_search['wechat_union_id'] = $wechat_union_id;
			$result = $this->basic_model->match($data_to_search);

			if ($return_boolean === FALSE):
				return $result;
			else:
				return ( empty($result) )? FALSE: TRUE;
            endif;
		} // end check_wechat

		// 检查是否已经有与相应user_id相关的员工记录
		private function check_stuff($user_info)
		{
			$data_to_search['user_id'] = $user_info['user_id'];
			$data_to_search['time_delete'] = NULL; // 仅获取有效的员工关系
			
			$this->switch_model('stuff', 'stuff_id');
		
            $stuff = $this->basic_model->match($data_to_search);
         

            // 默认用户非员工
            $this->result['status'] = 415;
            // 不允许已冻结员工登录
            if ($stuff['status'] === '已冻结'):
                    $this->result['content']['error']['message'] = '该用户的员工身份已冻结';
                    exit();

            else:
                // 管理端员工规则
                if ($this->app_type === 'admin'):
                    if (empty($stuff)):
                        $this->result['content']['error']['message'] = '该用户并非管理端员工';
                        exit();
                    endif;

                // 商家端员工规则
                elseif ($this->app_type === 'biz'):

                endif;

                $user_info['stuff_id'] = $stuff['stuff_id'];
                $user_info['biz_id'] = $stuff['biz_id'];
                $user_info['role'] = $stuff['role'];
                $user_info['level'] = $stuff['level'];

                return $user_info;

            endif;
		} // end check_stuff

        /**
         * 生成登录信息
         *
         * @param string $wechat_union_id
         * @return mixed
         */
        private function generate_login_info($wechat_union_id = NULL)
        {
            // 用户登录信息
            $login_info = array();

            // 获取传入的第三方登录信息
            $sns_info = json_decode($this->input->post('sns_info'), TRUE);
            if ( ! empty($sns_info)):
                // 生成可用于生成/更新的用户信息
                $sns_data = array(
                    'nickname' => $sns_info['nickname'],
                    'gender' => $sns_info['sex'] == 1? '男': '女',
                    'avatar' => @$this->get_wechat_largest_avatar($sns_info['headimgurl']),
                );
                $login_info = array_merge($login_info, $sns_data);
            endif;

            // 若未传入微信UnionID，尝试获取
            if (empty($wechat_union_id)) $login_info['wechat_union_id'] = $this->input->post('wechat_union_id');

            // 个推ID
            $login_info['getui_id'] = $this->input->post('getui_id');

            // 登录记录信息
            $login_info['last_login_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
            $login_info['last_login_timestamp'] = time();

            return array_filter($login_info);
        } // end generate_login_info

        /**
         * 获取最大尺寸的微信用户头像
         * 相关方法调试完成后应移到API，先判断当前用户头像是否为内部资源文件，若否则使用本方法获取最大尺寸的微信头像并更新用户资料
         *
         * @param string $avatar 微信用户头像，例如"http://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46"；最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像）
         * @return string 最大尺寸的头像文件URL
         */
        private function get_wechat_largest_avatar($avatar)
        {
            if (empty($avatar)):
                return NULL;

            else:
                // 截取当前头像URL至最后一次出现"/"符号的位置，并拼上表示最大尺寸的"0"作为新头像URL
                $base_url = substr($avatar, 0, (strripos($avatar, '/') + 1));
                $largest_avatar_url = $base_url.'0';
                return $largest_avatar_url;

            endif;
        } // end get_wechat_largest_avatar

        public function env(){
        	var_dump(ENVIRONMENT);
        }
	} // end class Account

/* End of file Account.php */
/* Location: ./application/controllers/Account.php */
