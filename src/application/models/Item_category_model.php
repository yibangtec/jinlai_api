<?php
	/**
	 * 平台商品分类模型类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Item_category_model extends CI_Model
	{
		/**
		 * 数据库表名
		 *
		 * @var string $table_name 表名
		 */
		public $table_name = 'item_category';

		/**
		 * 数据库主键名
		 *
		 * @var string $id_name 数据库主键名
		 */
		public $id_name = 'category_id';

		/**
		 * 初始化类
		 * @param void
		 */
        public function __construct()
        {
            // CI_Model类无构造函数，无需继承
        } // end __construct

        // 获取所有有上架商品的平台商品分类
        public function select_available($condition = NULL)
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

            $this->db->select('distinct(item.category_id), item_category.*');
            $this->db->join('item_category', 'item.category_id = item_category.category_id', 'left outer');
            $this->db->where('item.time_delete IS NULL');
            $this->db->order_by('item_category.category_id', 'ASC');

            $this->db->limit($limit, $offset);
            $query = $this->db->get('item');
            return $query->result_array();
        }

        public function getserivcecid(){
            $res = $this->db->query('select nature,category_id from ' . $this->table_name  . ' where nature=\'服务\'');
            $data = $res->result_array();
            $format = [];
            foreach ($data as $key => $value) {
                $value['category_id'] = '' . $value['category_id'];
                $format[$value['category_id']] = $value['category_id'];
            }
            return $format;

        }
	} // end class Item_category_model

/* End of file Item_category_model.php */
/* Location: ./application/models/Item_category_model.php */