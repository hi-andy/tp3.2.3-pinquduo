<<<<<<< HEAD
<?php


namespace Admin\Logic;
use Think\Model\RelationModel;
use Api\Controller\QQPayController;

class OrderLogic extends RelationModel
{
    /**
     * @param array $condition  搜索条件
     * @param string $order   排序方式
     * @param int $start    limit开始行
     * @param int $page_size  获取数量
     */
    public function getOrderList($condition,$order='',$start=0,$page_size=20){
	    $prom_id = "o.prom_id is null";
	    $res = M('order')->alias('o')
		    ->join('INNER JOIN tp_order_goods d on d.order_id = o.order_id')
		    ->join('INNER JOIN tp_merchant m on o.store_id = m.id')
		    ->field('o.*,d.goods_price,m.store_name')
		    ->where($condition)->where($prom_id)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }
    /*
     * 获取订单商品详情
     */
    public function getOrderGoods($order_id){
        //$sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods o ".
        $sql = "SELECT g.*,o.*,(o.goods_num * o.goods_price) AS goods_total FROM __PREFIX__order_goods o ".
            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = $order_id";
        $res = $this->query($sql);

        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
		//  订单总金额查询语句
		$total_fee = " (order_amount + shipping_price - discount - coupon_price) AS total_fee ";
		$sql = "SELECT *, " . $total_fee . " FROM __PREFIX__order WHERE order_id = '$order_id'";
		$res = $this->query($sql);
		$res[0]['address2'] = $this->getAddressName($res[0]['province'],$res[0]['city'],$res[0]['district']);
		$res[0]['address2'] = $res[0]['address2'].$res[0]['address'];
		return $res[0];
    }

    /*
     * 根据商品型号获取商品
     */
    public function get_spec_goods($goods_id_arr){
    	if(!is_array($goods_id_arr)) return false;
    		foreach($goods_id_arr as $key => $val)
    		{
    			$arr = array();
    			$goods = M('goods')->where("goods_id = $key")->find();
    			$arr['goods_id'] = $key; // 商品id
    			$arr['goods_name'] = $goods['goods_name'];
    			$arr['goods_sn'] = $goods['goods_sn'];
    			$arr['market_price'] = $goods['market_price'];
    			$arr['goods_price'] = $goods['shop_price'];
    			$arr['cost_price'] = $goods['cost_price'];
    			$arr['member_goods_price'] = $goods['shop_price'];
    			foreach($val as $k => $v)
    			{
    				$arr['goods_num'] = $v['goods_num']; // 购买数量
    				// 如果这商品有规格
    				if($k != 'key')
    				{
    					$arr['spec_key'] = $k;
    					$spec_goods = M('spec_goods_price')->where("goods_id = $key and `key` = '{$k}'")->find();
    					$arr['spec_key_name'] = $spec_goods['key_name'];
    					$arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
    					$arr['bar_code'] = $spec_goods['bar_code'];
    				}
    				$order_goods[] = $arr;
    			}
    		}
    		return $order_goods;
    }

    /*
     * 订单操作记录
     */
    public function orderActionLog($order_id,$action,$note=''){
        $order = M('order')->where(array('order_id'=>$order_id))->find();
        $data['order_id'] = $order_id;
        $data['action_user'] = session('admin_id');
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
//        if($action == 'delivery_confirm'){
//        	order_give($order);//确认收货
//        }
        return M('order_action')->add($data);//订单操作记录
    }

    /*
     * 获取订单商品总价格
     */
    public function getGoodsAmount($order_id){
        $sql = "SELECT SUM(goods_num * goods_price) AS goods_amount FROM __PREFIX__order_goods WHERE order_id = {$order_id}";
        $res = $this->query($sql);
        return $res[0]['goods_amount'];
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
        /* 选择一个随机的方案 */send_http_status('310');
		mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /*
     * 获取当前可操作的按钮
     */
    public function getOrderButton($order){
        /*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         */
	    $ot = $order['order_type'];//订单状态
        $btn = array();
	    if($ot==10 || $ot==1){
		    $btn['cancel'] = '取消';
	    }elseif($ot==14 || $ot==2){
		    $btn['delivery'] = '去发货';
	    }elseif($ot==15 || $ot==3){
//		    $btn['delivery_confirm'] = '确认收货';
	    }elseif($ot==5){
//		    $btn['remove'] = '删除';
	    }elseif($ot==6){
//		    $btn['refund'] = '申请退换货';
	    }
		    $btn['punishment'] = '惩罚';

//        if($ps==1) {
//        	if($ss==0&&$order['is_canel']!=1){
//        		$btn['delivery'] = '去发货';
//				$btn['pay_cancel'] = '设为未付款';
//        	}
//        }else{
//        	if($ps==0&&$order['is_canel']!=1){
//        		$btn['pay'] = '付款';
//        	}
////	        elseif($ps == 1){
////        		$btn['pay_cancel'] = '设为未付款';
////        		$btn['confirm'] = '确认';
////        	}
//	        elseif($ps==1 && $ss==0 && ){
//        		$btn['delivery'] = '去发货';
//        	}
//        }

//        if($ss == 1 && $ps == 1){
//        	$btn['delivery_confirm'] = '确认收货';
//        	$btn['refund'] = '申请退货';
//        }else{
//			$btn['invalid'] = '无效';
//		}
        return $btn;
    }


	/*
  * 获取当前可操作的按钮
  */
	public function getOrderButton_group($order,$group){
		/*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         *'ORDER_TYPE' => array(
				1 => '未付款',
				2 => '待发货',
				3 => '已收货',
				4 => '已完成',
				5 => '已取消',
				6 => '待退款',
				7 => '已退款',
				8 => '待退货',
				9 => '已退货',
				10 => '拼团中',
				11 => '拼团中',
				13 => '未成团',
				14 => '已成团',
				15 => '已成团',
				16 => '作废',
			),
         */
		$os = $order['order_status']; 	//订单状态
		$ss = $order['shipping_status'];//发货状态
		$ps = $order['pay_status'];	  	//支付状态
		$ot = $order['order_type'];   	//订单类型
		$re= $order['is_return_or_exchange'];
		$is_cancel = $order['is_cancel'];
//		$the_raise = $order['the_raise'];    //是否众筹发货
//		$is_fee = $order['is_fee'];
//
//		$btn = array();
//
//		if($ps == 1){
//			if( $ss == 0 && $group['goods_num'] == $group['order_num'] && $order['mark'] == 0 ){
//				$btn['delivery'] = '去发货';
//			}else{
//				$btn['pay_cancel'] = '设为未付款';
//			}
//		}else{
//			if($order['goods_num'] < $order['order_num']){
//				$btn['pay'] = '付款';
//			}else{
//				$btn['remove'] = '移除';
//			}
//		}
//		if($ss == 1 && $ps==1 && $group['mark'] == 0 ){
//			$btn['delivery_confirm'] = '确认收货';
//			$btn['refund'] = '申请退货';
//		}

//		if( $group['goods_num'] == $group['order_num'] && $ot == 6 && $ps==1 && $is_fee==1){
//			$btn['backpay'] = '退款';
//		}
		$ot = $order['order_type'];//订单状态
		$btn = array();
		if($ot==10 || $ot==1){
			$btn['cancel'] = '取消';
		}elseif($ot==14 || $ot==2){
			$btn['delivery'] = '去发货';
		}elseif($ot==15 || $ot==3){
//			$btn['delivery_confirm'] = '确认收货';
		}elseif($ot==5){
//			$btn['remove'] = '删除';
		}elseif($ot==6){
//			$btn['refund'] = '申请退换货';
		}

		$btn['punishment'] = '惩罚';


		return $btn;
	}


	public function orderProcessHandle($order_id,$act){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
    			$updata['order_type'] = 7;
    			$updata['pay_time'] = time();
    			break;
    		case 'pay_cancel': //取消付款
    			$updata['order_type'] = 1;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_type'] = 5;
    			break;
			case 'backpay':
				$this->backPay($order_id);
				return true;
				break;
    		case 'remove': //移除订单
    			$this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			$updata['order_type'] = 2;
    			$updata['confirm_time'] = time();
    			$updata['pay_status'] = 1;
    			break;
    		default:
    			return true;
    	}
    	return M('order')->where("order_id=$order_id")->save($updata);//改变订单状态
    }

	/**
	 * 处理退款
	 */
	public function backPay($order_id){
		$order_info = M('order')->where(array('order_id'=>$order_id))->find();

		if($order_info['pay_code'] =='weixin'){
			$result = $this->weixinBackPay($order_info['order_sn'],$order_info['order_amount']);
		}elseif($order_info['pay_code'] == 'alipay'){
			$result = $this->alipayBackPay($order_info['order_sn'],$order_info['order_amount']);
		}elseif($order_info['pay_code'] == 'qpay'){
			// Begin code by lcy
			$qqPay = new QQPayController();
			$result = $qqPay->doRefund($order_info['order_sn'], $order_info['order_amount']);
			// End code by lcy
		}

		if($result['status'] == 1){
			$data['out_refund_no'] = $result['out_refund_no'];
			$data['order_type'] = 7;
			M('order')->where(array('order_id'=>$order_id))->save($data);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 微信退款接口
	 */
	public function weixinBackPay($out_trade_no,$refund_fee){
		vendor('WxPay.WxPayPubHelper.WxPayPubHelper');

		//商户退款单号，商户自定义，此处仅作举例
		$out_refund_no = "$out_trade_no".time();
		//使用退款接口
		$refund = new \Refund_pub();
		//设置必填参数
		$refund->setParameter("out_trade_no","$out_trade_no");     //商户订单号
		$refund->setParameter("out_refund_no","$out_refund_no");   //商户退款单号
		$refund->setParameter("total_fee",$refund_fee*100);        //总金额
		$refund->setParameter("refund_fee",$refund_fee*100);       //退款金额
		$refund->setParameter("op_user_id",\WxPayConf_pub::MCHID); //操作员

		//调用结果
		$refundResult = $refund->getResult();

		//商户根据实际情况设置相应的处理流程,此处仅作举例
		if ($refundResult["return_code"] == "FAIL") {
			return array('status'=>0,'msg'=>"通信出错：".$refundResult['return_msg']."<br>");
		}
		else{
			$msg = "业务结果：".$refundResult['result_code']."<br>";
			$msg .= "错误代码：".$refundResult['err_code']."<br>";
			$msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
			$msg .= "公众账号ID：".$refundResult['appid']."<br>";
			$msg .= "商户号：".$refundResult['mch_id']."<br>";
			$msg .= "子商户号：".$refundResult['sub_mch_id']."<br>";
			$msg .= "设备号：".$refundResult['device_info']."<br>";
			$msg .= "签名：".$refundResult['sign']."<br>";
			$msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
			$msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
			$msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
			$msg .= "微信退款单号：".$refundResult['refund_idrefund_id']."<br>";
			$msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
			$msg .= "退款金额：".$refundResult['refund_fee']."<br>";
			$msg .= "现金券退款金额：".$refundResult['coupon_refund_fee']."<br>";

			return array('status'=>1,'msg'=>$msg,'out_refund_no'=>$out_refund_no);
		}
	}

    /**
     * 微信商城退款接口
     */
    public function weixinJsBackPay($out_trade_no,$refund_fee){
        require_once("plugins/payment/weixin/lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("plugins/payment/weixin/example/WxPay.NativePay.php");
        require_once("plugins/payment/weixin/example/WxPay.JsApiPay.php");

        //商户退款单号，商户自定义，此处仅作举例
        $out_refund_no = "$out_trade_no".time();
        $order_info = M('order')->where(array('order_sn'=>$out_trade_no))->find();
        //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
        $total_fee =  	$refund_fee * 100;
        $refund_fee = $refund_fee * 100;
        //使用退款接口
        $refund = new \WxPayRefund();
        //设置必填参数
        $refund->SetOut_trade_no($out_trade_no);    //商户订单号
        $refund->SetOut_refund_no($out_refund_no);  //商户退款单号
        $refund->SetTotal_fee($total_fee);          //总金额
        $refund->SetRefund_fee($refund_fee);        //退款金额
        $refund->SetOp_user_id(1405319302);         //操作员

        $WxPay = new \WxPayApi();
        $refundResult = $WxPay->refund($refund,30);

        //商户根据实际情况设置相应的处理流程,此处仅作举例
        if ($refundResult["return_code"] == "FAIL") {
            return array('status'=>0,'msg'=>"通信出错：".$refundResult['return_msg']."<br>");
        }
        else{
            $msg = "业务结果：".$refundResult['result_code']."<br>";
            $msg .= "错误代码：".$refundResult['err_code']."<br>";
            $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：".$refundResult['appid']."<br>";
            $msg .= "商户号：".$refundResult['mch_id']."<br>";
            $msg .= "子商户号：".$refundResult['sub_mch_id']."<br>";
            $msg .= "设备号：".$refundResult['device_info']."<br>";
            $msg .= "签名：".$refundResult['sign']."<br>";
            $msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
            $msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
            $msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
            $msg .= "微信退款单号：".$refundResult['refund_idrefund_id']."<br>";
            $msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
            $msg .= "退款金额：".$refundResult['refund_fee']."<br>";
            $msg .= "现金券退款金额：".$refundResult['coupon_refund_fee']."<br>";

            return array('status'=>1,'msg'=>$msg,'out_refund_no'=>$out_refund_no);
        }
    }

	/**
	 * 支付宝退款
	 */
	public function  alipayBackPay($out_trade_no,$refund_fee){
		include_once("plugins/payment/alipay/aop/AopClient.php");

		$aop = new \AopClient();

		$out_refund_no = "$out_trade_no".time();

		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = '2016111602882242';
		$aop->rsaPrivateKeyFilePath = C('alipay_config')['private_key_path'];
		$aop->alipayPublicKey= C('alipay_config')['ali_public_key_path'];
		$aop->apiVersion = '1.0';
		$aop->postCharset='UTF-8';
		$aop->format='json';

		include_once("plugins/payment/alipay/aop/request/AlipayTradeRefundRequest.php");

		$request = new \AlipayTradeRefundRequest();
		$request->setBizContent("{" .
			"    \"out_trade_no\":\"".$out_trade_no."\"," .
			"    \"trade_no\":\"\"," .
			"    \"refund_amount\":".$refund_fee."," .
			"    \"refund_reason\":\"正常退款\"," .
			"    \"out_request_no\":\"HZ01RF001\"," .
			"    \"operator_id\":\"OP001\"," .
			"    \"store_id\":\"NJ_S_001\"," .
			"    \"terminal_id\":\"NJ_T_001\"" .
			"  }");
		$result = $aop->execute($request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		if(!empty($resultCode) && $resultCode == 10000){
			return array('status'=>1,'msg'=>'退款成功','out_refund_no'=>$out_refund_no);
		} else {
			return array('status'=>0,'msg'=>'退款失败','out_refund_no'=>$out_refund_no);
		}

	}

    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data){
	    $order = M('order')->where('`order_id`='.$data['order_id'])->find();;
	    $orderGoods = $this->getOrderGoods($data['order_id']);
	    $data['order_sn'] = $order['order_sn'];
	    $data['zipcode'] = $order['zipcode'];
	    $data['user_id'] = $order['user_id'];
		$data['admin_id'] = session('admin_id');
	    $data['consignee'] = $order['consignee'];
	    $data['mobile'] = $order['mobile'];
	    $data['address_base'] = $order['address_base'];
	    $data['address'] = $order['address'];
	    $data['shipping_name']= M('logistics')->where(array('logistics_code'=>$data['shipping_code']))->getField('logistics_name');
	    $data['invoice_no'] = $data['shipping_order'];
	    $data['shipping_price'] = $order['shipping_price'];
	    $data['create_time'] = time();
	    $did = M('delivery_doc')->add($data);

	    $res['is_send'] = 1;
	    $res['delivery_id'] = $did;
	    $r = M('order_goods')->where("rec_id=".$orderGoods[0]['rec_id'])->save($res);//改变订单商品发货状态

	    $goods = M('goods')->where('`goods_id`='.$orderGoods[0]['goods_id'])->find();
	    if(!empty($order['prom_id'])){
		    $updata['order_status'] = $action['order_status'] = 11;
		    $updata['order_type'] = $action['order_type'] = 15;
	    }else{
		    $updata['order_type'] = $action['order_type'] = 3;
		    $action['order_status'] = 1;
	    }
	    $action['store_id'] = session('merchant_id');
	    $action['shipping_status'] = 1;
	    $action['order_id'] = $data['order_id'];
	    $action['pay_status'] = 1;
	    $action['action_note'] = $data['note'];
	    $action['log_time'] = time();
	    M('order_action')->add($action);

	    $updata['shipping_status'] = 1;
	    $updata['shipping_code'] = $data['shipping_code'];
	    $updata['shipping_name'] = $data['shipping_name'];
	    $updata['shipping_order'] = $data['shipping_order'];
	    $updata['shipping_price'] = $order['shipping_price'];
	    if($goods['is_special']==1)
	    {
		    $updata['automatic_time'] = time()+30*24*60*60;
	    }else{
		    $updata['automatic_time'] = time()+15*24*60*60;
	    }
	    M('order')->where("order_id=".$data['order_id'])->save($updata);//改变订单状态
	    $s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志
	    return $s && $r;
    }

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0){
        $p = M('region')->where(array('id'=>$p))->field('name')->find();
        $c = M('region')->where(array('id'=>$c))->field('name')->find();
        $d = M('region')->where(array('id'=>$d))->field('name')->find();
        return $p['name'].','.$c['name'].','.$d['name'].',';
    }

    /**
     * 删除订单
     */
    function delOrder($order_id){
    	$a = M('order')->where(array('order_id'=>$order_id))->delete();
    	$b = M('order_goods')->where(array('order_id'=>$order_id))->delete();
    	return $a && $b;
    }

}
=======
<?php


namespace Admin\Logic;
use Think\Model\RelationModel;
use Api\Controller\QQPayController;

class OrderLogic extends RelationModel
{
    /**
     * @param array $condition  搜索条件
     * @param string $order   排序方式
     * @param int $start    limit开始行
     * @param int $page_size  获取数量
     */
    public function getOrderList($condition,$order='',$start=0,$page_size=20){
	    $prom_id = "o.prom_id is null";
	    $res = M('order')->alias('o')
		    ->join('INNER JOIN tp_order_goods d on d.order_id = o.order_id')
		    ->join('INNER JOIN tp_merchant m on o.store_id = m.id')
		    ->field('o.*,d.goods_price,m.store_name')
		    ->where($condition)->where($prom_id)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }
    /*
     * 获取订单商品详情
     */
    public function getOrderGoods($order_id){
        //$sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods o ".
        $sql = "SELECT g.*,o.*,(o.goods_num * o.goods_price) AS goods_total FROM __PREFIX__order_goods o ".
            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = $order_id";
        $res = $this->query($sql);

        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
		//  订单总金额查询语句
		$total_fee = " (order_amount + shipping_price - discount - coupon_price) AS total_fee ";
		$sql = "SELECT *, " . $total_fee . " FROM __PREFIX__order WHERE order_id = '$order_id'";
		$res = $this->query($sql);
		$res[0]['address2'] = $this->getAddressName($res[0]['province'],$res[0]['city'],$res[0]['district']);
		$res[0]['address2'] = $res[0]['address2'].$res[0]['address'];
		return $res[0];
    }

    /*
     * 根据商品型号获取商品
     */
    public function get_spec_goods($goods_id_arr){
    	if(!is_array($goods_id_arr)) return false;
    		foreach($goods_id_arr as $key => $val)
    		{
    			$arr = array();
    			$goods = M('goods')->where("goods_id = $key")->find();
    			$arr['goods_id'] = $key; // 商品id
    			$arr['goods_name'] = $goods['goods_name'];
    			$arr['goods_sn'] = $goods['goods_sn'];
    			$arr['market_price'] = $goods['market_price'];
    			$arr['goods_price'] = $goods['shop_price'];
    			$arr['cost_price'] = $goods['cost_price'];
    			$arr['member_goods_price'] = $goods['shop_price'];
    			foreach($val as $k => $v)
    			{
    				$arr['goods_num'] = $v['goods_num']; // 购买数量
    				// 如果这商品有规格
    				if($k != 'key')
    				{
    					$arr['spec_key'] = $k;
    					$spec_goods = M('spec_goods_price')->where("goods_id = $key and `key` = '{$k}'")->find();
    					$arr['spec_key_name'] = $spec_goods['key_name'];
    					$arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
    					$arr['bar_code'] = $spec_goods['bar_code'];
    				}
    				$order_goods[] = $arr;
    			}
    		}
    		return $order_goods;
    }

    /*
     * 订单操作记录
     */
    public function orderActionLog($order_id,$action,$note=''){
        $order = M('order')->where(array('order_id'=>$order_id))->find();
        $data['order_id'] = $order_id;
        $data['action_user'] = session('admin_id');
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
//        if($action == 'delivery_confirm'){
//        	order_give($order);//确认收货
//        }
        return M('order_action')->add($data);//订单操作记录
    }

    /*
     * 获取订单商品总价格
     */
    public function getGoodsAmount($order_id){
        $sql = "SELECT SUM(goods_num * goods_price) AS goods_amount FROM __PREFIX__order_goods WHERE order_id = {$order_id}";
        $res = $this->query($sql);
        return $res[0]['goods_amount'];
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
        /* 选择一个随机的方案 */send_http_status('310');
		mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /*
     * 获取当前可操作的按钮
     */
    public function getOrderButton($order){
        /*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         */
	    $ot = $order['order_type'];//订单状态
        $btn = array();
	    if($ot==10 || $ot==1){
		    $btn['cancel'] = '取消';
	    }elseif($ot==14 || $ot==2){
		    $btn['delivery'] = '去发货';
	    }elseif($ot==15 || $ot==3){
//		    $btn['delivery_confirm'] = '确认收货';
	    }elseif($ot==5){
//		    $btn['remove'] = '删除';
	    }elseif($ot==6){
//		    $btn['refund'] = '申请退换货';
	    }
		    $btn['punishment'] = '惩罚';

//        if($ps==1) {
//        	if($ss==0&&$order['is_canel']!=1){
//        		$btn['delivery'] = '去发货';
//				$btn['pay_cancel'] = '设为未付款';
//        	}
//        }else{
//        	if($ps==0&&$order['is_canel']!=1){
//        		$btn['pay'] = '付款';
//        	}
////	        elseif($ps == 1){
////        		$btn['pay_cancel'] = '设为未付款';
////        		$btn['confirm'] = '确认';
////        	}
//	        elseif($ps==1 && $ss==0 && ){
//        		$btn['delivery'] = '去发货';
//        	}
//        }

//        if($ss == 1 && $ps == 1){
//        	$btn['delivery_confirm'] = '确认收货';
//        	$btn['refund'] = '申请退货';
//        }else{
//			$btn['invalid'] = '无效';
//		}
        return $btn;
    }


	/*
  * 获取当前可操作的按钮
  */
	public function getOrderButton_group($order,$group){
		/*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         *'ORDER_TYPE' => array(
				1 => '未付款',
				2 => '待发货',
				3 => '已收货',
				4 => '已完成',
				5 => '已取消',
				6 => '待退款',
				7 => '已退款',
				8 => '待退货',
				9 => '已退货',
				10 => '拼团中',
				11 => '拼团中',
				13 => '未成团',
				14 => '已成团',
				15 => '已成团',
				16 => '作废',
			),
         */
		$os = $order['order_status']; 	//订单状态
		$ss = $order['shipping_status'];//发货状态
		$ps = $order['pay_status'];	  	//支付状态
		$ot = $order['order_type'];   	//订单类型
		$re= $order['is_return_or_exchange'];
		$is_cancel = $order['is_cancel'];
//		$the_raise = $order['the_raise'];    //是否众筹发货
//		$is_fee = $order['is_fee'];
//
//		$btn = array();
//
//		if($ps == 1){
//			if( $ss == 0 && $group['goods_num'] == $group['order_num'] && $order['mark'] == 0 ){
//				$btn['delivery'] = '去发货';
//			}else{
//				$btn['pay_cancel'] = '设为未付款';
//			}
//		}else{
//			if($order['goods_num'] < $order['order_num']){
//				$btn['pay'] = '付款';
//			}else{
//				$btn['remove'] = '移除';
//			}
//		}
//		if($ss == 1 && $ps==1 && $group['mark'] == 0 ){
//			$btn['delivery_confirm'] = '确认收货';
//			$btn['refund'] = '申请退货';
//		}

//		if( $group['goods_num'] == $group['order_num'] && $ot == 6 && $ps==1 && $is_fee==1){
//			$btn['backpay'] = '退款';
//		}
		$ot = $order['order_type'];//订单状态
		$btn = array();
		if($ot==10 || $ot==1){
			$btn['cancel'] = '取消';
		}elseif($ot==14 || $ot==2){
			$btn['delivery'] = '去发货';
		}elseif($ot==15 || $ot==3){
//			$btn['delivery_confirm'] = '确认收货';
		}elseif($ot==5){
//			$btn['remove'] = '删除';
		}elseif($ot==6){
//			$btn['refund'] = '申请退换货';
		}

		$btn['punishment'] = '惩罚';


		return $btn;
	}


	public function orderProcessHandle($order_id,$act){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
    			$updata['order_type'] = 7;
    			$updata['pay_time'] = time();
    			break;
    		case 'pay_cancel': //取消付款
    			$updata['order_type'] = 1;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_type'] = 5;
    			break;
			case 'backpay':
				$this->backPay($order_id);
				return true;
				break;
    		case 'remove': //移除订单
    			$this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			$updata['order_type'] = 2;
    			$updata['confirm_time'] = time();
    			$updata['pay_status'] = 1;
    			break;
    		default:
    			return true;
    	}
    	return M('order')->where("order_id=$order_id")->save($updata);//改变订单状态
    }

	/**
	 * 处理退款
	 */
	public function backPay($order_id){
		$order_info = M('order')->where(array('order_id'=>$order_id))->find();

		if($order_info['pay_code'] =='weixin'){
			$result = $this->weixinBackPay($order_info['order_sn'],$order_info['order_amount']);
		}elseif($order_info['pay_code'] == 'alipay'){
			$result = $this->alipayBackPay($order_info['order_sn'],$order_info['order_amount']);
		}elseif($order_info['pay_code'] == 'qpay'){
			// Begin code by lcy
			$qqPay = new QQPayController();
			$result = $qqPay->doRefund($order_info['order_sn'], $order_info['order_amount']);
			// End code by lcy
		}

		if($result['status'] == 1){
			$data['out_refund_no'] = $result['out_refund_no'];
			$data['order_type'] = 7;
			M('order')->where(array('order_id'=>$order_id))->save($data);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 微信退款接口
	 */
	public function weixinBackPay($out_trade_no,$refund_fee){
		vendor('WxPay.WxPayPubHelper.WxPayPubHelper');

		//商户退款单号，商户自定义，此处仅作举例
		$out_refund_no = "$out_trade_no".time();
		//使用退款接口
		$refund = new \Refund_pub();
		//设置必填参数
		$refund->setParameter("out_trade_no","$out_trade_no");     //商户订单号
		$refund->setParameter("out_refund_no","$out_refund_no");   //商户退款单号
		$refund->setParameter("total_fee",$refund_fee*100);        //总金额
		$refund->setParameter("refund_fee",$refund_fee*100);       //退款金额
		$refund->setParameter("op_user_id",\WxPayConf_pub::MCHID); //操作员

		//调用结果
		$refundResult = $refund->getResult();

		//商户根据实际情况设置相应的处理流程,此处仅作举例
		if ($refundResult["return_code"] == "FAIL") {
			return array('status'=>0,'msg'=>"通信出错：".$refundResult['return_msg']."<br>");
		}
		else{
			$msg = "业务结果：".$refundResult['result_code']."<br>";
			$msg .= "错误代码：".$refundResult['err_code']."<br>";
			$msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
			$msg .= "公众账号ID：".$refundResult['appid']."<br>";
			$msg .= "商户号：".$refundResult['mch_id']."<br>";
			$msg .= "子商户号：".$refundResult['sub_mch_id']."<br>";
			$msg .= "设备号：".$refundResult['device_info']."<br>";
			$msg .= "签名：".$refundResult['sign']."<br>";
			$msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
			$msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
			$msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
			$msg .= "微信退款单号：".$refundResult['refund_idrefund_id']."<br>";
			$msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
			$msg .= "退款金额：".$refundResult['refund_fee']."<br>";
			$msg .= "现金券退款金额：".$refundResult['coupon_refund_fee']."<br>";

			return array('status'=>1,'msg'=>$msg,'out_refund_no'=>$out_refund_no);
		}
	}

    /**
     * 微信商城退款接口
     */
    public function weixinJsBackPay($out_trade_no,$refund_fee){
        require_once("plugins/payment/weixin/lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("plugins/payment/weixin/example/WxPay.NativePay.php");
        require_once("plugins/payment/weixin/example/WxPay.JsApiPay.php");

        //商户退款单号，商户自定义，此处仅作举例
        $out_refund_no = "$out_trade_no".time();
        $order_info = M('order')->where(array('order_sn'=>$out_trade_no))->find();
        //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
        $total_fee =  	$refund_fee * 100;
        $refund_fee = $refund_fee * 100;
        //使用退款接口
        $refund = new \WxPayRefund();
        //设置必填参数
        $refund->SetOut_trade_no($out_trade_no);    //商户订单号
        $refund->SetOut_refund_no($out_refund_no);  //商户退款单号
        $refund->SetTotal_fee($total_fee);          //总金额
        $refund->SetRefund_fee($refund_fee);        //退款金额
        $refund->SetOp_user_id(1405319302);         //操作员

        $WxPay = new \WxPayApi();
        $refundResult = $WxPay->refund($refund,30);

        //商户根据实际情况设置相应的处理流程,此处仅作举例
        if ($refundResult["return_code"] == "FAIL") {
            return array('status'=>0,'msg'=>"通信出错：".$refundResult['return_msg']."<br>");
        }
        else{
            $msg = "业务结果：".$refundResult['result_code']."<br>";
            $msg .= "错误代码：".$refundResult['err_code']."<br>";
            $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：".$refundResult['appid']."<br>";
            $msg .= "商户号：".$refundResult['mch_id']."<br>";
            $msg .= "子商户号：".$refundResult['sub_mch_id']."<br>";
            $msg .= "设备号：".$refundResult['device_info']."<br>";
            $msg .= "签名：".$refundResult['sign']."<br>";
            $msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
            $msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
            $msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
            $msg .= "微信退款单号：".$refundResult['refund_idrefund_id']."<br>";
            $msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
            $msg .= "退款金额：".$refundResult['refund_fee']."<br>";
            $msg .= "现金券退款金额：".$refundResult['coupon_refund_fee']."<br>";

            return array('status'=>1,'msg'=>$msg,'out_refund_no'=>$out_refund_no);
        }
    }

	/**
	 * 支付宝退款
	 */
	public function  alipayBackPay($out_trade_no,$refund_fee){
		include_once("plugins/payment/alipay/aop/AopClient.php");

		$aop = new \AopClient();

		$out_refund_no = "$out_trade_no".time();

		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = '2016111602882242';
		$aop->rsaPrivateKeyFilePath = C('alipay_config')['private_key_path'];
		$aop->alipayPublicKey= C('alipay_config')['ali_public_key_path'];
		$aop->apiVersion = '1.0';
		$aop->postCharset='UTF-8';
		$aop->format='json';

		include_once("plugins/payment/alipay/aop/request/AlipayTradeRefundRequest.php");

		$request = new \AlipayTradeRefundRequest();
		$request->setBizContent("{" .
			"    \"out_trade_no\":\"".$out_trade_no."\"," .
			"    \"trade_no\":\"\"," .
			"    \"refund_amount\":".$refund_fee."," .
			"    \"refund_reason\":\"正常退款\"," .
			"    \"out_request_no\":\"HZ01RF001\"," .
			"    \"operator_id\":\"OP001\"," .
			"    \"store_id\":\"NJ_S_001\"," .
			"    \"terminal_id\":\"NJ_T_001\"" .
			"  }");
		$result = $aop->execute($request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		if(!empty($resultCode) && $resultCode == 10000){
			return array('status'=>1,'msg'=>'退款成功','out_refund_no'=>$out_refund_no);
		} else {
			return array('status'=>0,'msg'=>'退款失败','out_refund_no'=>$out_refund_no);
		}

	}

    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data){
	    $order = M('order')->where('`order_id`='.$data['order_id'])->find();;
	    $orderGoods = $this->getOrderGoods($data['order_id']);
//		$selectgoods = $data['goods'];
	    $data['order_sn'] = $order['order_sn'];
//		$data['delivery_sn'] = $this->get_delivery_sn();
	    $data['zipcode'] = $order['zipcode'];
	    $data['user_id'] = $order['user_id'];
		$data['admin_id'] = session('admin_id');
//	    $data['store_id'] = session('merchant_id');
	    $data['consignee'] = $order['consignee'];
	    $data['mobile'] = $order['mobile'];
//		$data['country'] = $order['country'];
//		$data['province'] = $order['province'];
//		$data['city'] = $order['district'];
	    $data['address_base'] = $order['address_base'];
//	    $data['district'] = $order['order_sn'];
	    $data['address'] = $order['address'];
	    $data['shipping_name']= M('logistics')->where(array('logistics_code'=>$data['shipping_code']))->getField('logistics_name');
	    $data['invoice_no'] = $data['shipping_order'];
	    $data['shipping_price'] = $order['shipping_price'];
	    $data['create_time'] = time();
	    $did = M('delivery_doc')->add($data);

//		foreach ($orderGoods as $k=>$v){
//			if($v['is_send'] == 1){
//				$is_delivery++;
//			}
//			if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
//				$res['is_send'] = 1;
//				$res['delivery_id'] = $did;
//				$r = M('order_goods')->where("rec_id=".$v['rec_id'])->save($res);//改变订单商品发货状态
//				$is_delivery++;
//			}
//		}
	    $res['is_send'] = 1;
	    $res['delivery_id'] = $did;
	    $r = M('order_goods')->where("rec_id=".$orderGoods[0]['rec_id'])->save($res);//改变订单商品发货状态

	    $goods = M('goods')->where('`goods_id`='.$orderGoods[0]['goods_id'])->find();
	    if(!empty($order['prom_id'])){
		    $updata['order_status'] = $action['order_status'] = 11;
		    $updata['order_type'] = $action['order_type'] = 15;
	    }else{
		    $updata['order_type'] = $action['order_type'] = 3;
		    $action['order_status'] = 1;
	    }
	    $action['store_id'] = session('merchant_id');
	    $action['shipping_status'] = 1;
	    $action['order_id'] = $data['order_id'];
	    $action['pay_status'] = 1;
	    $action['action_note'] = $data['note'];
	    $action['log_time'] = time();
	    M('order_action')->add($action);

	    $updata['shipping_status'] = 1;
	    $updata['shipping_code'] = $data['shipping_code'];
	    $updata['shipping_name'] = $data['shipping_name'];
	    $updata['shipping_order'] = $data['shipping_order'];
	    $updata['shipping_price'] = $order['shipping_price'];
	    if($goods['is_special']==1)
	    {
		    $updata['automatic_time'] = time()+30*24*60*60;
	    }else{
		    $updata['automatic_time'] = time()+15*24*60*60;
	    }
	    M('order')->where("order_id=".$data['order_id'])->save($updata);//改变订单状态
	    $s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志
	    return $s && $r;
    }

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0){
        $p = M('region')->where(array('id'=>$p))->field('name')->find();
        $c = M('region')->where(array('id'=>$c))->field('name')->find();
        $d = M('region')->where(array('id'=>$d))->field('name')->find();
        return $p['name'].','.$c['name'].','.$d['name'].',';
    }

    /**
     * 删除订单
     */
    function delOrder($order_id){
    	$a = M('order')->where(array('order_id'=>$order_id))->delete();
    	$b = M('order_goods')->where(array('order_id'=>$order_id))->delete();
    	return $a && $b;
    }

}
>>>>>>> 0b7f13d20f77f1260095c707f48567c3375029f4
