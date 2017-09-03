<?php
/**
 *
 */ 
namespace Clound\Controller;
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
        $currentpage = max(1, $currentpage);
        $currentpage = min($currentpage, $totalpage);
        $nextpage = min($currentpage+1, $totalpage);
        return  compact('total', 'totalpage', 'pagesize', 'currentpage', 'nextpage', 'items');
    }

    function getStatus($order)//订单表详情
    {
            if ($order['pay_status'] == 0 && $order['order_status'] == 0) {
                //待支付
                $status['annotation'] = '待支付';
                $status['order_type'] = '1';
            } elseif ($order['pay_status']==1 && $order['order_status']==1 && $order['shipping_status']!=1) {
                //待发货
                $status['annotation'] = '待发货';
                $status['order_type'] = '2';
            } elseif ($order['shipping_status']==1 && $order['order_status']==1 && $order['pay_status']==1) {
                //待收货
                $status['annotation'] = '待收货';
                $status['order_type'] = '3';
            } elseif ($order['order_status']==2 && $order['pay_status']==1) {
                //'已取消'
                $status['annotation'] = '已完成';
                $status['order_type'] = '4';
            } elseif ( $order['pay_status']==0 && $order['order_status']==3 && $order['shipping_status']==0) {
                //'已完成'
                $status['annotation'] = '已取消';
                $status['order_type'] = '5';
            } elseif ($order['order_status']==4 && $order['pay_status']==1) {
                //'已完成'
                $status['annotation'] = '待退款';
                $status['order_type'] = '6';
            } elseif ($order['order_status']==5 && $order['pay_status']==1) {
                //'已完成'
                $status['annotation'] = '已退款';
                $status['order_type'] = '7';
            }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==6) {
                $status['annotation'] = '待退货';
                $status['order_type'] = '8';
            }elseif($order['pay_status']==1 && $order['shipping_status']==1 && $order['order_status']==7) {
                $status['annotation'] = '已退货';
                $status['order_type'] = '9';
            }else{
                $status['annotation'] = '订单状态异常';
                $status['order_type'] = null;
            }
        return $status;
    }

    function getPromStatus($order,$prom,$num)//订单表详情、团购表详情、参团人数
    {
        if(($num+1)<$prom['goods_num'] && ($prom['end_time']>time()) && $order['pay_status']==0 && $order['order_status']==8 && $order['shipping_status']!=1){
            $status['annotation'] = '拼团中,未付款';
            $status['order_type'] = '10';
        }
        elseif(($num+1)<$prom['goods_num'] && $prom['end_time']>time() && $order['pay_status']==1 && $order['order_status']==8 && $order['shipping_status']!=1){
            $status['annotation'] = '拼团中,已付款';
            $status['order_type'] = '11';
        }
        elseif(($num+1)<$prom['goods_num'] && $prom['end_time'] < time() && $order['pay_status']==1 && $order['order_status']==9 ){
            $status['annotation'] = '未成团,待退款';
            $status['order_type'] = '12';
        }
        elseif(($num+1)<$prom['goods_num'] && $prom['end_time'] < time() && $order['pay_status']==1 && $order['order_status']==10){
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

    public function getCountUserOrder($user_id)
    {
        //获取订单信息
        $data['daifahuo'] = M('order')->where('`pay_status`=1 and `order_status`=1 and `shipping_status`!=1 and `user_id`='.$user_id)->count();
        $data['daishouhuo'] = M('order')->where('`order_status`=1 and `shipping_status`=1 and `pay_status`=1 and `user_id`='.$user_id)->count();
        $data['daifukuan'] = M('order')->where('`pay_status` = 0 and `order_status` =0 and `user_id` = '.$user_id)->count();
        $data['success'] = M('order')->where('`order_status`=2 and `shipping_status`=1 and `pay_status`=1 and `user_id` = '.$user_id)->count();
        $data['refund'] = M('order')->where('`pay_status`=1 and (`order_status`=6 or `order_status`=7 or `order_status`=4  or `order_status`=5  or `order_status`=9  or `order_status`=10) and `user_id`='.$user_id)->count();

        //拼团中
        //先拿到自己开的团，再算人数和开团人数是否相等
        $mark = M('group_buy')->where('`mark` = 0 and `user_id` = '.$user_id)->select();
        $count = '0';
        if(!empty($mark))
        {
            foreach($mark as &$v)
            {
                $num[] = M('group_buy')->where('`mark` = '.$v['id'])->count();
            }

            for($i = 0;$i<count($num);$i++)
            {
                if(($num[$i]+1) < $mark[$i]['goods_num'])
                {
                    $count++;
                }
            }
        }
        //再计算参与的团
        $mark2 = M('group_buy')->where('`mark` != 0 and `user_id` = '.$user_id)->select();
        if(!empty($mark2))
        {
            foreach($mark2 as &$v)
            {
                $num2[] = M('group_buy')->where('`mark` = '.$v['id'])->count();
                for($i = 0;$i<count($num2);$i++)
                {
                    if(($num2[$i]+1) < $mark[$i]['goods_num'])
                    {
                        $count++;
                    }
                }
            }
        }
        $data['count'] = $count;

        return $data;
    }
}