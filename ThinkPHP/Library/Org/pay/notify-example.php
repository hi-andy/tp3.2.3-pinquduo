<?php
/**
 * 支付回调使用示例
 */
use pay\QQPay;

$qqPay = new QQPay();

list($status, $data) = $qqPay->checkNotify();

if ($status === 0) {
	// TODO: 支付成功的业务逻辑
    // $data 字段参见 QQPay::checkNotify()相关注释
	// 处理成功后给QQ钱包返回成功，免得重复回调
	$qqPay->successAck();
} else {
	echo "Failed: ", $data;
}