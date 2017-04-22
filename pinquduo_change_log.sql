/**
 *兑吧订单表
 */
CREATE TABLE IF NOT EXISTS `tp_duiba_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `credits` int(11) NOT NULL COMMENT '扣除的积分',
  `params` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '详情参数，不同的类型，返回不同的内容',
  `ip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP地址',
  `sign` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '签名',
  `timestamp` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '时间戳',
  `actualPrice` int(11) NOT NULL COMMENT '实际扣除开发者账户费用，单位为分',
  `description` int(11) NOT NULL COMMENT '描述',
  `facePrice` int(11) NOT NULL COMMENT '兑换商品的市场价值，单位是分',
  `duiba_orderNum` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '兑吧订单编号',
  `datetime` datetime NOT NULL COMMENT '添加时间',
  `order_num` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内部订单编号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='兑吧订单表' AUTO_INCREMENT=1 ;
ALTER TABLE `tp_duiba_order` ADD `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '订单状态 1-已扣积分 2-兑换成功' ;



/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 签到表
*/
CREATE TABLE `pinquduo`.`tp_signin` ( 
	`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '记录id' ,
	 `datetime` DATE NOT NULL COMMENT '签到时间' , 
	 `user_id` varchar(50) NOT NULL COMMENT '用户id' , 
	 PRIMARY KEY (`id`)
	 ) ENGINE = InnoDB COMMENT = '签到表';

/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 团购类型
*/
DROP TABLE IF EXISTS `tp_group_type`;
CREATE TABLE IF NOT EXISTS `tp_group_type` (
  `gt_id` int(11) NOT NULL COMMENT '团购类型id',
  `numbers` int(11) NOT NULL COMMENT '人数',
  `text` varchar(20) NOT NULL COMMENT '文本',
  `addtime` datetime NOT NULL COMMENT '添加权限'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 订单表修改
*/
ALTER TABLE `tp_order` ADD `is_group` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否是团购 0-不是 1-是' AFTER `parent_sn`;

ALTER TABLE `tp_order` ADD `gb_id` INT(11) NOT NULL DEFAULT '0' COMMENT '团购id' AFTER `is_group`;


/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 参团组表
*/
DROP TABLE IF EXISTS `tp_group_join`;

CREATE TABLE IF NOT EXISTS `tp_group_join` (
 
`joinid` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录id',
  
`user_id` int(11) NOT NULL COMMENT '用户id',
  
`gb_id` int(11) NOT NULL COMMENT '参团id',
  
`goods_id` int(11) NOT NULL COMMENT '商品id',
  
`join_time` datetime NOT NULL COMMENT '参团时间',
  
`team_id` int(11) NOT NULL COMMENT '组id',
  PRIMARY KEY (`joinid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='参团组表';


/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 中奖人表 
*/
DROP TABLE IF EXISTS `tp_win_user`;

CREATE TABLE IF NOT EXISTS `tp_win_user` (
  `id` int(11) NOT NULL COMMENT '记录id',
  `gb_id` int(11) NOT NULL COMMENT '团购id',
  `user_id` int(11) NOT NULL COMMENT '中奖人id',
  `nickname` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='中奖人表'


/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品表
 */
ALTER TABLE `tp_goods` 
ADD COLUMN `store_id`  int UNSIGNED NOT NULL COMMENT '门店id' AFTER `store_id`;

ALTER TABLE `tp_goods` 
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;


/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 团购规则 
*/
ALTER TABLE `tp_group_buy` CHANGE `rule` `rule` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '团购规则';
ALTER TABLE `tp_group_buy` ADD `rule` TEXT NOT NULL AFTER `is_show`;
ALTER TABLE `tp_group_buy` ADD `store_id` INT(11) NOT NULL COMMENT '商户id' AFTER `rule`;


/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品类型表
 */
ALTER TABLE `tp_goods_type` 
ADD COLUMN `store_id`  int UNSIGNED NOT NULL COMMENT '门店id' AFTER `store_id`;

ALTER TABLE `tp_goods_type` 
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;



/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品规格表
 */
ALTER TABLE `tp_spec` 
ADD COLUMN `store_id`  int UNSIGNED NOT NULL COMMENT '门店id' AFTER `store_id`;

ALTER TABLE `tp_spec` 
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;



/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品属性表
 */
ALTER TABLE `tp_goods_attribute` 
ADD COLUMN `store_id`  int UNSIGNED NOT NULL COMMENT '门店id' AFTER `store_id`;

ALTER TABLE `tp_goods_attribute` 
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;




/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品品牌表
 */
ALTER TABLE `tp_brand`
ADD COLUMN `store_id`  int UNSIGNED NOT NULL COMMENT '门店id' AFTER `store_id`;

ALTER TABLE `tp_brand`
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;



/*
 *author : 苏家浩
 *date : 2016-08-25 17:51
 *remark : 总后台商品分类表
 */
ALTER TABLE `tp_goods_category`
ADD COLUMN `is_show`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '删除，1显示，0不显示' AFTER `is_show`;


/*
 *author : 吴银海
 *date : 2016-08-26 11:15
 *remark : 门户后台订单表
 */
ALTER TABLE `tp_order` CHANGE `discount` `discount` DECIMAL(10,2) NULL DEFAULT '0.00' COMMENT '价格调整';


/*
 *author : 吴银海
 *date : 2016-08-26 11:15
 *remark : 首页表
 */
DROP TABLE IF EXISTS `tp_group_category`;
CREATE TABLE `tp_group_category` (
  `id` int(50) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `cat_name` varchar(10) NOT NULL,
  `cat_img` varchar(255) DEFAULT NULL COMMENT '分类图片',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


/*
 *author : 吴银海
 *date : 2016-08-26 11:15
 *remark : 海淘表
 */
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tp_haitao
-- ----------------------------
DROP TABLE IF EXISTS `tp_haitao`;
CREATE TABLE `tp_haitao` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '海淘首页菜单id',
  `name` varchar(10) NOT NULL COMMENT '菜单名',
  `link` varchar(50) DEFAULT NULL COMMENT '菜单链接',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

/*
 *author : 吴银海
 *date : 2016-09-27 15:21
 *remark : 用户表
 */
ALTER TABLE `tp_users` ADD `integral` INT(5) NOT NULL DEFAULT '0' COMMENT '用户积分' AFTER `is_lock`;

/*
 *author : 吴银海
 *date : 2016-09-27 20:05
 *remark : 优惠卷表
 */
ALTER TABLE `tp_coupon_list` ADD `store_id` INT(5) NOT NULL DEFAULT '0' COMMENT '商户id' AFTER `is_use`;