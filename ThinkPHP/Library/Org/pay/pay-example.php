<?php
/**
 * 调起支付使用示例
 */
use pay\QQPay;

$qqPay = new QQPay();

$orderSn = ''; // 生成订单号
$amount  = 1; // 计算支付金额，单位：分
$notifyUrl = ''; // 设置支付异步回调地址

list($status, $data) = $qqPay->unifyOrder($orderSn, $amount, $notifyUrl);

if ($status === 0) {
	// 成功
	echo $qqPay->createPayScript($data); // 输出支付js脚本
} else {
	echo "Failed: ", $data;
}