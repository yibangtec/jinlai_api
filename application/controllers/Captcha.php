<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Captcha/CAP 验证码类
	 *
	 * 验证码相关功能
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Captcha extends MY_Controller
	{
		// 验证码字符数量
		protected $length = 4; // 最多6个字符
		
		// 验证码有效期，及计算出的失效时间
		protected $seconds_before_expire = 180; // 默认3分钟
		protected $time_expire;
		
		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'captcha'; // 这里……
			$this->id_name = 'captcha_id';  // 还有这里，OK，这就可以了
			$this->names_to_return[] = 'captcha_id';

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * 3 验证码创建
		 */
		public function create()
		{
			// 可传入需要的验证码位数
			$length = $this->input->post('length')? $this->input->post('length'): $this->length;

			// 生成随机数字字符串
		    $captcha = '';
		    for ($i = 0; $i < $length; $i++)
		        $captcha .= rand(0, 9);

			// 计算失效时间
			$this->time_expire = time() + $this->seconds_before_expire;

			// 保存到数据库
			$data_to_create = array(
				'captcha' => $captcha,
				'time_expire' => $this->time_expire,

				'user_ip' => $this->input->post('ip')? $this->input->post('ip'): $this->input->ip_address(), // 对于不是通过服务器发来的请求，需要获取IP地址
			);

			$result = $this->basic_model->create($data_to_create, TRUE);

			$this->result['status'] = 200;
			$this->result['content']['captcha_id'] = $result;
			$this->result['content']['captcha'] = $captcha;
			$this->result['content']['time_expire'] = $this->time_expire;
		} // end create

		/**
		 * 2 验证验证码有效性
		 *
		 * @params $captcha 验证码
		 * @params $sms_id 短信ID
		 * @params $ip_address 用户IP
		 */
		public function verify()
		{
			// 检查必要参数是否已传入
			$required_params = array('captcha', 'captcha_id');
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 筛选条件
			$condition = array(
				'captcha_id' => $captcha_id,
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
		} // end verify

        /**
         * 以下为工具类方法
         */

	} // end class Captcha

/* End of file Captcha.php */
/* Location: ./application/controllers/Captcha.php */
