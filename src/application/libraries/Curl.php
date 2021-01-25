<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');
	/**
	* Curl 类
	*
	* @version 1.0.0
	* @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	* @copyright ICBG <www.bingshankeji.com>
	*/
	class Curl
	{
		/**
		* 执行CURL
		*
		* @param string $url 待请求的URL
		* @param string $method 待发送的CURL请求类型；默认为get，可设为'post'
		* @param array $params 待发送的CURL请求参数数组，当且以POST方式发送的时候需传入此数组
		* @param string $return 需返回的数据格式；默认为数组格式，可传入'object'以设置为以对象格式返回
		* @return object|array 返回的CURL请求结果
		*/
		public function go($url, $params = NULL, $return = 'array', $method = 'post')
		{
		    $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $url);
		    // 设置cURL参数，要求结果保存到字符串中还是输出到屏幕上。
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
			
			// 需要通过POST方式发送的数据
			if ($method === 'post'):
                // 引用原始CodeIgniter对象
                $this->CI =& get_instance();
                // 发送当前应用类型
			    $params['app_type'] = $this->CI->app_type;
			    // 若未传入商家ID，则发送当前商家ID（若有）
                if ( ! isset($params['biz_id']) && isset($this->CI->session->biz_id))
                    $params['biz_id'] = $this->CI->session->biz_id;
				curl_setopt($curl, CURLOPT_POST, count($params));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			endif;
			
		    // 运行cURL，请求API
			$result = curl_exec($curl);
			
			// 输出CURL请求头以便调试
			//var_dump(curl_getinfo($curl));
			// 关闭URL请求
		    curl_close($curl);
			// 转换返回的json数据为相应格式并返回
			if ($return === 'object'):
				$result = json_decode($result);
			elseif ($return === 'array'):
				$result = json_decode($result, TRUE);
			endif;
			return $result;
		} // end go
		
	} // end class Curl
	
/* End of file Curl.php */
/* Location: ./application/libraries/Curl.php */