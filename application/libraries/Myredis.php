<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

   /**
	* Redis
	*
	* @version 1.0.0
	* @author HuangXin 2018-06-26 13:37:02
	*/
	class Myredis 
	{	
		protected static $_default_config = array(
			'host' => '127.0.0.1',
			'password' => NULL,
			'port' => 6379,
			'timeout' => 0,
			'database' => 0
		);
		
	   /**
		* 构建函数
		* @param $argv 
		* @return $data
		*/
		public function __construct($argv = '')
		{
			if ( ! $this->is_supported())
			{
				log_message('error', 'Cache: Failed to create Redis object; extension not loaded?');
				return;
			}

			$CI =& get_instance();

			if ($CI->config->load('redis', TRUE, TRUE))
			{	
				$config = array_merge(self::$_default_config, $CI->config->item('redis'));
			}
			else
			{
				$config = self::$_default_config;
			}

			$this->_redis = new Redis();

			try
			{
				if ( ! $this->_redis->connect($config['host'], ($config['host'][0] === '/' ? 0 : $config['port']), $config['timeout']))
				{
					log_message('error', 'Cache: Redis connection failed. Check your configuration.');
				}

				if (isset($config['password']) && ! $this->_redis->auth($config['password']))
				{
					log_message('error', 'Cache: Redis authentication failed.');
				}

				if (isset($config['database']) && $config['database'] > 0 && ! $this->_redis->select($config['database']))
				{
					log_message('error', 'Cache: Redis select database failed.');
				}
			}
			catch (RedisException $e)
			{
				log_message('error', 'Cache: Redis connection refused ('.$e->getMessage().')');
			}
		}

	   /**
		* 返回redis实例
		*
		* @return $data
		*/
		public function getinstance(){
			return $this->_redis;
		}

	   /**
		* Check if Redis driver is supported
		* @return	bool
		*/
		public function is_supported()
		{
			return extension_loaded('redis');
		}

	   /**
		* Class destructor
		* Closes the connection to Redis if present.
		* @return	void
		*/
		public function __destruct()
		{
			if ($this->_redis)
			{
				$this->_redis->close();
			}
		}

	   /**
		* set Key-value with expiretime
		* 
		* @return bool
		*/
		public function set($key, $value, $livetime = 0){
			if (!$this->_redis->set($key, $value)) :
				return FALSE;
			elseif ($livetime > 0) :
				$this->_redis->setTimeout($key, intval($livetime));
			endif;
			return TRUE;
		}

	   /**
		* get value with key
		* 
		* @return string
		*/
		public function get($key){
			return $this->_redis->get($key);
		}

	   /**
		* 设置bit位 1kb可以有1024*8个计数
		* @return bool
		*/
		public function give($key, $offset, $wether)
		{
			return $this->_redis->setbit($key, intval($offset), $wether ? 1 : 0);
		}

	   /**
		* 获取bit位
		* @return 1|0
		*/
		public function have($key, $offset)
		{
			return $this->_redis->getbit($key, intval($offset));
		}

	   /**
		* 设置字典
		* @return bool
		*/
		public function savehash($key, $params, $livetime = 0)
		{
			if (!$this->_redis->hmset($key, $params)) :
				return FALSE;
			elseif ($livetime > 0) :
				$this->_redis->setTimeout($key, intval($livetime));
			endif;
			return TRUE;
		}


	   /**
		* 获取字典
		* type : keys, vals, getall, mget, get ,len,
		* @return mixed
		*/
		public function gethash($key, $type, $field  = '')
		{
			$hash_func_map = ['len'=>'','keys'=>'', 'vals'=>'', 'getall'=>'', 'get'=>$field, 'mget'=>$field];

			if (array_key_exists($type, $hash_func_map)) :
				$func = 'h' . $type;
				if ($hash_func_map[$type]) :
                    return $this->_redis->$func($key, $hash_func_map[$type]);
				else :
					return $this->_redis->$func($key);
				endif;
			endif;
			return FALSE;
		}	

	   /**
		* 删除键
		* key
		* @return bool
		*/
		public function delete($key){
			return $this->_redis->delete($key);
		}

	   /**
		* 给bitmap类型数据 设置假数据
		* @param size KB 1KB = 1024*8
		* @param wether 1 | 0
		* @return bool
		*/
		public function placeholder($key, $size, $wether){
			$size = $size * 1024 * 8;
			while ( $size > 0 ) {
				$size--;
				$this->give($key, $size, $wether);
			}  
			return $this->_redis->bitcount($key);
		}



		//list,sorted set to be continued

	} //endmyredisclass