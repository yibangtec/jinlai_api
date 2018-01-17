<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Schedule/SCD 计划任务类
	 *
	 * 计划任务
     *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Schedule extends CI_Controller
	{
		/* 主要相关表名 */
		public $table_name;

		/* 主要相关表的主键名*/
		public $id_name;
		
		// 短信后缀签名
		protected $suffix = '【进来商城】';
		// 接收短信的手机号
		protected $sms_mobile;
		// 批量接收短信的手机号，CSV格式
		protected $mobile_list;
		// 批量发送短信的预订时间
		protected $time = NULL;
		// 短信内容
		protected $sms_content;

		public function __construct()
		{
			parent::__construct();

			// 向类属性赋值
			$this->table_name = 'item'; // 和这里……
			$this->id_name = 'item_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end __construct

		/**
		 * 截止3.1.3为止，CI_Controller类无析构函数，所以无需继承相应方法
		 */
		public function __destruct()
		{
			// 调试信息输出开关
			//$this->output->enable_profiler(TRUE);
		} // end __destruct

		// 路由
		public function index()
		{
			$this->hour();
            $this->minute();
		} // end index

		/**
		 * 每小时任务
		 *
		 * 测试发送报时短信
         */
		public function hour()
		{
			$this->sms_mobile = '17664073966';
			$this->sms_content = '现在时间 '. date('Y-m-d H:i:s');

			// 发送短信
            //@$this->sms_send();

		} // end hour

        /**
         * 每分钟任务
         *
         * 测试发送报时短信
         */
        public function minute()
        {
            $this->sms_mobile = '17664073966';
            $this->sms_content = '计划任务 '. $this->router->method. ' 已于 '. date('Y-m-d H:i:s'). ' 执行';

            // 发送短信
            //@$this->sms_send();
        } // end minute

        /**
         * 以下为工具方法
         */

        // 更换所用数据库
        protected function switch_model($table_name, $id_name)
        {
            $this->db->reset_query(); // 重置查询
            $this->basic_model->table_name = $table_name;
            $this->basic_model->id_name = $id_name;
        } // end switch_model

        /**
         * 发送短信
         */
        protected function sms_send()
        {
            // 为短信内容添加后缀签名
            $this->sms_content .= '【'. SITE_NAME. '】';

            $this->load->library('luosimao');
            @$result = $this->luosimao->send($this->sms_mobile, $this->sms_content);
        } // end sms_send

	} // end class Schedule

/* End of file Schedule.php */
/* Location: ./application/controllers/Schedule.php */
