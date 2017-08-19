<?php
/**
 * 抽奖订单管理控制器
 * Author: Hua
 */
namespace Admin\Controller;

use Api\Controller\QQPayController;
use Think\AjaxPage;
use Think\Controller;
use Api_2_0_1\Controller\WxtmplmsgController;
use Admin\Logic\OrderLogic;

class AwardOrderController extends Controller {

    public  $order_status;
    public  $shipping_status;
    public  $pay_status;
    /*
     * 初始化操作
     */
    public function _initialize() {
        C('TOKEN_ON',false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
    }
    // 订单列表页
    public function orderList()
    {
        // 搜索条件
        $condition = array();
        $goodsId = I('goods_id');
        $condition['o.goods_id'] = $goodsId;

        $count = M('group_buy')->alias('g')
            ->join('left JOIN tp_users u on g.user_id = u.user_id ')
            ->join('left JOIN tp_order o on o.order_id = g.order_id')
            ->where($condition)
            ->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }

        $show = bootstrap_page_style($Page->show());
        //echo M('group_buy')->fetchSql('true')->alias('g')
        $orderList = M('group_buy')->alias('g')
            ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
            ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
            ->where($condition)
            ->field('g.id,g.start_time,g.end_time,g.goods_name,g.price,g.goods_price,g.is_successful,u.nickname,o.order_sn,o.order_id,o.is_award,o.pay_time')
            ->limit($Page->firstRow,$Page->listRows)
            ->select();

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('goods_id', $goodsId);

        $this->display();
    }
    /**
     * 根据活动商品，显示相关订单列表
     */
    public function ajaxOrderList()
    {
        // 搜索条件
        $condition = array();
        $goodsId = I('goods_id');
        $orderSn = I('order_sn');
        $condition['o.goods_id'] = $goodsId;

        if($timeRange = I('timeRange')){
            list($begin, $end) = explode('-', $timeRange);
            $begin = strtotime($begin);
            $end = strtotime($end);

            //　时间范围
            if($begin && $end){
                $condition['_string'] = "o.add_time > $begin && o.add_time < $end";
            }
        }
        //订单号搜索
        if($orderSn) {
            $condition['o.order_sn'] = $orderSn;
        }

        // 成团/未成团
        if(I('Open_group')){
            if(I('Open_group')==1){
                $condition['g.is_successful'] = 1;
            }elseif(I('Open_group')==2){
                $condition['g.is_successful'] = 0;
            }
        }

        $count = M('group_buy')->alias('g')
            ->join('left JOIN tp_users u on g.user_id = u.user_id ')
            ->join('left JOIN tp_order o on o.order_id = g.order_id')
            ->where($condition)
            ->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }

        $show = bootstrap_page_style($Page->show());
        //echo M('group_buy')->fetchSql('true')->alias('g')
        $orderList = M('group_buy')->alias('g')
            ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
            ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
            ->where($condition)
            ->field('g.id,g.start_time,g.end_time,g.goods_name,g.price,g.goods_price,g.is_successful,u.nickname,o.order_sn,o.order_id,o.is_award,o.pay_time')
            ->limit($Page->firstRow,$Page->listRows)
            ->select();

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('goods_id', $goodsId);
        $this->assign('order_sn', $orderSn);
        $this->assign('timeRange', $timeRange);
        $this->assign('openGroup', I('Open_group'));
        $this->display();
    }

    /*
     * 设指定订单为中奖，并推送中奖消息。
     */
    public function orderAward()
    {
        $condition['order_id']  = I('order_id');
        //订单信息
        $orderInfo = M('order')->field('user_id,order_sn,total_amount,goods_id')->where($condition)->find();
        // 商品名称
        $goods_name = M('goods')->where('goods_id='.$orderInfo['goods_id'])->getField('goods_name');
        // 订单设为中奖
        M('order')->where($condition)->setField('is_award', 1);
        // 微信推送中奖消息
        $openid = M('users')->where('user_id='.$orderInfo['user_id'])->getField('openid');
        $wxPush = new WxtmplmsgController();
        $result = $wxPush->award_notify($openid,'恭喜！您参与的活动已中奖！！！',$orderInfo['total_amount'],$goods_name,$orderInfo['order_sn']);
        $this->ajaxReturn('操作成功！');
    }

    /**
     * 未中奖的订单处理，批量推送消息
     */
    public function noAwardOrderPush()
    {
        if ($dataRange = I('dateRange')) {
            list($begin, $end) = explode('-', $dataRange);
            $begin = strtotime($begin);
            $end = strtotime($end);
        } else {
            $this->ajaxReturn('请选取时间范围！');
        }
        $goods_id = I('goods_id');
        // 商品名称
        $goods_name = M('goods')->where('goods_id='.$goods_id)->getField('goods_name');
        // 所有未中奖的订单信息
        $orderInfo = M('order')->field('order_id,user_id,order_amount,order_sn,is_jsapi,pay_code')->where('goods_id='.$goods_id.' and add_time>'.$begin.' and add_time<'.$end.' and is_award=0')->select();
        $orderLogic = new OrderLogic();
        foreach($orderInfo as $value) {
            // 未中奖订单执行退款  Hua 2017-8-10
            $result = '';
            if ($value['pay_code'] == 'weixin') {
                if ($value['is_jsapi'] == 1) {
                    $result = $orderLogic->weixinJsBackPay($value['order_sn'], $value['order_amount']);
                } else {
                    $result = $orderLogic->weixinBackPay($value['order_sn'], $value['order_amount']);
                }
            } elseif ($value['pay_code'] == 'alipay') {
                $result = $orderLogic->alipayBackPay($value['order_sn'], $value['order_amount']);
            } elseif ($value['pay_code'] == 'qpay') {
                $qqPay = new QQPayController();
                $result = $qqPay->doRefund($value['order_sn'], $value['order_amount']);
            }
            if ($result['status'] == 1) {
                $data['order_status'] = 10;
                $data['order_type'] = 13;
                $data['is_award'] = -1; // 未中奖订单状态
                M('order')->where('`order_id`=' . $value['order_id'])->data($data)->save();
            }

            // 微信推送消息
            $openid = M('users')->where('user_id='.$value['user_id'])->getField('openid');
            $wxPush = new WxtmplmsgController();
            $wxPush->award_notify($openid,'很遗憾！您参与的活动未中奖！已退款到您的原支付方式，谢谢参与！', $value['order_amount'], $goods_name, $value['order_sn']);
        }
        $this->ajaxReturn('操作成功！');
    }

  /**
     * 订单状态
     */
    public function format_order_status($order,$group){
        $shipping_status = $order['shipping_status'];
        $pay_status = $order['pay_status'] ;
        $goods_num = $group['goods_num'] ;
        $order_num = $group['order_num'] ;

        $status_str = "";
        if($pay_status == 1 ){
            $status_str .= '已支付';
        }else{
            $status_str .= '未支付';
        }

        if($shipping_status==1){
            $status_str .= '/已发货';
        }else{
            $status_str .= '/未发货';
        }

        if($goods_num == $order_num){
            $status_str .= '/众筹成功';
        }else{
            $status_str .= '/众筹中';
        }
        return $status_str;
    }

    public function getPromStatus($order,$prom,$num)//订单表详情、团购表详情、参团人数
    {
        if(($num+1)<$prom['goods_num'] && ($prom['end_time']>time()) && $order['pay_status']==0 && $order['order_status']==8){
            $status['annotation'] = '拼团中,未付款';
            $status['order_type'] = '10';
        }
        elseif($order['order_type']==11){
            $status['annotation'] = '拼团中,已付款';
            $status['order_type'] = '11';
        }
        elseif(($num+1)<$prom['goods_num'] && $prom['end_time'] && $order['order_status']==9){//< time() && $order['pay_status']==1 && $order['order_status']==9
            $status['annotation'] = '未成团,待退款';
            $status['order_type'] = '12';
        }
        elseif(($num+1)<$prom['goods_num']  && $order['pay_status']==1 && $order['order_status']==10){
            $status['annotation'] = '未成团,已退款';
            $status['order_type'] = '13';
        }
        elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==0 && $order['order_status']==11){
            $status['annotation'] = '已成团,待发货';
            $status['order_type'] = '14';
        }
        elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==11){
            $status['annotation'] = '已成团,待收货';
            $status['order_type'] = '15';
        }elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==2) {
            $status['annotation'] = '已完成';
            $status['order_type'] = '4';
        }elseif ($order['order_status']==3){
            //'已取消'
            $status['annotation'] = '已取消';
            $status['order_type'] = '5';
        }elseif ($order['order_status']==4 && $order['pay_status']==1) {
            //'已完成'
            $status['annotation'] = '待换货';
            $status['order_type'] = '6';
        } elseif ($order['order_status']==5 && $order['pay_status']==1) {
            //'已完成'
            $status['annotation'] = '已换货';
            $status['order_type'] = '7';
        }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==6) {
            $status['annotation'] = '待退货';
            $status['order_type'] = '8';
        }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==7) {
            $status['annotation'] = '已退货';
            $status['order_type'] = '9';
        }elseif($order['order_type']==16 && $order['order_status']==15){
            $status['annotation'] = '拒绝受理';
            $status['order_type'] = '16';
        }else{
            $status['annotation'] = '订单状态异常';
            $status['order_type'] = null;
        }

        return$status;
    }


}