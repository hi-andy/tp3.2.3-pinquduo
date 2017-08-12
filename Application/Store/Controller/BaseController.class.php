<?php

/**

 */

namespace Store\Controller;
use Think\Controller;
use Store\Logic\UpgradeLogic;
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
	    if(empty(redis('store_id_'.$_SESSION['merchant_id']))){
		    session_unset();
		    session_destroy();
		    setcookie('storeid',null);
		    $this->error("数据维护，请重新登录",U('Store/Admin/login'));
	    }
        $this->assign('action',ACTION_NAME);
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
        }else{
        	if(session('merchant_id') > 0 ){

        	}else{
        		$this->error('请先登陆',U('Store/Admin/login'),1);
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
    	}elseif(strpos('ajax',$act) || in_array($act,$no_check) || $act_list == 'all'){
    		return true;
    	}else{
    		$mod_id = M('system_module')->where("ctl='$ctl' and act='$act'")->getField('mod_id');
    		$act_list = explode(',', $act_list);
    		if($mod_id){
    			if(!in_array($mod_id, $act_list)){
    				$this->error('您的账号没有此菜单操作权限,超级管理员可分配权限',U('Admin/Index/index'));
    				exit;
    			}else{
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

	public function cash_available($store_id){
        //拿到总共能体现的资金
//		$store_id  = 3439;
//		$_SESSION['merchant_id'] = 3439;
        $one = M('order')->where('(order_type =4 or order_type = 16 or order_type = 7 or order_type=6) and confirm_time is not null and store_id='.$store_id)->field('order_id,confirm_time,order_amount')->select();
		(float)$reflect = null;
        foreach($one as $v){
            $temp = 2*3600*24;
            $cha = time()-$v['confirm_time'];
            if($cha>=$temp){
	            (float)$reflect = (float)$reflect+$v['order_amount'];
            }
        }
        //获取以前的提取记录
		(float)$total = 0;
        $withdrawal_total = M('store_withdrawal')->where('store_id='.$store_id.' and (status=1 or status=0 )')->field('withdrawal_money')->select();

        $suoding = M('store_withdrawal')->where('store_id='.$store_id.' and status=1')->field('withdrawal_money,withdrawal_code')->order('sw_id desc')->find();
        if(!empty($suoding))
        {
            $this->assign('suoding',$suoding);
        }
        foreach($withdrawal_total as $v)
        {
	        (float)$total = (float)$total+$v['withdrawal_money'];
        }
		(float)$reflects = $reflect;
		(float)$reflect = $reflect-$total;

        if(empty($reflect)||((string)$reflects==(string)$total)){
	        $reflect = 0;
        }

        $c = getFloatLength($reflect);
        if($c>=3){
            $reflect = operationPrice($reflect);
        }
        return (float)$reflect;
    }
}