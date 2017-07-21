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

		$merchant_name = M('merchant')->where("merchant_name = '$store_name'")->find();
		if(empty($merchant_name)){
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号不存在 ^_^')));
		}
		$store_info = M('merchant')->where("merchant_name = '$store_name' and password = '$store_pass' ")->find();
		if(!empty($store_info)){
			exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_id'=>$store_info['id'],'store_logo'=>$store_info['store_logo'],'store_name'=>$store_info['store_name']))));
		}else{
			exit(json_encode(array('status'=>-1,'msg'=>'商户密码不正确 ^_^')));
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
			$res = $Store->where("id = '$store_id'")->save(array('password' => md5($new_pass_word2)));
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
	 * $time 时间戳
	 * */
	function sendSMS(){
		$data = $_REQUEST;
		$store_name = $data['store_mobile'];
		$time = $data['time'];

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
			redis($store_name.'_name', serialize($store_name));
			redis($store_name.'_code', serialize($code));
			redis($store_name.'_time', serialize($time));
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
	 * $time 时间戳
	 */
	function  confirm(){
		$data = $_REQUEST;
		$store_name = $data['store_mobile'];
		$code = $data['code'];
		$time = $data['time'];
		if(empty($store_name) || empty($code)){
			exit(json_encode(array('status'=>-1,'msg'=>'商户账号或验证码不能为空 ^_^')));
		}

		$session_store_name = unserialize(redis($store_name.'_name'));
		$session_store_code = unserialize(redis($store_name.'_code'));
		$session_store_time = unserialize(redis($store_name.'_time'));

		$q = $time - $session_store_time;
//		if($q > 60){
//			redisdelall($store_name.'_name');
//			redisdelall($store_name.'_code');
//			redisdelall($store_name.'_time');
//			exit(json_encode(array('status'=>-1,'msg'=>'验证码超时，请重新获取 ^_^')));
//		}
//       $code==$session_store_code && $store_name==$session_store_name
		if(1){
			$store_info = M('merchant')->where("merchant_name = '$store_name'")->field('id')->find();
			if(!empty($store_info)){
				redisdelall($store_name.'_name');
				redisdelall($store_name.'_code');
				redisdelall($store_name.'_time');
				exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_id'=>$store_info['id']))));
			}else{
				exit(json_encode(array('status'=>-1,'msg'=>'该商户不存在 ^_^')));
			}
		}else{
			exit(json_encode(array('status'=>-1,'msg'=>'商户名或验证码错误 ^_^')));
		}

	}

//	function test($store_name){
//		$session_store_name = unserialize(redis($store_name.'_name'));
//		$session_store_code = unserialize(redis($store_name.'_code'));
//		$session_store_time = unserialize(redis($store_name.'_time'));
//		var_dump($session_store_name);var_dump($session_store_code);var_dump($session_store_time);
//	}

	/*
	 * nature：工作台
	 * author：吴银海
	 * time：17/07/19
	 * $store_id 用户账号
	 */
	function workbench(){
		$data = $_REQUEST;
		$store_id = $data['store_id'];
		if(empty($store_id)){
			exit(json_encode(array('status'=>-1,'msg'=>'商户id不能为空 ^_^')));
		}
		$Order = M('order');
		//今日销售额  今日订单数 待成团 待付款 未处理售后 退款中
		$today = strtotime(date('Y-m-d'));
		$info[0]['key'] = '今日销售额';
		$info[0]['value'] = $Order->where('pay_status=1 and add_time>'.$today.' and add_time<'.($today+24*3600).' and store_id = '.$store_id)->sum('order_amount');
		empty($info[0]['value']) &&  $info[0]['value']=0;
		$info[1]['key'] = '今日订单数';
		$info[1]['value'] = $Order->where('pay_status=1 and add_time>'.$today.' and add_time<'.($today+24*3600).' and store_id = '.$store_id)->count();

		$info[2]['key'] = '待成团';
		$info[2]['value'] = M('group_buy')->where('is_successful = 0 and end_time > '.time())->count();

		$info[3]['key'] = '待付款';
		$info[3]['value'] = $Order->where('pay_status = 0 and order_type != 5 and store_id = '.$store_id)->count();

		$info[4]['key'] = '未处理售后';
		$info[4]['value'] = M('return_goods')->where("status = 0 and store_id = $store_id")->count();

		$info[5]['key'] = '退款中';
		$info[5]['value'] = M('return_goods')->where("status != 3 and status != 0 and store_id = $store_id")->count();

		exit(json_encode(array('status'=>1,'msg'=>'获取成功 ^_^','result'=>$info)));
	}

	function test2($store_name){
		redisdelall($store_name.'_name');
		redisdelall($store_name.'_code');
		redisdelall($store_name.'_time');
	}
}