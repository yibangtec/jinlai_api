<?php
	defined('BASEPATH') OR exit('No direct script access allowed');

	/**
	* Verify类
	*
	* 根据本地生成的签名检查接收到的请求签名是否合法
	*
	* @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	* @copyright ICBG <www.bingshankeji.com>
	*/
	class Verify
	{
		// 本地TOKEN
		private $local_token = API_TOKEN;
		
		// 本地签名
		private $local_sign;
		
		// 验证请求有效性
		public function go($timestamp, $sign)
		{
			$expiration = time() - 59; // 签名仅60秒内有效

			// 若缺少签名相关参数，签名已过期，或签名不正确等，则返回FALSE
			if ( empty($timestamp) || empty($sign) ):
				return FALSE;

			// 根据时间戳检查签名是否已过期
			elseif ($timestamp < $expiration):
				return FALSE;
			
			// 使用时间戳生成本地签名以供比对
			else:
				$local_sign = $this->local_sign($timestamp);
				if ($sign !== $local_sign):
					return FALSE;
				else:
					return TRUE;
				endif;

			endif;
		}

		// 根据参数生成签名
		public function local_sign($timestamp)
		{
			$this->local_sign = sha1($this->local_token. $timestamp);
			return $this->local_sign;
		}
	}
	
/* End of file Verify.php */
/* Location: ./application/libraries/Verify.php */