<?php
/**
 * 生成商品详情缓存控制器
 */
namespace Api_2_0_2\Controller;

class CacheController extends BaseController {

    public function _initialize() {

    }

	//新版本商品详情 2.0.2
	function getDetaile()
	{
        $goods_id = I('goods_id');
        // 查看一下要生成缓存的商品是否真实存在，状态正常
        $goods_id = M('goods')->where('goods_id='.$goods_id.' and is_show=1 and is_on_sale=1 and is_audit=1')->getField('goods_id');
        if ($goods_id){
            redisdelall("getDetaile_".$goods_id);
        } else {
            return '';
        }
        $keyName = 'getDetail_' . $goods_id;

        $goods = $this->getGoodsInfo($goods_id);
        //轮播图
        if($goods['is_special']==7){
            $f_goods_id = M('goods_activity')->where('goods_id='.$goods_id)->getField('f_goods_id');
            $banner = M('goods_images')->where("`goods_id` = $f_goods_id")->field('image_url')->select();
        }else{
            $banner = M('goods_images')->where("`goods_id` = $goods_id")->field('image_url')->select();
        }

        foreach ($banner as &$v) {
            //TODO 缩略图处理
            $v['small'] = TransformationImgurl($v['image_url']);
            $v['origin'] = TransformationImgurl($v['image_url']);
            unset($v['image_url']);
        }

        for($i=0;$i<count($banner);$i++){
            $size = getimagesize($banner[$i]['small']);
            $banner[$i]['origin'] = $banner[$i]['small'];
            $banner[$i]['width']=$size[0];
            $banner[$i]['height']=$size[1];
        }
        if (empty($banner)) {
            $banner = null;
        }
        //商品规格
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $spec_goods_price = M('spec_goods_price')->where("goods_id = $goods_id")->select(); // 规格 对应 价格 库存表
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

        $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'prom_price' => $goods['prom_price'], 'market_price' => $goods['market_price'], 'shop_price' => $goods['shop_price'], 'prom' => $goods['prom'], 'goods_remark' => $goods['goods_remark'], 'store_id' => $goods['store_id'], 'is_support_buy' => $goods['is_support_buy'], 'is_special' => $goods['is_special'], 'original_img' => $goods['original_img'],'original'=>$goods['original'],'goods_content_url' => $goods['goods_content_url'], 'goods_share_url' => $goods['goods_share_url'], 'fenxiang_url' => $goods['fenxiang_url'], 'collect' => $goods['collect'], 'original_img' => $goods['original_img'], 'img_arr' => $goods['img_arr'], 'security' => $security, 'store' => $goods['store'], 'spec_goods_price' => $new_spec_goods, 'filter_spec' => $new_filter_spec));
        redis($keyName, serialize($json));//写入缓

        exit('ok');
	}

}
