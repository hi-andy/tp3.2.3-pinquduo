<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/4/17
 * Time: 12:00
 *
 * 团购/拼团 控制器
 *
 * 此控制器暂未使用，将来重构，相关功能迁移至此，请知悉。
 */

namespace Api_2_0_1\Controller;


use Api_2_0_1\Controller;

class PurchaseController extends  BaseController
{

    public function _initialize() {
        $this->encryption();
    }

    /*
	 * type:  0、参团、1、开团、2、单买
	 */
    function getBuy()
    {
        header("Access-Control-Allow-Origin:*");
        $user_id = I('user_id');
        $prom_id =I('prom_id');
        $address_id = I('address_id');
        $goods_id = I('goods_id');
        $num = I('num',1);
        $free = I('free',0);
        $type = I('type');
        I('coupon_id') && $coupon_id = I('coupon_id');
        I('coupon_list_id') && $coupon_list_id = I('coupon_list_id');
        $spec_key = I('spec_key');
        I('prom') && $prom = I('prom');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        $parameter['prom_id'] = $prom_id;
        $parameter['prom'] = $prom;
        $parameter['user_id'] = $user_id;
        $parameter['address_id'] = $address_id;
        $parameter['goods_id'] = $goods_id;
        $parameter['num'] = $num;
        $parameter['free'] = $free;
        $parameter['coupon_id'] = $coupon_id;
        $parameter['spec_key'] = $spec_key;
        $parameter['ajax_get'] = $ajax_get;
        $parameter['coupon_list_id'] = $coupon_list_id;

        if (empty(redis("getBuy_lock_".$goods_id))) {//如果无锁
            redis("getBuy_lock_" . $goods_id, "1", 5);//写入锁

        $res1 = M('group_buy')->alias('gb')
            ->join('INNER JOIN tp_goods g on g.goods_id = gb.goods_id ')
            ->where("gb.`user_id`=$user_id and gb.`is_pay`=1 and gb.`goods_id`=$goods_id")
            ->field("g.is_special,g.sales")
            ->find();
        if(!empty($res1) && $res1['is_special']==7){
            $json =	array('status'=>-1,'msg'=>'您已购买过此宝贝T_T');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if (!empty($ajax_get)) {
                echo "<script> alert('" . $json['msg'] . "') </script>";
                exit;
            }
        }
        if(!empty($spec_key)){
            $spec_res = M('spec_goods_price')->where('`goods_id`=' . $goods_id . " and `key`='$spec_key'")->find();
        }else{
            $json = array('status' => -1, 'msg' => '该规格刚售完，请重新选择');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        if ($spec_res['store_count'] <= 0) {
            $json = array('status' => -1, 'msg' => '该商品已经被亲们抢光了');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }elseif ($spec_res['store_count']<$num){
            $json = array('status' => -1, 'msg' => '库存不足！');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }elseif ($res1['is_special']==7 && $spec_res['store_count']<(($res1['sales']+$spec_res['store_count'])/2)&&$type==1){
            $json = array('status' => -1, 'msg' => '库存不足，亲只能参团哦！^_^');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
            //参团购物
            if ($type == 0) {
                $result = M('group_buy')->where("`id` = $prom_id")->find();
                if ($result['end_time'] < time()) {
                    $json = array('status' => -1, 'msg' => '该团已结束了，请选择别的团参加');
                    redisdelall("getBuy_lock_" . $goods_id);//删除锁
                    if (!empty($ajax_get)) {
                        echo "<script> alert('" . $json['msg'] . "') </script>";
                        exit;
                    }
                    exit(json_encode($json));
                }
                //为我点赞只允许每个人参团一次
                if ($result['is_raise'] == 1) {
                    $raise = M('group_buy')->where('(end_time>' . time() . ' or is_successful=1) and mark!=0 and is_raise=1')->order('id desc')->select();
                    $raise_id_array = array_column($raise, 'user_id');
                    if (in_array("$user_id", $raise_id_array)) {
                        $json = array('status' => -1, 'msg' => '您已参加过该拼团活动，不能再参团，只能继续开团');
                        redisdelall("getBuy_lock_" . $goods_id);//删除锁
                        if (!empty($ajax_get)) {
                            echo "<script> alert('" . $json['msg'] . "') </script>";
                            exit;
                        }
                        exit(json_encode($json));
                    }
                }
                //每个团的最后一个人直接将订单锁住防止出现错误
                $num = M('group_buy')->where('`id`=' . $result['id'] . ' or `mark`=' . $result['id'] . ' and `is_pay`=1 and `is_cancel`=0')->count();
                if ($num == $result['goods_num']) {
                    $on_buy = M('group_buy')->where('`mark`=' . $result['id'] . ' and `is_pay`=0 and `is_cancel`=0')->find();
                    if (!empty($on_buy)) {
                        $json = array('status' => -1, 'msg' => '有用户尚未支付，您可以在他取消订单后进行支付');
                        redisdelall("getBuy_lock_" . $goods_id);//删除锁
                        if (!empty($ajax_get)) {
                            echo "<script> alert('" . $json['msg'] . "') </script>";
                            exit;
                        }
                        exit(json_encode($json));
                    }
                } elseif ($num == $result['goods_num']) {
                    $json = array('status' => -1, 'msg' => '该团已经满员开团了，请选择别的团参加');
                    redisdelall("getBuy_lock_" . $goods_id);//删除锁
                    if (!empty($ajax_get)) {
                        echo "<script> alert('" . $json['msg'] . "') </script>";
                        exit;
                    }
                    exit(json_encode($json));
                }
                //判断该用户是否参团了
                $self = M('group_buy')->where('`mark`=' . $result['id'] . ' and `user_id`=' . $user_id . ' and `is_pay`=1')->find();
                if (!empty($self)) {
                    $json = array('status' => -1, 'msg' => '你已经参团了');
                    redisdelall("getBuy_lock_" . $goods_id);//删除锁
                    if (!empty($ajax_get)) {
                        echo "<script> alert('" . $json['msg'] . "') </script>";
                        exit;
                    }
                    exit(json_encode($json));
                }
                //判断用户是否已经生成未付款订单
                $on_buy = M('group_buy')->where('`mark`=' . $result['id'] . ' and `user_id`=' . $user_id . ' and `is_pay`=0 and `is_cancel`=0')->find();
                if (!empty($on_buy)) {
                    $json = array('status' => -1, 'msg' => '该团你有未付款订单，请前往支付再进行操作');
                    redisdelall("getBuy_lock_" . $goods_id);//删除锁
                    if (!empty($ajax_get)) {
                        echo "<script> alert('" . $json['msg'] . "') </script>";
                        exit;
                    }
                    exit(json_encode($json));
                }
                if ($result['mark'] != 0) {
                    $num2 = M('group_buy')->where('`id`=' . $result['mark'] . ' or `mark` = ' . $result['mark'] . ' and `is_pay`=1')->count();
                } else {
                    $num2 = M('group_buy')->where('`id`=' . $prom_id . ' or `mark` = ' . $prom_id . ' and `is_pay`=1')->count();
                }
                $this->joinGroupBuy($parameter);
            } else if ($type == 1)    //开团
            {
                $this->openGroup($parameter);
            } //自己买
            else if ($type == 2) {
                $this->buyBymyself($parameter);
            }
        } else {
            $json = array('status' => -1, 'msg' => '抢购比较激烈，请再猛戳试试');
            if (!empty($ajax_get)) {
                echo "<script> alert('" . $json['msg'] . "') </script>";
                exit;
            }
            exit(json_encode($json));
        }
    }

    /**
     * 参加拼团
     */
    public function joinGroupBuy($parameter){
        $prom_id = $parameter['prom_id'];
        $user_id = $parameter['user_id'];
        $data = array();
        $order = array();
        $address_id = $parameter['address_id'];
        $spec_key = $parameter['spec_key'];
        $coupon_id = $parameter['coupon_id'];
        $ajax_get =  $parameter['ajax_get'];
        $num = $parameter['num'];
        $coupon_list_id = $parameter['coupon_list_id'];
        M()->startTrans();//开启事务处理
        //找到参加的这张单
        $result = M('group_buy')->where("`id` = $prom_id")->find();

        //是否使用优惠卷
        if(!empty($coupon_id)){
            $coupon = M('coupon')->where('`id`='.$coupon_id)->field('money')->find();
        }else{
            $coupon['money'] = 0;
        }
        //找到开团的商品,并获取要用的数据
        $goods_id = $result['goods_id'];
        $goods = M('goods')->where('`goods_id` = '.$goods_id)->find();
        //找到开团的商品,并获取要用的数据
        if(!empty($spec_key)){
            $goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->find();
            $goods['prom_price']=(string)($goods_spec['prom_price']);
        }else{
            $goods_spec['key_name']= '默认';
            $goods['prom_price']=(string)$goods['prom_price'];
        }
        $free = $result['free'];
        $prom = $result['goods_num'];
        if(!empty($free)){//是否免单
            redis("get_Free_Order_status", "1");
            if(!empty($prom)){
                $goods['prom_price'] = (string)($goods['prom_price']*$prom/($prom-$free));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$free));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }
        }

        //如果是众筹订单
        if($result['is_raise']==1){
            $data['is_raise']=1;
            $order['the_raise']=1;
        }else{
            $data['is_raise']=0;
            $order['the_raise']=0;
        }
        if($result)
        {
            //在团购表加一张单
            $data['start_time'] = time();
            $data['end_time'] = $result['end_time'];
            $data['goods_id'] = $result['goods_id'];
            if(!empty($coupon_list_id)){
                $data['price'] = (string)($goods['prom_price']*$num-$coupon['money']);
            }else{
                $data['price'] = (string)($goods['prom_price']*$num);
            }
            $data['goods_num'] = $result['goods_num'];
            $data['order_num'] = (M('group_buy')->where("`mark`=". $result['id'])->count())+1;
            $data['intro'] = $result['intro'];
            $data['goods_price'] = $result['goods_price'];
            $data['goods_name'] = $result['goods_name'];
            $data['photo'] = '/Public/upload/logo/logo.jpg';
            $data['mark'] = $result['id'];
            $data['user_id'] = $user_id;
            $data['store_id'] = $result['store_id'];
            $data['address_id'] = $address_id;
            $data['free'] = $result['free'];
            $group_buy = M('group_buy')->data($data)->add();

            //在订单表加一张单
            $address = M('user_address')->where("`address_id` = $address_id")->find();//获取地址信息
            $invitation_num = M('order')->where('`prom_id`='.$prom_id)->field('invitation_num')->find();
            $order['user_id'] = $user_id;
            $order['order_sn'] = C('order_sn');
            $order['invitation_num'] = $invitation_num['invitation_num'];
            $order['goods_id'] = $result['goods_id'];
            $order['pay_status'] = 0;
            $order['order_status'] = 8;
            $order['order_type'] = 10;
            $order['consignee'] = $address['consignee'];
            $order['country'] = 1;
            $order['address_base'] = $address['address_base'];
            $order['address'] = $address['address'];
            $order['mobile'] = $address['mobile'];
            if(I('code')=='weixin'){
                $order['pay_code'] = 'weixin' ;
                $order['pay_name'] = '微信支付';
            }elseif(I('code')=='alipay'){
                $order['pay_code'] = 'alipay' ;
                $order['pay_name'] = '支付宝支付';
			}
            elseif(I('code')=='alipay_wap')  // 添加手机网页版支付 2017-5-25 hua
            {
                $order['pay_code'] = 'alipay_wap' ;
                $order['pay_name'] = '支付宝手机网页支付';
            }
			elseif(I('code')=='qpay')
			{
                $order['pay_code'] = 'qpay';
                $order['pay_name'] = 'QQ钱包支付';
            }
            $order['goods_price'] = $order['total_amount'] = $goods['prom_price']*$num;
            if(!empty($coupon_list_id)){
                $order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
            }else{
                $order['order_amount'] = (string)($goods['prom_price']*$num);
            }
            $order['coupon_price'] = $coupon['money'];
            I('coupon_list_id') && $order['coupon_list_id'] = $coupon_list_id;
            I('coupon_id') && $order['coupon_id'] = $coupon_id;
            $order['add_time'] = $order['pay_time'] = time();
            $order['prom_id'] = $group_buy;
            $order['free'] = $result['free'];
            $order['store_id'] = $result['store_id'];
            $order['num'] = $num;
            if(!empty($ajax_get)){
                $order['is_jsapi'] = 1;
            }
            $o_id = M('order')->data($order)->add();

            //将参与的团id在订单规格表查出第一张单
            $one_order = M('order_goods')->where("`order_id`=".$result['order_id'])->find();
            $spec_data['order_id'] = $o_id;
            $spec_data['goods_id'] = $goods_id;
            $spec_data['goods_name'] = $one_order['goods_name'];
            $spec_data['goods_num'] = $num;
            $spec_data['market_price'] = $one_order['market_price'];
            if(!empty($spec_key)){
                $spec_data['goods_price'] = $goods_spec['prom_price'];
            }else{
                $spec_data['goods_price'] = $goods['prom_price'];
            }
            $coupon && $spec_data['coupon_price'] = $coupon['money'];
            $spec_data['spec_key'] = $spec_key;
            $spec_data['spec_key_name'] = $goods_spec['key_name'];
            $spec_data['prom_id'] = $group_buy;
            $spec_data['store_id'] = $one_order['store_id'];
            $spec_res = M('order_goods')->data($spec_data)->add();

            if(empty($spec_res) || empty($group_buy) || empty($o_id))
            {
                M()->rollback();//有数据库操作不成功时进行数据回滚
                $json = array('status'=>-1,'msg'=>'参团失败');
                redisdelall("getBuy_lock_" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }
            //优惠卷(有就使用··不然就直接跳过)
            if(!empty(I('coupon_id'))) {
                $coupon_Inc = M('coupon')->where('`id`=' . $coupon_id)->setInc('use_num');
                $this->changeCouponStatus($coupon_list_id, $o_id);
                if (empty($coupon_Inc)) {
                    M()->rollback();//有数据库操作不成功时进行数据回滚
                    redisdelall("getBuy_lock_" . $goods_id);//删除锁
                    $json = array('status' => -1, 'msg' => '参团失败');
                    if($ajax_get){
                        $json = array('status' => -1, 'msg' => '参团失败');
                        echo "<script> alert('" . $json['msg'] . "') </script>";
                        exit;
                    }else{
                        exit(json_encode($json));
                    }
                }
            }
            //将订单号写会团购表
            $res = M('group_buy')->where("`id` = $group_buy")->data(array('order_id'=>$o_id))->save();
            if(!empty($res) )
            {
                M()->commit();//都操作成功的时候才真的把数据放入数据库

                redisdelall("getBuy_lock_".$goods_id);//删除锁
                $user_id_arr = M('group_buy')->where('id = '.$result['id'].' or mark ='.$result['id'])->field('user_id')->select();
                redis("group_buy", serialize($user_id_arr), 300);
                for($i=0;$i<count($user_id_arr);$i++){
                    redis("getOrderList_status_".$user_id_arr[$i]['user_id'], "1");
                    redis("getOrderList_status_".$user_id_arr[$i]['user_id'], "1");
                }
                $rdsname = "TuiSong*";
                redisdelall($rdsname);//删除推送缓存
                if($order['pay_code']=='weixin'){
                    $weixinPay = new WeixinpayController();
                    if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1){
                        $order['order_id'] = $result['order_id'];
                        $code_str = $weixinPay->getJSAPI($order);
                        $pay_detail = $code_str;
                    }else{
                        $pay_detail = $weixinPay->addwxorder($order['order_sn']);
                    }
                }elseif($order['pay_code'] == 'alipay'){//AlipayController
                    $AliPay = new AlipayController();
                    $pay_detail = $AliPay->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
				}
                elseif($order['pay_code'] == 'alipay_wap'){ // 添加手机网页版支付 2017-5-25 hua
                    $AlipayWap = new AlipayWapController();
                    $pay_detail = $AlipayWap->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
                }
				elseif($order['pay_code'] == 'qpay'){
                    $qqPay = new QQPayController();
                    $pay_detail = $qqPay->getQQPay($order);
                }
                $json = array('status'=>1,'msg'=>'参团成功','result'=>array('order_id'=>$o_id,'group_id'=>$group_buy,'pay_detail'=>$pay_detail));
                $this->aftermath($user_id,$goods,$num,$o_id);
                if(!empty($ajax_get)){
                    //echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }else{
                M()->rollback();//有数据库操作不成功时进行数据回滚
                $json = array('status'=>-1,'msg'=>'参团失败');
                redisdelall("getBuy_lock_" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }
        }
    }

    /**
     * 开团
     */
    public function openGroup($parameter){
        $goods_id = $parameter['goods_id'];
        $data = array();
        $order = array();
        $only_userid = $user_id = $parameter['user_id'];
        $address_id = $parameter['address_id'];
        $num = $parameter['num'];
        $spec_key = $parameter['spec_key'];
        $coupon_id = $parameter['coupon_id'];
        $free = $parameter['free'];
        $ajax_get =  $parameter['ajax_get'];
        $coupon_list_id = $parameter['coupon_list_id'];
        $prom = $parameter['prom'];
        M()->startTrans();
        //是否使用优惠卷
        if(!empty($coupon_id))
        {
            $coupon = M('coupon')->where('`id`='.$coupon_id)->field('money')->find();
        }else{
            $coupon['money'] = 0;
        }
        //找到开团的商品,并获取要用的数据
        $goods = M('goods')->where('`goods_id` = '.$goods_id)->find();
        //获取商品规格和相应的价格
        if(!empty($spec_key)){
            $goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->field('key_name,prom_price')->find();
            $goods['prom_price']=(string)($goods_spec['prom_price']);
        }else{
            $goods_spec['key_name']= '默认';
            $goods['prom_price']=(string)$goods['prom_price'];
        }
        if(!empty($free))//是否免单
        {
            redis("get_Free_Order_status", "1");
            if(!empty($prom))
            {
                $goods['prom_price'] = (string)($goods['prom_price']*$prom/($prom-$free));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$free));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }
        }
        elseif(!empty($goods['free'])){
            if(!empty($prom)){
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$goods['free']));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$goods['free']));
                $count = getFloatLength($goods['prom_price']);
                if($count>=3){
                    $price = operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price;
                }
            }
        }

        //在团购表加单
        $data['start_time'] = time();
        $data['end_time'] = time()+24*60*60;
        $data['goods_id'] = $goods_id;
        if(!empty($prom)){
            $data['goods_num'] = $prom;
        }else{
            $data['goods_num'] = $goods['prom'];
        }
        $data['order_num'] = 1;
        $data['buy_num'] = $data['order_num'] = 1;
        if(!empty($coupon_list_id)){
            $data['price'] = (string)($goods['prom_price']*$num-$coupon['money']);
        }else{
            $data['price'] = (string)($goods['prom_price']*$num);
        }
        $data['intro'] = $goods['goods_name'];
        $data['goods_price'] = $goods['market_price'];
        $data['goods_name'] = $goods['goods_name'];
        $data['photo'] = '/Public/upload/logo/logo.jpg';
        $data['mark'] = 0;
        $data['user_id'] = $user_id;
        $data['store_id'] = $goods['store_id'];
        $data['address_id'] = $address_id;
        $data['free'] = $free;
        //如果是众筹订单
        if($goods['the_raise']==1)
        {
            $data['is_raise']=1;
        }
        $group_buy = M('group_buy')->data($data)->add();

        //在订单加一张单
        $address = M('user_address')->where('`address_id` = '.$address_id)->find();//获取地址信息
        $order['user_id'] = $user_id;
        $order['order_sn'] = C('order_sn');
        $order['invitation_num'] = $this->getInvitationNum();
        $order['goods_id'] = $goods_id;
        $order['pay_status'] = 0;
        $order['order_status'] = 8;
        $order['order_type'] = 10;
        $order['consignee'] = $address['consignee'];
        $order['country'] = 1;
        $order['address_base'] = $address['address_base'];
        $order['address'] = $address['address'];
        $order['mobile'] = $address['mobile'];
        if(I('code')=='weixin'){
            $order['pay_code'] = 'weixin' ;
            $order['pay_name'] = '微信支付';
        }elseif(I('code')=='alipay'){
            $order['pay_code'] = 'alipay' ;
            $order['pay_name'] = '支付宝支付';
		}
        elseif(I('code')=='alipay_wap')  // 添加手机网页版支付 2017-5-25 hua
        {
            $order['pay_code'] = 'alipay_wap' ;
            $order['pay_name'] = '支付宝手机网页支付';
        }
		elseif(I('code')=='qpay')
		{
            $order['pay_code'] = 'qpay';
            $order['pay_name'] = 'QQ钱包支付';
        }
        $order['goods_price'] = $goods['market_price'];
        $order['total_amount'] = $goods['prom_price']*$num;
        if(!empty($coupon_list_id)){
            $order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
        }else{
            $order['order_amount'] = (string)($goods['prom_price']*$num);
        }
        $order['coupon_price'] = $coupon['money'];
        I('coupon_list_id') && $order['coupon_list_id'] = $coupon_list_id;
        I('coupon_id') && $order['coupon_id'] = $coupon_id;
        $order['add_time'] = $order['pay_time'] = time();
        $order['store_id'] = $goods['store_id'];
        $order['prom_id'] = $group_buy;
        $order['free'] = $free;
        $order['num'] = $num;
        //如果是众筹订单
        if($goods['the_raise']==1){
            $order['the_raise']=1;
        }
        if(!empty($ajax_get)){
            $order['is_jsapi'] = 1;
        }
        $o_id = M('order')->data($order)->add();
        $order['order_id'] = $o_id;

        //在商品规格订单表加一条数据
        $spec_data['order_id'] = $o_id;
        $spec_data['goods_id'] = $goods_id;
        $spec_data['goods_name'] =$goods['goods_name'];
        $spec_data['goods_num'] = $num;
        $spec_data['market_price'] = $goods['market_price'];
        if(!empty($spec_key))
        {
            $spec_data['goods_price'] = $goods_spec['prom_price'];
        }else{
            $spec_data['goods_price'] = $goods['prom_price'];
        }
        $coupon && $spec_data['coupon_price'] = $coupon['money'];
        $spec_data['spec_key'] = $spec_key;
        $spec_data['spec_key_name'] = $goods_spec['key_name'];
        $spec_data['prom_type'] = 1;
        $spec_data['prom_id'] = $group_buy;
        $spec_data['store_id'] = $goods['store_id'];
        $spec_res = M('order_goods')->data($spec_data)->add();
        if(empty($spec_res) || empty($group_buy) || empty($o_id))
        {
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'开团失败');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }

        //优惠卷(有就使用··不然就直接跳过)
        if(!empty(I('coupon_id'))) {

            $coupon_Inc = $this->changeCouponStatus($coupon_list_id,$o_id);
            if(empty($coupon_Inc))
            {
                M()->rollback();//有数据库操作不成功时进行数据回滚
                $json = array('status'=>-1,'msg'=>'开团失败');
                redisdelall("getBuy_lock_" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }
        }
        $res = M('group_buy')->where("`id` = $group_buy")->data(array('order_id'=>$o_id))->save();
        if(!empty($res))
        {
            M()->commit();//都插入成功的时候才真的把数据放入数据库

            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            $rdsname = "getOrderList_".$only_userid."*";
            redisdelall($rdsname);//删除订单列表
            $rdsname = "TuiSong*";
            redisdelall($rdsname);//删除推送缓存

            if($order['pay_code']=='weixin'){
                $weixinPay = new WeixinpayController();
                //微信JS支付 && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')
                if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1){
                    $code_str = $weixinPay->getJSAPI($order);
                    $pay_detail = $code_str;
                }else{
                    $pay_detail = $weixinPay->addwxorder($order['order_sn']);
                }
            }elseif($order['pay_code'] == 'alipay'){
                $AliPay = new AlipayController();
				$pay_detail = $AliPay->addAlipayOrder($order['order_sn']);
			}
            elseif($order['pay_code'] == 'alipay_wap'){ // 添加手机网页版支付 2017-5-25 hua
                $AlipayWap = new AlipayWapController();
                $pay_detail = $AlipayWap->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
            }
			elseif($order['pay_code'] == 'qpay'){
                // Begin code by lcy
                $qqPay = new QQPayController();
                $pay_detail = $qqPay->getQQPay($order);
                // End code by lcy
            }
            $json = array('status'=>1,'msg'=>'参团成功','result'=>array('order_id'=>$o_id,'group_id'=>$group_buy,'pay_detail'=>$pay_detail));
            $this->aftermath($user_id,$goods,$num,$o_id);
            if(!empty($ajax_get)){
                //echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }else{
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'开团失败');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }
    }

    /**
     * 自己购买
     */
    public function buyBymyself($parameter){
        $goods_id = $parameter['goods_id'];
        $address_id = $parameter['address_id'];
        $only_userid = $user_id = $parameter['user_id'];
        $num = $parameter['num'];
        $spec_key = $parameter['spec_key'];
        $ajax_get = $parameter['ajax_get'];
        $coupon_id = $parameter['coupon_id'];
        $coupon_list_id = $parameter['coupon_list_id'];
        M()->startTrans();
        //是否使用优惠卷
        if(!empty($coupon_id))
        {
            $coupon = M('coupon')->where('`id`='.$coupon_id)->field('money')->find();
        }else{
            $coupon['money'] = 0;
        }
        $goods = M('goods')->where('`goods_id` = '.$goods_id)->find();//找到商品信息
        //获取商品规格和相应的价格
        if(!empty($spec_key))
        {
            $goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->field('key_name,price,prom_price')->find();
            $price=(string)($goods_spec['price']);
        }else{
            $goods_spec['key_name']= '默认';
            $price=(string)($goods['shop_price']);
        }
        $address = M('user_address')->where('`address_id` = '.$address_id)->find();//获取地址信息
        $order['user_id'] = $user_id;
        $order['goods_id'] = $goods_id;
        $order['order_sn'] = C('order_sn');
        $order['pay_status'] = 0;
        $order['order_status'] = 1;
        $order['order_type'] = 1;
        $order['consignee'] = $address['consignee'];
        $order['country'] = 1;
        $order['address_base'] = $address['address_base'];
        $order['address'] = $address['address'];
        $order['mobile'] = $address['mobile'];
        if(I('code')=='weixin')
        {
            $order['pay_code'] = 'weixin' ;
            $order['pay_name'] = '微信支付';
        }
        elseif(I('code')=='alipay')
        {
            $order['pay_code'] = 'alipay' ;
            $order['pay_name'] = '支付宝支付';
        }
        elseif(I('code')=='alipay_wap')  // 添加手机网页版支付 2017-5-25 hua
        {
            $order['pay_code'] = 'alipay_wap' ;
            $order['pay_name'] = '支付宝手机网页支付';
        }
        // Begin code by lcy
        elseif(I('code')=='qpay')
        {
            $order['pay_code'] = 'qpay';
            $order['pay_name'] = 'QQ钱包支付';
        }
        // End code by lcy
        $order['goods_price'] = $price;
        $order['total_amount'] = $price*$num;
        $order['order_amount'] = (string)(($price*$num)-$coupon['money']);
        $order['coupon_price'] = $coupon['money'];
        I('coupon_list_id') && $order['coupon_list_id'] = $coupon_list_id;
        I('coupon_id') && $order['coupon_id'] = $coupon_id;
        $order['num'] = $num;
        $order['add_time'] = $order['pay_time'] = time();
        $order['store_id'] = $goods['store_id'];
        $o_id = M('order')->data($order)->add();
        if(!empty($ajax_get))
        {
            $order['is_jsapi'] = 1;
        }
        $order['order_id'] = $o_id;

        if(empty($o_id))
        {
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'购买失败');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }
        //在商品规格订单表加一条数据
        $spec_data['order_id'] = $o_id;
        $spec_data['goods_id'] = $goods_id;
        $spec_data['goods_name'] =$goods['goods_name'];
        $spec_data['goods_num'] = $num;
        $spec_data['market_price'] = $goods['market_price'];

        if(!empty($spec_key))
        {
            $spec_data['goods_price'] = $goods_spec['price'];
        }else{
            $spec_data['goods_price'] = $goods['shop_price'];
        }
        $coupon && $spec_data['coupon_price'] = $coupon['money'];
        $spec_data['spec_key'] = $spec_key;
        $spec_data['spec_key_name'] = $goods_spec['key_name'];
        $spec_data['prom_type'] = 1;
        $spec_data['prom_id'] = 0;
        $spec_data['store_id'] = $goods['store_id'];
        $spec_res = M('order_goods')->data($spec_data)->add();
        if(empty($spec_res))
        {
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'购买失败');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }
        //优惠卷(有就使用··不然就直接跳过)
        if(!empty(I('coupon_id'))) {
            $coupon_Inc = M('coupon')->where('`id`=' . $coupon_id)->setInc('use_num');
            $this->changeCouponStatus($coupon_list_id,$o_id);
            if(empty($coupon_Inc))
            {
                M()->rollback();//有数据库操作不成功时进行数据回滚
                $json = array('status'=>-1,'msg'=>'购买失败');
                redisdelall("getBuy_lock_" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }
        }
        if(!empty($o_id))
        {
            M()->commit();//都操作s成功的时候才真的把数据放入数据库

            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            $rdsname = "getOrderList_".$only_userid."*";
            redisdelall($rdsname);//删除订单列表
            $rdsname = "TuiSong*";
            redisdelall($rdsname);//删除推送缓存
            if($order['pay_code'] == 'weixin'){
                $weixinPay = new WeixinpayController();
                //微信JS支付 && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')
                if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1 ){
                    $code_str = $weixinPay->getJSAPI($order);
                    $pay_detail = $code_str;
                }else{
                    $pay_detail = $weixinPay->addwxorder($order['order_sn']);
                }
            }elseif($order['pay_code'] == 'alipay'){
                $AliPay = new AlipayController();
				$pay_detail = $AliPay->addAlipayOrder($order['order_sn']);
			}elseif($order['pay_code'] == 'alipay_wap'){ // 添加手机网页版支付 2017-5-25 hua
				$AlipayWap = new AlipayWapController();
				$pay_detail = $AlipayWap->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
            }elseif($order['pay_code'] == 'qpay'){
                $qqPay = new QQPayController();
                $pay_detail = $qqPay->getQQPay($order);
            }
            $json = array('status'=>1,'msg'=>'购买成功','result'=>array('order_id'=>$o_id,'pay_detail'=>$pay_detail));
            $this->aftermath($user_id,$goods,$num,$o_id);
            if(!empty($ajax_get)){
                //echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }else{
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'购买失败');
            redisdelall("getBuy_lock_" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
        }
    }

    //获取coupon_list的id，将用户的优惠券状态改掉
    public function changeCouponStatus($coupon_list_id,$order_id)
    {
        $coupon_data['is_use'] = 1;
        $coupon_data['use_time'] = time();
        $coupon_data['order_id'] = $order_id;
        $res = M('coupon_list')->where('`id`='.$coupon_list_id)->data($coupon_data)->save();
        return $res;
    }

    public function getInvitationNum()//获取邀请码
    {
        $string = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $code='';
        for($i=0;$i<6;$i++)
        {
            $end = rand(0,35);
            $code = $code.substr($string,$end,1);
        }

        $test = M('order')->where('`invitation_num`='.$code)->find();
        if(!empty($test))
            $code = $this->getInvitationNum();
        return $code;
    }

    public function aftermath($user_id,$goods,$num,$o_id){
        $this->order_redis_status_ref($user_id);
        if($goods['is_special']==7){
            M('goods_activity')->where('`goods_id` = '.$goods['goods_id'])->setDec('quantity',$num);
        }
        //销量、库存//商品规格库存
        M('goods')->where('`goods_id` = '.$goods['goods_id'])->setDec('store_count',$num);
        $spec_name = M('order_goods')->where('`order_id`='.$o_id)->field('spec_key,store_id')->find();
        M('spec_goods_price')->where("`goods_id`=$goods[goods_id] and `key`='$spec_name[spec_key]'")->setDec('store_count',$num);
    }
}