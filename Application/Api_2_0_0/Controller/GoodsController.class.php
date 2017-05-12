<?php
/**
 * api接口-商品管理模块
 */
namespace Api_2_0_0\Controller;

use Think\Page;
class GoodsController extends BaseController {

    public function _initialize() {
        $this->encryption();
    }
    
    public function index(){
        $this->display();
    }

	//获取用户地址列表
	function getUserAddressList()
	{
		$user_id = I('user_id');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$a = M('user_address')->where('`user_id` = '.$user_id.' and `is_default` = 1')->field('address_id,consignee,address_base,address,mobile,is_default')->find();
		$b = M('user_address')->where('`user_id` = '.$user_id.' and `is_default` != 1')->field('address_id,consignee,address_base,address,mobile,is_default')->select();

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
	function getMore()
	{
		$id = I('id');
		$type = I('type');
		$page=I('page',1);
		$pagesize = I('pagesize',20);
		$version = I('version');
		$ajax_get=null;
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$rdsname = "getMore".$id.$type.$page.$pagesize.$ajax_get;
		if (empty(redis($rdsname))) {//判断是否有缓存
			$data = $this->getOtheyMore($id,$type,$page,$pagesize,$version,$ajax_get);
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

	function getOtheyMore($id,$type,$page,$pagesize,$version,$ajax_get)
	{
		if($type==0)
		{
			$parent_id = M('goods_category')->where('`parent_id`=0 and id != 10044 ')->field('id')->select();
			$ids =array(array_column($parent_id,'id'));
			if(in_array("$id", $ids[0]))
			{
				$data = $this->getNextCat($id,$version);
				return $data;
			}else{
				$condition['parent_id'] = $ids =array('in',array_column($parent_id,'id'));
				$parent_id2 = M('goods_category')->where($condition)->field('id')->select();
				$ids2 =array(array_column($parent_id2,'id'));
				if(in_array("$id", $ids2[0]))//确定为二级分类id
				{
					//找到一级菜单的下级id
					$parent_cat = M('goods_category')->where('`parent_id`='.$id)->field('id')->select();
					$condition2['cat_id'] =array('in',array_column($parent_cat,'id'));
					$condition2['is_on_sale']=1;
					$condition2['is_show'] = 1;
					$condition2['is_audit'] =1;
					$condition2['show_type'] =0;
					$condition2['the_raise'] =0;
					if($version=='2.0.0'){
						$data = $this->getGoodsList($condition2,$page,$pagesize,'sort asc,sales desc');
					}else {
						$count = M('goods')->where($condition2)->count();
						$goods = M('goods')->where($condition2)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page, $pagesize)->order('sales desc')->select();
						foreach ($goods as &$v) {
							$v['original_img'] = goods_thum_images($v['goods_id'], 400, 400);
						}
						$data = $this->listPageData($count, $goods);
					}
					return $data;
				}else{
					if($version=='2.0.0'){
						$where = '`show_type`=0 and `cat_id`=' . $id . ' and is_show=1 and is_on_sale=1 and is_audit=1';
						$data = $this->getGoodsList($where,$page,$pagesize,'sort asc,sales desc');
					}else {
						$count = M('goods')->where('`show_type`=0 and `cat_id`=' . $id . ' and is_show=1 and is_on_sale=1 and is_audit=1')->count();
						$goods = M('goods')->where('`show_type`=0 and `cat_id`=' . $id . ' and is_show=1 and is_on_sale=1 and is_audit=1')->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->order('sales desc')->page($page, $pagesize)->select();
						foreach ($goods as &$v) {
							$v['original_img'] = goods_thum_images($v['goods_id'], 400, 400);
						}
						$data = $this->listPageData($count, $goods);
					}
					return $data;
				}
			}
		}
		elseif($type==1)
		{
			if($id==0)//全部
			{
				if($version=='2.0.0'){
					$where = '`is_special` = 1 and is_show=1 and is_on_sale=1 and is_audit=1 and show_type = 0';
					$data = $this->getGoodsList($where,$page,$pagesize,'sort asc,sales desc');
				}else {
					$count = M('goods')->where('`is_special` = 1 and is_show=1 and is_on_sale=1 and is_audit=1 and show_type = 0')->count();
					$goods = M('goods')->where('`is_special` = 1 and is_show=1 and is_on_sale=1 and is_audit=1 and show_type = 0')->page($page, $pagesize)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->order('sales desc')->select();
					foreach($goods as &$v)
					{
						$v['original_img'] =  goods_thum_images($v['goods_id'],400,400);
					}
					$data = $this->listPageData($count,$goods);
				}
			}
			else
			{
				$cat = M('haitao')->where('`parent_id` = '.$id)->field('id')->select();
				$condition['is_on_sale']=1;
				$condition['is_show'] = 1;
				$condition['is_audit'] = 1;
				$condition['show_type'] =0;
				$condition['the_raise'] =0;
				if(empty($cat))
				{
					$condition['haitao_cat'] = $id;
				}else{

					//array_column()将二维数组转成一维
					$condition['haitao_cat'] =array('in',array_column($cat,'id'));
				}
				if($version=='2.0.0'){
					$data = $this->getGoodsList($condition,$page,$pagesize,'sort asc,sales desc');
				}else{

					$count = M('goods')->where($condition)->count();
					$goods = M('goods')->where($condition)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->order('sales desc')->select();
					foreach($goods as &$v)
					{
						$v['original_img'] =  goods_thum_images($v['goods_id'],400,400);
					}
					$data = $this->listPageData($count,$goods);
				}
			}
		}
		else
		{
			$json = array('status'=>-1,'msg'=>'参数错误');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		return $data;
	}

	function getNextCat($id,$version)
	{
		$page = I('page',1);
		$pagesize = I('pagesize',20);
		//找到一级菜单的下级id
		$parent_cat = M('goods_category')->where('`parent_id`='.$id)->field('id')->select();
		$condition['parent_id'] =array('in',array_column($parent_cat,'id'));
		$parent_cat2 = M('goods_category')->where($condition)->field('id')->select();
		$condition2['cat_id'] =array('in',array_column($parent_cat2,'id'));
		$condition2['is_on_sale']=1;
		$condition2['is_show'] = 1;
		$condition2['is_audit'] = 1;
		$condition2['show_type'] =0;
		$condition2['the_raise'] =0;

		if ($version=='2.0.0'){
			$data = $this->getGoodsList($condition2,$page,$pagesize,'sort asc,sales desc');
		}else{
			$count = M('goods')->where($condition2)->count();
			$goods = M('goods')->where($condition2)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->order('sales desc')->select();
			foreach($goods as &$v)
			{
				$v['original_img'] =  goods_thum_images($v['goods_id'],400,400);
			}
			$data = $this->listPageData($count,$goods);
		}

		return $data;
	}
    /**
     * 获取商品分类列表
     */
    public function goodsCategoryList(){
        $parent_id = I("parent_id",0);
        $goodsCategoryList = M('GoodsCategory')->where("parent_id = $parent_id AND is_show=1")->order("parent_id_path,sort_order desc")->select();
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
        $count = M('Goods')->where("goods_id = $goods_id")->count();
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
		    $banner = M('goods_images')->where("`goods_id` = $goods_id")->field('image_url')->select();

		    foreach ($banner as &$v) {
			    //TODO 缩略图处理
			    $v['small'] = TransformationImgurl($v['image_url']);
			    $v['origin'] = TransformationImgurl($v['image_url']);
			    unset($v['image_url']);
		    }

		    if (empty($banner)) {
			    $banner = null;
		    }
		    $details = M('goods')->where(" `goods_id` = $goods_id")->field('goods_id,goods_name,prom_price,market_price,shop_price,prom,goods_remark,goods_content,store_id,sales,is_support_buy,free,the_raise,is_special,original_img')->find();
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
		    $store = M('merchant')->where(' `id` = ' . $details['store_id'])->field('id,store_name,store_logo,sales')->find();
		    $store['store_logo'] = TransformationImgurl($store['store_logo']);
		    $goods['store'] = $store;
		    $goods['is_special'] = $details['is_special'];
		    $goods['goods_content'] = $details['goods_content'];
		    $goods['original_img'] = TransformationImgurl($details['original_img']);
			$goods['img_arr'] = getImgs($details['goods_content']);
		    $goods['img_arr'] = getImgSize($goods['img_arr']);
		    $goods['fenxiang_url'] = $details['original_img'] . "?watermark/3/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nTE9HTy5qcGc=/dissolve/100/gravity/South/dx/0/dy/0";
		    //获取已经开好的团
		    $group_buy = M('group_buy')->where(" `goods_id` = $goods_id and `is_pay`=1 and `is_successful`=0 and `mark` =0 and `end_time`>=" . time())->field('id,end_time,goods_id,photo,goods_num,user_id,free')->order('start_time desc')->limit(3)->select();
		    if (!empty($group_buy)) {
			    for ($i = 0; $i < count($group_buy); $i++) {
				    $order_id = M('order')->where('`prom_id`=' . $group_buy[$i]['id'] . ' and `is_return_or_exchange`=0')->field('order_id,prom_id')->find();
				    $group_buy[$i]['id'] = $order_id['order_id'];

				    $mens = M('group_buy')->where('`mark` = ' . $order_id['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();

				    $group_buy[$i]['prom_mens'] = $group_buy[$i]['goods_num'] - $mens - 1;

				    $user_name = M('users')->where('`user_id` = ' . $group_buy[$i]['user_id'])->field('nickname,oauth,mobile,head_pic')->find();
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
			    $collect = M('goods_collect')->where("`user_id` = $user_id and `goods_id` = $goods_id")->find();
			    if ($collect) {
				    $goods['collect'] = 1;
			    } else {
				    $goods['collect'] = 0;
			    }
		    }
		    //商品规格
		    $goodsLogic = new \Home\Logic\GoodsLogic();
		    $spec_goods_price = M('spec_goods_price')->where("goods_id = $goods_id")->select(); // 规格 对应 价格 库存表
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
			    $key_name = M('spec_goods_price')->where("`key`='$spec_key'")->field('key_name')->find();
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
		for($i=0;$i<$prom_num;$i++)
		{
            redis("getUserPromList_status".$join_num[$i]['user_id'], "1");
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
				if($res && $res2)
				{
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

	/*
	 * type:  0、参团、1、开团、2、单买
	 */
	function getBuy()
	{
		header("Access-Control-Allow-Origin:*");
		$user_id = I('user_id');
		$order_id =I('order_id');
		$address_id = I('address_id');
		$goods_id = I('goods_id');
		$store_id = I('store_id');
		$num = I('num',1);
		$free = I('free',0);
		$type = I('type');
		I('coupon_id') && $coupon_id = I('coupon_id');
		I('coupon_list_id') && $coupon_list_id = I('coupon_list_id');
		$spec_key = I('spec_key');
		I('prom') && $prom = I('prom');
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

		$parameter['order_id'] = $order_id;
		$parameter['prom'] = $prom;
		$parameter['user_id'] = $user_id;
		$parameter['address_id'] = $address_id;
		$parameter['goods_id'] = $goods_id;
		$parameter['store_id'] = $store_id;
		$parameter['num'] = $num;
		$parameter['free'] = $free;
		$parameter['coupon_id'] = $coupon_id;
		$parameter['spec_key'] = $spec_key;
		$parameter['ajax_get'] = $ajax_get;
		$parameter['coupon_list_id'] = $coupon_list_id;

		if (empty(redis("getBuy_lock".$goods_id)))//如果无锁
			redis("getBuy_lock" . $goods_id, "1", 5);//写入锁

		//销量、库存
		M('goods')->where('`goods_id` = '.$goods_id)->setInc('sales',$num);
		M('merchant')->where('`id`='.$store_id)->setInc('sales',$num);
		//商品规格库存
		$spec_name = M('order_goods')->where('`order_id`='.$goods_id)->field('spec_key')->find();
		M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_name[spec_key]'")->setDec('store_count',$num);

		if(!empty($spec_key)) {
				$spec_res = M('spec_goods_price')->where('`goods_id`=' . $goods_id . " and `key`='$spec_key'")->find();
			}else{
				$spec_res = M('goods')->where('`goods_id`='.$goods_id)->find();
			}
			if ($spec_res['store_count'] <= 0) {
				redisdelall("getBuy_lock" . $goods_id);//删除锁
				$json = array('status' => -1, 'msg' => '该商品已经被亲们抢光了');
				if (!empty($ajax_get))
					$this->getJsonp($json);

			$spec_res = M('spec_goods_price')->where('`goods_id`=' . $goods_id . " and `key`='$spec_key'")->find();
		}else{
			$spec_res = M('goods')->where('`goods_id`='.$goods_id)->find();
		}
		if ($spec_res['store_count'] <= 0) {
			redisdelall("getBuy_lock" . $goods_id);//删除锁
			$json = array('status' => -1, 'msg' => '该商品已经被亲们抢光了');
			if (!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
		//参团购物
		if($type == 0)
		{
			$result = M('group_buy')->where("`order_id` = $order_id")->find();
			if($result['end_time']<time())
			{
				$json = array('status'=>-1,'msg'=>'该团已结束了，请选择别的团参加');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
				if(!empty($ajax_get)){

                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
				exit(json_encode($json));
			}
			//为我点赞只允许每个人参团一次
			if($result['is_raise']==1)
			{
				$raise = M('group_buy')->where('(end_time>'.time().' or is_successful=1) and mark!=0 and is_raise=1')->order('id desc')->select();
				$raise_id_array = array_column($raise,'user_id');
				if(in_array("$user_id",$raise_id_array))
				{
					$json =array('status'=>-1,'msg'=>'您已参加过该拼团活动，不能再参团，只能继续开团');
					redisdelall("getBuy_lock" . $goods_id);//删除锁
                    if(!empty($ajax_get)){
                        echo "<script> alert('".$json['msg']."') </script>";
                        exit;
                    }
					exit(json_encode($json));
				}
			}
			//每个团的最后一个人直接将订单锁住防止出现错误
			$num = M('group_buy')->where('`id`='.$result['id'].' or `mark`='.$result['id'].' and `is_pay`=1 and `is_cancel`=0')->count();
			if($num+1==$result['goods_num'])
			{
				$on_buy = M('group_buy')->where('`mark`='.$result['id'].' and `is_pay`=0 and `is_cancel`=0' )->find();
				if(!empty($on_buy))
				{
					$json =array('status'=>-1,'msg'=>'有用户尚未支付，您可以在他取消订单后进行支付');
					redisdelall("getBuy_lock" . $goods_id);//删除锁
                    if(!empty($ajax_get)){
                        echo "<script> alert('".$json['msg']."') </script>";
                        exit;
                    }

					exit(json_encode($json));
				}
			}elseif($num==$result['goods_num']){
				$json =array('status'=>-1,'msg'=>'该团已经满员开团了，请选择别的团参加');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
				exit(json_encode($json));
			}
			//判断该用户是否参团了
			$self = M('group_buy')->where('`mark`='.$result['id'].' and `user_id`='.$user_id.' and `is_pay`=1')->find();
			if(!empty($self))
			{
				$json =array('status'=>-1,'msg'=>'你已经参团了');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
				exit(json_encode($json));
			}
			//判断用户是否已经生成未付款订单
			$on_buy = M('group_buy')->where('`mark`='.$result['id'].' and `user_id`='.$user_id.' and `is_pay`=0 and `is_cancel`=0' )->find();
//			if(!empty($on_buy))
//			{
//				$json =array('status'=>-1,'msg'=>'该团你有未付款订单，请前往支付再进行操作');
//				redisdelall("getBuy_lock" . $goods_id);//删除锁
//                if(!empty($ajax_get)){
//                    echo "<script> alert('".$json['msg']."') </script>";
//                    exit;
//                }
//				exit(json_encode($json));
//			}
			$num2 = M('group_buy')->where('`id`='.$result['id'].' `mark` = '.$order_id.' and `is_pay`=1')->count();
			if($num2==$result['goods_num'])
			{
				$json =	array('status'=>-1,'msg'=>'该团已经满员开团了，请选择别的团参加');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
				exit(json_encode($json));
			}
			$this->joinGroupBuy($parameter);
		}
		else if($type == 1)	//开团
		{
			$this->openGroup($parameter);
		}
		//自己买
		else if($type == 2)
		{
			$this->buyBymyself($parameter);
		}
        $rdsname = "getUserOrderList".$user_id."*";
        redisdelall($rdsname);//删除用户订单缓存
        $rdsname = "getGoodsDetails".$goods_id."*";
        redisdelall($rdsname);//删除商品详情缓存
        $rdsname = "TuiSong*";
        redisdelall($rdsname);//删除推送缓存
        //跨区同步订单、推送、详情缓存
        $url = array("http://api.hn.pinquduo.cn/api/index/index/getGoodsDetails/1/user_id/$user_id/goods_id/$goods_id");
        async_get_url($url);
        $url = array("http://pinquduo.cn/api/index/index/getGoodsDetails/1/user_id/$user_id/goods_id/$goods_id");
        async_get_url($url);
	}

	/**
	 * 参加拼团
	 */
	public function joinGroupBuy($parameter){
		$order_id = $parameter['order_id'];
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
			if(empty($coupon_list_id)){
				$order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
			}else{
				$order['order_amount'] = (string)($goods['prom_price']);
			}
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
				redisdelall("getBuy_lock" . $goods_id);//删除锁
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
				$json = array('status'=>-1,'msg'=>'参团失败');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
				if (empty($coupon_Inc)) {
					M()->rollback();//有数据库操作不成功时进行数据回滚
					$json = array('status' => -1, 'msg' => '参团失败');
					echo "<script> alert('" . $json['msg'] . "') </script>";
					exit;
				}
				exit(json_encode($json));
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
					$pay_detail = $AliPay->addAlipayOrder($order['order_sn'],$user_id,$goods_id);
				}elseif($order['pay_code'] == 'qpay'){
					$qqPay = new QQPayController();
					$pay_detail = $qqPay->getQQPay($order);
				}
				$json = array('status'=>1,'msg'=>'参团成功','result'=>array('order_id'=>$o_id,'group_id'=>$group_buy,'pay_detail'=>$pay_detail));
				redisdelall("getBuy_lock" . $goods_id);//删除锁
                $rdsname = "getUserOrderList".$user_id."*";
                redisdelall($rdsname);//删除用户订单缓存
                $rdsname = "getGoodsDetails".$goods_id."*";
                redisdelall($rdsname);//删除商品详情缓存
                $rdsname = "TuiSong*";
                redisdelall($rdsname);//删除推送缓存
                if(!empty($ajax_get)){
                    echo "<script> alert('".$json['msg']."') </script>";
                    exit;
                }
				exit(json_encode($json));
			}else{
				M()->rollback();//有数据库操作不成功时进行数据回滚
				$json = array('status'=>-1,'msg'=>'参团失败');
				redisdelall("getBuy_lock" . $goods_id);//删除锁
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
		if(empty($coupon_list_id)){
			$order['order_amount'] = (string)($goods['prom_price']*$num-$coupon['money']);
		}else{
			$order['order_amount'] = (string)($goods['prom_price']);
		}
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
			redisdelall("getBuy_lock" . $goods_id);//删除锁
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
				redisdelall("getBuy_lock" . $goods_id);//删除锁
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
			redisdelall("getBuy_lock" . $goods_id);//删除锁
            $rdsname = "getUserOrderList".$user_id."*";
            redisdelall($rdsname);//删除用户订单缓存
            $rdsname = "getGoodsDetails".$goods_id."*";
            redisdelall($rdsname);//删除商品详情缓存
            $rdsname = "TuiSong*";
            redisdelall($rdsname);//删除推送缓存
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
			exit(json_encode($json));
		}else{
			M()->rollback();//有数据库操作不成功时进行数据回滚
			$json = array('status'=>-1,'msg'=>'开团失败');
			redisdelall("getBuy_lock" . $goods_id);//删除锁
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
		$user_id = $parameter['user_id'];
		$num = $parameter['num'];
		$store_id = $parameter['store_id'];
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
		$order['store_id'] = $store_id;
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
			redisdelall("getBuy_lock" . $goods_id);//删除锁
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
		$spec_data['store_id'] = $store_id;
		$spec_res = M('order_goods')->data($spec_data)->add();
		if(empty($spec_res))
		{
			M()->rollback();//有数据库操作不成功时进行数据回滚
			$json = array('status'=>-1,'msg'=>'购买失败');
			redisdelall("getBuy_lock" . $goods_id);//删除锁
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
				redisdelall("getBuy_lock" . $goods_id);//删除锁
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

			if($order['pay_code'] == 'weixin'){
				$weixinPay = new WeixinpayController();
				if($_REQUEST['openid'] || $_REQUEST['is_mobile_browser'] ==1 ){
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
                // End code by lcy
            }
			$json = array('status'=>1,'msg'=>'购买成功','result'=>array('order_id'=>$o_id,'pay_detail'=>$pay_detail));
			redisdelall("getBuy_lock" . $goods_id);//删除锁
            $rdsname = "getUserOrderList".$user_id."*";
            redisdelall($rdsname);//删除用户订单缓存
            $rdsname = "getGoodsDetails".$goods_id."*";
            redisdelall($rdsname);//删除商品详情缓存
            $rdsname = "TuiSong*";
            redisdelall($rdsname);//删除推送缓存
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
			exit(json_encode($json));
		}else{
			M()->rollback();//有数据库操作不成功时进行数据回滚
			$json = array('status'=>-1,'msg'=>'购买失败');
			redisdelall("getBuy_lock" . $goods_id);//删除锁
            if(!empty($ajax_get)){
                echo "<script> alert('".$json['msg']."') </script>";
                exit;
            }
		}
	}

	//获取小数点后面的长度
	private function getFloatLength($num) {
		$count = 0;
		$temp = explode ( '.', $num );
		if (sizeof ( $temp ) > 1) {
			$decimal = end ( $temp );
			$count = strlen ( $decimal );
		}
		return $count;
	}

	//操作价格
	public function operationPrice($price)
	{
		$price = sprintf('%.2f', $price);
		$fix = floatval(pow(10, strlen(explode('.', strval($price))[1])));
		$price = ($price*$fix)/$fix;
		return $price;
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

	public function getCompleteBuy()
	{
		$order_id = I('order_id');
		$pay_code = I('code');

		$order = M('order')->where('`order_id`='.$order_id)->field('order_sn,user_id')->find();
		//当订单已经是取消状态是不能继续支付
		if($order['order_status']==3)
		{
			$json = array('status'=>-1,'msg'=>'当前订单已经取消，请重新下单');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}
		$rdsname = "getOrderList_".$order['user_id']."*";
		redisdelall($rdsname);//删除订单列表
		$rdsname = "TuiSong*";
		redisdelall($rdsname);//删除推送缓存
		if($pay_code!=$order['pay_code'])
		{
			if($pay_code=='alipay')
			{
				$pay_name = '支付宝支付';
			}elseif($pay_code=='weixin'){
				$pay_name = '微信支付';
			}else{
				$pay_name = 'QQ支付';
			}
			M('order')->where('order_id='.$order_id)->save(array('pay_code'=>$pay_code,'pay_name'=>$pay_name));
		}
		if($pay_code=='weixin')
		{
			$weixinPay = new WeixinpayController();
			$pay_detail = $weixinPay->addwxorder($order['order_sn']);
		} elseif($pay_code=='alipay') {
				$AliPay = new AlipayController();
				$pay_detail = $AliPay->addAlipayOrder($order['order_sn']);
		}elseif($pay_code == 'qpay'){
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

		$test = M('order')->where('`invitation_num`='.$code)->find();
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
				if(empty($user_address))
				{
				$user_address = M('user_address')->where("`user_id` = $user_id")->field('address_id,consignee,address_base,address,mobile')->find();
			}
			//库存
			$store_count =  M('goods')->where("`goods_id` = $goods_id")->field('store_count')->find();

			$goods = M('goods')->where("`goods_id` = $goods_id")->field('goods_id,goods_name,shop_price,original_img,prom_price,the_raise,prom')->find();
		$goods['original_img'] = C('HTTP_URL').goods_thum_images($goods['goods_id'],400,400);
		$goods['store'] = M('merchant')->where("`id` = $store_id")->field('id,store_name,store_logo')->find();
		$goods['store']['store_logo'] = C('HTTP_URL').$goods['store']['store_logo'];
		//获取商品规格
		if(!empty($spec_key))
		{
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
//			$order = M('order')->where('`order_id`='.$order_id)->field('order_amount')->find();
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

		$key = M('temporary_key')->where('goods_id='.$goods_id.' and user_id='.$user_id)->order('add_time desc')->find();
		$spec_price = M('spec_goods_price')->where('goods_id='.$goods_id. " and `key`='".$key['goods_spec_key']."'")->find();
		if($type==1||$type==0) {
			$price = $spec_price['prom_price']*$num;
		} elseif($type==2) {
			$price = $spec_price['price']*$num;
		} else {
			exit(json_encode(array('status'=>-1,'msg'=>'参数错误')));
		}

		$user_coupon = M('coupon_list')->where('`uid`='.$user_id.' and `store_id`='.$store_id.' and `is_use`=0')->field('id,cid')->select();

		$id =array_column($user_coupon,'cid');
		//拿到所有优惠券，并根据condition倒叙输出
		$coupon = M('coupon')->alias('c')
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
		if(empty($coupon))
			$coupon=null;
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
		$countries = M('haitao_style')->where('`id` = '.$id)->find();
		$countries['img'] = TransformationImgurl($countries['img']);

		if($version=='2.0.0'){
			$where ='`show_type` = 0 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `countries_type` = '.$id;
			$data = $this->getGoodsList($where,$page,$pagesize,'sales desc');
		}else{
			$count = M('goods')->where('`show_type` = 0 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `countries_type` = '.$id)->count();
			$goods = M('goods')->where('`show_type` = 0 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `countries_type` = '.$id)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->select();

			foreach($goods as &$v)
			{
				$v['original_img'] =  TransformationImgurl($v['goods_id']);
			}

			$data = $this->listPageData($count,$goods);
		}

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
		$category = M('haitao')->where('`parent_id` = '.$id)->field('id,name')->select();

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
			$cat = M('haitao')->where('`parent_id` = '.$p_id)->field('id')->select();

			$condition['is_on_sale']=1;
			$condition['is_show'] = 1;
			$condition['is_audit'] = 1;
			$condition['is_special'] = 1;
			//array_column()将二维数组转成一维
			$condition['haitao_cat'] =array('in',array_column($cat,'id'));

			$count = M('goods')->where($condition)->count();
			$goods = M('goods')->where($condition)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->select();
		}
		else
		{
			$count = M('goods')->where('`is_special`=1 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `haitao_cat` = '.$id)->count();
			$goods = M('goods')->where('`is_special`=1 and `is_on_sale` = 1 and `is_show` = 1 and is_audit=1 and `haitao_cat` = '.$id)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page,$pagesize)->select();
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
		$version = I('version');
		$rdsname = "getsearch".$key.$page.$pagesize.$version;
        if (empty(redis($rdsname))) {//判断是否有缓存
	        if($version=='2.0.0'){
		        $where = "`goods_name` like '%$key%' and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `show_type`=0 ";
		        $data = $this->getGoodsList($where,$page,$pagesize,'');
	        }else{
		        $count = M('goods')->where("`goods_name` like '%$key%' and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `show_type`=0 ")->count();
		        $goods = M('goods')->where("`goods_name` like '%$key%' and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `show_type`=0 ")->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,free')->page($page, $pagesize)->select();
		        foreach ($goods as &$v) {
			        $v['original_img'] = goods_thum_images($v['goods_id'], 400, 400);
		        }
		        $data = $this->listPageData($count, $goods);
	        }
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

		$order = M('order')->where('`order_id` = '.$order_id)->field('shipping_code,shipping_order')->find();
		if(empty($order)){
			$json = array('status'=>-1,'msg'=>'订单不存在');
			if(!empty($ajax_get))
				$this->getJsonp($json);
			exit(json_encode($json));
		}

		$logistics = M('logistics')->where("`logistics_code`='".$order['shipping_code']."'")->field('logistics_name,logistics_mobile')->find();
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
		$order_info = M('group_buy')->where('`id`='.$id.' or `mark`='.$id.' and is_successful=1')->field('user_id,goods_num,mark')->select();
		//判断进来的是不是团长
		if(count($order_info)==1)
		{
			$order_info = M('group_buy')->where('`id`='.$order_info[0]['mark'].' or `mark`='.$order_info[0]['mark'].' and is_successful=1')->field('user_id,goods_num')->select();
		}
		$user_id['user_id'] = array('in',array_column($order_info,'user_id'));
		$user_info = M('users')->where($user_id)->field('oauth,head_pic,nickname,user_id')->select();

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

		$free = M('group_buy')->where('(`id`='.$id.' or `mark`='.$id.') and `is_free`=1')->field('user_id,goods_num')->select();
		$free_id['user_id'] = array('in',array_column($free,'user_id'));
		$free_info = M('users')->where($free_id)->field('oauth,head_pic,nickname,user_id')->select();

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
            $result = M('goods')->where(array('refresh'=>array('eq',0)))->field('goods_id')->order('goods_id desc')->find();
            if ($result) {
                M('goods')->where(array("goods_id" => array("eq", $result['goods_id'])))->setField('refresh', 1);
                $goods_id = $result['goods_id'];
                redisdelall("getDetaile_".$goods_id);
            } else {
                exit;
            }
        }
        //////
        $rdsname = 'getDetaile_'.$goods_id;
		if(empty(redis($rdsname))){
			//轮播图
			$banner = M('goods_images')->where("`goods_id` = $goods_id")->field('image_url')->select();

			foreach ($banner as &$v) {
				//TODO 缩略图处理
				$v['small'] = TransformationImgurl($v['image_url']);
				$v['origin'] = TransformationImgurl($v['image_url']);
				unset($v['image_url']);
			}

			if (empty($banner)) {
				$banner = null;
			}
			$goods = $this->getGoodsInfo($goods_id);
			//商品规格
			$goodsLogic = new \Home\Logic\GoodsLogic();
			$spec_goods_price = M('spec_goods_price')->where("goods_id = $goods_id")->select(); // 规格 对应 价格 库存表
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
				$key_name = M('spec_goods_price')->where("`key`='$spec_key'")->field('key_name')->find();
				$goods['goods_spec_name'] = $goods['goods_name'] . $key_name['key_name'];
			}
			if (!empty($ajax_get)) {
				$goods['html'] = htmlspecialchars_decode($goods['goods_content']);
			}

			//提供保障
			$security = array(array('type'=>'全场包邮','desc'=>'所有商品均无条件包邮'),array('type'=>'7天退换','desc'=>'商家承诺7天无理由退换货'),array('type'=>'48小时发货','desc'=>'成团后，商家将在48小时内发货'),array('type'=>'假一赔十','desc'=>'若收到的商品是假货，可获得加倍赔偿'));

			$json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'prom_price' => $goods['prom_price'], 'market_price' => $goods['market_price'], 'shop_price' => $goods['shop_price'], 'prom' => $goods['prom'], 'goods_remark' => $goods['goods_remark'], 'store_id' => $goods['store_id'] , 'is_support_buy' => $goods['is_support_buy'], 'is_special' => $goods['is_special'], 'original_img' => $goods['original_img'], 'goods_content_url' => $goods['goods_content_url'], 'goods_share_url' => $goods['goods_share_url'], 'fenxiang_url' => $goods['fenxiang_url'], 'collect' => $goods['collect'],'original_img'=>$goods['original_img'],'img_arr'=>$goods['img_arr'],'security'=>$security,'store' => $goods['store'],  'spec_goods_price' => $new_spec_goods, 'filter_spec' => $new_filter_spec));
			redis($rdsname, serialize($json));//写入缓
		}else{
			$json = unserialize(redis($rdsname));
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
			$collect = M('goods_collect')->where('goods_id = '.$goods_id.' and user_id = '.$user_id)->count();
		}else{
			$collect = 0;
		}

        $goods = M('goods')->alias('g')
	        ->join('INNER JOIN tp_merchant m on m.id = g.store_id')
	        ->where(array('g.goods_id'=>array('eq',$goods_id)))
	        ->field('g.store_count,g.sales,g.is_special,g.on_time,g.is_support_buy,m.sales as store_sales')
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
        $data['collect'] = $collect;
        $data['store_count'] = $goods['store_count'];
        $data['sales'] = $goods['sales'];
		$data['is_special'] = $goods['is_special'];
		$data['is_support_buy'] = $goods['is_support_buy'];
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

				$mens = M('group_buy')->where('`mark` = ' . $order_id['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();
				$group_buy[$i]['prom_mens'] = $group_buy[$i]['goods_num'] - $mens - 1;

				$user_name = M('users')->where('`user_id` = ' . $group_buy[$i]['user_id'])->field('nickname,oauth,mobile,head_pic')->find();
				if (!empty($user_name['oauth'])) {
					$group_buy[$i]['user_name'] = $user_name['nickname'];
					$group_buy[$i]['photo'] = $user_name['head_pic'];
				} else {
					$group_buy[$i]['photo'] = $user_name['head_pic'];
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
		$time = M()->query($sql);

		return $time;
	}

	//操作价格
	public function test($price=0.166666)
	{
		$price = sprintf("%.2f",substr(sprintf("%.4f", $price), 0, -2));
		$price = $price+0.01;
		echo $price;
	}
}