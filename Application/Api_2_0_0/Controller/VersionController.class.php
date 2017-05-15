<?php

/**
 * 版本接口
 */
namespace Api_2_0_0\Controller;
use Think\Controller;

class VersionController extends BaseController {

    public function _initialize() {
        $this->encryption();
    }

	/**
	 * 获取最新的android版本信息
	 */
	public function getlastversion($terminal="")
    {
        $rdsname = "getlastversion".$terminal;
        if (empty(redis($rdsname))) {
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
            $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
            redis($rdsname, serialize($json), REDISTIME);
        } else {
            $json = unserialize(redis($rdsname));
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
}