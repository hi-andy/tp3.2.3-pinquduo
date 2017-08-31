<?php
namespace Api_2_0_2\Controller;
use Think\Controller;

class QQPayController extends BaseController
{
    public  $appId      = "1105994087";
    private $appKey     = "xwmkB51fQDnvcnwR";
    private $mchId      = "1447755601";
    private $md5Key     = "u6rAIksPMZVm4V6wc5Xh8STxvxJ3Vym1";
    private $logPath    ="./";

    //const CERT_DIR = '/sites/pqd/Application/Common/QQpay/Cacert/';
    const CERT_DIR = '/data/wwwroot/default/Application/Common/QQpay/Cacert/';
    private $certFile   = 'apiclient_cert.pem';
    private $keyFile    = 'apiclient_key.pem';
    private $cacertFile = 'rootca.pem';

    private $opUserPassMd5 = '97b7917a023928a2fb7799589985f4a7';
    /**
     * 构造函数，支付时：配置支付相关参数、日志路径
     *           退款时：配置证书路径，操作员密码md5值
     * @param array $config [description]
     */
    public function __construct($config = array())
    {
        foreach ($config as $k => $v) {
            if (!empty($v)) {
                $this->{$k} = $v;
            }
        }
        //if (defined(RUNTIME_PATH)) {
        //    $this->logPath = RUNTIME_PATH;
        //}
    }

    /**
     * [统一下单接口]
     * @param  [string]  $orderSn  [订单号]
     * @param  [integer] $amount [支付金额，单位：分]
     * @param  [string] $notifyUrl 回调地址
     * @return [array]         [array($code, $msg), $code=0表示成功，此时$msg的值为prepay_id]
     *                          $code 为其他表示失败，$msg是错误描述
     */
    public function unifyOrder($orderSn, $amount, $notifyUrl, $goodsDesc = "商品")
    {
        $url = "https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi";

        $arr = array(
            "appid" => $this->appId,
            "mch_id" => $this->mchId,
            "nonce_str" => $this->getNonceStr(),
            "body"      => "拼趣多-" . $goodsDesc,
            "out_trade_no" => $orderSn,
            "fee_type"  => "CNY",
            "total_fee" => intval($amount), // 支付金额，单位：分
            "spbill_create_ip" => self::getRealIp(),
            "time_start" => date("YmdHis"),
            "trade_type"  => "JSAPI",
            "notify_url"  => $notifyUrl
        );

        $sign = $this->createSign($arr);
        $arr["sign"] = $sign;

        $postStr = $this->arrayToXml($arr);
        //echo $postStr;exit();
        $this->log($postStr, "pay-request");

        $result = self::remotePost($url, $postStr);

        $this->log($result, "pay-response");

        if (!empty($result)) {
            $xml = simplexml_load_string($result);
            if ($xml->return_code == "SUCCESS") {
                if ($xml->result_code == "SUCCESS") {
                    $data = array(
                        "tokenId"   => $xml->prepay_id,
                        "appInfo"   => sprintf("appid#%s|bargainor_id#%s|channel#wallet", $this->appId, $this->mchId)
                    );
                    return array(0, $data);
                } else {
                    return array(2, sprintf("[%s]%s", $xml->err_code, $xml->err_code_desc));
                }
            } else {
                return array(1, sprintf("[%s]%s", $xml->retcode, $xml->retmsg));
            }
        } else {
            return array(-1, "network error");
        }
    }
    public function test(){
	echo "OK";
    }
    public function createPayScript($params)
    {
	$scriptTpl  = '<script src="https://open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>';
        $scriptTpl .= "<script>\n";
        $scriptTpl .= "(function(){\n";
        $scriptTpl .= "    mqq.tenpay.pay({tokenId: '%s',appInfo: '%s'}, \n";
        $scriptTpl .= "        function(result, resultCode){\n";
        $scriptTpl .= "            if ((result && result.resultCode === 0) || (resultCode === 0)) {\n";
        $scriptTpl .="                 setTimeout(function(){window.location.href='%s';}, 1500);\n";
        $scriptTpl .="             } else {\n";
        $scriptTpl .="                  if (result.match(/permission/)) {\n";
        $scriptTpl .="                     alert('您的QQ钱包需要实名认证才能使用');\n";
        $scriptTpl .="                  } else {\n";
        $scriptTpl .="                      alert('支付失败' + result);setTimeout(function(){history.back(-1);},1500);\n";
        $scriptTpl .="                  }\n";
        $scriptTpl .="             }\n";
        $scriptTpl .="         }\n";
        $scriptTpl .= ")})();</script>\n";

        return sprintf($scriptTpl, $params["tokenId"], $params["appInfo"], $params["go_url"]);
    }

    /**
     * QQ支付
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function getQQPay($order=array())
    {
	file_put_contents("log.txt", $_SERVER['HTTP_USER_AGENT'], FILE_APPEND);
	//echo "<script>alert('Sorry, QQ pay Currrently is not enabled!');</script>"; die();
	//if(empty($order)){$order=array('order_sn' => date('YmdHis').mt_rand(1000,9999), 'order_amount' => 0.1);}
        $notifyUrl = C('HTTP_URL').'/Api_2_0_2/QQPay/notify';
        list($code, $data) = $this->unifyOrder($order['order_sn'], $order['order_amount']*100, $notifyUrl);

        if($order['prom_id']){
            $prom_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
            $type = $prom_info['mark']>0?1:0;
            $go_url =C('SHARE_URL').'/order_detail.html?order_id='.$prom_info['order_id'].'&type='.$type.'&user_id='.$order['user_id'];
        }else{
            $go_url =C('SHARE_URL').'/order_detail.html?order_id='.$order['order_id'].'&type=2&user_id='.$order['user_id'];;
        }
	$data['go_url'] = $go_url;

        if ($code == 0) {
            $script = $this->createPayScript($data);
            echo $script;
            die();
        } else {
            echo sprintf('<script>alert("%s");</script>', $data);
            die();
        }
    }

    /**
     * 支付回调
     * @return [type] [description]
     */
    public function notify()
    {
        //$log_ = new \Log_();
        //$log_name = $this->logPath . "qq_notify.log";

        list($code, $data) = $this->checkNotify();
        if ($code !== 0) {
            $this->failAck();
            exit() ;
        }

        M()->startTrans();

        //更新商户状态
        $order_sn = $data['out_trade_no'];

        $where=array('order_sn'=>$order_sn);
        $order=M('order')->where($where)->find();

        if($order['pay_status']==1){
            $this->successAck();
            exit();
        }

        $res = $this->changeOrderStatus($order);

        if(!$res)
        {
            $this->log("【修改订单状态】:\n".$res."\n", "notify");
            M()->rollback();
            exit();
        }

        if($order['prom_id']){
            $res2 = $this->Join_Prom($order['prom_id']);
            $this->log("【团修改】:\n".$res2."\n", "notify");
            if($res2){
                $group_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
                M('group_buy')->where(array('id'=>$group_info['mark']))->setInc('order_num');

                if($group_info['mark']>0){
                    $nums = M('group_buy')->where('(`mark`='.$group_info['mark'].' or `id`='.$group_info['mark'].') and `is_pay`=1')->count();
                    M('group_buy')->where(array('mark'=>$group_info['mark']))->save(array('order_num'=>$nums));
                    //修改逻辑判断-温立涛
                    if(intval($nums)>=$group_info['goods_num'])
                    {
                        $Goods = new BaseController();
                        $Goods->getFree($group_info['mark'],$order);
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
        $log_name = '';
        $this->log($log_name,"【成功】", "notify");
        $this->successAck();
    }

    /**
     * 支付回调通知处理方法
     * @return [array] [array($code, $data) $code=0表示支付成功，非0表示支付失败，$data为失败错误信息 string]
     *                  $code=0时，$data是一个数组，包含bank_type、total_fee、cash_fee、out_trade_no、openid等字段
     */
    public function checkNotify()
    {
        $postData = file_get_contents("php://input");
        if (!empty($postData)) {
        	$this->log($postData, "pay-notify");

            $xml = simplexml_load_string($postData);
            $arr = [];
            foreach ($xml as $k => $v) {
                $arr[$k] = $v . "";
            }

            if ($arr["trade_state"] == "SUCCESS") {
                if ($arr["mch_id"] != $this->mchId) {
                    return array(2, "mch_id incorrect!");
                } else {
                    $qqSign = $arr["sign"];
                    unset($arr["sign"]);

                    $mySign = $this->createSign($arr);
                    if ($qqSign == $mySign) {
                        $fields = array("bank_type", "total_fee", "cash_fee", "coupon_fee", "out_trade_no", "transaction_id", "openid");
                        foreach ($fields as $field) {
                            if (!empty($arr[$field])) {
                                $info[$field] = $arr[$field];
                            }
                        }
                        /**
                        //     "bank_type"      银行类型,
                        //     "total_fee"      订单总金额，单位：分
                        //     "cash_fee"       实际支付金额，单位：分
                        //     "coupon_fee"     本次交易中，QQ钱包提供的优惠金额
                        //     "out_trade_no"   商户系统内部的订单号
                        //     "transaction_id" QQ钱包订单号
                        //     "openid"         用户openid 
                        **/
                        return array(0, $info);
                    } else {
                    	return array(3, "sing error!". $mySign);
                    }
                }
            } else {
                return array(1, "Failed!");
            }
        } else {
            return array(-1, "network error");
        }
    }

    //开团 参团的时候在支付完成时将is_pay字段改变，标示加入团成功
    public function Join_Prom($order_id)
    {
        $data['is_pay']=1;
        $res = M('group_buy')->where('`id`='.$order_id)->data($data)->save();
        return $res;
    }

    /**
     * 向腾讯发送成功通知
     * @return [type] [description]
     */
    public function successAck()
    {
    	echo "<xml><return_code>SUCCESS</return_code></xml>";
    }

    /**
     * 向腾讯发送失败通知
     * @return [type] [description]
     */
    public function failAck()
    {
    	echo "<xml><return_code>FAIL</return_code></xml>";
    }

    /**
     * 退款
     * @param  [type] $orderSn       [description]
     * @param  [type] $fefundSn      [description]
     * @param  [type] $refundFee     [description]
     * @param  [type] $opUserPassMd5 [description]
     * @param  [type] $outTradeNo    [description]
     * @return [type]                [description]
     */
    public function refund($orderSn, $refundSn, $refundFee, $opUserPassMd5 = '', $transactionId = '')
    {
        $url = 'https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi';

        $arr = array(
            'appid'     => $this->appId,
            'mch_id'    => $this->mchId,
            'nonce_str' => $this->getNonceStr(),
            'out_trade_no'  => $orderSn,
            'out_refund_no' => $refundSn,
            'refund_fee'    => intval($refundFee),
            'op_user_id'    => $this->mchId,
            'op_user_password' => $this->opUserPassMd5 ? $this->opUserPassMd5 : $opUserPassMd5
        );
        if (!empty($transactionId)) {
            $arr['transaction_id'] = $transactionId;
        }

        $sign = $this->createSign($arr);
        $arr["sign"] = $sign;

        $postStr = $this->arrayToXml($arr);

        //echo $postStr;exit();
        $this->log($postStr, "refund-request");

        $header = array('Content-Type: application/xml');
        // 下面这三个变量没有用到了。
        $certFilePath = self::CERT_DIR . $this->certFile;
        $keyFilePath  = self::CERT_DIR . $this->keyFile;
        $cacertFilePath = self::CERT_DIR . $this->cacertFile;

        $result = self::curlHttps($url, $postStr, $certFilePath, $keyFilePath, $cacertFilePath, $header);

        $this->log($result, "refund-response");

        if ($result) {
            $xml = simplexml_load_string($result);
            if ($xml->return_code == "SUCCESS") {
                if ($xml->result_code == "SUCCESS") {
                    // $data = array(
                    //     'transactionId' => $xml->transaction_id + '', // QQ钱包订单号
                    //     'orderSn'       => $xml->out_trade_no + '', // 订单号
                    //     'totalFee'      => $xml->total_fee + '', // 订单总金额
                    //     'refundSn'      => $xml->out_refund_no + '', // 商户退款单号
                    //     'refundId'      => $xml->refund_id + '', // QQ钱包退款单号
                    //     'refundFee'     => $xml->refund_fee + '' // 退款金额，单位：分
                    // );
                    $data = [];
                    foreach ($xml as $k => $v) {
                        $data[$k] = $v . '';
                    }
                    return array(0, $data);
                } else {
                    return array(2, sprintf("[%s]%s", $xml->err_code, $xml->err_code_desc));
                }
            } else {
                return array(1, sprintf("[%s]%s", $xml->retcode, $xml->retmsg));
            }
        } else {
            return array(-1, "config or network error");
        }
    }

    /**
     * 执行退款
     * @param  [type] $orderSn       退款订单号
     * @param  [type] $refundFee     退款金额（单位：元）
     * @param  string $opUserPassMd5 [description]
     * @param  string $transactionId [description]
     * @return [type]                [description]
     */
    public function doRefund($orderSn, $refundFee, $opUserPassMd5 = '', $transactionId = '')
    {
        $refundSn = $orderSn . time();

        list ($code, $refundResult) = $this->refund($orderSn, $refundSn, $refundFee*100);
        if ($code != 0) {
            return array(
                'status' => 0,
                'msg'    => '失败：'. $refundResult . "<br/>"
            );
        } else {
            $msg = "业务结果：".$refundResult['result_code']."<br>";
            //$msg .= "错误代码：".$refundResult['err_code']."<br>";
            // $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：".$refundResult['appid']."<br>";
            $msg .= "商户号：".$refundResult['mch_id']."<br>";

            $msg .= "签名：".$refundResult['sign']."<br>";
            $msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
            $msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
            $msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
            $msg .= "微信退款单号：".$refundResult['refund_id']."<br>";
            $msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
            $msg .= "退款金额：".$refundResult['refund_fee']."<br>";
            
            return array(
                'status'    => 1,
                'msg'       => $msg,
                'out_refund_no' => $refundSn
            );
        }
    }
    private function createSign($arr)
    {
        ksort($arr);
        $s = "";
        foreach ($arr as $k => $v) {
            if (!empty($v)) {
                $s .= ("$k=$v&");
            }
        }

        $s .= ("key=" . $this->md5Key);
        return strtoupper(md5($s));
    }

    private function getNonceStr($len = 16)
    {
        return substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyzABCDEFHGIJKLMNOPQRSTUVWXYZ"), 0, $len);
    }

    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                 $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    public function log($str, $note = "request")
    {
        file_put_contents($this->logPath . "qq_pay.log", date("Y-m-d H:i:s") . "\t" . $note . PHP_EOL . $str . PHP_EOL, FILE_APPEND);
    }

    private static function getHttpContent($url, $method = "GET", $postData = array())  
    {  
        $data = "";
      
        if (!empty($url)) {  
            try {  
                $ch = curl_init();  
                curl_setopt($ch, CURLOPT_URL, $url);  
                curl_setopt($ch, CURLOPT_HEADER, false);  
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时  
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                if (strtoupper($method) == "POST") {  
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);  
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);  
                }  
                $data = curl_exec($ch);
                curl_close($ch);  
            } catch (Exception $e) {  
                $data = null;  
            }  
        }  
      
        return $data;
    }

    public static function remoteGet($url, $data = array())
    {
        if (!empty($data)) {
            if (strrpos($url, "?") === false) {
                $url .= "?";
            }
            $url .= http_build_query($data);
        }
        
        return self::getHttpContent($url);
    }

    public static function remotePost($url, $data = array())
    {
        return self::getHttpContent($url, "POST", $data);
    }

    private static function curlHttps($url, $data, $certPemFile, $keyPemFile, $cacertPemFile, $header = array(), $timeout=30)
    {
        //未用到传入参数，发现会找不到文件。在此重新定义。
        $certPemFile = '/data/wwwroot/default/Application/Common/QQpay/Cacert/apiclient_cert.pem';
        $keyPemFile = '/data/wwwroot/default/Application/Common/QQpay/Cacert/apiclient_key.pem';
        $cacertPemFile = '/data/wwwroot/default/Application/Common/QQpay/Cacert/rootca.pem';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch,CURLOPT_SSLCERT, $certPemFile);
        curl_setopt($ch,CURLOPT_SSLKEY, $keyPemFile);
        curl_setopt($ch, CURLOPT_CAINFO, $cacertPemFile);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        if($error=curl_error($ch)){
            die($error);
        }

        curl_close($ch);

        return $response;
    }

    public static function getRealIp(){
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
                if (!preg_match ("/^(10|172\.16|192\.168)\./i", $ips[$i]))
                {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
}

