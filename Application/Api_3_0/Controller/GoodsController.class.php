<?php
/**
 * api接口-商品管理模块
 */
namespace Api_3_0\Controller;

use Think\Page;
class GoodsController extends BaseController {

    public function _initialize() {
	    header("Access-Control-Allow-Origin:*");
        $this->encryption();
    }


	//新版本商品详情 2.0.2
	function getDetaile($refresh="")
	{
        $goods_id = I('goods_id');
        if (false){
            $json = array('status' => -1, 'msg' => '该商品已下架', 'result' => '');
        } else {
            $goods = $this->getGoodsInfo($goods_id);
            if($goods['is_special']==7){
                $f_goods_id = M('goods_activity')->where('goods_id='.$goods_id)->getField('f_goods_id');
                $banner=M('goods_images')->where(['goods_id'=>$f_goods_id,'is_del'=>0,'position'=>1])->field('img_id,image_url,width,height')->select();
            }else{
                $banner=M('goods_images')->where(['goods_id'=>$goods_id,'is_del'=>0,'position'=>1])->field('img_id,image_url,width,height')->select();
            }
            foreach ($banner as &$v) {
                $v['small'] = TransformationImgurl($v['image_url']);
                $v['origin'] = TransformationImgurl($v['image_url']);
                unset($v['image_url']);
            }
            if (empty($banner)) {
                $banner = null;
            }
            //商品规格
            $goodsLogic = new \Home\Logic\GoodsLogic();
            $spec_goods_price = M('spec_goods_price')->where("goods_id = $goods_id and is_del=0")->select(); // 规格 对应 价格 库存表
            $filter_spec = $goodsLogic->get_spec($goods_id,$goods['is_special']);//规格参数
            $new_spec_goods = array();
            foreach ($spec_goods_price as $spec) {
                $new_spec_goods[] = $spec;
            }
            $new_filter_spec = array();

            foreach ($filter_spec as $key => $filter) {
                $new_filter_spec[] = array('title' => $key, 'items' => $filter);
            }
            for ($i = 0; $i < count($new_filter_spec); $i++) {
                foreach ($new_filter_spec[$i]['items'] as & $v) {
                    if (!empty($v['src'])) {
                        $v['src'] = $v['src'];
                    }
                    $keys[] = $v['item_id'];
                }
                array_multisort($keys, SORT_ASC, $new_filter_spec[$i]['items'], SORT_ASC);
            }
            //如果有传规格过来就改变商品名字
            if (!empty($spec_key)) {
                $key_name = M('spec_goods_price')->where("`key`='$spec_key'")->field('key_name')->find();
                $goods['goods_spec_name'] = $goods['goods_name'] . $key_name['key_name'];
            }
            if (!empty($ajax_get)) {
                $goods['html'] = htmlspecialchars_decode($goods['goods_content']);
            }
            //提供保障
            if ($goods['is_special'] == 1) {
                $security = array(array('type' => '全场包邮', 'desc' => '所有商品均无条件包邮'), array('type' => '假一赔十', 'desc' => '若收到的商品是假货，可获得加倍赔偿'));
            } else {
                $security = array(array('type' => '全场包邮', 'desc' => '所有商品均无条件包邮'), array('type' => '7天退换', 'desc' => '商家承诺7天无理由退换货'), array('type' => '48小时发货', 'desc' => '成团后，商家将在48小时内发货'), array('type' => '假一赔十', 'desc' => '若收到的商品是假货，可获得加倍赔偿'));
            }
            $json = array(
                'status' => 1,
                'msg' => '获取成功',
                'result' => array(
                    'banner' => $banner,
                    'goods_id' => $goods['goods_id'],
                    'goods_name' => $goods['goods_name'],
                    'prom_price' => $goods['prom_price'],
                    'market_price' => $goods['market_price'],
                    'shop_price' => $goods['shop_price'],
                    'prom' => $goods['prom'],
                    'goods_remark' => $goods['goods_remark'],
                    'store_id' => $goods['store_id'],
                    'is_support_buy' => $goods['is_support_buy'],
                    'is_special' => $goods['is_special'],
                    'original_img' => $goods['original_img'],
                    'original'=>$goods['original'],
                    'goods_content_url' => $goods['goods_content_url'],
                    'goods_share_url' => $goods['goods_share_url'],
                    'fenxiang_url' => $goods['fenxiang_url'],
                    'collect' => $goods['collect'],
                    'original_img' => $goods['original_img'],
                    'img_arr' => $goods['img_arr'],
                    'security' => $security,
                    'store' => $goods['store'], 'spec_goods_price' => $new_spec_goods, 'filter_spec' => $new_filter_spec));
            $json['result']['goods_share_url'] = C('SHARE_URL') . '/goods_detail.html?goods_id=' . $goods_id;
        }
		exit(json_encode($json));
	}

	//获取当前商品的拓展数据 显示用户的收藏状态，销量，支持的购买类型
	function getDetaile_expand(){//加商户销量，
		$goods_id = I('goods_id');
		$user_id = I('user_id');
		I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($user_id)){ // 用户是否收藏
			$collect = M('goods_collect')->where('goods_id = '.$goods_id.' and user_id = '.$user_id)->count();
		}else{
			$collect = 0;
		}

		/*
		 * goods 商品表
		 * tp_merchant 商户表
		 * g.store_count 库存
		 * g.sales 销量
		 * g.is_special 商品类型
		 * g.on_time 秒杀时间
		 * g.is_support_buy 是否支持单买
 		 * m.sales 商户销量
		 * g.is_prom_buy 是否支持团购
		 * */
        $goods = M('goods')->alias('g')
	        ->join('INNER JOIN tp_merchant m on m.id = g.store_id')
	        ->where(array('g.goods_id'=>array('eq',$goods_id)))
	        ->field('g.store_count,g.sales,g.is_special,g.on_time,g.is_support_buy,m.sales as store_sales,g.is_prom_buy')
	        ->find();
		//默认
		$data['buy_type'] = 1;
		$data['prompt']=null;
		//判断特殊商品是否在可购买时间内
		if($goods['is_special']==7){//0.1秒杀
			$time = M('goods_activity')->where('goods_id='.$goods_id)->find();
			$res = $time['start_date']+$time['start_time']*3600;
			if($res<time()){
				if($goods['store_count']<=0){
					$data['buy_type'] = 2;
					$data['prompt']='该商品已售罄！^_^';
				}else{
					$data['buy_type'] = 1;
					$data['prompt']=null;
				}
			}else{
				$data['buy_type'] = 0;
				$data['prompt']='本场未开始哦T_T';
			}
		}elseif ($goods['is_special']==2){//限时秒杀
			if($goods['on_time']<time()){
				if($goods['store_count']<=0){
					$data['buy_type'] = 2;
					$data['prompt']='该商品已售罄！^_^';
				}else{
					$data['buy_type'] = 1;
					$data['prompt']=null;
				}
			}else{
				$data['buy_type'] = 0;
				$data['prompt']='本场未开始哦T_T';
			}
		}
		$data['support_prompt'] = '该商品不支持单买哦T_T';
		$data['prom_prompt'] = '该商品不支持团购哦T_T';
        $data['collect'] = $collect;
        $data['store_count'] = $goods['store_count'];
        $data['sales'] = $goods['sales'];
		$data['is_special'] = $goods['is_special'];
		$data['is_support_buy'] = $goods['is_support_buy'];
		$data['is_prom_buy'] = $goods['is_prom_buy'];
		$data['store_sales'] = $goods['store_sales'];
		$json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	//获取已经开好的团
	function  getAvailableGroup(){
		$goods_id = I('goods_id');
		if($goods_id == 27857){
			I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
			$json = array('status' => 1, 'msg' => '获取成功', 'result' => null);
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
		/*
		 * group_buy 团购表
		 * id 团id
		 * end_time  结束时间
		 * goods_id 商品id
		 * photo 团长头像
		 * goods_num 团人数
		 * user_id 用户id
		 * free 免单人数
		 * auto 机器人标识
		 * */
		$group_buy = M('group_buy')->where(" `goods_id` = $goods_id and `is_pay`=1 and `is_successful`=0 and `mark` =0 and `end_time`>=" . time())->field('id,end_time,goods_id,photo,goods_num,user_id,free,auto')->order('id asc')->limit(3)->select();
		if (!empty($group_buy)) {
			for ($i = 0; $i < count($group_buy); $i++) {
                if($group_buy[$i]['auto']!=1){
                    $order_id = M('order')->where('`prom_id`=' . $group_buy[$i]['id'] . ' and `is_return_or_exchange`=0')->field('order_id,prom_id')->find();
                    $group_buy[$i]['id'] = $order_id['prom_id'];
                }else{
                    $order_id['prom_id'] = $group_buy[$i]['id'];
                }

				$mens = M('group_buy')->where('`mark` = ' . $order_id['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();
				$group_buy[$i]['prom_mens'] = $group_buy[$i]['goods_num'] - $mens - 1;

				$user_name = M('users')->where('`user_id` = ' . $group_buy[$i]['user_id'])->field('nickname,oauth,mobile,head_pic')->find();
				if (!empty($user_name['oauth'])) {
					$group_buy[$i]['user_name'] = $user_name['nickname'];
					$group_buy[$i]['photo'] = TransformationImgurl($user_name['head_pic']);
				} else {
					$group_buy[$i]['photo'] = TransformationImgurl($user_name['head_pic']);
					$group_buy[$i]['user_name'] = substr_replace($user_name['mobile'], '****', 3, 4);
				}
			}
			foreach ($group_buy as &$v) {
				$v['photo'] = TransformationImgurl($v['photo']);
			}
		} else {
			$group_buy = null;
		}

		I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status' => 1, 'msg' => '获取成功', 'result' => array('group_buy' => $group_buy));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	function getGenerateOrder(){
		header("Access-Control-Allow-Origin:*");
		$user_id = I('user_id');
		$goods_id = I('goods_id');
		$num = I('num',1);
		$type = I('type');
		$spec_key = I('spec_key');
		$prom_id = I('prom_id');
		/*
				 * user_address 用户收货地址表
				 * address_id 地址id
				 * consignee 收货人
				 * address_base 基础住址
				 * address 详细住址
				 * mobile 电话号码
				 * is_default 是否默认地址
				 * */
		$user_address = M('user_address')->where("`user_id` = $user_id and `is_default` = 1")->field('address_id,consignee,address_base,address,mobile')->find();
		if(empty($user_address))
		{
			$user_address = M('user_address')->where("`user_id` = $user_id")->field('address_id,consignee,address_base,address,mobile')->find();
			if(empty($user_address)){
				$user_address = null;
			}
		}

		$goods = $this->getGoodsInfo($goods_id,1);
		//获取商品规格
		if(!empty($spec_key))
		{
			M('temporary_key')->add(array('goods_id'=>$goods_id,'goods_spec_key'=>$spec_key,'user_id'=>$user_id,'add_time'=>time()));
			$goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->field('key_name,price,prom_price')->find();
			$goods['shop_price']=$goods_spec['price'];
			$goods['prom_price']=$goods_spec['prom_price'];
			$goods['key_name'] = $goods_spec['key_name'];
		}else{
			$goods_spec['key_name']='默认';
			$goods_spec['price']=$goods['shop_price'];
			$goods_spec['prom_price']=$goods['prom_price'];
		}

		//用来获取优惠券的价格
		//0-》参团 1-》开团 2-》单买
		if($type==0)
		{
			$price = $goods['prom_price']*$num;
			$order_info = M('group_buy')->where(' id = '.$prom_id)->find();
		}
		elseif($type==1){
			$price = $goods_spec['prom_price']*$num;
			$order_info['goods_num'] = null;
			$order_info['free'] = null;
		}
		elseif($type==2) {
			$price = $goods_spec['price']*$num;
			$order_info['goods_num'] = null;
			$order_info['free'] = null;
		}
		else
		{
			$json = array('status'=>-1,'msg'=>'参数错误');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
		//获取合适的店铺优惠卷
		//找到该店铺里用户的全部优惠券
		$user_coupon = M('coupon_list')->where('`uid`='.$user_id.' and `store_id`='.$goods['store_id'].' and `is_use`=0')->field('id,cid')->select();
		if(!empty($user_coupon)) {
			$id = array_column($user_coupon, 'cid');
			//拿到所有优惠券，并根据condition倒叙输出,获取最佳优惠卷
			$coupon = M('coupon')->where('`id` in ('.join(',',$id).') and `condition`<='.$price.' and `use_end_time`>'.time())->order('`money` desc')->field('id,name,money,condition,use_start_time,use_end_time')->find();
			if(!empty($coupon))
			{
				//根据获取的最佳优惠券在coupon_list里面的优惠券id
				for ($i = 0; $i < count($user_coupon); $i++) {
					$user_coupon_list_id = M('coupon_list')->where('`cid`='.$user_coupon[$i]['cid'].' and `uid`='.$user_id.' and `is_use`=0')->find();
					if ($coupon['id'] == $user_coupon_list_id['cid']) {
						$coupon['coupon_list_id'] = $user_coupon[$i]['id'];
						break;
					}
				}
			}else{
				$coupon = null;
			}
		}else{
			$coupon = null;
		}
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('user'=>$user_address,'goods'=>$goods,'key_name'=>$goods_spec['key_name'],'prom_price'=>$goods_spec['prom_price'],'price'=>$goods_spec['price'],'prom'=>$order_info['goods_num'],'free'=>$order_info['free'],'coupon'=>$coupon));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

    //秒杀抢购
    public function panic_buying($user_id, $goods_id){
        $return_arr = "";
        if($user_id && $goods_id) {
            if(!empty(redis('goods_stock'.$goods_id)) && intval(redis('goods_stock'.$goods_id)) >= 1){//如果有库存
                $data['user_id'] = $user_id;
                $data['goods_id'] = $goods_id;
                redislist('getbuy_goods'.$goods_id, serialize($data));//写入队列
                redis('goods_stock'.$goods_id, intval(redis('goods_stock'.$goods_id)) - 1);//减库存
                $return_arr = array('status' => 1, 'msg' => '正在拼抢', 'data' => '',);
            } else {
                $return_arr = array('status' => -1, 'msg' => '还没开始', 'data' => '',);
            }
        } else {
            $return_arr = array('status' => -1, 'msg' => '参数错误', 'data' => '',);
        }
        if(!empty($ajax_get))
            $this->getJsonp($return_arr);
        exit(json_encode($return_arr));
    }

    //返回抢购结果
    public function stock_result($user_id, $goods_id){
        $return_arr = "";
        if($user_id && $goods_id) {
            $order = unserialize(redis("goods_stock_order".$user_id.$goods_id));//读取结果
            if ($order) {
                if ($order["result"] === true){
                    if($order['code'] == 'weixin')
                    {
                        $order['pay_code'] = 'weixin' ;
                        $order['pay_name'] = '微信支付';
                    }
                    elseif($order['code'] == 'alipay')
                    {
                        $order['pay_code'] = 'alipay' ;
                        $order['pay_name'] = '支付宝支付';
                    }elseif($order['code'] == 'qpay')
                    {
                        $order['pay_code'] = 'qpay';
                        $order['pay_name'] = 'QQ钱包支付';
                    }
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
                        $pay_detail = $AliPay->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
                    }elseif($order['pay_code'] == 'qpay'){
                        $qqPay = new QQPayController();
                        $pay_detail = $qqPay->getQQPay($order);
                    }
                } else {
                    $return_arr = array('status' => -1, 'msg' => '已被抢光', 'data' => '',);
                }
            }
        } else {
            $return_arr = array('status' => -1, 'msg' => '参数错误', 'data' => '',);
        }
        if(!empty($ajax_get))
            $this->getJsonp($return_arr);
        exit(json_encode($return_arr));
    }

	//将限时秒杀的商品id写成缓存
	function  write_goodsid_RDS(){
		$time = $this->getTime();
		//获取秒杀的时间段
		$num = count($time);
		for ($i=0;$i<$num;$i++) {
			if ($i == 0) {
				$endtime = strtotime($time[0]['datetime'].':00:00');
				$starttime = $endtime - 3600;
			} elseif ($i == ($num - 1)) {
				$endtime = strtotime($time[$i]['datetime'].':00:00');
				$starttime = strtotime($time[$i - 1]['datetime'].':00:00');
			} else {
				$endtime = strtotime($time[$i + 1]['datetime'].':00:00');
				$starttime = strtotime($time[$i]['datetime'].':00:00');
			}
			$where = "`on_time` >= $starttime and `on_time` < $endtime and `is_show` = 1 and `show_type`=0 and `is_on_sale` = 1 and `is_special` = 2 and `is_audit`=1";

		}

	}

	function getTime(){
		$today_zero = strtotime(date('Y-m-d', time()));
		$today_zero2 = strtotime(date('Y-m-d', (time() + 2 * 24 * 3600)));
		$sql = "SELECT FROM_UNIXTIME(`on_time`,'%Y-%m-%d %H') as datetime from " . C('DB_PREFIX') . "goods WHERE `is_on_sale`=1 and `is_audit`=1 and `is_special` = 2 and `on_time`>=$today_zero and `on_time`<$today_zero2  GROUP BY `datetime`";
		$time = M('')->query($sql);

		return $time;
	}
}