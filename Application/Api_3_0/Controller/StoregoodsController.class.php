<?php
/**
 * 商家商品控制器
 * Time: 2017-9-11
 */
namespace Api_3_0\Controller;

use Think\Cache\Driver\Redis;
use Think\Controller;

class StoregoodsController extends Controller
{
    // 每页显示多少条数据
    const PAGESIZE = 10;
    // 分词服务器地址
    const SCWS = 'http://39.108.12.198';
    // 商品列表经常推荐显示商品个数
    const SHOWNUM = 10;
    // redis对象
    private $redis;

    // 控制器初始化函数
    public function _initialize()
    {
        header("Access-Control-Allow-Origin:*");
        $this->redis = new Redis();
    }

    /**
     * 获取商家商品列表
     * @param string $store_id
     * @param string $type  1:经常推荐 2:新品 3:销量 4:排序
     */
    public function goodsList(){
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测信息是否合法
        $result = $this->check($store_id);
        if($result['status'] == -1){
            exit(json_encode($result));
        }
        // 获取要显示的商品列表类型
        $type = (int)I('type',0);
        // 商品列表类型非法
        if($type <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商品列表类型不能为空')));
        }
        // 获取要显示的当前页数
        $page = (int)I('page',1);
        // 处理商品数据列表
        // 商品查询条件
        // 经常推荐商品列表
        if($type == 1){
            // 生成有序集合key
            $key = 'sortrecommend_'.$store_id;
            // 获取有序集合从大到小
            // 取得前10个商品
            $endNum = self::SHOWNUM - 1;
            $goodsArray = $this->redis->zRevRange($key, 0, $endNum);
            if(count($goodsArray) > 0){
                $goodsIds = implode(',',$goodsArray);
                $where = "goods_id in({$goodsIds})";
                $orderStr = "FIELD (goods_id, {$goodsIds})";
            }else{
                // 拼接客户端需要的数据
                $json = [
                    'status' => 1,
                    'msg' => '获取商品列表成功',
                    'result' => [
                        'totalpage' => 0,
                        'total' => 0,
                        'currentpage' => $page,
                        'nextpage' => 1,
                        'items' => []
                    ]
                ];
                // 返回json给客户端
                exit(json_encode($json));
            }

        }else{
            // 新品 添加时间排序
            if($type == 2){
                $orderStr = "addtime desc";
            }
            // 销量排序
            if($type == 3){
                $orderStr = "sales desc";
            }
            // 设置的排序顺序，从小到大
            if($type == 4){
                $orderStr = "sort asc";
            }
            $where = "store_id={$store_id} and is_show=1 and is_audit=1 and goodstatus=2 and is_on_sale=1 and is_special != 8 and the_raise = 0";
        }

        // 取得商品列表
        $info = $this->listData($where,$page,$orderStr);

        // 拼接客户端需要的数据
        $json = [
            'status' => 1,
            'msg' => '获取商品列表成功',
            'result' => [
                'totalpage' => $info['pagenum'],
                'total' => $info['total'],
                'currentpage' => $page,
                'nextpage' => ($page+1 > (int)$info['pagenum']) ? (int)$info['pagenum'] : $page+1,
                'items' => $info['list']
            ]
        ];
        // 返回json给客户端
        exit(json_encode($json));

    }

    /**
     * 搜索商家商品列表
     * @param string $store_id
     * @param string $key
     * @param string $page
     */
    function getSearch()
    {
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测信息是否合法
        $result = $this->check($store_id);
        if($result['status'] == -1){
            exit(json_encode($result));
        }
        // 搜索的关键词
        $key = I('key');
        // 搜索的关键词非法
        if(empty($key)){
            exit(json_encode(array('status' => -1,'msg' => '搜索的关键词为空')));
        }
        // 将关键词urldecode
        $key = urldecode($key);
        // 获取要显示的页数
        $page = (int)I('page',1);
        // 通过分词服务器获取查询的关键词
        $context = stream_context_create(array(
            'http' => array(
                //超时时间，单位为秒
                'timeout' => 5
            )
        ));
        // 获取分词服务器的关键词列表
        $content = file_get_contents(self::SCWS.'/?key='.$key, 0, $context);
        $res = json_decode($content,true);
        // 循环遍历key
        $keys = '(';
        foreach ($res as $v){
            $word = $v['word'];
            $keys .= "goods_name like '%{$word}%' and ";
        }
        $keys = substr($keys, 0, -4);
        $keys .= ')';
        // 拼接商品查询条件
        $where = "store_id={$store_id} and " . $keys . " and is_special != 8 and the_raise = 0 and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `show_type`=0 ";

        // 取得商品列表
        $info = $this->listData($where,$page);

        // 拼接客户端需要的数据
        $json = [
            'status' => 1,
            'msg' => '获取商品列表成功',
            'result' => [
                'totalpage' => $info['pagenum'],
                'total' => $info['total'],
                'currentpage' => $page,
                'nextpage' => ($page+1 > (int)$info['pagenum']) ? (int)$info['pagenum'] : $page+1,
                'items' => $info['list']
            ]
        ];
        // 返回json给客户端
        exit(json_encode($json));

    }

    /**
     * 查询商品列表
     * @param string $where
     * @param string $page
     * @param string $orderStr
     */
    private function listData($where,$page,$orderStr = 'sales desc'){
        // 获取商家商品总数
        $countNum = M('goods','tp_','DB_CONFIG2')
            ->where($where)
            ->count();
        // 获取总的页数
        $pageNum = ceil($countNum / self::PAGESIZE);
        // 获取开始位置
        $start = ($page-1) * self::PAGESIZE;
        // 获取商品列表
        $list = M('goods','tp_','DB_CONFIG2')
            ->field('goods_id,goods_name,store_count,sales,prom_price,list_img,original_img,prom')
            ->where($where)
            ->order($orderStr)
            ->limit($start,self::PAGESIZE)
            ->select();
        // 处理下数据
        foreach ($list as $k => $v){
            $list[$k]['goods_share_url'] = C('SHARE_URL')."/goods_detail.html?goods_id=".$v['goods_id'];
            $list[$k]['original'] = $v['list_img'];
            $list[$k]['original_img'] = empty($v['original_img']) ? $v['list_img'] : $v['original_img'];
            unset($list[$k]['list_img']);
            $list[$k]['prom_price_max'] = 0;
            $priceData = M('spec_goods_price','tp_','DB_CONFIG2')->field('prom_price')->where('goods_id='.$v['goods_id'].' and is_del=0')->select();
            if(count($priceData) > 1){
                $maxPrice = 0;
                foreach ($priceData as $row){
                    $maxPrice = max($maxPrice,(float)$row['prom_price']);
                }
                $list[$k]['prom_price_max'] = $maxPrice;
            }

        }
        // 返回数据
        return [
            'pagenum' => $pageNum,
            'total' => $countNum,
            'list' => $list
        ];

    }

    /**
     * 检测用户和商家是否合法
     * @param int $store_id
     * @param int $goods_id
     */
    private function check($store_id,$goods_id = 0){
        // 检测商家id非法
        if($store_id <= 0){
            return array('status' => -1,'msg' => '商户id不能为空');
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            return array('status' => -1,'msg' => '商户不存在');
        }

        if($goods_id > 0){
            // 查询下是否有商品数据
            $goodsInfo = M('goods','tp_','DB_CONFIG2')->field('goods_id')->where("goods_id={$goods_id} and store_id={$store_id}")->find();
            if(count($goodsInfo) == 0){
                return array('status' => -1,'msg' => '商家下该商品不存在');
            }
        }
        // 合法
        return array('status' => 1);
    }



    /**
     * 商家推荐给用户商品
     * @param string $store_id
     * @param string $goods_id
     */
    public function recommend()
    {
        // 获取商家id
        $store_id = (int)I('store_id', 0);
        // 获取商品id
        $goods_id = (int)I('goods_id', 0);
        // 检测商品id非法
        if ($goods_id <= 0) {
            exit(json_encode(array('status' => -1, 'msg' => '商品id不能为空')));
        }
        // 检测信息是否合法
        $result = $this->check($store_id,$goods_id);
        if($result['status'] == -1){
            exit(json_encode($result));
        }
        // 推荐商品的缓存key
        $redisKey = 'recommend_'.$store_id.'_'.$goods_id;
        // 自增1，原子操作
        $this->redis->incr($redisKey);
        // 获取推荐数
        $num = $this->redis->get($redisKey);
        $num = (int)$num;
        // 生成有序集合key
        $key = 'sortrecommend_'.$store_id;
        // 将数据写入有序集合
        $this->redis->zAdd($key, $num, $goods_id);
        /*
        $r = $this->redis->zRevRange($key, 0, -1);
        var_dump($r);
        exit();
        */
    }

}