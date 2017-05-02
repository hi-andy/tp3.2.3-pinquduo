<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/2
 * Time: 上午11:12
 */

namespace Api\Controller;
use Think\Controller;

class ChatController extends BaseController
{
    public function login($class="", $uid=""){
        if ($class && $uid) {
            $result = array(
                "action" => "login",
                "userid" => $class . $uid,
            );
            $result = $this->base64_json($result);
        } else {
            $result = $this->errjson("参数错误");
        }
        echo $result;
    }

    /**
     * 加密
     * @param string $str
     * @return string
     */
    public function base64_json($str=""){
        return transposition(base64_encode(json_encode($str)));
    }

    /**
     * 解密
     * @param string $str
     * @return string
     */
    public function base64_json_decode($str=""){
        return json_decode(base64_decode(transposition($str)));
    }

    /**
     * 输出错误信息
     * @param string $str
     * @return string
     */
    public function errjson($str=""){
        return json_encode(array('status' => -1, 'msg' => $str));
    }
}