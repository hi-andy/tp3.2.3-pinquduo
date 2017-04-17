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

namespace Api\Controller;


class GroupBuyController
{

    /**
     * 参加拼团
     */
    public function joinGroupBuy($parameter){
        $order_id = $parameter['order_id'];
        $user_id = $parameter['user_id'];
        $data = array();
        $order = array();
        $address_id = $parameter['address_id'];
        $goods_id = $parameter['goods_id'];
        $spec_key = $parameter['spec_key'];
        $coupon_id = $parameter['coupon_id'];
        $ajax_get =  $parameter['ajax_get'];
        $num = $parameter['num'];
        $coupon_list_id = $parameter['coupon_list_id'];
        M()->startTrans();//开启事务处理
        //找到参加的这张单
        $result = M('group_buy')->where("`order_id` = $order_id")->find();

        //是否使用优惠卷
        if(!empty($coupon_id))
        {
            $coupon = M('coupon')->where('`id`='.$coupon_id)->field('money')->find();
        }else{
            $coupon['money'] = 0;
        }
        //找到开团的商品,并获取要用的数据
        $goods_id = $result['goods_id'];
        $goods = M('goods')->where('`goods_id` = '.$goods_id)->find();
        //找到开团的商品,并获取要用的数据
        if(!empty($spec_key))
        {
            $goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->find();
            $goods['prom_price']=(string)($goods_spec['prom_price']);
        }else{
            $goods_spec['key_name']= '默认';
            $goods['prom_price']=(string)$goods['prom_price'];
        }
        $free = $result['free'];
        $prom = $result['goods_num'];
        if(!empty($free))//是否免单
        {
            if(!empty($prom))
            {
                $goods['prom_price'] = (string)($goods['prom_price']*$prom/($prom-$free));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$free));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }
        }

        //如果是众筹订单
        if($result['is_raise']==1)
        {
            $data['is_raise']=1;
            $order['the_raise']=1;
        }else{
            $data['is_raise']=0;
            $order['the_raise']=0;
        }
        if($result)
        {
            //是否使用优惠卷
            if(!empty($coupon_id))
            {
                $coupon = M('coupon')->where('`id`='.$coupon_id)->field('money')->find();
            }else{
                $coupon['money'] = 0;
            }
            //在团购表加一张单
            $data['start_time'] = time();
            $data['end_time'] = $result['end_time'];
            $data['goods_id'] = $result['goods_id'];
            $data['price'] = (string)($goods['prom_price']-$coupon['money'])*$num;
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
            $invitation_num = M('order')->where('`order_id`='.$order_id)->field('invitation_num')->find();
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
            if(I('code')=='weixin')
            {
                $order['pay_code'] = 'weixin' ;
                $order['pay_name'] = '微信支付';
            }
            elseif(I('code')=='alipay')
            {
                $order['pay_code'] = 'alipay' ;
                $order['pay_name'] = '支付宝支付';
            }elseif(I('code')=='qpay')
            {
                $order['pay_code'] = 'qpay';
                $order['pay_name'] = 'QQ钱包支付';
            }
            $order['goods_price'] = $order['total_amount'] = $goods['prom_price']*$num;
            $order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
            $order['coupon_price'] = $coupon['money'];
            I('coupon_list_id') && $order['coupon_list_id'] = $coupon_list_id;
            I('coupon_id') && $order['coupon_id'] = $coupon_id;
            $order['add_time'] = $order['pay_time'] = time();
            $order['prom_id'] = $group_buy;
            $order['free'] = $result['free'];
            $order['store_id'] = $result['store_id'];
            $order['num'] = $num;
            if(!empty($ajax_get))
            {
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
            if(!empty($spec_key))
            {
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
                    $json = array('status'=>-1,'msg'=>'参团失败');
                    if(!empty($ajax_get)){
                        echo "<script> alert('".$json['msg']."') </script>";
                        exit;
                    }
                    exit(json_encode($json));
                }
            }

            //将订单号写会团购表
            $res = M('group_buy')->where("`id` = $group_buy")->data(array('order_id'=>$o_id))->save();
            if(!empty($res) )
            {
                M()->commit();//都操作成功的时候才真的把数据放入数据库
                if($order['pay_code']=='weixin'){
                    $weixinPay = new WeixinpayController();
                    //微信JS支付 && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')
                    if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1){
                        $order['order_id'] = $order_id;
                        $code_str = $weixinPay->getJSAPI($order);
                        $pay_detail = $code_str;
                    }else{
                        $pay_detail = $weixinPay->addwxorder($order['order_sn']);
                    }
                }elseif($order['pay_code'] == 'alipay'){
                    $AliPay = new AlipayController();
                    $pay_detail = $AliPay->addAlipayOrder($order['order_sn']);
                }elseif($order['pay_code'] == 'qpay'){
                    $qqPay = new QQPayController();
                    $pay_detail = $qqPay->getQQPay($order);
                }
                $json = array('status'=>1,'msg'=>'参团成功','result'=>array('order_id'=>$o_id,'group_id'=>$group_buy,'pay_detail'=>$pay_detail));
                $rdsname = "getUserOrderList".$user_id."*";
                redisdelall($rdsname);//删除用户订单缓存
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
                exit(json_encode($json));
            }else{
                M()->rollback();//有数据库操作不成功时进行数据回滚
                $json = array('status'=>-1,'msg'=>'参团失败');
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
        $latitude = $parameter['latitude'];
        $longitude = $parameter['longitude'];
        $user_id = $parameter['user_id'];
        $store_id = $parameter['store_id'];
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
        if(!empty($spec_key))
        {
            $goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->field('key_name,prom_price')->find();
            $goods['prom_price']=(string)($goods_spec['prom_price']);
        }else{
            $goods_spec['key_name']= '默认';
            $goods['prom_price']=(string)$goods['prom_price'];
        }
        if(!empty($free))//是否免单
        {
            if(!empty($prom))
            {
                $goods['prom_price'] = (string)($goods['prom_price']*$prom/($prom-$free));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$free));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }
        }
        elseif(!empty($goods['free']))
        {
            if(!empty($prom))
            {
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$goods['free']));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }else{
                $goods['prom_price'] = (string)($goods['prom_price']*$goods['prom']/($goods['prom']-$goods['free']));
                $count = $this->getFloatLength($goods['prom_price']);
                if($count>3)
                {
                    $price = $this->operationPrice($goods['prom_price']);
                    $goods['prom_price'] = $price-$coupon['money'];
                }
            }
        }

        //在团购表加单
        $data['start_time'] = time();
        $data['end_time'] = time()+24*60*60;
        $data['goods_id'] = $goods_id;
        if(!empty($prom))
        {
            $data['goods_num'] = $prom;
        }else{
            $data['goods_num'] = $goods['prom'];
        }
        $data['order_num'] = 1;
        $data['buy_num'] = $data['order_num'] = 1;
        $data['price'] = $goods['prom_price']*$num;
        $data['intro'] = $goods['goods_name'];
        $data['goods_price'] = $goods['market_price'];
        $data['goods_name'] = $goods['goods_name'];
        $data['photo'] = '/Public/upload/logo/logo.jpg';
        $data['latitude'] = $latitude;
        $data['longitude'] = $longitude;
        $data['mark'] = 0;
        $data['user_id'] = $user_id;
        $data['store_id'] = $store_id;
        $data['address_id'] = $address_id;
        $data['free'] = $free;
        //如果是众筹订单
        if($goods['the_raise']==1)
        {
            $data['is_raise']=1;
        }
        $group_buy = M('group_buy')->data($data)->add();

//			var_dump($group_buy);
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
        if(I('code')=='weixin')
        {
            $order['pay_code'] = 'weixin' ;
            $order['pay_name'] = '微信支付';
        }
        elseif(I('code')=='alipay')
        {
            $order['pay_code'] = 'alipay' ;
            $order['pay_name'] = '支付宝支付';
        }elseif(I('code')=='qpay')
        {
            $order['pay_code'] = 'qpay';
            $order['pay_name'] = 'QQ钱包支付';
        }
        $order['goods_price'] = $goods['market_price'];
        $order['total_amount'] = $goods['prom_price']*$num;
        $order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
        $order['coupon_price'] = $coupon['money'];
        I('coupon_list_id') && $order['coupon_list_id'] = $coupon_list_id;
        I('coupon_id') && $order['coupon_id'] = $coupon_id;
        $order['add_time'] = $order['pay_time'] = time();
        $order['store_id'] = $store_id;
        $order['prom_id'] = $group_buy;
        $order['free'] = $free;
        $order['num'] = $num;
        //如果是众筹订单
        if($goods['the_raise']==1)
        {
            $order['the_raise']=1;
        }
        if(!empty($ajax_get))
        {
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
        $spec_data['store_id'] = $store_id;
        $spec_res = M('order_goods')->data($spec_data)->add();
        if(empty($spec_res) || empty($group_buy) || empty($o_id))
        {
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'开团失败');
//			if(!empty($ajax_get))
//				$this->getJsonp($json);
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
//				if(!empty($ajax_get))
//					$this->getJsonp($json);
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
            }elseif($order['pay_code'] == 'qpay'){
                // Begin code by lcy
                $qqPay = new QQPayController();
                $pay_detail = $qqPay->getQQPay($order);
                // End code by lcy
            }
            $json = array('status'=>1,'msg'=>'开团成功','result'=>array('order_id'=>$o_id,'group_id'=>$group_buy,'pay_detail'=>$pay_detail));
//			if(!empty($ajax_get))
//				$this->getJsonp($json);
            $rdsname = "getUserOrderList".$user_id."*";
            redisdelall($rdsname);//删除用户订单缓存
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }else{
            M()->rollback();//有数据库操作不成功时进行数据回滚
            $json = array('status'=>-1,'msg'=>'开团失败');
//			if(!empty($ajax_get))
//				$this->getJsonp($json);
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
            exit(json_encode($json));
        }
    }
}