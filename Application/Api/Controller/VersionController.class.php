<?php

/**
 * 版本接口
 */
namespace Api\Controller;
use Think\Controller;

class VersionController extends BaseController {

	/**
	 * 获取最新的android版本信息
	 */
	public function getlastversion($terminal="")
	{
        if ($terminal) {
            $where["terminal"] = array("eq", $terminal);
        } else {
            $where["terminal"] = array("eq", "a");
        }
		$item = M("version")->where($where)->order('createtime desc')->find();
        $data['version'] = $item['version'];
        $data['versionName'] = $item['versionname'];
        $data['versionDesc'] = $item['versiondesc'];
        $data['filepath'] = $item['file'];
        $data['force'] = $item['force'];
        $data['terminal'] = $item['terminal'];
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		$json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}
}