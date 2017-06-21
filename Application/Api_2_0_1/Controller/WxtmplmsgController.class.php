<?php
/**
 * 微信模板消息推送
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/6/14
 * Time: 上午10:53
 */

namespace Api_2_0_1\Controller;


class WxtmplmsgController
{

    /**
     * 订单支付成功
     * @param $openid
     * @param $orderMoneySum 支付金额
     * @param $orderProductName 商品信息
     * @param $remark 备注
     */
    public function order_payment_success($openid,$orderMoneySum,$orderProductName,$Remark='还有更省钱的办法？请点击此了解省钱大法'){
        $template_id = "YRyhnjefOtwOxIPtz34WuRhBkM4PfO-SXIv1NxgqDJE";
        $pagepath = "save_money.html";
        $data = array(
            'first' => array(
                'value' => urlencode('商品购买成功，商家已经为您准备货物中，请耐心等待'),
                'color' => '#FF0000'
            ),
            'orderMoneySum' => array(
                'value' => urlencode($orderMoneySum),
                'color' => '#000000'
            ),
            'orderProductName' => array(
                'value' => urlencode($orderProductName),
                'color' => '#000000'
            ),
            'Remark' => array(
                'value' => urlencode($Remark),
                'color' => '#436EEE'
            )
        );
        return $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 拼团成功通知
     * @param $openid
     * @param $keyword1 商品
     * @param $keyword2 拼团成员
     * @param $keyword3 发货时间
     * @param $remark 备注
     */
    public function spell_success($openid,$keyword1,$keyword2,$keyword3='商家将于2天内发货，如果未按承诺时间发货，平台将对商家进行处罚。',$Remark='【VIP专享】9.9元购买（电蚊拍充电式灭蚊拍、COCO香水型洗衣液、20软毛牙刷）'){
        $template_id = "L22LKQdaEErpxPaXHIn1U0sGc9yJ-q1jKWeF4kgU70E";
        $pagepath = "special99.html";
        $data = array(
            'first' => array(
                'value' => urlencode('您购买的商品已拼团成功'),
                'color' => '#FF0000'
            ),
            'keyword1' => array(
                'value' => urlencode($keyword1),
                'color' => '#000000'
            ),
            'keyword2' => array(
                'value' => urlencode($keyword2),
                'color' => '#000000'
            ),
            'keyword3' => array(
                'value' => urlencode($keyword3),
                'color' => '#000000'
            ),
            'remark' => array(
                'value' => urlencode($Remark),
                'color' => '#436EEE'
            )
        );
        return $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 拼团失败通知
     * @param $openid
     * @param $keyword1 拼团商品
     * @param $keyword2 商品金额
     * @param $keyword3 退款金额
     * @param $remark 备注
     */
    public function failed_to_spell($openid,$keyword1,$keyword2,$keyword3,$Remark){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "goods_order.html?id=2";
        $data = array(
            'first' => array(
                'value' => urlencode('您购买的商品已拼团失败'),
                'color' => '#FF0000'
            ),
            'keyword1' => array(
                'value' => urlencode($keyword1),
                'color' => '#000000'
            ),
            'keyword2' => array(
                'value' => urlencode($keyword2),
                'color' => '#000000'
            ),
            'keyword3' => array(
                'value' => urlencode($keyword3),
                'color' => '#000000'
            ),
            'remark' => array(
                'value' => urlencode($Remark),
                'color' => '#436EEE'
            )
        );
        return $this->push($template_id,$pagepath,$data,$openid);
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
    public function commodity_delivery($openid,$keyword1,$keyword2,$keyword3,$keyword4,$Remark='【VIP专享】9.9元购买（电蚊拍充电式灭蚊拍、COCO香水型洗衣液、20软毛牙刷）'){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "special99.html";
        $data = array(
            'first' => array(
                'value' => urlencode('您购买的商品已拼团失败'),
                'color' => '#FF0000'
            ),
            'keyword1' => array(
                'value' => urlencode($keyword1),
                'color' => '#000000'
            ),
            'keyword2' => array(
                'value' => urlencode($keyword2),
                'color' => '#000000'
            ),
            'keyword3' => array(
                'value' => urlencode($keyword3),
                'color' => '#000000'
            ),
            'keyword4' => array(
                'value' => urlencode($keyword4),
                'color' => '#000000'
            ),
            'remark' => array(
                'value' => urlencode($Remark),
                'color' => '#436EEE'
            )
        );
        return $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 退款通知
     * @param $openid
     * @param $reason 退款原因
     * @param $refund 退款金额
     * @param $remark 备注
     */
    public function refund($openid,$reason,$refund,$Remark='查看更多的好商品，选择高品质的商品就在趣多严选'){
        $template_id = "nmK37ic6m9mqUFIRZECAjR_26K3oUbhbNPL3KjZfAro";
        $pagepath = "strict_selection.html";
        $data = array(
            'first' => array(
                'value' => urlencode('您购买的商品已退款'),
                'color' => '#FF0000'
            ),
            'reason' => array(
                'value' => urlencode($reason),
                'color' => '#000000'
            ),
            'refund' => array(
                'value' => urlencode($refund),
                'color' => '#000000'
            ),
            'remark' => array(
                'value' => urlencode($Remark),
                'color' => '#436EEE'
            )
        );
        return $this->push($template_id,$pagepath,$data,$openid);
    }

    /**
     * 推送
     * @param $template_id 模板ID
     * @param $pagepath 跳转链接(不需要域名，例如 index.php?m=1)
     * @param array $data 推送内容
     * @param $openid
     * @return array
     */
    public function push($template_id,$pagepath,$data=array(),$openid){
        require_once("plugins/payment/weixin/lib/WxPay.Api.php");
        $client_credential = (array) json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".\WxPayConfig::$appid."&secret=".\WxPayConfig::$appsecret));
        $access_token = $client_credential['access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $push_data = array(
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => C("SHARE_URL").'/'.$pagepath,
            'data' => $data
        );
        $result = http_request($url,urldecode(json_encode($push_data)));
        return $result;
    }

}