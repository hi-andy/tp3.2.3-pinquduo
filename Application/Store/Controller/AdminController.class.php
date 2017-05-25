<?php
/**
 * 后台管理
 */

namespace Store\Controller;

use Think\Verify;

class AdminController extends BaseController {

    public function index(){
    	$res = $list = array();
    	$keywords = I('keywords');
    	if(empty($keywords)){
    		$res = D('admin')->select();
    	}else{
    		$res = D()->query("select * from __PREFIX__admin where user_name like '%$keywords%' order by admin_id");
    	}
    	$roles = D('admin_role')->select();
    	if($res && $roles){
    		foreach ($roles as $v){
    			$role[$v['role_id']] = $v['role_name'];
    		}
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        $this->display();
    }
    
    public function admin_info(){
	    $haitao = M('store_detail')->where('storeid='.$_SESSION['merchant_id'])->find();
	    if($haitao['is_pay']==0)
	    {
		    $url = U('Store/Index/pay_money');
		    exit(json_encode(array('status'=>1,'url'=>$url)));
	    }
    	$store_id = I('get.store_id',0);
    	if($store_id){
    		$info = D('merchant')->where("id=$store_id")->find();
		    $info['margin'] = $haitao['margin'];
		    $info['trade_no'] = $haitao['trade_no'];
    		$this->assign('info',$info);
    	}
    	$act = empty($store_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = D('admin_role')->where('1=1')->select();
    	$this->assign('role',$role);
    	$this->display();
    }
    
    public function adminHandle(){
    	$data = I('post.');
	    unset($data['store_name']);
    	if(empty($data['password2'])){
    		unset($data['password']);
    	}else{
		    $old_post_pwd = md5($data['password']);
    	}
    	if($data['act'] == 'add'){
    		unset($data['id']);
    		$data['add_time'] = time();
    		if(D('merchant')->where("store_name='".$data['store_name']."'")->count()){
    			$this->error("此用户名已被注册，请更换",U('Store/Report/index'));
    		}else{
    			$r = D('admin')->add($data);
    		}
    	}

    	if($data['act'] == 'edit'){
		    if(empty($data['store_logo']))
			    unset($data['store_logo']);

			$info = D('merchant')->where('id='.$data['id'])->find();
		    //如果输入确认密码就执行这些操作，否则直接去除
		    if(!empty($data['password2'])){
			    if($old_post_pwd == $info['password']){
				    unset($data['password']);
				    $this->error("该密码与原密码重复",U('Store/Admin/admin_info/store_id/'.$_SESSION['merchant_id']));
			    }else{
				    $data['password'] = $old_post_pwd;
			    }
		    }else{
			    unset($data['password']);
		    }
		    unset($data['password2']);

    		$r = D('merchant')->where('id='.$data['id'])->save($data);
            $result = M('goods')->where(array('store_id'=>array('eq',$data['id'])))->field('id')->select();
            foreach ($result as $value){
                redislist("goods_refresh_id", $value);
            }
    	}
    	
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('merchant')->where('id='.$data['id'])->delete();
    		exit(json_encode(1));
    	}
    	if($r){
    		$this->success("操作成功",U('Store/Report/index'));
    	}else{
    		$this->error("操作失败",U('Store/Report/index'));
    	}
    }

	//修改名字
	function changename()
	{
		if(IS_POST){
			$res = M('merchant')->where("store_name = '".$_POST['store_name']."'")->find();
			$res1 = M('merchant')->where('id = '.$_SESSION['merchant_id'])->find();
			if(empty($res)&&$res1['is_change']==0){

				$res2 = M('merchant')->where('id = '.$_SESSION['merchant_id'])->save(array('store_name'=>$_POST['store_name'],'old_name'=>$res1['store_name'],'is_change'=>1));
				if($res2){
					exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
				}else{
					exit(json_encode(array('status'=>0,'msg'=>'操作失败')));
				}
			}elseif($res1['is_change']==1){
				exit(json_encode(array('status'=>0,'msg'=>'您已经修改过了，只能修改一次哦')));
			}else{
				exit(json_encode(array('status'=>0,'msg'=>'该名字已被使用')));
			}
		}
	}

    /*
     * 管理员登陆
     */
    public function login(){
	    if($_GET['type']==1)
	    {
		    session_unset();
		    session_destroy();
	    }

	    if(session('?merchant_id') && session('merchant_id')>0) {
	        $this->error("您已登录", U('Store/Index/index'));
        }

	    if(!empty($_COOKIE['user_name']) && !empty($_COOKIE['pass_word']))
	    {
		    $this->assign('merchant_name',$_COOKIE['user_name']);
		    $this->assign('pass_word',$_COOKIE['pass_word']);
		    $this->assign('check',1);
	    }

        if(IS_POST){
//	        exit(json_encode(array('status'=>0,'msg'=>'正在和微信商城做对接调试，请您稍等')));
            $verify = new Verify();
            if (!$verify->check(I('post.vertify'), "Store/Login")) {
            	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }

            $merchant['merchant_name'] = trim(I('post.merchantname'));
	        $merchant['password'] = trim(I('post.password'));

            if(!empty($merchant['merchant_name']) || !empty($merchant['password'])){
	            $merchant['password'] = md5($merchant['password']);
               	$merchant_info = M('merchant')->where($merchant)->find();
	            $haitao = M('store_detail')->where('storeid='.$merchant_info['id'])->field('is_haitao,is_pay,trade_no')->find();

                if(is_array($merchant_info) && !empty($haitao) && !empty($merchant_info)){
	                if($merchant_info['is_check']==0)
	                {
		                exit(json_encode(array('status'=>0,'msg'=>'您的申请暂时还没审核，请耐心等待或者和客服联系')));
	                }elseif($merchant_info['is_check']==2){
		                exit(json_encode(array('status'=>0,'msg'=>'您的申请未通过审核，有疑问可与客服联系')));
	                }
	                session('merchant_id',$merchant_info['id']);
	                session('trade_no',$haitao['trade_no']);
	                if($haitao['is_pay']==0){
		                $url = U('Store/Index/pay_money');
		                exit(json_encode(array('status'=>1,'url'=>$url)));
	                }
	                //检查是否保存密码
	                if($_POST['checkbox']==1){
		                $this->set_Cookie($_POST['merchantname'],$_POST['password']);
	                }
                    session('act_list',$merchant_info['act_list']);
					session('is_haitao',$haitao['is_haitao']);
					session('state',$merchant_info['state']);
					session('merchant_name',$merchant_info['store_name']);
					session('merchant_name',$merchant_info['store_name']);
	                $data['last_login'] = time();
	                $data['last_ip'] = get_client_ip();
	                $last_login =M('merchant');
	                $last_login->where('id = '.$merchant_info['id'])->data($data)->save();
					$url = U('Store/Index/index');
					exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
					exit(json_encode(array('status'=>0,'msg'=>'帐号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }

        $this->display();
    }

	function  set_Cookie($user_name,$pass_word,$storeid)
	{
		//如果创建了就不再创建
		if(empty($_COOKIE['user_name']) || empty($_COOKIE['pass_word']) || empty($_COOKIE['storeid']))
		{
			setcookie("user_name","$user_name",time()+1*7*24*3600);
			setcookie("storeid","$storeid",time()+1*7*24*3600);
		}
		if($storeid != $_COOKIE['storeid'])
		{
			setcookie("storeid","$storeid",time()+1*7*24*3600);
		}
	}
    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
        $this->success("退出成功",U('Store/Admin/login'));
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );    
        $Verify = new Verify($config);
        $Verify->entry("Store/Login");
    }

    public function role(){
    	$list = D('admin_role')->order('role_id desc')->select();
    	$this->assign('list',$list);
    	$this->display();
    }

    public function role_info(){
    	$role_id = I('get.role_id');
    	$tree = $detail = array();
    	if($role_id){
    		$detail = D('admin_role')->where("role_id=$role_id")->find();
    		$this->assign('detail',$detail);
    	}

    	$res = D('system_module')->order('mod_id ASC')->select();
    	if($res){
    		foreach($res as $k=>$v){
    			if($detail['act_list']){
    				$act_list = explode(',', $detail['act_list']);
    				$v['enable'] = in_array($v['mod_id'], $act_list) ? 1 : 0;
    			}else{
    				$v['enable'] = 0 ;
    			}
    			$modules[$v['mod_id']] = $v;
    		}

    		if($modules){
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'top'){
    					$tree[$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'menu'){
    					$tree[$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'module'){
    					$ppk = $modules[$v['parent_id']]['parent_id'];
    					$tree[$ppk]['menu'][$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    		}
    	}

    	$this->assign('menu_tree',$tree);
    	$this->display();
    }

    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['menu']) ? implode(',', $data['menu']) : '';
    	if(empty($data['role_id'])){
    		$r = D('admin_role')->add($res);
    	}else{
    		$r = D('admin_role')->where('role_id='.$data['role_id'])->save($res);
    	}
		if($r){
			adminLog('管理角色',__ACTION__);
			$this->success("操作成功!",U('Store/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->success("操作失败!",U('Store/Admin/role'));
		}
    }

    public function roleDel(){
    	$role_id = I('post.role_id');
    	$admin = D('admin')->where('role_id='.$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id=$role_id")->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }

    public function log(){
    	$Log = M('admin_log');
    	$p = I('p',1);
    	$logs = $Log->join('__ADMIN__ ON __ADMIN__.admin_id =__ADMIN_LOG__.admin_id')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = $Log->where('1=1')->count();
    	$Page = new \Think\Page($count,20);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->display();
    }
	public function getIP()
	{
		static $realip;
		if (isset($_SERVER)){
			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
				$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
				$realip = $_SERVER["HTTP_CLIENT_IP"];
			} else {
				$realip = $_SERVER["REMOTE_ADDR"];
			}
		} else {
			if (getenv("HTTP_X_FORWARDED_FOR")){
				$realip = getenv("HTTP_X_FORWARDED_FOR");
			} else if (getenv("HTTP_CLIENT_IP")) {
				$realip = getenv("HTTP_CLIENT_IP");
			} else {
				$realip = getenv("REMOTE_ADDR");
			}
    }
		return $realip;
	}




/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 *
 * 姓名：吴银海
 * 时间：2016-8-3 10:57
 *
 * @HTTP_X_FORWARDED_FOR 浏览当前页面的用户计算机的网关
 * @HTTP_CLIENT_IP 客户端的ip
 * @REMOTE_ADDR 浏览当前页面的用户计算机的ip地址
 */
function get_client_ip($type = 0, $adv = false)
{
	$type = $type ? 1 : 0;
	static $ip = NULL;
	if ($ip !== NULL) return $ip[$type];
	if ($adv) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos = array_search('unknown', $arr);
			if (false !== $pos) unset($arr[$pos]);
			$ip = trim($arr[0]);
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
// IP地址合法验证
	$long = sprintf("%u", ip2long($ip));
	$ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
	return $ip[$type];
		}
}