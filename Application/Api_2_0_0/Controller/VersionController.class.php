<?php

/**
 * 版本接口
 */
namespace Api_2_0_0\Controller;
use Think\Controller;

class VersionController extends BaseController {

	/**
	 * 获取最新的android版本信息
	 */
	public function getlastversion()
	{
		$item = M("version")->order('createtime desc')->find();
		$version = $item['version'];
		$message = $item['message'];
		$packageUrl = C('HTTP_URL').'/'.$item['file'];
 		$data['version'] = $version;
		$data['versionDesc'] = $message;
		$data['filepath'] = $packageUrl;
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}
}