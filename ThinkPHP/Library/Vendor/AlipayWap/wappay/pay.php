<?php
/* *
 * 功能：支付宝手机网站支付接口(alipay.trade.wap.pay)接口调试入口页面
 * 版本：2.0
 * 修改日期：2016-11-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 请确保项目文件有可写权限，不然打印不了日志。
 */

class AlipayWap
{
    public function pay()
    {
        //header("Content-type: text/html; charset=utf-8");
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'service/AlipayTradeService.php';
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'buildermodel/AlipayTradeWapPayContentBuilder.php';
        //require dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../config.php';
        /**************************请求参数**************************/
        if(!$order_sn)
            $ordernum = $_GET['order_sn'];
        else
            $ordernum =$order_sn;

        $order = M('order')->where(array('order_sn'=>$ordernum))->find();

        if(!$order){
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在')));
        }

        //服务器异步通知页面路径
        $notify_url = C('HTTP_URL') . '/Api/Alipay/alipayendpay';

        //商户订单号
        $out_trade_no = $order['order_sn'];
        //商户网站订单系统中唯一订单号，必填

        //订单名称
        $subject = '拼趣多商品支付';
        //必填

        //付款金额
        $total_fee = $order['order_amount'];
        //必填
        //订单描述
        $body = M('goods')->where(array('goods_id'=>$order['goods_id']))->getField('goods_name');
        //商品展示地址

        //超时时间
        $timeout_express="30m";

        $config = C('alipay_config');

        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_fee);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $payResponse = new AlipayTradeService($config);
        $result = $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        return ;
    }
}


