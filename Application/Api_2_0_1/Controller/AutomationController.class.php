<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/22
 * Time: 下午3:45
 */

namespace Api_2_0_1\Controller;
use Admin\Logic\OrderLogic;

class AutomationController extends BaseController
{
    public $userLogic;
    public function _initialize(){
        parent::_initialize();
        $this->userLogic = new \Home\Logic\UsersLogic();
    }

    //把所有免单自动退款
    public function free_single(){
        $free_order = M('getwhere')->where('ok_time = 0 or ok_time is null ')->limit(0,10)->select();
        $orderLogic = new OrderLogic();
        $ids = "";
        for ($i = 0; $i < count($free_order); $i++) {
            $order = M('order')->where('`order_id`=' . $free_order[$i]['order_id'])->field('order_sn,user_id,goods_id')->find();
            if ($free_order[$i]['code'] == 'weixin') {
                if ($free_order[$i]['is_jsapi'] == 1) {
                    $result = $orderLogic->weixinJsBackPay($order['order_sn'], $free_order[$i]['price']);
                } else {
                    $result = $orderLogic->weixinBackPay($order['order_sn'], $free_order[$i]['price']);
                }
                if ($result['status'] == 1) {
                    $data['one_time'] = $data['two_time'] = $data['ok_time'] = time();
                    $ids .= $order['user_id'].",";
                }
            } elseif ($free_order[$i]['code'] == 'alipay') {
                $result = $orderLogic->alipayBackPay($order['order_sn'], $free_order[$i]['price']);
                if ($result['status'] == 1) {
                    $data['one_time'] = $data['two_time'] = $data['ok_time'] = time();
                    $ids .= $order['user_id'].",";
                }
            } elseif ($free_order[$i]['code'] == 'qpay') {
                $qqPay = new QQPayController();
                $qqPay->doRefund($free_order[$i]['order_sn'], $free_order[$i]['order_amount']);
                $data['one_time'] = $data['two_time'] = $data['ok_time'] = time();
                $ids .= $order['user_id'].",";
            }
            redis("getOrderList_status_".$order['user_id'], "1");
            redisdelall("TuiSong*");//删除推送缓存
        }
        if ($ids) {
            $ids = substr($ids, 0, -1);
            M('getwhere')->where("id in('{$ids}')")->data($data)->save();
        }
    }

    //将单买超时支付的订单设置成取消
    public function single_buy_overtime() {
        $self_cancel_order = M('order')->where('prom_id is null and `is_cancel`=0 and `order_type`=1 and `pay_status`=0')->field('order_id,add_time,user_id,goods_id')->select();
        if (count($self_cancel_order) > 0) {
            $goods_ids = "";
            for ($j = 0; $j < count($self_cancel_order); $j++) {
                $data_time = $self_cancel_order[$j]['add_time'] + ORDER_END_TIME;
                if ($data_time <= time()) {
                    $ids[]['id'] = $self_cancel_order[$j]['order_id'];
                    $this->order_redis_status_ref($self_cancel_order[$j]['user_id']);
                    M('goods')->where('`goods_id` = '.$self_cancel_order[$j]['goods_id'])->setInc('store_count',$self_cancel_order[$j]['num']);
                    $spec_name = M('order_goods')->where('`order_id`='.$self_cancel_order[$j]['order_id'])->field('spec_key,store_id')->find();
                    M('spec_goods_price')->where("`goods_id`=$self_cancel_order[$j]['goods_id'] and `key`='$spec_name[spec_key]'")->setInc('store_count',$self_cancel_order[$j]['num']);
                }
                //优惠卷回到原来的数量
                if ($self_cancel_order[$j]['coupon_id'] != 0) {
                    M('coupon')->where('`id`=' . $self_cancel_order[$j]['coupon_id'])->setDec('use_num');
                    //把优惠卷还给用户
                    $data['use_time'] = 0;
                    $data['is_use'] = 0;
                    $data['order_id'] = 0;
                    $data['order_id'] = 0;
                    M('coupon_list')->where('`id`=' . $self_cancel_order[$j]['coupon_list_id'])->data($data)->save();
                }
            }
            $where['order_id'] = array('IN', array_column($ids, 'id'));
            M('order')->where($where)->data(array('order_status' => 3, 'order_type' => 5, 'is_cancel' => 1))->save();
        }
    }

    //将团购里超时支付的订单设置成取消
    public function group_purchase_overtime() {
        $where = null;
        $join_prom_order = M('group_buy')->alias('gb')
            ->join(" LEFT JOIN tp_order AS o ON o.order_id = gb.order_id ")
            ->where('gb.`is_pay`=0 and gb.is_cancel=0')
            ->field('gb.id,gb.order_id,gb.start_time,gb.user_id,gb.goods_id,gb.free,gb.goods_id,o.num')
            ->select();
        if (count($join_prom_order) > 0) {
            for ($z = 0; $z < count($join_prom_order); $z++) {
                $data_time = $join_prom_order[$z]['start_time'] + ORDER_END_TIME;
                if ($data_time <= time()) {
                    if ($join_prom_order[$z]['free'] > 0) $free_status = true;
                    $order_id[]['order_id'] = $join_prom_order[$z]['order_id'];
                    $id[]['id'] = $join_prom_order[$z]['id'];
                    $this->order_redis_status_ref($join_prom_order[$z]['user_id']);
                    M('goods')->where('`goods_id` = '.$join_prom_order[$z]['goods_id'])->setInc('store_count',$join_prom_order[$z]['num']);
                    $spec_name = M('order_goods')->where('`order_id`='.$join_prom_order[$z]['order_id'])->field('spec_key,store_id')->find();
                    M('spec_goods_price')->where("`goods_id`=$join_prom_order[$z]['goods_id'] and `key`='$spec_name[spec_key]'")->setInc('store_count',$join_prom_order[$z]['num']);
                }
                if ($join_prom_order[$z]['free'] > 0) redis("get_Free_Order_status", "1");
            }
            $where['id'] = array('IN', array_column($id, 'id'));
            $conditon['order_id'] = array('IN', array_column($order_id, 'order_id'));
            $res = M('group_buy')->where($where)->data(array('is_cancel' => 1))->save();
            $res1 = M('order')->where($conditon)->data(array('order_status' => 3, 'order_type' => 5, 'is_cancel' => 1))->save();
            $r = M('order')->where($conditon)->select();
            for ($t = 0; $t < count($res1); $t++) {
                //优惠卷回到原来的数量
                if ($r[$t]['coupon_id'] != 0) {
                    M('coupon')->where('`id`=' . $r[$t]['coupon_id'])->setDec('use_num');
                    //把优惠卷还给用户
                    $data['use_time'] = 0;
                    $data['is_use'] = 0;
                    $data['order_id'] = 0;
                    M('coupon_list')->where('`id`=' . $r[$t]['coupon_list_id'])->data($data)->save();
                }
            }
            if ($free_status) redis("get_Seconds_Kill_status","1");
        }
    }

    //将时间到了团又没有成团的团解散
    public function incomplete_mass_overtime() {
        $user = new UserController();
        $where = null;
        $conditon = null;
        $time = time()-30;
        $prom_order = M('group_buy')->where('`is_dissolution`=0 and `is_pay`=1 and mark=0 and `is_successful`=0 and `end_time`<=' . $time)->field('id,order_id,start_time,end_time,goods_num,user_id,goods_id')->limit(0,50)->select();
        if (count($prom_order) > 0) {
            //将团ＩＤ一次性拿出来
            $where = $user->getPromid($prom_order);
            //找出这个团的团长和团员
            $join_proms = M('group_buy')->where($where)->select();
            redis("get_Free_Order_status", "1");
            //统计每个团的人数
            $prom_man = array();
            foreach ($join_proms as $k => $v) {
                $n = array();
                foreach ($join_proms as $k1 => $v1) {
                    if ($v['id'] == $v1['mark']) {
                        $n['id'][] = "'" . $v1['id'] . "',";
                        $n['order_id'][] = "'" . $v1['order_id'] . "',";
                    } elseif ($v['id'] == $v1['id']) {
                        $n['id'][] = "'" . $v['id'] . "',";
                        $n['order_id'][] = "'" . $v['order_id'] . "',";
                    }
                    $this->order_redis_status_ref($v1['user_id']);
                }
                $prom_man[$k] = $n;
            }
            $wheres = $user->ReturnSQL($prom_man);
            $i_d = $wheres['id'];
            $res = M('group_buy')->where("`id` IN " . $i_d)->data(array('is_dissolution' => 1))->save();
            $result1 = M('order')->where("`order_id` IN " . $wheres['order_id'])->data(array('order_status' => 9, 'order_type' => 12))->save();

            if ($res && $result1) {//给未成团订单退款
                $pay_cod = M('order')->where("`order_id` IN $wheres[order_id]")->field('order_id,user_id,order_sn,pay_code,order_amount,goods_id,store_id,num,coupon_id,coupon_list_id,is_jsapi')->select();
                $user->BackPay($pay_cod);
            }
        }
    }

    //将自动确认收货的订单的状态进行修改
    //单买的订单拿出来
    public function get_single_buy_order() {
        $one_buy = M('order','','DB_CONFIG2')->where('shipping_status=1 and order_status=1 and pay_status=1 and is_return_or_exchange=0 and confirm_time=0 and automatic_time<=' . time())->select();
        $one_buy_number = count($one_buy);
        if ($one_buy_number > 0) {
            $data = null;
            $ids['order_id'] = array('IN', array_column($one_buy, 'order_id'));
            $data['confirm_time'] = time();
            $data['order_status'] = 2;
            $data['order_type'] = 4;
            M('order')->where($ids)->data($data)->save();
            for ($oi=0; $oi<$one_buy_number; $oi++){
                $this->order_redis_status_ref($one_buy[$oi]['user_id']);
            }
        }
    }

    //拿出团购的订单
    public function group_purchase_order() {
        $group_nuy = M('order','','DB_CONFIG2')->where('order_status=11 and shipping_status=1 and pay_status=1 and is_return_or_exchange=0 and confirm_time=0 and automatic_time<=' . time())->select();
        $group_nuy_number = count($group_nuy);
        if ($group_nuy_number > 0) {
            $data = null;
            $order_id_array['order_id'] = array('IN', array_column($group_nuy, 'order_id'));
            $data['confirm_time'] = time();
            $data['order_status'] = 2;
            $data['order_type'] = 4;
            M('order')->where($order_id_array)->data($data)->save();
            for ($gi=0; $gi<$group_nuy_number; $gi++){
                $this->order_redis_status_ref($group_nuy[$gi]['user_id']);
            }
        }
    }

    //更新限时秒杀列表
    public function seconds_kill_list() {
        $is_special = M('goods','','DB_CONFIG2')
            ->where(array(
                'is_special'=>array('EQ',1),
                'on_time'=>array('ELT',time()),
                'store_count'=>array('GT',0)))
            ->count();
        if ($is_special > 0) redis("get_Seconds_Kill_status", "1");
    }

    //八小时自动成团
    public function auto_group_buy (){
        $where = null;
        $conditon = null;
        $time = time() + 16 * 60 * 60;
        $prom_order = M('group_buy')
            ->where('`user_id`=9222 and `is_raise`<>1 and `is_free`<>1 and `is_dissolution`=0 and `is_pay`=1 and mark=0 and `is_successful`=0 and `end_time`<=' . $time)
            ->limit(0,50)
            ->select();
        if (count($prom_order) > 0) {
            redis("get_Free_Order_status", "1");
            $message = "您拼的团已满，等待商家发货中";
            $ids = "";
            $order_ids = "";
            $sql = "INSERT INTO tp_group_buy(start_time, end_time, goods_id, price, goods_num, order_num, virtual_num, intro, goods_price, goods_name, photo, mark, user_id, order_id, store_id, address_id, free, is_raise, is_pay, is_free, is_successful, is_cancel, is_return_or_exchange, is_dissolution) VALUES";
            foreach ($prom_order as $v){
                if (empty(redis("getBuy_lock_".$v['goods_id']))) {//如果无锁
                    redis("getBuy_lock_" . $v['goods_id'], "1", 5);//写入锁
                    $group_buy_mark = M('group_buy')
                        ->where(array('mark' => array('eq', $v['id'])))
                        ->select();
                    $values = "";
                    for ($i = 0; $i < ($v['goods_num'] - count($group_buy_mark) - 1); $i++) {
                        $user = $this->get_robot($v['user_id']);
                        $values .= "({$v['start_time']},{$v['end_time']},{$v['goods_id']},{$v['price']},{$v['goods_num']},{$v['order_num']},{$v['virtual_num']},'{$v['intro']}',{$v['goods_price']},'{$v['goods_name']}','{$v['photo']}',{$v['id']},{$user['user_id']},{$v['order_id']},{$v['store_id']},{$v['address_id']},{$v['free']},{$v['is_raise']},{$v['is_pay']},{$v['is_free']},1,{$v['is_cancel']},{$v['is_return_or_exchange']},{$v['is_dissolution']}),";
                    }
                    $values = substr($values, 0, -1);
                    if ($values) {
                        $sql .= $values;
                        print_r($sql);
                        M()->query($sql);
                    }
                    foreach ($group_buy_mark as $value) {
                        $ids .= $value['id'] . ",";
                        $order_ids .= $value['order_id'] . ",";
                        $this->order_redis_status_ref($value['user_id']);
                        $custom = array('type' => '2','id'=>$v['id']);
                        $user_id = $value['user_id'];
                        SendXinge($message,"$user_id",$custom);
                    }
                    $this->order_redis_status_ref($v['user_id']);
                    $ids .= $v['id'] . ",";
                    $order_ids .= $v['order_id'] . ",";
                    redisdelall("getBuy_lock_" . $v['goods_id']);//删除锁
                }
            }

            $ids = substr($ids, 0, -1);
            $order_ids = substr($order_ids, 0, -1);
            if (!empty($ids)) M("group_buy")->where("id in({$ids})")->save(array("is_successful"=>1));
            if (!empty($order_ids)) M("order")->where("order_id in({$order_ids})")->save(array("order_status"=>11, "shipping_status"=>0, "pay_status"=>1, "order_type"=>14));
        }
    }
}