<?php
/**
 * ashop
 */

namespace Store\Controller;

class AlipayapiController extends BaseController {


	public function _initialize() {
		vendor('Alipay.Corefunction');
		vendor('Alipay.Rsa');
		vendor('Alipay.Notify');
		vendor('Alipay.Asubmit');
		vendor('Alipay.AlipaySubmit');
		if(empty($_SESSION['merchant_id']))
		{
			session_unset();
			session_destroy();
			$this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
		}
	}

	function pay_money()
	{
		$store_id = $_SESSION['merchant_id'];
		$detail = M('store_detail')->where('storeid = '.$store_id)->find();
		$store_name = M('merchant')->where('id = '.$store_id)->find();
		if($detail['store_from']==1)
		{
			if($detail['is_haitao']==1){
				$fee = 1500.00;
			}else{
				$fee = 1000.00;
			}
		}elseif($detail['store_from']==0)
		{
			if($detail['is_haitao']==1){
				$fee = 2000.00;
			}else{
				$fee = 1500.00;
			}
		}
		$rand = rand(100000,999999);
		$order = time().$rand;
		$body = '商户交纳保证金';

		$data['margin'] = $fee;
		$data['trade_no'] = $order;
		if($store_name['id']==2)
		{
			$fee = 0.01;
		}
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
		$alipay_config=C('alipay_config');
		vendor('Alipay.Corefunction');
		vendor('Alipay.Rsa');
		vendor('Alipay.Notify');
		vendor('Alipay.Asubmit');
		vendor('Alipay.AlipaySubmit');
		/**************************请求参数**************************/

		$payment_type = "1"; //支付类型 //必填，不能修改
		$notify_url = 'http://pinquduo.cn/store/Alipayapi/notify_url'; //服务器异步通知页面路径
		$return_url = 'http://pinquduo.cn/Store/Alipayapi/returnurl'; //页面跳转同步通知页面路径
		$seller_email = '2660357732@qq.com';//卖家支付宝帐户必填

		$out_trade_no = $_POST['order'];//商户订单号 通过支付页面的表单进行传递，注意要唯一！
		$subject = $_POST['subject'];  //订单名称 //必填 通过支付页面的表单进行传递
		$total_fee = doubleval($_POST['fee']);   //付款金额  //必填 通过支付页面的表单进行传递
		$body = $_POST['body'];  //订单描述 通过支付页面的表单进行传递
		$show_url = '11';  //商品展示地址 通过支付页面的表单进行传递
		$anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
		$exter_invoke_ip = get_client_ip(); //客户端的IP地址

		/************************************************************/

		$parameter = array(
			"service"       => "create_direct_pay_by_user",
			"partner"       => $alipay_config['partner'],
			"seller_id"  => $seller_email,
			"payment_type"	=> $payment_type,
			"notify_url"	=> $notify_url,
			"return_url"	=> $return_url,

			"anti_phishing_key"=>$anti_phishing_key,
			"exter_invoke_ip"=>$exter_invoke_ip,
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"body"	=> $body,
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
			//其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
			//如"参数名"=>"参数值"

		);

//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
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
		vendor('Alipay.Anotify');
		//这里还是通过C函数来读取配置项，赋值给$alipay_config
		$alipay_config=C('alipay_config');
		//计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if(1) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			//验证成功
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			$out_trade_no   = $_POST['out_trade_no'];      //商户订单号
			$trade_no       = $_POST['trade_no'];          //支付宝交易号
			$trade_status   = $_POST['trade_status'];      //交易状态
			$total_fee      = $_POST['total_fee'];         //交易金额
			$notify_id      = $_POST['notify_id'];         //通知校验ID。
			$notify_time    = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
			$buyer_email    = $_POST['buyer_email'];       //买家支付宝帐号；
			$parameter = array(
				"out_trade_no"     => $out_trade_no, //商户订单编号；
				"trade_no"     => $trade_no,     //支付宝交易号；
				"total_fee"     => $total_fee,    //交易金额；
				"trade_status"     => $trade_status, //交易状态
				"notify_id"     => $notify_id,    //通知校验ID。
				"notify_time"   => $notify_time,  //通知的发送时间。
				"buyer_email"   => $buyer_email,  //买家支付宝帐号；
			);
			$file = './log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
			$content = "$out_trade_no ··· $trade_no ··· $trade_status"; //要写入的内容
			file_put_contents($file, $content,FILE_APPEND);//写入文件
			M('store_detail')->where('storeid = 2')->save(array('is_pay'=>3));
			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
				//如果有做过处理，不执行商户的业务程序
				$this->orderhandle($parameter);
				//注意：
				//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
				//调试用，写文本函数记录程序运行情况是否正常
				logResult("$parameter");
			}
			else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
				//如果有做过处理，不执行商户的业务程序
				if(!$this->check_order_status($out_trade_no))
				{
					//进行订单处理，并传送从支付宝返回的参数；
					$this->orderhandle($parameter);
				}
				//注意：
				//付款完成后，支付宝系统发送该交易状态通知
				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
			}

			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

			echo "success";		//请不要修改或删除

			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		else {
			//验证失败
			echo "fail";
			$file = './log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
			$content = "失败"; //要写入的内容
			file_put_contents($file, $content,FILE_APPEND);//写入文件
			M('store_detail')->where('storeid = 2')->save(array('is_pay'=>4));
			//调试用，写文本函数记录程序运行情况是否正常
			//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}
	/*
		页面跳转处理方法；
		这里其实就是将return_url.php这个文件中的代码复制过来，进行处理；
		*/
	function returnurl(){
		//头部的处理跟上面两个方法一样，这里不罗嗦了！
		$alipay_config=C('alipay_config');
		//计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {//验证成功
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代码
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
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
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
				if(!$this->check_order_status($out_trade_no)){
				$this->orderhandle($parameter);  //进行订单处理，并传送从支付宝返回的参数；
			}
			$this->redirect('Index/index');//跳转到配置项中配置的支付成功页面；
			}
			else {
				echo "trade_status=".$_GET['trade_status'];
//				$this->redirect('Admin/login');//跳转到配置项中配置的支付失败页面；
			}

			echo "验证成功<br />";

			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		else {
			//验证失败
			//如要调试，请看alipay_notify.php页面的verifyReturn函数
			echo "验证失败";
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

	//	/**
//	 * 支付结果返回
//	 */
//	public function notify() {
//		$apitype = I('get.apitype');
//
//		$pay = new \Think\Pay($apitype, C('payment.' . $apitype));
//		if (IS_POST && !empty($_POST)) {
//			$notify = $_POST;
//		} elseif (IS_GET && !empty($_GET)) {
//			$notify = $_GET;
//			unset($notify['method']);
//			unset($notify['apitype']);
//		} else {
//			exit('Access Denied');
//		}
//		//验证
//		if ($pay->verifyNotify($notify)) {
//			//获取订单信息
//			$info = $pay->getInfo();
//
//			if ($info['status']) {
//				$payinfo = M("Pay")->field(true)->where(array('out_trade_no' => $info['out_trade_no']))->find();
//				if ($payinfo['status'] == 0 && $payinfo['callback']) {
//					session("pay_verify", true);
//					$check = R($payinfo['callback'], array('money' => $payinfo['money'], 'param' => unserialize($payinfo['param'])));
//					if ($check !== false) {
//						M("Pay")->where(array('out_trade_no' => $info['out_trade_no']))->setField(array('update_time' => time(), 'status' => 1));
//					}
//				}
//				if (I('get.method') == "return") {
//					redirect($payinfo['url']);
//				} else {
//					$pay->notifySuccess();
//				}
//			} else {
//				$this->error("支付失败！");
//			}
//		} else {
//			E("Access Denied");
//		}
//	}
//
//	public function index() {
//		if (IS_POST) {
//			//页面上通过表单选择在线支付类型，支付宝为alipay 财付通为tenpay
//			$paytype = I('post.paytype');
//
//			$pay = new \Think\Pay($paytype, C('payment.' . $paytype));
//			$order_no = $pay->createOrderNo();
//			$vo = new \Think\Pay\PayVo();
//			$vo->setBody("商品描述")
//				->setFee(I('post.money')) //支付金额
//				->setOrderNo($order_no)
//				->setTitle("巨树村")
//				->setCallback("Store/Index/pay")
//				->setUrl(U("Store/Index/index"))
//				->setParam(array('order_id' => "goods1业务订单号"));
//			echo $pay->buildRequestForm($vo);
//		} else {
//			//在此之前goods1的业务订单已经生成，状态为等待支付
//			$this->display();
//		}
//	}
//
//	/**
//	 * 订单支付成功
//	 * @param type $money
//	 * @param type $param
//	 */
//	public function pay($money, $param) {
//		if (session("pay_verify") == true) {
//			session("pay_verify", null);
//			//处理goods1业务订单、改名good1业务订单状态
//			$data['is_pay'] = 1;
//			$data['notify_time'] = $param['notify_time'];
//			$data['margin_order'] = $param['out_trade_no'];
//			$data['margin'] = $param['total_fee'];
//			$data['buyer_email'] = $param['buyer_email'];
//			$data['trade_no'] = $param['trade_no'];
//			$store_id=M('merchant')->where('store_name ='."'".$param['subject']."'")->getField('id');
//			M('store_detail')->where('storeid='.$store_id)->data($data)->save();
//		} else {
//			E("Access Denied");
//		}
//	}
}