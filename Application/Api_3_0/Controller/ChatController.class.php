<?php
/**
 * 聊天消息控制器
 * 同步环信，保存读取聊天消息，最多保存7天。
 * 改用 Redis 数据库存储聊天数据
 * Author: yonghua
 * Time: 2017-8-7
 */
namespace Api_3_0\Controller;

use Think\Cache\Driver\Redis;
use Think\Controller;

class ChatController extends Controller
{
    private $redis = '';
    public function _initialize()
    {
        $this->redis = new Redis();
    }

    /**
     * 存储聊天
     * @param string $timestamp 时间戳
     * @param string $to　接收方
     * @param string $from　发送方
     * @param string $payload　消息内容
     * @param string $status　状态：0未读，1已读
     */
    public function set_chat($timestamp='', $to, $from, $payload='', $status)
    {
        //print_r($this->redis->info()); exit;
        if ($timestamp && $to && $from  && $payload != '' && $status != '') {

            // 存储 key
            $day = date('Ymd', $timestamp);
            $toUser = 'messages:' . $to . '_' . $day;
            $fromUser = 'messages:' . $from . '_' . $day;
            $toFirst = $this->redis->zcard($toUser);
            $fromFirst = $this->redis->zcard($fromUser);

            $msgData = array(
                'timestamp' => $timestamp,
                'to' => $to,
                'from' => $from,
                'payload' => $payload,
            );

            $storeData = serialize($msgData);
            $expireTime = strtotime($day) + 7 * 86400;

            // 如果当天　接收用户　第一次开始聊天（新增集合），为集合设置过期时间
            if (!$toFirst) {
                $this->redis->zadd($toUser, $timestamp, $storeData);
                $this->redis->expireat($toUser, $expireTime);
            } else {
                $this->redis->zadd($toUser, $timestamp, $storeData);
            }

            // 如果当天　发送用户　第一次开始聊天（新增集合），为集合设置过期时间
            if (!$fromFirst) {
                $this->redis->zadd($fromUser, $timestamp, $storeData);
                $this->redis->expireat($fromUser, $expireTime);
            } else {
                $this->redis->zadd($fromUser, $timestamp, $storeData);
            }

            //　状态为0，为接收用户保存为未读
            if ($status == 0) {
                $unread = 'messages:' . $to . '_unread';
                $this->redis->zadd($unread, $timestamp, $storeData);
                $this->redis->expireat($unread, $expireTime);
            }

            $this->ajaxReturn(array('status' => 1, 'msg' => '保存成功', 'result' => $msgData));
        } else {
            $this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => ''));

        }
    }

    /**
     * 读取聊天
     * @param string $user_id　用户
     * @param $startTime　开始时间
     * @param string $endTime　结束时间
     */
    public function get_chat($user_id, $startTime, $endTime='')
    {
        if ($user_id && $startTime) {
            // key
            $day = date('Ymd', $startTime);
            $user = 'messages:' . $user_id . '_' . $day;

            // 获取时间范围内的聊天数据，以时间范围分页
            if ($startTime && $endTime) {
                $data = $this->redis->zrangebyscore($user, $startTime, $endTime);
            } elseif ($startTime) {
                $data = $this->redis->zrangebyscore($user, $startTime, '+inf');
            } else {
                $data = $this->redis->zrangebyscore($user, '-inf', '+inf');
            }

            foreach ($data as $key=>$value) {
                $data[$key] = unserialize($value);
            }

            $this->ajaxReturn(array('status' => 1, 'msg' => '保存成功', 'result' => $data));
        } else {
            $this->ajaxReturn(array('status' => 1, 'msg' => '缺少参数', 'result' => ''));
        }
    }

    /**
     * 获取未读列表
     * @param string $user_id //接收方ID
     */
    public function get_unread($user_id){
        if ($user_id) {
            $unread = 'messages:' . $user_id . '_unread';
            $data = $this->redis->zrangebyscore($unread, '-inf', '+inf');
            // 读取完毕，删除未读消息数据
            //$this->redis->expire($unread, 0);
            foreach ($data as $key=>$value) {
                $data[$key] = unserialize($value);
            }
            // 对相同发送者的消息分组
            $msgGroup = array();
            foreach ($data as $key=>$value) {
                $msgGroup[$value['from']][] = $value;
            }
            //　计算消息数量
            foreach ($msgGroup as &$value) {
                $count = count($value);
                $value['count'] = $count;
            }

            $this->ajaxReturn(array('status' => 1, 'msg' => '读取成功', 'result' => $msgGroup));
        } else {
            $this->ajaxReturn(array('status' => 1, 'msg' => '缺少参数', 'result' => ''));
        }
    }

    /**
     * 删除聊天记录 删除相关用户的全部聊天记录，和任何人的已读和未读消息。
     * @param string $user_id
     */
    public function del_chat($user_id){
        if ($user_id){
            $key = $this->redis->keys('messages:' . $user_id.'_*');
            $this->redis->del($key);
            $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'result' => ''));
        } else {
            $this->ajaxReturn(array('status' => 1, 'msg' => '缺少参数', 'result' => ''));
        }
    }
}