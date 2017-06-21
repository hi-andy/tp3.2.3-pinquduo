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
    public function set_chat($msg_id='', $timestamp='', $direction='', $to='', $from='', $chat_type='', $payload='', $status='')
    {
        if ($msg_id && $timestamp && $direction && $to && $from && $chat_type && $payload != '' && $status != '') {
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
                // 暂不使用缓存
                //redislist("chatlist", json_encode($msgdata));//写入redis队列

                $sql = "INSERT INTO tp_chat(msg_id, timestamp, direction, tos, froms, chat_type, payload, status) VALUES";
                $sql .= "('{$msg_id}',{$timestamp},'{$direction}','{$to}','{$from}','{$chat_type}','{$payload}',{$status})";
                M()->query($sql);

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
     * @param string $to 接收人
     * @param $from $to 发送人
     * @param string $chat_type 用来判断单聊还是群聊。chat: 单聊；groupchat: 群聊
     * @param int $page
     * @param int $pagesize
     */
    public function get_chat($to='', $from='', $chat_type='', $page=0, $pagesize=20){
        if ($to && $from && $chat_type) {
            $page *= $pagesize;
            $in_msg_id = "0,";
            $where = "((tos = '{$to}' and froms = '{$from}') or (tos = '{$from}' and froms = '{$to}')) and chat_type = '{$chat_type}' and status <> 2";
            $result = M('chat','','DB_CONFIG2')->where($where)->order('timestamp desc')->limit($page,$pagesize)->select();
            foreach ($result as $key => $value){
                $data[$key]['msg_id'] = $value['msg_id'];
                $data[$key]['timestamp'] = $value['timestamp'];
                $data[$key]['to'] = $value['tos'];
                $data[$key]['from'] = $value['froms'];
                $data[$key]['chat_type'] = $value['chat_type'];
                $data[$key]['payload'] = $value['payload'];
                $data[$key]['status'] = $value['status'];
                $in_msg_id .= "'{$value['msg_id']}',";
            }
            $in_msg_id = substr($in_msg_id, 0, -1);
            M('chat')->where("msg_id in({$in_msg_id})")->save(array('status'=>1));
            json('读取成功',$data);
        } else {
            errjson('缺少参数');
        }
    }

    /**
     * 获取未读列表
     * @param string $user_id //接收方ID
     */
    public function get_unread($user_id='', $page=0, $pageSize=20){
        if ($user_id){
            if (empty(redis('get_unread'))) {
                $page *= $pageSize;
                $data1 = M('', '', 'DB_CONFIG2')->query("SELECT froms,count(tos) as count FROM tp_chat where tos='{$user_id}' and status=0 GROUP BY froms ORDER BY timestamp DESC LIMIT $page, $pageSize");
                $froms='';
                foreach ($data1 as $k1 => $v1) {
                    $data[$k1] = $v1;
                    $data[$k1]['payload'] = M('chat', '', 'DB_CONFIG2')->where(array('froms' => $v1['froms'], 'tos' => $user_id))->order('timestamp desc')->getField('payload');
                    $froms .= "'".$v1['froms']."',";
                }
                $froms = substr($froms, 0, -1);
                if (!empty($froms)) $andwhere = "and froms not in({$froms})";
                $data2 = M('chat', '', 'DB_CONFIG2')->query("SELECT froms,0 as count FROM tp_chat where tos='{$user_id}' and status=1 {$andwhere} GROUP BY froms ORDER BY timestamp DESC");
                foreach ($data2 as $k2 => $v2) {
                    $data[count($data1)+$k2] = $v2;
                    $data[count($data1)+$k2]['payload'] = M('chat', '', 'DB_CONFIG2')->where(array('froms' => $v2['froms'], 'tos' => $user_id))->order('timestamp desc')->getField('payload');
                }
                //$data['page'] = $page;
                // 暂不使用缓存
                //redis('get_unread', serialize($data), 8);
            } else {
                $data = unserialize(redis('get_unread'));
            }
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