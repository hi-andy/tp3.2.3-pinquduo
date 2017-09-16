<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/8/23
 * Time: 21:06
 */

namespace Api_2_0_2\Controller;

use Think\Controller;
use Think\Cache\Driver\Redis;

class AreaController extends Controller
{
    // redis对象
    private $redis = '';

    public function _initialize()
    {
        header("Access-Control-Allow-Origin:*");
        $this->redis = new Redis();
    }

    /**
     * 专门给H5获取地址信息
     * @param string $parent_id
     */
    public function areaList(){
        // 获取父ID
        $parent_id = (int)I('parent_id',1);
        // 设置地区缓存key
        $redisKey = "areaList_{$parent_id}";
        // 获取缓存数据
        $area = $this->redis->get($redisKey);
        // 缓存有数据
        if(!empty($area)){
            // 反序列化字符串数据
            $data = unserialize($area);
            // 返回结果
            $json = [
                'status' => 1,
                'msg' => '获取地址成功',
                'result' => [
                    'items' => $data
                ]
            ];
            exit(json_encode($json));
        }
        // 获取地区数据
        $data = M('region','tp_','DB_CONFIG2')->field("region_id,region_name")->where("parent_id={$parent_id}")->select();
        // 设置数据
        $this->redis->set($redisKey,serialize($data));
        // 返回结果
        $json = [
            'status' => 1,
            'msg' => '获取地址成功',
            'result' => [
                'items' => $data
            ]
        ];
        exit(json_encode($json));
    }



    /**
     * APP获取省市区地址
     */
    public function appRegionList(){
        // 获取父ID
        $parent_id = 1;
        // 设置地区缓存key
        $redisKey = "appRegionList_{$parent_id}";
        // 获取缓存数据
        $area = $this->redis->get($redisKey);
        // 缓存有数据
        if(!empty($area)){
            // 反序列化字符串数据
            $data = unserialize($area);
            // 返回结果
            $json = [
                'status' => 1,
                'msg' => '获取地址成功',
                'result' => [
                    'items' => $data
                ]
            ];
            exit(json_encode($json));
        }
        // 获取省数据
        $data = M('region','tp_','DB_CONFIG2')->field("region_id,region_name")->where("parent_id={$parent_id}")->select();
        foreach($data as $key => $val){
            // 获取市数据
            $provinceId = $val['region_id'];
            $provinceChildRen = M('region','tp_','DB_CONFIG2')->field("region_id,region_name")->where("parent_id={$provinceId}")->select();
            foreach($provinceChildRen as $k => $v){
                // 获取地区数据
                $cityid = (int)$v['region_id'];
                $cityChildRen = M('region','tp_','DB_CONFIG2')->field("region_id,region_name")->where("parent_id={$cityid}")->select();
                $provinceChildRen[$k]['children'] = $cityChildRen;
            }
            $data[$key]['children'] = $provinceChildRen;
        }


        // 设置数据
        $this->redis->set($redisKey,serialize($data));
        // 返回结果
        $json = [
            'status' => 1,
            'msg' => '获取地址成功',
            'result' => [
                'items' => $data
            ]
        ];
        exit(json_encode($json));
    }

}