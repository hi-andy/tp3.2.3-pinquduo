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
            $result = transposition(base64_encode(json_encode($result)));
            //$result = base64_encode(json_encode($result));
        } else {
            $result = json_encode(array('status' => -1, 'msg' => '参数错误'));
        }
        echo $result;
    }
}