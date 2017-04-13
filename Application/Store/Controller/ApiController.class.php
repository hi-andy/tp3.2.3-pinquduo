<?php
/**
 * ashop
 */

namespace Store\Controller;


class ApiController extends BaseController
{
    /*
     * 获取地区
     */
    public function getRegion()
    {
        $parent_id = I('get.parent_id');
        $data = M('region')->where("parent_id=$parent_id")->select();
        $html = '';
        if ($data) {
            foreach ($data as $h) {
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }

    public function getGoodsSpec()
    {
        $goods_id = I('get.goods_id');
        $temp = M()->query("SELECT GROUP_CONCAT(`key` SEPARATOR '_' ) AS goods_spec_item FROM __PREFIX__spec_goods_price WHERE goods_id = " . $goods_id);
        $goods_spec_item = $temp[0]['goods_spec_item'];
        $goods_spec_item = array_unique(explode('_', $goods_spec_item));
        if ($goods_spec_item[0] != '') {
            $spec_item = M()->query("SELECT i.*,s.name FROM __PREFIX__spec_item i LEFT JOIN __PREFIX__spec s ON s.id = i.spec_id WHERE i.id IN (" . implode(',', $goods_spec_item) . ") ");
            $new_arr = array();
            foreach ($spec_item as $k => $v) {
                $new_arr[$v['name']][] = $v;
            }
            $this->assign('specList', $new_arr);
        }
        $this->display();
    }

    /*
     * 获取商品价格
     */
    public function getSpecPrice()
    {
        $spec_id = I('post.spec_id');
        $goods_id = I('get.goods_id');
        if (!is_array($spec_id)) {
            exit;
        }
        $item_arr = array_values($spec_id);
        sort($item_arr);
        $key = implode('_', $item_arr);
        $goods = M('spec_goods_price')->where(array('key' => $key, 'goods_id' => $goods_id))->find();
        $info = array(
            'status' => 1,
            'msg' => 0,
            'data' => $goods['price'] ? $goods['price'] : 0
        );
        exit(json_encode($info));
    }

    //商品价格计算
    public function calcGoods()
    {
        $goods_id = I('post.goods'); // 添加商品id
        $price_type = I('post.price') ? I('post.price') : 3; // 价钱类型
        $goods_info = M('goods')->where(array('goods_id' => $goods_id))->find();
        if (!$goods_info['goods_id'] > 0)
            exit; // 不存在商品
        switch ($price_type) {
            case 1:
                $goods_price = $goods_info['market_price']; //市场价
                break;
            case 2:
                $goods_price = $goods_info['shop_price']; //市场价
                break;
            case 3:
                $goods_price = I('post.goods_price'); //自定义
                break;
        }

        $goods_num = I('post.goods_num'); // 商品数量

        $total_price = $goods_price * $goods_num; // 计算商品价格

        $info = array(
            'status' => 1,
            'msg' => '',
            'data' => $total_price
        );
        exit(json_encode($info));

    }

    /*
     * 易掌柜接口
     * */
    //
    function Y_login()
    {
        $store_name = I('post.store_name');
        $store_pass_word = I('post.pass_word');

        $res = M('merchant')->where("merchant_name = '$store_name' and password = '".md5($store_pass_word)."'")->find();

        if(!empty($res)){
            if($res['state']==0) {
                exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂时没有营业，请及时客服沟通')));
            }elseif($res['is_check']==0){
                exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂时还没审核')));
            }elseif($res['is_check']==2){
                exit(json_encode(array('code'=>0,'Msg'=>'您的店铺暂审核未通过')));
            }else{
                exit(json_encode(array('code'=>1,'Msg'=>'登录成功','Store_id'=>$res['id'])));
            }
        }else{
            exit(json_encode(array('code'=>0,'Msg'=>'没有该店铺')));
        }
    }

    function Y_orderlist()
    {
        $store_id = I('post.store_id');//商户ID
        $start_time = I('post.start_time',0);//获取订单的时间段
        $end_time = I('post.end_time');//结束时间必须传

        $where = "o.store_id = $store_id and o.order_type in (3,14) and ";
        //判断值的接收状态拼接where条件
        !empty($start_time) && !empty($end_time) && $where = $where."o.add_timen between '$start_time' and '$end_time'";

        $store_info = M('order')->alias('o')
            ->join('INNER JOIN tp_merchant on m o.store_id = m.id')
            ->where($where)
            ->field('o.*,m.store_name')
            ->select();
        if(!empty($store_info)){
            /*处理订单数组的数据*/


        }else{
            exit(json_encode(array('code'=>0,'Msg'=>'您暂时还有新的未发货订单哦！')));
        }

    }

}