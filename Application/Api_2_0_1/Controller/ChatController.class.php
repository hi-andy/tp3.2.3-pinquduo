<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/31
 * Time: 上午10:18
 */

namespace Api_2_0_1\Controller;


class ChatController extends BaseController
{
    /**
     * 自动脚本保存消息队列
     */
    public function set_msglist(){
        $num = 500;
        $values  = "";
        $sql = "INSERT INTO tp_chat(f_userid, userid, data, status) VALUES";
        for ($i=0; $i<$num; $i++) {
            $msg = (array) $this->get_decrypt(redislist("msglist"));
            if (!empty($msg)) {
                $values .= "({$msg['f_userid']},{$msg['userid']},{$msg['data']},{$msg['status']}),";
            }
        }
        $values = substr($values, 0, -1);
        if ($values) {
            $sql .= $values;
            M("chat")->query($sql);
        }
    }
}