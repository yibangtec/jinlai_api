<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Cart/CRT 购物车类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Cart extends MY_Controller
	{
		// 订单信息（订单创建）
		protected $order_data = array();

		// 订单相关商品信息（订单创建）
		protected $order_items = array();

		// 订单收货地址信息（订单创建）
		protected $order_address = array();

		public function __construct()
		{
			parent::__construct();

			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('client'); // 客户端类型
			$this->client_check($type_allowed);

			// 设置主要数据库信息
			$this->table_name = 'user'; // 这里……
			$this->id_name = 'user_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * 1 列表
		 *
		 * 特定用户的购物车内容列表
		 *
		 * @param int/string $user_id 用户ID
		 */
		public function index()
		{
			// 检查必要参数是否已传入
			$id = $this->input->post('user_id');
			if ( empty($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select('cart_string');

			// 获取用户购物车内容
			$item = $this->basic_model->select_by_id($id);
			if ( empty($item) ):
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '购物车是空的';

			else:
				// 解析购物车字符串
				$this->cart_decode($item['cart_string']);

				$this->result['status'] = 200;
				$this->result['content']['order_items'] = $this->order_data;

			endif;
		} // end index

		/**
		 * 2 详情/下载
		 */
		public function sync_down()
		{
			// 检查必要参数是否已传入
			$id = $this->input->post('id');
			if ( empty($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 限制可返回的字段
			$this->db->select('cart_string');

			// 获取特定项；默认可获取已删除项
			$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '购物车是空的';

			endif;
		} // end sync_down

		/**
		 * 5 单项修改/上传
		 */
		public function sync_up()
		{	
			// 检查必要参数是否已传入
			$required_params = array('id', 'name', 'value');
			foreach ($required_params as $param):
				${$param} = trim($this->input->post($param));
				if ( $param !== 'value' && empty( ${$param} ) ): // value 可以为空；必要字段会在字段验证中另行检查
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
			$this->form_validation->set_rules('cart_string', '购物车内容', 'trim');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:

				if( empty($this->input->post('value'))) {
					$result = $this->basic_model->edit($id, ['cart_string'=>'']);
					$this->result['status'] = 200;
					$this->result['content']['message'] = '同步成功';
					exit;
				}
				$old_cart = $this->basic_model->select_by_id($id, FALSE);
				
				if (!array_key_exists('cart_string', $old_cart)) {
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '同步失败';
					exit;
				}
				$data_to_edit['operator_id'] = $id;
				$data_to_edit[$name] = trim($value, ',');

				//检测
				$all_item = explode(',', $data_to_edit['cart_string']);
				foreach ($all_item as $key => $value) {
					//如果某条信息不变，跳过
					//改变的 检测
					if (strpos('-' . $old_cart['cart_string'], $value) === FALSE) {
						$item = explode('|', $value);
						if (empty($value) || empty($item)) {
							unset($all_item[$key]);
							continue;
						}
						$this->switch_model('item', 'item_id');

						$pid = $item[1];
						//item2为sku的ID
						$item[2] = intval($item[2]); 
						if ($item[2] != 0) {
							$pid = $item[2];
							$this->switch_model('sku', 'sku_id');
						}

						//产品的库存
						$saved_pdt = $this->basic_model->select_by_id($pid, FALSE);
						
						if (is_array($saved_pdt) && $saved_pdt['stocks'] >= intval($item[3])) {
							$all_item[$key] = $saved_pdt['biz_id'] . '|' . $saved_pdt['item_id'] . '|' . $item[2] . '|' . $item[3];
						} else {
							$this->result['status'] = 434;
							$this->result['content']['error']['message'] = '库存不足！';
							exit;
						}

						#检查最大限购数量，如果是减少购物车，则不进行验证
						$op_type = $this->input->post['op_type'];
						if( $op_type != 'reduce' ){
							$res = $this->db->select('quantity_max')->where(['item_id'=>$item[1]])->get('item')->row_array();
							$quantity_max = $res['quantity_max'];

							#检测用户购物车
							$item = explode('|', $value);

							$current_cart_num = $item[3];

							if( $current_cart_num > $quantity_max ){
								$this->result['status'] = 434;
								$this->result['content']['error']['message'] = '超出限购数量，每人限购'.$quantity_max.'件';
								exit;				
							}
						}
					}
				}
				$this->switch_model('user', 'user_id');
				$data_to_edit['cart_string'] = implode(',', $all_item);
				$result = $this->basic_model->edit($id, $data_to_edit);

				// 需要编辑的数据
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '同步成功';
				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '同步失败';
				endif;
			endif;
		} // end sync_up

        /**
         * 6 解析
         *
         * 解析出购物车内容
         *
         * @param int/string $cart_string 购物车字符串
         */
        public function parse($cart_string = NULL)
        {
            // 检查必要参数是否已传入
            $cart_string = empty($cart_string)? $this->input->post('cart_string'): $cart_string;
            if ( empty($cart_string) ):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未传入';
                exit();
            endif;

            // 解析购物车字符串
            $item = $this->cart_decode($cart_string);
            if ($item !== FALSE):
                $this->result['status'] = 200;
                $this->result['content'] = $item;

            else:
                $this->result['status'] = 414;
                $this->result['content']['error']['message'] = '购物车是空的';

            endif;
        } // end parse

        /**
         * 以下为工具类方法
         */

	} // end class Cart

/* End of file Cart.php */
/* Location: ./application/controllers/Cart.php */
