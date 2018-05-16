<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * 个推类
	 *
	 * 个推相关功能
     * http://docs.getui.com/getui/server/rest/push/
     * http://docs.getui.com/getui/server/rest/other_if/
     * http://docs.getui.com/getui/server/rest/template/
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright SSEC <www.ssectec.com>
	 */
	class Getui
	{
		// 测试应用参数
		/*private $app_id = 'JJneyXef8L7Isjn2ygHPu1';
		private $app_secret = '1TxOGflNhu9Lqcs84Nc8kA';
		private $app_key = 'OKbCT2GSPi5wzMbutkbYZ8';
		private $master_secret = 'Cdb0urkJF59vajqc9j9fD9';*/

        // 开发环境参数
		private $app_id = 'SazPPTAIDi7kw4PACjIbU3';
        private $app_secret = 'pZ9VmM2jnt9Y4ugTyJ1yr2';
        private $app_key = '0BxsRyoY479LL6pgvWw9f4';
        private $master_secret = 'qUiJZ4DrBr9kiWlrqgIQh2';

		public $auth_token = NULL; // 鉴权token

        public $cid = 'a24e50760b23f3f8dccf438e32ec18a9'; // 用户身份ID

		// （可选）原始CodeIgniter对象
		private $CI;

		// （可选）构造函数
		public function __construct()
		{
			// (可选)引用原始CodeIgniter对象
			$this->CI =& get_instance();
		} // end __construct
		
		// （可选）析构函数
		public function __destruct()
		{
		
		} // end __destruct

        /**
         * 鉴权
         *
         * 用户身份验证通过获得auth_token权限令牌，后面的请求都需要带上auth_token
         *
         * @return mixed
         */
		public function auth_sign()
		{
            // 获取档期时间毫秒数 秒数*1000
            $timestamp = time() * 1000;

            $content = array(
                'appkey' => $this->app_key,
                'timestamp' => $timestamp,
                'sign' => hash('sha256', $this->app_key.$timestamp.$this->master_secret, FALSE),
            );

            $url = 'https://restapi.getui.com/v1/'.$this->app_id.'/auth_sign';
            return $this->curl($url, json_encode($content));
		} // end auth_sign

        /**
         * TODO 单推
         *
         * 对使用App的某个用户，单独推送消息
         *
         * @param $cid
         * @return mixed
         */
        public function push_single($cid)
        {
            // 消息内容
            $message = array(

            );

            // 消息应用模板
            $notification = array(

            );

            $content = array(
                'requestid' => 'test_'.time(),

                'cid' => $cid,
                'message' => $message,
                'notification' => $notification,
            );

            $url = 'https://restapi.getui.com/v1/'.$this->app_id.'/push_single';
            return $this->curl($url, json_encode($content));
        } // end push_single

        /**
         * 群推
         *
         * 针对某个，根据筛选条件，将消息群发给符合条件客户群，或所有用户
         *
         * @param $message_to_send
         * @param string $type 推送类型，notification（通知，弹出通知栏）、transmission（透传，不弹出通知栏）
         * @return mixed
         */
        public function push_app($message_to_send, $type = 'notification')
        {
            // 标识推送类型
            $message_to_send['push_type'] = $type;

            // 消息内容
            $message = array(
                'appkey' => $this->app_key,
                //'is_offline' => FALSE,
                'msgtype' => $type,
            );

            // 通知消息模板
            $notification = array(
                'transmission_content' => json_encode($message_to_send),
                //'transmission_type' => FALSE,
                //'duration_begin': '2017-03-22 11:40:00', // 展示开始时间
                // 'duration_end': '2017-03-23 11:40:00', // 展示结束时间
                'style' => array(
                    'type' => 0,
                    'title' => "[$type]".$message_to_send['params']['title'],
                    'text' => $message_to_send['params']['excerpt'],
                    'logo' => 'logo_notification.png',
                    //'is_ring' => TRUE, // 客户端收到消息后响铃
                    //'is_vibrate' => TRUE, // 客户端收到消息后震动
                    //'is_clearable' => TRUE, // 通知是否可清除
                )
            );

            // 透传消息模板
            $transmission = array(
                'transmission_content' => json_encode($message_to_send),
                //'transmission_type' => FALSE,
                //'duration_begin': '2017-03-22 11:40:00', // 展示开始时间
                // 'duration_end': '2017-03-23 11:40:00', // 展示结束时间
            );

            // 推送给iOS设备的通知内容
            $push_info = array(
                'aps' => array(
                    'alert' => array(
                        'title' => "[$type]".$message_to_send['params']['title'],
                        'subtitle' => '子标题，例如聊天对象商家名、用户名等',
                        'body' => $message_to_send['params']['excerpt'],
                    ),
                    'autoBadge' => '+1',
                )
            );

            // 整体待发送内容
            $content = array(
                'requestid' => 'test_'.time(),

                'message' => $message,
                "$type" => ${$type},
                'push_info' => $push_info
            );
            $content_json = json_encode($content); // JSON格式
            $this->CI->result['content']['push_content'] = $content_json;
            if ($this->CI->input->post('test_mode') === 'on') var_dump($content_json);

            $url = 'https://restapi.getui.com/v1/'.$this->app_id.'/push_app';
            $result = $this->curl($url, $content_json);
            if ($this->CI->input->post('test_mode') === 'on') var_dump($result);

            return $result;
        } // end push_app

        /**
         * 获取推送结果
         *
         * 调用此接口查询推送数据，可查询消息有效可下发总数，消息回执总数和用户点击数等结果。
         *
         * @param $list_task
         * @return mixed
         */
        public function push_result($list_task)
        {
            $content = array(
                'taskIdList' => $list_task
            );

            $url = 'https://restapi.getui.com/v1/'.$this->app_id.'/push_result';
            $result = $this->curl($url, json_encode($content));
            if ($this->CI->input->post('test_mode') === 'on') var_dump($result);

            return $result;
        } // end push_result

        /**
         * 以下为工具方法
         */

        /**
         * 发送CURL请求
         *
         * @param $url
         * @param $params
         * @return mixed
         */
        protected function curl($url, $params)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);

            // 设置cURL参数，要求结果保存到字符串中还是输出到屏幕上。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');

            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

            $header = array(
                'Content-Type: application/json',
                'Content-Length: '. strlen($params),
                'authtoken: '. (empty($this->auth_token)? NULL: $this->auth_token),
            );
            //var_dump($header);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            // 运行cURL，请求API
            $result = curl_exec($curl);

            // 输出CURL请求头以便调试
            //var_dump(curl_getinfo($curl));

            // 关闭URL请求
            curl_close($curl);

            // 转换返回的json数据为数组格式并返回
            $result = json_decode($result, TRUE);

            return $result;
        } // end curl

	} // end Class

/* End of file Getui.php */
/* Location: ./application/libraries/Getui.php */