SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS  `address`;
CREATE TABLE `address` (
  `address_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '地址ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `brief` varchar(10) DEFAULT NULL COMMENT '简称；例如“母亲家”，最多10个字符',
  `fullname` varchar(10) NOT NULL COMMENT '姓名；最多10个字符',
  `mobile` varchar(11) NOT NULL COMMENT '手机号；不支持固定电话号码',
  `nation` varchar(10) DEFAULT '中国' COMMENT '国别；默认“中国”，最多10个字符',
  `province` varchar(10) NOT NULL COMMENT '省；省级行政区/直辖市，最多10个字符',
  `city` varchar(10) NOT NULL COMMENT '市；地级行政区，最多10个字符',
  `county` varchar(10) DEFAULT NULL COMMENT '区/县；县级行政区，最多10个字符',
  `street` varchar(50) NOT NULL COMMENT '具体地址；最多50个字符',
  `longitude` varchar(10) DEFAULT NULL COMMENT '经度；高德坐标系，最多10个字符，小数点后保留5位数字，下同',
  `latitude` varchar(10) DEFAULT NULL COMMENT '纬度',
  `zipcode` varchar(6) DEFAULT NULL COMMENT '邮政编码',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COMMENT='地址信息表';

DROP TABLE IF EXISTS  `article`;
CREATE TABLE `article` (
  `article_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `category_id` varchar(11) NOT NULL DEFAULT '1' COMMENT '所属分类ID',
  `title` varchar(30) NOT NULL COMMENT '标题；4-30个字符',
  `excerpt` varchar(100) DEFAULT NULL COMMENT '摘要；10-100个字符',
  `content` varchar(20000) NOT NULL COMMENT '正文；10-20000个字符',
  `url_name` varchar(30) DEFAULT NULL COMMENT '自定义域名',
  `url_images` varchar(255) DEFAULT NULL COMMENT '形象图；URL们，CSV格式',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='平台文章信息表';

DROP TABLE IF EXISTS  `article_biz`;
CREATE TABLE `article_biz` (
  `article_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `title` varchar(30) NOT NULL COMMENT '标题；4-30个字符',
  `excerpt` varchar(100) DEFAULT NULL COMMENT '摘要；10-100个字符',
  `content` varchar(20000) NOT NULL COMMENT '正文；10-20000个字符',
  `url_images` varchar(255) DEFAULT NULL COMMENT '形象图；URL们，CSV格式',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='商家文章信息表';

DROP TABLE IF EXISTS  `article_category`;
CREATE TABLE `article_category` (
  `category_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章分类ID',
  `parent_id` varchar(3) DEFAULT NULL COMMENT '所属分类ID',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `url_name` varchar(30) DEFAULT NULL COMMENT '自定义域名；最多30个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='文章分类信息表';

DROP TABLE IF EXISTS  `biz`;
CREATE TABLE `biz` (
  `biz_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商家ID',
  `identity_id` varchar(11) DEFAULT NULL COMMENT '商家认证ID',
  `category_id` varchar(3) NOT NULL COMMENT '主营商品分类ID',
  `name` varchar(35) DEFAULT NULL COMMENT '商家全称；须与营业执照一致，5-35个字符',
  `brief_name` varchar(20) NOT NULL COMMENT '店铺名称；最多20个字符',
  `url_name` varchar(20) DEFAULT NULL COMMENT '店铺域名；最多20个字符，仅允许英文小写及下划线，未设置此项的店铺只能通过biz_id进行访问',
  `url_logo` varchar(255) DEFAULT NULL COMMENT '商家LOGO；图片URL',
  `slogan` varchar(30) DEFAULT NULL COMMENT '宣传语；2-30个字符',
  `description` varchar(255) DEFAULT NULL COMMENT '简介；最多255个字符',
  `notification` varchar(255) DEFAULT NULL COMMENT '店铺公告；最多255个字符',
  `tel_public` varchar(13) NOT NULL COMMENT '消费者联系电话；手机号或“区号-固定电话号码”均可',
  `tel_protected_biz` varchar(11) NOT NULL COMMENT '商务联系手机号',
  `tel_protected_fiscal` varchar(11) NOT NULL COMMENT '财务联系手机号',
  `tel_protected_order` varchar(11) NOT NULL COMMENT '订单通知手机号',
  `url_image_product` varchar(255) DEFAULT NULL COMMENT '产品照片；照片URL们，CSV格式，后同',
  `url_image_produce` varchar(255) DEFAULT NULL COMMENT '工厂/产地照片',
  `url_image_retail` varchar(255) DEFAULT NULL COMMENT '门店/柜台照片',
  `freight_template_id` varchar(11) DEFAULT NULL COMMENT '商家运费模板ID',
  `ornament_id` varchar(11) DEFAULT NULL COMMENT '店铺装修ID',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('正常','冻结','待受理','受理中','待补充','已拒绝','待发布') NOT NULL DEFAULT '待受理' COMMENT '状态；正常、冻结、待受理、受理中、待补充、已拒绝、待发布',
  PRIMARY KEY (`biz_id`),
  KEY `url_name` (`url_name`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8 COMMENT='商家信息表';

DROP TABLE IF EXISTS  `branch`;
CREATE TABLE `branch` (
  `branch_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '门店ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `description` varchar(30) DEFAULT NULL COMMENT '说明；最多30个字符',
  `tel_public` varchar(13) DEFAULT NULL COMMENT '消费者联系电话；手机号或“区号-固定电话号码”均可',
  `tel_protected_biz` varchar(11) DEFAULT NULL COMMENT '商务联系手机号',
  `tel_protected_order` varchar(11) DEFAULT NULL COMMENT '订单通知手机号',
  `day_rest` varchar(13) DEFAULT NULL COMMENT '休息日；0周日1周一，以此类推，多个休息日间以一个半角逗号分隔',
  `time_open` tinyint(2) unsigned zerofill NOT NULL DEFAULT '08' COMMENT '开放时间；开始营业时间或开始配送时间，例如08',
  `time_close` tinyint(2) unsigned zerofill NOT NULL DEFAULT '18' COMMENT '结束时间；结束营业时间或停止配送时间，例如23',
  `url_image_main` varchar(255) DEFAULT NULL COMMENT '主图；图片URL',
  `figure_image_urls` varchar(255) DEFAULT NULL COMMENT '形象图；URL们，多个URL间用一个半角逗号分隔',
  `nation` varchar(10) NOT NULL DEFAULT '中国' COMMENT '国别；默认“中国”，最多10个字符',
  `province` varchar(10) NOT NULL COMMENT '省；省级行政区/直辖市，最多10个字符',
  `city` varchar(10) NOT NULL COMMENT '市；地级行政区，最多10个字符',
  `county` varchar(10) NOT NULL COMMENT '区/县；县级行政区，最多10个字符',
  `street` varchar(50) NOT NULL COMMENT '具体地址；最多50个字符',
  `longitude` varchar(10) DEFAULT NULL COMMENT '经度；高德坐标系，最多10个字符，小数点后保留5位数字，下同',
  `latitude` varchar(10) DEFAULT NULL COMMENT '纬度',
  `region_id` int(11) unsigned DEFAULT NULL COMMENT '地区ID',
  `region` varchar(20) DEFAULT NULL COMMENT '地区；商圈、社区等',
  `poi_id` int(11) unsigned DEFAULT NULL COMMENT '兴趣点ID',
  `poi` varchar(20) DEFAULT NULL COMMENT '兴趣点；地标、小区名等',
  `range_deliver` tinyint(2) unsigned DEFAULT '0' COMMENT '配送范围；公里，最高99',
  `status` enum('正常','冻结') NOT NULL DEFAULT '正常' COMMENT '状态；正常、冻结',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作时间',
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店信息表';

DROP TABLE IF EXISTS  `captcha`;
CREATE TABLE `captcha` (
  `captcha_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '图片验证码ID',
  `user_ip` varchar(39) NOT NULL COMMENT '用户IP地址；支持IPv6',
  `captcha` varchar(6) NOT NULL COMMENT '验证码内容；最多6个字符',
  `time_expire` varchar(10) NOT NULL COMMENT '失效时间；UNIX时间戳',
  `time_create` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`captcha_id`),
  KEY `word` (`captcha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片验证码信息表';

DROP TABLE IF EXISTS  `ci_sessions_admin`;
CREATE TABLE `ci_sessions_admin` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理端session信息表';

DROP TABLE IF EXISTS  `ci_sessions_biz`;
CREATE TABLE `ci_sessions_biz` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商家端session信息表';

DROP TABLE IF EXISTS  `ci_sessions_web`;
CREATE TABLE `ci_sessions_web` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户端session信息表';

DROP TABLE IF EXISTS  `comment_biz`;
CREATE TABLE `comment_biz` (
  `comment_id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `biz_id` varchar(11) NOT NULL COMMENT '相关商家ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `order_id` varchar(20) DEFAULT NULL COMMENT '所属订单ID',
  `score_service` tinyint(1) unsigned DEFAULT '4' COMMENT '服务态度；1-5，越高越好，下同',
  `score_deliver` tinyint(1) unsigned DEFAULT '4' COMMENT '物流服务；仅实物类订单有此项',
  `score_environment` tinyint(1) unsigned DEFAULT '4' COMMENT '店面环境；仅服务类订单有此项',
  `content` varchar(255) DEFAULT NULL COMMENT '最多255个字；仅服务类订单有此项',
  `image_urls` varchar(255) DEFAULT NULL COMMENT '图片URL们；仅服务类订单有此项，最多4个，多个URL间以一个半角逗号分隔',
  `time_create` varchar(11) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='商家评论信息表';

DROP TABLE IF EXISTS  `comment_item`;
CREATE TABLE `comment_item` (
  `comment_id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `order_id` varchar(20) NOT NULL COMMENT '所属订单ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `biz_id` varchar(11) NOT NULL COMMENT '相关商家ID',
  `item_id` varchar(11) NOT NULL COMMENT '相关商品ID',
  `score` tinyint(1) unsigned DEFAULT '4' COMMENT '描述相符；1-5，越高越好',
  `content` varchar(255) DEFAULT '好评不解释！' COMMENT '评价内容；最多255个字符',
  `image_urls` varchar(255) DEFAULT NULL COMMENT '评价图片；URL们，最多4个，CSV',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('正常','冻结') DEFAULT '正常' COMMENT '状态；正常、冻结',
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='订单评论信息表';

DROP TABLE IF EXISTS  `coupon`;
CREATE TABLE `coupon` (
  `coupon_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '优惠券ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `combo_id` varchar(11) DEFAULT NULL COMMENT '所属优惠券包ID',
  `template_id` varchar(11) NOT NULL COMMENT '所属优惠券模板ID',
  `category_id` varchar(3) DEFAULT NULL COMMENT '限定系统分类ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属/限定商家ID',
  `category_biz_id` varchar(11) DEFAULT NULL COMMENT '限定商家分类ID',
  `item_id` varchar(11) DEFAULT NULL COMMENT '限定商品ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `amount` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '面额（元）；最高999',
  `min_subtotal` int(4) unsigned DEFAULT '1' COMMENT '最低订单小计（元）；最高9999，最低1，默认1',
  `time_start` int(10) unsigned DEFAULT NULL COMMENT '开始时间；UNIX时间戳',
  `time_end` int(10) unsigned DEFAULT NULL COMMENT '结束时间；UNIX时间戳',
  `order_id` varchar(11) DEFAULT NULL COMMENT '所属订单ID；若已被使用，则有此值',
  `time_used` varchar(10) DEFAULT NULL COMMENT '使用时间；UNIX时间戳',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间；后台删除用户优惠券时使用此字段',
  `time_expire` varchar(10) DEFAULT NULL COMMENT '失效时间；UNIX时间戳',
  `status` enum('正常','冻结') NOT NULL DEFAULT '正常' COMMENT '状态；正常、冻结',
  PRIMARY KEY (`coupon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='优惠券（用户）信息表；用于记录用户获得的优惠券';

DROP TABLE IF EXISTS  `coupon_combo`;
CREATE TABLE `coupon_combo` (
  `combo_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '优惠券套餐ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属商家ID；系统级优惠券可不填写此项',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `description` varchar(30) DEFAULT NULL COMMENT '说明；最多30个字符',
  `template_ids` varchar(255) NOT NULL COMMENT '所含优惠券；优惠券模板ID|数量，CSV格式',
  `max_amount` int(6) unsigned DEFAULT '0' COMMENT '限量；最高999999',
  `period` int(8) unsigned DEFAULT '2592000' COMMENT '有效期；秒，最高31622400（366天），默认2592000（30天）',
  `time_start` int(10) unsigned DEFAULT '0' COMMENT '开始时间；UNIX时间戳',
  `time_end` int(10) unsigned DEFAULT NULL COMMENT '结束时间；UNIX时间戳',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`combo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='优惠券套餐信息表';

DROP TABLE IF EXISTS  `coupon_template`;
CREATE TABLE `coupon_template` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属商家ID；系统级优惠券可不填写此项',
  `category_id` varchar(3) DEFAULT NULL COMMENT '限用系统商品分类ID',
  `category_biz_id` varchar(11) DEFAULT NULL COMMENT '限用商家商品分类ID',
  `item_id` varchar(20) DEFAULT NULL COMMENT '限用商品ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `description` varchar(30) DEFAULT NULL COMMENT '说明；最多30个字符',
  `amount` int(3) unsigned NOT NULL COMMENT '面额（元）；最高999，最低1',
  `min_subtotal` int(4) unsigned NOT NULL DEFAULT '1' COMMENT '最低订单小计（元）；最高9999，最低1，默认1',
  `max_amount` int(6) unsigned NOT NULL DEFAULT '0' COMMENT '总限量；不限0，最高999999',
  `max_amount_user` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '单个用户限量；不限0，最高99，默认1',
  `period` int(8) unsigned DEFAULT '2592000' COMMENT '有效期；秒，最高31622400（366天），默认2592000（30天）',
  `time_start` int(10) unsigned DEFAULT NULL COMMENT '开始时间；UNIX时间戳',
  `time_end` int(10) unsigned DEFAULT NULL COMMENT '结束时间；UNIX时间戳',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='优惠券模板信息表';

DROP TABLE IF EXISTS  `credit`;
CREATE TABLE `credit` (
  `credit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '积分流水ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `order_id` varchar(11) NOT NULL COMMENT '所属订单ID',
  `type` enum('income','outgo') DEFAULT 'income' COMMENT '类型',
  `amount` int(5) unsigned NOT NULL COMMENT '数额；最多99999',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后编辑时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`credit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分流水信息表';

DROP TABLE IF EXISTS  `data_order_total`;
CREATE TABLE `data_order_total` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `value` int(11) unsigned NOT NULL COMMENT '值',
  `year` year(4) NOT NULL COMMENT '年',
  `month` tinyint(2) unsigned NOT NULL COMMENT '月',
  `day` tinyint(2) unsigned NOT NULL COMMENT '日',
  `hour` tinyint(2) unsigned NOT NULL COMMENT '时；24小时制',
  `minute` tinyint(2) unsigned NOT NULL COMMENT '分',
  `time_create` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4374 DEFAULT CHARSET=utf8 COMMENT='数据统计-总订单表';

DROP TABLE IF EXISTS  `data_pv_total`;
CREATE TABLE `data_pv_total` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `value` int(11) unsigned NOT NULL COMMENT '值',
  `year` year(4) NOT NULL COMMENT '年',
  `month` tinyint(2) unsigned NOT NULL COMMENT '月',
  `day` tinyint(2) unsigned NOT NULL COMMENT '日',
  `hour` tinyint(2) unsigned NOT NULL COMMENT '时；24小时制',
  `minute` tinyint(2) unsigned NOT NULL COMMENT '分',
  `second` tinyint(2) NOT NULL COMMENT '秒',
  `time_create` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1311634 DEFAULT CHARSET=utf8 COMMENT='数据统计-总UV表';

DROP TABLE IF EXISTS  `data_uv_biz`;
CREATE TABLE `data_uv_biz` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `value` int(11) unsigned NOT NULL COMMENT '值',
  `year` year(4) NOT NULL COMMENT '年',
  `month` tinyint(2) unsigned NOT NULL COMMENT '月',
  `day` tinyint(2) unsigned NOT NULL COMMENT '日',
  `hour` tinyint(2) unsigned NOT NULL COMMENT '时；24小时制',
  `minute` tinyint(2) unsigned NOT NULL COMMENT '分',
  `time_create` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4374 DEFAULT CHARSET=utf8 COMMENT='数据统计-商家UV表';

DROP TABLE IF EXISTS  `data_uv_total`;
CREATE TABLE `data_uv_total` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `value` int(11) unsigned NOT NULL COMMENT '值',
  `year` year(4) NOT NULL COMMENT '年',
  `month` tinyint(2) unsigned NOT NULL COMMENT '月',
  `day` tinyint(2) unsigned NOT NULL COMMENT '日',
  `hour` tinyint(2) unsigned NOT NULL COMMENT '时；24小时制',
  `minute` tinyint(2) unsigned NOT NULL COMMENT '分',
  `time_create` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4374 DEFAULT CHARSET=utf8 COMMENT='数据统计-总UV表';

DROP TABLE IF EXISTS  `deal`;
CREATE TABLE `deal` (
  `deal_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `category_id` varchar(3) NOT NULL COMMENT '所属系统分类ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `code_biz` varchar(20) DEFAULT NULL COMMENT '商家自定义商品编码；最多20个英文大小写字母、数字',
  `url_image_main` varchar(255) NOT NULL COMMENT '主图；URL',
  `figure_image_urls` varchar(255) DEFAULT NULL COMMENT '形象图；URL们，CSV格式',
  `figure_video_urls` varchar(255) DEFAULT NULL COMMENT '形象视频；URL们，CSV格式',
  `name` varchar(40) NOT NULL COMMENT '名称；最多40个字符，中英文、数字，不可为纯数字',
  `slogan` varchar(30) DEFAULT NULL COMMENT '宣传语/卖点；最多30个字符，中英文、数字，不可为纯数字',
  `description` varchar(20000) DEFAULT NULL COMMENT '商品描述；最多20000个字符',
  `tag_price` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '标签价/原价（元）；0.00为不显示，最高99999.99',
  `price` decimal(7,2) unsigned NOT NULL COMMENT '商城价/现价（元）；最高99999.99',
  `days_advanced` tinyint(2) unsigned DEFAULT '0' COMMENT '提前预约天数；默认0（无需预约），最高31',
  `max_overlay` tinyint(1) unsigned DEFAULT '0' COMMENT '最高叠加次数；默认0（不可叠加使用），最高9',
  `count_payed` int(11) unsigned DEFAULT '0' COMMENT '已售数量',
  `quantity_max` tinyint(2) unsigned DEFAULT '50' COMMENT '每用户最高限量（份）；0为不限，最高50',
  `quantity_min` tinyint(2) unsigned DEFAULT '1' COMMENT '每用户最低限量（份）；0为不限，最高50',
  `coupon_allowed` enum('0','1') DEFAULT '1' COMMENT '是否可用优惠券；默认1，不允许使用优惠券可设0',
  `discount_credit` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '积分抵扣率；例如允许5%的金额使用积分抵扣则为0.05，10%为0.1，最高0.5',
  `commission_rate` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '佣金比例/提成率；例如提成成交价5%的金额则为0.05，10%为0.1，最高0.5',
  `promotion_id` varchar(10) DEFAULT NULL COMMENT '店内活动ID',
  `branch_ids` varchar(255) DEFAULT NULL COMMENT '可用门店ID们；CSV格式',
  `time_start` int(10) DEFAULT NULL COMMENT '开始时间；UNIX时间戳，后同',
  `time_end` int(10) DEFAULT NULL COMMENT '结束时间',
  `time_publish` int(10) unsigned DEFAULT NULL COMMENT '上架时间',
  `time_suspend` int(10) unsigned DEFAULT NULL COMMENT '下架时间',
  `time_to_publish` int(10) unsigned DEFAULT NULL COMMENT '预定上架时间',
  `time_to_suspend` int(10) unsigned DEFAULT NULL COMMENT '预定下架时间',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `note_admin` varchar(255) DEFAULT NULL COMMENT '管理员备注；例如审核意见等',
  `status` enum('正常','下架','冻结','禁售','待审核') NOT NULL DEFAULT '待审核' COMMENT '状态；正常、冻结、禁售、待审核',
  PRIMARY KEY (`deal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='实物类商品信息表';

DROP TABLE IF EXISTS  `fav_biz`;
CREATE TABLE `fav_biz` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏记录ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `biz_id` varchar(20) NOT NULL COMMENT '相关商家ID',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8 COMMENT='商家收藏信息表';

DROP TABLE IF EXISTS  `fav_item`;
CREATE TABLE `fav_item` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏记录ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `item_id` varchar(20) NOT NULL COMMENT '相关商品ID',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8 COMMENT='商品收藏信息表';

DROP TABLE IF EXISTS  `freight_dada`;
CREATE TABLE `freight_dada` (
  `freight_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '物流ID',
  `order_id` varchar(20) NOT NULL COMMENT '所属订单ID',
  `distance` decimal(6,2) unsigned DEFAULT NULL COMMENT '配送距离（公里）',
  `fee` decimal(5,2) unsigned DEFAULT NULL COMMENT '配送费（元）',
  `shop_no` varchar(10) NOT NULL COMMENT '门店ID',
  `origin_id` varchar(30) NOT NULL COMMENT '带时间戳的订单ID',
  `city_code` varchar(4) NOT NULL DEFAULT '0532' COMMENT '城市代码',
  `cargo_price` decimal(5,2) unsigned NOT NULL COMMENT '订单金额',
  `receiver_name` varchar(10) NOT NULL COMMENT '收件人姓名',
  `receiver_address` varchar(30) NOT NULL COMMENT '收件人地址',
  `receiver_phone` varchar(11) NOT NULL COMMENT '收件人手机号',
  `receiver_lat` varchar(10) NOT NULL COMMENT '收件人纬度',
  `receiver_lng` varchar(10) NOT NULL COMMENT '收件人经度',
  `is_prepay` tinyint(1) unsigned DEFAULT '0' COMMENT '是否垫付;默认0',
  `expected_fetch_time` varchar(10) DEFAULT NULL COMMENT '期望取货时间；Unix时间戳',
  `expected_finish_time` varchar(10) DEFAULT NULL COMMENT '期望送达时间；Unix时间戳',
  `cargo_type` tinyint(2) unsigned DEFAULT NULL COMMENT '商品种类；1、餐饮 2、饮料 3、鲜花 4、票务 5、其他 8、印刷品 9、便利店 10、学校餐饮 11、校园便利 12、生鲜 13、水果',
  `cargo_weight` decimal(5,2) unsigned DEFAULT NULL COMMENT '商品重量',
  `cargo_num` tinyint(3) unsigned DEFAULT NULL COMMENT '商品数量',
  `tips` decimal(3,1) unsigned DEFAULT '0.0' COMMENT '小费',
  `info` varchar(30) DEFAULT NULL COMMENT '备注；最多30个字符',
  `invoice_title` varchar(30) DEFAULT NULL COMMENT '发票抬头；最多30个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_cancel` datetime DEFAULT NULL COMMENT '取消时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后编辑时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `order_status` varchar(1) NOT NULL DEFAULT '1' COMMENT '达达订单状态；待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8',
  `cancel_reason` varchar(10) DEFAULT NULL COMMENT '订单取消原因；最多10个字符',
  `cancel_from` varchar(1) DEFAULT NULL COMMENT '订单取消来源；1:达达配送员取消；2:商家主动取消；3:系统或客服取消；0:默认值',
  `deduct_fee` decimal(5,2) unsigned DEFAULT NULL COMMENT '订单取消违约金(元)',
  `dm_id` varchar(20) DEFAULT NULL COMMENT '达达配送员ID',
  `dm_name` varchar(10) DEFAULT NULL COMMENT '配送员姓名',
  `dm_mobile` varchar(11) DEFAULT NULL COMMENT '配送员手机号',
  PRIMARY KEY (`freight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='物流商达达信息表';

DROP TABLE IF EXISTS  `freight_template_biz`;
CREATE TABLE `freight_template_biz` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '运费模板ID',
  `biz_id` varchar(10) NOT NULL COMMENT '所属商家ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `type` enum('物流配送','电子凭证') NOT NULL DEFAULT '物流配送' COMMENT '类型；物流配送、电子凭证',
  `time_valid_from` int(10) unsigned DEFAULT NULL COMMENT '有效期起始时间；UNIX时间戳',
  `time_valid_end` int(10) unsigned DEFAULT NULL COMMENT '有效期结束时间；UNIX时间戳',
  `period_valid` int(8) unsigned DEFAULT '31622400' COMMENT '有效期；秒，最低86400（24小时），最高及默认31622400（366个自然日）',
  `expire_refund_rate` decimal(3,2) unsigned DEFAULT '1.00' COMMENT '过期退款比例；例如100%为1，30%为0.30，依次类推',
  `nation` varchar(10) DEFAULT '''中国''' COMMENT '国家；发货点位置，下同',
  `province` varchar(10) DEFAULT NULL COMMENT '省级行政区',
  `city` varchar(10) DEFAULT NULL COMMENT '市级行政区',
  `county` varchar(10) DEFAULT NULL COMMENT '区县级行政区',
  `longitude` varchar(10) DEFAULT NULL COMMENT '经度；高德坐标系，最多10个字符，下同',
  `latitude` varchar(10) DEFAULT NULL COMMENT '纬度',
  `time_latest_deliver` int(10) unsigned DEFAULT '259200' COMMENT '发货时间；自收款时起秒数，默认259200（3个自然日），最小3600（1小时），最高3888000（45个自然日）',
  `type_actual` enum('计件','净重','毛重','体积重') DEFAULT '计件' COMMENT '运费计算方式；计件、净重、毛重、体积重',
  `max_amount` int(4) unsigned DEFAULT '0' COMMENT '每单最高量（单位）；最高9999',
  `start_amount` int(4) unsigned DEFAULT '0' COMMENT '首量（单位）；最高9999',
  `unit_amount` int(4) unsigned DEFAULT '0' COMMENT '续量（单位）',
  `fee_start` int(3) unsigned DEFAULT '0' COMMENT '首量运费（元）；最高999',
  `fee_unit` int(3) unsigned DEFAULT '0' COMMENT '续量运费（元/单位）；最高999',
  `exempt_amount` int(4) unsigned DEFAULT '0' COMMENT '包邮量（单位）',
  `exempt_subtotal` int(4) unsigned DEFAULT '0' COMMENT '包邮订单小计（元）；最高9999',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='商家物流模板信息表';

DROP TABLE IF EXISTS  `history_item_view`;
CREATE TABLE `history_item_view` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '历史记录ID',
  `user_id` varchar(11) NOT NULL COMMENT '用户ID',
  `item_id` varchar(11) NOT NULL COMMENT '商品ID',
  `times_viewed_today` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '当天浏览次数；最高255',
  `last_viewed_date` date NOT NULL COMMENT '当天日期；Y-m-d',
  `last_viewed_time` varchar(10) NOT NULL COMMENT '最后浏览时间；UNIX时间戳',
  `last_viewed_platform` enum('android','ios','web','weapp') DEFAULT 'web' COMMENT '最后浏览平台；android、ios、web、weapp',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='商品浏览记录信息表';

DROP TABLE IF EXISTS  `identity_biz`;
CREATE TABLE `identity_biz` (
  `identity_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '认证ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `name` varchar(35) NOT NULL COMMENT '主体名称；须与营业执照一致，5-35个字符',
  `fullname_owner` varchar(15) NOT NULL COMMENT '法人姓名',
  `fullname_auth` varchar(15) NOT NULL COMMENT '经办人姓名',
  `code_license` varchar(18) NOT NULL COMMENT '工商注册号/统一社会信用代码；最长18位',
  `code_ssn_owner` varchar(18) NOT NULL COMMENT '法人身份证号；最长18位',
  `code_ssn_auth` varchar(18) NOT NULL COMMENT '经办人身份证号；最长18位',
  `url_image_license` varchar(255) NOT NULL COMMENT '营业执照；照片URL，后同',
  `url_image_owner_ssn` varchar(255) NOT NULL COMMENT '法人身份证',
  `url_image_auth_ssn` varchar(255) NOT NULL COMMENT '经办人身份证',
  `url_image_auth_doc` varchar(255) NOT NULL COMMENT '经办人授权书',
  `url_verify_photo` varchar(255) NOT NULL COMMENT '经办人持身份证照片',
  `bank_name` varchar(20) NOT NULL COMMENT '开户行名称；最长20位中英文',
  `bank_account` varchar(20) NOT NULL COMMENT '开户行账号；最长30位纯数字',
  `nation` varchar(10) DEFAULT '中国' COMMENT '国家；默认中国',
  `province` varchar(10) NOT NULL COMMENT '省；省级行政区',
  `city` varchar(10) NOT NULL COMMENT '市；地市级行政区',
  `county` varchar(10) NOT NULL COMMENT '区；区县级行政区',
  `street` varchar(50) NOT NULL COMMENT '具体地址；小区名、路名、门牌号等',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待受理','受理中','待补充','已拒绝','已认证','已失效') NOT NULL DEFAULT '待受理' COMMENT '状态；正常、冻结、待受理、受理中、待补充、已拒绝、待发布',
  PRIMARY KEY (`identity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='企业认证信息表';

DROP TABLE IF EXISTS  `identity_user`;
CREATE TABLE `identity_user` (
  `identity_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '认证ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `fullname` varchar(35) NOT NULL COMMENT '姓名；须与身份证一致，2-15个字符',
  `code_ssn` varchar(18) NOT NULL COMMENT '身份证号；最长18位',
  `url_image_ssn` varchar(255) NOT NULL COMMENT '身份证照片；图片URL，下同',
  `url_verify_photo` varchar(255) NOT NULL COMMENT '用户持身份证照片',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待受理','受理中','待补充','已拒绝','已认证','已失效') NOT NULL DEFAULT '待受理' COMMENT '状态；待受理、受理中、待补充、已拒绝、已认证、已失效',
  PRIMARY KEY (`identity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业认证表';

DROP TABLE IF EXISTS  `item`;
CREATE TABLE `item` (
  `item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `category_id` varchar(3) NOT NULL COMMENT '所属系统分类ID',
  `brand_id` varchar(11) DEFAULT NULL COMMENT '所属品牌ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `category_biz_id` varchar(11) DEFAULT NULL COMMENT '所属商家分类ID',
  `code_biz` varchar(20) DEFAULT NULL COMMENT '商家商品编码；最多20个英文大小写字母、数字',
  `barcode` varchar(13) DEFAULT NULL COMMENT '商品条形码',
  `url_image_main` varchar(255) NOT NULL COMMENT '主图；URL',
  `figure_image_urls` varchar(255) DEFAULT NULL COMMENT '形象图；URL们，CSV格式',
  `figure_video_urls` varchar(255) DEFAULT NULL COMMENT '形象视频；URL们，CSV格式',
  `name` varchar(40) NOT NULL COMMENT '名称；最多40个字符，中英文、数字，不可为纯数字',
  `slogan` varchar(30) DEFAULT NULL COMMENT '宣传语/卖点；最多30个字符，中英文、数字，不可为纯数字',
  `description` varchar(20000) DEFAULT NULL COMMENT '商品描述；最多20000个字符',
  `tag_price` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '标签价/原价（元）；0.00为不显示，最高99999.99',
  `price` decimal(7,2) unsigned NOT NULL COMMENT '商城价/现价（元）；最高99999.99',
  `sold_overall` int(11) unsigned DEFAULT '0' COMMENT '总销量；有史以来',
  `sold_monthly` int(11) unsigned DEFAULT '0' COMMENT '月销量；31自然日内',
  `sold_daily` int(11) unsigned DEFAULT '0' COMMENT '日销量；24小时内',
  `stocks` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '库存量（单位）；最多65535',
  `unit_name` varchar(10) NOT NULL DEFAULT '份' COMMENT '销售单位；最多10个字符，例如斤、双、头、件等，默认份',
  `weight_net` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '净重（KG）；最高999.99',
  `weight_gross` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '毛重（KG）；最高999.99',
  `weight_volume` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '体积重（KG）；最高999.99',
  `quantity_max` tinyint(2) unsigned DEFAULT '50' COMMENT '每用户最高限量（份）；0为不限，最高50',
  `quantity_min` tinyint(2) unsigned DEFAULT '1' COMMENT '每用户最低限量（份）；0为不限，最高50',
  `coupon_allowed` enum('0','1') DEFAULT '1' COMMENT '是否可用优惠券；默认1，不允许使用优惠券可设0',
  `discount_credit` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '积分抵扣率；例如允许5%的金额使用积分抵扣则为0.05，10%为0.1，最高0.5',
  `commission_rate` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '佣金比例/提成率；例如提成成交价5%的金额则为0.05，10%为0.1，最高0.5',
  `promotion_id` varchar(10) DEFAULT NULL COMMENT '店内活动ID',
  `freight_template_id` varchar(10) DEFAULT NULL COMMENT '商家运费模板ID',
  `time_publish` varchar(10) DEFAULT NULL COMMENT '上架时间；Unix时间戳',
  `time_suspend` varchar(10) DEFAULT NULL COMMENT '下架时间；Unix时间戳',
  `time_to_publish` varchar(10) DEFAULT NULL COMMENT '预定上架时间；Unix时间戳，不可小于当前时间',
  `time_to_suspend` varchar(10) DEFAULT NULL COMMENT '预定下架时间；Unix时间戳，不可小于当前时间',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `note_admin` varchar(255) DEFAULT NULL COMMENT '管理员备注；例如审核意见等',
  `status` enum('正常','下架','冻结','禁售','待审核') NOT NULL DEFAULT '待审核' COMMENT '状态；正常、冻结、禁售、待审核',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=634 DEFAULT CHARSET=utf8 COMMENT='实物类商品信息表';

DROP TABLE IF EXISTS  `item_category`;
CREATE TABLE `item_category` (
  `category_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品分类ID',
  `parent_id` varchar(3) DEFAULT NULL COMMENT '所属分类ID',
  `nature` enum('商品','服务') NOT NULL DEFAULT '商品' COMMENT '商品性质；商品、服务',
  `level` enum('1','2','3') NOT NULL DEFAULT '1' COMMENT '分类级别；顶级分类1，次级分类2，末级分类3',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `description` varchar(100) DEFAULT NULL COMMENT '描述；最多100个字符',
  `url_name` varchar(30) DEFAULT NULL COMMENT '自定义域名；最多30个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '形象图；URL',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商品分类（系统级）信息表';

DROP TABLE IF EXISTS  `item_category_biz`;
CREATE TABLE `item_category_biz` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商家分类ID',
  `parent_id` tinyint(3) unsigned DEFAULT NULL COMMENT '所属商家分类ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `nature` enum('商品','服务') DEFAULT '商品' COMMENT '商品性质；商品、服务',
  `level` enum('1','2','3') DEFAULT '1' COMMENT '分类级别；顶级分类1，次级分类2，末级分类3',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `url_image` varchar(100) DEFAULT NULL COMMENT '分类图片；URL',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商品分类（商家级）信息表';

DROP TABLE IF EXISTS  `item_recharge`;
CREATE TABLE `item_recharge` (
  `item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '充值套餐ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属商户ID',
  `amount` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值金额（元）；一般情况下与total值相同，单独预留出该字段以供后续增加营销相关新功能',
  `bonus` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '赠送金额（元）',
  `subtotal` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '小计金额（元）；即实际入账金额',
  `discount` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣/减免金额（元）；直接输入被减免的金额',
  `total_expected` decimal(7,2) unsigned NOT NULL COMMENT '应支付金额（元）',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后编辑时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='充值套餐信息表';

DROP TABLE IF EXISTS  `material`;
CREATE TABLE `material` (
  `material_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '素材ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '商家ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `url` varchar(255) NOT NULL COMMENT 'URL',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后编辑时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) NOT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='素材信息表；营销用的媒体文件，例如图片、音频、视频等';

DROP TABLE IF EXISTS  `member_biz`;
CREATE TABLE `member_biz` (
  `member_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '会员卡记录ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `biz_id` varchar(20) NOT NULL COMMENT '相关商家ID',
  `mobile` varchar(11) NOT NULL COMMENT '登记手机号',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '会员等级',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=228 DEFAULT CHARSET=utf8 COMMENT='商家收藏信息表';

DROP TABLE IF EXISTS  `message`;
CREATE TABLE `message` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `sender_type` enum('admin','biz','client') NOT NULL DEFAULT 'client' COMMENT '发信端类型；发信者客户端类型admin,biz,client',
  `receiver_type` enum('admin','biz','client') NOT NULL DEFAULT 'biz' COMMENT '收信端类型；收信者客户端类型admin,biz,client',
  `user_id` varchar(11) DEFAULT NULL COMMENT '收信用户ID；收信端非client则留空',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '收信商家ID；收信端非biz则留空',
  `stuff_id` varchar(11) DEFAULT NULL COMMENT '收信员工ID；收信端为client则留空',
  `type` enum('address','article','article_biz','audio','branch','coupon_combo','coupon_templates','item','image','location','order','promotion','promotion_biz','text','video') NOT NULL DEFAULT 'text' COMMENT '类型；''address'',''article'',''article_biz'',''audio'',''branch'',''coupons'',''item'',''image'',''location'',''order'',''promotion'',''promotion_biz'',''text'',''video''',
  `title` varchar(30) DEFAULT NULL COMMENT '标题；最多30个字符',
  `excerpt` varchar(100) DEFAULT NULL COMMENT '摘要；最多100个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '形象图；URL',
  `content` varchar(5000) DEFAULT NULL COMMENT '内容；最多5000个字符',
  `ids` varchar(255) DEFAULT NULL COMMENT '内容ID们；CSV',
  `longitude` varchar(10) DEFAULT NULL COMMENT '经度；高德坐标系，最多10个字符，小数点后保留5位数字，下同',
  `latitude` varchar(10) DEFAULT NULL COMMENT '纬度',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_revoke` varchar(10) DEFAULT NULL COMMENT '撤回时间；UNIX时间戳',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='聊天消息信息表';

DROP TABLE IF EXISTS  `meta`;
CREATE TABLE `meta` (
  `meta_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '参数ID',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `value` varchar(255) DEFAULT NULL COMMENT '内容；最多255个字符',
  `description` varchar(255) NOT NULL COMMENT '说明；最多255个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`meta_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COMMENT='系统参数信息表';

DROP TABLE IF EXISTS  `noderegister`;
CREATE TABLE `noderegister` (
  `name` varchar(255) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tel` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS  `notice`;
CREATE TABLE `notice` (
  `notice_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '通知ID',
  `receiver_type` enum('admin','biz','client') NOT NULL DEFAULT 'client' COMMENT '收信端类型；admin,biz,client',
  `user_id` varchar(11) DEFAULT NULL COMMENT '用户ID；若非发给特定用户则留空',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '商家ID；若非发给特定商家则留空',
  `article_id` varchar(11) DEFAULT NULL COMMENT '相关文章ID',
  `title` varchar(30) DEFAULT NULL COMMENT '标题；最多30个字符',
  `excerpt` varchar(100) DEFAULT NULL COMMENT '摘要；最多100个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '形象图；URL',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_revoke` varchar(10) DEFAULT NULL COMMENT '撤回时间；UNIX时间戳',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`notice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='系统通知信息表';

DROP TABLE IF EXISTS  `order`;
CREATE TABLE `order` (
  `order_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `biz_id` varchar(11) NOT NULL DEFAULT '1' COMMENT '商户ID',
  `biz_name` varchar(20) NOT NULL COMMENT '店铺名称；最多20个字符',
  `biz_url_logo` varchar(255) DEFAULT NULL COMMENT '商户LOGO',
  `user_id` varchar(11) NOT NULL COMMENT '用户ID',
  `user_ip` varchar(39) DEFAULT NULL COMMENT '用户下单IP地址；支持IPv6',
  `subtotal` decimal(7,2) unsigned NOT NULL COMMENT '小计（元）',
  `promotion_id` varchar(11) DEFAULT NULL COMMENT '营销活动ID',
  `discount_promotion` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '营销活动折抵金额（元）',
  `coupon_id` varchar(20) DEFAULT NULL COMMENT '优惠券ID',
  `discount_coupon` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '优惠券折抵金额（元）',
  `freight` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '运费（元）；整数或0，包邮为0',
  `discount_reprice` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '改价折抵金额（元）',
  `repricer_id` varchar(11) DEFAULT NULL COMMENT '改价操作者ID',
  `total` decimal(7,2) unsigned NOT NULL COMMENT '应支付金额（元）',
  `credit_id` bigint(20) unsigned DEFAULT NULL COMMENT '积分流水ID',
  `credit_payed` int(5) unsigned DEFAULT '0' COMMENT '积分支付金额（元）；最高99999',
  `total_payed` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '实际支付金额（元）',
  `total_refund` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '实际退款金额（元）',
  `fullname` varchar(15) NOT NULL COMMENT '收件人姓名；最多15个字符',
  `code_ssn` varchar(18) DEFAULT NULL COMMENT '身份证号；18位，用于海淘订单',
  `mobile` varchar(11) NOT NULL COMMENT '收件人手机号',
  `nation` varchar(10) DEFAULT '中国' COMMENT '收件人国别',
  `province` varchar(10) NOT NULL COMMENT '收件人省',
  `city` varchar(10) NOT NULL COMMENT '收件人市',
  `county` varchar(10) NOT NULL COMMENT '收件人区/县',
  `street` varchar(50) NOT NULL COMMENT '收件人具体地址',
  `longitude` varchar(10) DEFAULT NULL COMMENT '经度；高德坐标系，最多10个字符',
  `latitude` varchar(10) DEFAULT NULL COMMENT '纬度；高德坐标系，最多10个字符',
  `note_user` varchar(100) DEFAULT NULL COMMENT '用户留言；最多100个字符',
  `note_stuff` varchar(255) DEFAULT NULL COMMENT '员工留言；即处理订单的员工留言，最多255个字符',
  `reason_cancel` varchar(20) DEFAULT NULL COMMENT '取消原因；最多20个字符',
  `payment_type` enum('现金','银行转账','微信支付','支付宝','余额','待支付') DEFAULT '待支付' COMMENT '付款方式；余额、银行转账、微信支付、支付宝',
  `payment_account` varchar(50) DEFAULT NULL COMMENT '付款账号',
  `payment_id` varchar(255) DEFAULT NULL COMMENT '付款流水号',
  `commission` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '佣金（元）；最高999.99',
  `promoter_id` varchar(11) DEFAULT NULL COMMENT '推广者ID',
  `deliver_method` enum('用户自提','同城配送','物流快递') DEFAULT '物流快递' COMMENT '发货方式；用户自提、同城配送、物流快递',
  `deliver_biz` varchar(30) DEFAULT NULL COMMENT '物流服务商',
  `waybill_id` varchar(30) DEFAULT NULL COMMENT '物流运单号；用户自提、自行配送时可留空',
  `invoice_status` enum('未申请','待审核','待开票','待寄出','已寄出','已电开') NOT NULL DEFAULT '未申请' COMMENT '发票状态',
  `invoice_id` varchar(20) DEFAULT NULL COMMENT '发票ID',
  `time_create` varchar(10) NOT NULL COMMENT '用户下单时间；UNIX时间戳，下同',
  `time_cancel` varchar(10) DEFAULT NULL COMMENT '用户取消时间',
  `time_expire` varchar(10) DEFAULT NULL COMMENT '自动过期时间；创建后未付款',
  `time_pay` varchar(10) DEFAULT NULL COMMENT '用户付款时间',
  `time_refuse` varchar(10) DEFAULT NULL COMMENT '商家拒绝时间；系统自动发起退款',
  `time_accept` varchar(10) DEFAULT NULL COMMENT '商家接单时间',
  `time_deliver` varchar(10) DEFAULT NULL COMMENT '商家发货时间',
  `time_confirm` varchar(10) DEFAULT NULL COMMENT '用户确认时间',
  `time_confirm_auto` varchar(10) DEFAULT NULL COMMENT '系统确认时间',
  `time_comment` varchar(10) DEFAULT NULL COMMENT '用户评价时间',
  `time_refund` varchar(10) DEFAULT NULL COMMENT '商家退款时间',
  `time_delete` datetime DEFAULT NULL COMMENT '用户删除时间；仅用户取消、自动过期、用户确认等时间不为空的订单可被用户删除',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待付款','待接单','待发货','待收货','待评价','已完成','已退款','已拒绝','已取消','已关闭','待使用') NOT NULL DEFAULT '待付款' COMMENT '订单状态',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8 COMMENT='商品类订单信息表';

DROP TABLE IF EXISTS  `order_items`;
CREATE TABLE `order_items` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品记录ID',
  `order_id` varchar(20) NOT NULL COMMENT '所属订单ID',
  `user_id` varchar(11) NOT NULL COMMENT '所属用户ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `item_id` varchar(20) NOT NULL COMMENT '商品ID',
  `name` varchar(40) NOT NULL COMMENT '商品名称',
  `item_image` varchar(255) NOT NULL COMMENT '商品主图',
  `slogan` varchar(30) DEFAULT NULL COMMENT '宣传语/卖点',
  `sku_id` varchar(20) DEFAULT NULL COMMENT '规格ID',
  `sku_name` varchar(32) DEFAULT NULL COMMENT '规格名称',
  `sku_image` varchar(255) DEFAULT NULL COMMENT '规格主图',
  `tag_price` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '标签价/原价（元）',
  `price` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商城价/现价（元）',
  `count` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `single_total` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单品小计',
  `time_create` int(10) unsigned NOT NULL COMMENT '创建时间；UNIX时间戳，下同',
  `time_refused` int(10) unsigned DEFAULT NULL COMMENT '退单时间',
  `time_accepted` int(10) unsigned DEFAULT NULL COMMENT '接单时间',
  `time_assigned` int(10) unsigned DEFAULT NULL COMMENT '指派时间',
  `time_picked` int(10) unsigned DEFAULT NULL COMMENT '配货时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('') NOT NULL COMMENT '状态',
  `refund_status` enum('未申请','待处理','已取消','已关闭','已拒绝','待退货','待退款','已退款') NOT NULL DEFAULT '未申请' COMMENT '退款状态',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8 COMMENT='订单商品信息表，即订单所含各项商品';

DROP TABLE IF EXISTS  `ornament_biz`;
CREATE TABLE `ornament_biz` (
  `ornament_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '装修方案ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `name` varchar(30) NOT NULL COMMENT '方案名称；最多30个字符',
  `template_id` varchar(11) DEFAULT NULL COMMENT '装修模板ID',
  `vi_color_first` varchar(6) DEFAULT NULL COMMENT '第一识别色；16进制颜色码，即#4cb5ff格式，下同',
  `vi_color_second` varchar(6) DEFAULT NULL COMMENT '第二识别色',
  `main_figure_url` varchar(255) DEFAULT NULL COMMENT '主形象图；URL',
  `member_logo_url` varchar(255) DEFAULT NULL COMMENT '会员卡LOGO；URL',
  `member_figure_url` varchar(255) DEFAULT NULL COMMENT '会员卡封图；URL',
  `member_thumb_url` varchar(255) DEFAULT NULL COMMENT '会员卡列表图；URL',
  `home_json` mediumtext COMMENT '首页JSON格式内容；10-20000个字符',
  `home_html` mediumtext COMMENT '首页HTML格式内容；10-20000个字符',
  `home_slides` varchar(255) DEFAULT NULL COMMENT '顶部模块轮播图内容；图片URL[|链接URL]，最多3组/张，CSV格式',
  `home_m0_ids` varchar(255) DEFAULT NULL COMMENT '顶部模块首推商品ID；最多3个商品ID，CSV格式，后同',
  `home_m1_ace_url` varchar(255) DEFAULT NULL COMMENT '模块一形象图URL',
  `home_m1_ace_id` varchar(11) DEFAULT NULL COMMENT '模块一首推商品ID',
  `home_m1_ids` varchar(255) DEFAULT NULL COMMENT '模块一陈列商品',
  `home_m2_ace_url` varchar(255) DEFAULT NULL COMMENT '模块二形象图URL',
  `home_m2_ace_id` varchar(11) DEFAULT NULL COMMENT '模块二首推商品ID',
  `home_m2_ids` varchar(255) DEFAULT NULL COMMENT '模块二陈列商品',
  `home_m3_ace_url` varchar(255) DEFAULT NULL COMMENT '模块三形象图URL',
  `home_m3_ace_id` varchar(11) DEFAULT NULL COMMENT '模块三首推商品ID',
  `home_m3_ids` varchar(255) DEFAULT NULL COMMENT '模块三陈列商品',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`ornament_id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8 COMMENT='商家装修信息表';

DROP TABLE IF EXISTS  `page`;
CREATE TABLE `page` (
  `page_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '页面ID',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `url_name` varchar(30) DEFAULT NULL COMMENT '页面URL；最多30个字符',
  `description` varchar(255) DEFAULT NULL COMMENT '说明；最多255个字符',
  `content_type` enum('HTML','文件') DEFAULT '文件' COMMENT '内容形式；HTML、文件',
  `content_html` varchar(20000) DEFAULT NULL COMMENT '页面内容（HTML格式）；最多20000个字符',
  `content_file` varchar(30) DEFAULT NULL COMMENT '页面文件；最多30个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='页面信息表';

DROP TABLE IF EXISTS  `plan`;
CREATE TABLE `plan` (
  `plan_id` bigint(20) NOT NULL COMMENT '计划ID',
  `table_name` varchar(30) NOT NULL COMMENT '目标行表名',
  `target_id` varchar(255) DEFAULT NULL COMMENT '目标行主键值',
  `target_name` varchar(255) DEFAULT NULL COMMENT '目标行字段名',
  `target_value` varchar(255) DEFAULT NULL COMMENT '目标字段值',
  `name` varchar(255) NOT NULL COMMENT '字段名',
  `value` varchar(255) DEFAULT NULL COMMENT '字段值',
  `time_operate` varchar(10) NOT NULL COMMENT '执行时间；UNIX时间戳',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待执行','已执行') DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='计划信息表';

DROP TABLE IF EXISTS  `promotion`;
CREATE TABLE `promotion` (
  `promotion_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '营销活动ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `description` varchar(255) DEFAULT NULL COMMENT '说明；最多255个字符',
  `url_images` varchar(255) DEFAULT NULL COMMENT '形象图；URL们多个URL间以一个半角逗号分隔,最多255个字符',
  `url_web` varchar(255) DEFAULT NULL COMMENT '活动页面URL',
  `brand_ids` varchar(255) DEFAULT NULL COMMENT '参与活动的品牌ID们',
  `biz_ids` varchar(255) DEFAULT NULL COMMENT '参与活动的商家ID们；多个ID间以一个半角逗号分隔',
  `item_ids` varchar(255) DEFAULT NULL COMMENT '参与活动的商品ID们；多个ID间以一个半角逗号分隔',
  `time_start` int(10) unsigned DEFAULT NULL COMMENT '开始时间；UNIX时间戳',
  `time_end` int(10) unsigned DEFAULT NULL COMMENT '结束时间；UNIX时间戳',
  `note_stuff` varchar(255) DEFAULT NULL COMMENT '员工备注；最多255个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`promotion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销活动信息表';

DROP TABLE IF EXISTS  `promotion_biz`;
CREATE TABLE `promotion_biz` (
  `promotion_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商家营销活动ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `name` varchar(20) NOT NULL COMMENT '名称；最多20个字符',
  `type` enum('单品折扣','单品满赠','单品满减','单品赠券','单品预购','单品团购','订单折扣','订单满赠','订单满减','订单赠券') NOT NULL COMMENT '活动类型',
  `time_start` int(10) unsigned NOT NULL COMMENT '开始时间；UNIX时间戳',
  `time_end` int(10) unsigned NOT NULL COMMENT '结束时间；UNIX时间戳',
  `description` varchar(255) DEFAULT NULL COMMENT '说明；最多255个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '活动图；URL',
  `url_image_wide` varchar(255) DEFAULT NULL COMMENT '宽屏活动图；URL',
  `fold_allowed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否允许折上折；即减免运费、使用优惠券等',
  `discount` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '折扣率；例如8折为0.80，3折为0.30，最低0.10',
  `present_trigger_amount` int(5) unsigned DEFAULT '0' COMMENT '赠品触发金额（元）；最高99999',
  `present_trigger_count` tinyint(2) unsigned DEFAULT '0' COMMENT '赠品触发份数（份）；满几份送赠品，最高99',
  `present` varchar(255) DEFAULT NULL COMMENT '赠品信息；商品ID|数量，多组信息间以一个半角逗号分隔',
  `reduction_trigger_amount` int(5) unsigned DEFAULT '0' COMMENT '满减触发金额（元）；最高99999',
  `reduction_trigger_count` tinyint(2) unsigned DEFAULT NULL COMMENT '满减触发件数（件）；最高99',
  `reduction_amount` int(3) unsigned DEFAULT '0' COMMENT '减免金额（元）；最高999',
  `reduction_amount_time` tinyint(2) unsigned DEFAULT '1' COMMENT '最高减免次数；最高99，默认1',
  `reduction_discount` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '减免比例；即折扣率例如8折为0.80，3折为0.30',
  `coupon_id` varchar(11) DEFAULT NULL COMMENT '赠送优惠券ID',
  `coupon_combo_id` varchar(11) DEFAULT NULL COMMENT '赠送优惠券套餐ID',
  `deposit` int(5) unsigned DEFAULT NULL COMMENT '订金/预付款（元）；最多为现价的40%，最多99999',
  `balance` int(5) unsigned DEFAULT NULL COMMENT '尾款（元）；最多为现价的60%，最多99999',
  `time_book_start` int(10) unsigned DEFAULT NULL COMMENT '支付预付款开始时间；UNIX时间戳',
  `time_book_end` int(10) unsigned DEFAULT NULL COMMENT '支付预付款结束时间；UNIX时间戳',
  `time_complete_start` int(10) unsigned DEFAULT NULL COMMENT '支付尾款开始时间；UNIX时间戳',
  `time_complete_end` int(10) unsigned DEFAULT NULL COMMENT '支付尾款结束时间；UNIX时间戳',
  `groupbuy_order_amount` tinyint(2) unsigned DEFAULT '5' COMMENT '团购成团订单数（单）;最高99',
  `groupbuy_quantity_max` tinyint(1) unsigned DEFAULT '1' COMMENT '团购个人最高限量（份/位）；最高9',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`promotion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商家营销活动信息表';

DROP TABLE IF EXISTS  `refund`;
CREATE TABLE `refund` (
  `refund_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '退款ID',
  `order_id` varchar(11) NOT NULL COMMENT '相关订单ID',
  `biz_id` varchar(11) NOT NULL COMMENT '相关商家ID',
  `user_id` varchar(11) NOT NULL COMMENT '相关用户ID',
  `record_id` varchar(20) NOT NULL COMMENT '订单商品ID',
  `type` enum('仅退款','退货退款') NOT NULL DEFAULT '退货退款' COMMENT '类型；仅退款、退货退款',
  `cargo_status` enum('未收货','已收货') NOT NULL DEFAULT '已收货' COMMENT '货物状态；已收货、未收货',
  `reason` enum('无理由','退运费','未收到','不开发票') NOT NULL DEFAULT '无理由' COMMENT '原因；无理由、退运费、未收到、不开发票',
  `description` varchar(255) DEFAULT NULL COMMENT '补充说明；最多255个字符',
  `url_images` varchar(255) DEFAULT NULL COMMENT '相关图片URL；最多255个字符',
  `total_applied` decimal(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '申请退款金额（元）；最高99999.99',
  `total_approved` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '同意退款金额（元）',
  `total_payed` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '实际退款金额',
  `deliver_method` enum('用户自提','同城配送','物流快递') DEFAULT NULL COMMENT '退货方式；用户自提、同城配送、物流快递',
  `deliver_biz` varchar(30) DEFAULT NULL COMMENT '物流服务商',
  `waybill_id` varchar(30) DEFAULT NULL COMMENT '物流运单号；用户自提、自行配送时可留空',
  `note_stuff` varchar(255) DEFAULT NULL COMMENT '员工备注',
  `time_create` varchar(10) NOT NULL COMMENT '用户创建时间；UNIX时间戳，下同',
  `time_cancel` varchar(10) DEFAULT NULL COMMENT '用户取消时间',
  `time_close` varchar(10) DEFAULT NULL COMMENT '关闭时间；用户规定时间内未响应',
  `time_refuse` varchar(10) DEFAULT NULL COMMENT '商家拒绝时间',
  `time_accept` varchar(10) DEFAULT NULL COMMENT '商家同意时间',
  `time_confirm` varchar(10) DEFAULT NULL COMMENT '商家收货时间',
  `time_refund` varchar(10) DEFAULT NULL COMMENT '商家退款时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待处理','已取消','已关闭','已拒绝','待退货','待退款','已退款') DEFAULT '待处理' COMMENT '状态；待处理、已取消、已关闭、已拒绝、待退货、待退款、已退款',
  PRIMARY KEY (`refund_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 COMMENT='退款信息表';

DROP TABLE IF EXISTS  `refund_record`;
CREATE TABLE `refund_record` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '退款记录ID',
  `refund_id` varchar(20) NOT NULL COMMENT '退款ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '商家ID',
  `user_id` varchar(11) DEFAULT NULL COMMENT '用户ID',
  `content` varchar(255) NOT NULL COMMENT '内容',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间；Unix时间戳，下同',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='退款记录信息表';

DROP TABLE IF EXISTS  `region`;
CREATE TABLE `region` (
  `region_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '地区ID',
  `nation` varchar(10) NOT NULL COMMENT '国别；默认“中国”，最多10个字符，下同',
  `province` varchar(10) NOT NULL COMMENT '省级行政区',
  `province_index` varchar(3) NOT NULL COMMENT '省级行政区索引；前二/三个词/字的发音首字母',
  `province_abbr` varchar(1) NOT NULL COMMENT '省级行政区简称',
  `province_brief` varchar(3) NOT NULL COMMENT '省级行政区通称',
  `city` varchar(10) NOT NULL COMMENT '市级行政区',
  `county` varchar(10) NOT NULL COMMENT '区县级行政区',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`region_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1548 DEFAULT CHARSET=utf8 COMMENT='地区信息表；主要用于省市区选择';

DROP TABLE IF EXISTS  `router`;
CREATE TABLE `router` (
  `router_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '路由ID',
  `name` varchar(30) NOT NULL COMMENT '路由规则名称',
  `light_preg` varchar(30) DEFAULT NULL COMMENT 'URL匹配字符串',
  `preg` varchar(30) DEFAULT NULL COMMENT 'URL匹配正则表达式',
  `controller` varchar(20) NOT NULL COMMENT '控制器名称',
  `function` varchar(20) NOT NULL COMMENT '方法名',
  `params` varchar(255) DEFAULT NULL COMMENT '参数名称；所需参数，CSV格式',
  `url_native_ios` varchar(30) DEFAULT NULL COMMENT 'iOS功能路径',
  `url_native_android` varchar(30) DEFAULT NULL COMMENT 'Android功能路径',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`router_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COMMENT='路由信息表；用于移动端拦截URL_Schema或WebView中点击的链接并转到原生页面';

DROP TABLE IF EXISTS  `sessions`;
CREATE TABLE `sessions` (
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `expires` int(11) unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS  `sku`;
CREATE TABLE `sku` (
  `sku_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'SKU ID',
  `biz_id` varchar(11) NOT NULL COMMENT '所属商家ID',
  `item_id` varchar(11) NOT NULL COMMENT '所属商品ID',
  `url_image` varchar(255) DEFAULT NULL COMMENT '图片；URL',
  `name_first` varchar(15) NOT NULL COMMENT '名称第一部分；例如“36码”',
  `name_second` varchar(15) DEFAULT NULL COMMENT '名称第二部分；例如“跟高8公分”',
  `name_third` varchar(15) DEFAULT NULL COMMENT '名称第三部分；例如“黑色”',
  `tag_price` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '原价/市场价（元）；最高99999.99',
  `price` decimal(7,2) unsigned NOT NULL COMMENT '现价（元）；最高99999.99',
  `stocks` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '库存量（单位）；最多65535',
  `weight_net` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '净重（KG）；最高999.99',
  `weight_gross` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '毛重（KG）；最高999.99',
  `weight_volume` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '体积重（KG）；最高999.99',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`sku_id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8 COMMENT='SKU信息表';

DROP TABLE IF EXISTS  `sms`;
CREATE TABLE `sms` (
  `sms_id` bigint(19) unsigned NOT NULL AUTO_INCREMENT COMMENT '短信ID',
  `mobile` varchar(11) DEFAULT NULL COMMENT '收信手机号',
  `mobile_list` tinyblob COMMENT '批量发信手机号；每个手机号之间以一个半角逗号分隔',
  `type` enum('1','2','9') NOT NULL DEFAULT '1' COMMENT '短信类型；1验证码2非验证码9通知类群发短信',
  `captcha` varchar(6) DEFAULT NULL COMMENT '验证码；仅验证码类型有此项',
  `content` varchar(67) NOT NULL COMMENT '短信内容；最多67个字符，非验证码类型有此项',
  `time` varchar(19) DEFAULT NULL COMMENT '批量发送定时；yyyy-mm-dd hh:ii:ss格式',
  `user_ip` varchar(39) DEFAULT NULL COMMENT '用户IP地址；支持IPv6',
  `time_expire` varchar(10) NOT NULL DEFAULT '' COMMENT '失效时间；UNIX时间戳',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`sms_id`)
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8 COMMENT='短信发送记录资料';

DROP TABLE IF EXISTS  `stuff`;
CREATE TABLE `stuff` (
  `stuff_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '员工ID',
  `user_id` varchar(11) NOT NULL COMMENT '用户ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属商家ID；系统员工留空',
  `fullname` varchar(12) NOT NULL COMMENT '姓名',
  `mobile` varchar(11) NOT NULL COMMENT '手机号',
  `password` varchar(40) NOT NULL COMMENT '员工操作密码',
  `role` enum('管理员','经理','成员','财务','客服','仓储','配送') NOT NULL DEFAULT '成员' COMMENT '角色',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '级别；0暂不授权，1普通员工，10门店级，20品牌级，30企业级',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) DEFAULT NULL COMMENT '创建者user_id',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('正常','冻结') DEFAULT '正常' COMMENT '状态；正常、冻结',
  PRIMARY KEY (`stuff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='员工信息表';

DROP TABLE IF EXISTS  `user`;
CREATE TABLE `user` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `identity_id` varchar(11) DEFAULT NULL COMMENT '个人认证ID',
  `password` varchar(40) DEFAULT NULL COMMENT '登录密码',
  `mobile` varchar(11) DEFAULT NULL COMMENT '手机号',
  `wechat_union_id` varchar(29) DEFAULT NULL COMMENT '微信union_id',
  `email` varchar(40) DEFAULT NULL COMMENT '电子邮件地址；最长40位',
  `nickname` varchar(12) DEFAULT NULL COMMENT '昵称；最多12个字符',
  `lastname` varchar(9) DEFAULT NULL COMMENT '姓氏；最多9个汉字“爨邯汕寺武穆云籍鞲”（这不是乱码，真的有这个姓氏啊我去……）',
  `firstname` varchar(6) DEFAULT NULL COMMENT '名；最多6个汉字中文最长名字是“欧阳成功奋发图强”，唉……',
  `gender` enum('女','男') DEFAULT NULL COMMENT '性别',
  `dob` date DEFAULT NULL COMMENT '出生日期；公历，YYYY-MM-DD',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像；图片URL',
  `level` tinyint(1) unsigned DEFAULT '0' COMMENT '平台会员级别',
  `address_id` varchar(11) DEFAULT NULL COMMENT '默认收货地址ID',
  `bank_name` varchar(20) DEFAULT NULL COMMENT '开户行名称；最短3位，最长20位中英文',
  `bank_account` varchar(30) DEFAULT NULL COMMENT '开户行账号；最长30位纯数字',
  `cart_string` mediumtext COMMENT '购物车内容;CSV格式的购物车项列表，商家ID|商品ID|规格ID|数量，最多50项',
  `promoter_id` varchar(11) DEFAULT NULL COMMENT '推广者ID',
  `time_create` varchar(10) NOT NULL DEFAULT '' COMMENT '创建时间；即注册时间，UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `last_login_timestamp` varchar(10) DEFAULT NULL COMMENT '最后登录时间；UNIX时间戳',
  `last_login_ip` varchar(39) DEFAULT NULL COMMENT '最后登录IP地址；兼容IPv6',
  `status` enum('正常','冻结') DEFAULT '正常' COMMENT '状态',
  PRIMARY KEY (`user_id`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=8050 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户信息表';

DROP TABLE IF EXISTS  `vote`;
CREATE TABLE `vote` (
  `vote_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '投票ID',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `url_name` varchar(30) DEFAULT NULL COMMENT '自定义URL；最多30个字符',
  `description` varchar(255) DEFAULT NULL COMMENT '描述；最多255个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '形象图；URL，后同',
  `url_audio` varchar(255) DEFAULT NULL COMMENT '背景音乐',
  `url_video` varchar(255) DEFAULT NULL COMMENT '形象视频',
  `url_video_thumb` varchar(255) DEFAULT NULL COMMENT '形象视频缩略图',
  `url_default_option_image` varchar(255) DEFAULT NULL COMMENT '选项默认占位图',
  `signup_allowed` enum('否','是') NOT NULL DEFAULT '否' COMMENT '可报名',
  `max_user_total` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '每选民最高总选票数',
  `max_user_daily` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '每选民最高日选票数',
  `max_user_daily_each` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '每选民同选项最高日选票数；即每人每天可向同一选项投几票',
  `exturl_before` varchar(255) DEFAULT NULL COMMENT '活动前相关外链；URL，后同',
  `exturl_ongoing` varchar(255) DEFAULT NULL COMMENT '活动中相关外链',
  `exturl_after` varchar(255) DEFAULT NULL COMMENT '活动后相关外链',
  `time_start` varchar(10) NOT NULL COMMENT '开始时间；UNIX时间戳，后同',
  `time_end` varchar(10) NOT NULL COMMENT '结束时间；UNIX时间戳',
  `time_create` varchar(10) NOT NULL COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`vote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='投票信息表';

DROP TABLE IF EXISTS  `vote_ballot`;
CREATE TABLE `vote_ballot` (
  `ballot_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '选票ID',
  `vote_id` varchar(11) NOT NULL COMMENT '所属投票ID',
  `option_id` varchar(20) NOT NULL COMMENT '候选项ID',
  `user_id` varchar(11) NOT NULL COMMENT '用户ID',
  `date_create` varchar(10) NOT NULL COMMENT '投票日期；格式为YYYY-MM-DD，例如2018-03-02',
  `time_create` varchar(10) NOT NULL COMMENT '投票时间；UNIX时间戳',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('正常','已冻结') DEFAULT '正常' COMMENT '状态',
  PRIMARY KEY (`ballot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=716424 DEFAULT CHARSET=utf8 COMMENT='投票选票信息表';

DROP TABLE IF EXISTS  `vote_option`;
CREATE TABLE `vote_option` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '候选项ID',
  `vote_id` varchar(11) NOT NULL COMMENT '所属投票ID',
  `tag_id` varchar(3) DEFAULT NULL COMMENT '所属标签ID',
  `index_id` smallint(5) unsigned DEFAULT NULL COMMENT '索引序号；最高65535',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `description` varchar(100) DEFAULT NULL COMMENT '描述；最多100个字符',
  `url_image` varchar(255) DEFAULT NULL COMMENT '形象图URL',
  `mobile` varchar(11) DEFAULT NULL COMMENT '审核联系手机号',
  `ballot_overall` int(11) unsigned DEFAULT '0' COMMENT '总票数；有史以来',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('正常','待审核','已拒绝') NOT NULL DEFAULT '正常' COMMENT '状态',
  PRIMARY KEY (`option_id`)
) ENGINE=InnoDB AUTO_INCREMENT=418 DEFAULT CHARSET=utf8 COMMENT='投票候选项信息表';

DROP TABLE IF EXISTS  `vote_tag`;
CREATE TABLE `vote_tag` (
  `tag_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `vote_id` varchar(11) NOT NULL COMMENT '所属投票ID',
  `name` varchar(30) NOT NULL COMMENT '名称；最多30个字符',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COMMENT='投票候选项标签信息表';

DROP TABLE IF EXISTS  `voucher`;
CREATE TABLE `voucher` (
  `order_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `biz_id` varchar(11) NOT NULL DEFAULT '1' COMMENT '商户ID',
  `biz_name` varchar(20) NOT NULL COMMENT '店铺名称；最多20个字符',
  `deal_id` varchar(20) NOT NULL COMMENT '商品ID',
  `deal_name` varchar(40) NOT NULL COMMENT '商品名称',
  `deal_slogan` varchar(255) DEFAULT NULL COMMENT '商品广告语',
  `user_id` varchar(11) NOT NULL COMMENT '用户ID',
  `user_ip` varchar(39) DEFAULT NULL COMMENT '用户下单IP地址；支持IPv6',
  `subtotal` decimal(7,2) unsigned NOT NULL COMMENT '小计（元）',
  `promotion_id` varchar(11) DEFAULT NULL COMMENT '营销活动ID',
  `discount_promotion` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '营销活动折抵金额（元）',
  `coupon_id` varchar(20) DEFAULT NULL COMMENT '优惠券ID',
  `discount_coupon` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '优惠券折抵金额（元）',
  `total` decimal(7,2) unsigned NOT NULL COMMENT '应支付金额（元）',
  `credit_id` bigint(20) unsigned DEFAULT NULL COMMENT '积分流水ID',
  `credit_payed` int(5) unsigned DEFAULT '0' COMMENT '积分支付金额（元）；最高99999',
  `total_payed` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '实际支付金额（元）',
  `total_refund` decimal(7,2) unsigned DEFAULT '0.00' COMMENT '实际退款金额（元）',
  `note_stuff` varchar(255) DEFAULT NULL COMMENT '员工留言；即处理订单的员工留言，最多255个字符',
  `reason_cancel` varchar(20) DEFAULT NULL COMMENT '取消原因；最多20个字符',
  `payment_type` enum('现金','银行转账','微信支付','支付宝','余额','待支付') DEFAULT '待支付' COMMENT '付款方式；余额、银行转账、微信支付、支付宝',
  `payment_account` varchar(50) DEFAULT NULL COMMENT '付款账号',
  `payment_id` varchar(255) DEFAULT NULL COMMENT '付款流水号',
  `commission` decimal(5,2) unsigned DEFAULT '0.00' COMMENT '佣金（元）；最高999.99',
  `promoter_id` varchar(11) DEFAULT NULL COMMENT '推广者ID',
  `code_string` varchar(12) DEFAULT NULL COMMENT '卡券验证码',
  `invoice_status` enum('未申请','待审核','待开票','待寄出','已寄出','已电开') NOT NULL DEFAULT '未申请' COMMENT '发票状态',
  `invoice_id` varchar(20) DEFAULT NULL COMMENT '发票ID',
  `time_create` varchar(10) NOT NULL COMMENT '用户下单时间；UNIX时间戳，下同',
  `time_cancel` varchar(10) DEFAULT NULL COMMENT '用户取消时间',
  `time_expire` varchar(10) DEFAULT NULL COMMENT '自动过期时间；创建后未付款',
  `time_pay` varchar(10) DEFAULT NULL COMMENT '用户付款时间',
  `time_refuse` varchar(10) DEFAULT NULL COMMENT '商家拒绝时间；系统自动发起退款',
  `time_accept` varchar(10) DEFAULT NULL COMMENT '商家接单时间',
  `time_valid` varchar(10) DEFAULT NULL COMMENT '商家验证时间',
  `time_comment` varchar(10) DEFAULT NULL COMMENT '用户评价时间',
  `time_refund` varchar(10) DEFAULT NULL COMMENT '商家退款时间',
  `time_delete` datetime DEFAULT NULL COMMENT '用户删除时间；仅用户取消、自动过期、用户确认等时间不为空的订单可被用户删除',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `operator_id` varchar(11) DEFAULT NULL COMMENT '最后操作者ID',
  `status` enum('待付款','待接单','待发货','待收货','待评价','已完成','已退款','已拒绝','已取消','已关闭','待使用') NOT NULL DEFAULT '待付款' COMMENT '订单状态',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品类订单信息表';

DROP TABLE IF EXISTS  `wechat_account`;
CREATE TABLE `wechat_account` (
  `account_id` int(11) NOT NULL COMMENT '账户ID',
  `biz_id` varchar(11) DEFAULT NULL COMMENT '所属biz_id',
  `email` varchar(50) NOT NULL COMMENT '登录Email',
  `password` varchar(40) NOT NULL COMMENT '登录密码',
  `qrcode` varchar(20) DEFAULT NULL COMMENT '二维码字符串',
  `origin_id` varchar(15) NOT NULL COMMENT '原始ID',
  `app_id` varchar(18) DEFAULT NULL COMMENT 'AppID(应用ID)',
  `app_secret` varchar(32) DEFAULT NULL COMMENT 'AppSecret(应用密钥)',
  `token` varchar(32) DEFAULT NULL COMMENT 'Token(令牌)',
  `aes_key` varchar(43) DEFAULT NULL COMMENT 'EncodingAESKey(消息加解密密钥)',
  `wepay_id` varchar(21) DEFAULT NULL COMMENT '微信支付登录用户名',
  `wepay_password` varchar(6) DEFAULT NULL COMMENT '微信支付登录密码',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) NOT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信公众平台账号信息表';

DROP TABLE IF EXISTS  `wechat_menu`;
CREATE TABLE `wechat_menu` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单项ID',
  `sub_button` varchar(255) DEFAULT NULL COMMENT '若该菜单项存在子项时必填',
  `type` enum('click','view','scancode_push','scancode_waitmsg','pic_sysphoto','pic_photo_or_album','pic_weixin','location_select','media_id','view_limited') NOT NULL DEFAULT 'click' COMMENT '事件类型',
  `name` varchar(32) NOT NULL COMMENT '菜单项文本字样；一级菜单最多4个汉字，二级菜单最多7个汉字，多出来的部分将会以“...”代替。',
  `url` varchar(255) DEFAULT NULL COMMENT '需转到的页面URL；事件类型为view时必填',
  `key` varchar(255) DEFAULT NULL COMMENT '事件类型不为click、media_id、view_limited时必填',
  `media_id` varchar(255) DEFAULT NULL COMMENT '事件类型为media_id、view_limited时必填',
  `time_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后操作时间',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  `operator_id` varchar(11) NOT NULL COMMENT '最后操作者ID',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信自定义菜单信息表';

DROP TABLE IF EXISTS  `wechat_reply`;
CREATE TABLE `wechat_reply` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自动回复项编号',
  `biz_id` tinyint(11) NOT NULL COMMENT '企业ID',
  `keywords` varchar(255) NOT NULL COMMENT '关键字们；最多10个汉字，关键字之间用一个空格分隔',
  `type` varchar(10) NOT NULL DEFAULT 'text' COMMENT '回复消息类型',
  `content` varchar(255) DEFAULT NULL COMMENT '回复内容；文本类型有此项',
  `media_id` varchar(255) DEFAULT NULL COMMENT '多媒体资源ID；图片、语音、视频类型有此项',
  `title` varchar(255) DEFAULT NULL COMMENT '标题；视频、音乐、图文类型有此项',
  `description` varchar(255) DEFAULT NULL COMMENT '描述；视频、音乐、图文类型有此项',
  `music_url` varchar(255) DEFAULT NULL COMMENT '音乐链接；音乐类型有此项',
  `hq_music_url` varchar(255) DEFAULT NULL COMMENT '高质量音乐链接，WIFI环境优先使用该链接播放音乐；音乐类型有此项',
  `thumb_media_id` varchar(255) DEFAULT NULL COMMENT '缩略图的媒体id；音乐ID有此项',
  `pic_url` varchar(255) DEFAULT NULL COMMENT '图片链接；图文类型有此项，接受jpg或png格式',
  `url` varchar(255) DEFAULT NULL COMMENT '跳转链接；图文消息有此项',
  `time_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `time_delete` datetime DEFAULT NULL COMMENT '删除时间',
  `time_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后编辑时间',
  `operator_id` varchar(11) NOT NULL COMMENT '最后操作人stuff_id',
  `creator_id` varchar(11) NOT NULL COMMENT '创建者ID',
  PRIMARY KEY (`item_id`),
  FULLTEXT KEY `keywords` (`keywords`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='微信公众平台自动回复信息表';

SET FOREIGN_KEY_CHECKS = 1;

/* PROCEDURES */;
DROP PROCEDURE IF EXISTS `get_order_items`;
DELIMITER $$
CREATE PROCEDURE `get_order_items`(
		IN p_order_id INT UNSIGNED 
)
    COMMENT 'return order items'
BEGIN
	/* 获取订单相关商品信息 */
	SELECT `order_id`, `item_id`,  `sku_id`, `count` FROM `order_items` WHERE `order_id` = p_order_id;
END
$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `stocks_update`;
DELIMITER $$
CREATE PROCEDURE `stocks_update`(
		IN p_table_name VARCHAR(4),
		IN p_id INT UNSIGNED ,
		in p_count INT UNSIGNED 
)
BEGIN
	IF (p_table_name = 'item') THEN
	UPDATE `item` SET `stocks` = `stocks` - p_count WHERE `item_id` = p_id;
	ELSE
	UPDATE `sku` SET `stocks` = `stocks` - p_count WHERE `sku_id` = p_id;
	end if;
END
$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `update_vote_option_ballot_overall`;
DELIMITER $$
CREATE PROCEDURE `update_vote_option_ballot_overall`(
		IN p_option_id INT UNSIGNED 
)
BEGIN

	update `vote_option`
	set `ballot_overall` = (
		SELECT count(*)
		FROM `vote_ballot`
		WHERE option_id = p_option_id
        AND `time_delete` IS NULL
		AND `status`= '正常')
	WHERE option_id = p_option_id;

END
$$
DELIMITER ;

/* EVENTS */;
DROP EVENT IF EXISTS `data_generate_order_total`;
DELIMITER $$
CREATE EVENT `data_generate_order_total` ON SCHEDULE EVERY 5 MINUTE STARTS '2018-01-01 00:00:01' ON COMPLETION PRESERVE ENABLE COMMENT '生成总订单数据' DO begin

/**每5分钟生成0-12的数据**/
INSERT INTO data_order_total
(`value`, `year`, `month`, `day`, `hour`, `minute`, `time_create`)
VALUES
(ROUND(RAND() * 12 + 0), date_format(now(),'%y'), date_format(now(),'%m'), date_format(now(),'%d'), date_format(now(),'%k'), date_format(now(),'%i'), unix_timestamp());

end
$$
DELIMITER ;

DROP EVENT IF EXISTS `data_generate_pv_total`;
DELIMITER $$
CREATE EVENT `data_generate_pv_total` ON SCHEDULE EVERY 1 SECOND STARTS '2018-01-01 00:00:01' ON COMPLETION PRESERVE ENABLE COMMENT '生成总PV数据' DO begin

/**每5分钟生成1-3的数据**/
INSERT INTO data_pv_total
(`value`, `year`, `month`, `day`, `hour`, `minute`, `second`, `time_create`)
VALUES
(ROUND(RAND() * 2 + 1), date_format(now(),'%y'), date_format(now(),'%m'), date_format(now(),'%d'), date_format(now(),'%k'), date_format(now(),'%i'), date_format(now(),'%s'), unix_timestamp());

end
$$
DELIMITER ;

DROP EVENT IF EXISTS `data_generate_uv_biz`;
DELIMITER $$
CREATE EVENT `data_generate_uv_biz` ON SCHEDULE EVERY 5 MINUTE STARTS '2018-01-01 00:00:01' ON COMPLETION PRESERVE ENABLE COMMENT '生成商家UV数据' DO begin

/**每5分钟生成100-400的数据**/
INSERT INTO data_uv_biz
(`value`, `year`, `month`, `day`, `hour`, `minute`, `time_create`)
VALUES
(ROUND(RAND() * 300 + 100), date_format(now(),'%y'), date_format(now(),'%m'), date_format(now(),'%d'), date_format(now(),'%k'), date_format(now(),'%i'), unix_timestamp());

end
$$
DELIMITER ;

DROP EVENT IF EXISTS `data_generate_uv_total`;
DELIMITER $$
CREATE EVENT `data_generate_uv_total` ON SCHEDULE EVERY 5 MINUTE STARTS '2018-01-01 00:00:01' ON COMPLETION PRESERVE ENABLE COMMENT '生成总UV数据' DO begin

/**每5分钟生成500-800的数据**/
INSERT INTO data_uv_total
(`value`, `year`, `month`, `day`, `hour`, `minute`, `time_create`)
VALUES
(ROUND(RAND() * 300 + 500), date_format(now(),'%y'), date_format(now(),'%m'), date_format(now(),'%d'), date_format(now(),'%k'), date_format(now(),'%i'), unix_timestamp());

end
$$
DELIMITER ;

DROP EVENT IF EXISTS `minute_cleaning`;
DELIMITER $$
CREATE EVENT `minute_cleaning` ON SCHEDULE EVERY 1 MINUTE STARTS '2018-01-01 00:00:01' ON COMPLETION PRESERVE ENABLE COMMENT '每分钟清理\n优化常用表' DO begin

delete from `captcha` where `time_expire` < unix_timestamp();
delete from `sms` where `time_expire` < unix_timestamp();
delete from `ci_sessions_web` where `timestamp` < (unix_timestamp() - 60*60*24*30);
delete from `ci_sessions_biz` where `timestamp` < (unix_timestamp() - 60*60*24*7);
delete from `ci_sessions_admin` where `timestamp` < (unix_timestamp() - 60*60*24*3);

/* 关闭 创建3小时后未付款的订单 */
update `order` set `status` = '已关闭', `time_expire` = unix_timestamp() where `status` = '待付款' and `time_pay` IS NULL and `time_create` < (unix_timestamp() - 60*60*3);
/* 物理删除 已关闭或已取消3天的订单 */
delete from `order` where  `status` = '已关闭' AND `time_expire` < (unix_timestamp() - 60*60*24*3);
delete from `order` where  `status` = '已取消' AND `time_cancel` < (unix_timestamp() - 60*60*24*3);
/* 发起退款 已付款3天的待发货订单 */
update `order` set `status` = '待退款' where `status` = '待发货' AND `time_pay` < (unix_timestamp() - 60*60*24*3);
/* 确认收货 已发货7天的待确认订单 */
update `order` set `status` = '待评价' and `time_confirm` = unix_timestamp() where `status` = '待收货' AND `time_deliver` < (unix_timestamp() - 60*60*24*7);
/* 评价 已收货30天的订单 */
update `order` set `status` = '已完成' and `time_comment` = unix_timestamp() where `status` = '待评价' AND `time_confirm` < (unix_timestamp() - 60*60*24*30);

/* 物理删除 已删除3天的收货地址 */
delete from `address` where unix_timestamp(`time_delete`) < (unix_timestamp() - 60*60*24*3);

update `coupon` set `time_expire` = unix_timestamp() where time_expire IS NULL AND time_used IS NULL and `time_end` < unix_timestamp(); /* 删除未使用且已超过有效期的优惠券 */
delete from `coupon` where `time_expire` < (unix_timestamp() - 60*60*24*3); /* 删除已过期3天的优惠券 */
delete from `coupon` where `time_used` < (unix_timestamp() - 60*60*24*30); /* 删除已使用30天的优惠券 */
delete from `coupon` where `time_delete` IS NOT NULL;

delete from `comment_item` where `time_delete` IS NOT NULL;

OPTIMIZE TABLE `ci_sessions_web`, `ci_sessions_biz`, `address`, `article`, `article_biz`, `biz`, `branch`, `comment_item`, `coupon`, `coupon_combo`, `coupon_template`, `deal`, `fav_biz`, `fav_item`, `item`, `order`, `order_items`, `sku`, `stuff`, `user`, `voucher`, `vote_option`, `vote_ballot`;

end
$$
DELIMITER ;

