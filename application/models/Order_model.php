<?php
	/**
	 * 商品订单模型类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
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
            // CI_Model类无构造函数，无需继承
        } // end __construct
		
		// 获取列表
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

            $this->db->limit($limit, $offset);

            // 默认可返回已删除项
            if ($allow_deleted === FALSE)
                $this->db->where("`time_delete` IS NULL");

			if ($return_ids === TRUE):
				$this->db->select($this->id_name);
			endif;

            $query = $this->db->get($this->table_name);

            // 可选择仅返回符合条件项的ID列表
            if ($return_ids === FALSE):
                return $query->result_array();

            else:
                // 多维数组转换为一维数组
                $ids = array();
                $result = $query->result_array();

                foreach ($result as $item):
                    $ids[] = $item[$this->id_name]; // 返回当前行的主键
                endforeach;

                unset($result); // 释放原结果数组以节省内存

                // 返回数组
                return $ids;

            endif;
		} // end select

        // 获取有所属商品在退款中（待处理、待退货、待退款）状态的订单
        public function select_refunding($condition = NULL)
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

            $this->db->select('distinct(order_items.order_id), order.*');
            $this->db->join('order', 'order_items.order_id = order.order_id', 'left outer');
            //$this->db->where('item.time_delete IS NULL');
            //$this->db->order_by('item_category.category_id', 'ASC');

            $this->db->limit($limit, $offset);
            $query = $this->db->get('order_items');
            return $query->result_array();
        }

        /**
         * 根据ID获取特定项，默认可返回已删除项
         *
         * @param int $id 需获取的行的ID
         * @param bool $allow_deleted 是否可返回被标注为删除状态的行；默认为TRUE
         * @return array 结果行（一维数组）
         */
        public function select_by_id($id, $allow_deleted = TRUE)
        {
            // 获取必要信息
            $this->db->select($this->table_name.'.*, (SELECT tel_public from biz WHERE biz.biz_id = order.biz_id) as tel_public');

            // 默认可返回已删除项
            if ($allow_deleted === FALSE) $this->db->where('time_delete', NULL);

            $this->db->where($this->table_name.'.'.$this->id_name, $id);

            $query = $this->db->get($this->table_name);
            return $query->row_array();
        } // end select_by_id

        public function update_status($where, $status){
            if (array_key_exists('order_id', $where)) {
                $query = $this->db->query("update order_items set status='" . $status . "'  where nature='服务' and order_id=" . $where['order_id']);
            } elseif (array_key_exists('record_id', $status)) {
                $query = $this->db->query("update order_items set status='" . $status . "'  where nature='服务' and order_id=" . $where['order_id']);
            } else {
                return 0;
            }
            return $query;
        }
	} // end class Order_model

/* End of file Order_model.php */
/* Location: ./application/models/Order_model.php */