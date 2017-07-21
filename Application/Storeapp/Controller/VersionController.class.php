<?php

/**
 * 版本接口
 */
namespace Storeapp\Controller;
use Think\Controller;

class VersionController extends BaseController {

    public function _initialize() {
        $this->encryption();
    }

	/**
	 * 获取最新的android版本信息
	 */
	public function getlastversion()
    {
        $data = $_REQUEST;
        $terminal = $data['terminal'];
        $rdsname = "getstorelastversion".$terminal;
        if (empty(redis($rdsname))) {
            $where['type'] = array("eq", 1);
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
            if (empty($data))
                $data = null;
            $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
            redis($rdsname, serialize($json), REDISTIME);
        } else {
            $json = unserialize(redis($rdsname));
        }

        exit(json_encode($json));
    }
}