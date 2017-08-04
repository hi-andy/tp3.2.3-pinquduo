<?php
namespace Api_2_0_2\Controller;
use Think\Controller;

class GroupBuyController extends BaseController {
    //const API_URL = 'http://api.hn.pinquduo.cn/';
    const API_URL = 'http://test.pinquduo.cn/';
    /**
     * 关注点赞
     */
    public function autozan(){
        $group_buy_id = I('groupbuyid');
        $useropenid = I('useropenid');
        $oauth = 'weixin';
        $nickname = I('nickname');
        $group_buy_id = (int)$group_buy_id;
        if($group_buy_id<=0){
            echo '团id非法';
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
            echo '团id查不到数据记录';
            exit();
        }
        //判断该团是不是已经结束
        if($result['end_time']<time()){
            echo '团活动已经结束';
            exit();
        }
        $userdata = $this->thirdLogin($useropenid,$oauth,$nickname);
        $postdata['user_id'] = $userdata['user_id'];
        $postdata['prom_id'] = $result['id'];
        $postdata['goods_id'] = $result['goods_id'];
        $postdata['type'] = 0;
        $postdata['free'] = 0;
        $postdata['num'] = 1;
        $postdata['address_id'] = 0;

        $orderid = $result['order_id'];
        $goods_id = $result['goods_id'];
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
        $resArr = json_decode($resdata,true);
        $wxtmplmsg = new WxtmplmsgController();
        $wxmsg = ((int)$resArr['status'] == -1) ? '您的好友参团失败':'您的好友参团成功';
        $wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,'助力享免单','获得0元秒杀权利');

        echo $resdata;
        exit();
    }

    /**
     * 我的免单
     */
    public function danList(){
        $userid = I('userid',0);
        $userid = (int)$userid;
        if($userid<=0){
            $data = [
                'status' => -1,
                'msg' => '参数非法'
            ];
            $this->ajaxReturn($data,'JSON');
            exit();
        }
        $uerdata = M('users')->field("user_id")->where("user_id={$userid}")->find();
        if(count($uerdata) == 0){
            $data = [
                'status' => -1,
                'msg' => '用户id无效'
            ];
            $this->ajaxReturn($data,'JSON');
            exit();
        }
        $groupbuylist = M('group_buy')->field("id,goods_id,end_time,goods_name,is_successful,goods_num")->where("is_raise=1 and mark=0 and user_id={$userid}")->select();
        if(count($groupbuylist)>0){
            foreach ($groupbuylist as $k => $value){
                $buyid = (int)$value['id'];
                $goods_id = (int)$value['goods_id'];
                $groupbuylist[$k]['end'] = 0;
                if($value['end_time']<time()){
                    $groupbuylist[$k]['end'] = 1;
                }
                $goodsinfo = M('goods')->field('original_img,list_img')->where('goods_id='.$goods_id)->find();
                $groupbuylist[$k]['list_img'] = $goodsinfo['list_img'];
                $groupbuylist[$k]['original_img'] = $goodsinfo['original_img'];
                $cantuannum = M('group_buy')->where('mark='.$buyid)->count();
                $cantuannum = (int)$cantuannum + 1;
                $groupbuylist[$k]['morenum'] = (int)$value['goods_num']-$cantuannum;
            }
        }
        $data = [
            'status' => 1,
            'msg' => '获取信息成功',
            'list' => $groupbuylist
        ];
        $this->ajaxReturn($data,'JSON');
        exit();

    }


        /*
         * 第三方登录
         */
    public function thirdLogin($openid,$oauth,$nickname){
        $map['openid'] = $openid;
        $map['oauth'] = $oauth;
        $map['nickname'] = $nickname;
        $map['head_pic'] = '';
        $map['unionid'] = '';
        $userLogic = new \Home\Logic\UsersLogic();
        $data = $userLogic->thirdLogin($map);
        return $data;
    }

}