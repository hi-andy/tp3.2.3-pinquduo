<?php
namespace Store\Controller;

use Api\Controller\QQPayController;
use Store\Logic\OrderLogic;
/*
 * 团购订单管理
 */
class MiddlewareController  {

    public function account_edit(){

        $order_id = I('order_id');
        $order = M('order')->where('`order_id`='.$order_id)->find();
        $Order_Logic = new OrderLogic();
        if($order['order_type']==9 || $order['order_type']==7)
        {
            echo json_encode(array('status'=>2,'msg'=>'已退款'));
            die;
        }

        if($order['pay_code']=='weixin')
        {
            if ($order['is_jsapi']==1){
                $res = $Order_Logic->weixinJsBackPay($order['order_sn'], $order['order_amount']);
            }else{
                $res = $Order_Logic->weixinBackPay($order['order_sn'], $order['order_amount']);
            }
        }elseif($order['pay_code']=='alipay' || $order['pay_code']=='alipay_wap'){

            $res = $Order_Logic->alipayBackPay($order['order_sn'],$order['order_amount']);
        }elseif($order['pay_code'] == 'qpay'){
            $qqPay = new QQPayController();
            $res = $qqPay->doRefund($order['order_sn'], $order['order_amount']);
        }
        $result = M('return_goods')->where('order_id='.$order_id)->field('type')->find();
        if($res['status'] == 1){
            if($result['type']==0)
            {
                //退货
                $data['order_status'] = 7;
                $data['order_type'] = 9;
                $this->fallback($order);
            }elseif($result['type']==1)
            {
                //换货
                $data['order_status'] = 5;
                $data['order_type'] = 7;
            }
            $base = new \Api_2_0_2\Controller\BaseController();
            $base->order_redis_status_ref($order['user_id']);
            M('return_goods')->where('order_id='.$order_id)->save(array('status'=>3));
            M('order')->where('`order_id`='.$order_id)->data($data)->save();
            echo json_encode(array('status'=>1,'msg'=>'退款成功'));
        }else{
            
            echo json_encode(array('status'=>0,'msg'=>'退款失败'));
        }
        die;
    }








}