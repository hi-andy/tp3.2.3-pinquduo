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
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 保存聊天
     * @param string $msg_id 消息ID
     * @param string $timestamp 消息发送时间
     * @param string $direction 方向
     * @param string $to 接收人
     * @param string $from 发送人
     * @param string $chat_type 用来判断单聊还是群聊。chat: 单聊；groupchat: 群聊
     * @param string $payload 内容
     * @param string $status 状态1查看0未查看
     */
    public function set_chat($msg_id='', $timestamp='', $direction='', $to='', $from='', $chat_type='', $payload='', $status=''){
        if ($msg_id && $timestamp && $direction && $to && $from && $chat_type && $payload && $status) {
            $msgdata = array(
                'msg_id' => $msg_id,
                'timestamp' => $timestamp,
                'direction' => $direction,
                'to' => $to,
                'from' => $from,
                'chat_type' => $chat_type,
                'payload' => $payload,
                'status' => $status
            );
            redislist("chatlist", serialize($msgdata));//写入redis队列
            $this->json('保存成功',$msgdata);
        } else {
            $this->errjson('缺少参数');
        }
    }

    /**
     * 自动脚本保存消息队列
     */
    public function auto_set_chatlist(){
        $num = 500;//每次读取500条
        $values  = "";
        $sql = "INSERT INTO tp_chat(msg_id, timestamp, direction, to, from, chat_type, payload, status) VALUES";
        for ($i=0; $i<$num; $i++) {
            $msg = (array) unserialize(redislist("chatlist"));//读取redis队列
            if (!empty($msg)) {
                $values .= "({$msg['msg_id']},{$msg['timestamp']},{$msg['direction']},{$msg['to']},{$msg['from']},{$msg['chat_type']},{$msg['payload']},{$msg['status']}),";
            }
        }
        $values = substr($values, 0, -1);
        if ($values) {
            $sql .= $values;
            M("chat")->query($sql);
        }
    }

    /**
     * 读取聊天记录
     * @param string $to 接收人
     * @param string $chat_type 用来判断单聊还是群聊。chat: 单聊；groupchat: 群聊
     * @param int $page
     * @param int $pagesize
     */
    public function get_chat($to='', $chat_type='', $page=1, $pagesize=20){
        if ($to && $chat_type) {
            $result = M('chat')->where(array('user_id'))->limit($page,$pagesize)->select();
            $this->json('读取成功',$result);
        } else {
            $this->errjson('缺少参数');
        }
    }

    /**
     * 正常返回
     * @param string $msg
     * @param string $result
     * @return string
     */
    public function json($msg="", $result=""){
        echo json_encode(array('status' => 1, 'msg' => $msg, 'result' => $result));
    }

    /**
     * 输出错误信息
     * @param string $str
     * @return string
     */
    public function errjson($msg=""){
        echo json_encode(array('status' => -1, 'msg' => $msg, 'result' => ''));
    }
}