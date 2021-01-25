<?php
	/**
	 * 退款/售后模型类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Refund_model extends CI_Model
	{
		/**
		 * 数据库表名
		 *
		 * @var string $table_name 表名
		 */
		public $table_name = 'refund';

		/**
		 * 数据库主键名
		 *
		 * @var string $id_name 数据库主键名
		 */
		public $id_name = 'refund_id';

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
		public function select($condition = NULL, $order_by = NULL, $return_ids = FALSE)
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

            // 获取必要信息
            $this->db->select($this->table_name.'.*, biz.brief_name as brief_name, biz.url_logo as url_logo');
            $this->db->join('biz', $this->table_name.'.biz_id = biz.biz_id', 'left outer');

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

        /**
         * 根据ID获取特定项，默认可返回已删除项
         *
         * @param int $id 需获取的行的ID
         * @param int $record_id 需获取的数据的record_id
         * @param bool $allow_deleted 是否可返回被标注为删除状态的行；默认为TRUE
         * @return array 结果行
         */
        public function select_by_id($id = NULL, $record_id = NULL, $allow_deleted = TRUE)
        {
            // 获取退款信息及相关商家信息
            $this->db->select($this->table_name.'.*, biz.brief_name as brief_name, biz.url_logo as url_logo, biz.tel_public as tel_public');
            $this->db->join('biz', $this->table_name.'.biz_id = biz.biz_id', 'left outer');

            // 默认可返回已删除项
            if ($allow_deleted === FALSE) $this->db->where('time_delete', NULL);

            // 优先根据退款ID获取数据
            if ( $id !== NULL):
                $this->db->where($this->id_name, $id);
            else:
                $this->db->where($this->table_name.'.record_id', $record_id);
            endif;

            $query = $this->db->get($this->table_name);
            return $query->row_array();
        } // end select_by_id

	} // end class Refund_model

/* End of file Refund_model.php */
/* Location: ./application/models/Refund_model.php */