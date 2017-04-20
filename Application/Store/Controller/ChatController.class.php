<?php
/**
 *
 */
namespace Store\Controller;
use Api\Controller\HxcallController;

class ChatController extends BaseController{

    /**
     * 客服聊天主界面
     */
    public function index()
    {
        $HXcall = new HxcallController();
        $username = 'store'.$_COOKIE['merchant_id'];
        $password = md5($username.C('SIGN_KEY'));
        $nickname = $_COOKIE['merchant_name'];
        $res = $HXcall->hx_register($username,$password,$nickname);
        $store_logo = M('merchant')->where(array('id'=>$_COOKIE['merchant_id']))->getField('store_logo');

        $this->assign('store_logo',C('SERVER_HTTP').$store_logo);
        $this->assign('username',$username);
        $this->assign('password',$password);
        $this->display();
    }
}