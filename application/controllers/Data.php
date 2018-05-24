<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Data/DTA 数据统计类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Data extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'name',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'branch'; // 这里……
			$this->id_name = 'branch_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end __construct

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 筛选条件
			$condition = NULL;
			//$condition['name'] = 'value';

			// （可选）遍历筛选条件
			foreach ($this->names_to_sort as $sorter):
				if ( !empty($this->input->post_get($sorter)) ):
					// 对时间范围做限制
					if ($sorter === 'start_time'):
						$condition['time_create >='] = $this->input->post_get($sorter);
					elseif ($sorter === 'end_time'):
						$condition['time_create <='] = $this->input->post_get($sorter);
					else:
						$condition[$sorter] = $this->input->post_get($sorter);
					endif;

				endif;
			endforeach;

			// 获取列表；默认可获取已删除项
			$count = $this->basic_model->count($condition);

			if ($count !== FALSE):
				$this->result['status'] = 200;
				$this->result['content']['count'] = $count;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end count

		/**
		 * 1 列表/基本搜索
		 */
		public function index()
		{
			// 筛选条件
			$condition = NULL;

			// 排序条件
			$order_by = NULL;

			// 获取列表
            $table_to_index = array('data_pv_total', 'data_uv_total', 'data_uv_biz', 'data_order_total');
            foreach ($table_to_index as $index):
                $this->switch_model($index, 'record_id');
                if ($index === 'data_pv_total'):
                    // 每5秒钟统计一次总页面流量量
                    $this->db->select('ROUND(time_create/(5)) AS timekey, SUM(value) AS value');
                    $this->db->group_by('timekey', 'DESC');
                    $this->db->order_by('timekey', 'DESC');
                    $this->db->limit(20, 0);
                else:
                    $this->db->select('value');
                    $this->db->limit(10, 0);
                endif;
                $result = $this->basic_model->select($condition, $order_by);

                // 多维数组转换为一维数组
                $values = array();

                foreach ($result as $item):
                    $values[] = $item['value']; // 返回当前行的主键
                endforeach;
                $this->result['content'][$index] = $values;
            endforeach;

            $this->result['status'] = 200;
		} // end index

		/**
		 * 2 详情
		 */
		public function detail()
		{
			// 检查必要参数是否已传入
			$id = $this->input->post('id');
			if ( empty($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select_sum('value', 'value');
            $this->db->where('time_create BETWEEN (unix_timestamp()-5) and unix_timestamp()');
            $query = $this->db->get('data_pv_total');
            $item = $query->row_array();

			// 获取特定项；默认可获取已删除项
			//$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

        /**
         * 以下为工具类方法
         */

	} // end class Data

/* End of file Data.php */
/* Location: ./application/controllers/Data.php */
