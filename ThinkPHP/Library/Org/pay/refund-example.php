<?php
/*
 * 调用退款使用示例
 */
use pay\QQPay;

$certConfig = array(
    'certFile'      => 'application_cert.pem',
    'keyFile'       => 'application_key.pem',
    'cacertFile'    => 'cacert.pem',
    'opUserPassMd5' => '9i23iresrfwsrweoruiweriousf'
);

$qqPay = new QQPay($certConfig);

$orderSn = ''; // 订单号
$refundSn  = ''; // 退款单号
$refundFee = ''; // 退款金额，单位分

list($status, $data) = $qqPay->refund($orderSn, $refundSn, $refundFee);

if ($status === 0) {
    // 成功
    // 处理退款成功的业务逻辑
    // $data 字段见 QQPay::refund()相关注释
} else {
    echo "Failed: ", $data;
}