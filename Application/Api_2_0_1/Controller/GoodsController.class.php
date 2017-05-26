<?php
/**
 * api接口-商品管理模块
 */
namespace Api_2_0_1\Controller;

use Think\Page;
class GoodsController extends BaseController {

    public function _initialize() {
//        $this->encryption();
    }
    
    public function index(){
        $this->display();
    }

	//获取用户地址列表
	function getUserAddressList()
	{
		$user_id = I('user_id');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$a = M('user_address', '', 'DB_CONFIG2')->where('`user_id` = '.$user_id.' and `is_default` = 1')->field('address_id,consignee,address_base,address,mobile,is_default')->find();
		$b = M('user_address', '', 'DB_CONFIG2')->where('`user_id` = '.$user_id.' and `is_default` != 1')->field('address_id,consignee,address_base,address,mobile,is_default')->select();

		if(!empty($a)){
			$address[0] = $a;//把数组第一个放入默认地址
			for($i = 0;$i<count($b);$i++)
			{
				$address[$i+1] = $b[$i];
			}
		}else{
			$address = $b;
		}
		if(!empty($address))
		{
			$json = array('status'=>1,'msg'=>'获取成功','result'=>array('address'=>$address));
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}else{
			$json = array('status'=>0,'msg'=>'还没有收货地址哦，先添加吧','result'=>array('address'=>[]));
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
	}

	//查看全部
	function getMore(){
		$id = I('id');
		$type = I('type');
		$page=I('page',1);
		$pagesize = 20;
		$ajax_get=null;
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$rdsname = "getMore".$id.$type.$page.$pagesize.$ajax_get;
		if (empty(redis($rdsname))) {//判断是否有缓存
			$data = $this->getOtheyMore($id,$type,$page,$pagesize,$ajax_get);
			$json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
			redis($rdsname, serialize($json), REDISTIME);//写入缓存
		} else {
			$json = unserialize(redis($rdsname));//读取缓存
		}
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	function getOtheyMore($id,$type,$page,$pagesize,$ajax_get)
	{
		if($type==0){
			$parent_id = M('goods_category', '', 'DB_CONFIG2')->where('`parent_id`=0 and id != 10044 ')->field('id')->select();
			$ids =array(array_column($parent_id,'id'));
			if(in_array("$id", $ids[0])){
				$data = $this->getNextCat($id);
				return $data;
			}else{
				$condition['parent_id'] = $ids =array('in',array_column($parent_id,'id'));
				$parent_id2 = M('goods_category', '', 'DB_CONFIG2')->where($condition)->field('id')->select();
				$ids2 =array(array_column($parent_id2,'id'));
				if(in_array("$id", $ids2[0])){//确定为二级分类id
					//找到一级菜单的下级id
					$parent_cat = M('goods_category', '', 'DB_CONFIG2')->where('`parent_id`='.$id)->field('id')->select();
					$condition2['cat_id'] =array('in',array_column($parent_cat,'id'));
					$condition2['is_on_sale']=1;
					$condition2['is_show'] = 1;
					$condition2['is_audit'] =1;
					$condition2['show_type'] =0;
					$condition2['the_raise'] =0;
					$data = $this->getGoodsList($condition2,$page,$pagesize,'sort asc,sales desc');
					return $data;
				}else{
					$where = '`show_type`=0 and `cat_id`=' . $id . ' and is_show=1 and is_on_sale=1 and is_audit=1';
					$data = $this->getGoodsList($where,$page,$pagesize,'sort asc,sales desc');
					return $data;
				}
			}
		}elseif($type==1){
			if($id==0){//全部
				$where = '`is_special` = 1 and is_show=1 and is_on_sale=1 and is_audit=1 and show_type = 0';
				$data = $this->getGoodsList($where,$page,$pagesize,'sort asc,sales desc');
			}else{
				$cat = M('haitao', '', 'DB_CONFIG2')->where('`parent_id` = '.$id)->field('id')->select();
				$condition['is_on_sale']=1;
				$condition['is_show'] = 1;
				$condition['is_audit'] = 1;
				$condition['show_type'] =0;
				$condition['the_raise'] =0;
				if(empty($cat)){
					$condition['haitao_cat'] = $id;
				}else{//array_column()将二维数组转成一维
					$condition['haitao_cat'] =array('in',array_column($cat,'id'));
				}
				$data = $this->getGoodsList($condition,$page,$pagesize,'sort asc,sales desc');
			}
		}else{
			$json = array('status'=>-1,'msg'=>'参数错误');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		return $data;
	}

	function getNextCat($id)
	{
		$page = I('page',1);
		$pagesize = I('pagesize',20);
		//找到一级菜单的下级id
		$parent_cat = M('goods_category', '', 'DB_CONFIG2')->where('`parent_id`='.$id)->field('id')->select();
		$condition['parent_id'] =array('in',array_column($parent_cat,'id'));
		$parent_cat2 = M('goods_category', '', 'DB_CONFIG2')->where($condition)->field('id')->select();
		$condition2['cat_id'] =array('in',array_column($parent_cat2,'id'));
		$condition2['is_on_sale']=1;
		$condition2['is_show'] = 1;
		$condition2['is_audit'] = 1;
		$condition2['show_type'] =0;
		$condition2['the_raise'] =0;
		$data = $this->getGoodsList($condition2,$page,$pagesize,'sort asc,sales desc');
		return $data;
	}
    /**
     * 获取商品分类列表
     */
    public function goodsCategoryList(){
        $parent_id = I("parent_id",0);
        $goodsCategoryList = M('GoodsCategory', '', 'DB_CONFIG2')->where("parent_id = $parent_id AND is_show=1")->order("parent_id_path,sort_order desc")->select();
        $json_arr = array('status'=>1,'msg'=>'获取成功','result'=>$goodsCategoryList );
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

	/*
	 * H5显示
	 */
	public function H5_goodsInfo(){
		$id = $_GET['id'];

		echo "此处显示商品H5界面";
	}

    /**
     *  获取商品的缩略图
     */
    function goodsThumImages()
    {
        $goods_id = I('goods_id');
        $width = I('width');
        $height = I('height');
        $img_url = goods_thum_images($goods_id,$width,$height);
        $image = file_get_contents($img_url);  //假设当前文件夹已有图片001.jpg
        header('Content-type: image/jpg');
        exit($image);
    }
    /**
     * 收藏商品
     */
    function collectGoods(){
        $user_id = I('user_id');
        $goods_id = I('goods_id');
        $type = I('type',0);
	    I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $count = M('Goods', '', 'DB_CONFIG2')->where("goods_id = $goods_id")->count();
        if($count == 0)  exit(json_encode(array('status'=> -1,'msg'=>'收藏商品不存在')));
        //删除收藏商品
        if($type==1){
            M('goods_collect')->where("user_id = $user_id and goods_id = $goods_id")->delete();
	        $json = array('status'=> 1 ,'msg'=>'成功取消收藏' );
            redis("getUserCollection_status".$user_id, "1");//改变状态
	        if(!empty($ajax_get))
		        $this->getJsonp($json);
	        exit(json_encode($json));
        }
	        $count = M('goods_collect')->where("user_id = $user_id and goods_id = $goods_id")->count();
        if($count>0) {
	        $json = array('status' => 0, 'msg' => '您已收藏过该商品');
	        if(!empty($ajax_get))
		        $this->getJsonp($json);
	        exit(json_encode($json));
        }
        M('GoodsCollect')->add(array(
            'goods_id'=>$goods_id,
            'user_id'=>$user_id,
            'add_time'=>time(),
        ));
	    $json = array('status'=> 1 ,'msg'=>'收藏成功' );
        redis("getUserCollection_status".$user_id, "1");
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
    }

	//详情页
    function getGoodsDetails()
    {
	    $goods_id = I('goods_id');
	    I('user_id') && $user_id = I('user_id');
	    I('spec_key') && $spec_key = I('spec_key');
	    I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示

	    $rdsname = "getGoodsDetails" . $goods_id;
	    if (empty(redis($rdsname))) {//判断是否有缓存
		    $rdsname = "getGoodsDetails" . $goods_id;
		    //轮播图
		    $banner = M('goods_images', '', 'DB_CONFIG2')->where("`goods_id` = $goods_id")->field('image_url')->select();

		    foreach ($banner as &$v) {
			    //TODO 缩略图处理
			    $v['small'] = TransformationImgurl($v['image_url']);
			    $v['origin'] = TransformationImgurl($v['image_url']);
			    unset($v['image_url']);
		    }

		    if (empty($banner)) {
			    $banner = null;
		    }
		    $details = M('goods', '', 'DB_CONFIG2')->where(" `goods_id` = $goods_id")->field('goods_id,goods_name,prom_price,market_price,shop_price,prom,goods_remark,goods_content,store_id,sales,is_support_buy,free,the_raise,is_special,original_img')->find();
		    //商品详情
		    $goods['goods_id'] = $details['goods_id'];
		    $goods['goods_name'] = $details['goods_name'];
		    $goods['market_price'] = $details['market_price'];
		    $goods['shop_price'] = $details['shop_price'];
		    $goods['prom_price'] = $details['prom_price'];
		    $goods['prom'] = $details['prom'];
		    $goods['goods_remark'] = $details['goods_remark'];
		    $goods['goods_content_url'] = C('HTTP_URL') . '/Api/goods/get_goods_detail?id=' . $goods_id;
		    $goods['goods_share_url'] = C('SHARE_URL') . '/goods_detail.html?goods_id=' . $goods_id;
		    $goods['sales'] = $details['sales'];
		    $goods['is_support_buy'] = $details['is_support_buy'];
		    $goods['free'] = $details['free'];
		    $goods['the_raise'] = $details['the_raise'];
		    $store = M('merchant', '', 'DB_CONFIG2')->where(' `id` = ' . $details['store_id'])->field('id,store_name,store_logo,sales')->find();
		    $store['store_logo'] = TransformationImgurl($store['store_logo']);
		    $goods['store'] = $store;
		    $goods['is_special'] = $details['is_special'];
		    $goods['goods_content'] = $details['goods_content'];
		    $goods['original_img'] = TransformationImgurl($details['original_img']);
			$goods['img_arr'] = getImgs($details['goods_content']);
		    $goods['img_arr'] = getImgSize($goods['img_arr']);
		    $goods['fenxiang_url'] = $details['original_img'] . "?watermark/3/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nTE9HTy5qcGc=/dissolve/100/gravity/South/dx/0/dy/0";
		    //获取已经开好的团
		    $group_buy = M('group_buy', '', 'DB_CONFIG2')->where(" `goods_id` = $goods_id and `is_pay`=1 and `is_successful`=0 and `mark` =0 and `end_time`>=" . time())->field('id,end_time,goods_id,photo,goods_num,user_id,free')->order('start_time desc')->limit(3)->select();
		    if (!empty($group_buy)) {
			    for ($i = 0; $i < count($group_buy); $i++) {
				    $order_id = M('order', '', 'DB_CONFIG2')->where('`prom_id`=' . $group_buy[$i]['id'] . ' and `is_return_or_exchange`=0')->field('order_id,prom_id')->find();
				    $group_buy[$i]['id'] = $order_id['order_id'];

				    $mens = M('group_buy', '', 'DB_CONFIG2')->where('`mark` = ' . $order_id['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();

				    $group_buy[$i]['prom_mens'] = $group_buy[$i]['goods_num'] - $mens - 1;

				    $user_name = M('users', '', 'DB_CONFIG2')->where('`user_id` = ' . $group_buy[$i]['user_id'])->field('nickname,oauth,mobile,head_pic')->find();
				    if (!empty($user_name['oauth'])) {
					    $group_buy[$i]['user_name'] = $user_name['nickname'];
					    $group_buy[$i]['photo'] = $user_name['head_pic'];
				    } else {
					    $group_buy[$i]['user_name'] = substr_replace($user_name['mobile'], '****', 3, 4);
				    }

			    }
			    foreach ($group_buy as &$v) {
				    $v['photo'] = TransformationImgurl($v['photo']);
			    }
		    } else {
			    $group_buy = null;
		    }
		    //计算团购价
		    $goods['prom_price'] = (string)($goods['prom_price']);
		    //是否收藏
		    $goods['collect'] = 0;//默认没收藏
		    if (!empty($user_id)) {
			    $collect = M('goods_collect', '', 'DB_CONFIG2')->where("`user_id` = $user_id and `goods_id` = $goods_id")->find();
			    if ($collect) {
				    $goods['collect'] = 1;
			    } else {
				    $goods['collect'] = 0;
			    }
		    }
		    //商品规格
		    $goodsLogic = new \Home\Logic\GoodsLogic();
		    $spec_goods_price = M('spec_goods_price', '', 'DB_CONFIG2')->where("goods_id = $goods_id")->select(); // 规格 对应 价格 库存表
		    $filter_spec = $goodsLogic->get_spec($goods_id);//规格参数
		    $new_spec_goods = array();
		    foreach ($spec_goods_price as $spec) {
			    $new_spec_goods[] = $spec;
		    }
		    $new_filter_spec = array();

		    foreach ($filter_spec as $key => $filter) {
			    $new_filter_spec[] = array('title' => $key, 'items' => $filter);
		    }
		    for ($i = 0; $i < count($new_filter_spec); $i++) {
			    foreach ($new_filter_spec[$i]['items'] as &$v) {
				    if (!empty($v['src'])) {
					    $v['src'] =$v['src'];
				    }
			    }
		    }
		    //如果有传规格过来就改变商品名字
		    if (!empty($spec_key)) {
			    $key_name = M('spec_goods_price', '', 'DB_CONFIG2')->where("`key`='$spec_key'")->field('key_name')->find();
			    $goods['goods_spec_name'] = $goods['goods_name'] . $key_name['key_name'];
		    }
		    if (!empty($ajax_get)) {
			    $goods['html'] = htmlspecialchars_decode($goods['goods_content']);
		    }

		    //提供保障
		    $security = array(array('type'=>'全场包邮','desc'=>'所有商品均无条件包邮'),array('type'=>'7天退换','desc'=>'商家承诺7天无理由退换货'),array('type'=>'48小时发货','desc'=>'成团后，商家将在48小时内发货'),array('type'=>'假一赔十','desc'=>'若收到的商品是假货，可获得加倍赔偿'));

		    $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'group_buy' => $group_buy, 'goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'prom_price' => $goods['prom_price'], 'market_price' => $goods['market_price'], 'shop_price' => $goods['shop_price'], 'prom' => $goods['prom'], 'goods_remark' => $goods['goods_remark'], 'store_id' => $goods['store_id'] , 'sales' => $goods['sales'], 'is_support_buy' => $goods['is_support_buy'], 'is_special' => $goods['is_special'], 'original_img' => $goods['original_img'], 'goods_content_url' => $goods['goods_content_url'], 'goods_share_url' => $goods['goods_share_url'], 'fenxiang_url' => $goods['fenxiang_url'], 'collect' => $goods['collect'],'original_img'=>$goods['original_img'],'img_arr'=>$goods['img_arr'],'security'=>$security,'store' => $store,  'spec_goods_price' => $new_spec_goods, 'filter_spec' => $new_filter_spec));
		    redis($rdsname, serialize($json), REDISTIME);//写入缓
		    if (!empty($ajax_get))
			    $this->getJsonp($json);
		    exit(json_encode($json));
	    }
    }

	function getShare()
	{
		$id=$_GET['id'];
		$this->show();
	}
	/*
	 *   $latitude纬度
	 *   $longitude经度
	 *
	 *   获取地区
	 */
	public function getAddress($latitude,$longitude){
		//
		$res = file_get_contents("http://api.map.baidu.com/geocoder/v2/?ak=H0ipCgj55P9C0KgHb3ZqGl12xtRR69DK&callback=renderReverse&location=$latitude,$longitude&output=json&pois=1");

		$res = str_replace('renderReverse&&renderReverse(','',$res);
		$res = substr($res,0,strlen($res)-1);

		$res = json_decode($res,true);

		return $res['result']['addressComponent']['city'];
	}


	public function getFree($prom_id)
	{
		$join_num = M('group_buy')->where('(`id`='.$prom_id.' or `mark`='.$prom_id.') and `is_pay`=1')->field('id,goods_id,order_id,goods_num,free,is_raise,user_id')->order('mark asc')->select();

		$prom_num = $join_num[0]['goods_num'];
		$free_num = $join_num[0]['free'];
		M()->startTrans();
		//把所有人的状态改成发货
		for($i=0;$i<count($join_num);$i++)
		{
            $this->order_redis_status_ref($join_num[$i]['user_id']);
			if(!empty($join_num[0]['is_raise']))
			{
				if($i==0)
				{
					$res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>11,'order_type'=>14))->save();
				} else {
					$res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>2,'shipping_status'=>1,'order_type'=>5))->save();
				}
			} else {
				$res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>11,'order_type'=>14))->save();
			}
			$res2 = M('group_buy')->where('`id`='.$join_num[$i]['id'])->data(array('is_successful'=>1))->save();
			if($res && $res2)
			{
				M()->commit();
			}else{
				M()->rollback();
			}
		}

		if($free_num>0)//如果有免单，才执行getRand操作
		{
			redis("get_Free_Order_status","1");
			$order_ids =array_column($join_num,'order_id');//拿到全部参团和开团的订单id
			//给参团人和开团人推送信息
			$message = "你参与的团购,即将揭晓免单人";
			$custom = array('type' => '2','id'=>$join_num[0]['order_id']);
			foreach($join_num as $val){
				SendXinge($message,$val['user_id'],$custom);
			}

			$num = $this->getRand($free_num,($prom_num-1));//随机出谁免单
			for($i=0;$i<count($num);$i++)
			{
				$j = $num[$i];
				$order_id = $order_ids[$j];
				$res = M('order')->where('`order_id`='.$order_id)->data(array('is_free'=>1))->save();
				$res2 = M('group_buy')->where('`order_id`='.$order_id)->data(array('is_free'=>1))->save();
				if($res && $res2){
					$this->getWhere($order_id);
					M()->commit();
				}else{
					M()->rollback();
				}
			}
		}else{
//			$order_ids =array_column($join_num,'order_id');//拿到全部参团和开团的订单id

			//给参团人和开团人推送信息
//			$user_ids = M('order')->where(array('order_id'=>array('in',$order_ids)))->field('user_id')->select();
			$message = "你参与的团购,团满开团成功";
			$custom = array('type' => '1','id'=>$join_num[0]['order_id']);
			foreach($join_num as $val){
				SendXinge($message,$val['user_id'],$custom);
			}
		}
		exit ;
	}

	public function getWhere($order_id)
	{
		$result = M('order')->where('`order_id`='.$order_id)->find();
		if($result['is_jsapi']==1)
			$data['is_jsapi'] = 1;
		$data['order_id']=$order_id;
		$data['price'] = $result['order_amount'];
		$data['code'] = $result['pay_code'];
		$data['add_time'] = time();
		M('getwhere')->data($data)->add();
	}

	public function getRand($num,$max)//需要生成的个数，最大值
	{
		$rand_array=range(0,$max);
		shuffle($rand_array);//调用现成的数组随机排列函数
//		var_dump(array_slice($rand_array,0,$num));
		return array_slice($rand_array,0,$num);//截取前$num个
	}

	public function getCompleteBuy()
	{
		$order_id = I('order_id');
		$pay_code = I('code');

		$order = M('order')->where('`order_id`='.$order_id)->field('order_sn,user_id,add_time')->find();
		//当订单已经是取消状态是不能继续支付
		if($order['order_status']==3 || ($order['add_time'] + ORDER_END_TIME - 30) < time()){
			$json = array('status'=>-1,'msg'=>'当前订单已经取消，请重新下单');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
        $this->order_redis_status_ref($order['user_id']);
		if($pay_code!=$order['pay_code'])
		{
			if($pay_code=='alipay')
			{
				$pay_name = '支付宝支付';
			}
            elseif($pay_code=='alipay_wap'){
                $pay_name = '手机支付宝网页支付';
            }
			elseif($pay_code=='weixin'){
				$pay_name = '微信支付';
			}else{
				$pay_name = 'QQ支付';
			}
			M('order')->where('order_id='.$order_id)->save(array('pay_code'=>$pay_code,'pay_name'=>$pay_name));
		}
		if($pay_code=='weixin')
		{
            $weixinPay = new WeixinpayController();
            if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1){
                $code_str = $weixinPay->getJSAPI($order);
                $pay_detail = $code_str;
            }else{
                $pay_detail = $weixinPay->addwxorder($order['order_sn']);
            }
		} elseif($pay_code=='alipay') {
				$AliPay = new AlipayController();
				$pay_detail = $AliPay->addAlipayOrder($order['order_sn']);
		}
        elseif($order['pay_code'] == 'alipay_wap'){ // 添加手机网页版支付 2017-5-25 hua
            $AlipayWap = new AlipayWapController();
            $pay_detail = $AlipayWap->addAlipayOrder($order['order_sn']);
        }
		elseif($pay_code == 'qpay'){
			$qqPay = new QQPayController();
			$pay_detail = $qqPay->getQQPay($order);
		} else {
			$json = array('status'=>-1,'msg'=>'错误参数');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		$json = array('status'=>1,'msg'=>'预支付信息','result'=>array('pay_detail'=>$pay_detail));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
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

		$test = M('order', '', 'DB_CONFIG2')->where('`invitation_num`='.$code)->find();
		if(!empty($test))
			$code = $this->getInvitationNum();
		return $code;
	}

	/*
	 * type:  0、参团、1、开团、2、单买
	 */
		function getOrder()
			{
			header("Access-Control-Allow-Origin:*");
				$user_id = I('user_id');
				$goods_id = I('goods_id');
				$store_id = I('store_id');
				$num = I('num',1);
				$type = I('type');
				$spec_key = I('spec_key');
				$order_id = I('order_id');

				$user_address = M('user_address')->where("`user_id` = $user_id and `is_default` = 1")->field('address_id,consignee,address_base,address,mobile')->find();
				if(empty($user_address)){
				$user_address = M('user_address')->where("`user_id` = $user_id")->field('address_id,consignee,address_base,address,mobile')->find();
			}
			//库存
			$store_count =  M('goods')->where("`goods_id` = $goods_id")->field('store_count')->find();

			$goods = M('goods')->where("`goods_id` = $goods_id")->field('goods_id,goods_name,shop_price,original_img,prom_price,the_raise,prom')->find();
		$goods['original_img'] = C('HTTP_URL').goods_thum_images($goods['goods_id'],400,400);
		$goods['store'] = M('merchant')->where("`id` = $store_id")->field('id,store_name,store_logo')->find();
		$goods['store']['store_logo'] = C('HTTP_URL').$goods['store']['store_logo'];
		//获取商品规格
		if(!empty($spec_key)){
			M('temporary_key')->add(array('goods_id'=>$goods_id,'goods_spec_key'=>$spec_key,'user_id'=>$user_id,'add_time'=>time()));
			$goods_spec = M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_key'")->field('key_name,price,prom_price')->find();
			$goods['shop_price']=$goods_spec['price'];
			$goods['prom_price']=$goods_spec['prom_price'];
			$goods['key_name'] = $goods_spec['key_name'];
		}else{
			$goods['key_name']='默认';
			$goods_spec['price']=$goods['shop_price'];
			$goods_spec['price']=$goods['prom_price'];
		}

		//用来获取优惠券的价格
		//0-》参团 1-》开团 2-》单买
		if($type==0)
		{
			$price = $goods['prom_price']*$num;
			$order_info = M('group_buy')->where('order_id = '.$order_id)->find();
			$goods['prom_num'] = $order_info['goods_num'];
			$goods['free_num'] = $order_info['free'];
		}
		elseif($type==1){
			$price = $goods_spec['prom_price']*$num;;
		}
		elseif($type==2) {
			$price = $goods_spec['price']*$num;
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
		$user_coupon = M('coupon_list')->where('`uid`='.$user_id.' and `store_id`='.$store_id.' and `is_use`=0')->field('id,cid')->select();
		if(!empty($user_coupon)){
			$id = array_column($user_coupon, 'cid');
			//拿到所有优惠券，并根据condition倒叙输出,获取最佳优惠卷
			$coupon = M('coupon')->where('`id` in ('.join(',',$id).') and `condition`<='.$price.' and `use_end_time`>'.time())->order('`money` desc')->field('id,name,money,condition,use_start_time,use_end_time')->find();
			if(!empty($coupon)){
				//根据获取的最佳优惠券在coupon_list里面的优惠券id
				for ($i = 0; $i < count($user_coupon); $i++) {
					$user_coupon_list_id = M('coupon_list')->where('`cid`='.$user_coupon[$i]['cid'].' and `uid`='.$user_id.' and `is_use`=0')->find();
					if ($coupon['id'] == $user_coupon_list_id['cid']){
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
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('user'=>$user_address,'goods'=>$goods,'count'=>$store_count,'coupon'=>$coupon));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}


	//获取可用优惠券
	function getCoupon()
	{
		$user_id = I('user_id');
		$store_id = I('store_id');
		$type = I('type');
		$num = I('num',1);
		$goods_id = I('goods_id');

		$key = M('temporary_key', '', 'DB_CONFIG2')->where('goods_id='.$goods_id.' and user_id='.$user_id)->order('add_time desc')->find();
		$spec_price = M('spec_goods_price', '', 'DB_CONFIG2')->where('goods_id='.$goods_id. " and `key`='".$key['goods_spec_key']."'")->find();
		if($type==1||$type==0) {
			$price = $spec_price['prom_price']*$num;
		} elseif($type==2) {
			$price = $spec_price['price']*$num;
		} else {
			exit(json_encode(array('status'=>-1,'msg'=>'参数错误')));
		}

		$user_coupon = M('coupon_list', '', 'DB_CONFIG2')->where('`uid`='.$user_id.' and `store_id`='.$store_id.' and `is_use`=0')->field('id,cid')->select();
        if(!empty($user_coupon)){
	        $id =array_column($user_coupon,'cid');
	        //拿到所有优惠券，并根据condition倒叙输出
	        $coupon = M('coupon', '', 'DB_CONFIG2')->alias('c')
		        ->join('INNER JOIN tp_merchant m on m.id = c.store_id')
		        ->where('c.`id` in ('.join(',',$id).') and c.`condition`<='.$price.' and c.`use_end_time`>'.time())->order('c.`money` desc')
		        ->field('c.`id`,c.`name`,c.`money`,c.`condition`,c.`use_start_time`,c.`use_end_time`,m.`store_name`')
		        ->select();

	        for ($i=0;$i<count($coupon);$i++){
		        for($j=0;$j<count($user_coupon);$j++){
			        if($coupon[$i]['id']==$user_coupon[$j]['cid'])
				        $coupon[$i]['coupon_list_id'] = $user_coupon[$i]['id'];
		        }
	        }
        }else{
	        $coupon=null;
        }

		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('items'=>$coupon));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	/*
	 *海淘页面商品管理
	 */

	//海淘顶部分类
	function getCountries()
	{
		$id = I('id');
		$page = I('page',1);
		$pagesize = I('pagesize',20);
		$version = I('version');
		$countries = M('haitao_style', '', 'DB_CONFIG2')->where('`id` = '.$id)->find();
		$countries['img'] = TransformationImgurl($countries['img']);

		$where ='`show_type` = 0 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `countries_type` = '.$id;
		$data = $this->getGoodsList($where,$page,$pagesize,'sales desc');

		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('countries'=>$countries,'goods'=>$data));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	//海淘中间分类
	function getCategory()
	{
		$id = I('id');
		$category = M('haitao', '', 'DB_CONFIG2')->where('`parent_id` = '.$id)->field('id,name')->select();

		array_unshift($category,array('id'=>'0','name'=>'全部'));
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('category'=>$category));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	//海淘获取中间分类信息
	function getCategoryData()
	{
		$id = I('id');
		$p_id = I('p_id');
		$page = I('page',1);
		$pagesize = I('pagesize',20);
		if($p_id && $id == 0)
		{
			$cat = M('haitao', '', 'DB_CONFIG2')->where('`parent_id` = '.$p_id)->field('id')->select();

			$condition['is_on_sale']=1;
			$condition['is_show'] = 1;
			$condition['is_audit'] = 1;
			$condition['is_special'] = 1;
			//array_column()将二维数组转成一维
			$condition['haitao_cat'] =array('in',array_column($cat,'id'));

			$count = M('goods', '', 'DB_CONFIG2')->where($condition)->count();
			$goods = M('goods', '', 'DB_CONFIG2')->where($condition)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->select();
		}
		else
		{
			$count = M('goods', '', 'DB_CONFIG2')->where('`is_special`=1 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `haitao_cat` = '.$id)->count();
			$goods = M('goods', '', 'DB_CONFIG2')->where('`is_special`=1 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `haitao_cat` = '.$id)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->select();
		}

		foreach($goods as &$v)
		{
			$v['original_img'] = TransformationImgurl($v['goods_id']);
		}
		$data = $this->listPageData($count,$goods);
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	/*
	 * 收货地址的增加修改
	 *
	 * type:1修改、2增加
	 * */
	function addEidtAddress()
	{
		$user_id = I('user_id');
		I('address_id') && $address_id = I('address_id');
		$data['address_base'] = I('address_base');//基础地址
		I('default') && $data['is_default'] = I('default');//是否设为默认
		$data['address'] = I('address');//地址
		$data['mobile'] = I('mobile');
		$data['consignee'] = I('consignee');
		$type = I('type');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

		if($type==1)//type:1修改、2增加
		{
			if($data['is_default']==1)
			{
				M('user_address')->where("`user_id` = $user_id ")->data(array('is_default'=>0))->save();
			}
			$res = M('user_address')->where("`user_id` = $user_id and `address_id` = $address_id")->data($data)->save();
			if($res)
			{
				$json = array('status'=>1,'msg'=>'修改成功');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}
			else
			{
				$json = array('status'=>-1,'msg'=>'修改失败');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}
		}
		else
		{
			if($data['is_default']==1)
			{
				M('user_address')->where("`user_id` = $user_id ")->data(array('is_default'=>0))->save();

			}
			$data['user_id'] = $user_id;
			$res = M('user_address')->data($data)->add();
			if($res)
			{
				$json = array('status'=>1,'msg'=>'添加成功');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}
			else
			{
				$json = array('status'=>-1,'msg'=>'添加失败');
				if(!empty($ajax_get));
					$this->getJsonp($json);
				exit(json_encode($json));
			}
		}
	}

	function delAddress()
	{
		$user_id = I('user_id');
		$address_id = I('address_id');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		M()->startTrans();
		//先对要删除的地址进行一次检查是否为默认地址
		$default = M('user_address')->where('`address_id` = '.$address_id)->find();

		if($default['is_default'])//如果是默认的，就将剩下的该用户的第一个地址设置默认
		{
			$address_list = M('user_address')->where("`user_id` = $user_id && `address_id` != $address_id")->field('address_id')->select();
			if(count($address_list)>=1)
			{
				$new = M('user_address')->where('`address_id` = '.$address_list[0]['address_id'])->data(array('is_default'=>1))->save();
			}else{
				$new = 1;
			}
			$res = M('user_address')->where("`user_id` = $user_id and `address_id` = $address_id")->delete();
			if($res && $new)
			{
				M()->commit();
				$json = array('status'=>1,'msg'=>'删除成功');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}else{
				M()->rollback();
				$json = array('status'=>-1,'msg'=>'删除失败');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}
		}else{
			$res = M('user_address')->where("`user_id` = $user_id and `address_id` = $address_id")->delete();
			if($res)
			{
				M()->commit();
				$json = array('status'=>1,'msg'=>'删除成功');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}else{
				M()->rollback();
				$json = array('status'=>-1,'msg'=>'删除失败');
				if(!empty($ajax_get))
					$this->getJsonp($json);
				exit(json_encode($json));
			}
		}
	}

	//商品特殊类型 1-海淘，2-限时秒杀，3-一元夺宝，4-99专场，5-多人拼团
	//搜索
    function getsearch()
    {
        $key = I('key');
        $page = I('page',1);
        $pagesize = I('pagesize',50);
        $rdsname = "getsearch".$key.$page.$pagesize;
        if (empty(redis($rdsname))) {//判断是否有缓存
            $where = "`goods_name` like '%{$key}%' and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `show_type`=0 ";
            $data = $this->getGoodsList($where,$page,$pagesize);
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读出缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

	/*
	 * 快递100
	 * */
	public function getCourier()
	{
		$order_id = I('order_id');
		I('ajax_get') &&  $ajax_get = I('ajax_get');
		if(empty($order_id)) {
			$json = array('status'=>-1,'msg'=>'参数不全');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		$order = M('order', '', 'DB_CONFIG2')->where('`order_id` = '.$order_id)->field('shipping_code,shipping_order')->find();
		if(empty($order)){
			$json = array('status'=>-1,'msg'=>'订单不存在');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		$logistics = M('logistics', '', 'DB_CONFIG2')->where("`logistics_code`='".$order['shipping_code']."'")->field('logistics_name,logistics_mobile')->find();
		$logistics['shipping_order']=$order['shipping_order'];
		//参数设置
		$post_data = array();
		$post_data["customer"] = 'A1638F91623252C0207C481E2B112F52';
		$key= 'DLTlUmMA8292' ;
		$post_data["param"] = '{"com":"'.$order['shipping_code'].'","num":"'.$order['shipping_order'].'"}';

		$url='http://poll.kuaidi100.com/poll/query.do';

		$post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
		$post_data["sign"] = strtoupper($post_data["sign"]);

		$o = "";

		foreach ($post_data as $k=>$v)
		{
			$o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
		}
		$post_data=substr($o,0,-1);
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
		$result=curl_exec($ch);
		curl_close($ch);
		$data = json_decode($result,TRUE);

		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>array('logistics'=>$logistics,'date'=>$data['data']));
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	public function zhuanpan()
	{
		$id= I('id');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$order_info = M('group_buy', '', 'DB_CONFIG2')->where('`id`='.$id.' or `mark`='.$id.' and is_successful=1')->field('user_id,goods_num,mark')->select();
		//判断进来的是不是团长
		if(count($order_info)==1)
		{
			$order_info = M('group_buy', '', 'DB_CONFIG2')->where('`id`='.$order_info[0]['mark'].' or `mark`='.$order_info[0]['mark'].' and is_successful=1')->field('user_id,goods_num')->select();
		}
		$user_id['user_id'] = array('in',array_column($order_info,'user_id'));
		$user_info = M('users', '', 'DB_CONFIG2')->where($user_id)->field('oauth,head_pic,nickname,user_id')->select();

		$order_array = array();
		for($i=0;$i<count($user_info);$i++)
		{
			if(!empty($user_info[$i]['oauth']))
			{
				$join[$i]['name'] = $user_info[$i]['nickname'];
			}else{
				$join[$i]['name'] = substr_replace($user_info[$i]['nickname'], '****', 3, 4);//将手机号码中间四位变成*号
			}
			$join[$i]['head_pic'] = TransformationImgurl($user_info[$i]['head_pic']);
			$order_array[$user_info[$i]['user_id']] = $i;
		}

		$free = M('group_buy', '', 'DB_CONFIG2')->where('(`id`='.$id.' or `mark`='.$id.') and `is_free`=1')->field('user_id,goods_num')->select();
		$free_id['user_id'] = array('in',array_column($free,'user_id'));
		$free_info = M('users', '', 'DB_CONFIG2')->where($free_id)->field('oauth,head_pic,nickname,user_id')->select();

		for($j=0;$j<count($free_info);$j++)
		{
			if(!empty($free_info[$j]['oauth']))
			{
				$frees[$j]['username'] = $free_info[$j]['nickname'];
			}else{
				$frees[$j]['username'] = substr_replace($free_info[$j]['nickname'], '****', 3, 4);//将手机号码中间四位变成*号
			}
			$frees[$j]['order'] = $order_array[$free_info[$j]['user_id']];
			$frees[$j]['head_pic'] = TransformationImgurl($free_info[$j]['head_pic']);
		}

		$data['winners'] = $frees;
		$data['winner_num'] = count($frees);
		$data['is_draw'] = 0;
		$this->assign('join',$join);
		$this->assign('free',json_encode($data));
		$this->show();
	}

	//新版本详情 2.0.0
	function getDetaile($refresh="")
	{
        $goods_id = I('goods_id');
        //自动脚本
        if ($refresh) {
            $goods_id = redislist("goods_refresh_id");
            if ($goods_id){
                redisdelall("getDetaile_".$goods_id);
            } else {
                exit;
            }
        }
        $goodsstatus = M('goods')
            ->where("goods_id=$goods_id and (show_type=1 or is_show=0 or is_on_sale=0)")
            ->count();
        if ($goodsstatus >0){
            $json = array('status' => -1, 'msg' => '该商品已下架', 'result' => '');
        } else {
            $rdsname = 'getDetaile_' . $goods_id;
            if (empty(redis($rdsname))) {
                $goods = $this->getGoodsInfo($goods_id);
	            //轮播图
	            if($goods['is_special']==7){
		            $f_goods_id = M('goods_activity')->where('goods_id='.$goods_id)->getField('f_goods_id');
		            $banner = M('goods_images', '', 'DB_CONFIG2')->where("`goods_id` = $f_goods_id")->field('image_url')->select();
	            }else{
		            $banner = M('goods_images', '', 'DB_CONFIG2')->where("`goods_id` = $goods_id")->field('image_url')->select();
	            }

	            foreach ($banner as &$v) {
		            //TODO 缩略图处理
		            $v['small'] = TransformationImgurl($v['image_url']);
		            $v['origin'] = TransformationImgurl($v['image_url']);
		            unset($v['image_url']);
	            }
	            if (empty($banner)) {
		            $banner = null;
	            }
                //商品规格
                $goodsLogic = new \Home\Logic\GoodsLogic();
                $spec_goods_price = M('spec_goods_price', '', 'DB_CONFIG2')->where("goods_id = $goods_id")->select(); // 规格 对应 价格 库存表
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
                    foreach ($new_filter_spec[$i]['items'] as &$v) {
                        if (!empty($v['src'])) {
                            $v['src'] = $v['src'];
                        }
                    }
                }
                //如果有传规格过来就改变商品名字
                if (!empty($spec_key)) {
                    $key_name = M('spec_goods_price', '', 'DB_CONFIG2')->where("`key`='$spec_key'")->field('key_name')->find();
                    $goods['goods_spec_name'] = $goods['goods_name'] . $key_name['key_name'];
                }
                if (!empty($ajax_get)) {
                    $goods['html'] = htmlspecialchars_decode($goods['goods_content']);
                }

                //提供保障
                $security = array(array('type' => '全场包邮', 'desc' => '所有商品均无条件包邮'), array('type' => '7天退换', 'desc' => '商家承诺7天无理由退换货'), array('type' => '48小时发货', 'desc' => '成团后，商家将在48小时内发货'), array('type' => '假一赔十', 'desc' => '若收到的商品是假货，可获得加倍赔偿'));

                $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'prom_price' => $goods['prom_price'], 'market_price' => $goods['market_price'], 'shop_price' => $goods['shop_price'], 'prom' => $goods['prom'], 'goods_remark' => $goods['goods_remark'], 'store_id' => $goods['store_id'], 'is_support_buy' => $goods['is_support_buy'], 'is_special' => $goods['is_special'], 'original_img' => $goods['original_img'],'original'=>$goods['original'],'goods_content_url' => $goods['goods_content_url'], 'goods_share_url' => $goods['goods_share_url'], 'fenxiang_url' => $goods['fenxiang_url'], 'collect' => $goods['collect'], 'original_img' => $goods['original_img'], 'img_arr' => $goods['img_arr'], 'security' => $security, 'store' => $goods['store'], 'spec_goods_price' => $new_spec_goods, 'filter_spec' => $new_filter_spec));
                redis($rdsname, serialize($json));//写入缓
            } else {
                $json = unserialize(redis($rdsname));
            }
        }
		I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

	//获取当前商品的拓展数据
	function getDetaile_expand(){//加商户销量，
		$goods_id = I('goods_id');
		$user_id = I('user_id');
		I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($user_id)){
			$collect = M('goods_collect', '', 'DB_CONFIG2')->where('goods_id = '.$goods_id.' and user_id = '.$user_id)->count();
		}else{
			$collect = 0;
		}
        $goods = M('goods', '', 'DB_CONFIG2')->alias('g')
	        ->join('INNER JOIN tp_merchant m on m.id = g.store_id')
	        ->where(array('g.goods_id'=>array('eq',$goods_id)))
	        ->field('g.store_count,g.sales,g.is_special,g.on_time,g.is_support_buy,m.sales as store_sales,g.is_prom_buy')
	        ->find();
		//默认
		$data['buy_type'] = 1;
		$data['prompt']=null;
		//判断特殊商品是否在可购买时间内
		if($goods['is_special']==7){//0.1秒杀
			$time = M('goods_activity', '', 'DB_CONFIG2')->where('goods_id='.$goods_id)->find();
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
		$group_buy = M('group_buy')->where(" `goods_id` = $goods_id and `is_pay`=1 and `is_successful`=0 and `mark` =0 and `end_time`>=" . time())->field('id,end_time,goods_id,photo,goods_num,user_id,free')->order('id asc')->limit(3)->select();
		if (!empty($group_buy)) {
			for ($i = 0; $i < count($group_buy); $i++) {
				$order_id = M('order')->where('`prom_id`=' . $group_buy[$i]['id'] . ' and `is_return_or_exchange`=0')->field('order_id,prom_id')->find();
				$group_buy[$i]['id'] = $order_id['prom_id'];

				$mens = M('group_buy', '', 'DB_CONFIG2')->where('`mark` = ' . $order_id['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();
				$group_buy[$i]['prom_mens'] = $group_buy[$i]['goods_num'] - $mens - 1;

				$user_name = M('users', '', 'DB_CONFIG2')->where('`user_id` = ' . $group_buy[$i]['user_id'])->field('nickname,oauth,mobile,head_pic')->find();
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
		$time = M('', '', 'DB_CONFIG2')->query($sql);

		return $time;
	}
}