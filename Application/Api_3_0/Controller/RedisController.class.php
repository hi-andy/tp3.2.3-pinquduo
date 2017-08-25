<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/8/23
 * Time: 21:06
 */

namespace Api_3_0\Controller;

use Think\Controller;

class RedisController extends Controller
{
    private $redis = '';
    public function _initialize()
    {
        $this->redis = new Redis();
    }

    // 查看缓存是否有效
    public function getKey($key)
    {
        $data = unserialize($this->redis($key));
        print_r($data);
    }

    //删除缓存
    public function delKey($key){
        $this->redis->delete($this->redis($key));
        echo '删除 ' . $key . ' 缓存成功！';
    }

    // 查看队列
    public function rpopKey($key)
    {
        $data = $this->redis->rpop($key);
        print_r($data);
    }
}