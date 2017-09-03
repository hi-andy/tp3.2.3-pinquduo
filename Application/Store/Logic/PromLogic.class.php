<?php


namespace Store\Logic;


class PromLogic
{
    /**
     * @param array $condition  搜索条件
     * @param string $order   排序方式
     * @param int $start    limit开始行
     * @param int $page_size  获取数量
     */
    public function getOrderList($condition,$order='',$start=0,$page_size=20){
        $res = M('order')->where($condition)->where('`prom_id` != 0')->limit("$start,$page_size")->order($order)->select();
//	    $BASE = new BaseController();

//	    for($i=0;$i<count($res);$i++)
//	    {
//		    $mark = M('group_buy')->where('`id` = '.$res[$i]['prom_id'])->field('id,goods_name,end_time,store_id,end_time,goods_num,order_id,goods_id,goods_price,mark,goods_num,end_time')->find();
//		    $num = M('group_buy')->where('`mark` = '.$mark['id'])->count();
//		    $data = $BASE->getPromStatus($res[$i],$mark,$num);
//		    $res[$i]['annotation'] = $data['annotation'];
//		    $res[$i]['order_type'] = $data['order_type'];
//	    }

        return $res;
    }
    /*
     * 获取订单商品详情
     */
    public function getOrderGoods($order_id){
//        $sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods o ".
//            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = $order_id";
//
//        $res = $this->query($sql);
//	    $res = M('order_goods')->alias('o')->join('LEFT JOIN __GOODS__ g ON o.goods_id = g.goods_id')
//		       ->where(array('o.order_id'=>$order_id))->field('g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total')
//		       ->find();

		$res = M('order_goods')->where('`order_id`='.$order_id)->select();

        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
        //  订单总金额查询语句
//        $total_fee = " (goods_price + shipping_price - discount - coupon_price) AS total_fee ";
//        $sql = "SELECT *, " . $total_fee . " FROM __PREFIX__order WHERE order_id = '$order_id'";
	    $total = M('order')->where('`order_id`='.$order_id)->find();

//        $res[0]['address2'] = $this->getAddressName($res[0]['province'],$res[0]['city'],$res[0]['district']);
	    $total['address'] = $total['address_base'].' '.$total['address'];
        return $total;
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
					    $arr['store_id'] = $_SESSION['merchant_id'];
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
        $data['action_user'] = $_SESSION['merchant_id'];
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
	    $data['store_id'] = $_SESSION['merchant_id'];
		$res = M('order_action')->where("order_id=".$data['order_id'])->find();
	    if(!empty($res)){
		    $r = M('order_action')->where("order_id=".$data['order_id'])->save($data);
	    }else{
		    $r = M('order_action')->add($data);
	    }
//        if($action == 'delivery_confirm'){
//        	order_give($order);//确认收货
//        }

        return  $r;//订单操作记录
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
    public function getOrderButton($group,$order){
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
                  12 => '未成团',
                  13 => '未成团',
                  14 => '已成团',
                  15 => '已成团',
                  16 => '作废',
              ),
           */
	    $ot = $order['order_type'];//订单状态
	    $btn = array();
	    if($ot==10 || $ot==1){
		    $btn['cancel'] = '取消';
	    }elseif($ot==14 || $ot==2){
		    $btn['delivery'] = '去发货';
	    }elseif($ot==15 || $ot==3){
//			$btn['delivery_confirm'] = '确认收货';
	    }elseif($ot==5){
		    $btn['remove'] = '删除';
	    }elseif($ot==6){
		    $btn['refund'] = '申请退换货';
	    }

		return $btn;
    }

    
    public function orderProcessHandle($order_id,$act){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
    			$updata['pay_status'] = 1;
    			$updata['pay_time'] = time();
    			break;
    		case 'pay_cancel': //取消付款
    			$updata['pay_status'] = 0;
    			break;
    		case 'confirm': //确认订单
    			$updata['order_status'] = 1;
    			break;
    		case 'cancel': //取消确认
    			$updata['order_status'] = 0;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_status'] = 5;
    			break;
    		case 'remove': //移除订单
			    $a = $this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			$updata['order_status'] = 2;
    			$updata['confirm_time'] = time();
    			$updata['pay_status'] = 1;
    			break;	
    		default:
    			return true;
    	}
	    if($a)
	    {
		    M('order')->where("order_id=$order_id")->save($updata);
		    return $a;
	    }else{
		    return M('order')->where("order_id=$order_id")->save($updata);//改变订单状态
	    }

    }
    
    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data){
		$order = M('order')->where('`order_id`='.$data['order_id'])->find();
		$orderGoods = $this->getOrderGoods($data['order_id']);
		$data['order_sn'] = $order['order_sn'];
		$data['user_id'] = $order['user_id'];
	    $data['store_id'] = session('merchant_id');
		$data['consignee'] = $order['consignee'];
		$data['mobile'] = $order['mobile'];
	    $data['address_base'] = $order['address_base'];
		$data['district'] = $order['order_sn'];
		$data['address'] = $order['address'];
		$data['shipping_name'] = M('logistics')->where(array('logistics_code'=>$data['shipping_code']))->getField('logistics_name');
		$data['shipping_price'] = $order['shipping_price'];
		$data['create_time'] = time();
		$did = M('delivery_doc')->add($data);
	    $res['is_send'] = 1;
	    $res['delivery_id'] = $did;
	    $r = M('order_goods')->where("rec_id=".$orderGoods[0]['rec_id'])->save($res);//改变订单商品发货状态

	    $goods = M('goods')->where('`goods_id`='.$orderGoods[0]['goods_id'])->find();
	    $updata['order_status'] = $action['order_status'] = 11;
	    $updata['order_type'] = $action['order_type'] = 15;
	    $action['store_id'] = session('merchant_id');
	    $action['shipping_status'] = 1;
	    $action['order_id'] = $data['order_id'];
	    $action['pay_status'] = 1;
	    $action['action_note'] = $data['note'];
	    $action['log_time'] = time();
	    M('order_action')->add($action);

	    $updata['shipping_status'] = 1;
	    $updata['delivery_time'] = time();
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
        $base = new \Api_2_0_2\Controller\BaseController();
        $base->order_redis_status_ref($order['user_id']);
		return $s && $r;
    }

	function buchongfahuoxinxi($data){
		$order = M('order')->where('`order_id`='.$data['order_id'])->find();;
		$orderGoods = $this->getOrderGoods($data['order_id']);
//		$selectgoods = $data['goods'];
		$data['order_sn'] = $order['order_sn'];
		$data['zipcode'] = $order['zipcode'];
		$data['user_id'] = $order['user_id'];
		$data['store_id'] = session('merchant_id');
		$data['consignee'] = $order['consignee'];
		$data['mobile'] = $order['mobile'];
		$data['address_base'] = $order['address_base'];
		$data['district'] = $order['order_sn'];
		$data['address'] = $order['address'];
		$data['shipping_name']= M('logistics')->where(array('logistics_code'=>$data['shipping_code']))->getField('logistics_name');
		$data['invoice_no'] = $data['shipping_order'];
		$data['shipping_price'] = $order['shipping_price'];
		$data['create_time'] = time();
		$did = M('delivery_doc')->where("order_id=".$data['order_id'])->save($data);

		$res['is_send'] = 1;
		$res['delivery_id'] = $did;
		$r = M('order_goods')->where("rec_id=".$orderGoods[0]['rec_id'])->save($res);//改变订单商品发货状态

		$goods = M('goods')->where('`goods_id`='.$orderGoods[0]['goods_id'])->find();
		$updata['order_type'] = $action['order_type'] = 3;
		$action['order_status'] = 1;
		$action['store_id'] = session('merchant_id');
		$action['shipping_status'] = 1;
		$action['order_id'] = $data['order_id'];
		$action['pay_status'] = 1;
		$action['action_note'] = $data['note'];
		$action['log_time'] = time();
		M('order_action')->where("order_id=".$data['order_id'])->save($action);

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
		$base = new \Api_2_0_0\Controller\BaseController();
		$base->order_redis_status_ref($order['user_id']);
		$s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志
		return $s ;
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
	        $data['is_show'] = 0;
	        $a = M('order')->where('order_id = '.$order_id)->data($data)->save();
//	    var_dump(M()->getLastSql());
	        $b = M('order_goods')->where(array('order_id'=>$order_id))->data($data)->save();
//	    var_dump(M()->getLastSql());die;
    	return $a && $b;
    }



}