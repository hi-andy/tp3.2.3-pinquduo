<?php
/**
 * 聊天消息控制器
 * 同步环信，保存读取聊天消息，最多保存7天。
 * 改用 Redis 数据库存储聊天数据
 * Author: yonghua
 * Time: 2017-8-7
 */
namespace Api_3_0\Controller;

use Think\Controller;

class QuickreplyController extends Controller
{
    // 限制快捷回复的内容字数
    const MAX_LEN = 200;
    // 限制快捷回复的条数
    const MAX_NUM = 20;
    /**
     * 添加商家快捷回复数据
     * @param string $store_id　商家id
     * @param string $content 快捷回复内容
     * @param string $id 快捷回复id
     */	
    public function addInfo()
    {
        // 设置跨域
        header("Access-Control-Allow-Origin:*");
        // 获取商家id
        $store_id = (int)I('store_id');
        // 商家id非法
        if($store_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商户id不能为空')));
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            exit(json_encode(array('status' => -1,'msg' => '商户不存在')));
        }
        // 获取快捷回复内容
        $content = I('content');
        // 检测快捷回复内容数据是否为空
        if(empty($content)){
            exit(json_encode(array('status' => -1,'msg' => '快捷回复内容不能为空')));
        }
        // 检测快捷回复内容的字数大小
        // utf-8编码 一个中文占用3个字节
        if(strlen($content) > self::MAX_LEN*3){
            exit(json_encode(array('status' => -1,'msg' => '快捷回复内容不能超过200个字')));
        }
        // 获取快捷回复id
        $id = (int)I('id',0);
        // $id = 0 添加  > 0 更新
        if($id > 0){
            // 查询下是否有这条数据
            $quickReplyInfo = M('quick_reply','tp_','DB_CONFIG2')->field('id')->where("id={$id} and store_id={$store_id}")->find();
            // 查询到没有该条数据
            if(count($quickReplyInfo) == 0){
                exit(json_encode(array('status' => -1,'msg' => '查无此快捷回复id')));
            }
            // 开始更新数据
            $re = M('quick_reply','tp_','DB_CONFIG2')->data([
                'content' => $content,
                'updatetime' => time(),
            ])->where("id={$id}")->save();
            if($re !== false){
                $result = M('quick_reply','tp_','DB_CONFIG2')->where("id={$id}")->field('id,content')->find();
                exit(json_encode(array('status' => 1,'msg' => '更新成功' , 'result' => $result)));
            }
            exit(json_encode(array('status' => -1,'msg' => '更新失败')));
        }
        $countNum = M('quick_reply','tp_','DB_CONFIG2')->where("store_id={$store_id}")->count();
        // 检测快捷回复的数据是否超过20条
        if((int)$countNum >= self::MAX_NUM){
            exit(json_encode(array('status' => -1,'msg' => '快捷回复数据超过20条')));
        }
        $re = M('quick_reply','tp_','DB_CONFIG2')->data([
            'content' => $content,
            'store_id' => $store_id,
            'addtime' => time(),
        ])->add();
        if($re !== false){
            $result = M('quick_reply','tp_','DB_CONFIG2')->where("id={$re}")->field('id,content')->find();
            exit(json_encode(array('status' => 1,'msg' => '添加成功' , 'result' => $result)));
        }
        exit(json_encode(array('status' => -1,'msg' => '添加失败')));
    }


    /**
     * 获取快捷回复数据
     * @param string $store_id　商家id
     */
    public function listData()
    {
        // 设置跨域
        header("Access-Control-Allow-Origin:*");
        // 获取商家id
        $store_id = (int)I('store_id');
        // 商家id非法
        if($store_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商户id不能为空')));
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            exit(json_encode(array('status' => -1,'msg' => '商户不存在')));
        }
        $listData = M('quick_reply','tp_','DB_CONFIG2')->where("store_id={$store_id}")->order('id desc')->select();
        exit(json_encode(array('status' => 1,'msg' => '数据列表','result' => $listData)));
    }

    /**
     * 删除快捷回复数据
     * @param string $store_id　商家id
     * @param string $id 快捷回复id
     */
    public function delData()
    {
        // 设置跨域
        header("Access-Control-Allow-Origin:*");
        // 获取商家id
        $store_id = (int)I('store_id');
        // 商家id非法
        if($store_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商户id不能为空')));
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            exit(json_encode(array('status' => -1,'msg' => '商户不存在')));
        }
        $id = (int)I('id',0);
        // 检测id是否合法
        if($id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '回复ID无效')));
        }
        // 查询下是否有这条数据
        $quickReplyInfo = M('quick_reply','tp_','DB_CONFIG2')->field('id')->where("id={$id} and store_id={$store_id}")->find();
        // 查询到没有该条数据
        if(count($quickReplyInfo) == 0){
            exit(json_encode(array('status' => -1,'msg' => '查无此快捷回复id')));
        }
        // 删除快捷回复数据
        $re = M('quick_reply','tp_','DB_CONFIG2')->where("id={$id}")->delete();
        if($re){
            exit(json_encode(array('status' => 1,'msg' => '删除成功')));
        }
        exit(json_encode(array('status' => -1,'msg' => '删除失败')));

    }

	
	
	
}