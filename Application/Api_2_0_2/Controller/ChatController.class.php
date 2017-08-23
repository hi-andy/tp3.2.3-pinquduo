<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/22
 * Time: 下午3:45
 */

namespace Api_2_0_2\Controller;


class ChatController
{
    public function change()
    {
        $store_id = I('store_id');
        if(empty($store_id)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户id不能为空')));
        }

        $store_info = M('merchant')->where("id = {$store_id}")->find();
        if(empty($store_info)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户不存在')));
        }else{
            $HXcall = new HxcallController();
            $username = 'store'.$store_info['id'];
            $password = md5($username);
            $nickname = $_SESSION['merchant_name'];
            $res = $HXcall->hx_register($username,$password,$nickname);
            exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_id'=>$store_info['id'],'store_logo'=>$store_info['store_logo'],'store_name'=>$store_info['store_name']))));
        }
    }

}