<?php
/**
 *
 */
namespace Admin\Controller;
use Api\Controller\HxcallController;

class ChatController extends BaseController{

    /**
     * 客服聊天主界面
     */
    public function index()
    {
        $HXcall = new HxcallController();
        $username = 'admin'.$_SESSION['admin_info']['admin_id'];
        $password = md5($username.C('SIGN_KEY'));
        $nickname = $_SESSION['admin_info']['user_name'];
        $res = $HXcall->hx_register($username,$password,$nickname);

        $this->assign('username',$username);
        $this->assign('password',$password);
        $this->display();
    }
}