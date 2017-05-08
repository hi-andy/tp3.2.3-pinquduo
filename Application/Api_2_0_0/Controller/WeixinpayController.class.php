<?php
namespace Api\Controller;
use Think\Controller;

class WeixinpayController extends BaseController {

    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();

        require_once("plugins/payment/weixin/lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("plugins/payment/weixin/example/WxPay.NativePay.php");
        require_once("plugins/payment/weixin/example/WxPay.JsApiPay.php");
    }

    /**
     * 微信生成预支付订单
     */
    public function addwxorder($order_sn="", $time_expire=30)
    {
        vendor('WxPay.WxPayPubHelper.WxPayPubHelper');
        vendor('WxPay.WxPayPubHelper.log_');

        $log_ = new \Log_();
        $log_->log("=========dowxpay begin===========");

        if(!$order_sn)
            $ordernum = $_GET['order_sn'];
        else
            $ordernum =$order_sn;

        $order = M('order')->where(array('order_sn'=>$ordernum))->find();

        if(!$order){
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在')));
        }

        $unifiedOrder = new \UnifiedOrder_pub();

		$unifiedOrder->setParameter ( "out_trade_no", $order['order_sn'] ); // 商户订单号
		$unifiedOrder->setParameter ( "total_fee",  $order['order_amount']*100); // 总金额，单位是分
//		$unifiedOrder->setParameter ( "total_fee",  1);   // 总金额，单位是分  测试使用
		$unifiedOrder->setParameter ( "notify_url", C('HTTP_URL').'/Api/Weixinpay/endpay'); // 通知地址
		$unifiedOrder->setParameter ("trade_type","APP"); // 交易类型
		$unifiedOrder->setParameter ( "body", '商品支付' ); // 商品描述
		$unifiedOrder->setParameter ( "time_start", date ( "YmdHis" ) ); // 交易起始时间
		$unifiedOrder->setParameter ( "time_expire", date ( "YmdHis", time () + $time_expire ) ); // 交易结束时间
		$unifiedOrder->setParameter ( "product_id", $ordernum ); // 商品ID
		$result = $unifiedOrder->getResult();

        if($result['return_code']!='SUCCESS'){
            exit(json_encode(array('status'=>-1,'msg'=>'支付异常')));
        }
        $time=time();

        $returndata = new \Common_util_pub();
        $nonceStr = $returndata->createNoncestr();
        $signdata['appid']    =  \WxPayConf_pub::APPID;
        $signdata['partnerid'] =  \WxPayConf_pub::MCHID;
        $signdata['prepayid'] = $result['prepay_id'];
        $signdata['timestamp']= $time;
        $signdata['package']  = "Sign=WXPay";
        $signdata['noncestr'] = $nonceStr;

        $sign=$returndata->getSign($signdata);

        $orderdetail['appid'] = \WxPayConf_pub::APPID;
        $orderdetail['parentid'] = \WxPayConf_pub::MCHID;
        $orderdetail['prepayid'] = $result['prepay_id'];
        $orderdetail['timestamp'] = $time;
        $orderdetail['packages'] = "Sign=WXPay";
        $orderdetail['sign'] = $sign;
        $orderdetail['noncestr'] = $nonceStr;

        if($_GET['order_sn'])
        {
            exit(json_encode(array('status'=>1,'msg'=>'预支付订单生成成功','data'=>$orderdetail)));
        }else {
            return $orderdetail;
        }
    }

    function getJSAPI($order, $time_expire=30){
        header("Access-Control-Allow-Origin:*");
        if($order['prom_id']){
            $prom_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
            $type = $prom_info['mark']>0?1:0;
            $go_url ='http://wx.pinquduo.cn/order_detail.html?order_id='.$prom_info['order_id'].'&type='.$type.'&user_id='.$order['user_id'];
        }else{
            $go_url ='http://wx.pinquduo.cn/order_detail.html?order_id='.$order['order_id'].'&type=2&user_id='.$order['user_id'];;
        }

        $back_url = "http://wx.pinquduo.cn/goods_detail.html?goods_id={$order['goods_id']}";

        //①、获取用户openid
        $tools = new \JsApiPay();
        //$openId = $tools->GetOpenid();

        $openId = M('users')->where(array("user_id"=>array("eq",$order['user_id'])))->field('openid')->find();
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn']);
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + $time_expire));
        $input->SetGoods_tag("tp_wx_pay");
        $input->SetNotify_url(C('HTTP_URL').'/Api/Weixinpay/js_endpay');
        if($_REQUEST['is_mobile_browser']==1){
            $input->SetTrade_type("MWEB");
            $input->SetSpbill_create_ip($this->get_real_ip());
        }else{
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId['openid']);
        }
        $order2 = \WxPayApi::unifiedOrder($input);
        //redis("wxpay", serialize($order2), 6000);
        //redisdelall("wxpay");
        if($_REQUEST['is_mobile_browser']==1) {
            echo $this->get_real_ip();
            var_dump($order2);
            die;
        }
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        $html = <<<EOF
	<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',$jsApiParameters,
			function(res){
				WeixinJSBridge.log(res.err_msg);
				 if(res.err_msg == "get_brand_wcpay_request:ok") {
				    location.href='$go_url';
				 }else if(res.err_msg == "get_brand_wcpay_request:cancel")  {
                     location.href='$back_url';
                 }else{
				    location.href='$back_url';
				 }
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	callpay();
	</script>
EOF;
//        return $html;
        echo $html;
        die;
        //$this->assign('js_api',$html);
        //$this->display();
    }

    public function test(){
        echo $this->get_real_ip();
    }

    public function get_real_ip(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
        {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip)
            {
                array_unshift($ips, $ip); $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++)
            {
                if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i]))
                {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    /**
     * 微信支付回调函数
     */
    public function endpay(){
        vendor('WxPay.WxPayPubHelper.WxPayPubHelper');
        vendor('WxPay.WxPayPubHelper.log_');

        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if(!$xml) $xml=file_get_contents("php://input");
        $notify->saveData($xml);

        //以log文件形式记录回调信息
        $log_ = new \Log_();
        $log_name=dirname(__FILE__)."/notify_url.log";//log文件路径

        $log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

        if($notify->checkSign() == TRUE)
        {
            M()->startTrans();

            //更新商户状态
            $order_sn = $notify->data['out_trade_no'];

            $where="order_sn = $order_sn";
            $order=M('order')->where($where)->find();

            if($order['pay_status']==1){
                $notify->setReturnParameter("return_code","SUCCESS");
                exit();
            }
            $res = $this->changeOrderStatus($order);

            if(!$res)
            {
                $log_->log_result($log_name,"【修改订单状态】:\n".$res."\n");
                M()->rollback();
                exit();
            }

            if($order['prom_id']){
                $res2 = $this->Join_Prom($order['prom_id']);
                $log_->log_result($log_name,"【团修改】:\n".$res2."\n");
                if($res2){
                    $group_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
                    M('group_buy')->where(array('id'=>$group_info['mark']))->setInc('order_num');

                    if($group_info['mark']>0){
                        $nums = M('group_buy')->where('(`mark`='.$group_info['mark'].' or `id`='.$group_info['mark'].') and `is_pay`=1')->count();
                        M('group_buy')->where(array('mark'=>$group_info['mark']))->save(array('order_num'=>$nums));
                        if(($nums)==$group_info['goods_num'])
                        {
                            $Goods = new GoodsController();
                            $Goods->getFree($group_info['mark']);
                            M()->commit();
                        }
                        M()->commit();
                    }
                    M()->commit();
                }else{
                    M()->rollback();
                    exit();
                }
            }else{
                M()->commit();
            }
            $log_->log_result($log_name,"【成功】");
            $notify->setReturnParameter("return_code","SUCCESS");
        }else{
            $log_->log_result($log_name,"签名验证:".$notify->checkSign());
        }
    }

    /*
     * 微信公众号支付 回调
     */
    public function js_endpay(){
        vendor('WxPay.WxPayPubHelper.WxPayPubHelper');
        vendor('WxPay.WxPayPubHelper.log_');

        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if(!$xml) $xml=file_get_contents("php://input");
        $notify->saveData($xml);

        //以log文件形式记录回调信息
        $log_ = new \Log_();
        $log_name=dirname(__FILE__)."/notify_url.log";//log文件路径

        $log_->log_result($log_name,"【接收到WX的notify通知】:\n".$notify->data['out_trade_no']."\n");

        M()->startTrans();

        //更新商户状态
        $order_sn = $notify->data['out_trade_no'];

        $where="order_sn = $order_sn";
        $order=M('order')->where($where)->find();

        if($order['pay_status']==1){
            $notify->setReturnParameter("return_code","SUCCESS");
            exit();
        }

        $res = $this->changeOrderStatus($order);

        if(!$res)
        {
            $log_->log_result($log_name,"【WX修改订单状态】:\n".$res."\n");
            M()->rollback();
            exit();
        }

        if($order['prom_id']){
            $res2 = $this->Join_Prom($order['prom_id']);
            $log_->log_result($log_name,"【WX团修改】:\n".$res2."\n");
            if($res2){
                $group_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
                M('group_buy')->where(array('id'=>$group_info['mark']))->setInc('order_num');
                $log_->log_result($log_name,"【WX】:\n".$group_info."\n");
                if($group_info['mark']>0){
                    $nums = M('group_buy')->where('(`mark`='.$group_info['mark'].' or `id`='.$group_info['mark'].') and `is_pay`=1')->count();
                    M('group_buy')->where(array('mark'=>$group_info['mark']))->save(array('order_num'=>$nums));
                    if(($nums)==$group_info['goods_num'])
                    {
                        $Goods = new GoodsController();
                        $Goods->getFree($group_info['mark']);
                        $log_->log_result($log_name,"【getFree】:\n".$group_info['mark']."\n");
                        M()->commit();
                    }
                    M()->commit();
                }
                M()->commit();
            }else{
                M()->rollback();
                exit();
            }
        }else{
            M()->commit();
        }
        $log_->log_result($log_name,"【WX成功】");
        $notify->setReturnParameter("return_code","SUCCESS");
    }

    public function changeOrderStatus($order)
    {
        $data['pay_status'] = 1;
        if(!empty($order['prom_id']))
        {
            $data['order_type'] = 11;
        }else{
            $data['order_type'] = 2;
        }
        //销量、库存
        M('goods')->where('`goods_id` = '.$order['goods_id'])->setInc('sales',$order['num']);
        M('merchant')->where('`id`='.$order['store_id'])->setInc('sales',$order['num']);
        //商品规格库存
        $spec_name = M('order_goods')->where('`order_id`='.$order['order_id'])->field('spec_key')->find();
        M('spec_goods_price')->where("`goods_id`=$order[goods_id] and `key`='$spec_name[spec_key]'")->setDec('store_count',$order['num']);
        $res = M('order')->where('`order_id`='.$order['order_id'])->data($data)->save();
        return $res;
    }

    //开团 参团的时候在支付完成时将is_pay字段改变，标示加入团成功
    public function Join_Prom($order_id)
    {
        $data['is_pay']=1;
        $res = M('group_buy')->where('`id`='.$order_id)->data($data)->save();
        return $res;
    }




}
