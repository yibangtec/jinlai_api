<?php
	$this->form_validation->set_rules('url_logo', '店铺LOGO', 'trim|max_length[255]');
	$this->form_validation->set_rules('slogan', '宣传语', 'trim|max_length[30]');
	$this->form_validation->set_rules('description', '简介', 'trim|max_length[255]');
	$this->form_validation->set_rules('notification', '店铺公告', 'trim|max_length[255]');

	$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|required|min_length[10]|max_length[13]');
	$this->form_validation->set_rules('tel_protected_fiscal', '财务联系手机号', 'trim|exact_length[11]|is_natural');
	$this->form_validation->set_rules('tel_protected_order', '订单通知手机号', 'trim|exact_length[11]|is_natural');

	$this->form_validation->set_rules('url_image_product', '产品', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_produce', '工厂/产地', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_retail', '门店/柜台', 'trim|max_length[255]');