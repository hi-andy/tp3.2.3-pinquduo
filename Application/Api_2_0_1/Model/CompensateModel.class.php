<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 李江鹏 <mercury@jozhi.com.cn> <http://www.jozhi.com.cn>
// +----------------------------------------------------------------------
namespace Api_2_0_1\Model;
use Think\Model;
class CompensateModel extends Model {
	//自动验证
	protected $_validate = array(
			//字段名		验证规则	 提示错误
            array("user_id",        'require', "您尚未登录，请先登录",  self::MUST_VALIDATE),
			array("goods_price",    'require', "请输入购买价格", self::MUST_VALIDATE),
            array("bought_date",    'require', "请选择购买日期", self::MUST_VALIDATE),
			array("other_name",     'require', "请输入其它购买平台名称", self::MUST_VALIDATE),
			array("other_price",    'require', "请输入其它平台购买价格", self::MUST_VALIDATE),
			array("other_date",     'require', "请选择其它平台购买日期", self::MUST_VALIDATE),
            array("mobile",         'require', "请输入您的联系电话", self::MUST_VALIDATE),
            array("qq",             'require', "请输入您的联系QQ", self::MUST_VALIDATE),
            array("alipay",         'require', "请输入您的支付宝账户，若审核成功以接收退款", self::MUST_VALIDATE),
            array("picture",        'require', "请上传凭证图片", self::MUST_VALIDATE),
	);
	//　自动完成
	protected $_auto = array(
			array('create_time', NOW_TIME, self::MODEL_INSERT),
			array('update_time', NOW_TIME, self::MODEL_UPDATE),
	);
}