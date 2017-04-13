<?php

/**
 * 兑吧class
 */
class  Duiba
{

    public function __construct() { }
    /*
    *  md5签名，$array中务必包含 appsecret
    */
    public function sign($array)
    {
        ksort($array);
        $string = "";
        while (list($key, $val) = each($array)) {
            $string = $string . $val;
        }
        return md5($string);
    }

    /*
    *  签名验证,通过签名验证的才能认为是合法的请求
    */
    public function signverify($appsecret, $array)
    {
        $newarray = array();
        $newarray["appsecret"] = $appsecret;
        reset($array);
        while (list($key, $val) = each($array)) {
            if ($key != "sign") {
                $newarray[$key] = $val;
            }
        }
        $sign = $this->sign($newarray);

        if ($sign == $array["sign"]) {
            return true;
        }
        return false;
    }

    /*
    *  生成自动登录地址
    *  通过此方法生成的地址，可以让用户免登录，进入积分兑换商城
    */
    public function buildcreditautologinrequest($appkey, $appsecret, $uid, $credits)
    {
        $url = "http://www.duiba.com.cn/autoLogin/autologin?";
        $timestamp = time() * 1000;
        $array = array("uid" => $uid, "credits" => $credits, "appsecret" => $appsecret, "appkey" => $appkey, "timestamp" => $timestamp);
        $sign = $this->sign($array);
        $url = $url . "uid=" . $uid . "&credits=" . $credits . "&appKey=" . $appkey . "&sign=" . $sign . "&timestamp=" . $timestamp;
        return $url;
    }

    /*
    *  生成订单查询请求地址
    *  ordernum 和 bizid 二选一，不填的项目请使用空字符串
    */
    public function buildcreditorderstatusrequest($appkey, $appsecret, $ordernum, $bizid)
    {
        $url = "http://www.duiba.com.cn/status/orderstatus?";
        $timestamp = time() * 1000 . "";
        $array = array("ordernum" => $ordernum, "bizid" => $bizid, "appkey" => $appkey, "appsecret" => $appsecret, "timestamp" => $timestamp);
        $sign = $this->sign($array);
        $url = $url . "ordernum=" . $ordernum . "&bizid=" . $bizid . "&appkey=" . $appkey . "&timestamp=" . $timestamp . "&sign=" . $sign;
        return $url;
    }

    /*
    *  兑换订单审核请求
    *  有些兑换请求可能需要进行审核，开发者可以通过此api接口来进行批量审核，也可以通过兑吧后台界面来进行审核处理
    */
    public  function buildcreditauditrequest($appkey, $appsecret, $passordernums, $rejectordernums)
    {
        $url = "http://www.duiba.com.cn/audit/apiaudit?";
        $timestamp = time() * 1000 . "";
        $array = array("appkey" => $appkey, "appsecret" => $appsecret, "timestamp" => $timestamp);
        if ($passordernums != null && !empty($passordernums)) {
            $string = null;
            while (list($key, $val) = each($passordernums)) {
                if ($string == null) {
                    $string = $val;
                } else {
                    $string = $string . "," . $val;
                }
            }
            $array["passordernums"] = $string;
        }
        if ($rejectordernums != null && !empty($rejectordernums)) {
            $string = null;
            while (list($key, $val) = each($rejectordernums)) {
                if ($string == null) {
                    $string = $val;
                } else {
                    $string = $string . "," . $val;
                }
            }
            $array["rejectordernums"] = $string;
        }
        $sign = $this->sign($array);
        $url = $url . "appkey=" . $appkey . "&passordernums=" . $array["passordernums"] . "&rejectordernums=" . $array["rejectordernums"] . "&sign=" . $sign . "&timestamp=" . $timestamp;
        return $url;
    }

    /*
    *  积分消耗请求的解析方法
    *  当用户进行兑换时，兑吧会发起积分扣除请求，开发者收到请求后，可以通过此方法进行签名验证与解析，然后返回相应的格式
    *  返回格式为：
    *  {"status":"ok","message":"查询成功","data":{"bizid":"9381"}} 或者
    *  {"status":"fail","message":"","errormessage":"余额不足"}
    */
    public  function parsecreditconsume($appkey, $appsecret, $request_array)
    {
        if ($request_array["appkey"] != $appkey) {
            throw new exception("appkey not match");
        }
        if ($request_array["timestamp"] == null) {
            throw new exception("timestamp can't be null");
        }
        $verify = $this->signverify($appsecret, $request_array);
        if (!$verify) {
            throw new exception("sign verify fail");
        }
        $ret = array("appkey" => $request_array["appkey"], "credits" => $request_array["credits"], "timestamp" => $request_array["timestamp"], "description" => $request_array["description"], "ordernum" => $request_array["ordernum"]);
        return $ret;
    }

    /*
    *  兑换订单的结果通知请求的解析方法
    *  当兑换订单成功时，兑吧会发送请求通知开发者，兑换订单的结果为成功或者失败，如果为失败，开发者需要将积分返还给用户
    */
    public  function parsecreditnotify($appkey, $appsecret, $request_array)
    {
        if ($request_array["appkey"] != $appkey) {
            throw new exception("appkey not match");
        }
        if ($request_array["timestamp"] == null) {
            throw new exception("timestamp can't be null");
        }
        $verify = $this->signverify($appsecret, $request_array);
        if (!$verify) {
            throw new exception("sign verify fail");
        }
        $ret = array("success" => $request_array["success"], "errormessage" => $request_array["errormessage"], "bizid" => $request_array["bizid"]);
        return $ret;
    }
}