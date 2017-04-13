<?php

/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */

namespace Admin\Controller;
use Think\Controller;
use Admin\Logic\UpgradeLogic;
class BaseController extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        parent::__construct();
        $upgradeLogic = new UpgradeLogic();
        $upgradeMsg = $upgradeLogic->checkVersion(); //升级包消息        
        $this->assign('upgradeMsg',$upgradeMsg);    
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);
        tpversion();
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        $this->assign('action',ACTION_NAME);
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
        }else{
        	if(session('admin_id') > 0 ){
        		$this->check_priv();//检查管理员菜单操作权限
        	}else{
        		$this->error('请先登陆',U('Admin/Admin/login'),1);
        	}
        }
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $tpshop_config = array();
       $tp_config = M('config')->select();       
       foreach($tp_config as $k => $v)
       {
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('tpshop_config', $tpshop_config);
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;

		$act_list = session('act_list');
		$no_check = array('login','logout','vertifyHandle','vertify','imageUp','upload');
    	if($ctl == "Index" && $act == 'index'){
    		return true;
    	}elseif(strpos($act,'ajax')!=false || strpos($act,'ajax')==0 || in_array($act,$no_check) || $act_list == 'all'){
    		return true;
    	}else{
    		$mod_id = M('system_module')->where("ctl='$ctl' and act='$act'")->getField('mod_id');
    		$act_list = explode(',', $act_list);
    		if($mod_id){
    			if(!in_array($mod_id, $act_list)){
    				$this->error('您的账号没有此菜单操作权限,超级管理员可分配权限',U('Admin/Index/index'));
    				exit;
      				return true;
    			}
    		}else{
    			$this->error('请系统管理员先在菜单管理页添加该菜单',U('Admin/System/menu'));
    			exit;
    		}
    	}
    }

	/*
	 * 对退货的图片进行操作
	 * */
	public function getIMG($return_goods,$num)
	{
		for($i=0;$i<$num;$i++)
		{
			if(strstr($return_goods['imgs'][$i],'"width"')||strstr($return_goods['imgs'][$i],'height'))
			{
				unset($return_goods['imgs'][$i]);
			}
			elseif(strstr($return_goods['imgs'][$i],'{"origin":"')||strstr($return_goods['imgs'][$i],'small')||strstr($return_goods['imgs'][$i],'"}')||strstr($return_goods['imgs'][$i],'"}')||strstr($return_goods['imgs'][$i],']')||strstr($return_goods['imgs'][$i],'jpg"'))
			{
				$return_goods['imgs'][$i] = str_replace(array('[{"origin":"','"small":"','{"origin":"','"}',']','"'),"",$return_goods['imgs'][$i]);
			}
		}
		$return_goods['imgs'] = array_values($return_goods['imgs']);
		foreach($return_goods['imgs'] as &$v)
		{
			$v = C('HTTP_URL').$v;
		}
		$nums = count($return_goods['imgs']);
		for($j=0;$j<$nums;$j++)
		{
			if($j%2==0)
			{
				unset($return_goods['imgs'][$j]);
			}
		}
		$return_goods['imgs'] = array_values($return_goods['imgs']);
		return $return_goods;
	}

	public function getStoreWhere($where,$store_name)
	{
		$store_id = M('merchant')->where("`store_name` like '%".$store_name."%'")->select();
		$store_ids =null;
		$num = count($store_id);
		for($i=0;$i<$num;$i++)
		{
			if($num==1){
				$store_ids = $store_ids."('".$store_id[$i]['id']."')";
			}elseif($i==$num-1)
			{
				$store_ids = $store_ids."'".$store_id[$i]['id']."')";
			}elseif($i==0){
				$store_ids = $store_ids."('".$store_id[$i]['id']."',";
			}else{
				$store_ids = $store_ids."'".$store_id[$i]['id']."',";
			}
		}
		$where = "$where and store_id IN $store_ids";
		return $where;
	}

	public function getStoreWhereID($where,$store_name)
	{
		$store_id = M('merchant')->where("`store_name` like '%".$store_name."%'")->select();
		$store_ids =null;
		$num = count($store_id);
		for($i=0;$i<$num;$i++)
		{
			if($num==1){
				$store_ids = $store_ids."('".$store_id[$i]['id']."')";
			}elseif($i==$num-1)
			{
				$store_ids = $store_ids."'".$store_id[$i]['id']."')";
			}elseif($i==0){
				$store_ids = $store_ids."('".$store_id[$i]['id']."',";
			}else{
				$store_ids = $store_ids."'".$store_id[$i]['id']."',";
			}
		}
		$where = "$where and id IN $store_ids";
		return $where;
	}
}