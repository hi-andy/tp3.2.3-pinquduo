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
        $username = 'store'.$_SESSION['merchant_id'];
        $password = md5($username.C('SIGN_KEY'));
        $nickname = $_SESSION['merchant_name'];
        $res = $HXcall->hx_register($username,$password,$nickname);
        $store_logo = M('merchant')->where(array('id'=>$_SESSION['merchant_id']))->getField('store_logo');

        $this->assign('store_logo',$store_logo);
        $this->assign('username',$username);
        $this->assign('password',$password);
        $this->display();
    }
}