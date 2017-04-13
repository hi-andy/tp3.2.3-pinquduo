<?php

namespace pay;

class QQPay
{
    public  $appId      = "1105994087";
    private $appKey     = "xwmkB51fQDnvcnwR";
    private $mchId      = "1447755601";
    private $md5Key     = "u6rAIksPMZVm4V6wc5Xh8STxvxJ3Vym1";
    private $logPath    ="./";

    private $certFile;
    private $keyFile;
    private $cacertFile;
    private $opUserPassMd5;
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
        if (defined(RUNTIME_PATH)) {
            $this->logPath = RUNTIME_PATH;
        }
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
            "spbill_create_ip" => $_SERVER["REMOTE_ADDR"],
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

    public function createPayScript($params)
    {
        $scriptTpl = "<script>\n";
        $scriptTpl .= "(function(){\n";
        $scriptTpl .= "    mqq.tenpay.pay({tokenId: '%s',appInfo: '%s'}, \n";
        $scriptTpl .= "        function(result, resultCode){\n";
        $scriptTpl .= "            if ((result && result.resultCode === 0) || (resultCode === 0)) {\n";
        $scriptTpl .="                 alert('支付成功');\n";
        $scriptTpl .="             } else {\n";
        $scriptTpl .="                  if (result.match(/permission/)) {\n";
        $scriptTpl .="                     alert('您的QQ钱包需要实名认证才能使用');\n";
        $scriptTpl .="                  } else {\n";
        $scriptTpl .="                      alert('支付失败');\n";
        $scriptTpl .="                  }\n";
        $scriptTpl .="             }\n";
        $scriptTpl .="         }\n";
        $scriptTpl .= ")})();</script>\n";

        return sprintf($scriptTpl, $params["tokenId"], $params["appInfo"]);
    }

    /**
     * 支付回调通知
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
        $result = self::curlHttps($url, $postStr, $this->certFile, $this->keyFile, $this->cacertFile, $header);

        $this->log($result, "refund-response");

        if ($result) {
            $xml = simplexml_load_string($result);
            if ($xml->return_code == "SUCCESS") {
                if ($xml->result_code == "SUCCESS") {
                    $data = array(
                        'transactionId' => $xml->transaction_id + '', // QQ钱包订单号
                        'orderSn'       => $xml->out_trade_no + '', // 订单号
                        'totalFee'      => $xml->total_fee + '', // 订单总金额
                        'refundSn'      => $xml->out_refund_no + '', // 商户退款单号
                        'refundId'      => $xml->refund_id + '', // QQ钱包退款单号
                        'refundFee'     => $xml->refund_fee + '' // 退款金额，单位：分
                    );
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
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
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
}

