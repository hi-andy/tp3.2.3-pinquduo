<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/7/6
 * Time: 10:48
 *
 * 微信相关接口调用控制器
 */

namespace Api_2_0_2\Controller;


use Think\Controller;

class WechatController extends Controller
{
    public function  __construct() {
        parent::__construct();

        require_once("plugins/payment/weixin/lib/WxPay.Config.php"); // 微信配置信息
        require_once("plugins/payment/weixin/lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("plugins/payment/weixin/example/WxPay.NativePay.php");
        require_once("plugins/payment/weixin/example/WxPay.JsApiPay.php");
    }

    /**
     * 获取用户基本信息
     * 主要是查询用户是否关注公众号
     * 供微信端调用
     * @return mixed|string
     */
    public function getUserInfo()
    {
        // 获取用户openid, 以下方法获取出错
        // $tools = new \JsApiPay();
        // $openId = $tools->GetOpenid();
        //　微信前端传递
        $openId = I('openId');
        $client_credential = (array) json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".\WxPayConfig::$appid."&secret=".\WxPayConfig::$appsecret));
        $access_token = $client_credential['access_token'];
        // 发送获取用户信息请求
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openId.'&lang=zh_CN';
        $result = http_request($url);
        exit(json_encode($result));
    }

}