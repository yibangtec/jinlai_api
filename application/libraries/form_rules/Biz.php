<?php
	$this->form_validation->set_rules('url_logo', 'LOGO', 'trim|max_length[255]');
	$this->form_validation->set_rules('slogan', '说明', 'trim|max_length[20]');
	$this->form_validation->set_rules('description', '简介', 'trim|max_length[200]');
	$this->form_validation->set_rules('notification', '公告', 'trim|max_length[100]');
	$this->form_validation->set_rules('url_web', '官方网站', 'trim|max_length[255]|valid_url');
	$this->form_validation->set_rules('url_weibo', '官方微博', 'trim|max_length[255]|valid_url');
	$this->form_validation->set_rules('url_wechat', '微信二维码', 'trim|max_length[255]');

	$this->form_validation->set_rules('tel_public', '消费者联系电话', 'trim|required|min_length[10]|max_length[13]');
	$this->form_validation->set_rules('tel_protected_fiscal', '财务联系手机号', 'trim|exact_length[11]|is_natural');
	$this->form_validation->set_rules('tel_protected_order', '订单通知手机号', 'trim|exact_length[11]|is_natural');

	$this->form_validation->set_rules('code_license', '统一社会信用代码', 'trim|required|exact_length[18]');
	$this->form_validation->set_rules('code_ssn_owner', '法人身份证号', 'trim|required|exact_length[18]');
	$this->form_validation->set_rules('code_ssn_auth', '经办人身份证号', 'trim|exact_length[18]');

	$this->form_validation->set_rules('url_image_license', '营业执照正/副本', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_owner_id', '法人身份证照片', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_auth_id', '经办人身份证', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_auth_doc', '授权书', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_product', '产品', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_produce', '工厂/产地', 'trim|max_length[255]');
	$this->form_validation->set_rules('url_image_retail', '门店/柜台', 'trim|max_length[255]');

	$this->form_validation->set_rules('bank_name', '开户行名称', 'trim|min_length[3]|max_length[20]');
	$this->form_validation->set_rules('bank_account', '开户行账号', 'trim|max_length[30]');

	$this->form_validation->set_rules('min_order_subtotal', '订单最低金额', 'trim|decimal|greater_than_equal_to[1.00]|less_than_equal_to[99.99]');
	$this->form_validation->set_rules('delivery_time_start', '配送起始时间', 'trim|is_natural|greater_than_equal_to[0]|less_than_equal_to[23]');
	$this->form_validation->set_rules('delivery_time_end', '配送结束时间', 'trim|is_natural_no_zero|greater_than_equal_to[0]|less_than_equal_to[23]');

	$this->form_validation->set_rules('country', '国家', 'trim|max_length[10]');
	$this->form_validation->set_rules('province', '省', 'trim|max_length[10]');
	$this->form_validation->set_rules('city', '市', 'trim|max_length[10]');
	$this->form_validation->set_rules('county', '区/县', 'trim|max_length[10]');
	$this->form_validation->set_rules('detail', '详细地址；小区名、路名、门牌号等', 'trim|max_length[50]');
	$this->form_validation->set_rules('longitude', '经度', 'trim|min_length[7]|max_length[10]|decimal');
	$this->form_validation->set_rules('latitude', '纬度', 'trim|min_length[7]|max_length[10]|decimal');