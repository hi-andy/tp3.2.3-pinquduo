
ALTER TABLE `tp_store_detail` ADD `yyzz_img` TEXT NOT NULL COMMENT '营业执照' , ADD `zzjg_img` TEXT NOT NULL COMMENT '组织架构' , ADD `shxy_img` TEXT NOT NULL COMMENT '社会信用' ;

ALTER TABLE `tp_order` ADD `coupon_list_id` TINYINT(5) NULL DEFAULT NULL COMMENT '将使用的优惠卷id存放在这' AFTER `coupon_price`;

ALTER TABLE `tp_return_goods` ADD `store_id` INT(11) NOT NULL COMMENT '商户id' ;
ALTER TABLE `tp_order` ADD `out_refund_no` VARCHAR(100) NOT NULL DEFAULT '0' COMMENT '退款单号' ;

/*
 * author : Fable
 * date : 2016-11-16 10:39
 * 商户惩罚表
 */
ALTER TABLE `tp_store_detail` ADD `sbzm_imgs` TEXT NOT NULL COMMENT '商标注册证明' , ADD `ppsq_imgs` TEXT NOT NULL COMMENT '品牌授权证明' , ADD `zjbg_imgs` TEXT NOT NULL COMMENT '质检报告' ;

ALTER TABLE `tp_store_detail` ADD `margin` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '保证金' ;

/*
 * author : Fable
 * date : 2016-11-16 10:39
 * 商户惩罚表
 */
CREATE TABLE IF NOT EXISTS `tp_store_punishment` (
  `sp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '惩罚id',
  `store_id` int(11) NOT NULL COMMENT '商户id',
  `store_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商户名称',
  `order_id` int(11) NOT NULL COMMENT '订单id',
  `order_sn` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单编号',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付款金额',
  `sp_penal_sum` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '罚金',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '惩罚理由',
  `admin_id` int(11) NOT NULL COMMENT '操作人id',
  `admin_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作人姓名',
  `datetime` datetime NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`sp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户惩罚表' AUTO_INCREMENT=1 ;

ALTER TABLE `tp_store_punishment`  ADD `status` TINYINT(1) NOT NULL COMMENT '1-已处理,2-已撤销'  AFTER `admin_name`;
ALTER TABLE `tp_merchant` ADD `margin` DECIMAL(10,2) NOT NULL COMMENT '保证金' ;

/*
 *author : yinhai 
 *date : 2016-08-04 18:51
 *remark : 用户标签数据表
 */
DROP TABLE IF EXISTS `tp_user_label`;
CREATE TABLE `tp_user_label` (
  `id` int(5) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(60) NOT NULL COMMENT '标签名',
  `parent_id` varchar(5) NOT NULL COMMENT '父ID',
  `level` smallint(1) NOT NULL DEFAULT '1' COMMENT '等级',
  `is_show` smallint(1) NOT NULL DEFAULT '1' COMMENT '是否显示，0，显示  1、不显示',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*
 *author : yinhai 
 *date : 2016-08-03 19:05
 *remark : 商品标签数据表
 */
DROP TABLE IF EXISTS `tp_goods_label`;
CREATE TABLE `tp_goods_label` (
  `id` int(5) NOT NULL AUTO_INCREMENT COMMENT '标签id',
  `name` varchar(90) NOT NULL DEFAULT '' COMMENT '标签分类名称',
  `parent_id` smallint(5) NOT NULL DEFAULT '0' COMMENT '父id',
  `level` tinyint(1) DEFAULT NULL COMMENT '级别',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;

/*
 *author : yinhai 
 *date : 2016-08-03 19:07
 *remark : 商户后台
 */
DROP TABLE IF EXISTS `tp_merchant`;
CREATE TABLE `tp_merchant` (
  `merchant_id` smallint(5) NOT NULL AUTO_INCREMENT COMMENT '商户ID',
  `merchant_name` varchar(60) NOT NULL DEFAULT '' COMMENT '商户名',
  `email` varchar(60) NOT NULL DEFAULT '' COMMENT 'email',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `last_login` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  PRIMARY KEY (`merchant_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;