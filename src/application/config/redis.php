<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Redis settings
| -------------------------------------------------------------------------
| Your Redis servers can be specified below.
|
| Huangxin 2018-6-26 10:01:38 
| 数据库选择： 测试开发环境0， 生产环境3
| 主从选择：   写入用主， 读取用从
| ----集群
| 
*/
$config = [];

$config['socket_type'] = 'tcp';      //如果想提高安全性和速度 可以使用unixsocket
$config['host']     = '47.100.19.150';  
$config['password'] = NULL;          //密码作为最后一道防线 
$config['port']     = 6379;          //保护服务器 可修改默认端口
$config['timeout']  = 4; 
$config['database'] = 0;


// $slave['socket_type'] = 'tcp';