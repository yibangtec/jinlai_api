<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Tool 类
	 *
	 * 以API服务形式返回数据列表、详情、创建、单行编辑、单/多行编辑（删除、恢复）等功能提供了常见功能的示例代码
	 * CodeIgniter官方网站 https://www.codeigniter.com/user_guide/
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Tool extends CI_Controller
	{
		public function __construct()
		{
			parent::__construct();

			// 统计业务逻辑运行时间起点
			$this->benchmark->mark('start');
		}

		/**
		 * 析构时将待输出的内容以json格式返回
		 * 截止3.1.3为止，CI_Controller类无析构函数，所以无需继承相应方法
		 */
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
		}

		// 签名生成工具
		public function sign_generate()
		{
			// 设置需要参与签名的必要参数；由于本方法为测试用途，故timestamp字段可以不传入，由服务器端生成
			$params_required = array(
				'app_type',
				'app_version',
				'device_platform',
				'device_number',
				'timestamp',
				'random',
			);

			// 获取传入的参数们
			$params = $_POST;
			// 为便于测试，时间戳可由服务器生成
			if ( empty($params['timestamp']) ):
				$timestamp = time();
				$params['timestamp'] = &$timestamp;
			endif;

			// 检查必要参数是否已传入
			if ( array_intersect_key($params_required, array_keys($params)) !== $params_required ):
				$this->result['status'] = 400;
				$this->result['content']['error_code'] = '必要参数未全部传入';

			// 检查来自移动客户端的请求中，必要参数是否存在空值
			//elseif:

			else:
				// 对参与签名的参数进行排序
				ksort($params);

				// 对随机字符串进行SHA1计算
				$params['random'] = SHA1( $params['random'] );

				// 拼接字符串
				$param_string = '';
				foreach ($params as $key => $value)
					$param_string .= '&'. $key.'='.$value;
				$param_string .= '&key='. API_TOKEN;

				// 计算字符串SHA1值并转为大写
				$sign = strtoupper( SHA1($param_string) );

				// 输出生成的签名及相关测试内容
				$this->result['status'] = 200;
				$this->result['content']['params_input'] = &$_POST;
				if ( empty($_POST['timestamp']) ):
					$this->result['content']['params_added']['timestamp'] = &$timestamp;
				endif;
				$this->result['content']['params_added']['key'] = '安全起见不显示key值';
				$this->result['content']['sign_string'] = substr($param_string,0,-25).'安全起见不显示key值';
				$this->result['content']['sign'] = $sign;

			endif; //end 检查必要参数是否已传入
		} // end sign_generate
		
		// 导出数据库结构
		public function table_structure()
		{
			// 表名
			$table_name = $this->input->post('table_name');

			$this->db->select('COLUMN_NAME as name,COLUMN_TYPE as type,IS_NULLABLE as allow_null, COLUMN_DEFAULT as default,COLUMN_COMMENT as comment');
			$this->db->where('table_name', $table_name);
			$query = $this->db->get('information_schema.COLUMNS');

			$result = $query->result_array();
			
			if ( !empty($result) ):
				$this->result['status'] = 200;
				$this->result['content'] = array(
					'names' => '',
					'comments' => '',
				);
				foreach ($result as $column):
					$this->result['content']['names'] .= $column['name'].',';
					$this->result['content']['comments'] .= $column['comment'].',';
				endforeach;
			else:
				$this->result['status'] = 400;
				$this->result['content'] = '该表不存在或不含有任何字段';
			endif;
		} // end table_columns

	}

/* End of file Tool.php */
/* Location: ./application/controllers/Tool.php */
