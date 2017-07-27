<?php
namespace Api_2_0_2\Controller;
use Think\Controller;

class GroupBuyController extends BaseController {
    /**
     * 关注点赞
     */
    public function autozan(){
        $group_buy_id = I('groupbuyid');
        $useropenid = I('useropenid');
        $oauth = 'weixin';
        //模拟微信登录
        // Create the stream context
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 20
            )
        ));
        // Fetch the URL's contents
        $url = "http://api.hn.pinquduo.cn/api_2_0_2/User/thirdLogin/openid/{$useropenid}/oauth/{$oauth}";
        $contents = file_get_contents($url, 0, $context);
        echo $contents;
        exit();
    }

}