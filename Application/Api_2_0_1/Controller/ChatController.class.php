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
     * @param string $status 状态 0未查看 1查看 2删除
     */
    public function set_chat($msg_id='', $timestamp='', $direction='', $to='', $from='', $chat_type='', $payload='', $status=''){
        if ($msg_id && $timestamp && $direction && $to && $from && $chat_type && $payload && $status != '') {
            $chatcount = M('chat','','DB_CONFIG2')->where(array('msg_id'=>array('eq',$msg_id)))->count();
            if ($chatcount < 1) {
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
                redislist("chatlist", json_encode($msgdata));//写入redis队列
                json('保存成功',$msgdata);
            } else {
                errjson('msg_id已存在');
            }
        } else {
            errjson('缺少参数');
        }
    }

    /**
     * 自动脚本保存消息队列
     */
    public function auto_set_chatlist(){
        $num = 100;//每次读取N条
        $values  = "";
        $sql = "INSERT INTO tp_chat(msg_id, timestamp, direction, tos, froms, chat_type, payload, status) VALUES";
        for ($i=0; $i<$num; $i++) {
            $msg = (array) json_decode(redislist("chatlist"));//读取redis队列
            if ($msg) {
                $values .= "('{$msg['msg_id']}',{$msg['timestamp']},'{$msg['direction']}','{$msg['to']}','{$msg['from']}','{$msg['chat_type']}','{$msg['payload']}',{$msg['status']}),";
            }
        }
        $values = substr($values, 0, -1);
        if ($values) {
            $sql .= $values;
            M()->query($sql);
        }
    }

    /**
     * 读取聊天记录
     * @param string $user_id 接收人
     * @param string $chat_type 用来判断单聊还是群聊。chat: 单聊；groupchat: 群聊
     * @param int $page
     * @param int $pagesize
     */
    public function get_chat($user_id='', $chat_type='', $page=0, $pagesize=20){
        if ($user_id && $chat_type) {
            $in_msg_id = "0,";
            $where = "(tos = '{$user_id}' or froms = '{$user_id}') and chat_type = '{$chat_type}' and status <> 2";
            $result = M('chat','','DB_CONFIG2')->where($where)->order('timestamp asc')->limit($page,$pagesize)->select();
            foreach ($result as $key => $value){
                $data[$key]['msg_id'] = $value['msg_id'];
                $data[$key]['timestamp'] = $value['timestamp'];
                $data[$key]['to'] = $value['tos'];
                $data[$key]['from'] = $value['froms'];
                $data[$key]['chat_type'] = $value['chat_type'];
                $data[$key]['payload'] = $value['payload'];
                $data[$key]['status'] = $value['status'];
                if ($value['tos'] == $user_id) $in_msg_id .= "'{$value['msg_id']}',";
            }
            $in_msg_id = substr($in_msg_id, 0, -1);
            M('chat')->where("msg_id in({$in_msg_id})")->save(array('status'=>1));
            json('读取成功',$data);
        } else {
            errjson('缺少参数');
        }
    }

    public function get_unread($user_id=''){
        if ($user_id){
            $data = M('','','DB_CONFIG2')->query("SELECT froms,count(tos) as count FROM tp_chat where tos='{$user_id}' and status=0 GROUP BY froms ORDER BY timestamp DESC");
            json('读取成功',$data);
        } else {
            errjson('缺少参数');
        }
    }

    /**
     * 删除聊天记录
     * @param string $msg_id
     */
    public function del_chat($msg_id=''){
        if ($msg_id){
            $result = M('chat')->where(array('msg_id'=>array('eq',$msg_id)))->save(array('status'=>2));
            if ($result) {
                json('删除成功',$result);
            } else {
                errjson('删除失败');
            }
        } else {
            errjson('缺少参数');
        }
    }
}