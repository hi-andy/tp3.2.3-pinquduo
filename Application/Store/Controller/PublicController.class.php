<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/7/4
 * Time: 11:22
 */

namespace Store\Controller;

use Api\Controller\HxcallController;
use Think\Verify;

class PublicController extends BaseController {

	function footre(){
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