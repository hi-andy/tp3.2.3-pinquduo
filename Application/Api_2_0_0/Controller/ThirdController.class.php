<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/5/22
 * Time: 17:51
 */
namespace Api_2_0_0\Controller;
class ThirdController {
	/*
     * 第三方接口
     * */
	function Third_login(){
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

	function Third_orderlist(){
		$store_id = I('post.store_id');//商户ID
		$page = I('post.page',1);//页码
		$page_num = I('post.page_num',10);//分页变量
		I('post.start_time') && $start_time = I('post.start_time');
		I('post.end_time') && $end_time = I('post.end_time');
		I('post.order_sn') && $order_sn = I('post.order_sn');

		$where = "o.store_id = $store_id and o.order_type in (2,14)";
		if (!empty($start_time) && !empty($end_time)) {
			$where = "$where and o.add_time between $start_time and $end_time ";
		}
		if (!empty($order_sn)){
			$where = "$where and o.order_sn = $order_sn";
			$count = M('order')->alias('o')
				->join('INNER JOIN tp_merchant m on o.store_id = m.id')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where($where)
				->count();

			$order_info = M('order')->alias('o')
				->join('INNER JOIN tp_merchant m on o.store_id = m.id')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where($where)
				->field('o.order_id,o.order_sn,o.address,o.address_base,o.goods_id,o.order_amount,o.consignee,o.user_id,o.mobile,m.store_name,g.original_img,o.add_time')
				->select();
		}else{
			$count = M('order')->alias('o')
				->join('INNER JOIN tp_merchant m on o.store_id = m.id')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where($where)
				->count();

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
				$adress_info = $this->getAdress($order_info[$i]['address_base']);
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
			$data = $this->listPageData($count,$order_info);

			exit(json_encode(array('code'=>1,'Msg'=>'获取成功！','data'=>$data)));
		}else{
			exit(json_encode(array('code'=>0,'Msg'=>'您暂时还没有新的未发货订单哦！')));
		}
	}

	function Third_changestatus()
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

		$logistics = array('shunfeng'=>'顺丰','shentong'=>'申通','youzhengguonei'=>'邮政包裹/平邮','yuantong'=>'圆通','zhongtong'=>'中通','huitongkuaidi'=>'百世物流','yunda'=>'韵达','zhaijisong'=>'宅急送','tiantian'=>'天天','youzhengguoji'=>'国际包裹','ems'=>'EMS','emsguoji'=>'EMS-国际件','huitongkuaidi'=>'汇通','debangwuliu'=>'德邦','guotongkuaidi'=>'国通','zengyisudi'=>'增益','suer'=>'速尔','zhongtiewuliu'=>'中铁快运','ganzhongnengda'=>'能达','youshuwuliu'=>'优速','quanfengkuaidi'=>'全峰','kuaijiesudi'=>'快捷','wanxiangwuliu'=>'万象','tiandihuayu'=>'天地华宇','annengwuliu'=>'安能');
		$data['shipping_name'] = $logistics[$code];
		M()->startTrans();
		$res = M('order')->where('store_id = '.$store_id.' and order_sn = '.$data['order_sn'])->save(array('shipping_code'=>$data['shipping_code'],'shipping_order'=>$data['shipping_order'],'shipping_name'=>$data['shipping_name']));
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

		$goods = M('goods')->where('`goods_id`='.$order['goods_id'])->find();

		$updata['shipping_code'] = $data['shipping_code'];
		$updata['shipping_name'] = $data['shipping_name'];
		$updata['shipping_order'] = $data['shipping_order'];
		$updata['shipping_price'] = $order['shipping_price'];
		$updata['shipping_status'] = 1;
		if(!empty($order['prom_id'])){
			$updata['order_status'] = $action['order_status'] = 11;
			$updata['order_type'] = 15;
		}else{
			$updata['order_status'] = 1;
			$updata['order_type'] = 3;
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

	//地址处理问题
	function getAdress($adress)
	{
		$cha = $adress;
		//新疆维吾尔自治区伊犁哈萨克自治州霍城县
		//判断字符串里面是否包含省
		if(strstr($cha,"省")){
			//按省市区切割
			$cha = explode('省',$cha);
			$province = $cha[0].'省';
			if(strstr($cha[1],'自治州'))
			{
				$cha = explode('自治州',$cha[1]);
				$city = $cha[0].'自治州';
			}elseif(strstr($cha[1],'地区')){
				$cha = explode('地区',$cha[1]);
				$city = $cha[0].'地区';
			}elseif(strstr($cha[1],'自治区')) {
				$cha = explode('自治区', $cha[1]);
				$city = $cha[0] . '自治区';
			}else{
				$cunt = substr_count($cha[1],'市');
				if($cunt>1){
					$cha = explode('市',$cha[1]);
					$cha[1] = '市'.$cha[2];
					$city = $cha[0].'市';
				}else{
					$cha = explode('市',$cha[1]);
					$city = $cha[0].'市';
				}
			}
			$area = $cha[1];
		}elseif(strstr($cha,"北京市") || strstr($cha,"天津市") || strstr($cha,"上海市") ||strstr($cha,"重庆市")){//判断是否为直辖市
			//按市区切割
			$cha = explode('市',$cha);
			$province = $cha[0].'市';
			$city = $cha[0].'市';
			$area = $cha[2];
		}elseif(strstr($cha,"内蒙古自治区") || strstr($cha,"广西壮族自治区") || strstr($cha,"宁夏回族自治区") || strstr($cha,"西藏自治区") || strstr($cha,"新疆维吾尔自治区")){ //判断是否为自治区
			//按自治区切割
			$cha = explode('自治区',$cha);
			$province = $cha[0].'自治区';
			if(strstr($cha[1],"盟")){
				$cha = explode('盟',$cha[1]);
				$city = $cha[0].'盟';
				$area = $cha[1];
			}elseif(strstr($cha[1],"地区")){
				$cha = explode('地区',$cha[1]);
				$city = $cha[0].'地区';
				$area = $cha[1];
			}elseif(strstr($cha[1],"自治州")){
				$cha = explode('自治州',$cha[1]);
				$city = $cha[0].'自治州';
				$area = $cha[1];
			}elseif(strstr($cha[1],"市")){
				$cha = explode('市',$cha[1]);
				$city = $cha[0].'市';
				$area = $cha[1];
			}
		}elseif(strstr($cha,"行政区"))
		{
			$cha = explode('行政区',$cha);
			$province = $cha[0].'行政区';
			$city = $province;
			$area = $province;
		}
		$adress_arry['province'] =$province;
		$adress_arry['city'] = $city;
		$adress_arry['district'] = $area;
		return $adress_arry;
	}
}