<?php
namespace Api_2_0_1\Controller;
use Think\Controller;

class AlipayWapController extends BaseController
{

    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('AlipayWap.AopSdk');
        vendor('AlipayWap.wappay.buildermodel.AlipayTradeWapPayContentBuilder');
        vendor('AlipayWap.wappay.service.AlipayTradeService');
    }

    /**
     * 支付宝预支付
     */
    public function addAlipayOrder($order_sn="",$user_id="",$goods_id="")
    {
        /**************************请求参数**************************/
        if(!$order_sn)
            $ordernum = $_GET['order_sn'];
        else
            $ordernum =$order_sn;

        $order = M('order')->where(array('order_sn'=>$ordernum))->find();

        if(!$order){
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在')));
        }

        //商户订单号, 商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order['order_sn'];
        //订单名称 必填
        $subject = '拼趣多商品支付';
        //付款金额
        $total_fee = $order['order_amount'];
        //订单描述
        $body = M('goods')->where(array('goods_id'=>$order['goods_id']))->getField('goods_name');

        $config = C('alipay_wap');

        //服务器异步通知页面路径
        $config['notify_url'] = C('HTTP_URL') . '/Api_2_0_1/AlipayWap/alipayendpay';
        if($order['prom_id']){
            $prom_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
            $type = $prom_info['mark'] > 0 ? 1 : 0;
            $config['return_url'] =C('SHARE_URL').'/order_detail.html?order_id='.$prom_info['order_id'].'&type='.$type.'&user_id='.$order['user_id'];
        }else{
            $config['return_url'] =C('SHARE_URL').'/order_detail.html?order_id='.$order['order_id'].'&type=2&user_id='.$order['user_id'];;
        }

        $timeout_express="30m";

        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();

        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_fee);
        $payRequestBuilder->setTimeExpress($timeout_express);
        $payResponse = new \AlipayTradeService($config);
        $result = $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);


        $orderdetail = array('alipay_text' => $result);
        if($_GET['order_sn'])
        {
            exit(json_encode(array('status'=>1,'msg'=>'支付宝预支付订单生成成功','data'=>$orderdetail)));
        }else {
            return $orderdetail;
        }
    }

    /**
     * 支付宝支付回调函数
     */
    public function alipayendpay()
    {
        vendor('Alipay.AlipayNotify');

        //这里还是通过C函数来读取配置项，赋值给$alipay_config
//		$alipay_config=C('alipay_config');
//		//计算得出通知验证结果
//		$alipayNotify = new \AlipayNotify($alipay_config);
//		$verify_result = $alipayNotify->verifyNotify();

        //商户订单号
        $order_sn = $_POST['out_trade_no'];

        //支付宝交易号
        $out_trade_no = $_POST['trade_no'];

        //交易状态
        $trade_status = $_POST['trade_status'];

        if ($_POST['trade_status'] == 'TRADE_FINISHED') {
            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序

            //注意：
            //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }else if ($_POST['trade_status'] == 'TRADE_SUCCESS'){

            M()->startTrans();

            $where=array('order_sn'=>$order_sn);
            $order=M('order')->where($where)->find();

            if($order['pay_status']==1){
                echo "success";        //如果支付成功 直接放回success
                exit();
            }

            $res = $this->changeOrderStatus($order);

            if(!$res)
            {
                M()->rollback();
            }

            if($order['prom_id']){
                $res2 = $this->Join_Prom($order['prom_id']);
                if($res2){
                    M()->commit();
                    $group_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
                    if($group_info['mark'] > 0){
                        $nums = M('group_buy')->where('(`mark`='.$group_info['mark'].' or `id`='.$group_info['mark'].') and `is_pay`=1')->count();
                        if(($nums)==$group_info['goods_num'])
                        {
                            $Goods = new BaseController();
                            $Goods->getFree($group_info['mark'],$order);
                        }
                        M('group_buy')->where(array('id'=>$group_info['mark']))->setInc('order_num');
                        M('group_buy')->where(array('mark'=>$group_info['mark']))->save(array('order_num'=>$nums+1));
                    }
                }else{
                    M()->rollback();
                    exit();
                }
            }else{
                M()->commit();
            }

        }
        echo "success";        //请不要修改或删除
    }

    //开团 参团的时候在支付完成时将is_pay字段改变，标示加入团成功
    public function Join_Prom($order_id)
    {
        $data['is_pay']=1;
        $res = M('group_buy')->where('`id`='.$order_id)->data($data)->save();
        return $res;
    }
//    public function TuiSong($order_id)
//    {
//        $order = M('order')->where('`order_id`='.$order_id)->find();
//        $prom = M('group_buy')->where('`id`='.$order['prom_id'])->find();
//        if($prom['mark']!=0)
//        {
//            return null;
//        }
//        $user = M('users')->where('`user_id`='.$order['user_id'])->field('mobile,niclname,head_pic')->find();
//        if($user['nickname']!=null)
//        {
//            $name = $user['nickname'].' 刚刚发布了一个新订单';
//        }else{
//            $name = substr_replace($user['mobile'], '****', 3, 4);
//            $name = $name.' 刚刚发布了一个新订单';
//        }
//        SendAllXinge($name,array('order_id'=>$order_id,'user_logo'=>$user['head_pic']));
//    }
}