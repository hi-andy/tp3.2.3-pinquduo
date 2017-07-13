<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/7/11
 * Time: 11:31
 */

namespace Storeapp\Controller;

use Api_2_0_0\Controller\AlidayuController;

class IndexController {

	/**
	 * nature：商家端APP登录接口
	 * author：吴银海
	 * time：17/07/11
	 * $store_name 用户账号
	 * $store_pass 用户密码
	 */
	function login(){
		$store_name = I('store_name');
		$store_pass = I('store_pass');//直接给MD5

		if(empty($store_name) && empty($store_pass)){
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号或密码不能为空 ^_^')));
		}

		$store_info = M('merchant')->where("merchant_name = '$store_name' and password = '$store_pass' ")->find();
		if(!empty($store_info)){
			exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_id'=>$store_info['id'],'store_logo'=>$store_info['store_logo'],'store_name'=>$store_info['store_name']))));
		}else{
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号不存在 ^_^')));
		}
	}

	/**
	 * nature：忘记密码
	 * author：吴银海
	 * time：17/07/11
	 * $store_id 商户id
	 * $new_pass_word1 新密码1
	 * $new_pass_word2 新密码2
	 */
	function forget (){
		$store_id = I('store_id');
		$new_pass_word1 = trim(I('new_pass_word1'));
		$new_pass_word2 = trim(I('new_pass_word2'));

		if(empty($store_id) || empty($new_pass_word1) || empty($new_pass_word2)){
			exit(json_encode(array('status'=>-1,'msg'=>'请确认正确填写信息后提交 ^_^')));
		}
		if($new_pass_word1!=$new_pass_word2){
			exit(json_encode(array('status'=>-1,'msg'=>'两次输入的密码不相同 ^_^')));
		}
		if(strlen($new_pass_word2)>18 || strlen($new_pass_word2)<6){
			exit(json_encode(array('status'=>-1,'msg'=>'密码长度不符合要求 ^_^')));
		}
		$Store = M('merchant');
		$store_info = $Store->where("id = '$store_id'")->find();
		if(!empty($store_info)){
			$res = $Store->data(array('password'=>$new_pass_word2))->sava();
			if($res){
				exit(json_encode(array('status'=>1,'msg'=>'重置成功 ^_^')));
			}else{
				exit(json_encode(array('status'=>-1,'msg'=>'重置失败 ^_^')));
			}
		}else{
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号不存在 ^_^')));
		}
	}

	/*
	 * nature：获取验证
	 * author：吴银海
	 * time：17/07/12
	 * $store_name 用户账号
	 * */
	function sendSMS(){
		$store_name = I('store_mobile');

		if (!check_mobile($store_name)){
			exit(json_encode(array('status' => -1, 'msg' => '手机号码格式有误')));
		}
		$store_info = M('merchant')->where("merchant_name = '$store_name'")->find();

		if($store_info['state']==0){
			exit(json_encode(array('status' => -1, 'msg' => '您输入的帐号不正确')));
		}
		
		$code = rand(100000, 999999);
		$alidayu = new AlidayuController();
		$result = $alidayu->sms($store_name, "code", $code, "SMS_62265043", "normal", "拼趣多修改验证", "拼趣多");

		if(!empty($result)){
			session($store_name.'_name',$store_name);
			session($store_name.'_code',$code);
			session($store_name.'_time',time());
			exit(json_encode(array('status' => 1, 'msg' => '验证码发送成功')));
		}else{
			exit(json_encode(array('status' => -1, 'msg' => '验证码发送失败')));
		}
	}

	/*
	 * nature：验证验证码
	 * author：吴银海
	 * time：17/07/12
	 * $store_name 用户账号
	 * $code 验证码
	 */
	function  confirm(){
		$store_name = I('store_name');
		$code = I('code');

		if(empty($store_name) || empty($code)){
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号或验证码不能为空 ^_^')));
		}

		$session_store_name = $_SESSION[$store_name.'_name'];
		$session_store_code = $_SESSION[$store_name.'_code'];
		$session_store_time = $_SESSION[$store_name.'_time'];

		if((time() - $session_store_time) > 60){
			session($store_name.'_name',null);
			session($store_name.'_code',null);
			session($store_name.'_time',null); //销毁session
			exit(json_encode(array('status'=>-1,'msg'=>'验证码超时，请重新获取 ^_^')));
		}

		if($code==$session_store_code && $store_name==$session_store_name){
			$store_info = M('merchant')->where("merchant_name = '$store_name'")->field('id')->find();
			if(!empty($store_info)){
				session($store_name.'_name',null);
				session($store_name.'_code',null);
				session($store_name.'_time',null); //销毁session
				exit(json_encode(array('status'=>1,'msg'=>'获取成功','store_id'=>$store_info['id'])));
			}else{
				exit(json_encode(array('status'=>-1,'msg'=>'该商户不存在 ^_^')));
			}
		}else{
			exit(json_encode(array('status'=>-1,'msg'=>'商户名或验证码错误 ^_^')));
		}

	}

	function test(){
		$username = 'store'. 2 ;
		$password = md5($username);
		var_dump($password);
	}

	function test1(){
		var_dump();
	}
}