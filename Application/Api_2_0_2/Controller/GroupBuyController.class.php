<?php
namespace Api_2_0_2\Controller;
use Think\Controller;

class GroupBuyController extends BaseController {
    const API_URL = 'http://api.hn.pinquduo.cn/';
    /**
     * 关注点赞
     */
    public function autozan(){
        $group_buy_id = I('groupbuyid');
        $useropenid = I('useropenid');
        $oauth = 'weixin';
        $group_buy_id = (int)$group_buy_id;
        if($group_buy_id<=0){
            echo '1';
            exit();
        }
        //先检测这个团id是否合法
        $where = [
            'id' => $group_buy_id,  //团id
            'mark' => 0,            //团长
            'is_raise' => 1,        //点赞团
            'is_pay' => 1,          //已支付
            'is_successful' => 0,   //未成团
        ];
        $result = M('group_buy')->where($where)->find();
        //查不到数据记录
        if(count($result)<=0){
            echo '2';
            exit();
        }
        //判断该团是不是已经结束
        if($result['end_time']<time()){
            echo '3';
            exit();
        }
        //模拟微信登录
        // Create the stream context
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5
            )
        ));
        // Fetch the URL's contents
        $url = self::API_URL."api_2_0_2/User/thirdLogin/openid/{$useropenid}/oauth/{$oauth}";
        echo $url;
        exit();
        $contents = file_get_contents($url, 0, $context);
        $getArray = json_decode($contents,true);
        $userdata = $getArray['result'];
        $postdata['user_id'] = $userdata['user_id'];
        $postdata['prom_id'] = $result['id'];
        $postdata['goods_id'] = $result['goods_id'];
        $postdata['type'] = 0;
        $postdata['free'] = 0;
        $postdata['num'] = 1;
        $postdata['address_id'] = 0;

        $orderid = $result['order_id'];
        $ordergood = M('order_goods')->field('spec_key')->where(['goods_id'=>$goods_id,'order_id'=>$orderid])->find();
        $postdata['spec_key'] = $ordergood['spec_key'];
        //模拟为我点赞请求
        $posturl = self::API_URL."api_2_0_2/Purchase/getBuy";
        $postcontext = array();
        ksort($postdata);
        $postcontext['http'] = array(
            'timeout'=>5,
            'method' => 'POST',
            'content' => http_build_query($postdata, '', '&'),
        );
        $resdata = file_get_contents($posturl, false, stream_context_create($postcontext));
        echo $resdata;
        exit();
    }

}