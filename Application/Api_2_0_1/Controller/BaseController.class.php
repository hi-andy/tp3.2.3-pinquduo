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
namespace Api_2_0_1\Controller;
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
        $data =  M('region', '', 'DB_CONFIG2')->select();
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
        $upload = new \Think\Upload();
//        //设置上传文件大小
//        $upload->maxSize=30120000;
//
//        $upload->rootPath = './'.C("UPLOADPATH") ; // 设置附件上传目录
//
//        //设置上传文件规则
//        $upload->saveRule='uniqid';
//        //设置需要生成缩略图，仅对图像文件有效
//        $upload->thumb = true;
//        // 设置引用图片类库包路径
//        $upload->imageClassPath ='@.ORG.Image';
//
        if(!$file){
            $file=$_FILES;
        }
//
        //$result=$upload->upload($file);
//
//        if(!$result )
//        {
//            return array();      //不存在图片则返回空
//        }else{
//            $endreturn=array();
            foreach ($result as $file) {
                $src=$file['savepath'].$file['savename'];
                $imageinfo=getimagesize(C("UPLOADPATH").$src);  //获取原图宽高
                /*生成缩略图*/
                $image = new \Think\Image();
                $image->open(C("UPLOADPATH") . $src);
                $namearr=explode('.',$file['savename']);
                $thumb_url=C("UPLOADPATH").$file['savepath'].$namearr[0].'200_200.'.$namearr[1];
                // 生成一个居中裁剪为200*200的缩略图并保存为thumb.jpg
                $image->thumb(200, 200,\Think\Image::IMAGE_THUMB_CENTER)->save($thumb_url);
                $src=$file['savepath'].$file['savename'];
                $returnData=array('origin'=>'/'.C("UPLOADPATH") . $src,'width'=>$imageinfo[0],'height'=>$imageinfo[1],'small'=>'/'.$thumb_url);
                $endreturn[]=$returnData;
            }
//            return $endreturn;
//        }

        //调用七牛云上传
        //redis("mobile_uploadimage", serialize($file),REDISTIME);
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
            $data['daifahuo'] = M('order', '', 'DB_CONFIG2')->where('(order_type = 2 or order_type = 14) and `user_id` = ' . $user_id)->count();
            $data['daishouhuo'] = M('order', '', 'DB_CONFIG2')->where('(order_type = 3 or order_type = 15) and `user_id` = ' . $user_id)->count();
            $data['daifukuan'] = M('order', '', 'DB_CONFIG2')->where('(order_type = 1 or order_type = 10) and `user_id` = ' . $user_id)->count();
            $data['refund'] = M('order', '', 'DB_CONFIG2')->where('(`order_type`=6 or `order_type`=7 or `order_type`=8 or `order_type`=9 or `order_type`=12 or `order_type`=13) and `user_id`=' . $user_id)->count();//售后
            $data['in_prom'] = M('order', '', 'DB_CONFIG2')->where('(order_type = 11 or order_type = 10) and `user_id`=' . $user_id)->count();
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
        $order_info = M('order', '', 'DB_CONFIG2')->where(array('order_sn'=>$out_trade_no))->find();
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

//    /**
//     *快递单打印信息
//     */
//    public function print_kuaidi(){
//        $url = 'http://api.kuaidi100.com/eorderapi.do?method=getElecOrder';
//
//        $data='{"partnerId":"15269563802","partnerKey":"15269563802","net":"","kuaidicom":"yuantong","kuaidinum":"883470537892631971","orderId":"278","recMan":{"name":"冯鸿飞","mobile":"13543390771","tel":"","zipCode":"","province":"","city":"","district":"","addr":"","printAddr":"广东省深圳市宝安区西乡街道圣淘沙骏园5B1603","company":""},"sendMan":{"name":"苗先生","mobile":"18002540807","tel":"","zipcode":"","province":"","city":"","district":"","addr":"","printAddr":"广东省深圳市龙岗区龙珠花园C区9栋","company":""},"cargo":"","count":"1","weight":"0.5","volumn":"","payType":"MONTHLY","expType":"标准快递","remark":"","valinsPay":"","collection":"","needChild":"0","needBack":"0","needTemplate":"1"}';
//
//        //加密sign   parma.key.cunstomer
//        $sign_data = $data.'ewAfmDpi4749'.'CDAC209E6F84C0834E546E86C23C6621';
//
//        $time = time();
//        $param= '&p='.$data;
//        $param.= '&sign='.md5($sign_data);
//        $param.= '&customer=CDAC209E6F84C0834E546E86C23C6621';
//        $param.= '&t='.$time;
//        echo $url.$param;
//        die;
//
//        /*
//        http://api.kuaidi100.com/eorderapi.do?method=getElecOrder&param={"recMan":{"name":"向刚","mobile":"13590479355","tel":"","zipCode":"","province":"广东省","city":"深圳市","district":"南山区","addr":"高新南一道2号","company":""},"sendMan":{"name":"向刚","mobile":"13590479355","tel":"","zipCode":"","province":"广东省","city":"深圳市","district":"南山区","addr":"高新南一道2号","company":""},"kuaidicom":"shunfeng","partnerId":"7554070512","partnerKey":"","net":"","kuaidinum":"","orderId":"A2147","payType":"SHIPPER","expType":"标准快递","weight":"1","volumn":"0","count":1,"remark":"备注","valinsPay":"0","collection":"0","needChild":"0","needBack":"0","cargo":"书","needTemplate":"1"}&sign=0df88f6aca30b81130c82420c4c2aafb&t=1480337087&key=ewAfmDpi4749
//        */
//
//        $post_data['partnerId'] = 'DLTlUmMA8292';
//        $post_data['kuaidicom'] = 'shunfeng';
//        $post_data['kuaidinum'] = '928378873999';
//        $post_data['recMan']['name'] = '冯鸿飞';  //收件人名称
//        $post_data['recMan']['mobile'] = '13543390771'; //收件人手机
//        $post_data['recMan']['tel'] = '';
//        $post_data['recMan']['zipCode']  = '';
//        $post_data['recMan']['province'] = '广东省';
//        $post_data['recMan']['city'] = '深圳市';
//        $post_data['recMan']['district'] = '宝安区';
//        $post_data['recMan']['addr'] = '众里创业社区410';
//        $post_data['sendMan']['name'] = '苗先生';
//        $post_data['sendMan']['mobile'] = '18002540807';
//        $post_data['sendMan']['province'] = '广东省';
//        $post_data['sendMan']['city'] = '深圳市';
//        $post_data['sendMan']['district'] = '龙岗区';
//        $post_data['sendMan']['addr'] = '龙珠花园C区9栋';
//        $post_data['cargo'] = '手表';
//        $post_data['count'] = 1;
//        $post_data['needBack'] = 1;
//        $post_data['needTemplate'] = 1;
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        curl_setopt($ch, CURLOPT_URL,$url);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
//        $result = curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）
//    }

    /*
     * 用商户名关键字做检索
     * */
    public function getStoreWhere($where,$store_name)
    {
        $store_id = M('merchant', '', 'DB_CONFIG2')->where("`store_name` like '%".$store_name."%'")->select();
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

    //版本2.0.0
    //调度商品详情
    function  getGoodsInfo($goods_id,$type='')
    {
        $goods = M('goods', '', 'DB_CONFIG2')->where(" `goods_id` = $goods_id")->field('goods_id,cat_id,goods_name,prom_price,market_price,shop_price,prom,goods_remark,sales,goods_content,store_id,is_support_buy,is_special,original_img as original,list_img as original_img')->find();
        if(!empty($goods)){
            //商品详情
            $goods['goods_content_url'] = C('HTTP_URL') . '/Api/goods/get_goods_detail?id=' . $goods_id;
            $goods['goods_share_url'] = C('SHARE_URL') . '/goods_detail.html?goods_id=' . $goods_id;
            $store = M('merchant', '', 'DB_CONFIG2')->where(' `id` = ' . $goods['store_id'])->field('id,store_name,store_logo,sales,mobile')->find();
            $store['store_logo'] = TransformationImgurl($store['store_logo']);
            $goods['store'] = $store;
            if(empty($goods['original_img'])){
                $goods['original_img'] =TransformationImgurl($goods['original']);
            }else{
                $goods['original_img'] =TransformationImgurl($goods['original_img']);
            }
            $goods['original'] =TransformationImgurl($goods['original']);
            $goods['fenxiang_url'] = $goods['original']."?imageView2/1/w/400/h/400/q/75%7Cwatermark/1/image/aHR0cDovL2Nkbi5waW5xdWR1by5jbi9QdWJsaWMvaW1hZ2VzL2ZlbnhpYW5nX2xvZ29fNDAwLmpwZw==/dissolve/100/gravity/South/dx/0/dy/0%7Cimageslim";
            if($type!=1){
                $goods['img_arr'] = getImgs($goods['goods_content']);
                $goods['img_arr'] = getImgSize($goods['img_arr']);

                //获取店铺优惠卷store_logo_compression
                $coupon = M('coupon', '', 'DB_CONFIG2')->where('`store_id` = ' . $goods['store_id'] . ' and `send_start_time` <= ' . time() . ' and `send_end_time` >= ' . time() . ' and createnum!=send_num')->select();
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

    //版本2.0.0
    //调度商品列表
    function getGoodsList($where,$page,$pagesize,$order='is_recommend desc')
    {
//        if($page<=2){
//            $order = 'sort asc';
//            $id_arr = '1=1';
//        }else{
//            $id = M('goods')->where('`show_type`=0 and is_show=1 and is_on_sale=1 and is_audit=1')->where($where)->order('sort asc')->limit('0,40')->field('goods_id')->select();
//            $id_arr = ' goods_id not in (';
//            foreach ($id as $v) {
//                $id_arr .= $v['goods_id'] . ",";
//            }
//            $id_arr = substr($id_arr, 0, -1);
//            $id_arr = $id_arr.")";
//        }
        $count = M('goods', '', 'DB_CONFIG2')->where($where)->count();
        $goods = M('goods', '', 'DB_CONFIG2')->where($where)->page($page, $pagesize)->order($order)->field('goods_id,goods_name,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img')->select();
        $result = $this->listPageData($count, $goods,$pagesize);
        foreach ($result['items'] as &$v) {
            $v['original_img'] = empty($v['original_img'])?$v['original']:$v['original_img'];
        }
        return $result;
    }

    function get_OrderList($where,$page,$pagesize)
    {
        $count = M('order', '', 'DB_CONFIG2')->where($where)->count();
        $all = M('order', '', 'DB_CONFIG2')
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
        $count = M('group_buy', '', 'DB_CONFIG2')->where($where)->count();
        $all = M('group_buy', '', 'DB_CONFIG2')->alias('b')
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

    //团购订单处理
    private function operationOrder($count,$all,$nums)
    {
        for ($i=0;$i<$nums;$i++){
            $all[$i]['key_name'] = M('order_goods')->where('`order_id`=' . $all[$i]['order_id'])->getField('spec_key_name');
            //判断是不是团购订单
            if (!empty($all[$i]['prom_id'])) {
                $mark = M('group_buy', '', 'DB_CONFIG2')->where('`id` = ' . $all[$i]['prom_id'])->field('id,goods_name,end_time,end_time,goods_num,order_id,goods_id,mark,goods_num')->find();
                $all[$i]['goods_num'] = $mark['goods_num'];
                if ($mark['mark'] == 0) {
                    $num = M('group_buy', '', 'DB_CONFIG2')->where('`is_pay`=1 and `mark` = ' . $mark['id'])->count();
                    $all[$i]['type'] = 1;
                    $order_status = $this->getPromStatus($all[$i], $mark, $num);
                    $all[$i]['annotation'] = $order_status['annotation'];
                    $all[$i]['order_type'] = $order_status['order_type'];
                } elseif ($mark['mark'] != 0) {
                    $perant = M('group_buy', '', 'DB_CONFIG2')->where('`id` = ' . $all[$i]['prom_id'])->field('mark')->find();
                    $num = M('group_buy', '', 'DB_CONFIG2')->where('`mark` = ' . $perant['mark'] . ' and `is_pay`=1')->count();
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
                $all[$i]['goodsInfo'] = $goods = M('goods', '', 'DB_CONFIG2')->where(" `goods_id` = ".$all[$i]['goods_id'])->field('goods_id,goods_name,prom_price,shop_price,prom,store_id,sales,is_support_buy,is_special,original_img as original,list_img as original_img')->find();
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

    public function getFree($prom_id,$type='')
    {
        $join_num = M('group_buy')->where('(`id`='.$prom_id.' or `mark`='.$prom_id.') and `is_pay`=1')->field('id,goods_id,order_id,goods_name,goods_num,free,is_raise,user_id,auto')->order('mark asc')->select();
        $prom_num = $join_num[0]['goods_num'];
        $free_num = $join_num[0]['free'];
        M()->startTrans();
        //把所有人的状态改成发货
        $user_ids = "";
        for($i=0;$i<count($join_num);$i++){
            if($join_num[$i]['auto']==0){
                $this->order_redis_status_ref($join_num[$i]['user_id']);
                $user_ids .= $join_num[$i]['user_id'].",";
                $goodsname = $join_num[$i]['goods_name'];
                if(!empty($join_num[0]['is_raise'])){
                    if($i==0){
                        $res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>11,'order_type'=>14))->save();
                        //销量、库存
                        $goods_id = $join_num[$i]['goods_id'];
                        $spec_name = M('order_goods')->where('`order_id`='.$join_num[$i]['order_id'])->field('spec_key')->find();
                        M('spec_goods_price')->where("`goods_id`=$goods_id and `key`='$spec_name[spec_key]'")->setDec('store_count',1);
                        M('goods')->where('`goods_id` = '.$goods_id)->setDec('store_count',1);

                    } else {
                        $res = M('order')->where('`prom_id`='.$join_num[$i]['id'])->data(array('order_status'=>2,'shipping_status'=>1,'order_type'=>5))->save();
                    }
                } else {
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
                $wxtmplmsg = new WxtmplmsgController();
                foreach ($user as $v){
                    $nicknames .= $v['nickname'] . '、';
                    $wxtmplmsg->spell_success($v['openid'],$goodsname,$nicknames);
                }
            }

        }


        if($free_num>0){//如果有免单，才执行getRand操作
            redis("get_Free_Order_status","1");
            $order_ids =array_column($join_num,'order_id');//拿到全部参团和开团的订单id
            //给参团人和开团人推送信息
            $num = $this->getRand($free_num,($prom_num-1));//随机出谁免单
            for ($j=0;$j<count($join_num);$j++){
                for($i=0;$i<count($num);$i++){
                    if($j == $num[$i]){
                        $order_id = $order_ids[$j];
                        $res = M('order')->where('`order_id`='.$order_id)->data(array('is_free'=>1))->save();
                        $res2 = M('group_buy')->where('`order_id`='.$order_id)->data(array('is_free'=>1))->save();
                        if($res && $res2){
                            $custom = array('type' => '6','id'=>$join_num[$j]['id']);
                            SendXinge('恭喜！您参与的免单拼团获得了免单',$join_num[$j]['user_id'],$custom);
                            $this->getWhere($order_id);
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

    public function getWhere($order_id)
    {
        $result = M('order')->where('`order_id`='.$order_id)->find();
        if($result['is_jsapi']==1)
            $data['is_jsapi'] = 1;
        $data['order_id']=$order_id;
        $data['price'] = $result['order_amount'];
        $data['code'] = $result['pay_code'];
        $data['add_time'] = time();
        M('getwhere')->data($data)->add();
    }

    public function getRand($num,$max)//需要生成的个数，最大值
    {
        $rand_array=range(0,$max);
        shuffle($rand_array);//调用现成的数组随机排列函数
//		var_dump(array_slice($rand_array,0,$num));
        return array_slice($rand_array,0,$num);//截取前$num个
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
            $user = M('','','DB_CONFIG2')->query("select user_id from tp_users order by rand() LIMIT 1");
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
        $openid = M('users')->where("user_id={$order['user_id']}")->getField('openid');
        $goods_name = M('goods')->where("goods_id={$order['goods_id']}")->getField('goods_name');
        $wxtmplmsg = new WxtmplmsgController();
        $wxtmplmsg->order_payment_success($openid,$order['order_amount'],$goods_name);
        redis("wxtmplmsg","123",100);

        //销量、库存
        M('goods')->where('`goods_id` = '.$order['goods_id'])->setInc('sales',$order['num']);
        M('merchant')->where('`id`='.$order['store_id'])->setInc('sales',$order['num']);
        $res = M('order')->where('`order_id`='.$order['order_id'])->data($data)->save();
        return $res;
    }
}