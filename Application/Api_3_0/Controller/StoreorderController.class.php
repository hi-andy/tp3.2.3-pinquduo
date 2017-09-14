<?php
/**
 * 商家商品控制器
 * Time: 2017-9-11
 */
namespace Api_3_0\Controller;

use Think\Controller;

class StoreorderController extends Controller
{
    // 每页显示多少条数据
    const PAGESIZE = 10;
    // 控制器初始化函数
    public function _initialize()
    {
        header("Access-Control-Allow-Origin:*");
    }


    /**
     * 获取用户在商家的订单
     * @param string $store_id
     * @param string $user_id
     * @param string $page
     * @param string $type
     */
    public function orderData(){
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 获取用户id
        $user_id = (int)I('user_id',0);
        // 获取订单状态
        $type = (int)I('type',0);
        // 检测是否合法
        $data = $this->check($user_id,$store_id,$type);
        // status不为1 检测非法
        if($data['status'] == -1){
            exit(json_encode($data));
        }
        switch ($type){
            // 未完成的订单
            case 1:
                $order_type = '1,10,11';
                $where = "user_id={$user_id} and store_id={$store_id} and the_raise=0 and order_type in({$order_type})";
                break;
            // 待发货的订单
            case 2:
                $order_type = '2,14';
                $where = "user_id={$user_id} and store_id={$store_id} and the_raise=0 and order_type in({$order_type})";
                break;
            // 待收货的订单
            case 3:
                $order_type = '3,15';
                $where = "user_id={$user_id} and store_id={$store_id} and the_raise=0 and order_type in({$order_type})";
                break;
            // 已签收的订单
            case 4:
                $order_type = '4';
                $where = "user_id={$user_id} and store_id={$store_id} and the_raise=0 and order_type={$order_type}";
                break;
            // 退款中的订单
            case 5:
                $order_type = '8,12';
                $where = "user_id={$user_id} and store_id={$store_id} and the_raise=0 and order_type in({$order_type})";
                break;
        }

        // 获取要显示的当前页数
        $page = (int)I('page',1);
        // 获取订单数据
        $info = $this->orderList($where,$page);
        // 拼接客户端需要的数据
        $json = [
            'status' => 1,
            'msg' => '获取订单列表成功',
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
     * 检测用户和商家是否合法
     * @param int $store_id
     * @param int $user_id
     * @param int $type
     */
    private function check($user_id,$store_id,$type){
        // 检测商家id非法
        if($store_id <= 0){
            return array('status' => -1,'msg' => '商户id不能为空');
        }
        // 检测用户id非法
        if($user_id <= 0){
            return array('status' => -1,'msg' => '用户id不能为空');
        }
        // 检测订单状态type非法
        if($type <= 0){
            return array('status' => -1,'msg' => '订单状态类型不能为空');
        }

        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            return array('status' => -1,'msg' => '商户不存在');
        }

        // 查询用户信息
        $user_info = M('users','tp_','DB_CONFIG2')->where("user_id = {$user_id}")->find();
        // 商家信息没有数据记录
        if(empty($user_info)){
            return array('status' => -1,'msg' => '用户不存在');
        }
        // 商家和用户合法
        return array('status' => 1);
    }

    /**
     * 订单状态
     * @param string $order_type
     * @return string $str
     */
    private function transformStatus($order_type)
    {
        switch ($order_type) {
            case 1 :
                return '待付款';
                break;
            case 2 :
                return '待发货';
                break;
            case 3 :
                return '待收货';
                break;
            case 4 :
                return '已完成';
                break;
            case 5 :
                return '已取消';
                break;
            case 6 :
//                return '待退款';
                return '待换货';
                break;
            case 7 :
//                return '已退款';
                return '已换货';
                break;
            case 8 :
                return '待退货';
                break;
            case 9 :
                return '已退货';
                break;
            case 10 :
                return '拼团中，未付款';
                break;
            case 11 :
                return '拼团中，已付款';
                break;
            case 12 :
                return '未成团，待退款';
                break;
            case 13 :
                return '未成团，已退款';
                break;
            case 14 :
                return '已成团，待发货';
                break;
            case 15  :
                return '已成团，待收货';
                break;
            case 16:
                return '拒绝退货';
                break;
            default:
                return '未定义';
        }
    }

    /**
     * 处理订单列表
     * @param string $where
     * @param int $page
     * $param bool $user
     */
    private function orderList($where,$page,$user=false){
        // 获取订单总条数
        $countNum = M('order','tp_','DB_CONFIG2')
            ->where($where)
            ->count();
        // 获取总的页数
        $pageNum = ceil($countNum / self::PAGESIZE);
        // 获取开始位置
        $start = ($page-1) * self::PAGESIZE;
        // 获取订单列表
        $list = M('order','tp_','DB_CONFIG2')
            ->field('order_id,order_sn,add_time,user_id,order_amount,order_status,order_type,consignee,mobile,address,province,city,district,goods_id')
            ->where($where)
            ->order('order_id desc')
            ->limit($start,self::PAGESIZE)
            ->select();
        // 处理下订单数据
        foreach ($list as $k => $v){
            $list[$k]['add_time'] = date('y/m/d H:i',$v['add_time']);
            $orderGoods = M('order_goods','tp_','DB_CONFIG2')->field('goods_name,spec_key_name,goods_num,goods_price')
                ->where("order_id=".$v['order_id'])
                ->find();
            $list[$k]['goods_name'] = $orderGoods['goods_name'];
            $list[$k]['spec_key_name'] = $orderGoods['spec_key_name'];
            $list[$k]['goods_num'] = $orderGoods['goods_num'];
            $list[$k]['goods_price'] = $orderGoods['goods_price'];
            $goodsInfo = M('goods','tp_','DB_CONFIG2')->field('goods_id,goods_name,shop_price,prom_price,prom,sales,original_img,list_img as original')->where("goods_id=".$v['goods_id'])->find();
            $goodsInfo['original'] = empty($goodsInfo['original']) ? $goodsInfo['original_img'] : $goodsInfo['original'];
            $list[$k]['goodsInfo'] = $goodsInfo;
            $list[$k]['annotation'] = $this->transformStatus($v['order_type']);
            if($user){
                // 获取订单用户ID
                $userId = (int)$v['user_id'];
                $userInfo = M('users','tp_','DB_CONFIG2')->field('nickname,user_id,head_pic')->where("user_id={$userId}")->find();
                $list[$k]['userInfo'] = $userInfo;
            }
            $list[$k]['shippingInfo'] = [
                'consignee' => $v['consignee'],
                'mobile' => $v['mobile'],
                'address' => $v['address'],
                'province' => $v['province'],
                'city' => $v['city'],
                'district' => $v['district'],
            ];
            unset($list[$k]['consignee']);
            unset($list[$k]['mobile']);
            unset($list[$k]['address']);
            unset($list[$k]['province']);
            unset($list[$k]['city']);
            unset($list[$k]['district']);
        }
        return [
            'pagenum' => $pageNum,
            'total' => $countNum,
            'list' => $list
        ];
    }


    /**
     * 处理商家订单列表
     * @param string $store_id
     * @param string $type  1：待支付 2:待成团
     * @param string $page
     */
    public function orderStore(){
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测商家id非法
        if($store_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商户id不能为空')));
        }
        // 获取订单状态
        $type = (int)I('type',0);
        // 检测订单状态type非法
        if($type <= 0){
            exit(json_encode(array('status' => -1,'msg' => '订单状态类型不能为空')));
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            exit(json_encode(array('status' => -1,'msg' => '商户不存在')));
        }
        // 获取满足订单状态的查询条件
        if($type == 1){
            $orderType = "1,10";
            $where = "store_id={$store_id} and order_type in({$orderType})";
        }else if($type == 2){
            $orderType = "11";
            $where = "store_id={$store_id} and order_type={$orderType}";
        }
        // 获取要显示的当前页数
        $page = (int)I('page',1);
        // 获取订单数据
        $info = $this->orderList($where,$page,true);
        // 拼接客户端需要的数据
        $json = [
            'status' => 1,
            'msg' => '获取订单列表成功',
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
     * 修改用户订单收货信息
     * @param string $order_id
     * @param string $consignee
     * @param string $mobile
     * @param string $address
     * @param string $province
     * @param string $city
     * @param string $district
     * @param string $store_id
     * $param string $user_id
     */
    public function editOrderShipping(){
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测商家id非法
        if($store_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '商户id不能为空')));
        }
        // 查询商家信息
        $store_info = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($store_info)){
            exit(json_encode(array('status' => -1,'msg' => '商户不存在')));
        }
        // 获取用户id
        $user_id = (int)I('user_id',0);
        // 检测用户id非法
        if($user_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '用户id不能为空')));
        }
        // 查询用户信息
        $user_info = M('users','tp_','DB_CONFIG2')->where("user_id = {$user_id}")->find();
        // 商家信息没有数据记录
        if(empty($user_info)){
            exit(json_encode(array('status' => -1,'msg' => '用户不存在')));
        }
        // 获取订单id
        $order_id = (int)I('order_id',0);
        // 检测订单id非法
        if($order_id <= 0){
            exit(json_encode(array('status' => -1,'msg' => '订单id不能为空')));
        }
        // 获取订单数据
        $order_info = M('order','tp_','DB_CONFIG2')->where("order_id={$order_id} and user_id={$user_id} and store_id={$store_id}")->find();
        // 订单信息没有数据记录
        if(empty($order_info)){
            exit(json_encode(array('status' => -1,'msg' => '订单不存在')));
        }
        // 获取要修改的收货的信息
        // 姓名
        $consignee = I('consignee');
        // 手机
        $mobile = I('mobile');
        // 详细地址
        $address = I('address');
        // 省份id
        $province = (int)I('province');
        // 城市id
        $city = (int)I('city');
        // 地区id
        $district = (int)I('district');
        // 检测数据是否合法
        if(empty($consignee) || empty($mobile) || empty($address) || $province <= 0 || $city <= 0 || $district <= 0){
            exit(json_encode(array('status' => -1,'msg' => '请求的数据非法')));
        }
        // 检测手机号
        if(!preg_match("/^1[34578]\d{9}$/",$mobile)){
            exit(json_encode(array('status' => -1,'msg' => '手机号格式不正确')));
        }
        // 将数据写入到数据中
        $res = M('order','tp_','DB_CONFIG2')->where("order_id={$order_id}")
            ->data([
                'consignee' => $consignee,
                'mobile' => $mobile,
                'address' => $address,
                'province' => $province,
                'city' => $city,
                'district' => $district,
            ])->save();
        // 追加商家修改用户收货信息到日志表
        M('order_action','tp_','DB_CONFIG2')->data([
            'order_id' => $order_id,
            'action_user' => $store_id,
            'store_id' => $store_id,
            'order_status' => (int)$order_info['order_status'],
            'shipping_status' => (int)$order_info['shipping_status'],
            'pay_status' => (int)$order_info['pay_status'],
            'order_type' => (int)$order_info['order_type'],
            'action_note' => '修改订单——收货人地址',
            'log_time' => time(),
            'status_desc' => 'edit'
        ])->add();
        // 返回数据结果
        if($res){
            exit(json_encode(array('status' => 1,'msg' => '操作成功')));
        }else{
            exit(json_encode(array('status' => -1,'msg' => '操作失败')));
        }



    }


}