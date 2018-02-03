<?php
    /**
     * 优惠券模型类
     *
     * @version 1.0.0
     * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
     * @copyright ICBG <www.bingshankeji.com>
     */
    class Coupon_model extends CI_Model
    {
        /**
         * 数据库表名
         *
         * @var string $table_name 表名
         */
        public $table_name = 'coupon';

        /**
         * 数据库主键名
         *
         * @var string $id_name 数据库主键名
         */
        public $id_name = 'coupon_id';

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
                $this->db->select($this->table_name.'.*, biz.brief_name as brief_name');
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
         * 根据ID获取特定项，默认可返回已删除项
         *
         * @param int $id 需获取的行的ID
         * @param bool $allow_deleted 是否可返回被标注为删除状态的行；默认为TRUE
         * @return array 结果行（一维数组）
         */
        public function select_by_id($id, $allow_deleted = TRUE)
        {
            // 获取必要信息
            $this->db->select($this->table_name.'.*, biz.brief_name as brief_name');
            $this->db->join('biz', $this->table_name.'.biz_id = biz.biz_id', 'left outer');

            // 默认可返回已删除项
            if ($allow_deleted === FALSE) $this->db->where('time_delete', NULL);

            $this->db->where($this->table_name.'.'.$this->id_name, $id);

            $query = $this->db->get($this->table_name);
            return $query->row_array();
        } // end select_by_id

        /**
         * 获取面值最高的有效优惠券
         *
         * @param string/int $user_id
         * @param string/int $biz_id
         * @param string $subtotal
         */
        public function get_max_valid($user_id, $biz_id, $subtotal)
        {
            $condition = array(
                'status' => '正常',
                'time_delete' => 'NULL', // 未被删除
                'order_id' => 'NULL', // 未被使用
                'user_id' => $user_id,
                'biz_id' => $biz_id,
                'amount <' => $subtotal, // 面值低于订单小计
                'min_subtotal <' => $subtotal, // 起用金额低于订单小计
                'time_end >' => time(),
            );
            foreach ($condition as $name => $value):
                if ($value === 'NULL'):
                    $this->db->where($this->table_name.'.'."$name IS NULL");

                elseif ($value === 'IS NOT NULL'):
                    $this->db->where($this->table_name.'.'."$name IS NOT NULL");

                else:
                    $this->db->where($this->table_name.'.'.$name, $value);
                endif;
            endforeach;

            // 未指定起用日期，或未超过起用日期
            $this->db->group_start()
                    ->where('time_start IS NULL')
                    ->or_where('time_start <', time())
                ->group_end();

            $this->db->order_by('amount', 'DESC');

            $query = $this->db->get($this->table_name);
            return $query->row_array();
        } // end get_max_valid

    } // end class Coupon_model

/* End of file Coupon_model.php */
/* Location: ./application/models/Coupon_model.php */