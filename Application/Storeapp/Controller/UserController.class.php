<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/7/11
 * Time: 11:31
 */

namespace Storeapp\Controller;

use Api_2_0_0\Controller\AlidayuController;
use Mobile\Controller\CartController;

class UserController extends CartController{

	/*
	 * nature：用户反馈
	 * author：吴银海
	 * time：17/07/14
	 * $store_name 商户名
	 * $text 反馈内容
	 * $store_id 商户名id
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

	/*
	 * nature：站内信
	 * author：吴银海
	 * time：17/07/14
	 * $store_id 商户名id
	 * $page 页码
	 * $pagesize 返回数量
	 */
	function stand_inside_letter(){
		$data = $_REQUEST;
		$store_id = $data['store_id'];
		$page = $data['page'];
		$pagesize = $data['pagesize'];

		if(empty($store_id) || empty($page) || empty($pagesize)){
			exit(json_encode(array('status' => -1, 'msg' => '参数有误 ^_^')));
		}

		$stand_inside_letter_list = M('stand_inside_letter')
			->where("store_id like '%".$store_id."%'")
			->field('msg_id,msg_title,msg_time,msg_content')
			->page($page,$pagesize)
			->select();
		foreach ($stand_inside_letter_list as $k=>$v){
			$stand_inside_letter_list[$k]['msg_content'] = strip_tags($v['msg_content']);
			$stand_inside_letter_list[$k]['msg_content_url']  = $url = "http://119.23.56.30/Storeapp/user/stand_inside_letter_H5?msg_id=".$v['msg_id']."&store_id=$store_id";
		}

		exit(json_encode(array('status' => 1, 'msg' => '获取成功','result'=>$stand_inside_letter_list)));
	}

	/*
	 * nature：站内信H5页面
	 * author：吴银海
	 * time：17/07/14
	 * $store_name 商户名
	 * $text 反馈内容
	 * $store_name 商户名id
	 */
	function stand_inside_letter_H5($msg_id,$store_id){

		if(empty($msg_id)){
			exit(json_encode(array('status' => -1, 'msg' => '参数有误 ^_^')));
		}

		$msg = M('stand_inside_letter')
			->where('msg_id = '.$msg_id)
			->field('store_id,msg_content')
			->find();

		if(!empty(substr_count($store_id,$msg['store_id']))){
			exit(json_encode(array('status' => -1, 'msg' => '无法访问当前站内信')));
		}

		$this->assign('msg_content',$msg['msg_content']);
		$this->display();
	}
}