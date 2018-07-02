<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Messages/MSS 即时消息类
	 *
	 * 即时类消息
     *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Messages extends CI_Controller
	{
		/* 主要相关表名 */
		public $table_name;

		/* 主要相关表的主键名*/
		public $id_name;

		public $app_type; // 应用类型

		/* 收信者信息 */
		public $receiver_type;
        public $user_id; // user_id
        public $biz_id; // biz_id
        public $stuff_id; // stuff_id

        public $limit = 10;

        public $time_min; // 最后获取时间，Unix时间戳

		public function __construct()
		{
			parent::__construct();

			// 向类属性赋值
			$this->table_name = 'message'; // 和这里……
			$this->id_name = 'message_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

            // 获取收信者参数
			$this->app_type = $this->receiver_type = $this->input->post_get('app_type');
			$this->user_id = $this->input->post_get('user_id');
            $this->biz_id = $this->input->post_get('biz_id');
            $this->stuff_id = $this->input->post_get('stuff_id');

            // 若未传入消息获取截止时间，默认为从3分钟前至今
            $this->time_min = empty($this->input->get_post('time_min'))? time() - 60 * 3: $this->input->get_post('time_min');

            header("Content-Type:text/event-stream;charset=utf-8");
            header('Cache-Control:no-cache');
		} // end __construct

		// 获取消息
		public function index()
		{
            // 间隔多少秒后继续运行
            $second_to_sleep = 5;

            while (TRUE)
            {
                // 获取待发送信息
                $messages = $this->get_messages();
                //var_dump($messages);

                // 标记下次获取时间为当前时间
                $this->time_min = time();

                foreach ($messages as $message):
                    // 发送内容
                    try {
                        $this->output($message);
                    } catch(Exception $e) {
                        print $e->getMessage();
                        exit();
                    }
                endforeach;

                // 稍作间隔以节省数据库连接数
                sleep($second_to_sleep);
            }
        } // end index

        /**
         * 以下为工具类方法
         */

        // 获取收信人为当前连接者的信息
        private function get_messages($user_id = NULL, $biz_id = NULL, $stuff_id = NULL)
        {
            // 通用条件
            $condition = array(
                'receiver_type' => $this->receiver_type,
                'time_delete' => 'NULL',
                'time_create >' => $this->time_min,
            );

            // 根据收信者类型添加限制条件
            if ($this->receiver_type === 'client'):
                $condition['user_id'] = $this->user_id;

            else:
                $condition['stuff_id'] = $this->stuff_id;

                // 若为发送给商家的信息，应限制商家ID
                if ($this->receiver_type === 'biz')
                    $condition['biz_id'] = $this->biz_id;
            endif;

            $order_by = NULL;

            $items = $this->basic_model->select($condition, $order_by);

            return $items;
        } // end get_messages

        // 输出待发送内容
        private function output($data)
        {
            echo "id:". $data['message_id']. "\n"; // 可选
            // echo "event:message". "\n"; // 可选，默认为message
            echo "retry:5000". "\n"; // 可选
            echo "data:". json_encode($data). "\n";
            echo "\n";

            @ob_flush();@flush();

            // 若上述语句无效，需禁用Nginx的buffering，即在nginx.conf文件中添加/替换配置项（若有则修改值），并重启Nginx。
            //proxy_buffering off;
            //fastcgi_keep_conn on;
        } // end output

        /**
         * 更换所用数据库
         */
        protected function switch_model($table_name, $id_name)
        {
            $this->db->reset_query(); // 重置查询
            $this->basic_model->table_name = $table_name;
            $this->basic_model->id_name = $id_name;
        } // end switch_model

        /**
         * 还原所用数据库
         */
        protected function reset_model()
        {
            $this->db->reset_query(); // 重置查询
            $this->basic_model->table_name = $this->table_name;
            $this->basic_model->id_name = $this->id_name;
        } // end reset_model

	} // end class Messages

/* End of file Messages.php */
/* Location: ./application/controllers/Messages.php */
