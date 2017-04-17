<?php
/**
 * User: admin
 */

namespace Api\Controller;

class StoreController extends BaseController{

	/*
	 * 商户详情
	 * */
	public function getStoreList()
	{
		$store_id = I('store_id');
		$stor = I('stor','sales');//last_update

		$page = I('page',1);
		$pagesize = I('pagesize',10);
        $rdsname = "getStoreList".$store_id.$stor.$page.$pagesize;
        if (empty($rdsname)) {//判断是否有缓存
            $store = M('merchant')->where('`id` = ' . $store_id)->field('store_name,mobile,store_logo,sales,introduce,store_logo')->find();
            $store['store_share_url'] = 'http://wx.pinquduo.cn/shop_detail.html?store_id=' . $store_id;

            $count = M('goods')->where('`show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_audit`=1 and `store_id` = ' . $store_id)->count();
            $goods = M('goods')->where('`show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_audit`=1 and `store_id` = ' . $store_id)->page($page, $pagesize)->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,prom_price,free')->order("$stor desc ")->select();

            //合成商户logo的分享图
            if (file_exists('Public/upload/store_fenxiang/' . $store_id . '.jpg')) {
                $store['logo_share_url'] = C('HTTP_URL') . '/Public/upload/store_fenxiang/' . $store_id . '.jpg';
            } elseif (file_exists('Public/upload/store_fenxiang/' . $store_id . '.png')) {
                $store['logo_share_url'] = C('HTTP_URL') . '/Public/upload/store_fenxiang/' . $store_id . '.png';
            } elseif (file_exists('Public/upload/store_fenxiang/' . $store_id . '.gif')) {
                $store['logo_share_url'] = C('HTTP_URL') . '/Public/upload/store_fenxiang/' . $store_id . '.gif';
            } else {
                $goods_pic_url = $store['store_logo'];
                $pin = $this->storeLOGO($goods_pic_url, $store_id);
                $store['logo_share_url'] = C('HTTP_URL') . $pin;
            }
            $store['store_logo'] = C('HTTP_URl') . $store['store_logo'];
            if (empty($count)) {
                $count = null;
            } elseif (empty($goods)) {
                $goods = null;
            }
            //获取店铺优惠卷store_logo_compression
            $coupon = M('coupon')->where('`store_id` = ' . $store_id . ' and `send_start_time` <= ' . time() . ' and `send_end_time` >= ' . time() . ' and createnum!=send_num')->select();
            if (empty($coupon)) {
                $coupon = null;
            }

            foreach ($goods as &$v) {
                $v['original_img'] = C('HTTP_URL') . goods_thum_images($v['goods_id'], 400, 400);
            }

            $data = $this->listPageData($count, $goods);
            $json = array('status' => 1, 'msg' => '', 'result' => array('store' => $store, 'goods' => $data, 'coupon' => $coupon));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }

		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}


	function storeLOGO($path,$store_id)
	{
		$bigImgPath = $this->store_thum_images($path,$store_id,80,80);
		//'Public/images/goods_thumb_1055_400_400_58a8643127a2c.jpeg'
		$qCodePath = 'Public/images/stroe_logo.jpg';

		$bigImg = imagecreatefromstring(file_get_contents($bigImgPath));
		$qCodeImg = imagecreatefromstring(file_get_contents($qCodePath));

		list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize($qCodePath);
		// imagecopymerge使用注解
		imagecopymerge($bigImg, $qCodeImg, 0, 50, 0, 0, $qCodeWidth, $qCodeHight, 100);

		list($bigWidth, $bigHight, $bigType) = getimagesize($bigImgPath);

		switch ($bigType) {
			case 1: //gif
//                header('Content-Type:image/gif');
				$pic = '/data/wwwroot/default/Public/upload/store_fenxiang/'.$store_id.'.gif';
				$pin = '/Public/upload/store_fenxiang/'.$store_id.'.gif';
				imagejpeg($bigImg, $pic);
				break;
			case 2: //jpg
//                header('Content-Type:image/jpg');
				$pic = '/data/wwwroot/default/Public/upload/store_fenxiang/'.$store_id.'.jpg';
				$pin = '/Public/upload/store_fenxiang/'.$store_id.'.jpg';
				imagejpeg($bigImg, $pic);
				break;
			case 3: //png
//                header('Content-Type:image/png');
				$pic = '/data/wwwroot/default/Public/upload/store_fenxiang/'.$store_id.'.png';
				$pin = '/Public/upload/store_fenxiang/'.$store_id.'.png';
				imagejpeg($bigImg,$pic);
				break;
			default:
				# code...
				break;
		}
		return $pin;
	}

	function store_thum_images($logo,$store_id,$width,$height){

		if(empty($store_id))
			return '';

		$img = explode('/',$logo);
		$img1 = explode('.',$img[6]);

		//判断缩略图是否存在
		$path = "Public/upload/store_logo_compression/";
		$goods_thumb_name =$store_id.$img1[0];

		// 这个商品 已经生成过这个比例的图片就直接返回了
		if(file_exists($path.$goods_thumb_name.'.jpg'))  return $path.$goods_thumb_name.'.jpg';
		if(file_exists($path.$goods_thumb_name.'.jpeg')) return $path.$goods_thumb_name.'.jpeg';
		if(file_exists($path.$goods_thumb_name.'.gif'))  return $path.$goods_thumb_name.'.gif';
		if(file_exists($path.$goods_thumb_name.'.png'))  return $path.$goods_thumb_name.'.png';

		if(empty($logo)) return '';

		$logo = '.'.$logo; // 相对路径
		if(!file_exists($logo)) return '';

		$image = new \Think\Image();
		$image->open($logo);

		$goods_thumb_name = $goods_thumb_name. '.'.$image->type();
		// 生成缩略图
		if(!is_dir($path))
			mkdir($path,0777,true);

		// 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
		$image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存

		return $path.$goods_thumb_name;
	}
}