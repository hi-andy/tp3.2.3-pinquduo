<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/8/23
 * Time: 21:06
 */

namespace Api_2_0_2\Controller;


use Think\Controller;

class RedisController extends Controller
{
    // 查看缓存是否有效
    public function getKey($key)
    {
        $data = unserialize(redis($key));
        print_r($data);
    }

    //删除缓存
    public function redisDel($key = ""){
        redisdelall($key);
        echo '删除 '.$key . ' 成功！';
    }
}