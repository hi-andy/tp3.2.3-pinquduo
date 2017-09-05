<?php
/**
 * User: admin
 */

namespace Api_2_0_2\Controller;

class StoreController extends BaseController{

    public function _initialize() {
	    header("Access-Control-Allow-Origin:*");
        $this->encryption();
    }

    /**
     * 更新销量自动脚本
     */
    public function store_sales(){
        $merchant = redislist("set_sales_id");
        if ($merchant) M()->query("update tp_merchant set sales=(select SUM(sales) as a from tp_goods where store_id = {$merchant}) where id={$merchant}");
    }

	/*
	 * 商户详情
	 * */
	public function getStoreList()
	{
		$store_id = I('store_id');
		$stor = I('stor','sales');//last_update
		$version = I('version');
		$page = I('page',1);
		$pagesize = I('pagesize',10);
		$rdsname = "getStoreList".$store_id.$stor.$page.$pagesize.$version;
		if(empty(redis($rdsname))) {//判断是否有缓存
			/*
			 * merchant 商户表
			 * store_name 商户名
			 * mobile 电话
			 * store_logo 商户logo
			 * sales 销量
			 * introduce 简介
			 * */

			$store = M('merchant')->where('`id` = ' . $store_id)->field('store_name,mobile,store_logo,sales,introduce')->find();
			$store['store_share_url'] = C('SHARE_URL').'/shop_detail.html?store_id=' . $store_id;//商户分享URL
			$store['logo_share_url'] = $store['store_logo']."?imageView2/1/w/80/h/80/q/75%7Cwatermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9zdHJvZV9sb2dvLmpwZw==/dissolve/100/gravity/South/dx/0/dy/0%7Cimageslim/dissolve/100/gravity/South/dx/0/dy/0";//分享logo
			$store['store_logo'] = TransformationImgurl($store['store_logo']);
			//获取店铺优惠卷store_logo_compression
			//获取商户有的优惠券
			$coupon = M('coupon')->where('`store_id` = ' . $store_id . ' and `send_start_time` <= ' . time() . ' and `send_end_time` >= ' . time() . ' and createnum!=send_num')->select();
			if (empty($coupon)) {
				$coupon = null;
			}
			$where = '`is_special` != 8 and `the_raise` = 0 and `show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_audit`=1 and `store_id` = ' . $store_id;
			//$data = $this->getGoodsList($where,$page,$pagesize,"$stor desc ");
			$data = $this->getGoodsList($where,$page,$pagesize,"store_count>0 desc,shop_sort desc,$stor desc");
			$store['coupon'] = $coupon;
			$store['goods'] = $data;
			$json = array('status' => 1, 'msg' => '', 'result' => array('id'=>$store_id,'store_name' => $store['store_name'],'mobile' => $store['mobile'],'store_logo' => $store['store_logo'],'sales' => $store['sales'],'introduce' => $store['introduce'],'store_share_url' => $store['store_share_url'],'logo_share_url' => $store['logo_share_url'], 'goods' => $data, 'coupon' => $coupon));

			redis($rdsname, serialize($json), REDISTIME);//写入缓存
		} else {
			$json = unserialize(redis($rdsname));//读取缓存
		}
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}
	/*
     * 易掌柜接口
     * */
	//
	function Y_login()
	{
		$store_name = I('post.store_name');
		$store_pass_word = I('post.pass_word');
		$y_time = I('post.timeStamp');

		$res = M('merchant')->where("merchant_name = '$store_name' and password = '".md5($store_pass_word)."'")->find();

		if(!empty($res)){
			if($res['state']==0) {
				exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂时没有营业，请及时客服沟通')));
			}elseif($res['is_check']==0){
				exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂时还没审核')));
			}elseif($res['is_check']==2){
				exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂审核未通过')));
			}else{
				M('merchant')->where('id = '.$res['id'])->save(array('y_time'=>$y_time));
				exit(json_encode(array('code'=>1,'Msg'=>'登录成功','store_id'=>$res['id'])));
			}
		}else{
			exit(json_encode(array('code'=>0,'Msg'=>'没有该店铺')));
		}
	}

	function Y_orderlist()
	{
		$store_id = I('store_id',910);//商户ID
		$page = I('page',1);//页码
		$page_num = I('page_num',10);//分页变量
		I('start_time') && $start_time = I('start_time');
		I('end_time') && $end_time = I('end_time');
		I('order_sn') && $order_sn = I('order_sn');

		$where = "o.store_id = $store_id and o.order_type in (2,14) and o.consignee is not null";
		if (!empty($start_time) && !empty($end_time)) {
			$where = "$where and o.add_time between $start_time and $end_time ";
		}
		if (!empty($order_sn)){
			$where = "$where and o.order_sn = $order_sn";
			$order_info = M('order')->alias('o')
				->join('INNER JOIN tp_merchant m on o.store_id = m.id')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where($where)
				->field('o.order_id,o.order_sn,o.address,o.address_base,o.goods_id,o.order_amount,o.consignee,o.user_id,o.mobile,m.store_name,g.original_img,o.add_time')
				->select();
		}else{
			$order_info = M('order')->alias('o')
				->join('INNER JOIN tp_merchant m on o.store_id = m.id')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where($where)
				->field('o.order_id,o.order_sn,o.address,o.address_base,o.goods_id,o.order_amount,o.consignee,o.user_id,o.mobile,m.store_name,g.original_img,o.add_time')
				->page($page,$page_num)
				->order('o.order_id asc')
				->select();
		}
		/*处理订单数组的数据*/
		if(!empty($order_info)){
			//处理地址问题
			$num = count($order_info);
			for ($i=0;$i<$num;$i++){
				$adress_info = getAdress($order_info[$i]['address_base']);
				$order_info[$i]['province'] =$adress_info['province'];//省
				$order_info[$i]['city'] = $adress_info['city'];//市
				$order_info[$i]['district'] = $adress_info['district'];//区
				$order_info[$i]['street'] = $order_info[$i]['address'];

				$goods_info = M('order_goods')->alias('og')
					->join('INNER JOIN tp_order o on og.goods_id = o.goods_id')
					->where('og.order_id = '.$order_info[$i]['order_id'])
					->field('og.goods_name,og.goods_id,og.market_price,og.goods_price,og.goods_num,og.spec_key_name')
					->limit($page,$page_num)
					->order('og.order_id asc')
					->find();

				$order_info[$i]['goods'] = $goods_info;
				//unset()掉多余数据
				unset($order_info[$i]['address_base']);
				unset($order_info[$i]['address']);
				unset($order_info[$i]['goods_id']);
				unset($order_info[$i]['user_id']);
			}
			exit(json_encode(array('code'=>1,'Msg'=>'获取成功！','data'=>$order_info)));
		}else{
			exit(json_encode(array('code'=>0,'Msg'=>'您暂时还没有新的未发货订单哦！')));
		}
	}

	function Y_changestatus()
	{
		$store_id = I('post.store_id');
		$data['order_sn'] = I('post.order_sn');
		$data['shipping_order'] = I('post.deliverSn');
		$data['shipping_name'] = I('post.deliverName');
		$data['shipping_code'] = I('post.deliverCode');
		$data['y_time'] = I('post.timeStamp');
		$data['shipping_order'] = (I('post.deliverSn'));
		$code = $data['shipping_code'] = I('post.deliverCode');
		$data['y_time'] = I('post.timeStamp');

		$logistics = array('shunfeng'=>'顺丰','shentong'=>'申通','youzhengguonei'=>'邮政包裹/平邮','yuantong'=>'圆通','zhongtong'=>'中通','huitongkuaidi'=>'百世物流','yunda'=>'韵达','zhaijisong'=>'宅急送','tiantian'=>'天天','youzhengguoji'=>'国际包裹','ems'=>'EMS','emsguoji'=>'EMS-国际件','huitongkuaidi'=>'汇通','debangwuliu'=>'德邦','guotongkuaidi'=>'国通','zengyisudi'=>'增益','suer'=>'速尔','zhongtiewuliu'=>'中铁快运','ganzhongnengda'=>'能达','youshuwuliu'=>'优速','quanfengkuaidi'=>'全峰','kuaijiesudi'=>'快捷','wanxiangwuliu'=>'万象','tiandihuayu'=>'天地华宇','annengwuliu'=>'安能','longbanwuliu'=>'龙邦物流');
		$data['shipping_name'] = $logistics[$code];
		M()->startTrans();
		$res = M('order')->where('store_id = '.$store_id.' and order_sn = '.$data['order_sn'])->save(array('shipping_code'=>$data['shipping_code'],'shipping_order'=>$data['shipping_order'],'shipping_name'=>$data['shipping_name'],'y_time'=>$data['y_time']));
		$res1 = $this->deliveryHandle($data);
		if($res && $res1){
			M()->commit();
			exit(json_encode(array('code'=>1,'Msg'=>'发货成功！')));
		}elseif (!$res){
			M()->rollback();
			exit(json_encode(array('code'=>0,'Msg'=>'订单修改失败')));
		}else{
			M()->rollback();
			exit(json_encode(array('code'=>0,'Msg'=>'创建发货订单失败')));
		}
	}

	function deliveryHandle($data)
	{
		$order = M('order')->where('`order_sn` = '.$data['order_sn'])->find();
		$datas['order_id'] = $order['order_id'];
		$datas['order_sn'] = $order['order_sn'];
		$datas['store_id'] = $order['store_id'];
		$datas['zipcode'] = $order['zipcode'];
		$datas['user_id'] = $order['user_id'];
		$datas['admin_id'] = 1;
		$datas['consignee'] = $order['consignee'];
		$datas['mobile'] = $order['mobile'];
		$datas['address_base'] = $order['address_base'];
		$datas['address'] = $order['address'];
		$datas['shipping_name']= $data['shipping_name'];
		$datas['shipping_order'] = $data['shipping_order'];
		$datas['shipping_code'] = $data['shipping_code'];
		$datas['shipping_price'] = 0;
		$datas['create_time'] = time();
		$datas['ytime'] = $data['y_time'];
		$did = M('delivery_doc')->add($datas);

		$this->order_redis_status_ref($order['user_id']);

		$goods = M('goods')->where('`goods_id`='.$order['goods_id'])->find();
		if(!empty($order['prom_id'])){
			$updata['order_status'] = $action['order_status'] = 11;
			$updata['order_type'] = $action['order_type'] = 15;
		}else{
			$updata['order_type'] = $action['order_type'] = 3;
		}
		if($goods['is_special']==1)
		{
			$updata['automatic_time'] = time()+30*24*60*60;
		}else{
			$updata['automatic_time'] = time()+15*24*60*60;
		}
		reserve_logistics($order['order_id']);
		$res1 = M('order')->where("order_sn=".$data['order_sn'])->save($updata);//改变订单状态

		return $did and $res1;
	}
	//多维数组去重
	function mult_unique($array)
	{
		$return = array();
		foreach($array as $key=>$v)
		{
			if(!in_array($v, $return))
			{
				$return[$key]=$v;
			}
		}
		return $return;
	}

	function test(){

	}
}