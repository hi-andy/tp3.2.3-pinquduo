<?php
namespace Store\Controller;


class IndexController extends BaseController {


    public function index(){
        $this->pushVersion();
        $act_list = session('act_list');
        $menu_list = $this->getRoleMenu($act_list);
        $this->assign('menu_list',$menu_list);
        $merchant_info = getAdminInfo(session('merchant_id'));
		$this->assign('merchant_info',$merchant_info);
        $this->display();
    }
   
    public function welcome(){
    	$this->assign('sys_info',$this->get_sys_info());
    	$today = strtotime("-1 day");
    	$count['handle_order'] = M('order')->where("add_time>$today ".C('WAITSEND'))->count();//待发货订单
    	$count['new_order'] = M('order')->where("add_time>$today")->count();//今天新增订单
    	$count['goods'] =  M('goods')->where("1=1")->count();//商品总数
    	$count['article'] =  M('article')->where("1=1")->count();//文章总数
    	$count['users'] = M('users')->where("1=1")->count();//会员总数
    	$count['today_login'] = M('users')->where("last_login>$today")->count();//今日访问
    	$count['new_users'] = M('users')->where("reg_time>$today")->count();//新增会员
    	$count['comment'] = M('comment')->where("is_show=0")->count();//最新评论
    	$this->assign('count',$count);
        $this->display();
    }
    
    public function map(){
    	$all_menu = $this->getRoleMenu('all');
    	$this->assign('all_menu',$all_menu);
    	$this->display();
    }
    
    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');		
        $sys_info['version']   		= file_get_contents('./Application/Admin/Conf/version.txt');
        $dbPort = C("DB_PORT"); $dbHost = C("DB_HOST");
        $dbHost = empty($dbPort) || $dbPort == 3306 ? $dbHost : $dbHost.':'.$dbPort;
		mysql_connect($dbHost, C("DB_USER"), C("DB_PWD"));
		$sys_info['mysql_version']   = mysql_get_server_info();
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
    
    
    public function pushVersion()
    {            
        if(!empty($_SESSION['isset_push']))
            return false;    
        $_SESSION['isset_push'] = 1;    
        error_reporting(0);//关闭所有错误报告
        $app_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $version_txt_path = $app_path.'/Application/Admin/Conf/version.txt';
        $curent_version = file_get_contents($version_txt_path);

        $vaules = array(            
                'domain'=>$_SERVER['SERVER_NAME'], 
                'last_domain'=>$_SERVER['SERVER_NAME'], 
                'key_num'=>$curent_version, 
                'install_time'=>INSTALL_DATE, 
                'cpu'=>'0001',
                'mac'=>'0002',
                'serial_number'=>SERIALNUMBER,
         );     
         $url = "http://service.tp".'-'."shop".'.'."cn/index.php?m=Home&c=Index&a=user_push&".http_build_query($vaules);
         stream_context_set_default(array('http' => array('timeout' => 3)));
         file_get_contents($url);         
    }
    
    
    public function getRoleMenu($act_list)
    {
    	$modules = $roleMenu = array();
    	$rs = M('system_module')->where('level>1 AND visible=1')->order('orderby ASC')->select();

    	if($act_list=='all'){
    		foreach($rs as $row){
    			if($row['level'] == 3){
    				$row['url'] = U("Store/".$row['ctl']."/".$row['act']."");
    				$modules[$row['parent_id']][] = $row;//子菜单分组
    			}
    			if($row['level'] == 2){
    				$pmenu[$row['mod_id']] = $row;//二级父菜单
    			}
    		}
    	}else{
    		$act_list = explode(',', $act_list);
    		foreach($rs as $row){
    			if(in_array($row['mod_id'],$act_list)){
    				$row['url'] = U("Store/".trim($row['ctl'])."/".$row['act']."");
    				$modules[$row['parent_id']][] = $row;//子菜单分组
    			}
    			if($row['level'] == 2){
    				$pmenu[$row['mod_id']] = $row;//二级父菜单
    			}
    		}
    	}
    	$keys = array_keys($modules);//导航菜单
    	foreach ($pmenu as $k=>$val){
    		if(in_array($k, $keys)){
    			$val['submenu'] = $modules[$k];//子菜单
    			$roleMenu[] = $val;
    		}
    	}

    	return $roleMenu;
    }
    
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table = I('table'); // 表名
            $id_name = I('id_name'); // 表主键id名
            $id_value = I('id_value'); // 表主键id值
            $field  = I('field'); // 修改哪个字段
            $value  = I('value'); // 修改字段值                        
            M($table)->where("$id_name = $id_value")->save(array($field=>$value)); // 根据条件保存修改的数据
    }

	public function _initialize() {
		vendor('Alipay.Corefunction');
		vendor('Alipay.Md5function');
		vendor('Alipay.Notify');
		vendor('Alipay.Submit');
		vendor('Alipay.AlipaySubmit');


			session_unset();
			session_destroy();
			setcookie('storeid',null);
			$this->error("请前往新商户后台登录",U('Store/Admin/login'));

	}

	function pay_money()
	{
		$store_id = $_SESSION['merchant_id'];
		$detail = M('store_detail')->where('storeid = '.$store_id)->find();
		$store_name = M('merchant')->where('id = '.$store_id)->find();
		if($detail['store_from']==1)
		{
			if($detail['is_haitao']==1){
				$fee = 2000.00;
			}else{
				$fee = 1500.00;
			}
		}elseif($detail['store_from']==0)
		{
			if($detail['is_haitao']==1){
				$fee = 2500.00;
			}else{
				$fee = 2000.00;
			}
		}
		$rand = rand(100000,999999);
		$order = time().$rand;
		$body = '商户交纳保证金';

		$data['margin'] = $fee;
		$data['trade_no'] = $order;
		$this->assign('subject',$store_name['store_name']);
		$this->assign('order',$order);
		$this->assign('body',$body);
		$this->assign('fee',$fee);
		$this->display();
	}

	public function doalipay(){
		/*********************************************************
		把alipayapi.php中复制过来的如下两段代码去掉，
		第一段是引入配置项，
		第二段是引入submit.class.php这个类。
		为什么要去掉？？
		第一，配置项的内容已经在项目的Config.php文件中进行了配置，我们只需用C函数进行调用即可；
		第二，这里调用的submit.class.php类库我们已经在PayAction的_initialize()中已经引入；所以这里不再需要；
		 *****************************************************/
		//这里我们通过TP的C函数把配置项参数读出，赋给$alipay_config；
		$alipay_config=C('alipay_config_face');

		/**************************请求参数**************************/

		$payment_type = "1"; //支付类型 //必填，不能修改
		$notify_url = 'http://pinquduo.cn/store/Index/notify_url'; //服务器异步通知页面路径
		$return_url = 'http://pinquduo.cn/Store/Index/returnurl'; //页面跳转同步通知页面路径
		$seller_email = '2660357732@qq.com';//卖家支付宝帐户必填

		$out_trade_no = $_POST['order'];//商户订单号 通过支付页面的表单进行传递，注意要唯一！
		$subject = $_POST['subject'];  //订单名称 //必填 通过支付页面的表单进行传递
		$total_fee = doubleval($_POST['fee']);   //付款金额  //必填 通过支付页面的表单进行传递
		$body = $_POST['body'];  //订单描述 通过支付页面的表单进行传递
		$show_url = '11';  //商品展示地址 通过支付页面的表单进行传递
		$anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
		$exter_invoke_ip = get_client_ip(); //客户端的IP地址

		/************************************************************/

		//构造要请求的参数数组，无需改动
		$parameter = array(
			"service" => "create_direct_pay_by_user",
			"partner" => trim($alipay_config['partner']),
			"payment_type"    => $payment_type,
			"notify_url"    => $notify_url,
			"return_url"    => $return_url,
			"seller_email"    => $seller_email,
			"out_trade_no"    => $out_trade_no,
			"subject"    => $subject,
			"total_fee"    => $total_fee,
			"body"         => $body,
			"show_url"    => $show_url,
			"anti_phishing_key"    => $anti_phishing_key,
			"exter_invoke_ip"    => $exter_invoke_ip,
			"_input_charset"    => trim(strtolower($alipay_config['input_charset']))
		);
		//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"post", "确认");
		echo $html_text;
	}


	/******************************
	服务器异步通知页面方法
	其实这里就是将notify_url.php文件中的代码复制过来进行处理

	 *******************************/
	function notify_url(){
		/*
		同理去掉以下两句代码；
		*/
		//这里还是通过C函数来读取配置项，赋值给$alipay_config
		$alipay_config=C('alipay_config_face');
		//计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {
			//验证成功
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			$out_trade_no = $_POST['out_trade_no'];      //商户订单号
			$trade_no = $_POST['trade_no'];          //支付宝交易号
			$trade_status = $_POST['trade_status'];      //交易状态
			$total_fee = $_POST['total_fee'];         //交易金额
			$notify_id = $_POST['notify_id'];         //通知校验ID。
			$notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
			$buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
			$parameter = array(
				"out_trade_no" => $out_trade_no, //商户订单编号；
				"trade_no" => $trade_no,     //支付宝交易号；
				"total_fee" => $total_fee,    //交易金额；
				"trade_status" => $trade_status, //交易状态
				"notify_id" => $notify_id,    //通知校验ID。
				"notify_time" => $notify_time,  //通知的发送时间。
				"buyer_email" => $buyer_email,  //买家支付宝帐号；
			);
			if ($_POST['trade_status'] == 'TRADE_FINISHED') {
				$this->orderhandle($parameter);
			} else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				if (!$this->check_order_status($out_trade_no)) {
					//进行订单处理，并传送从支付宝返回的参数；
					$this->orderhandle($parameter);
				}
			}
			echo "success";  //请不要修改或删除
		}else{
			//验证失败
			echo "fail";
		}
	}
	/*
		页面跳转处理方法；
		这里其实就是将return_url.php这个文件中的代码复制过来，进行处理；
		*/
	function returnurl(){
		//头部的处理跟上面两个方法一样，这里不罗嗦了！
		$alipay_config=C('alipay_config_face');
		$alipayNotify = new \AlipayNotify($alipay_config);//计算得出通知验证结果
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {
			//验证成功
			//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
			$out_trade_no   = $_GET['out_trade_no'];      //商户订单号
			$trade_no       = $_GET['trade_no'];          //支付宝交易号
			$trade_status   = $_GET['trade_status'];      //交易状态
			$subject   		= $_GET['subject'];      	  //交易商户名
			$total_fee      = $_GET['total_fee'];         //交易金额
			$notify_id      = $_GET['notify_id'];         //通知校验ID。
			$notify_time    = $_GET['notify_time'];       //通知的发送时间。
			$buyer_email    = $_GET['buyer_email'];       //买家支付宝帐号；

			$parameter = array(
				"out_trade_no"     => $out_trade_no,      //商户订单编号；
				"trade_no"     => $trade_no,          //支付宝交易号；
				"total_fee"      => $total_fee,         //交易金额；
				"trade_status"     => $trade_status,      //交易状态
				"subject"     => $subject,      //交易商户名
				"notify_id"      => $notify_id,         //通知校验ID。
				"notify_time"    => $notify_time,       //通知的发送时间。
				"buyer_email"    => $buyer_email,       //买家支付宝帐号
			);

			if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				if(!$this->check_order_status($out_trade_no)){
					$this->orderhandle($parameter);  //进行订单处理，并传送从支付宝返回的参数；
				}
				$this->redirect('Index/index');//跳转到配置项中配置的支付成功页面；
			}else {
				echo "trade_status=".$_GET['trade_status'];
				$this->redirect('Admin/login');//跳转到配置项中配置的支付失败页面；
			}
		}else {
			//验证失败
			//如要调试，请看alipay_notify.php页面的verifyReturn函数
			echo "支付失败！";
		}
	}
	function check_order_status($order)
	{
		$ordstatus=M('store_detail')->where('margin_order='.$order)->getField('is_pay');
		if($ordstatus==1){
			return true;
		}else{
			return false;
		}
	}

	function orderhandle($parameter)
	{
		$data['is_pay'] = 1;
		$data['notify_time'] = $parameter['notify_time'];
		$data['margin_order'] = $parameter['out_trade_no'];
		$data['margin'] = $parameter['total_fee'];
		$data['buyer_email'] = $parameter['buyer_email'];
		$data['trade_no'] = $parameter['trade_no'];
		$store_id=M('merchant')->where('store_name ='."'".$parameter['subject']."'")->getField('id');
		M('store_detail')->where('storeid='.$store_id)->data($data)->save();
	}

//	function  test(){
//		$stores = M('store_detail')->field('storeid')->limit(5500,500)->select();
//		$arr = array();
//		for($i=0;$i<count($stores);$i++){
//			$res = $this->cash_available($stores[$i]['storeid']);
//			$c = 0.00;
//			if($res < $c){
//				$arr[]['id'] =  $stores[$i]['storeid'];
//			}
//		}
//		var_dump($arr);
//	}
}