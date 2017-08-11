<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace Storeapp\Controller;
use Think\Controller;
class BaseController extends Controller {
    public $http_url;
    public $user = array();
    public $user_id = 0;
    /**
     * 析构函数
     */
    function __construct() {
        parent::__construct();
//        if($_REQUEST['test'] == '1')
//        {
//            $test_str = 'POST'.print_r($_POST,true);
//            $test_str .= 'GET'.print_r($_GET,true);
//            file_put_contents('a.html', $test_str);
//        }
    }

    /*
     * 初始化操作
     */
    public function _initialize() {
        header("Access-Control-Allow-Origin:*");
        $this->injection_prevention();
    }

    /**
     *  app 端万能接口 传递 sql 语句 sql 错误 或者查询 错误 result 都为 false 否则 返回 查询结果 或者影响行数
     */
    public function sqlApi()
    {
        exit(array('status'=>-1,'msg'=>'使用万能接口必须开启签名验证才安全','result'=>'')); //  开启后注释掉这行代码即可

        C('SHOW_ERROR_MSG',1);
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $sql = $_REQUEST['sql'];
        try
        {
            if(preg_match("/insert|update|delete/i", $sql))
                $result = $Model->execute($sql);
            else
                $result = $Model->query($sql);
        }
        catch (\Exception $e)
        {
            $json_arr = array('status'=>-1,'msg'=>'系统错误','result'=>'');
            $json_str = json_encode($json_arr);
            exit($json_str);
        }

        if($result === false) // 数据非法或者sql语句错误
            $json_arr = array('status'=>-1,'msg'=>'系统错误','result'=>'');
        else
            $json_arr = array('status'=>1,'msg'=>'成功!','result'=>$result);

        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 获取全部地址信息
     */
    public function allAddress(){
        $data =  M('region')->select();
        $json_arr = array('status'=>1,'msg'=>'成功!','result'=>$data);
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * app端请求签名
     * @return type
     */
    protected function getSign(){
        header("Content-type:text/html;charset=utf-8");
        $data = $_POST;
        unset($data['time']);    // 删除这两个参数再来进行排序
        unset($data['sign']);    // 删除这两个参数再来进行排序
        ksort($data);
        $str = implode('', $data);
        $str = $str.$_POST['time'].C('API_SECRET_KEY');
        return md5($str);
    }

    /**
     * 获取服务器时间
     */
    public function getServerTime()
    {
        $json_arr = array('status'=>1,'msg'=>'成功!','result'=>time());
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 数据分页处理模型
     * @param int $total
     * @param array $items
     * @return array
     * author Fox
     */
    function listPageData($total=0,$items=array(),$pagesize=null) {
        if(empty($pagesize)){
            $pagesize = I('request.pagesize', C('PAGE_SIZE'), 'intval');
        }
        $totalpage = ceil($total/$pagesize);
        $currentpage = I('request.page', 1, 'intval');
        if( I('request.page')==0){
            $currentpage = 1;
        }
        if(empty($items))
        {
            $items=array();
        }
        if(empty($total))
        {
            $total = 0;
        }
        $currentpage = max(1, $currentpage);
        $currentpage = min($currentpage, $totalpage);
        $nextpage = min($currentpage+1, $totalpage);
        return  compact('total', 'totalpage', 'pagesize', 'currentpage', 'nextpage', 'items');
    }

    function getStatus($order)//订单表详情
    {
        if ($order['order_type']==1) {
            $status['annotation'] = '待付款';
        } elseif ($order['order_type']==2) {
            $status['annotation'] = '待发货';
        } elseif ($order['order_type']==3) {
            $status['annotation'] = '待收货';
        } elseif ($order['order_type']==4) {
            $status['annotation'] = '已完成';
        } elseif ($order['order_type']==5) {
            $status['annotation'] = '已取消';
        } elseif ($order['order_type']==6) {
            $status['annotation'] = '待换货';
        } elseif ($order['order_type']==7) {
            $status['annotation'] = '已换货';
        }elseif($order['order_type']==8) {
            $status['annotation'] = '待退货';
        }elseif($order['order_type']==9) {
            $status['annotation'] = '已退货';
        }elseif($order['order_type']==16){
            $status['annotation'] = '拒绝受理';
        }else{
            $status['annotation'] = '订单状态异常';
            $status['order_type'] = null;
        }
        return $status;
    }

    function getPromStatus($order,$prom,$num)//订单表详情、团购表详情、参团人数
    {
        if(($num+1)<$prom['goods_num'] && ($prom['end_time']>time()) && $order['pay_status']==0 && $order['order_status']==8){
            $status['annotation'] = '拼团中,未付款';
            $status['order_type'] = '10';
        }
        elseif($order['order_type']==11){
            $status['annotation'] = '拼团中,已付款';
            $status['order_type'] = '11';
        }
        elseif(($num+1)<$prom['goods_num'] && $prom['end_time'] && $order['order_status']==9){//< time() && $order['pay_status']==1 && $order['order_status']==9
            $status['annotation'] = '未成团,待退款';
            $status['order_type'] = '12';
        }
        elseif(($num+1)<$prom['goods_num']  && $order['pay_status']==1 && $order['order_status']==10){
            $status['annotation'] = '未成团,已退款';
            $status['order_type'] = '13';
        }
        elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==0 && $order['order_status']==11){
            $status['annotation'] = '已成团,待发货';
            $status['order_type'] = '14';
        }
        elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==11){
            $status['annotation'] = '已成团,待收货';
            $status['order_type'] = '15';
        }elseif(($num+1)==$prom['goods_num'] && $order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==2) {
            $status['annotation'] = '已完成';
            $status['order_type'] = '4';
        }elseif ($order['order_status']==3){
            //'已取消'
            $status['annotation'] = '已取消';
            $status['order_type'] = '5';
        }elseif ($order['order_status']==4 && $order['pay_status']==1) {
            //'已完成'
            $status['annotation'] = '待换货';
            $status['order_type'] = '6';
        } elseif ($order['order_status']==5 && $order['pay_status']==1) {
            //'已完成'
            $status['annotation'] = '已换货';
            $status['order_type'] = '7';
        }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==6) {
            $status['annotation'] = '待退货';
            $status['order_type'] = '8';
        }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==7) {
            $status['annotation'] = '已退货';
            $status['order_type'] = '9';
        }elseif($order['order_type']==16 && $order['order_status']==15){
            $status['annotation'] = '拒绝受理';
            $status['order_type'] = '16';
        }else{
            $status['annotation'] = '订单状态异常';
            $status['order_type'] = null;
        }

        return$status;
    }

    /**
     * 上传图片 多图
     */
    public function mobile_uploadimage($file='')
    {
        if(!$file) $file=$_FILES;

        //调用七牛云上传
        $qiniu = new \Admin\Controller\QiniuController();
            foreach ($file['picture']['name'] as $key => $value) {
                $suffix = substr(strrchr($value, '.'), 1);
                $files = array(
                    "key" => time() . rand(0, 9) . "." . $suffix,
                    "filePath" => $file['picture']['tmp_name'][$key],
                    "mime" => $file['picture']['type'][$key]
                );
                $info = $qiniu->uploadfile("imgbucket", $files);
                $return_data[$key]['origin'] = CDN . "/" . $info[0]["key"];
                $return_data[$key]['width'] = '100';
                $return_data[$key]['height'] = '100';
                $return_data[$key]['small'] = CDN . "/" . $info[0]["key"];
            }
        redis("mobile_uploadimage", serialize($return_data),REDISTIME);
        return $return_data;
    }

    public function h5_uploadimage(){
        $res = $this->mobile_uploadimage();

        $res = $res[0];

        $this->getJsonp($res);
    }

    /*
     *
     $data['daifahuo'] = M('order')->where('`pay_status` = 1 and (`order_status` = 1 or `order_status` = 11) and `shipping_status` != 1  and `user_id` = '.$user_id)->count();
        $data['daishouhuo'] = M('order')->where('`pay_status` = 1 and `shipping_status` = 1 and (`order_status` = 1 or `order_status` = 11) and `user_id` = '.$user_id)->count();
        $data['daifukuan'] = M('order')->where('`pay_status` = 0 and (`order_status` = 1 or `order_status` = 8 ) and `is_cancel`=0 and `user_id` = '.$user_id)->count();
        $data['refund'] = M('order')->where('(`order_type`=6 or `order_type`=7 or `order_type`=8 or `order_type`=9 or `order_type`=12 or `order_type`=13) and `user_id`='.$user_id)->count();//售后
        $mark = M('group_buy')->where('`is_successful`=0 and `is_cancel`=0 and `user_id` = '.$user_id.' and `end_time`>='.time())->count();
     * */

    public function getCountUserOrder($user_id)
    {
        $rdsname = "getCountUserOrder_status".$user_id;
        if (redis("getCountUserOrder_status".$user_id) == 1){
            redisdelall('getCountUserOrder'.$user_id);
            redisdelall($rdsname."*");
        }
        if (empty(redis($rdsname))) {
            //获取订单信息
            $data['daifahuo'] = M('order')->where('(order_type = 2 or order_type = 14) and `user_id` = ' . $user_id)->count();
            $data['daishouhuo'] = M('order')->where('(order_type = 3 or order_type = 15) and `user_id` = ' . $user_id)->count();
            $data['daifukuan'] = M('order')->where('(order_type = 1 or order_type = 10) and `user_id` = ' . $user_id)->count();
            $data['refund'] = M('order')->where('(`order_type`=6 or `order_type`=7 or `order_type`=8 or `order_type`=9 or `order_type`=12 or `order_type`=13) and `user_id`=' . $user_id)->count();//售后
            $data['in_prom'] = M('order')->where('(order_type = 11 or order_type = 10) and `user_id`=' . $user_id)->count();
            redis($rdsname, serialize($data));
        } else {
            $data = unserialize(redis($rdsname));
        }
        return $data;
    }

    public function getJsonp($data)
    {
        $b = json_encode($data);
        echo "{$_GET['jsoncallback']}({$b})";
        exit;
    }

    /**
     * 获取物流信息
     */
    public function obtain_logistics(){
        $param=$_POST['param'];
        $result =  json_decode($param['lastResult']);

        if($result['message'] =='ok') {
            M('delivery_doc')->where(array('shipping_order'=>$result['nu']))->save(array('express_info'=>json_encode($result['date'])));

            echo '{"result":"true",	"returnCode":"200","message":"成功"}';
        }else{
            echo  '{"result":"true",	"returnCode":"200","500":"失败"}';
        }
    }

    /**
     * test物流订阅
     */
    public function reserve_logistics(){
        $order_id = $_GET['order_id'];

        $res = reserve_logistics($order_id);
    }


    /**
     * 微信退款接口
     */
    public function weixinBackPay(){
        require_once("plugins/payment/weixin/lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("plugins/payment/weixin/example/WxPay.NativePay.php");
        require_once("plugins/payment/weixin/example/WxPay.JsApiPay.php");

        $out_trade_no = $_GET['order_sn'];
        //商户退款单号，商户自定义，此处仅作举例
        $out_refund_no = "$out_trade_no".time();
        $order_info = M('order')->where(array('order_sn'=>$out_trade_no))->find();
        //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
        $total_fee =  	$order_info['order_amount'] * 100;
        $refund_fee = $order_info['order_amount'] * 100;
        //使用退款接口
        $refund = new \WxPayRefund();
        //设置必填参数
        $refund->SetOut_trade_no($out_trade_no);    //商户订单号
        $refund->SetOut_refund_no($out_refund_no);  //商户退款单号
        $refund->SetTotal_fee($total_fee);          //总金额
        $refund->SetRefund_fee($refund_fee);        //退款金额
        $refund->SetOp_user_id(1405319302);         //操作员

        $WxPay = new \WxPayApi();
        $refundResult = $WxPay->refund($refund,30);
        var_dump($refundResult);
        die;
        //商户根据实际情况设置相应的处理流程,此处仅作举例
        if ($refundResult["return_code"] == "FAIL") {
            return array('status'=>0,'msg'=>"通信出错：".$refundResult['return_msg']."<br>");
        }
        else{
            $msg = "业务结果：".$refundResult['result_code']."<br>";
            $msg .= "错误代码：".$refundResult['err_code']."<br>";
            $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：".$refundResult['appid']."<br>";
            $msg .= "商户号：".$refundResult['mch_id']."<br>";
            $msg .= "子商户号：".$refundResult['sub_mch_id']."<br>";
            $msg .= "设备号：".$refundResult['device_info']."<br>";
            $msg .= "签名：".$refundResult['sign']."<br>";
            $msg .= "微信订单号：".$refundResult['transaction_id']."<br>";
            $msg .= "商户订单号：".$refundResult['out_trade_no']."<br>";
            $msg .= "商户退款单号：".$refundResult['out_refund_no']."<br>";
            $msg .= "微信退款单号：".$refundResult['refund_idrefund_id']."<br>";
            $msg .= "退款渠道：".$refundResult['refund_channel']."<br>";
            $msg .= "退款金额：".$refundResult['refund_fee']."<br>";
            $msg .= "现金券退款金额：".$refundResult['coupon_refund_fee']."<br>";

            return array('status'=>1,'msg'=>$msg,'out_refund_no'=>$out_refund_no);
        }
    }

    /*
     * 用商户名关键字做检索
     * */
    public function getStoreWhere($where,$store_name)
    {
        $store_id = M('merchant')->where("`store_name` like '%".$store_name."%'")->select();
        $store_ids =null;
        $num = count($store_id);
        for($i=0;$i<$num;$i++)
        {
            if($num==1){
                $store_ids = $store_ids."('".$store_id[$i]['id']."')";
            }elseif($i==$num-1) {
                $store_ids = $store_ids."'".$store_id[$i]['id']."')";
            }elseif($i==0){
                $store_ids = $store_ids."('".$store_id[$i]['id']."',";
            }else{
                $store_ids = $store_ids."'".$store_id[$i]['id']."',";
            }
        }
        $where = "$where and store_id IN $store_ids";
        return $where;
    }

    function fenxiangLOGO($path,$goods_id,$store_id)
    {
        $bigImgPath = $path;
        //'Public/images/goods_thumb_1055_400_400_58a8643127a2c.jpeg'
        $qCodePath = 'Public/images/fenxiangLOGO.jpg';

        $bigImg = imagecreatefromstring(file_get_contents($bigImgPath));
        $qCodeImg = imagecreatefromstring(file_get_contents($qCodePath));

        list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize($qCodePath);
        // imagecopymerge使用注解
        imagecopymerge($bigImg, $qCodeImg, 0, 260, 0, 0, $qCodeWidth, $qCodeHight, 100);

        list($bigWidth, $bigHight, $bigType) = getimagesize($bigImgPath);

        switch ($bigType) {
            case 1: //gif
//                header('Content-Type:image/gif');
                $pic = '/sites/pqd/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.gif';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.gif';
                imagejpeg($bigImg, $pic);
                break;
            case 2: //jpg
//                header('Content-Type:image/jpg');
                $pic = '/sites/pqd/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.jpg';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.jpg';
                imagejpeg($bigImg, $pic);
                break;
            case 3: //png
//                header('Content-Type:image/png');
                $pic = '/sites/pqd/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.png';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.png';
                imagejpeg($bigImg, $pic);
                break;
            default:
                # code...
                break;
        }
        return $pin;
    }

    /**
     * 调度商品详情
     *
     * goods 商品表
     * goods_id 商品id
     * cat_id 分类id
     * goods_name 商品名
     * prom_price 团购价
     * market_price 市场价
     * shop_price 商城价
     * prom 团购人数
     * goods_remark  商品简介
     * sales 销量
     * goods_content  商品详情
     * store_id 商户id
     * is_support_buy 是否支持单买
     * is_special 商品type
     * original_img 内页展示图
     * list_img 列表图
     *
     */
    function  getGoodsInfo($goods_id,$type='')
    {
        $goods = M('goods')->where(" `goods_id` = $goods_id")->field('goods_id,cat_id,goods_name,prom_price,market_price,shop_price,prom,goods_remark,sales,goods_content,store_id,is_support_buy,is_special,original_img as original,list_img as original_img')->find();
        if(!empty($goods)){
            //商品详情
            $goods['goods_content_url'] = C('HTTP_URL') . '/Api/goods/get_goods_detail?id=' . $goods_id;
            $goods['goods_share_url'] = C('SHARE_URL') . '/goods_detail.html?goods_id=' . $goods_id;
            $store = M('merchant')->where(' `id` = ' . $goods['store_id'])->field('id,store_name,store_logo,sales,mobile')->find();
            $store['store_logo'] = TransformationImgurl($store['store_logo']);
            $goods['store'] = $store;
            if(empty($goods['original_img'])){
                $goods['original_img'] =TransformationImgurl($goods['original']);
            }else{
                $goods['original_img'] =TransformationImgurl($goods['original_img']);
            }
            $goods['original'] =TransformationImgurl($goods['original']);
            /**
             * 此生成水印图片的链接对比下面的，多出了两个方法，导致安卓分享出去的链接看不到图片，
             * 目前还不知道会不会有其它问题，如没问题后续删除。
             *$goods['fenxiang_url'] = $goods['original']."?imageView2/1/w/400/h/400/q/75%7Cwatermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nX2xvZ29fNDAwLmpwZw==/dissolve/100/gravity/South/dx/0/dy/0%7Cimageslim";
             *
             */
            $goods['fenxiang_url'] = $goods['original'].'?watermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nX2xvZ29fNDAwLmpwZw==/dissolve/100/gravity/South/dx/0/dy/0';
            if($type!=1){
                $goods['img_arr'] = getImgs($goods['goods_content']);
                $goods['img_arr'] = getImgSize($goods['img_arr']);

                //获取店铺优惠卷store_logo_compression
                /*
			 * coupon 优惠券类型表
			 * id 优惠券id
			 * condition 满减条件
			 * user_end_time 最后使用时间
			 * send_start_time 开始发放时间
			 * send_end_time 最后发放时间
			 * id  优惠券id
			 * name 优惠券名字
			 * money 优惠券满减金额
			 * condition 满减条件
			 * use_start_time 开始使用时间
			 * use_end_time 最后使用时间
			 * */
                $coupon = M('coupon')->where('`store_id` = ' . $goods['store_id'] . ' and `send_start_time` <= ' . time() . ' and `send_end_time` >= ' . time() . ' and createnum > send_num')->select();
                if (empty($coupon)) {
                    $coupon = null;
                }
                $goods['store']['coupon'] = $coupon;
            }
            unset($goods['goods_content']);
            unset($goods['list_img']);
        }else{
            $goods=null;
        }
        return $goods;
    }


    /*
     * 调度商品列表
     *
     * goods 商品表
     * goods_id 商品id
     * cat_id 分类id
     * goods_name 商品名
     * prom_price 团购价
     * market_price 市场价
     * shop_price 商城价
     * prom 团购人数
     * goods_remark  商品简介
     * sales 销量
     * goods_content  商品详情
     * store_id 商户id
     * is_support_buy 是否支持单买
     * is_special 商品type
     * original_img 内页展示图
     * list_img 列表图
     *
     */
    function getGoodsList($where,$page,$pagesize,$order='is_recommend desc')
    {
        $count = M('goods')->where($where)->count();
        $goods = M('goods')->where($where)->page($page, $pagesize)->order($order)->field('goods_id,goods_name,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img')->select();

        for($i=0;$i<count($goods);$i++){
            $type = M('promote_icon')->where('goods_id = '.$goods[$i]['goods_id'])->getField('src');
            if(!empty($type)){
                $goods[$i]['icon_src'] = $type;
            }
        }
        $result = $this->listPageData($count, $goods,$pagesize);
        foreach ($result['items'] as &$v) {
            $v['original_img'] = empty($v['original_img'])?$v['original']:$v['original_img'];
        }
        return $result;
    }

    function get_OrderList($where,$page,$pagesize)
    {
        /*
         * order 订单表
         * order_id 订单id
         * goods_id 商品id
         *order_status order状态
         *shipping_status 发货状态
         *pay_status 支付状态
         * prom_id 团点单id
         * order_amount 实付金额
         * store_id 商户id
         * num 购买数量
         *order_type 订单type
         * */
        $count = M('order')->where($where)->count();
        $all = M('order')
            ->where($where)
            ->order('order_id desc')
            ->page($page, $pagesize)->field('order_id,goods_id,order_status,shipping_status,pay_status,prom_id,order_amount,store_id,num,order_type')->select();
        //团购订单处理
        $num = count($all);
        $all = $this->operationOrder($count,$all,$num);
        return $all;
    }

    function getPromList($where,$page,$pagesize)
    {
        /*
         * group_buy 团购表
         * tp_order 订单表
         * tp_goods 商品表
         * b.id 团订单id
         * b.order_id 点单id
         * b.start_time 开团时间
         * b.end_time 结束时间
         * b.goods_id 商品id
         * o.num 购买数量
         * o.prom 团人数
         * o.free 免单人数
         * */
        $count = M('group_buy')->where($where)->count();
        $all = M('group_buy')->alias('b')
            ->join('INNER JOIN tp_order o on b.order_id = o.order_id ')
            ->join('INNER JOIN tp_goods g on g.goods_id = b.goods_id ')
            ->where($where)
            ->order('b.order_id desc')
            ->page($page, $pagesize)
            ->field('b.id,b.order_id,b.start_time,b.end_time,b.goods_id,o.num,o.prom,o.free')
            ->select();
        //团购订单处理
        $num = count($all);
        $all = $this->operationOrder($count,$all,$num);

        return $all;
    }

    /*
     * 团购订单处理
     * group_buy 团购表
     * id 团id
     * goods_name 商品名
     * end_time 结束时间
     * start_time 开团时间
     * goods_num 参团人数
     * order_id 订单id
     * goods_id 商品id
     * mark 标识
     * order_goods 商品详细信息表
     * spec_key_name 規格名
     * */
    private function operationOrder($count,$all,$nums)
    {
        for ($i=0;$i<$nums;$i++){
            $all[$i]['key_name'] = M('order_goods')->where('`order_id`=' . $all[$i]['order_id'])->getField('spec_key_name');
            //判断是不是团购订单
            if (!empty($all[$i]['prom_id'])) {

                $mark = M('group_buy')->where('`id` = ' . $all[$i]['prom_id'])->field('id,goods_name,start_time,end_time,goods_num,order_id,goods_id,mark')->find();
                $all[$i]['goods_num'] = $mark['goods_num'];
                if ($mark['mark'] == 0) {//是否是团长
                    $num = M('group_buy')->where('`is_pay`=1 and `mark` = ' . $mark['id'])->count();
                    $all[$i]['type'] = 1;
                    $order_status = $this->getPromStatus($all[$i], $mark, $num);
                    $all[$i]['annotation'] = $order_status['annotation'];
                    $all[$i]['order_type'] = $order_status['order_type'];
                } elseif ($mark['mark'] != 0) {
                    $perant = M('group_buy')->where('`id` = ' . $all[$i]['prom_id'])->field('mark')->find();
                    $num = M('group_buy')->where('`mark` = ' . $perant['mark'] . ' and `is_pay`=1')->count();
                    $all[$i]['type'] = 0;
                    $order_status = $this->getPromStatus($all[$i], $mark, $num);
                    $all[$i]['annotation'] = $order_status['annotation'];
                    $all[$i]['order_type'] = $order_status['order_type'];
                }
                $all[$i]['goodsInfo'] = $goods = M('goods')->where(" `goods_id` = ".$mark['goods_id'])->field('goods_id,goods_name,prom_price,shop_price,prom,store_id,sales,is_support_buy,is_special,original_img as original,list_img as original_img')->find();
                if(!empty($all[$i]['goodsInfo'])){
                    $all[$i]['goodsInfo']['store'] = M('merchant')->where(' `id` = ' . $goods['store_id'])->field('id,store_name,store_logo,sales')->find();
                    if(empty($all[$i]['goodsInfo']['original_img'])){
                        $all[$i]['goodsInfo']['original_img'] = $all[$i]['goodsInfo']['original'];
                    }
                }else{
                    $all[$i]['goodsInfo']=null;
                }
            } elseif (empty($all[$i]['prom_id'])) {
                $all[$i]['type'] = 2;
                $order_status = $this->getStatus($all[$i]);
                $all[$i]['annotation'] = $order_status['annotation'];
                $all[$i]['goodsInfo'] = $goods = M('goods')->where(" `goods_id` = ".$all[$i]['goods_id'])->field('goods_id,goods_name,prom_price,shop_price,prom,store_id,sales,is_support_buy,is_special,original_img as original,list_img as original_img')->find();
                if(!empty($all[$i]['goodsInfo'])){
                    $all[$i]['goodsInfo']['store'] = M('merchant')->where(' `id` = ' . $goods['store_id'])->field('id,store_name,store_logo,sales')->find();
                    if(empty($all[$i]['goodsInfo']['original_img'])){
                        $all[$i]['goodsInfo']['original_img'] = $all[$i]['goodsInfo']['original'];
                    }
                }else{
                    $all[$i]['goodsInfo']=null;
                }
            }
        }
        $all = $this->listPageData($count, $all);

        return $all;
    }

    public function FormatOrderInfo($order){
        $return['order_id'] = $order['order_id'];
        $return['goods_id'] = $order['goods_id'];
        $return['num'] = $order['num'];
        $return['order_status'] = $order['order_status'];
        $return['shipping_status'] = $order['shipping_status'];
        $return['pay_status'] = $order['pay_status'];
        $return['prom_id'] = $order['prom_id'];
        $return['key_name'] = $order['key_name'];
        $return['goods_num'] = $order['goods_num'];
        $return['goods_price'] = $order['goods_price'];
        $return['order_amount'] = $order['order_amount'];
        $return['annotation'] = $order['annotation'];
        $return['order_type'] = $order['order_type'];
        $return['goodsInfo'] = $order['goodsInfo'];
        $return['storeInfo'] = $order['storeInfo'];
        $return['annotation'] = $order['annotation'];
        $return['order_type'] = $order['order_type'];

        return $return;
    }

    /**
     * 圆满执行的操作
     * 修改：17/07/05 刘亚豪 修改内容：微信消息推送 手机号中间几位用*号代替
     * @param $prom_id
     * @param string $type
     * @return int
     */
    public function getFree($prom_id,$type='')
    {
        if($prom_id==0){
            exit();
        }
        $wxtmplmsg = new WxtmplmsgController();
        /*
         * group_buy 团购表
         * tp_users 用户表
         * gb.id 团id
         * gb.mark 团标识
         * gb.is_pay 是否支付
         * gb.goods_id 商品id
         * gb.order_id 订单id
         * gb.goods_name 商品名
         * gb.goods_num 团人数
         * gb.free 免单恩叔
         * gb.is_raise 为我点赞显示
         * gb.user_id 用户id
         * gb.auto 机器人标识
         * u.openid 微信openid
         * u.nickname 用户昵称
         * u.mobile 电话号码
         * */
        $join_num = M('group_buy')->alias('gb')
            ->join('INNER JOIN tp_users u on u.user_id = gb.user_id')
            ->where('(gb.id='.$prom_id.' or gb.mark='.$prom_id.' ) and gb.is_pay=1')
            ->field("gb.id,gb.goods_id,gb.order_id,gb.goods_name,gb.goods_num,gb.free,gb.is_raise,gb.user_id,gb.auto,u.openid,u.nickname,REPLACE(u.mobile, SUBSTR(u.mobile,4,4), '****') as mobile")
            ->order('mark asc')
            ->select();
        $prom_num = $join_num[0]['goods_num'];
        $free_num = $join_num[0]['free'];
        M()->startTrans();
        //把所有人的状态改成发货
        $user_ids = "";
        for($i=0;$i<count($join_num);$i++){
            //　不是机器开团
            if($join_num[$i]['auto']==0){
                $this->order_redis_status_ref($join_num[$i]['user_id']);
                $user_ids .= $join_num[$i]['user_id'].",";
                if (empty($goodsname)) {$goodsname = $join_num[$i]['goods_name'];}
                // 如果团长发起的不是为我点赞团
                if(!empty($join_num[0]['is_raise'])){
                    if($i==0){
                        $res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>11,'order_type'=>14))->save();
                        //销量、库存
                        $goods_id = $join_num[0]['goods_id'];
                        $spec_name = M('order_goods')->where('`order_id`='.$join_num[0]['order_id'])->field('spec_key')->find();
                        M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_name[spec_key]'")->setDec('store_count',1);
                        M('goods')->where('`goods_id` = '.$goods_id)->setDec('store_count',1);//库存自减
                        M('goods')->where('`goods_id` = '.$goods_id)->setInc('sales',1);//销量自加

                        if(($join_num[0]['mobile'])!=null){
                            $name = substr_replace($join_num[0]['mobile'],'*****',3,5);
// 原有代码：                           $name = $join_num[0]['mobile'];
                        }else{
                            $name = $join_num[0]['nicknames'];
                        }
                        //　微信推送拼团成功消息
                        $wxtmplmsg->spell_success($join_num[0]['openid'],$goodsname,$name,'如果未按承诺时间发货，平台将对商家进行处罚。','【VIP专享】9.9元购买（电蚊拍充电式灭蚊拍、COCO香水型洗衣液、20支软毛牙刷）');
                    } else {
                        $res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>2,'shipping_status'=>1,'order_type'=>4))->save();
                    }
                } else {
                    if(($join_num[$i]['mobile'])!=null){
                        $name = substr_replace($join_num[$i]['mobile'],'*****',3,5);
//原有代码：                        $name = $join_num[$i]['mobile'];
                    }else{
                        $name = $join_num[$i]['nicknames'];
                    }
                    $wxtmplmsg->spell_success($join_num[$i]['openid'],$goodsname,$name,'','【VIP专享】9.9元购买（电蚊拍充电式灭蚊拍、COCO香水型洗衣液、20支软毛牙刷）');
                    $res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>11,'order_type'=>14))->save();
                }
            }else{
                $res = 1;
            }
            $res2 = M('group_buy')->where('`id`='.$join_num[$i]['id'])->data(array('is_successful'=>1))->save();
            if($res && $res2){
                M()->commit();
            }else{
                M()->rollback();
            }
        }
        //微信推送消息
        $user_ids = substr($user_ids, 0, -1);
        if (!empty($user_ids)){
            $user = M('users','','DB_CONFIG2')->where("user_id in({$user_ids})")->field('openid,nickname')->select();
            if ($user) {
                $nicknames = "";
                foreach ($user as $v){
                    $nicknames .= $v['nickname'] . '、';
                }
                $nicknames = substr($nicknames, 0, -1);
                $wxtmplmsg = new WxtmplmsgController();
                foreach ($user as $v){
                    $wxtmplmsg->spell_success($v['openid'],$goodsname,$nicknames,'如果未按承诺时间发货，平台将对商家进行处罚。','【VIP专享】9.9元购买（电蚊拍充电式灭蚊拍、COCO香水型洗衣液、20支软毛牙刷）');
                }
            }
        }

        //给参团人和开团人推送信息
        //如果有免单的处理
        if($free_num>0){
            redis("get_Free_Order_status","1");
            $order_ids =array_column($join_num,'order_id');//拿到全部参团和开团的订单id
            //随机出谁免单
            $num = getRand($free_num,($prom_num-1));
            for ($j=0;$j<count($join_num);$j++){
                for($i=0;$i<count($num);$i++){
                    if($j == $num[$i]){
                        $res = M('order')->where('`order_id`='.$order_ids[$j])->data(array('is_free'=>1))->save();
                        $res2 = M('group_buy')->where('`order_id`='.$order_ids[$j])->data(array('is_free'=>1))->save();
                        if($res && $res2){
                            $custom = array('type' => '6','id'=>$join_num[$j]['id']);
                            SendXinge('恭喜！您参与的免单拼团获得了免单',$join_num[$j]['user_id'],$custom);
                            $this->getWhere($order_ids[$j]);
                            M()->commit();
                        }else{
                            M()->rollback();
                        }
                    }else{
                        $custom = array('type' => '6','id'=>$join_num[$j]['id']);
                        SendXinge('您的免单拼团人已满，点击查看免单买家',$join_num[$j]['user_id'],$custom);
                    }
                }
            }
        }else{
            $message = "您拼的团已满，等待商家发货中";
            foreach($join_num as $val){
                if($val['auto']==0){
                    $custom = array('type' => '2','id'=>$val['id']);
                    SendXinge($message,$val['user_id'],$custom);
                }
            }
        }
        if(empty($type)){
            exit ;
        }else{
            return 1;
        }

    }

    //　记录免单订单信息，以备后面退款脚本执行退款
    public function getWhere($order_id)
    {
        $result = M('order')->where('`order_id`='.$order_id)->find();
        //标识是否为微信商城添加的免单订单
        if($result['is_jsapi']==1)
            $data['is_jsapi'] = 1;

        $data['order_id']=$order_id;// 订单id
        $data['price'] = $result['order_amount'];//免单实付价格
        $data['code'] = $result['pay_code'];//支付方式
        $data['add_time'] = time();//添加时间
        M('getwhere')->data($data)->add();
    }

    //验签
    public function encryption(){
//        $arr = empty($_GET) ? $_POST : $_GET;
//        ksort ($arr);
//        $sig = $arr['sig'];
//        unset($arr['sig']);
//        $str = "";
//        foreach ($arr as $k => $v){
//            $str .= $k . "=" . $v . "&";
//        }
//        $str .= "sig=pinquduo_sing";
//        if (md5($str) != $sig) {
//            $json_arr = array('status'=>-1,'msg'=>'无权验证','result'=>'');
//            exit(json_encode($json_arr));
//        }
    }

    public function order_redis_status_ref($user_id){
        redis("getOrderList_status_".$user_id,"1");
        redis("getCountUserOrder_status".$user_id,"1");
        redis("return_goods_list_status".$user_id,"1");
        redis("getUserPromList_status".$user_id,"1");
        redisdelall("TuiSong*");//删除推送缓存
    }

    function changStatus($where){
        $order=M('order')->alias('o')
            ->join('INNER JOIN tp_group_buy gb on gb.order_id = o.order_id ')
            ->where($where)->find();
        return $order;
    }

    //防注入
    public function injection_prevention(){
        $arr = empty($_GET) ? $_POST : $_GET;
        foreach ($arr as $value){
            if (
                strstr($value, "select") !== false ||
                strstr($value, "update") !== false ||
                strstr($value, "insert") !== false
            ) {
                $json_arr = array('status'=>-1,'msg'=>'非法接入','result'=>'');
                exit(json_encode($json_arr));
            }
        }
    }

    /**
     * 机器人
     * @param string $not_in_user_id 排除的user_id
     * @return mixed
     */
    public function get_robot($not_in_user_id='') {
        if (!empty($not_in_user_id)) {
            $user = M('','','DB_CONFIG2')->query("select user_id,nickname from tp_users order by rand() LIMIT 1");
            return $user[0];
        }
    }

    /**
     * 修改订单状态
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function changeOrderStatus($order)
    {
        $data['pay_status'] = 1;
        if(!empty($order['prom_id']))
        {
            $data['order_type'] = 11;
        }else{
            $data['order_type'] = 2;
        }
        $this->order_redis_status_ref($order['user_id']);
        //微信推送消息
        $openid = M('users','','DB_CONFIG2')->where("user_id={$order['user_id']}")->getField('openid');
        $goods_name = M('goods','','DB_CONFIG2')->where("goods_id={$order['goods_id']}")->getField('goods_name');
        $wxtmplmsg = new WxtmplmsgController();
        $wxtmplmsg->order_payment_success($openid,$order['order_amount'],$goods_name);

        //销量、库存
        M('goods')->where('`goods_id` = '.$order['goods_id'])->setInc('sales',$order['num']);
        M('merchant')->where('`id`='.$order['store_id'])->setInc('sales',$order['num']);
        $res = M('order')->where('`order_id`='.$order['order_id'])->data($data)->save();
        return $res;
    }

}