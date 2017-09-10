<?php
	/**
	 * 商品订单类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 * @copyright BasicCodeIgniter <https://github.com/kamaslau/BasicCodeIgniter>
	 */
	class Order_model extends CI_Model
	{
		/**
		 * 数据库表名
		 *
		 * @var string $table_name 表名
		 */
		public $table_name = 'order';

		/**
		 * 数据库主键名
		 *
		 * @var string $id_name 数据库主键名
		 */
		public $id_name = 'order_id';

		/**
		 * 初始化类
		 * @param void
		 * @return void
		 */
		public function __construct()
		{
			parent::__construct();
		}
		
		// TODO 获取列表
		public function select($condition = NULL, $order_by = NULL, $return_ids = FALSE, $allow_deleted = FALSE)
		{
			$limit = $this->input->get_post('limit')? $this->input->get_post('limit'): NULL; // 需要从数据库获取的数据行数
			$offset = $this->input->get_post('offset')? $this->input->get_post('offset'): NULL; // 需要从数据库获取的数据起始行数（与$limit配合可用于分页等功能）

			// 拆分筛选条件（若有）
			if ($condition !== NULL):
				foreach ($condition as $name => $value):
					if ($value === 'NULL'):
						$this->db->where($this->table_name.'.'."$name IS NULL");

					elseif ($value === 'IS NOT NULL'):
						$this->db->where($this->table_name.'.'."$name IS NOT NULL");
					
					else:
						$this->db->where($this->table_name.'.'.$name, $value);
					endif;
				endforeach;
			endif;

			// 拆分排序条件（若有）
			if ($order_by !== NULL):
				foreach ($order_by as $column_name => $value):
					$this->db->order_by($column_name, $value);
				endforeach;
			// 若未指定排序条件，则默认按照ID倒序排列
			else:
				$this->db->order_by($this->id_name, 'DESC');
			endif;

			// 默认不返回已删除项
			if ($allow_deleted === FALSE) $this->db->where($this->table_name.'.time_delete', NULL);

			if ($return_ids === TRUE):
				$this->db->select($this->id_name);
			else:
				// 获取必要信息
				$this->db->select($this->table_name.'.*, biz.name as name, biz.url_logo as url_logo, biz.status as status');
				$this->db->join('biz', $this->table_name.'.biz_id = biz.biz_id', 'left outer');
			endif;

			$this->db->limit($limit, $offset);

			$query = $this->db->get($this->table_name);
			return $query->result_array();

			if ($return_ids === TRUE):
				// 多维数组转换为一维数组
				$ids = array();
				foreach ($results as $item):
					$ids[] = $item[$this->id_name]; // 返回当前行的主键
				endforeach;

				// 释放原结果数组以节省内存
				unset($results);

				// 返回数组
				return $ids;
			endif;
		} // end select

	} // end class Order_model

/* End of file Order_model.php */
/* Location: ./application/models/Order_model.php */