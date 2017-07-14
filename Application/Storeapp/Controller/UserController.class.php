<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/7/11
 * Time: 11:31
 */

namespace Storeapp\Controller;

use Api_2_0_0\Controller\AlidayuController;

class UserController {

	/*
	 * nature：用户反馈
	 * author：吴银海
	 * time：17/07/14
	 * $store_name 商户名
	 * $text 反馈内容
	 * $store_name 商户名id
	 */
		function feedback(){
		$data = $_REQUEST;
		$text = $data['text'];
		$store_id = $data['store_id'];
		$store_name = $data['store_name'];
		if(empty($text)){
			exit(json_encode(array('status' => -1, 'msg' => '请确输入无误后提交 ^_^')));
		}

		$res = M('feedback')->data(array('user_id'=>$store_id,'user_name'=>$store_name,'msg_content'=>$data['text'],'msg_time'=>time()))->add();
		if ($res){
			exit(json_encode(array('status' => 1, 'msg' => '提交成功 ^_^')));
		}else{
			exit(json_encode(array('status' => -1, 'msg' => '提交失败，请确认后提交 ^_^')));
		}
	}

	function stand_inside_letter(){
		
	}
}