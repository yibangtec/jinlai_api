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

            // 调试信息输出开关
            //$this->output->enable_profiler(TRUE);
		} // end __construct

		/**
		 * 截止3.1.7为止，CI_Controller类无析构函数，所以无需继承相应方法
		 */
		public function __destruct()
		{

		} // end __destruct

		// 执行所有计划任务
		public function index()
		{
			$this->daily();
		    $this->hour();
            $this->minute();
		} // end index

        /**
         * 每自然日任务
         */
        public function daily()
        {
            // 每天更新所有实物类商品总销量
            $this->renew_sold_overall();
        } // end daily

		/**
		 * 每小时任务
         */
		public function hour()
		{
			// 每3小时更新所有实物类商品月销量
            if (date('H')%3 === 0)
                $this->renew_sold_monthly();

			// 发送短信
            $this->sms_mobile = '17664073966';
            $this->sms_content = '现在时间 '. date('Y-m-d H:i:s');
            if (date('H') === '18')
                @$this->sms_send();

		} // end hour

        /**
         * 每分钟任务
         */
        public function minute()
        {
            // 每5分钟更新所有实物类商品日销量
            if (date('i')%5 === 0)
                $this->renew_sold_daily();

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
         * 获取信息列表
         *
         * @param string $table_name 信息所属表名
         * @param string $table_id 信息所属表ID
         * @param array $condition 筛选条件
         * @param boolean $ids_only 是否仅需返回CSV格式的主键ID
         * @return mixed
         */
        protected function get_items($table_name = 'item', $table_id = 'item_id', $condition = array(), $ids_only = FALSE)
        {
            // 初始化数据表
            $this->switch_model($table_name, $table_id);

            // 判断是否仅需返回主键ID
            if ($ids_only === TRUE) $this->db->select($table_id); // 仅获取ID即可

            return $this->basic_model->select($condition);
        } // end get_items

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

        /**
         * 更新实物类商品总销量
         *
         * 即item.sold_overall字段值
         */
        protected function renew_sold_overall()
        {
            // 获取所有商品
            $condition = array(
                //'status' => '正常', // TODO 仅处理status为正常的商品
            );
            $items = $this->get_items('item', 'item_id', $condition, TRUE);

            // 更新所有商品总销量
            if ( !empty($items) ):
                foreach ($items as $item):
                    // 获取总销量
                    $sold_overall = $this->count_sold($item['item_id']);

                    // 更新总销量
                    $this->switch_model('item', 'item_id');
                    $this->update_record($item['item_id'], 'sold_overall', $sold_overall);
                endforeach;
            endif;
        } // end renew_sold_overall

        /**
         * 更新实物类商品月销量
         *
         * 即item.sold_monthly字段值
         */
        public function renew_sold_monthly()
        {
            // 统计起始时间
            $period_start = time() - 2678400; // 31个自然日，即60*60*24*31秒之前

            // 获取所有商品
            $condition = array(
                //'status' => '正常', // TODO 仅处理status为正常的商品
            );
            $items = $this->get_items('item', 'item_id', $condition, TRUE);

            // 更新所有商品总销量
            if ( !empty($items) ):
                foreach ($items as $item):
                    // 获取总销量
                    $sold_overall = $this->count_sold($item['item_id'], 'item', $period_start);

                    // 更新总销量
                    $this->switch_model('item', 'item_id');
                    $this->update_record($item['item_id'], 'sold_monthly', $sold_overall);
                endforeach;
            endif;
        } // end renew_sold_monthly

        /**
         * 更新实物类商品日销量
         *
         * 即item.sold_daily字段值
         */
        public function renew_sold_daily()
        {
            // 统计起始时间
            $period_start = time() - 86400; // 1个自然日，即60*60*24秒之前

            // 获取所有商品
            $condition = array(
                //'status' => '正常', // TODO 仅处理status为正常的商品
            );
            $items = $this->get_items('item', 'item_id', $condition, TRUE);

            // 更新所有商品总销量
            if ( !empty($items) ):
                foreach ($items as $item):
                    // 获取总销量
                    $sold_overall = $this->count_sold($item['item_id'], 'item', $period_start);

                    // 更新总销量
                    $this->switch_model('item', 'item_id');
                    $this->update_record($item['item_id'], 'sold_daily', $sold_overall);
                endforeach;
            endif;
        } // end renew_sold_daily

        /**
         * 统计特定商品/规格总下单量
         *
         * 只要被下单即纳入统计
         *
         * @param int/string $id 待统计项ID
         * @param string $stuff_to_count 需要统计的数据类型，默认为商品item，可选规格sku
         * @param string $period_start 统计起始时间点，若未传入则不限制；UNIX时间戳
         * @param string $period_end 统计截止时间点，若未传入则不限制；UNIX时间戳
         * @return int
         */
        private function count_sold($id, $stuff_to_count = 'item', $period_start = NULL, $period_end = NULL)
        {
            $condition = array(
                $stuff_to_count.'_id' => $id,
            );

            // 若传入了统计起始时间点
            if ( ! empty($period_start) )
                $condition['time_create >'] = $period_start;

            // 若传入了统计截止时间点
            if ( ! empty($period_end) )
                $condition['time_create <='] = $period_end;

            // 切换数据库至订单商品信息表
            $this->switch_model('order_items', 'record_id');

            // 仅获取返回的统计数量
            $this->db->select('SUM(count) as count');
            $result = $this->basic_model->match($condition);

            return empty($result['count'])? 0: $result['count'];
        } // end count_sold

        /**
         * 更新单行信息
         *
         * 表名、主键名使用相关类属性，因此一般与switch_model方法结合使用
         *
         * @param int/string $id
         * @param string $name
         * @param string $value
         */
        private function update_record($id, $name, $value)
        {
            $data_to_edit = array(
                $name => $value,
            );

            $this->basic_model->edit($id, $data_to_edit);
        } // end update_record

	} // end class Schedule

/* End of file Schedule.php */
/* Location: ./application/controllers/Schedule.php */
