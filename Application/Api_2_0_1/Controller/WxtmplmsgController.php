<?php
/**
 * 微信模板消息推送
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/6/14
 * Time: 上午10:53
 */

namespace Api_2_0_1\Controller;


class Wxtmplmsg
{

    /**
     * 订单支付成功
     * @param $openid
     * @param $orderMoneySum 支付金额
     * @param $orderProductName 商品信息
     * @param $remark 备注
     */
    public function order_payment_success($openid,$orderMoneySum,$orderProductName,$remark){
        $template_id = "YRyhnjefOtwOxIPtz34WuRhBkM4PfO-SXIv1NxgqDJE";
        $pagepath = "user_center.html";
        $data = array(
            'first' => '您购买的商品已支付成功',
            'orderMoneySum' => $orderMoneySum,
            'orderProductName' => $orderProductName,
            'remark' => $remark
        );
        $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 拼团成功通知
     * @param $openid
     * @param $keyword1 商品
     * @param $keyword2 拼团成员
     * @param $keyword3 发货时间
     * @param $remark 备注
     */
    public function spell_success($openid,$keyword1,$keyword2,$keyword3,$remark){
        $template_id = "L22LKQdaEErpxPaXHIn1U0sGc9yJ-q1jKWeF4kgU70E";
        $pagepath = "goods_order.html?id=2";
            $data = array(
                'first' => '您购买的商品已拼团成功',
                'keyword1' => $keyword1,
                'keyword2' => $keyword2,
                'keyword3' => $keyword3,
                'remark' => $remark
        );
        $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 拼团失败通知
     * @param $openid
     * @param $keyword1 拼团商品
     * @param $keyword2 商品金额
     * @param $keyword3 退款金额
     * @param $remark 备注
     */
    public function failed_to_spell($openid,$keyword1,$keyword2,$keyword3,$remark){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "goods_order.html?id=2";
        $data = array(
            'first' => '您购买的商品已拼团失败',
            'keyword1' => $keyword1,
            'keyword2' => $keyword2,
            'keyword3' => $keyword3,
            'remark' => $remark
        );
        $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 商品发货通知
     * @param $openid
     * @param $keyword1 快递公司
     * @param $keyword2 快递单号
     * @param $keyword3 商品信息
     * @param $keyword4 商品数量
     * @param $remark 备注
     */
    public function commodity_delivery($openid,$keyword1,$keyword2,$keyword3,$keyword4,$remark){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "goods_order.html?id=2";
        $data = array(
            'first' => '您购买的商品已发货',
            'keyword1' => $keyword1,
            'keyword2' => $keyword2,
            'keyword3' => $keyword3,
            'keyword4' => $keyword4,
            'remark' => $remark
        );
        $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 退款通知
     * @param $openid
     * @param $reason 退款原因
     * @param $refund 退款金额
     * @param $remark 备注
     */
    public function refund($openid,$reason,$refund,$remark){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "goods_order.html?id=2";
        $data = array(
            'first' => '您购买的商品已退款',
            'reason' => $reason,
            'refund' => $refund,
            'remark' => $remark
        );
        $this->push($template_id,$pagepath,$data,$openid);
    }

    public function push($template_id,$pagepath,$data,$openid){
        require_once("plugins/payment/weixin/lib/WxPay.Api.php");
        $client_credential = (array) json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".\WxPayConfig::$appid."&secret=".\WxPayConfig::$appsecret));
        $access_token = $client_credential['access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $push_data = array(
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => C("SHARE_URL"),
            'miniprogram' => array(
                'appid' => \WxPayConfig::$appid,
                'pagepath' => $pagepath
            ),
            'data' => $data
        );
        $result = async_get_url($url,urldecode(json_encode($push_data)));
        return $result;
    }

}