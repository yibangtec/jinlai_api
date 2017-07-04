<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * SMS 短信类
	 *
	 * 调用第三方服务
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Sms extends MY_Controller
	{
		// 设置短信后缀签名
		protected $suffix = '【进来商城】';
		
		// 短信类型；1验证码2非验证码
		protected $type = '1';
		
		// 接收短信的手机号
		protected $mobile;
		
		// 批量接收短信的手机号，CSV格式
		protected $mobile_list;
		
		// 批量发送短信的预订时间
		protected $time = NULL;
		
		// 短信内容
		protected $content;

		// 验证码字符数、有效期、内容及过期时间；仅适用验证码类短信
		protected $captcha_length = 6;
		protected $seconds_before_expire = 180; // 默认3分钟
		protected $captcha;
		protected $time_expire;

		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'mobile', 'mobile_list', 'type', 'captcha', 'time', 'time_expire',

			'time_create', 'time_delete',
		);

		/**
		 * 可作为查询结果返回的字段名
		 */
		protected $names_to_return = array(
			'mobile', 'mobile_list', 'type', 'captcha', 'content', 'time', 'user_ip', 'time_expire',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'sms'; // 这里……
			$this->id_name = 'sms_id';  // 还有这里，OK，这就可以了
			$this->names_to_return[] = 'sms_id';

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}
		
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
			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post($sorter)) ):
					$condition[$sorter] = $this->input->post($sorter);
				endif;
			endforeach;
			
			// 排序条件
			$order_by = NULL;
			
			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认不获取已删除项
			$items = $this->basic_model->select($condition, $order_by, FALSE, FALSE);
			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = NULL;
			
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
				$this->result['content']['error']['message'] = NULL;

			endif;
		} // end detail
		
		/**
		 * 3 创建单条短信
		 *
		 * 生成单条短信内容以备后续发送
		 */
		public function create()
		{
			// 检查必要参数是否已传入
			$this->mobile = $this->input->post('mobile');
			if ( empty($this->mobile) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 需要发送的短信内容
			$content = $this->input->post('content')? $this->input->post('content'): NULL;
			
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|is_natural');
			$this->form_validation->set_rules('content', '短信内容', 'trim|max_length[67]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 根据是否传入了短信内容判断短信类型
				if ( empty($content) ):
					// 验证码短信
					$this->captcha = random_string('numeric', $this->captcha_length); // 根据类属性中设置的位数，生成纯数字验证码
					$content = $this->captcha. '是您的验证码，3分钟内有效；如非本人操作，请忽略本信息且勿将此码转告他人。';
					// 设置短信验证码有效期为发送成功后180秒内
					$this->time_expire = time() + $this->seconds_before_expire;

				else:
					$this->type = '2'; // 非验证码短信类型为2

				endif;
			
				$this->content = $content;

				// 发送短信
				$this->send();
			endif;
		} // create

		/**
		 * 发送单条短信并存储内容
		 *
		 * https://luosimao.com/docs/api/20#send_msg
		 *
		 * @param string $mobile 手机号
		 * @param string $content 短信内容；验证码短信可不传
		 * @param string $type 短信类型；验证码1，非验证码2，默认1
		 */
		public function send()
		{
			// 为短信内容添加后缀签名
			$this->content .= $this->suffix;

			// 发送短信
			$this->load->library('luosimao');
			$result = $this->luosimao->send($this->mobile, $this->content);

			// 解析发送结果
			$result_array = json_decode($result);

			// 根据短信发送结果进行相关操作
			if ($result_array->error == 0):
				// 保存短信内容
				$this->save();

			else:
				// 获取错误码相应的文本提示
				$error_message = $this->luosimao->error_text($result_array);

				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = $error_message;

			endif;
		} // send

		// 保存已单条发送成功的短信
		private function save()
		{
			$data_to_create = array(
				'type' => $this->type,
				'mobile' => $this->mobile,
				'content' => $this->content,

				'user_ip' => $this->input->post('ip')? $this->input->post('ip'): $this->input->ip_address(), // 对于不是通过服务器发来的请求，需要获取IP地址
			);
			// 对于验证码短信，记录验证码内容及过期时间
			if ($this->type == '1'):
				$data_to_create['captcha'] = $this->captcha;
				$data_to_create['time_expire'] = $this->time_expire;
			endif;
			$result = $this->basic_model->create($data_to_create, TRUE);

			$this->result['status'] = 200;
			$this->result['content']['sms_id'] = $result;
			$this->result['content']['message'] = '单条发送短信成功';
			$this->result['content']['time_expire'] = $this->time_expire;
		} // save

		/**
		 * 4 发送批量短信
		 *
		 * @params string $mobile_list 目标手机号码列表，多个号码间使用1个半角逗号分隔
		 * @params string $content 待发送短信内容
		 * @params string $time 待发送时间；2016-04-01 12:30:00
		 */
		public function create_bulk()
		{
			// 检查必要参数是否已传入
			$required_params = array('mobile_list', 'content');
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			$this->form_validation->set_rules('mobile_list', '目标手机号码列表', 'trim|required');
			$this->form_validation->set_rules('content', '短信内容', 'trim|required|max_length[67]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				$this->mobile_list = $mobile_list;
				$this->content = $content;
				$time = $this->input->post('time');
				if ( !empty($time) ) $this->time = $time;

				$this->type = '9'; // 通知类群发短信类型为9
				$this->send_bulk();

			endif;
		} // create_bulk

		/**
		 * 发送批量短信并储存内容
		 * https://luosimao.com/docs/api/20#send_batch
		 */
		public function send_bulk()
		{
			// 为短信内容添加后缀签名
			$this->content .= $this->suffix;

			// 发送短信
			$this->load->library('luosimao');
			$result = $this->luosimao->send_bulk($this->mobile_list, $this->content, $this->time);

			// 解析发送结果
			$result_array = json_decode($result);

			// 根据短信发送结果进行相关操作
			if ($result_array->error == 0):
				// 保存短信内容
				$this->save_bulk();

			else:
				// 获取错误码相应的文本提示
				$error_message = $this->luosimao->error_text($result_array);

				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = $error_message;

			endif;
		} // send_bulk

		// 保存已批量发送成功的短信
		public function save_bulk()
		{
			$data_to_create = array(
				'type' => $this->type,
				'mobile_list' => $this->mobile_list,
				'content' => $this->content,
				'time' => $this->time,

				'user_ip' => $this->input->post('ip')? $this->input->post('ip'): $this->input->ip_address(), // 对于不是通过服务器发来的请求，需要获取IP地址
			);

			$result = $this->basic_model->create($data_to_create, TRUE);

			$this->result['status'] = 200;
			$this->result['content']['sms_id'] = $result;
			$this->result['content']['message'] = '群发短信成功';
		} // end save_bulk

		/**
		 * 5 验证短信验证码有效性
		 *
		 * @params $mobile 手机号
		 * @params $captcha 验证码
		 * @params $sms_id 短信ID
		 */
		public function verify()
		{
			// 检查必要参数是否已传入
			$required_params = array('mobile', 'captcha', 'sms_id');
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
			// 动态设置待验证字段名及字段值
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|is_unique[user.mobile]');
			$this->form_validation->set_rules('sms_id', '短信ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('captcha', '短信验证码', 'trim|required|exact_length[6]|is_natural_no_zero');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

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
				$items = $this->basic_model->select($condition, NULL, FALSE, FALSE);
				if ( !empty($items) ):
					$this->result['status'] = 200;
					$this->result['content'] = '验证码有效';

				else:
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '验证码错误或已过期';

				endif;
			
			endif;
		} // end verify
	}

/* End of file Sms.php */
/* Location: ./application/controllers/Sms.php */
