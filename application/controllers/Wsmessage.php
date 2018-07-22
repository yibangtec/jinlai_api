<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * websocket 授权
	 *
	 * @version 1.0.0
	 * @author huangxin
	 * @copyright 
	 */
	class Wsmessage extends MY_Controller{

		public $_wsconfig = [];
		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'biz'; // 这里……
			$this->id_name = 'biz_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;

			if ($this->wssuseful() == 2) {
				$this->result['status'] = 501;
				$this->result['content']['error']['message'] = '当前websocket服务器不可用';
				exit();
			} 
			
			
		} // end __construct

        // 获取websocket身份token
		public function getverify(){
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('biz', 'client'); // 客户端类型
			$this->client_check($type_allowed);

			//当前客户端对应用户字段
			$param_map = ['client'=>'user_id', 'biz'=>'biz_id'];
			
			$uid = $this->input->post($param_map[$this->app_type]);
			$uid = intval($uid);
			if ($uid == 0) {
				$this->result['status'] = 440;
				$this->result['content']['error']['message'] = '请传入正确的uid';
				return FALSE;
			}

			$this->switch_model(str_replace('_id', '', $param_map[$this->app_type]), $param_map[$this->app_type]);

			//获取当前用户信息
			$request_Minions = $this->basic_model->select_by_id($uid);
			if (is_null($request_Minions)) {
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';
				exit;
			}

			//设定对应关系
			$field_refer = ['client'=>['avatar'=>'uimg','nickname'=>'uname','user_id'=>'uid','mobile'=>'phone'],'biz'=>['brief_name'=>'uname','url_logo'=>'uimg','biz_id'=>'uid','tel_public'=>'phone']];
			$entity = [];
			foreach ($field_refer[$this->app_type] as $key => $value) {
				$entity[$value] = $request_Minions[$key];
			}

			$this->switch_model('ws_verify', 'id');
			if (empty($entity['uimg'])) 
				$entity['uimg'] = "https://cdn-remote.517ybang.com/default_avatar.png";
			
			$entity['token'] = sha1('ws_' . $uid . date('mdis'));
			$entity['time_create'] = time();
			$entity['utype'] = $this->app_type == 'client' ? 'user' : 'biz';

			if ($this->basic_model->create($entity) && $this->cache_client($entity)) {
				$this->_wsconfig['token'] = $entity['token'];
				$this->_wsconfig['port']  = '2989'; //暂时一个够用了 写死
				$this->result['status'] = 200;
				$this->result['content']= $this->_wsconfig;
			} else {
				$this->result['status'] = 502;
				$this->result['content']['error']['message'] = '获取签名失败，请稍后重试';
			}
		}

		//同步消息 未读的消息

        public function sync(){
            // 操作可能需要检查客户端及设备信息
            $type_allowed = array('biz', 'client'); // 客户端类型
            $this->client_check($type_allowed);

            $type_refer = ['biz'=>'biz_id', 'client' => 'user_id'];
            $condition = $this->input->post($type_refer[$this->app_type]);
            $condition = intval($condition);

            $this->load->model('message_model');

            $res = $this->message_model->sync($condition, $type_refer[$this->app_type], $this->basic_model);

            if ($res) {
            	$this->result['content'] = $res;
            	$this->result['status'] = 200;
            } else {
            	$this->result['status'] = 400;
            	$this->result['content']['error']['message'] = '没有符合条件的数据';
            }
            
        } //end

        // // 获取更多
        public function index(){
        	// 操作可能需要检查客户端及设备信息
            $type_allowed = array('biz', 'client'); // 客户端类型
            $this->client_check($type_allowed);

            $type_refer = ['biz'=>'biz_id', 'client' => 'user_id'];
            $originKey = $type_refer[$this->app_type];
            unset($type_refer[$this->app_type]);
            //发给我的
            $condition['me'] = [$originKey => intval($this->input->post($originKey))];                 
            //我发给他的
            $condition['to'] = [current($type_refer) => intval($this->input->post(current($type_refer)))];
            //最后一条id
            $condition['message_id'] = intval($this->input->post('message_id'));
            if ($condition['message_id'] <= 0) {
            	$this->result['status'] = 500;
            	$this->result['content']['error']['message'] = '缺少message_id';
            	exit();
            }
            $this->load->model('message_model');

            $res = $this->message_model->index($condition, $originKey, $this->basic_model);

            if ($res) {
            	$this->result['content'] = $res;
            	$this->result['status'] = 200;
            } else {
            	$this->result['status'] = 400;
            	$this->result['content']['error']['message'] = '没有符合条件的数据';
            }
        }

        // // 打招呼
        public function hi(){
        	// 操作可能需要检查客户端及设备信息
            $type_allowed = array('biz', 'client'); // 客户端类型
            $this->client_check($type_allowed);

            $type_refer = ['biz'=>'biz_id', 'client' => 'user_id'];
            $originKey = $type_refer[$this->app_type];
            unset($type_refer[$this->app_type]);
            $message['s_' . $originKey] = intval($this->input->post($originKey));
            $message[current($type_refer)] = intval($this->input->post(current($type_refer)));
            $message['type'] = 'text';
            $message['content'] = '亲，在吗？';
            $message['creator_id'] = current($message);
            $message['time_create'] = time();
            $message['sender_type'] = $this->app_type == 'biz' ? 'biz' : 'user';
            $message['receiver_type'] = $this->app_type == 'user' ? 'biz' : 'biz';
            $this->switch_model('message', 'message_id');
            $r = $this->basic_model->create($message);
            if ($r) {
            	$this->result['content'] = 'success';
            	$this->result['status'] = 200;
            } else {
            	$this->result['status'] = 400;
            	$this->result['content']['error']['message'] = '没有符合条件的数据';
            }
        }


        // 通过身份验证的，保存到redis
		public function cache_client($client){
			if ($this->init_redis()) {
				$validTime = 1800;//60 过期时间 暂定30分钟，正式运行时1分钟 甚至更短
				return $this->myredis->savehash($client['token'], $client, $validTime);
			}
			return false;
		}

		// 获取redis
		public function wssuseful(){
			if ($this->init_redis()) {
				return intval($this->myredis->get('websocket:useful'));
			}
			return 2;//不可用
		}
	}