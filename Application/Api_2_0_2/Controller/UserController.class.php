<?php
namespace Api_2_0_2\Controller;
use Admin\Logic\OrderLogic;
use Think\Controller;

class UserController extends BaseController {
    public $userLogic;
    public function _initialize(){
        parent::_initialize();
        $version = I('version');
        $this->userLogic = new \Home\Logic\UsersLogic();
//        $this->encryption();
    }

    /*
     * 第三方登录
     */
    public function thirdLogin(){
        $map['openid'] = I('openid','');
        $map['oauth'] = I('oauth','');
        $map['nickname'] = I('nickname','');
        $map['head_pic'] = I('head_pic','');
        $map['unionid'] = I('unionid','');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        // 注册用户到环信
        $data = $this->userLogic->thirdLogin($map);
        $HXcall = new HxcallController();
        $username = $data['user_id'];
        $password = md5($username.C('SIGN_KEY'));
        $nickname = $data['nickname'];
        $HXcall->hx_register($username,$password,$nickname);

        //　构建返回数据
        $data['name'] = $data['nickname'];
        $data['head_pic'] = TransformationImgurl($data['head_pic']);
        unset($data['nickname']);
        $json = array('status'=>1,'msg'=>'登录成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /*
     * 获取用户信息
     */
    public function userInfo(){
        $user_id = I('user_id');
        $data = $this->userLogic->get_info($user_id);

        exit(json_encode($data));
    }

    /*
     *更新用户信息
     */
    public function updateUserInfo(){
        if(IS_POST){
            $user_id = I('user_id');
            if(!$user_id)
                exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));

            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : false;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区

            if(!$this->userLogic->update_info($user_id,$post))
                exit(json_encode(array('status'=>-1,'msg'=>'更新失败','result'=>'')));
            exit(json_encode(array('status'=>1,'msg'=>'更新成功','result'=>'')));
        }
    }

    /*
     * 修改用户密码
     */
    public function password(){
        if(IS_POST){
            $user_id = I('user_id');
            if(!$user_id)
                exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
            $data = $this->userLogic->password($user_id,I('post.old_password'),I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            exit(json_encode($data));
        }
    }

    /**
     * 获取收货地址
     */
    public function getAddressList(){
        $user_id = I('user_id');
        if(!$user_id)
            exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
        $address = M('user_address')->where(array('user_id'=>$user_id))->select();
        if(!$address)
            exit(json_encode(array('status'=>1,'msg'=>'没有数据','result'=>'')));
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$address)));
    }

    /*
     * 获取优惠券列表
     */
    public function getCouponList(){
        $user_id = I('user_id');
        $state = I('state');
        $page = I('page',1);
        $pagesize = I('pagesize',30);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!$user_id){
            $json = array('status'=>-1,'msg'=>'参数有误');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        $coupons_list = M('coupon_list')->where('`uid` = '.$user_id)->field('cid,is_use')->page($page,$pagesize)->select();

        if($state == 0)//未使用的优惠券
        {
            $j=0;
            for($i=0;$i<count($coupons_list);$i++)
            {//获取领取的优惠券的详细参数
                $coupons_details[$i] = M('coupon')->alias('c')
                    ->where('c.id = '.$coupons_list[$i]['cid'])
                    ->join('INNER JOIN tp_merchant m on c.store_id = m.id ')
                    ->field('c.id,c.name,c.money,c.condition,c.use_start_time,c.use_end_time,m.store_name,m.id as store_id')
                    ->find();
                if($coupons_list[$i]['is_use']==0 && $coupons_details[$i]['use_end_time'] >= time() )
                {
                    $data[$j] = $coupons_details[$i];
                    $j++;
                }
            }
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('items'=>$data));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        elseif($state==1)//使用的优惠券
        {
            $j=0;
            for($i=0;$i<count($coupons_list);$i++)
            {
                $coupons_details[$i] = M('coupon')->alias('c')
                    ->where('c.id = '.$coupons_list[$i]['cid'])
                    ->join('INNER JOIN tp_merchant m on c.store_id = m.id ')
                    ->field('c.id,c.name,c.money,c.condition,c.use_start_time,c.use_end_time,m.store_name,m.id as store_id')
                    ->find();
                if($coupons_list[$i]['is_use']==1 || $coupons_details[$i]['use_end_time'] < time() )
                {
                    $data[$j] = $coupons_details[$i];
                    $j++;
                }
            }
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('items'=>$data));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        else
        {
            $json = array('status'=>-1,'msg'=>'非法参数');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }

    /*
     * 用户点击领取优惠券
     * */
    function getReceiveCoupon()
    {
        $user_id = I('user_id');
        $coupon_id = I('coupon_id');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        //判断是否重复领取
        $res = M('coupon_list')->where("`uid` = $user_id and `cid` = $coupon_id")->select();
        if($res)
        {
            $json = array('status'=>-1,'msg'=>'已经领取过了，快去购买使用吧');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }else{
            M()->startTrans();
            $coupon_max = M('coupon')->where('`id`='.$coupon_id)->field('createnum,send_num')->find();
            if($coupon_max['createnum']==$coupon_max['send_num'])
            {
                $json = array('status'=>-1,'msg'=>'已发放完');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }
            $data['cid'] = $coupon_id;
            $data['uid'] = $user_id;
            $data['type'] = M('coupon')->where('`id` = '.$coupon_id)->getField('type');
            $data['send_time'] = M('coupon')->where('`id` = '.$coupon_id)->getField('send_start_time');
            $data['store_id'] = M('coupon')->where('`id` = '.$coupon_id)->getField('store_id');

            $res = M('coupon_list')->data($data)->add();
            if(empty($res))
            {
                M()->rollback();
                $json = array('status'=>-1,'msg'=>'领取失败');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }
            $setInc = M('coupon')->where('`id`='.$coupon_id)->setInc('send_num');
            if(!empty($setInc))
            {
                M()->commit();
                $json = array('status'=>1,'msg'=>'领取成功');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }else{
                M()->rollback();
                $json = array('status'=>-1,'msg'=>'领取失败');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }
        }

    }
    /*
     * 获取拼团或订单详情
     */
    public function getPromDetail(){
        $order_id = I('order_id');
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        I('invitation_num') && $invitation_num = strtolower(I('invitation_num'));//统一大小写
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(empty($user_id))
        {
            //if(empty(redis($rdsname))){//判断是否有缓存
            $json = array('status'=>-1,'msg'=>'非法数据');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        if($invitation_num)
        {
            $order = M('order')->where("`invitation_num`='$invitation_num'")->field('order_id,invitation_num,user_id,goods_id,total_amount,address,address_base,order_sn,add_time,pay_name,order_status,shipping_status,pay_status,prom_id,num,order_amount,shipping_name,shipping_code,shipping_order,delivery_time,automatic_time,is_return_or_exchange,the_raise,free,order_type')->find();
        }else{
            $order = M('order')->where('`order_id`='.$order_id)->field('order_id,invitation_num,user_id,goods_id,total_amount,address,address_base,order_sn,add_time,pay_name,order_status,shipping_status,shipping_order,pay_status,prom_id,num,order_amount,shipping_name,shipping_code,delivery_time,automatic_time,is_return_or_exchange,the_raise,free,order_type')->find();
        }
        $order['share_url'] = C('SHARE_URL').'/prom_regiment.html?order_id='.$order_id.'&user_id='.$user_id;
        $order['spec_key'] = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key')->find();

        if($order['prom_id']!=null)
        {
            $order['goodsInfo'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('goods_id,goods_name,goods_remark,original_img,prom,prom_price,cat_id,market_price,store_id')->find();
            $order['goodsInfo']['original_img'] =  goods_thum_images($order['goods_id'],400,400);

            //获取分享缩略图
            $order['goodsInfo']['fenxiang_url'] = $order['goodsInfo']['original_img']."/q/75|watermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nTE9HTy5qcGc=/dissolve/100/gravity/South/dx/0/dy/0|imageslim";
            $store_id['id'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('store_id')->find();
            $order['goodsInfo']['store']['id']= $store_id['id']['store_id'];
            $order['rules'] = M('rules_text')->find();
            $promInfo = M('group_buy')->where('`order_id` = '.$order['order_id'])->field('id,photo,end_time,goods_num,start_time,user_id,mark,order_id,end_time,is_free')->find();

            if(!empty($promInfo['mark']))
            {
                $is_self = M('group_buy')->where('`id` = '.$promInfo['mark'].' and `user_id`='.$user_id)->field('mark')->find();
                if(!empty($is_self)){
                    $promInfo['is_self'] = null;
                }
            } elseif(empty($promInfo['mark'])) {
                $is_self = M('group_buy')->where('`order_id` = '.$order['order_id'].' and `user_id`='.$user_id)->field('mark')->find();
                if(!empty($is_self)) {
                    $promInfo['is_self'] = 1;
                }elseif(empty($is_self)){
                    $promInfo['is_self'] = null;
                }
            }
            else
            {
                $json = array('status'=>-1,'msg'=>'数据异常');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }

            $promInfo['photo'] = TransformationImgurl($promInfo['photo']);
            $promInfo['prom'] = $order['goodsInfo']['prom'];
            if(!empty($promInfo['mark'])) {
                $join_num = M('group_buy')->where('`mark` = '.$promInfo['mark'].' and `is_pay`=1')->select();
                $parents = M('group_buy')->where('`id`='.$promInfo['mark'])->find();
                $promInfo['join_num'][0] = M('users')->where('`user_id` = '.$parents['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();
                $promInfo['join_num'][0]['is_free'] = $parents['is_free'];
                $promInfo['join_num'][0]['addtime'] = $parents['start_time'];
                $promInfo['join_num'][0]['id'] = $parents['id'];
            } elseif (empty($promInfo['mark'])) {
                $join_num = M('group_buy')->where('`mark` = '.$promInfo['id'].' and `is_pay`=1')->select();
                $promInfo['join_num'][0] = M('users')->where('`user_id` = '.$order['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();
                $promInfo['join_num'][0]['is_free'] = $promInfo['is_free'];
                $promInfo['join_num'][0]['addtime'] = $promInfo['start_time'];
                $promInfo['join_num'][0]['id'] = $promInfo['id'];
            }
            $promInfo['join_num'][0]['head_pic'] = TransformationImgurl($promInfo['join_num'][0]['head_pic']);
            if(!empty($promInfo['join_num'][0]['oauth']))
            {
                $promInfo['join_num'][0]['name'] = $promInfo['join_num'][0]['nickname'];
            }else{
                $promInfo['join_num'][0]['name'] = substr_replace($promInfo['join_num'][0]['mobile'], '****', 3, 4);
            }

            for($i=1;$i<=count($join_num);$i++)
            {
                $mobile = M('users')->where('`user_id` = '.$join_num[$i-1]['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();

                $start_time = M('group_buy')->where('`id`='.$join_num[$i-1]['id'])->field('start_time,is_free')->find();

                $promInfo['join_num'][$i]['user_id'] = $mobile['user_id'];
                if(!empty($mobile['oauth']))
                {
                    $promInfo['join_num'][$i]['name'] = $mobile['nickname'];
                }else{
                    $promInfo['join_num'][$i]['name'] = substr_replace($mobile['mobile'], '****', 3, 4);
                }
                $promInfo['join_num'][$i]['head_pic'] = TransformationImgurl($mobile['head_pic']);
                $promInfo['join_num'][$i]['addtime'] = $start_time['start_time'];
                $promInfo['join_num'][$i]['is_free'] = $start_time['is_free'];
                if($user_id==$join_num[$i-1]['user_id'])
                {
                    $promInfo['is_self'] = 2;
                }
            }
            $order['join_num'] = count($join_num);
            if(count($join_num)+1==$promInfo['goods_num']) {
                $promInfo['successful_time'] = $join_num[count($join_num)-1]['start_time'];
            }else{
                $promInfo['successful_time'] = null;
            }
            $order['promInfo'] = $promInfo;

            //找到order表里的详情
            $order['address'] = M('user_address')->where("`address` = '".$order['address']."' and `address_base` = '".$order['address_base']."' and `user_id` = ".$user_id)->field('consignee,address_base,address,mobile')->find();
            $order['goods'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('goods_name,original_img,store_id,market_price')->find();
            $order['goods']['original_img'] = goods_thum_images($order['goods_id'],400,400);
            $order['store'] = M('merchant')->where('`id` = '.$order['goods']['store_id'])->field('store_name,store_logo,mobile')->find();
            $order['store']['store_logo'] = TransformationImgurl($order['store']['store_logo']);

            $order_status = $this->getPromStatus($order,$promInfo,count($join_num));
            $order['annotation'] = $order_status['annotation'];
            $order['order_type'] = $order_status['order_type'];
            $key_name = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key_name,spec_key')->find();
            $spec_key = M('spec_goods_price')->where("`key`='".$key_name['spec_key']."'")->find();
            $order['key_name'] = $spec_key['key_name'];
            //猜你喜欢
            $data = $this->if_you_like($order['goodsInfo']['cat_id'],$page,$pagesize);
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('isGroup'=>array('order'=>$order,'goods'=>$data),'is_order'=>array('order'=>$order,'addreess'=>$order['address'],'goods'=>$order['goods'],'store'=>$order['store'],'like'=>$data)));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));

        }
        elseif($order['prom_id']==null)
        {
            $address = M('user_address')->where("`address` = '".$order['address']."' and `address_base` = '".$order['address_base']."' and `user_id` = ".$user_id)->field('consignee,address_base,address,mobile')->find();

            $goods = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('cat_id,goods_name,original_img,store_id,market_price')->find();
            $goods['original_img'] = goods_thum_images($order['goods_id'],200,200);
            $store = M('merchant')->where('`id` = '.$goods['store_id'])->field('store_name,store_logo,mobile')->find();
            $store['store_logo'] = TransformationImgurl($store['store_logo']);
            $key_name = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key_name')->find();
            $order['key_name'] = $key_name['spec_key_name'];
            $order_status = $this->getStatus($order);
            $order['annotation'] = $order_status['annotation'];
            $order['order_type'] = $order_status['order_type'];

            //你可能喜欢
            $data = $this->if_you_like($goods['cat_id'],$page,$pagesize);

            $order['goods_info'] = null;
            $order['prom_info'] = null;

            $json = array('status'=>1,'msg'=>'','result'=>array('isGroup'=>null,'is_order'=>array('order'=>$order,'addreess'=>$address,'goods'=>$goods,'store'=>$store,'like'=>$data)));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        else{
            $json = array('status'=>-1,'msg'=>'非法数据');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }

    /*
     * 微信端获取拼团或订单详情
     */
    public function get_WX_PromDetail(){
        $order_id = I('order_id');
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $version = I('version');
        I('invitation_num') && $invitation_num = strtolower(I('invitation_num'));//统一大小写
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(empty($user_id))
        {
            $json = array('status'=>-1,'msg'=>'非法数据');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        if($invitation_num)
        {
            $order = M('order')->where("`invitation_num`='$invitation_num'")->field('order_id,invitation_num,user_id,goods_id,total_amount,address,address_base,order_sn,add_time,pay_name,order_status,shipping_status,pay_status,prom_id,num,order_amount,shipping_name,shipping_code,shipping_order,delivery_time,automatic_time,is_return_or_exchange,the_raise,free,order_type')->find();
        }else{
            $order = M('order')->where('`order_id`='.$order_id)->field('order_id,invitation_num,user_id,goods_id,total_amount,address,address_base,order_sn,add_time,pay_name,order_status,shipping_status,shipping_order,pay_status,prom_id,num,order_amount,shipping_name,shipping_code,delivery_time,automatic_time,is_return_or_exchange,the_raise,free,order_type')->find();
        }
        $order['share_url'] = C('SHARE_URL').'/prom_regiment_app.html?order_id='.$order_id.'&user_id='.$user_id;
        $order['spec_key'] = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key')->find();

        if($order['prom_id']!=null)
        {
            $order['goodsInfo'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('goods_id,goods_name,goods_remark,original_img,prom,prom_price,cat_id,market_price,store_id')->find();
            $order['goodsInfo']['original_img'] =  goods_thum_images($order['goods_id'],400,400);

            //获取分享缩略图
            if(file_exists(CDN.'Public/upload/fenxiang/'.$order['goodsInfo']['goods_id'].'_'.$order['goodsInfo']['store_id'].'.jpg'))
            {
                $goods['fenxiang_url'] = CDN.'/Public/upload/fenxiang/'.$order['goodsInfo']['goods_id'].'_'.$order['goodsInfo']['store_id'].'.jpg';
            }else{
                $goods_pic_url = goods_thum_images($order['goodsInfo']['goods_id'],400,400);
                $this->fenxiangLOGO($goods_pic_url,$order['goodsInfo']['goods_id'],$order['goodsInfo']['store_id']);
                $order['goodsInfo']['fenxiang_url'] = $order['goodsInfo']['original_img']."/q/75|watermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nTE9HTy5qcGc=/dissolve/100/gravity/South/dx/0/dy/0|imageslim";
            }
            $store_id['id'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('store_id')->find();
            $order['goodsInfo']['store']['id']= $store_id['id']['store_id'];
            $order['rules'] = M('rules_text')->find();
            $promInfo = M('group_buy')->where('`order_id` = '.$order['order_id'])->field('id,photo,end_time,goods_num,start_time,user_id,mark,order_id,end_time,is_free')->find();

            if(!empty($promInfo['mark']))
            {
                $is_self = M('group_buy')->where('`id` = '.$promInfo['mark'].' and `user_id`='.$user_id)->field('mark')->find();
                if(!empty($is_self)){
                    $promInfo['is_self'] = null;
                }
            } elseif(empty($promInfo['mark'])) {
                $is_self = M('group_buy')->where('`order_id` = '.$order['order_id'].' and `user_id`='.$user_id)->field('mark')->find();
                if(!empty($is_self)) {
                    $promInfo['is_self'] = 1;
                }elseif(empty($is_self)){
                    $promInfo['is_self'] = null;
                }
            }
            else
            {
                $json = array('status'=>-1,'msg'=>'数据异常');
                if(!empty($ajax_get))
                    $this->getJsonp($json);
                exit(json_encode($json));
            }

            $promInfo['photo'] = TransformationImgurl($promInfo['photo']);
            $promInfo['prom'] = $order['goodsInfo']['prom'];
            if(!empty($promInfo['mark'])) {
                $join_num = M('group_buy')->where('`mark` = '.$promInfo['mark'].' and `is_pay`=1')->select();
                $parents = M('group_buy')->where('`id`='.$promInfo['mark'])->find();
                $promInfo['join_num'][0] = M('users')->where('`user_id` = '.$parents['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();
                $promInfo['join_num'][0]['is_free'] = $parents['is_free'];
                $promInfo['join_num'][0]['addtime'] = $parents['start_time'];
                $promInfo['join_num'][0]['id'] = $parents['id'];
            } elseif (empty($promInfo['mark'])) {
                $join_num = M('group_buy')->where('`mark` = '.$promInfo['id'].' and `is_pay`=1')->select();
                $promInfo['join_num'][0] = M('users')->where('`user_id` = '.$order['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();
                $promInfo['join_num'][0]['is_free'] = $promInfo['is_free'];
                $promInfo['join_num'][0]['addtime'] = $promInfo['start_time'];
                $promInfo['join_num'][0]['id'] = $promInfo['id'];
            }
            $promInfo['join_num'][0]['head_pic'] = TransformationImgurl($promInfo['join_num'][0]['head_pic']);
            if(!empty($promInfo['join_num'][0]['oauth']))
            {
                $promInfo['join_num'][0]['name'] = $promInfo['join_num'][0]['nickname'];
            }else{
                $promInfo['join_num'][0]['name'] = substr_replace($promInfo['join_num'][0]['mobile'], '****', 3, 4);
            }

            for($i=1;$i<=count($join_num);$i++)
            {
                $mobile = M('users')->where('`user_id` = '.$join_num[$i-1]['user_id'])->field('user_id,mobile,head_pic,oauth,nickname')->find();
                $start_time = M('group_buy')->where('`id`='.$join_num[$i-1]['id'])->field('start_time,is_free')->find();
                $promInfo['join_num'][$i]['user_id'] = $mobile['user_id'];
                if(!empty($mobile['oauth']))
                {
                    $promInfo['join_num'][$i]['name'] = $mobile['nickname'];
                }else{
                    $promInfo['join_num'][$i]['name'] = substr_replace($mobile['mobile'], '****', 3, 4);
                }
                $promInfo['join_num'][$i]['head_pic'] = TransformationImgurl($mobile['head_pic']);
                $promInfo['join_num'][$i]['addtime'] = $start_time['start_time'];
                $promInfo['join_num'][$i]['is_free'] = $start_time['is_free'];
                if($user_id==$join_num[$i-1]['user_id'])
                {
                    $promInfo['is_self'] = 2;
                }
            }
            $order['join_num'] = count($join_num);
            if(count($join_num)+1==$promInfo['goods_num']) {
                $promInfo['successful_time'] = $join_num[count($join_num)-1]['start_time'];
            }else{
                $promInfo['successful_time'] = null;
            }
            $order['promInfo'] = $promInfo;
            //找到order表里的详情
            $order['address'] = M('user_address')->where("`address` = '".$order['address']."' and `address_base` = '".$order['address_base']."' and `user_id` = ".$user_id)->field('consignee,address_base,address,mobile')->find();
            $order['goods'] = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('goods_name,original_img,store_id,market_price')->find();
            $order['goods']['original_img'] = goods_thum_images($order['goods_id'],400,400);
            $order['store'] = M('merchant')->where('`id` = '.$order['goods']['store_id'])->field('store_name,store_logo,mobile')->find();
            $order['store']['store_logo'] = TransformationImgurl($order['store']['store_logo']);

            $order_status = $this->getPromStatus($order,$promInfo,count($join_num));
            $order['annotation'] = $order_status['annotation'];
            $order['order_type'] = $order_status['order_type'];
            $key_name = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key_name,spec_key')->find();
            $spec_key = M('spec_goods_price')->where("key='".$key_name['spec_key']."'")->find();
            $order['key_name'] = $spec_key['key_name'];
            //猜你喜欢
            $data = $this->if_you_like($order['goodsInfo']['cat_id'],$page,$pagesize);
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('isGroup'=>array('order'=>$order,'goods'=>$data),'is_order'=>array('order'=>$order,'addreess'=>$order['address'],'goods'=>$order['goods'],'store'=>$order['store'],'like'=>$data)));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));

        }
        elseif($order['prom_id']==null)
        {
            $address = M('user_address')->where("`address` = '".$order['address']."' and `address_base` = '".$order['address_base']."' and `user_id` = ".$user_id)->field('consignee,address_base,address,mobile')->find();

            $goods = M('goods')->where('`goods_id` = '.$order['goods_id'])->field('cat_id,goods_name,original_img,store_id,market_price')->find();
            $goods['original_img'] = goods_thum_images($order['goods_id'],200,200);
            $store = M('merchant')->where('`id` = '.$goods['store_id'])->field('store_name,store_logo,mobile')->find();
            $store['store_logo'] = TransformationImgurl($store['store_logo']);
            $key_name = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key_name')->find();
            $order['key_name'] = $key_name['spec_key_name'];
            $order_status = $this->getStatus($order);
            $order['annotation'] = $order_status['annotation'];
            $order['order_type'] = $order_status['order_type'];

            //你可能喜欢
            $data = $this->if_you_like($goods['cat_id'],$page,$pagesize,$version);

            $order['goods_info'] = null;
            $order['prom_info'] = null;

            $json = array('status'=>1,'msg'=>'','result'=>array('isGroup'=>null,'is_order'=>array('order'=>$order,'addreess'=>$address,'goods'=>$goods,'store'=>$store,'like'=>$data)));
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        else{
            $json = array('status'=>-1,'msg'=>'非法数据');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }

    /*
     * 猜你喜欢 在订单详情或者团详情下面出现的猜你喜欢商品列表
     * */
    public function if_you_like($cat_id,$page,$pagesize)
    {
        $where = '`show_type`=0 and `cat_id` = '.$cat_id.' and `is_on_sale`=1 and `is_show`=1';
        $count = M('goods')->where($where)->count();
        if(empty($count)){
            $cat_arr = M('goods_category')->where('parent_id = '.M('goods_category')->where('id='.$cat_id)->getField('parent_id'))->field('id')->select();
            $ids= null;
            for($i=0;$i<count($cat_arr);$i++){
                $ids = $ids.','.$cat_arr[$i]['id'];
            }
            $ids = substr($ids,1);
            $where = '`show_type`=0 and `cat_id` in ('.$ids.') and `is_on_sale`=1 and `is_show`=1';
        }
        $data = $this->getGoodsList($where,$page,$pagesize);
        return $data;
    }
    public function get_prom_share()
    {
        $this->show();
    }

    /**
     * 用户点击取消订单的操作
     */
    public function cancelOrder(){
        $id = I('order_id');
        $user_id = I('user_id',0);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $data = $this->userLogic->cancel_order($user_id,$id);
        $json = $data;
        $returnjson = $json;
        if(!$user_id > 0 || !$id > 0){
            $json = array('status'=>-1,'msg'=>'参数有误','result'=>'');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            $returnjson = json_encode($json);
        }
        $this->order_redis_status_ref($user_id);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($returnjson));
    }

    /**
     * 发送手机注册验证码
     * http://www.tp-shop.cn/index.php?m=Api&c=User&a=send_sms_reg_code&mobile=13800138006&unique_id=123456
     */
    public function send_sms_reg_code(){
        $mobile = I('mobile');
        $unique_id = I('unique_id');
        if(!check_mobile($mobile))
            exit(json_encode(array('status'=>-1,'msg'=>'手机号码格式有误')));
        //rand(1000,9999)
        $code = 6666 ;
        $send = $this->userLogic->sms_log($mobile,$code,$unique_id);
        if($send['status'] != 1)
            exit(json_encode(array('status'=>-1,'msg'=>$send['msg'])));
        exit(json_encode(array('status'=>1,'msg'=>'验证码已发送，请注意查收')));
    }

    /**
     *  用户点击收货确认的操作
     */
    public function orderConfirm(){
        $id = I('order_id',0);
        $user_id = I('user_id',0);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(!$user_id || !$id) {
            $json = array('status' => -1, 'msg' => '参数有误', 'result' => '');
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        $data = $this->userLogic->confirm_order($user_id,$id);
        $json = $data;
        $this->order_redis_status_ref($user_id);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }


    /*
     *添加评论
     */
    public function add_comment(){

        // 晒图片
        if($_FILES[img_file][tmp_name][0])
        {
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =    $map['author'] = (1024*1024*3);// 设置附件上传大小 管理员10M  否则 3M
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './Public/upload/comment/'; // 设置附件上传根目录
            $upload->replace  =     true; // 存在同名文件是否是覆盖，默认为false
            //$upload->saveName  =   'file_'.$id; // 存在同名文件是否是覆盖，默认为false
            // 上传文件
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                exit(json_encode(array('status'=>-1,'msg'=>$upload->getError()))); //$this->error($upload->getError());
            }else{
                foreach($info as $key => $val)
                {
                    $comment_img[] = '/Public/upload/comment/'.$val['savepath'].$val['savename'];
                }
                $comment_img = serialize($comment_img); // 上传的图片文件
            }
        }

        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I('user_id'); // 用户id
        $user_info = M('users')->where("user_id = $user_id")->find();

        $add['goods_id'] = I('goods_id');
        $add['email'] = $user_info['email'];
        //$add['nick'] = $user_info['nickname'];
        $add['username'] = $user_info['nickname'];
        $add['order_id'] = I('order_id');
        $add['service_rank'] = I('service_rank');
        $add['deliver_rank'] = I('deliver_rank');
        $add['goods_rank'] = I('goods_rank');
        // $add['content'] = htmlspecialchars(I('post.content'));
        $add['content'] = I('content');
        $add['img'] = $comment_img;
        $add['add_time'] = time();
        $add['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $add['user_id'] = $user_id;

        //添加评论
        $row = $this->userLogic->add_comment($add);
        exit(json_encode($row));
    }

    /*
     * 账户资金
     */
    public function account(){

        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I('user_id'); // 用户id
        //获取账户资金记录

        $data = $this->userLogic->get_account_log($user_id,I('get.type'));
        $account_log = $data['result'];
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$account_log)));
    }

    /**
     * 获取用户的退款/售后列表数据
     */
    public function return_goods_list()
    {
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $version = I('version');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(empty($user_id)) {
            $json = array('status' => -1, 'msg' => '错误参数');
            if (!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        if (redis("return_goods_list_status".$user_id) == "1") {
            redisdelall("return_goods_list".$user_id."*");
            redisdelall("return_goods_list_status".$user_id);
        }
        $rdsname = "return_goods_list".$user_id.$page.$pagesize;
        if(empty(redis($rdsname))) {
            $conditon = 'order_type in (6,7,8,9,16) and `user_id`=' . $user_id;
            $data = $this->get_OrderList($conditon, $page, $pagesize);
            redis($rdsname, serialize($data));
        } else {
            $data = unserialize(redis($rdsname));
        }
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }


    /**
     *  售后 详情
     */
    public function return_goods_info()
    {
        $id = I('id',0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = M('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();
        $return_goods['goods_name'] = $goods['goods_name'];
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$return_goods)));
    }


    /**
     * 用户申请退换货的操作
     */
    public function return_goods()
    {
        header("Access-Control-Allow-Origin:*");
//        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I('user_id'); // 用户id
        $order_id = I('order_id');
        $type = I('type'); // 0、退货 1、换货 （退款类型）
        $gold =I('gold');//退款金额
        $reason = I('reason'); //退款原因
        $problem = I('problem');// 问题描述
        $mobile = I('mobile');//号码
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(empty($order_id)||empty($user_id) ||empty($gold)||empty($reason)||empty($problem)||empty($mobile)){
            exit(json_encode(array('status'=>-1,'msg'=>'参数不足，请补齐后再次提交')));
        }

        $image_arr = array();
        if($_FILES['picture']){
            $image_arr = $this->mobile_uploadimage();
        }
        $data['picture'] = json_encode($image_arr);

        $return_goods = M('return_goods')->where("order_id = $order_id and status in(0,1)")->find();
        if(!empty($return_goods))
        {
            exit(json_encode(array('status'=>-1,'msg'=>'已经提交过退货申请!')));
        }

        $order_sn = M('order')->where('`order_id`='.$order_id.' and `user_id`='.$user_id)->field('order_sn,goods_id,prom_id,pay_code,store_id,num,store_id,order_amount')->find();
        $data['order_id'] = $order_id;
        $data['order_sn'] = $order_sn['order_sn'];
        $data['goods_id'] = $order_sn['goods_id'];
        $data['addtime'] = time();
        $data['user_id'] = $user_id;
        $data['type'] = $type; //  0、退货 1、换货 （退款类型）
        $data['reason'] = $reason; // 问题描述
        $data['gold'] = $gold;
        $data['problem'] = $problem;
        $data['mobile'] = $mobile;
        $data['imgs'] = $data['picture'];
        $data['pay_code'] = $order_sn['pay_code'];
        $data['store_id'] = $order_sn['store_id'];
        $data['is_return'] = 0;
        if(!empty($ajax_get))
        {
            $data['is_jsapi'] = 1;
        }
        if(!empty($order_sn['prom_id']))
        {
            $data['is_prom'] = 1;
            M('group_buy')->where('`order_id`='.$order_id)->data(array('is_return_or_exchange'=>1))->save();
        }else{
            $data['is_prom'] = 0;
        }
        $res = M('return_goods')->add($data);
        $getsql = M('return_goods')->getLastSql();
        M('admin_log')->data(['admin_id'=>1,'log_info'=>'err','log_url'=>'eeee'])->add();
        if($res){
            //将状态改变
            $return['is_return_or_exchange']=1;
            if($type==0){
                //退货
                $return['order_status'] = 6;
                $return['order_type'] = 8;
                if($gold!=$order_sn['order_amount']){
                    $return['not_all'] = 1;
                }
            }elseif($type==1){
                //换货
                $return['order_status'] = 4;
                $return['order_type'] = 6;
                M('goods')->where('`goods_id`='.$order_sn['goods_id'])->setInc('store_count',$order_sn['num']);
                $spec_name = M('order_goods')->where('`order_id`='.$order_id)->field('spec_key,store_id')->find();
                M('spec_goods_price')->where("`goods_id`=$order_sn[goods_id] and `key`='$spec_name[spec_key]'")->setInc('store_count',$order_sn['num']);
                M('goods')->where('`goods_id` = '.$order_sn['goods_id'])->setDec('sales',$order_sn['num']);
                M('merchant')->where('`id`='.$order_sn['store_id'])->setDec('sales',$order_sn['num']);
            }
            M('order')->where('`order_id`='.$order_id)->data($return)->save();
            $this->order_redis_status_ref($user_id);
            $json = array('status'=>1,'msg'=>'申请成功,客服第一时间会帮你处理!');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }else{
            $json = array('status'=>-1,'msg'=>'申请失败，请检查后再申请');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }


    /**
     * 验证短信验证码
     */
    public function checkcaptcha($mobile=0,$captcha=0,$unique_id=0){
        if(I('mobile') || I('code') || I('unique_id')){
            $mobile = I('mobile');
            $code = I('code');
            $unique_id = I('unique_id');
        }
//        $res =TpCache('sms.regis_sms_enable');
        //验证captcha
        if(check_mobile($mobile)){

            if(empty($code))
                exit(json_encode(array('status'=>-1,'msg'=>'请输入验证码','result'=>'')));

            $check_code = $this->userLogic->sms_code_verify($mobile,$code,$unique_id);

            if($check_code['status'] != 1){
                return 0;
            }else{
                return 1;
            }
        }
    }

    /*
     *   获取短信验证码
     */
    public function getCode()
    {
        $this->sendSMS();
    }

    /**
     * 发送短信接口
     */
    public function sendSMS(){
        if (intval(time()) - intval(session("code")) > 60) {
            session("code", time());
            $mobile = I('mobile');
            if (!check_mobile($mobile))
                exit(json_encode(array('status' => -1, 'msg' => '手机号码格式有误')));
            if ($mobile != '15019236664' && $mobile != '15919910684') {
                $code = rand(1000, 9999);
                $alidayu = new AlidayuController();
                $result = $alidayu->sms($mobile, "code", $code, "SMS_62265047", "normal", "登录验证", "拼趣多");
                //$result = sendMessage($mobile,array($code,'5分钟'),'155220');
            } else {
                $code = 111111;
                $result = 1;
            }
            //先将短信code值存起来
            if (!empty($result)) {
                $res = M('sms_log')->add(array('mobile' => $mobile, 'add_time' => time(), 'code' => $code));
            }
        }

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(!empty($result) && !empty($res)) {
            if(!empty($ajax_get))
                $this->getJsonp(array('status'=>1,'msg'=>'验证码已发送'));
            else
                exit(json_encode(array('status'=>1,'msg'=>'验证码已发送')));
        } else {
            if(!empty($ajax_get))
                $this->getJsonp(array('status'=>-1,'msg'=>'验证码发送失败'));
            else
                exit(json_encode(array('status'=>-1,'msg'=>'验证码发送失败')));
        }
    }

    /*
     * 登录测试验证正确后获取用户信息，点单列表的信息的操作
     */
    public function confirm()
    {
        if($_REQUEST)
        {
            $mobile = I('mobile');
            $code = I('code');
            $user_id = M('users')->where(array('mobile' => $mobile))->getField('user_id');
            if (!$user_id)
            {
                $_REQUEST['reg_time'] = time();
                $_REQUEST['head_pic'] = CDN.'/Public/upload/logo/logo.jpg';
                $_REQUEST['nickname'] = $mobile;
                M('users')->data($_REQUEST)->add();
                $user_id = M('users')->where(array('mobile' => $mobile))->getField('user_id');

                $HXcall = new HxcallController();
                $username = $user_id;
                $password = md5($username.C('SIGN_KEY'));
                $nickname = $mobile;
                $res = $HXcall->hx_register($username,$password,$nickname);
            }
            session('mobile_user',$user_id);

            $r = 1;
            if($r)
            {  //对比验证码
                $res = M('sms_log')->where("mobile = '$mobile' and code = '$code'")->delete();
                if($res != 1)
                {
                    I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
                    if(!empty($ajax_get))
                        $this->getJsonp(array('status' => -1,'msg'=>'验证失败'));
                    else
                        exit(json_encode(array('status' => -1, 'msg' => '验证失败')));
                }
                $userinfo = M('users')->where(array('mobile' => $mobile))->field('user_id,pay_points,mobile,head_pic')->find();
                $userinfo['head_pic'] = TransformationImgurl($userinfo['head_pic']);
                $userinfo['name'] = substr_replace($userinfo['mobile'], '****', 3, 4);//将手机号码中间四位变成*号

                $pay_points=$userinfo['pay_points'];
                $mobile=$userinfo['mobile'];
                $head_pic=$userinfo['head_pic'];
                $name=$userinfo['name'];

                $data = $this->getCountUserOrder($user_id);

                $result= array('status' => 1, 'msg' => '验证成功','result'=>array('user_id'=>$user_id,'pay_points'=>$pay_points,'mobile'=>$mobile,'head_pic'=>$head_pic,'name'=>$name,'userdetails'=>$data));

                I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
                if(!empty($ajax_get))
                    $this->getJsonp($result);
                else
                    exit(json_encode($result));
            }else{
                I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
                if(!empty($ajax_get))
                    $this->getJsonp(array('status' => -1,'msg'=>'验证失败'));
                else
                    exit(json_encode(array('status' => -1, 'msg' => '验证失败')));
            }
        }else{
            I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
            if(!empty($ajax_get))
                $this->getJsonp(array('status'=>-1,'msg'=>'缺少必要数据'));
            else
                exit(json_encode(array('status' => -1, 'msg' => '缺少必要数据')));
        }
    }
    //刷新用户的个人中心的数据的接口
    public function getRefresh()
    {
        $user_id = I('user_id');
        $data = $this->getCountUserOrder($user_id);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status' => 1, 'msg' => '验证成功','result'=>array('userdetails'=>$data));
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    public function mobile_changepassword(){
        if(IS_POST)
        {
            $new_password = I('password');
            if(strlen($new_password) < 6)
                exit(json_encode(array('status'=>-1,'msg'=>'密码不能下于6位')));

            $res = M('users')->where("user_id='{$_SESSION['mobile_user']}'")->save(array('password' => encrypt($new_password)));
            if ($res) {
                exit(json_encode(array('status' => 1, 'msg' => '修改成功')));
            } else {
                exit(json_encode(array('status' => -1, 'msg' => '修改失败')));
            }
        }
    }

    //获取用户的订单列表getOrderList
    function getUserOrderList()
    {
        $user_id = I('user_id');
        $type = I('type',0);
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $rdsname = "getUserOrderList".$user_id.$type.$page.$pagesize;
        if (redis("getUserOrderList_status".$user_id) == 1){
            redisdelall('getUserOrderList'.$user_id);
            redisdelall($rdsname."*");
        }
        if(empty(redis($rdsname))) {//判断是否有缓存
            if ($type == 1) {
                $condition = '`the_raise` = 0 and `order_status`=8 and `user_id`=' . $user_id;
            } elseif ($type == 2) {//待发货
                $condition = '`the_raise` = 0 and `pay_status` = 1 and (`order_status` = 1 or `order_status` = 11) and `shipping_status` != 1  and `user_id` = ' . $user_id;
            } elseif ($type == 3) {//待收货
                $condition = '`the_raise` = 0 and (order_type = 3 or order_type = 15) and `user_id` = ' . $user_id;
            } elseif ($type == 4) {//待付款
                $condition = '`the_raise` = 0 and `pay_status` = 0 and (`order_status` = 1 or `order_status` = 8 ) and `is_cancel`=0 and `user_id` = ' . $user_id;
            } elseif ($type == 5) {//已完成
                $condition = '`the_raise` = 0 and `order_status`=2 and `user_id` = ' . $user_id;
            } else {
                $condition = '`the_raise` = 0 and `user_id` = ' . $user_id;
            }
            $count = M('order')->where($condition)->count();
            $all = M('order')->where($condition)->order('order_id desc')->page($page, $pagesize)->field('order_id,goods_id,order_status,shipping_status,pay_status,prom_id,order_amount,store_id,num,order_type')->select();

            for ($i = 0; $i < count($all); $i++) {
                //将规格放入数组
                $goods_spec = M('order_goods')->where('`order_id`=' . $all[$i]['order_id'])->field('spec_key_name')->find();
//                var_dump(M()->getLastSql());
                //判断是不是团购订单
                if (!empty($all[$i]['prom_id'])) {
                    $mark = M('group_buy')->where('`id` = ' . $all[$i]['prom_id'])->field('id,goods_name,end_time,store_id,end_time,goods_num,order_id,goods_id,goods_price,mark,goods_num,end_time')->find();
                    $all[$i]['goods_num'] = $mark['goods_num'];
                    $all[$i]['end_time'] = $mark['end_time'];
                    $all[$i]['goods_price'] = $mark['goods_price'];
                    $all[$i]['mark'] = $mark['mark'];

                    if ($mark['mark'] == 0) {
                        $num = M('group_buy')->where('`is_pay`=1 and `mark` = ' . $mark['id'])->count();
                        $all[$i]['type'] = 1;
                        $all[$i]['goodsInfo'] = M('goods')->where('`goods_id` = ' . $mark['goods_id'])->field('goods_name,original_img')->find();
                        $all[$i]['goodsInfo']['original_img'] = goods_thum_images($all[$i]['goods_id'], 400, 400);
                        $all[$i]['storeInfo'] = M('merchant')->where('`id` = ' . $mark['store_id'])->field('store_name,store_logo')->find();
                        $all[$i]['storeInfo']['store_logo'] = TransformationImgurl($all[$i]['storeInfo']['store_logo']);
                        $all[$i]['goods_num'] = $mark['goods_num'];

                        $order_status = $this->getPromStatus($all[$i], $mark, $num);
                        $all[$i]['annotation'] = $order_status['annotation'];
                        $all[$i]['order_type'] = $order_status['order_type'];
                    } elseif ($mark['mark'] != 0) {
                        $perant = M('group_buy')->where('`id` = ' . $all[$i]['prom_id'])->field('mark')->find();
                        $num = M('group_buy')->where('`mark` = ' . $perant['mark'] . ' and `is_pay`=1')->count();
                        $all[$i]['type'] = 0;
                        $all[$i]['goodsInfo'] = M('goods')->where('`goods_id` = ' . $all[$i]['goods_id'])->field('goods_name,original_img,shop_price')->find();
                        $all[$i]['goods_price'] = $all[$i]['goodsInfo']['shop_price'];
                        unset($all[$i]['goodsInfo']['shop_price']);
                        $all[$i]['goodsInfo']['original_img'] = goods_thum_images($all[$i]['goods_id'], 400, 400);
                        $all[$i]['storeInfo'] = M('merchant')->where('`id`=' . $mark['store_id'])->field('store_name,store_logo')->find();
                        $all[$i]['storeInfo']['store_logo'] = TransformationImgurl($all[$i]['storeInfo']['store_logo']);

                        $order_status = $this->getPromStatus($all[$i], $mark, $num);
                        $all[$i]['annotation'] = $order_status['annotation'];
                        $all[$i]['order_type'] = $order_status['order_type'];
                    }
                } elseif (empty($all[$i]['prom_id'])) {
                    $all[$i]['type'] = 2;
                    $all[$i]['goodsInfo'] = M('goods')->where('`goods_id` = ' . $all[$i]['goods_id'])->field('goods_name,original_img,shop_price')->find();
                    $all[$i]['goods_price'] = $all[$i]['goodsInfo']['shop_price'];
                    unset($all[$i]['goodsInfo']['shop_price']);
                    $all[$i]['goodsInfo']['original_img'] = goods_thum_images($all[$i]['goods_id'], 400, 400);
                    $all[$i]['storeInfo'] = M('merchant')->where('`id` = ' . $all[$i]['store_id'])->field('store_name,store_logo')->find();
                    $all[$i]['storeInfo']['store_logo'] = TransformationImgurl($all[$i]['storeInfo']['store_logo']);

                    $order_status = $this->getStatus($all[$i]);
                    $all[$i]['annotation'] = $order_status['annotation'];
                    $all[$i]['order_type'] = $order_status['order_type'];
                }
                $all[$i] = $this->FormatOrderInfo($all[$i]);
                $all[$i]['key_name'] = $goods_spec['spec_key_name'];
            }
            $all = $this->listPageData($count, $all);

            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $all);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /*
     * 获取用户收藏的商品列表
     */

    function getUserCollection()
    {
        $user_id = I('user_id');
        $page  = I('page',1);
        $pagesize = I('pagesize',20);
        $rdsname = "getUserCollection".$user_id.$page.$pagesize;
        if (redis("getUserCollection_status".$user_id) == "1"){
            redisdelall("getUserCollection".$user_id."*");
            redisdelall("getUserCollection_status".$user_id);
        }
        if (empty(redis($rdsname))) {//是否有缓存
            $goods_array = M('goods_collect')->where('`user_id` = ' . $user_id)->order('collect_id desc')->select();
            $counts = count($goods_array);
            if(!empty($goods_array)){
                $ids['g.is_special'] = array('neq',8);
                $ids['g.goods_id'] = array('IN', array_column($goods_array, 'goods_id'));
                $goods = M('goods')
                    ->alias('g')
                    ->join(" LEFT JOIN tp_goods_collect AS c ON c.goods_id = g.goods_id ")
                    ->where('g.is_show = 1 and g.is_on_sale = 1 and c.user_id=' . $user_id)
                    ->where($ids)
                    ->field('g.goods_id,g.goods_name,g.market_price,g.shop_price,g.prom,g.list_img as original_img,g.original_img as original,g.sales,g.store_count,g.prom_price,g.free,g.is_special')
                    ->order(' c.add_time desc')
                    ->page($page, $pagesize)
                    ->select();
                foreach ($goods as $k => $v){
                    $goods[$k]['original_img'] = empty($v['original_img'])?$v['original'] : $v['original_img'];
                }
                $collection = $this->listPageData($counts, $goods);
                $json = array('status' => 1, 'msg' => '获取成功', 'result' => $collection);
            }else{
                $json = array('status' => 1, 'msg' => '获取成功', 'result' => null);
            }
            redis($rdsname, serialize($json));//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     * 格式化订单列表单个详情
     * @param $order
     * @return mixed
     * author Fox
     */
    public function FormatOrderInfo($order){
        $return['order_id'] = $order['order_id'];
        $return['goods_id'] = $order['goods_id'];
        $return['num'] = $order['num'];
        $return['order_status'] = $order['order_status'];
        $return['shipping_status'] = $order['shipping_status'];
        $return['pay_status'] = $order['pay_status'];
        $return['prom_id'] = $order['prom_id'];
        $return['end_time'] = $order['end_time'];
        $return['goods_num'] = $order['goods_num'];
        $return['goods_price'] = $order['goods_price'];
        $return['mark'] = $order['mark'];
        $return['order_amount'] = $order['order_amount'];
        $return['store_id'] = $order['store_id'];
        $return['annotation'] = $order['annotation'];
        $return['order_type'] = $order['order_type'];
        $return['goodsInfo'] = $order['goodsInfo'];
        $return['storeInfo'] = $order['storeInfo'];
        $return['annotation'] = $order['annotation'];
        $return['order_type'] = $order['order_type'];

        return $return;
    }

    function Help_center()
    {
        $this->display('Details/Help_center');
    }


    public function DuiBa()
    {
        $User = new IndexController();
        $User->return_Duiba_loginurl();
    }

    //获取用户的我的拼团的订单列表
    public function getUserPromList()
    {
        $user_id = I('user_id');
        $type = I('type',0);//0全部 1拼团中 2已成团 3拼团失败
        $page = I('page',1);
        $version = I('version');
        $pagesize = I('pagesize',10);
        $rdsname = "getUserPromList".$user_id.$type.$page.$pagesize.$version;
        if (redis("getUserPromList_status".$user_id) == "1"){
            redisdelall("getUserPromList".$user_id."*");
            redisdelall("getUserPromList_status".$user_id);
        }
        if (empty(redis($rdsname))) {//判断是否有缓存
            if ($type == 1) {
                $condition = '`the_raise` = 0 and `order_status`=8 and `user_id`=' . $user_id;
            } elseif ($type == 2) {
                $condition = '`the_raise` = 0 and `order_status`=11 and `user_id`=' . $user_id;
            } elseif ($type == 3) {
                $condition = '`the_raise` = 0 and `pay_status`=1 and (`order_status`=9 or `order_status`=10) and `user_id`=' . $user_id;
            } elseif ($type == 0) {
                $condition = '`the_raise` = 0 and `prom_id`>0 and `user_id`=' . $user_id;
            } else {
                exit(json_encode(array('status' => -1, 'msg' => '参数错误')));
            }
            $data = $this->get_OrderList($condition,$page,$pagesize);
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            redis($rdsname, serialize($json));//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    //用户点击延长收货时间的接口
    public function getIncreaseGoodsTime()
    {
        $order_id = I('order_id');
        $user_id = I('user_id');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(empty($order_id) || empty($user_id))
        {
            exit(json_encode(array('status'=>-1,'msg'=>'参数不齐')));
        }

        $order = M('order')->where('`order_id`='.$order_id.' and `user_id`='.$user_id)->field('automatic_time,is_automatic_time')->find();
        if(empty($order))
        {
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在')));
        }
        if($order['is_automatic_time']==1)
        {
            exit(json_encode(array('status'=>-1,'msg'=>'该订单已延时')));
        }

        $data['automatic_time'] = $order['automatic_time']+C('automatic_time');
        $data['is_automatic_time'] = 1;

        $res=M('order')->where('`order_id`='.$order_id.' and `user_id`='.$user_id)->data($data)->save();
        if($res)
        {
            I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
            $json = array('status'=>1,'msg'=>'延时成功');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
        else{
            $json = array('status'=>-1,'msg'=>'延时失败');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }

    //获取退款订单钱款去向的数据接口
    public function getWhere_Is_The_Money()
    {
        //0申请中1等待批准2等待到账3完成
        $order_id = I('order_id');

        $return_order = M('return_goods')->where('`order_id`='.$order_id)->find();
        if(empty($return_order))
        {
            exit(json_encode(array('status'=>1,'msg'=>'订单不存在','result'=>'')));
        }

        $pay_code = M('order')->where('`order_id`='.$order_id)->field('pay_code')->find();

        if($pay_code['pay_code']=='weixin')
        {
            $pay_name = '微信支付';
        } else {
            $pay_name = '支付宝支付';
        }

        $data['gold']= $return_order['gold'];
        $data['way'] = $pay_name.'用户的零钱';
        if($return_order['status']==0)
        {
            if($return_order['is_return']==1){
                $data['one']['title'] = '未成团，即将为您退款';
            }else{
                $data['one']['title'] = '等待平台同意退款';
            }
            $data['one']['text'] = null;
            $data['one']['time'] = $return_order['addtime'];
        } elseif($return_order['status']>=1) {
            if($return_order['is_return']==1){
                $data['one']['title'] = '平台准备退款';
            }else{
                $data['one']['title'] = '平台同意退款';
            }
            $data['one']['title'] = '平台同意退款';
            $data['one']['text'] = null;
            $data['one']['time'] = $return_order['one_time'];
        }
        if($return_order['status']>=2)
        {
            $data['two']['title'] = $pay_name.'受理';
            $data['two']['text'] = '退款有一定延迟，用零钱支付的退款20分钟内到账，请耐心等候';
            $data['two']['time'] = $return_order['two_time'];
        }
        if($return_order['status']==3)
        {
            $data['ok']['title'] = '退款成功';
            $data['ok']['text'] = null;
            $data['ok']['time'] = $return_order['ok_time'];
        }

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
        //获取用户被免单的订单列表
    public function getUserFreeOrder()
    {
        $conditions['user_id'] = I('user_id');
        $conditions['is_free'] = 1;
        $conditions['is_pay'] = 1;
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $data = $this->get_OrderList($conditions,$page,$pagesize);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
        //将最新的开团在首页左上角推送的接口
    public function TuiSong()
    {
        I('user_id') && $user_id = I('user_id');
        $version = I('version');
        $rdsname = "TuiSong".$user_id.$version;
        if(empty(redis($rdsname))) {//
            if (empty($user_id)) {
                //开团十分钟以内的团
                $new_prom = M('group_buy')->where('`is_raise` = 0 and `auto`=0 and `mark`=0 and `is_pay`=1 and `is_successful`=0 and ' . (time() - 60000) . '<=`start_time`')->order('start_time desc')->field("id as prom_id,user_id")->limit('0,20')->select();
            } else {
                $new_prom = M('group_buy')->where('`is_raise` = 0 and `mark`=0 and `is_pay`=1 and `is_successful`=0 and `user_id`!=' . $user_id . ' and ' . (time() - 60000) . '<=`start_time`')->order('start_time desc')->field("id as prom_id,user_id")->limit('0,10')->select();
            }
            for ($i = 0; $i < count($new_prom); $i++) {
                $new_prom[$i]['userInfo'] = M('users')->where('`user_id`=' . $new_prom[$i]['user_id'])->field('mobile,nickname,oauth,head_pic')->find();
                if (empty($new_prom[$i]['userInfo']['oauth'])) {
                    $new_prom[$i]['userInfo']['name'] = substr_replace($new_prom[$i]['userInfo']['mobile'], '****', 3, 4);
                } else {
                    $new_prom[$i]['userInfo']['name'] = $new_prom[$i]['userInfo']['nickname'];
                }
                unset($new_prom[$i]['userInfo']['nickname']);
                unset($new_prom[$i]['userInfo']['mobile']);
                unset($new_prom[$i]['userInfo']['oauth']);
                unset($new_prom[$i]['user_id']);
                $new_prom[$i]['userInfo']['head_pic'] = TransformationImgurl($new_prom[$i]['userInfo']['head_pic']);
            }

            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $new_prom);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    //获取用户的为我点赞订单列表
    public function getRaise()
    {
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $where = '`pay_status`=1 and `the_raise`=1 and `user_id`='.$user_id;
        $data = $this->get_OrderList($where,$page,$pagesize);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));

    }

    public function get_user_agreement()
    {
        $this->show();
    }

    public function getUserMoney()//获取用户免单的钱款去向
    {
        $order_id = I('order_id');

        $money = M('getwhere')->where('`order_id`='.$order_id)->find();
        if(empty($money))
        {
            exit(json_encode(array('status'=>-1,'msg'=>'该订单不存在')));
        }

        if($money['code']=='weixin')
        {
            $pay_name = '微信支付';
        } else {
            $pay_name = '支付宝支付';
        }

        $data['gold']= $money['price'];
        $data['way'] = $pay_name.'用户的零钱';
        if($money['add_time']!=null && $money['one_time']==null)
        {
            $data['one']['title'] = '等待平台同意退款';
            $data['one']['text'] = null;
            $data['one']['time'] = $money['add_time'];
        } elseif($money['one_time']!=null) {
            $data['one']['title'] = '平台同意退款';
            $data['one']['text'] = null;
            $data['one']['time'] = $money['one_time'];
        }
        if($money['two_time']!=null)
        {
            $data['two']['title'] = $pay_name.'受理';
            $data['two']['text'] = '退款有一定延迟，用零钱支付的退款20分钟内到账，请耐心等候';
            $data['two']['time'] = $money['two_time'];
        }
        if($money['ok_time']!=null)
        {
            $data['ok']['title'] = '退款成功';
            $data['ok']['text'] = null;
            $data['ok']['time'] = $money['ok_time'];
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }


    public function getPromid($prom)//处理团id的操作
    {
        $id = "";
        foreach ($prom as $v) {
            $id .= $v['id'] . ",";
        }
        $id = substr($id, 0, -1);
        $result = "(id in ($id) or mark in ($id)) and is_pay=1";
        return $result;
    }

    public function ReturnSQL($array)//处理数组数据，分类拼接后返回
    {
        $id = null;
        $order = null;
        $num = count($array);
        for($i=0;$i<$num;$i++)
        {
            $num2 = count($array[$i]);
            for($j=0;$j<count($array[$i]);$j++)
            {
                if($num2==1){
                    $id = $id.$array[$i]['id'][$j];
                    $order = $order.$array[$i]['order_id'][$j];
                }elseif($i==$num2-1) {
                    $id = $id.$array[$i]['id'][$j];
                    $order = $order.$array[$i]['order_id'][$j];
                }elseif($i==0){
                    $id = $id.$array[$i]['id'][$j];
                    $order = $order.$array[$i]['order_id'][$j];
                }else{
                    $id = $id.$array[$i]['id'][$j];
                    $order = $order.$array[$i]['order_id'][$j];
                }
            }
        }
        $id = substr($id,0,strlen($id)-1);
        $order = substr($order,0,strlen($order)-1);
        $id = $this->str_prefix($id,1,'(');
        $id = $this->str_suffix($id,1,')');
        $order = $this->str_prefix($order,1,'(');
        $order = $this->str_suffix($order,1,')');
        return array('id'=>$id,'order_id'=>$order);
    }

    /*
     * 在字符串的前面添加元素
     * $str ： 操作的字符串
     * $n ：添加的个数
     * $char ： 要添加的元素
     * */
    function str_prefix($str, $n=1, $char=" "){
        for ($x=0;$x<$n;$x++){$str = $char.$str;}
        return $str;
    }

    /*
     * 在字符串的最后面添加元素
     * $str ： 操作的字符串
     * $n ：添加的个数
     * $char ： 要添加的元素
     * */
    function str_suffix($str, $n=1, $char=" "){
        for ($x=0;$x<$n;$x++){$str = $str.$char;}
        return $str;
    }
    //执行退款操作的方法
    public function BackPay($order)
    {
        $orderLogic = new OrderLogic();
        $num = count($order);
        for($i=0;$i<$num;$i++) {
            $custom = array('type' => '3','id'=>$order[$i]['order_id']);
            $user_id = $order[$i]['user_id'];
            $this->order_redis_status_ref($order[$i]['user_id']);
            SendXinge('抱歉您的拼团未成功，请重新开团',"$user_id",$custom);
            if($order[$i]['the_raise']==0) {
                if ($order[$i]['pay_code'] == 'weixin') {
                    if ($order[$i]['is_jsapi'] == 1) {
                        $result = $orderLogic->weixinJsBackPay($order[$i]['order_sn'], $order[$i]['order_amount']);
                    } else {
                        $result = $orderLogic->weixinBackPay($order[$i]['order_sn'], $order[$i]['order_amount']);
                    }
                    if ($result['status'] == 1) {
                        $data['order_status'] = 10;
                        $data['order_type'] = 13;
                        M('order')->where('`order_id`=' . $order[$i]['order_id'])->data($data)->save();
                        $this->fallback($order[$i]);
                    }
                } elseif ($order[$i]['pay_code'] == 'alipay') {
                    $result = $orderLogic->alipayBackPay($order[$i]['order_sn'], $order[$i]['order_amount']);
                    if ($result['status'] == 1) {
                        $data['order_status'] = 10;
                        $data['order_type'] = 13;
                        M('order')->where('`order_id`=' . $order[$i]['order_id'])->data($data)->save();
                        $this->fallback($order[$i]);
                    }
                } elseif ($order[$i]['pay_code'] == 'qpay') {
                    // Begin code by lcy
                    $qqPay = new QQPayController();
                    $result = $qqPay->doRefund($order[$i]['order_sn'], $order[$i]['order_amount']);
                    if ($result['status'] == 1) {
                        $data['order_status'] = 10;
                        $data['order_type'] = 13;
                        M('order')->where('`order_id`=' . $order[$i]['order_id'])->data($data)->save();
                        $this->fallback($order[$i]);
                    }
                }
            }else{
                $data['order_status'] = 10;
                $data['order_type'] = 13;
                M('order')->where('`order_id`=' . $order[$i]['order_id'])->data($data)->save();
            }
                $res = M('return_goods')->where('order_id='.$order[$i]['order_id'])->find();
                if(empty($res)){
                    $e =  M('return_goods')->add(array('order_id'=>$order[$i]['order_id'],'order_sn'=>$order[$i]['order_sn'],'goods_id'=>$order[$i]['goods_id'],'store_id'=>$order[$i]['store_id'],'gold'=>$order[$i]['order_amount'],'status'=>3,'is_prom'=>1,'reason'=>'未成团退款','is_return'=>1,'pay_code'=>$order[$i]['pay_code'],'addtime'=>time(),'user_id'=>$order[$i]['user_id'],'one_time'=>time(),'two_time'=>time(),'ok_time'=>time(),'is_return'=>1));
                }else{
                    $e = M('return_goods')->where('order_id='.$order[$i]['order_id'])->save(array('order_id'=>$order[$i]['order_id'],'order_sn'=>$order[$i]['order_sn'],'goods_id'=>$order[$i]['goods_id'],'store_id'=>$order[$i]['store_id'],'gold'=>$order[$i]['order_amount'],'status'=>3,'is_prom'=>1,'reason'=>'未成团退款','is_return'=>1,'pay_code'=>$order[$i]['pay_code'],'addtime'=>time(),'user_id'=>$order[$i]['user_id'],'one_time'=>time(),'two_time'=>time(),'ok_time'=>time(),'is_return'=>1));
                }
        }
    }
    //将商品相关的库存，销量，使用的优惠券还原成原来的样子
    public function fallback($orders)
    {
        $group_buy = M('group_buy')->where("order_id={$orders['order_id']}")->field('is_raise')->find();
        if($group_buy['is_raise'] != 1) {
            //商品销量减去订单中的数量
            M('goods')->where('`goods_id`=' . $orders['goods_id'])->setDec('sales', $orders['num']);
            //门店总销量减去订单中的数量
            M('merchant')->where('`id`=' . $orders['store_id'])->setDec('sales', $orders['num']);
            //规格库存回复到原来的样子
            $spec_name = M('order_goods')->where('`order_id`=' . $orders['order_id'])->field('spec_key')->find();
            M('spec_goods_price')->where('`goods_id`=' . $orders['goods_id'] . " and `key`='" . $spec_name['spec_key'] . "'")->setInc('store_count', $orders['num']);
            //优惠卷回到原来的数量
            if ($orders['coupon_id'] != 0) {
                M('coupon')->where('`id`=' . $orders['coupon_id'])->setDec('use_num');
                //把优惠卷还给用户
                $data['use_time'] = 0;
                $data['is_use'] = 0;
                $data['order_id'] = 0;
                M('coupon_list')->where('`id`=' . $orders['coupon_list_id'])->data($data)->save();
            }
        }
    }
    //获取订单支付状态
    function getPayState()
    {
        $order = I('order_id');
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(empty($order))
        {
            $json = array('status'=>-1,'msg'=>'获取失败','result'=>'参数缺失');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }

        $order_info = M('order')->where('order_id = '.$order)->find();

        if($order_info['order_type']==1 || $order_info['order_type']==10)
        {
            $json = array('status'=>-1,'msg'=>'获取失败','result'=>'尚未支付');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }else{
            $json = array('status'=>1,'msg'=>'获取成功','result'=>'已支付');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }
    }

    public function doRefund($orderSn, $refundFee, $opUserPassMd5 = '', $transactionId = '')
    {
        $refundSn = $orderSn . time();

        list ($code, $refundResult) = $this->refund($orderSn, $refundSn, $refundFee*100);
        if ($code != 0) {
            return array(
                'status' => 0,
                'msg'    => '失败：'. $refundResult . "<br/>"
            );
        } else {
            $msg = "业务结果：".$refundResult['result_code']."<br>";
            $msg .= "错误代码：".$refundResult['err_code']."<br>";
            $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：".$refundResult['appid']."<br>";
            $msg .= "商户号：".$refundResult['mch_id']."<br>";

            $msg .= "签名：".$refundResult['sign']."<br>";
            $msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
            $msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
            $msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
            $msg .= "微信退款单号：".$refundResult['refund_id']."<br>";
            $msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
            $msg .= "退款金额：".$refundResult['refund_fee']."<br>";

            return array(
                'status'    => 1,
                'msg'       => $msg,
                'out_refund_no' => $refundSn
            );
        }
    }

    /*
     * 版本：2.0.0
     * 获取用户的订单列表
     * */
    function getOrderList(){
        I('user_id') && $user_id = I('user_id');
        $type = I('type',0);//0.全部 1.拼团中 2.待发货 3.待收货 4.待付款 5.已完成
        $page = I('page',1);
        $pagesize = I('pagesiaze',20);
        $rdsname = "getOrderList_".$user_id.$type.$page.$pagesize;
        if (redis("getOrderList_status_".$user_id) == "1"){
            redisdelall("getOrderList_".$user_id."*");//删除旧缓存
            redisdelall("getOrderList_status_".$user_id);//删除状态
        }

        if(empty(redis($rdsname))) {//判断是否有缓存
        if ($type == 1) {
            $condition = '`the_raise` = 0 and (order_type = 11 or order_type = 10) and `user_id`=' . $user_id;
        } elseif ($type == 2) {//待发货
            $condition = '`the_raise` = 0 and (order_type = 2 or order_type = 14) and `user_id` = ' . $user_id;
        } elseif ($type == 3) {//待收货
            $condition = '`the_raise` = 0 and (order_type = 3 or order_type = 15) and `user_id` = ' . $user_id;
        } elseif ($type == 4) {//待付款
            $condition = '`the_raise` = 0 and (order_type = 1 or order_type = 10) and `user_id` = ' . $user_id;
        } elseif ($type == 5) {//已完成
            $condition = '`the_raise` = 0 and order_type = 4 and `user_id` = ' . $user_id;
        } else {
            $condition = '`the_raise` = 0 and `user_id` = ' . $user_id;
        }
        $all = $this->get_OrderList($condition,$page,$pagesize);
        $json = array('status' => 1, 'msg' => '获取成功', 'result' => $all);
            redis($rdsname, serialize($json));//写入缓存
        }else{
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    //获取团的团详情
        function get_Detaile_for_Prom()
    {
        $prom_id = I('prom_id');
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        I('invitation_num') && $invitation_num = strtolower(I('invitation_num'));//统一大小写
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $prom = M('group_buy')->where('id ='.$prom_id)->field('goods_num,free,auto,goods_id')->find();
        if($invitation_num){
            $order = M('order')->alias('o')
                ->join('INNER JOIN tp_order_goods og on o.order_id = og.order_id ')
                ->join('INNER JOIN tp_spec_goods_price sgp on sgp.`key` = og.`spec_key`')
                ->where("o.`invitation_num`='$invitation_num'")
                ->field('o.order_id,o.user_id,o.goods_id,o.invitation_num,sgp.prom_price,o.free')
                ->find();
        }else{
            if($prom['auto']==0){
                $order = M('order')->alias('o')
                    ->join('INNER JOIN tp_order_goods og on o.order_id = og.order_id ')
                    ->where('o.`prom_id`='.$prom_id)
                    ->field('o.order_id,o.user_id,o.goods_id,o.invitation_num,og.spec_key,o.free')
                    ->find();
            }else{
                $order = M('group_buy')
                    ->where('`id`='.$prom_id)
                    ->field('user_id,goods_id')
                    ->find();
            }
        }
        $goodsInfo = $this->getGoodsInfo($order['goods_id'],1);
        if($prom['auto']==0){
            $spec_price = M('spec_goods_price')->where('goods_id='.$order['goods_id']." and `key` = '".$order['spec_key']."'")->getField('prom_price');
            if($order['free']>0){
                $price = (string)($spec_price*$prom['goods_num'])/($prom['goods_num']-$prom['free']);
                $c = getFloatLength($price);
                if($c>=3){
                    (string)$price = operationPrice($price);
                }
            }else{
                (string)$price=(string)$spec_price;
            }
        }else{
            (string)$price = $goodsInfo['prom_price'];
        }

        //提供保障
        $security = array(array('type'=>'全场包邮','desc'=>'所有商品均无条件包邮'),array('type'=>'7天退换','desc'=>'商家承诺7天无理由退换货'),array('type'=>'48小时发货','desc'=>'成团后，商家将在48小时内发货'),array('type'=>'假一赔十','desc'=>'若收到的商品是假货，可获得加倍赔偿'));

        $goodsInfo['security'] =$security;
        //判断进来的是团长还是团员
        $res = M('group_buy')->where('(id='.$prom_id.' or mark = '.$prom_id.') and is_pay = 1' )->order('id asc')->select();
        if($res[0]['mark']!=0){
            $res = M('group_buy')->where('(id='.$res[0]['mark'].' or mark = '.$res[0]['mark'].') and is_pay = 1')->order('id asc')->select();
        }
        //循环对比进来的人在团里是什么身份
        $not = 0;
        for ($i=0;$i<count($res);$i++){
            if($i==0 && $user_id==$res[0]['user_id']){
                $not = 1;break;
            }
            if ($user_id==$res[$i]['user_id']){
                $not = 2;
            }
        }
        $share_url = C('SHARE_URL').'/prom_regiment.html?user_id='.$user_id.'&prom_id='.$prom_id;
        //将团员的信息补全
        $num = count($res);
        for($i=0;$i<$num;$i++){
            $join_num[$i] = $this->getUserInfo($res[$i]['user_id'],$res[$i]);
        }
        $data['join_num'] = $join_num;
        $data['not'] = $not;
        //判断团的状态
        if($res[0]['goods_num']==count($join_num)){
            $data['is_successful'] = 1;
            $data['successful_time'] = $res[count($join_num)-1]['start_time'];
        }else{
            $data['is_successful'] = $data['successful_time'] = 0;
        }

        //猜你喜欢
        $goods_like = $this->if_you_like($goodsInfo['cat_id'],$page,$pagesize);
        $json = array('status'=>1,'msg'=>'获取成功','result'=>array('goods'=>$goodsInfo,'prom_id'=>$prom_id,'end_time'=>$res[0]['end_time'],'invitation_num'=>$order['invitation_num'],'share_url'=>$share_url,'start_time'=>$res[0]['start_time'],'prom'=>$res[0]['goods_num'],'free'=>$res[0]['free'],'price'=>$price,'is_successful'=>$data['is_successful'],'successful_time'=>$data['successful_time'],'not'=>$data['not'],'join_num'=>$data['join_num'],'like'=>$goods_like));

        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    //获取用户详情
    function getUserInfo($user_id,$prom_order){
        $user = M('users')->where('user_id = '.$user_id)->field('nickname,mobile,oauth,head_pic')->find();
        if(!empty($user['oauth'])){
            $info['name'] = $user['nickname'];
        }else{
            $info['name'] =  substr_replace($user['mobile'], '****', 3, 4);
        }
        $info['head_pic'] = TransformationImgurl($user['head_pic']);
        $info['addtime'] = $prom_order['start_time'];
        $info['is_free'] = $prom_order['is_free'];

        return $info;
    }
    //获取用户的订单详情
    function get_Detaile_for_Order(){
        $order_id = I('order_id');
        $user_id = I('user_id');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        //查询订单
        $order_info = M('order')->alias('o')
            ->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id ')
            ->where('o.order_id = '.$order_id.' and o.user_id = '.$user_id)
            ->field('o.goods_id,o.store_id,o.order_sn,o.pay_name,o.add_time,o.consignee,o.address_base,o.address,o.mobile,o.store_id,o.shipping_order,o.shipping_name,g.cat_id,o.order_amount,o.num,o.prom_id,o.order_type,o.order_status,o.pay_status,o.shipping_status,o.automatic_time,o.delivery_time')->find();
        if($order_info['prom_id']!=0){
            $prom_info = M('group_buy')->where('order_id = '.$order_id)->find();
            //获取成团时间
            if ($prom_info['mark']==0){
                $res1 = M('group_buy')->where('id = '.$prom_info['id'].' or mark ='.$prom_info['id'])->order('id desc')->find();
            }else{
                $res1 = M('group_buy')->where('id = '.$prom_info['mark'].' or mark ='.$prom_info['mark'])->order('id desc')->find();
            }
        }
        //获取商品详情
        $goods_info = M('goods')->where(" `goods_id` = ".$order_info['goods_id'])->field('goods_id,goods_name,prom_price,shop_price,store_id,sales,is_support_buy,is_special,original_img')->find();
        $goods_info['store'] = M('merchant')->where(' `id` = ' . $order_info['store_id'])->field('id,store_name,store_logo,sales,mobile')->find();
        //获取规格详情
        $spec_info = M('order_goods')->alias('og')
            ->join('INNER JOIN tp_spec_goods_price sgp on sgp.`key` = og.`spec_key` ')
            ->where('order_id = '.$order_id)
            ->field('og.goods_name,sgp.key_name,sgp.price,sgp.prom_price')
            ->find();
        $goods_info['goods_name'] = $spec_info['goods_name'];
        //判断订单是否为团购或者代买订单
        if(!empty($order_info['prom_id'])){
            //判断是用户在团里面的身份
            if($prom_info['mark']==0){
                $prom = M('group_buy')->where('mark = '.$order_info['prom_id'].' and is_pay = 1')->select();
                $order_info['prom'] = $prom[0]['goods_num'];
                $mens = M('group_buy')->where('`mark` = ' . $order_info['prom_id'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();
                $order_info['prom_mens'] = $prom_info['goods_num'] - $mens - 1;
                $is_oneself = 1;
            }else{
                $prom = M('group_buy')->where('mark = '.$prom_info['mark'].' and is_pay = 1')->select();
                $order_info['prom'] = $prom[0]['goods_num'];
                $mens = M('group_buy')->where('`mark` = ' . $prom_info['mark'] . ' and `is_pay`=1 and `is_return_or_exchange`=0')->count();
                $order_info['prom_mens'] = $prom_info['goods_num']-$mens-1;
                $is_oneself = 2;
            }
            if($order_info['prom_mens']!=0){
                $res1['start_time'] = null;
            }
            $res = $this->getPromStatus($order_info,$prom_info,count($prom));//给对应的order_type 的值
            $order_info['annotation'] = $res['annotation'];
            $order_info['key_name'] = $spec_info['key_name'];
            $order_info['price'] = $spec_info['prom_price'];
        }else{
            $res = $this->getStatus($order_info);//给对应的order_type的值
            $order_info['annotation'] = $res['annotation'];
            $order_info['key_name'] = $spec_info['key_name'];
            $is_oneself = 0;
        }
        $is_self = $is_oneself;
        unset($order_info['order_status']);
        unset($order_info['pay_status']);
        unset($order_info['shipping_status']);
        $goods = $this->if_you_like($order_info['cat_id'],$page,$pagesize);//猜你喜欢商品列表

        $user['consignee'] = $order_info['consignee'];
        $user['address_base'] = $order_info['address_base'];
        $user['address'] = $order_info['address'];
        $user['mobile'] = $order_info['mobile'];
        unset($order_info['consignee']);
        unset($order_info['address_base']);
        unset($order_info['address']);
        unset($order_info['mobile']);
        unset($order_info['goods_num']);
        unset($order_info['cat_id']);
        unset($order_info['goods_id']);

        $json = array('status'=>1,'msg'=>'获取成功','result'=>array('order_id'=>$order_id,'order_amount'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'pay_name'=>$order_info['pay_name'],'add_time'=>$order_info['add_time'],'shipping_order'=>$order_info['shipping_order'],'shipping_name'=>$order_info['shipping_name'],'prom_id'=>$order_info['prom_id'],'order_type'=>$order_info['order_type'],'automatic_time'=>$order_info['automatic_time'],'prom'=>$order_info['prom'],'prom_mens'=>$order_info['prom_mens'],'annotation'=>$order_info['annotation'],'delivery_time'=>$order_info['delivery_time'],'key_name'=>$order_info['key_name'],'price'=>$order_info['price'],'num'=>$order_info['num'],'successful_time'=>$res1['start_time'],'is_self'=>$is_self,'is_oneself'=>$is_oneself,'goodsInfo'=>$goods_info,'user'=>$user,'like'=>$goods));

        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    //团详情页面怎么增加首页入口
    function  getentrance(){
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $category[0]['id'] = '1';
        $category[0]['cat_name'] = '免单拼';
        $category[0]['cat_img'] = 'http://cdn.pinquduo.cn/Public/upload/index/miandan1.jpg';
        $category[0]['type'] = '2';
        $category[0]['is_red'] = '0';

        $category[1]['id'] = '2';
        $category[1]['cat_name'] = '9.9专区';
        $category[1]['cat_img'] = 'http://cdn.pinquduo.cn/Public/upload/index/99.jpg';
        $category[1]['type'] = '6';
        $category[1]['is_red'] = '0';

        $category[2]['id'] = '3';
        $category[2]['cat_name'] = '限时秒杀';
        $category[2]['cat_img'] = 'http://cdn.pinquduo.cn/Public/upload/index/xianshi1.jpg';
        $category[2]['type'] = '7';
        $category[2]['is_red'] = '0';

        $category[3]['id'] = '4';
        $category[3]['cat_name'] = '省钱宝典';
        $category[3]['cat_img'] = 'http://cdn.pinquduo.cn/Public/upload/index/8-shenqian.gif';
        $category[3]['type'] = '10';
        $category[3]['is_red'] = '0';

        $category[4]['id'] = '5';
        $category[4]['cat_name'] = '趣多严选';
        $category[4]['cat_img'] = 'http://cdn.pinquduo.cn/Public/upload/index/quduoyanxuan.jpg';
        $category[4]['type'] = '4';
        $category[4]['is_red'] = '0';

        $json = array('status' => 1, 'msg' => '获取成功', 'result' => $category);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
}
