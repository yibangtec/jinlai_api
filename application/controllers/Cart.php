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
			$this->names_to_return[] = 'user_id'; // 还有这里，OK，这就可以了

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
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			else:
				// 解码为数组
				$this->cart_decode($item['cart_string']);

				$this->result['status'] = 200;
				$this->result['content'] = $this->order_data;

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
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end sync_down

		/**
		 * 5 单项修改/上传
		 */
		public function sync_up()
		{
			// 检查必要参数是否已传入
			$required_params = array('id', 'name', 'value',);
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
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
			$this->form_validation->set_rules('cart_string', '购物车内容', 'trim|required');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = trim($value, ',');

				// 获取ID
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '同步成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '同步失败';

				endif;
			endif;
		} // end sync_up

	} // end class Cart

/* End of file Cart.php */
/* Location: ./application/controllers/Cart.php */
