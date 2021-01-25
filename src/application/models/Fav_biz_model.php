<?php
	/**
	 * 商家关注模型类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Fav_biz_model extends CI_Model
	{
		/**
		 * 数据库表名
		 *
		 * @var string $table_name 表名
		 */
		public $table_name = 'fav_biz';

		/**
		 * 数据库主键名
		 *
		 * @var string $id_name 数据库主键名
		 */
		public $id_name = 'record_id';

		/**
		 * 初始化类
		 * @param void
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
                $this->db->where($this->table_name.".`time_delete` IS NULL");

			if ($return_ids === TRUE):
				$this->db->select($this->id_name);
			else:
				// 获取必要信息
				$this->db->select($this->table_name.'.*, biz.name as name, biz.brief_name as brief_name, biz.url_logo as url_logo, biz.status as status');
				$this->db->join('biz', $this->table_name.'.biz_id = biz.biz_id', 'left outer');
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

        /**
         * 修改（一般用于FVI5）
         *
         * @param int $user_id 待修改项所属用户ID
         * @param int $biz_id 待修改项所属商家ID
         * @param array $data 待修改数据
         * @param bool $return_rows 若修改成功，是否返回被编辑的行数量；默认不返回
         * @return int|bool 修改结果
         */
        public function edit($user_id, $biz_id, $data, $return_rows = FALSE)
        {
            // 尝试更新
            $this->db->where('user_id', $user_id);
            $this->db->where('biz_id', $biz_id);
            $update_result = $this->db->update($this->table_name, $data);

            // 直接返回结果，或返回编辑过的行数量
            if ($return_rows === TRUE && $update_result === TRUE):
                $this->db->affected_rows();
            else:
                return $update_result;
            endif;
        } // end edit

	} // end class Fav_biz_model

/* End of file Fav_biz_model.php */
/* Location: ./application/models/Fav_biz_model.php */