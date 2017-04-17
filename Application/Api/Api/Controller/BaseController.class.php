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
namespace Api\Controller;
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
        if($_REQUEST['test'] == '1')
        {
            $test_str = 'POST'.print_r($_POST,true);
            $test_str .= 'GET'.print_r($_GET,true);
            file_put_contents('a.html', $test_str);            
        }
        $this->user_id = I("user_id",0); // 用户id   
        if($this->user_id)
        {
            $this->user = M('users')->where("user_id = {$this->user_id}")->find();
        }        
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() {
                 
		//exit(array('status'=>-1,'msg'=>'请修改注释Application\Api\Controller\BaseController.class.php 文件42行打开手机接口','result'=>'')); //  开启后注释掉这行代码即可
		
        $local_sign = $this->getSign();
        $api_secret_key = C('API_SECRET_KEY');        
        // 不参与签名验证的方法
        /*
        if(!in_array(strtolower(ACTION_NAME), array('getservertime','getconfig','alipaynotify')))
        {        
            if($local_sign != $_POST['sign'])
            {    
                $json_arr = array('status'=>-1,'msg'=>'签名失败!!!','data'=>'' );
                exit(json_encode($json_arr));

            }
            if(time() - $_POST['time'] > 600)
            {    
                $json_arr = array('status'=>-1,'msg'=>'请求超时!!!','data'=>'' );
                exit(json_encode($json_arr));
            }
        }
        */
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
    function listPageData($total=0,$items=array()) {
        $pagesize = I('request.pagesize', C('PAGE_SIZE'), 'intval');
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
            if ($order['pay_status']==0 && ($order['order_status']==1 || $order['order_status']==8)) {
                //待支付
                $status['annotation'] = '待付款';
                $status['order_type'] = '1';
            } elseif ($order['pay_status']==1 && $order['order_status']==1 && $order['shipping_status']!=1) {
                //待发货
                $status['annotation'] = '待发货';
                $status['order_type'] = '2';
            } elseif ($order['shipping_status']==1 && $order['order_status']==1 && $order['pay_status']==1) {
                //待收货
                $status['annotation'] = '待收货';
                $status['order_type'] = '3';
            } elseif ($order['order_status']==2 && $order['pay_status']==1 && $order['shipping_status']==1) {
                //'已完成'
                $status['annotation'] = '已完成';
                $status['order_type'] = '4';
            } elseif ($order['order_status']==3) {
                //'已取消'
                $status['annotation'] = '已取消';
                $status['order_type'] = '5';
            } elseif ($order['order_status']==4 && $order['pay_status']==1) {
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
            }elseif($order['order_type']==16 || $order['order_status']==15){
                $status['annotation'] = '拒绝受理';
                $status['order_type'] = '16';
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
        //设置上传文件大小
        $upload->maxSize=30120000;

        $upload->rootPath = './'.C("UPLOADPATH") ; // 设置附件上传目录

        //设置上传文件规则
        $upload->saveRule='uniqid';
        //设置需要生成缩略图，仅对图像文件有效
        $upload->thumb = true;
        // 设置引用图片类库包路径
        $upload->imageClassPath ='@.ORG.Image';

        if(!$file){
            $file=$_FILES;
        }

        $result=$upload->upload($file);

        if(!$result )
        {
            return array();      //不存在图片则返回空
        }else{
            $endreturn=array();
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
            return $endreturn;
        }
    }

    public function h5_uploadimage(){
        $res = $this->mobile_uploadimage();

        $res = $res[0];

        $this->getJsonp($res);
    }

    public function getCountUserOrder($user_id)
    {
        //获取订单信息
        $data['daifahuo'] = M('order')->where('`pay_status` = 1 and (`order_status` = 1 or `order_status` = 11) and `shipping_status` != 1  and `user_id` = '.$user_id)->count();
        $data['daishouhuo'] = M('order')->where('`pay_status` = 1 and `shipping_status` = 1 and (`order_status` = 1 or `order_status` = 11) and `user_id` = '.$user_id)->count();
        $data['daifukuan'] = M('order')->where('`pay_status` = 0 and (`order_status` = 1 or `order_status` = 8 ) and `is_cancel`=0 and `user_id` = '.$user_id)->count();
        $data['refund'] = M('order')->where('(`order_type`=6 or `order_type`=7 or `order_type`=8 or `order_type`=9 or `order_type`=12 or `order_type`=13) and `user_id`='.$user_id)->count();//售后
//        $mark = M('group_buy')->where('`mark`=0 and `is_cancel`=0 and `user_id` = '.$user_id.' and `end_time`>='.time())->select();
//        $count = '0';
//        if(!empty($mark))
//        {
//            foreach($mark as &$v)
//            {
//                $num[] = M('group_buy')->where('`mark` = '.$v['id'])->count();
//            }
//            for($i = 0;$i<count($num);$i++)
//            {
//                if(($num[$i]+1) < $mark[$i]['goods_num'])
//                {
//                    $count++;
//                }
//            }
//        }
//        //再计算参与的团
//        $mark2 = M('group_buy')->where('`mark`!=0 and `is_cancel`=0 and `user_id` = '.$user_id.' and `end_time`>='.time())->select();
//        if(!empty($mark2))
//        {
//            foreach($mark2 as &$v)
//            {
//                $num2[] = M('group_buy')->where('`mark` = '.$v['id'])->count();
//                for($i = 0;$i<count($num2);$i++)
//                {
//                    if(($num2[$i]+1) < $mark[$i]['goods_num'])
//                    {
//                        $count++;
//                    }
//                }
//            }
//        }
        $mark = M('group_buy')->where('`is_successful`=0 and `is_cancel`=0 and `user_id` = '.$user_id.' and `end_time`>='.time())->count();
        $data['in_prom'] = $mark;

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


    /**
     *快递单打印信息
     */
    public function print_kuaidi(){
        $url = 'http://api.kuaidi100.com/eorderapi.do?method=getElecOrder';

        $data='{"partnerId":"15269563802","partnerKey":"15269563802","net":"","kuaidicom":"yuantong","kuaidinum":"883470537892631971","orderId":"278","recMan":{"name":"冯鸿飞","mobile":"13543390771","tel":"","zipCode":"","province":"","city":"","district":"","addr":"","printAddr":"广东省深圳市宝安区西乡街道圣淘沙骏园5B1603","company":""},"sendMan":{"name":"苗先生","mobile":"18002540807","tel":"","zipcode":"","province":"","city":"","district":"","addr":"","printAddr":"广东省深圳市龙岗区龙珠花园C区9栋","company":""},"cargo":"","count":"1","weight":"0.5","volumn":"","payType":"MONTHLY","expType":"标准快递","remark":"","valinsPay":"","collection":"","needChild":"0","needBack":"0","needTemplate":"1"}';

        //加密sign   parma.key.cunstomer
        $sign_data = $data.'ewAfmDpi4749'.'CDAC209E6F84C0834E546E86C23C6621';

        $time = time();
        $param= '&p='.$data;
        $param.= '&sign='.md5($sign_data);
        $param.= '&customer=CDAC209E6F84C0834E546E86C23C6621';
        $param.= '&t='.$time;
        echo $url.$param;
        die;

        /*
        http://api.kuaidi100.com/eorderapi.do?method=getElecOrder&param={"recMan":{"name":"向刚","mobile":"13590479355","tel":"","zipCode":"","province":"广东省","city":"深圳市","district":"南山区","addr":"高新南一道2号","company":""},"sendMan":{"name":"向刚","mobile":"13590479355","tel":"","zipCode":"","province":"广东省","city":"深圳市","district":"南山区","addr":"高新南一道2号","company":""},"kuaidicom":"shunfeng","partnerId":"7554070512","partnerKey":"","net":"","kuaidinum":"","orderId":"A2147","payType":"SHIPPER","expType":"标准快递","weight":"1","volumn":"0","count":1,"remark":"备注","valinsPay":"0","collection":"0","needChild":"0","needBack":"0","cargo":"书","needTemplate":"1"}&sign=0df88f6aca30b81130c82420c4c2aafb&t=1480337087&key=ewAfmDpi4749
        */

        $post_data['partnerId'] = 'DLTlUmMA8292';
        $post_data['kuaidicom'] = 'shunfeng';
        $post_data['kuaidinum'] = '928378873999';
        $post_data['recMan']['name'] = '冯鸿飞';  //收件人名称
        $post_data['recMan']['mobile'] = '13543390771'; //收件人手机
        $post_data['recMan']['tel'] = '';
        $post_data['recMan']['zipCode']  = '';
        $post_data['recMan']['province'] = '广东省';
        $post_data['recMan']['city'] = '深圳市';
        $post_data['recMan']['district'] = '宝安区';
        $post_data['recMan']['addr'] = '众里创业社区410';
        $post_data['sendMan']['name'] = '苗先生';
        $post_data['sendMan']['mobile'] = '18002540807';
        $post_data['sendMan']['province'] = '广东省';
        $post_data['sendMan']['city'] = '深圳市';
        $post_data['sendMan']['district'] = '龙岗区';
        $post_data['sendMan']['addr'] = '龙珠花园C区9栋';
        $post_data['cargo'] = '手表';
        $post_data['count'] = 1;
        $post_data['needBack'] = 1;
        $post_data['needTemplate'] = 1;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）
    }

    /**
     * 定时更新订单状态
     */
    public function CheckOrderStatus(){
        //超过三十分钟的未支付订单修改为过期
        $condition['pay_status'] = 0;
        $condition['_string'] = " `add_time` <= CURRENT_TIMESTAMP - INTERVAL 30 MINUTE ";
        M('order')->where($condition)->select();
        echo M()->getLastSql();
        die;
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

    public  function test(){
        $custom = array('type' => 'group','id'=>'404');

        $result = SendXinge('测试'.date('Y-m-d H:i:s'),'81',$custom);
        var_dump($result);
    }

    /**
     * 测试
     */
    public function testJsApi(){
        $Weixin = new WeixinpayController();

        $order = M('order')->where(array('order_id'=>4925))->find();

        $res = $Weixin->getJSAPI($order);

        var_dump($res);
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
                $pic = '/data/wwwroot/default/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.gif';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.gif';
                imagejpeg($bigImg, $pic);
                break;
            case 2: //jpg
//                header('Content-Type:image/jpg');
                $pic = '/data/wwwroot/default/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.jpg';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.jpg';
                imagejpeg($bigImg, $pic);
                break;
            case 3: //png
//                header('Content-Type:image/png');
                $pic = '/data/wwwroot/default/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.png';
                $pin = '/Public/upload/fenxiang/'.$goods_id.'_'.$store_id.'.png';
                imagejpeg($bigImg, $pic);
                break;
            default:
                # code...
                break;
        }
        return $pin;
    }
}