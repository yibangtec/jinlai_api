<?php
	/**
	 * TODO 商品订单类
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
		
		// 根据订单ID获取用户资料
		public function get_order_user($id)
		{
			$this->db->where($this->table_name.'.order_id', $id);

			$this->db->select($this->table_name.'.*, user.user_id as user_id, user.mobile as user_mobile, user.lastname as user_lastname, user.gender as user_gender');
			$this->db->join('user', $this->table_name.'.user_id = user.user_id', 'left outer');
			
			$query = $this->db->get($this->table_name);
			return $query->row_array();
		}
	}

/* End of file Order_model.php */
/* Location: ./application/models/Order_model.php */