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
    public function getStoreInfo()
    {
        header("Access-Control-Allow-Origin:*");
        $store_id = I('store_id');
        if(empty($store_id)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户id不能为空')));
        }
        $store_info = M('merchant')->where("id = {$store_id}")->find();
        if(empty($store_info)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户不存在')));
        }else{
            $username = 'store'.$store_info['id'];
            $password = md5($username);
            exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_name'=>$store_info['store_name'],'store_password'=>$password))));
        }
    }
}