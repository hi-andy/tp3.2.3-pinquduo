<?php
namespace Api_2_0_2\Controller;
use Think\Controller;

class GroupBuyController extends BaseController {
    //限制开团时间
    const GROUPSTART = '2017-08-17';
    //限制成团的最大数
    const GROUPMAX = 5;
    /**
     * 关注点赞
     */
    public function autozan(){
        $group_buy_id = I('groupbuyid');
        $useropenid = I('useropenid');
        $oauth = 'wx';
        $nickname = I('nickname');
        $unionid = I('unionid');
        $group_buy_id = (int)$group_buy_id;
        $wxtmplmsg = new WxtmplmsgController();
        $msgone = '助力享免单';
        $msgtwo = '获得0元秒杀权利';

        //非法的团id
        if($group_buy_id<=0){
            $wxmsg = '您参加的团id非法';
            //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
            echo json_encode(['status'=>0,'msg'=>$wxmsg]);
            exit();
        }
        //检测有没有redis数据
        if (empty(redis("GroupBuy_lock_".$group_buy_id))) {//如果无锁
            redis("GroupBuy_lock_" . $group_buy_id, "1", 5);//写入锁
            //先检测这个团id是否合法
            $where = [
                'id' => $group_buy_id,  //团id
                'mark' => 0,            //团长
                'is_raise' => 1,        //点赞团
                'is_pay' => 1,          //已支付
                //'is_successful' => 0,   //未成团
            ];
            $result = M('group_buy')->field('user_id,goods_id,is_successful,order_id,goods_num,end_time,intro,goods_price,goods_name,store_id')->where($where)->find();
            $tuanuserdata = M('users')->where("user_id={$result['user_id']}")->field("wx_openid,nickname")->find();
            $zhangnickname = $tuanuserdata['nickname'];
            //查不到数据记录
            if(count($result)<=0){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '您参加的团id查不到数据记录';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            if((int)$result['is_successful'] == 1){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '该团已经满员了，您可以自己开团享受0元进口榴莲秒杀哦';
                //wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            //判断该团是不是已经结束
            if($result['end_time']<=time()){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '您参加的团活动已经结束，您可以自己开团享受0元进口榴莲秒杀哦';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }

            $userdata = $this->thirdLogin($useropenid,$oauth,$nickname,$unionid);

            if(count($userdata)==0){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '您的用户信息非法';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            $user_id = (int)$userdata['user_id'];

            //处理自己参加自己的团
            if($user_id == (int)$result['user_id']){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '自己开团成功';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            //处理掉用户id非法的情况-温立涛结束
            $goods_id = $result['goods_id'];
            //判断商品是否已经下架-温立涛开始
            $goodsstatus = M('goods')
                ->where("goods_id={$goods_id} and (show_type=1 or is_show=0 or is_on_sale=0)")
                ->count();
            if ($goodsstatus >0){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '该商品已经下架，您可以自己选择其他商品开团哦';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }

            //防刷条件  2017-08-18 温立涛 开始
            //$groupStart = strtotime(self::GROUPSTART);
            //防刷条件  2017-08-24 温立涛 开始
            $groupStart = time()-259200;
            $order_id = $result['order_id'];
            $orderInfo = M('order')->where(['order_id'=>$order_id])->find();
            $mobile = $orderInfo['mobile'];
            $orderCount = M('order')->where("mobile='{$mobile}' and the_raise=1 and order_type>=14 and add_time>{$groupStart}")->count();
            M('admin_log')->data(['admin_id'=>1,'log_ip'=>'127.0.0.1','log_url'=>$orderCount.'=='.$mobile.'=='.$user_id])->add();
            if($orderCount > self::GROUPMAX){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = "很抱歉，您无法帮好友@{$zhangnickname}助力哦！\n只有新用户才能帮好友助力，这里送您0元秒杀的机会，快点试一下吧！\n点击下方消息参与↓↓↓";
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            //防刷条件  2017-08-18 温立涛 结束
            $raise = M('group_buy')->where('mark!=0 and is_raise=1 and is_pay = 1 and user_id ='.$user_id)->find();
            M('admin_log')->data(['admin_id'=>1,'log_ip'=>'127.0.0.1','log_url'=>json_encode($raise)])->add();
            if(!empty($raise)){
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = "很抱歉，您无法帮好友@{$zhangnickname}助力哦！\n只有新用户才能帮好友助力，这里送您0元秒杀的机会，快点试一下吧！\n点击下方消息参与↓↓↓";
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
//            $spec_key = M('order_goods')->where("order_id = {$result['order_id']}")->getField('spec_key');
//            $num = M('spec_goods_price')->where('`goods_id`=' . $goods_id . " and `key`='$spec_key'")->find();
//            if($num['store_count']<1){
//                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
//                $wxmsg = '该规格刚售完，请重新选择 ^_^';
//                $wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
//                exit();
//            }
            $groupnum = M('group_buy')->where("mark={$group_buy_id}")->count();
            $groupnum = (int)$groupnum+1;
            if($groupnum>=(int)$result['goods_num']){
                $morenum = $groupnum-(int)$result['goods_num'];
                if($morenum>0){
                    M('group_buy')->where("mark={$group_buy_id}")->order('id desc')->limit($morenum)->delete();
                }
                redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                $wxmsg = '该团已经满员了，您可以自己选择其他商品开团哦';
                //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                exit();
            }
            if(count($result)>0)
            {
                M()->startTrans();//开启事务处理
                //在团购表加一张单
                $data['start_time'] = time();
                $data['end_time'] = $result['end_time'];
                $data['goods_id'] = $goods_id;
                $data['price'] = 0.00;
                $data['goods_num'] = (int)$result['goods_num'];
                $data['order_num'] = 1;
                $data['intro'] = $result['intro'];
                $data['goods_price'] = (float)$result['goods_price'];
                $data['goods_name'] = $result['goods_name'];
                $data['photo'] = CDN.'/Public/upload/logo/logo.jpg';
                $data['mark'] = $group_buy_id;
                $data['user_id'] = $user_id;
                $data['store_id'] = (int)$result['store_id'];
                $data['address_id'] = 0;
                $data['free'] = 0;
                $data['order_id'] = 0;
                $data['is_raise']=1;
                $data['is_pay']=1;
                $group_buy = M('group_buy')->data($data)->add();

                if( (int)$group_buy>0 )
                {
                    redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                    if((int)$groupnum+1==(int)$result['goods_num']){
                        $ressave = M('group_buy')->data(['is_successful'=>1])->where("mark={$group_buy_id}")->save();
                        $mainres = M('group_buy')->data(['is_successful'=>1])->where("id={$group_buy_id}")->save();
                        $orderres = M('order')->where("order_id={$result['order_id']}")->data(['order_status'=>11,'order_type'=>14])->save();
                        $spec_name = M('order_goods')->where('`order_id`='.$result['order_id'])->field('spec_key')->find();
                        M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_name[spec_key]'")->setDec('store_count',1);
                        M('goods')->where('`goods_id` = '.$goods_id)->setDec('store_count',1);//库存自减
                        M('goods')->where('`goods_id` = '.$goods_id)->setInc('sales',1);//销量自加
                        if($ressave && $mainres && $orderres){
                            M()->commit();

                            $wxmsg = "恭喜您，已帮好友@{$zhangnickname} 助力成功哦，您好友的团满团了！\n为了表示感谢，这里送您0元秒杀的机会，快点试一下吧！\n点击下方消息参与↓↓↓";
                            //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                            echo json_encode(['status'=>1,'msg'=>$wxmsg]);
                            $wxmsg = '您的好友已经帮您助力成功，您的团成功满团';
                            $wxtmplmsg->groupbuy_msg($tuanuserdata['wx_openid'],$wxmsg,$msgone,$msgtwo);

                        }else{
                            M()->rollback();//有数据库操作不成功时进行数据回滚
                            $wxmsg = '服务器异常，请稍后重试';
                            //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                            echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                        }
                    }else{
                        M()->commit();
                        $wxmsg = "恭喜您，已帮好友@{$zhangnickname} 助力成功哦！\n为了表示感谢，这里送您0元秒杀的机会，快点试一下吧！\n点击下方消息参与↓↓↓";
                        //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                        echo json_encode(['status'=>1,'msg'=>$wxmsg]);
                    }
                    exit();
                }else{
                    M()->rollback();//有数据库操作不成功时进行数据回滚
                    redisdelall("GroupBuy_lock_".$group_buy_id);//删除锁
                    $wxmsg = '服务器异常，请稍后重试';
                    //$wxtmplmsg->groupbuy_msg($useropenid,$wxmsg,$msgone,$msgtwo);
                    echo json_encode(['status'=>0,'msg'=>$wxmsg]);
                    exit();
                }
            }
        }


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
        $groupbuylist = M('group_buy')->field("id,goods_id,end_time,goods_name,order_id,is_successful,goods_num")->where("is_raise=1 and mark=0 and user_id={$userid}")->order('id desc')->select();
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
                if(strstr($groupbuylist[$k]['original_img'],"http://cdn") && !strstr($groupbuylist[$k]['original_img'],"https://cdn2")){
                        $cha = $groupbuylist[$k]['original_img'];
                        $cha = explode('http://cdn',$cha);
                        $groupbuylist[$k]['original_img'] = 'https://cdn2'.$cha[1];
                    }
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
    public function thirdLogin($openid,$oauth,$nickname,$unionid){
        $map['openid'] = $openid;
        $map['oauth'] = $oauth;
        $map['nickname'] = $nickname;
        $map['head_pic'] = '';
        $map['unionid'] = $unionid;
        $userLogic = new \Home\Logic\UsersLogic();
        $data = $userLogic->thirdLogin($map);
        return $data;
    }

}