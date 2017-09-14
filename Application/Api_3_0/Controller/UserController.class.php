<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/7/11
 * Time: 11:31
 */

namespace Api_3_0\Controller;

use Api_2_0_2\Controller\BaseController;

class UserController extends BaseController{

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
		$page = ($page-1)*$pagesize;

		$count =  M('stand_inside_letter')
			->where("store_id like '%,".$store_id.",%'")
			->union("SELECT	COUNT(*) AS tp_count FROM `tp_stand_inside_letter` WHERE store_id = ',0,'",true)
			->field('COUNT(*) AS tp_count')
			->SELECT();

		$count = $count[0]['tp_count'] + $count[1]['tp_count'];

		$stand_inside_letter_list = M('stand_inside_letter')->query("SELECT * FROM ((SELECT `msg_id`,`msg_title`,`msg_time`,`msg_content`,`msg_logo_url` FROM `tp_stand_inside_letter` WHERE (store_id LIKE '%,$store_id,%')) UNION ALL (SELECT `msg_id`,`msg_title`,`msg_time`,`msg_content`,`msg_logo_url` FROM `tp_stand_inside_letter` WHERE store_id = ',0,')) AS a LIMIT $page,$pagesize");
		
		foreach ($stand_inside_letter_list as $k=>$v){
			$stand_inside_letter_list[$k]['msg_content'] = strip_tags($v['msg_content']);
			$stand_inside_letter_list[$k]['msg_content_url']  = $url = "http://pinquduo.cn/Storeapp/user/stand_inside_letter_H5?msg_id=".$v['msg_id']."&store_id=$store_id";
		}

		$stand_inside_letter_list = $this->listPageData($count,$stand_inside_letter_list,$pagesize);

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
			->field('store_id,msg_title,msg_content')
			->find();

		if(!empty(substr_count($store_id,$msg['store_id']))){
			exit(json_encode(array('status' => -1, 'msg' => '无法访问当前站内信')));
		}

		$this->assign('msg_content',$msg['msg_content']);
		$this->assign('msg_title',$msg['msg_title']);
		$this->display();
	}
}