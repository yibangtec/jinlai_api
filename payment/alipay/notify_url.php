<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */

require_once('alipay.config.php');
require_once('lib/alipay_notify.class.php');

function curl_go($params, $url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);

    // 设置cURL参数，要求结果保存到字符串中还是输出到屏幕上。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
	curl_setopt($curl, CURLOPT_POST, count($params));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

    // 运行cURL，请求API
	$result = curl_exec($curl);
	// 关闭URL请求
    curl_close($curl);
	
	return json_decode($result, TRUE);
}

// 计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

// 写入日志文件验证结果
logResult('验证结果：'. $verify_result);

// 验证成功
if ($verify_result):
	
	// 商户订单号
	$out_trade_no = $_POST['out_trade_no'];
	// 商户订单名称
	$subject = $_POST['subject'];
	// 支付宝交易号
	$trade_no = $_POST['trade_no'];
	// 交易状态
	$trade_status = $_POST['trade_status'];
	// 交易金额（请求时对应的参数,原样通知回来。）
	$total_fee = $_POST['total_fee'];

    if ($_POST['trade_status'] == 'TRADE_FINISHED')
	{
		// 判断该笔订单是否在商户网站中已经做过处理
			// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			// 如果有做过处理，不执行商户的业务程序

		// 注意：
		// 退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
    }
    elseif ($_POST['trade_status'] == 'TRADE_SUCCESS')
	{
		// 付款完成后，支付宝系统发送该交易状态通知

		// RESTful API更新订单状态并发送短信通知
		@list($order_prefix, $type, $order_id) = split('_', $out_trade_no); // 分解出订单前缀、订单类型、订单号等
		$params = array(
			'token' => '7C4l7JLaM3Fq5biQurtmk6nFS', // 与TOKEN_PAY保持一致的密码
			'order_id' => $order_id, // 交易订单号
			'status' => 1, // 交易状态为成功
			'payment_type' => 1, // 付款方式为支付宝
			'payment_id' => $trade_no, // 支付宝流水号
			'total' => $total_fee // 交易金额
		);

		// 更新订单状态
		$url = 'https://www.guangchecheng.com/'.$type.'/status';
	   	$result = curl_go($params, $url);
		// 若更新不成功，终止运行程序
		if ($result['status'] != '200'):
			exit;
		endif;

		$token = '7C4l7JLaM3Fq5biQurtmk6nFS';
		$timestamp = time();
		$sign = sha1($token. $timestamp);
		// 向用户发送订单支付成功短信提醒
		$params = array(
			'timestamp' => $timestamp,
			'sign' => $sign,
			'order_type' => $type,
			'order_id' => $order_id,
			'type' => 2
		);
		// 发送短信
		$url = 'https://api.guangchecheng.com/sms/send';
	   	curl_go($params, $url);

		// 向系统管理员发送订单支付成功短信提醒
		$params['mobile'] = '18611608581'; // 徐钰龙手机号
		/* 发送短信 */
		$url = 'https://api.guangchecheng.com/sms/send';
	   	curl_go($params, $url);
    }
        
	echo 'success'; // 请不要修改或删除

//验证失败
else:
    echo 'fail';

    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
endif;
