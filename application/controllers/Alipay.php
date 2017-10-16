<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Alipay/APY 支付宝类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Alipay extends CI_Controller
	{
		// 待签名字符串
		private $sign_string = '';
		
		// 支付参数字符串
		private $payment_string = '';
		
		public function __construct()
		{
	        parent::__construct();

			// 统计业务逻辑运行时间起点
			$this->benchmark->mark('start');

			// 若无任何通过POST方式传入的请求参数，提示并退出
			if ( empty($_POST) ):
				$this->result['status'] = 000;
				$this->result['content']['error']['message'] = '请求方式不正确';
				exit();
			endif;

			// 向类属性赋值
			$this->timestamp = time();
			$this->app_type = $this->input->post('app_type');
			$this->app_version = $this->input->post('app_version');
			$this->device_platform = $this->input->post('device_platform');
			$this->device_number = $this->input->post('device_number');

			// 签名有效性检查
			// 测试环境可跳过签名检查
			if ( ENVIRONMENT !== 'development' && $this->input->post('skip_sign') !== 'please' )
				$this->sign_check();
		} // end __construct

		// 析构时将待输出的内容作为json格式返回
		public function __destruct()
		{
			// 将请求参数一并返回以便调试
			$this->result['param']['get'] = $this->input->get();
			$this->result['param']['post'] = $this->input->post();

			// 返回服务器端时间信息
			$this->result['timestamp'] = time();
			$this->result['datetime'] = date('Y-m-d H:i:s');
			$this->result['timezone'] = date_default_timezone_get();

			// 统计业务逻辑运行时间终点
			$this->benchmark->mark('end');
			// 计算并输出业务逻辑运行时间（秒）
			$this->result['elapsed_time'] = $this->benchmark->elapsed_time('start', 'end');

			header("Content-type:application/json;charset=utf-8");
			$output_json = json_encode($this->result);
			echo $output_json;
		} // end __destruct

		// 待输出的内容
		public $result;

		/**
		 * APY3 获取支付宝支付所需参数
		 */
		public function create()
		{
			// 检查必要参数是否已传入
			$order_id = $this->input->post('order_id');
			if ( empty($order_id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 获取订单类型，默认为商品订单
			$type = $this->input->post('type')? $this->input->post('type'): 'order';

			/*
			// 根据订单类型和订单编号获取订单信息
			if ($type == 'daigou'):
				$this->load->model('daigou_model');
				$order = $this->daigou_model->select_by_id($order_id);
			else:
				$this->load->model('order_model');
				$order = $this->order_model->select_by_id($order_id);
			endif;
			*/

			// 获取订单信息备用
			$order_data = array(
				'body' => SITE_NAME. ($type === 'order'? '商品订单': '充值订单'),
				'total_fee' => '0.01',
			);

			// 请求地址
			$gateway_url = 'https://openapi.alipay.com/gateway.do';

			// 公共参数
			$params = array(
				'app_id' => ALIPAY_APP_ID,
				'method' => 'alipay.trade.app.pay', // 接口名称在具体请求中赋值，此处仅为示例
				'charset' => 'utf-8',
				//'format' => 'JSON',
				'sign_type' => 'RSA2',
				//'sign' => '', // 签名
				'timestamp' => date('Y-m-d H:i:s'),
				'version' => '1.0',
				'notify_url' => base_url('alipay/notify'),
				'biz_content' => '', // 请求参数的集合字符串，除公共参数外所有请求参数都通过该参数传递
			);

			// 参与签名的参数
			$out_trade_no = date('YmdHis').'_'. $type.'_'. $order_id; // 拼装订单号，64个字符以内
			$subject = $order_data['body']. ' 编号'. $order_id;
			$body = $order_data['body']. $out_trade_no;
			$request_params = array(
				'out_trade_no' => $out_trade_no,
				'total_amount' => $order_data['total_fee'],
				'subject' => $subject,
				'body' => $body,
				'product_code' => 'QUICK_MSECURITY_PAY', // 固定值
			);
			$params['biz_content'] = json_encode($request_params, JSON_UNESCAPED_UNICODE);

			// 生成签名并拼合不参与签名的参数到请求参数
			$this->sign_string_generate($params); // 生成待签名及支付参数字符串
			$sign = $this->sign_generate($params); // 生成签名
			$params['subject'] = $subject; // 订单名称
			$params['string_to_sign'] = $this->sign_string; // 待签名字符串
			$params['sign'] = $sign;
			$params['payment_string'] = $this->payment_string.'&sign='. urlencode($sign); // 含签名的所有参数字符串

			if ( !empty($params)):
				$this->result['status'] = 200;
				$this->result['content'] = $params;
			else:
				$this->result['status'] = 400;
				$this->result['content'] = '支付宝支付参数获取失败';
			endif;
		} // end create
		
		/**
		 * TODO APY5 订单通知
		 */
		public function notify()
		{
			echo '订单通知';

			$sign = $params['sign'];
			unset($params['sign']);

			$this->sign_verify($params, $sign);
		} // end notify

		// 生成待签名及支付字符串
		private function sign_string_generate($params)
		{
			$params = array_filter($params); // 清理空元素
			ksort($params); // 按数组键名升序排序

			foreach ($params as $key => $value):
				$this->sign_string .= '&'. $key. '='. $value;
				$this->payment_string .= '&'. $key. '='. urlencode($value);
			endforeach;

			// 去掉多余的“&”
			$this->sign_string = trim($this->sign_string, '&');
			$this->payment_string = trim($this->payment_string, '&');

			// 取消字符转义
			if (get_magic_quotes_gpc()):
				$this->sign_string = stripcslashes($this->sign_string);
			endif;
		} // end sign_string_generate

		/**
		 * 生成RSA2签名
		 */
		private function sign_generate($params)
		{
			$sign_string = $this->sign_string;

			$priKey = ALIPAY_KEY_PRIVATE;
			$res = "-----BEGIN RSA PRIVATE KEY-----\n".
				wordwrap($priKey, 64, "\n", true).
				"\n-----END RSA PRIVATE KEY-----";

			($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

		    openssl_sign($sign_string, $sign, $res, OPENSSL_ALGO_SHA256);

			// base64编码
		    $sign = base64_encode($sign);
		    return $sign;
		} // end sign_generate

		/**
		 * 验证RSA2签名
		 */
		private function sign_verify($params, $sign)
		{
			$sign_string = $this->sign_string_generate($params);

			$priKey = ALIPAY_KEY_PUBLIC;
			$res = "-----BEGIN PUBLIC KEY-----\n".
				wordwrap($priKey, 64, "\n", true).
				"\n-----END PUBLIC KEY-----";

			($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

			$result = (bool)openssl_verify($sign_string, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

			return $result;
		} // end sign_verify

	} // end class Alipay

/* End of file Alipay.php */
/* Location: ./application/controllers/Alipay.php */
