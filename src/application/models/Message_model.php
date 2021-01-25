<?php
	/**
	 * 聊天消息
	 *
	 * @version 1.0.0
	 * @author hx
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Message_model extends CI_Model
	{
		/**
		 * 数据库表名
		 *
		 * @var string $table_name 表名
		 */
		public $table_name = 'message';

		/**
		 * 数据库主键名
		 *
		 * @var string $id_name 数据库主键名
		 */
		public $id_name = 'message_id';
		private $to_client_user_type = '';
		private $bmodel;
		private $removemid = 0;

		public function sync($condtion, $key, $bmodel){
			$this->bmodel = $bmodel;
			$this->to_client_type = $key == 'biz_id' ? 'user_id' : 'biz_id';
			$sql1 = "select message_id,user_id,s_user_id,biz_id,s_biz_id,type,content,time_create from {$this->table_name} ";

			$sql = $sql1 . " where {$key}={$condtion} and `read`=1 union ";
			$sql .= $sql1 ." where s_{$key}={$condtion} and `read`=1 ";
			$query = "select * from ({$sql}) as tb order by message_id asc limit 0, 300";
			$res = $this->db->query($query);
			$data = $res->result_array();
			unset($res);
			$client = $this->chatClients($data);
			return $client;
		}
		public function index($condition, $key, $bmodel) {
			$this->bmodel = $bmodel;
			$this->to_client_type = $key == 'biz_id' ? 'user_id' : 'biz_id';

			$sql = "select  message_id,user_id,s_user_id,biz_id,s_biz_id,type,content,time_create from {$this->table_name} where ";
			$sql .= "(" . key($condition['to']) . "=" . intval(current($condition['to'])) . " and ";
			$sql .= "s_" . key($condition['me']) . "=" . intval(current($condition['me']));
			if ($condition['message_id'] > 1) {
				$sql .= " and message_id <= " . $condition['message_id'];
				$this->removemid = intval($condition['message_id']);
			}
			$sql .= ") ";


			$sql .= "or (s_" . key($condition['to']) . "=" . intval(current($condition['to'])) . " and ";
			$sql .= key($condition['me']) . "=" . intval(current($condition['me']));
			if ($condition['message_id'] > 1) {
				$sql .= " and message_id <= " . $condition['message_id'];
			}
			$sql .= ") ";


			$sql .= " order by message_id desc limit 0, 15";
			if (isset($_GET['debug'])) {
				var_dump($sql);
			}
			$res = $this->db->query($sql);
			$data = $res->result_array();
			unset($res);
			$client = $this->chatClients($data);
			if(empty($client)) {
				return [];
			} 
			return $client;
		}
		public function record($condition, $key, $bmodel) {
			$this->bmodel = $bmodel;
			$this->to_client_type = $key == 'biz_id' ? 'user_id' : 'biz_id';
			$withRead = ')';
			if($condition['read'] != 0){
				$withRead = " and `read` = " . $condition['read'] . ")";
			}
			$sql = "select  message_id,user_id,s_user_id,biz_id,s_biz_id,type,content,time_create from {$this->table_name} where ";
			
			$sql .= " (" . key($condition['me']) . "=" . current($condition['me']) . $withRead;
			$sql .= " or ";
			$sql .= " (s_" . key($condition['me']) . "=" . current($condition['me']) . $withRead;
			$sql .= " order by message_id desc limit 0,100";
			$res = $this->db->query($sql);
			$data = $res->result_array();
			unset($res);
			$client = $this->chatClients($data);
			return $client;
		}
		private function chatClients($res) {
			//取得聊天用户信息
			$clientIds = [];
			$toClientKey = $this->to_client_type;          //我发给他的
			$myselfKey = 's_' . $this->to_client_type;     //他发给我的
			foreach ($res as $key => $value) {
				if ($value[$toClientKey]) {
					$clientIds[$value[$toClientKey]] = $value[$toClientKey];
				}
				if ($value[$myselfKey]) {
					$clientIds[$value[$myselfKey]] = $value[$myselfKey];
				}
				if (count($clientIds) >= 10){
					break;
				}
			}
			$this->bmodel->db->reset_query(); // 重置查询
			$this->bmodel->table_name = str_replace('_id', '', $toClientKey);
			$this->bmodel->id_name =  $toClientKey;

			$fieldsMap = ['biz_id'=>[$toClientKey, 'brief_name', 'url_logo'], 'user_id'=>[$toClientKey, 'nickname', 'avatar']];
			$this->bmodel->setfields($fieldsMap[$toClientKey]);
			$chatUser = $this->bmodel->select_by_ids(implode(',', $clientIds));
			//拼接聊天消息
			foreach ($chatUser as $key => $value) {
				if (array_key_exists('avatar', $value) && is_null($value['avatar'])) {
					$value['avatar'] = "https://cdn-remote.517ybang.com/default_avatar.png";
				}
				if (array_key_exists('nickname', $value) && empty($value['nickname'])) {
					$value['nickname'] = "nickname{$value['user_id']}";
				}
				$clientKey = key($value);
				$value['list'] = [];
				foreach ($res as $index => $msg) {
					if (intval($msg['message_id']) == $this->removemid) {
						continue;
					}
					if ($msg['type'] == 'item' && $clientKey == 'biz_id') {
						continue;
					}
					$arr = ['message_id' => $msg['message_id'], 'content'=>$msg['content'], 'type'=> $msg['type'], 'time_create'=> $msg['time_create']];
					if ($msg[$clientKey] == $value[$clientKey]) {
						$arr['chat'] = 'send';
						$value['list'][] = $arr;
					} 
					if($msg['s_' . $clientKey] == $value[$clientKey]) {
						$arr['chat'] = 'receive';
						$value['list'][] = $arr;
					}
				}
				$value['list'] = array_reverse($value['list']);
				$chatUser[$key] = $value;
			}
			return $chatUser; 
		}
		
	}