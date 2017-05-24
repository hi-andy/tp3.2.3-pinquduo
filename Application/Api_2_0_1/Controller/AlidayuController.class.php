<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/4/15
 * Time: 下午4:55
 */

namespace Api_2_0_1\Controller;
use Think\Controller;
vendor("taobao-sdk.TopSdk");

//阿里大于
class AlidayuController extends BaseController
{
    const APPKEY = "23754686";
    const SECRETKEY = "7373475ca36dbd69aa25d6abdfdf3ea6";

    /**
     * 发送短信
     * @param $mobile 手机号码
     * @param $ctype 内容类型
     * @param $content 短信内容
     * @param $TemplateCode 模版ID
     * @param $SmsType 短信类型
     * @param $SignName 短信签名
     * @param $product 产品
     * @return mixed
     */
    public function sms($mobile, $ctype, $content, $TemplateCode, $SmsType, $SignName, $product)
    {
        $c = new \TopClient();
        $c->appkey = AlidayuController::APPKEY;
        $c->secretKey = AlidayuController::SECRETKEY;
        $req = new \AlibabaAliqinFcSmsNumSendRequest();
        $req->setExtend("");
        $req->setSmsType($SmsType);
        $req->setSmsFreeSignName($SignName);
        $req->setSmsParam("{" . $ctype . ":'" . $content . "',product:'" . $product . "'}");
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($TemplateCode);
        $resp = $c->execute($req);
        return $resp;

    }
}