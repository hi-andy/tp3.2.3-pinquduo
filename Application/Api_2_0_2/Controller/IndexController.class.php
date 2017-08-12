<?php
namespace Api_2_0_2\Controller;



class IndexController extends BaseController {
    public $version = null;
    public function _initialize() {
        $version = I('version');
        header("Access-Control-Allow-Origin:*");
//        $this->encryption();
    }

    public function index(){
        print_r(redis("wxtmplmsg"));
    }

    /*
     * 获取APP首页数据
     */
    public function home(){
//        var_dump($version);die;
        $page = I('page', 1);
        $pagesize = I('pagesize', 8);
        $version = I('version');
        $rdsname = "home".$version.$page.$pagesize;
        if (empty(redis($rdsname))) { //判断缓存是否存在
            //获取轮播图 ab轮播表 pid = 1 是首页轮播id enabled = 1 是显示的
            /*
             * ad_link=>banner跳转链接
             * ad_name=>banner名字
             * ad_code=>图片地址
             * type=>跳转类型
             */
            $data = M('ad')->where('pid = 1 and `enabled`=1')->field(array('ad_link', 'ad_name', 'ad_code', 'type'))->select();
            foreach ($data as & $v) {
                $v['ad_code'] = TransformationImgurl($v['ad_code']);
            }
            //中间图标 group_category APP首页展示的icon图标 不显示 8,9
            $category = M('group_category')->where('`id` != 9 and `id` != 8')->select();
            foreach ($category as &$v) {
                //TransformationImgurl 进行图片地址转换
                $v['cat_img'] = TransformationImgurl($v['cat_img']);
            }
            $category[0]['id'] = 'http://wx.pinquduo.cn/likes.html';
            $category[0]['cat_img'] = CDN .'/Public/upload/index/freewangzhe.gif';
	        $category[3]['cat_name'] = '趣多严选';
	        $category[3]['cat_img'] = CDN .'/Public/upload/index/quduoyanxuan.jpg';
            $category[4]['cat_name'] = '为我拼';
            $category[4]['cat_img'] = CDN .'/Public/upload/index/5-weiwo.jpg';
            $category[7]['cat_name'] = '省钱大法';
            $category[7]['cat_img'] = CDN . '/Public/upload/index/8-shenqian.gif';
            //中间活动模块
//            $activity['banner_url'] = CDN . '/Public/images/daojishibanner.jpg';
//            $activity['H5_url'] = 'http://pinquduo.cn/index.php?s=/Api/SecondBuy/';
//            $activity['logo_url'] = 'http://cdn.pinquduo.cn/activity.gif';
            $activity = null;

            $where = '`show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_recommend`=1 and `is_special` in (0,1) and `is_audit`=1 ';
            //getGoodsList  获取商品列表
            $result2 = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('goodsList' => $result2, 'activity' => $activity, 'ad' => $data, 'cat' => $category));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     * 获取服务器配置
     */
    public function getConfig()
    {
        $config_arr = M('config')->select();
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$config_arr)));
    }
    /**
     * 获取插件信息
     */
    public function getPluginConfig()
    {
        $data = M('plugin')->where("type='payment' OR type='login'")->select();
        $arr = array();
        foreach($data as $k=>$v){
            unset( $data[$k]['config']);
            unset( $data[$k]['config']);

            $data[$k]['config_value'] = unserialize($v['config_value']);
            if($data[$k]['type'] == 'payment'){
                $arr['payment'][] =  $data[$k];
            }
            if($data[$k]['type'] == 'login'){
                $arr['login'][] =  $data[$k];
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$arr ? $arr : '')));
    }

    function getHeader()
    {
        $header = M('goods_category')->where(' `parent_id` = 0 ')->field('id,name')->limit(8)->order('sort_order asc')->select();
        array_unshift($header,array('id'=>'0','name'=>'首页'));

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = $header;
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit($json);
    }

    /* 海淘页面 */
    public function getHaiTao()
    {
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $version= I('version');
        $rdsname = "getHaiTao".$version.$page.$pagesize.$version;
        if(empty(redis($rdsname))) {//判断是否有缓存
            //头部分类
            $directory = M('haitao_style')->select();
            foreach ($directory as &$v) {
                //转换图片地址
                $v['logo'] = TransformationImgurl($v['logo']);
            }
                //中间分类
                $directory2 = array('id' => 0, 'name' => '海淘专区', 'logo' => CDN . '/Public/upload/category/img_international@3x.png');
            //haitao 海淘分类表
            /*
             * id 分类id
             * name 分类名
             * img 分类icon地址
             * logo 菜单图标
             * */
                $directory2['cat2'] = M('haitao')->where('`parent_id` = 0')->field('id,name,img,logo')->limit('4')->select();
                foreach ($directory2['cat2'] as &$v) {
                    $v['img'] = TransformationImgurl($v['img']);
                }
                for ($i = 0; $i < count($directory2['cat2']); $i++) {
                    $directory2 ['cat2'][$i]['cat3'] = M('haitao')->where('`parent_id` = ' . $directory2['cat2'][$i]['id'])->field('id,name')->select();
                    array_unshift($directory2['cat2'][$i]['cat3'], array('id' => '0', 'name' => '全部'));
                }
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * */
                $where = '`show_type`=0 and is_special=1 and `is_on_sale`=1 and is_audit=1 and `is_show`=1 and haitao_cat != 65 ';
                $order = 'is_recommend desc,sort asc';
                $data = $this->getGoodsList($where,$page,$pagesize,$order);

            $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('goods' => $data, 'directory' => $directory, 'directory2' => $directory2));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));

    }

    //9.9专场
    function getJiuJiu()
    {
        $page = I('page', 1);
        $pagesize = I('pagesize', 20);
        $version = I('version');
        $rdsname = "getJiuJiu".$version.$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存
            //获取轮播图 ab轮播表 pid = 2 是99专场轮播id enabled = 1 是显示的
            /*
             * ad_name=>banner名字
             * ad_code=>图片地址
             * type=>跳转类型
             */
            $banner = M('ad')->where('pid = 2 and `enabled`=1')->field(array('ad_name', 'ad_code', 'type'))->find();
            $banner['ad_code'] = TransformationImgurl($banner['ad_code']);
            //中间四个小块
            $banner2 = M('exclusive')->select();

            foreach ($banner2 as &$v) {
                $v['img'] = TransformationImgurl($v['img']);
            }
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * */
            $where = '`show_type`=0 and is_special = 4 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'banner2' => $banner2, 'goods' => $data));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') && $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    //点击99专场的分类
    function getJIuJIuCategory()
    {
        $id = I('id');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $version= I('version');
        $rdsname = "getJIuJIuCategory".$id.$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存 //获取轮播图
            /*
             * exclusive 99专场专场表
             * */
            $banner = M('exclusive')->where('id =' . $id)->field(array('banner'))->find();
            $banner['banner'] = TransformationImgurl($banner['banner']);
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * exclusive_cat 专场id
             * */
            $where = '`show_type`=0 and `is_special`=4  and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `exclusive_cat` = ' . $id ;
            $data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('banner' => $banner, 'goods' => $data));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    /*
     *  限时秒杀
     *
     * */
    function get_Seconds_Kill_time()
    {
        $today_zero = strtotime(date('Y-m-d', time()));//将当天凌晨
        $today_zero2 = strtotime(date('Y-m-d', (time() + 1 * 24 * 3600)));//隔天凌晨
        //取出时间段
        $sql = "SELECT FROM_UNIXTIME(`on_time`,'%Y-%m-%d %H') as datetime from " . C('DB_PREFIX') . "goods WHERE `is_on_sale`=1 and `is_audit`=1 and `is_special` = 2 and `on_time`>=$today_zero and `on_time`<$today_zero2  GROUP BY `datetime`";
        $time = M('')->query($sql);
        //如果当天没发布商品，就把之前的商品找出来 往前找三天
        if (empty($time)) {
            for ($j = 1;$j<4; $j++) {
                $today_zero = $today_zero - $j * 24 * 3600;
                $today_zero2 = $today_zero2 - $j * 24 * 3600;
                $sql = "SELECT FROM_UNIXTIME(`on_time`,'%Y-%m-%d %H') as datetime from " . C('DB_PREFIX') . "goods WHERE `is_on_sale`=1 and `is_audit`=1 and `is_special` = 2 and `on_time`>=$today_zero and `on_time`<$today_zero2  GROUP BY `datetime`";
                $time = M('')->query($sql);
                if (!empty($time))
                    break;
            }
        }
        //给时间段增加文字
        for ($i = 0; $i < count($time); $i++) {
            if ($time[$i]['datetime'] == date('Y-m-d H')) {
                $time[$i]['title'] = '抢购中';
            } else if ($time[$i]['datetime'] < date('Y-m-d H')) {
                $time[$i]['title'] = '已开抢';
            } else {
                $time[$i]['title'] = '即将开始';
            }
        }
        $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('time' => $time));

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    function get_Seconds_Kill()
    {
        $starttime =I('starttime');//起始时间
        $version= I('version');//版本号
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $rdsname = "get_Seconds_Kill".$version.$starttime.$page;
        if (redis("get_Seconds_Kill_status") == "1"){
            redisdelall("get_Seconds_Kill*");
            redisdelall("get_Seconds_Kill_status");
        }
        if(empty(redis($rdsname))) {//判断是否有缓存
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * on_time 秒杀时间
             * */
            $count = M('goods')->where("`on_time` = $starttime and `is_show` = 1 and `show_type`=0 and `is_audit`=1 and`is_on_sale`=1 and `is_special` = 2 and `is_audit`=1")->count();
            $goods = M('goods')->where("`on_time` = $starttime and `is_show` = 1 and `show_type`=0 and `is_audit`=1 and`is_on_sale`=1 and `is_special` = 2 and `is_audit`=1")->field('goods_id,goods_name,market_price,shop_price,original_img,prom,prom_price,is_special,store_count,sales')->page($page, $pagesize)->order('is_recommend desc,sort asc')->select();
            $data = $this->listPageData($count, $goods);
            foreach ($data['items'] as &$v) {
                $v['original'] = TransformationImgurl($v['original_img']);
                $v['original_img'] = TransformationImgurl($v['original_img']);
            }
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /*
	* 探索
	* */
    function getexplore()
    {
        $rdsname = "getexplore";
        if(empty(redis($rdsname))) {//判断是否有缓存
            /*
             * goods_category 商品分类表 10044是邮费补拍不显示
             * */
            $category = M('goods_category');
            $cat1 = $category->where('`parent_id` = 0 and id != 10044')->order('sort_order asc')->field('id,name,logo')->select();
            for ($i = 0; $i < count($cat1); $i++) {
                $cat1[$i]['logo'] = TransformationImgurl($cat1[$i]['logo']);
                $cat1[$i]['cat2'] = $category->where('`parent_id` = ' . $cat1[$i]['id'])->order('sort_order asc')->field('id,name,img')->select();
                for ($j = 0; $j < count($cat1[$i]['cat2']); $j++) {
                    $cat1[$i]['cat2'][$j]['cat3'] = $category->where('`parent_id` = ' . $cat1[$i]['cat2'][$j]['id'])->field('id,name')->select();
                    $cat1[$i]['cat2'][$j]['img'] = TransformationImgurl($cat1[$i]['cat2'][$j]['img']);
                    array_unshift($cat1[$i]['cat2'][$j]['cat3'], array('id' => '0', 'name' => '全部'));
                }
            }
            /*
             * haitao 海淘商品分类表 64是邮费补拍不显示
             * */
            $haitao = array('id' => 0, 'name' => '海淘专区', 'logo' => CDN . '/Public/upload/category/img_international@3x.png');
            $haitao['cat2'] = M('haitao')->where('`parent_id` = 0 and `id` != 64 ')->field('id,name,img')->select();
            foreach ($haitao['cat2'] as &$v) {
                $v['img'] = TransformationImgurl($v['img']);
            }
            for ($i = 0; $i < count($haitao['cat2']); $i++) {
                $haitao['cat2'][$i]['cat3'] = M('haitao')->where('`parent_id` = ' . $haitao['cat2'][$i]['id'])->field('id,name')->select();
                array_unshift($haitao['cat2'][$i]['cat3'], array('id' => '0', 'name' => '全部'));
            }
            $json = array('status' => 1, 'msg' => '', 'result' => array('haitao' => $haitao, 'cat' => $cat1));
            redis($rdsname, serialize($json));//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /*
     *当拉取到当前最后数据的时候、重新拉取新的数据
     */
    function getNewData()
    {
        $caterogy_id = I('id');
        $page = I('page',1);
        $pagesize = I('pagesize',30);
        $version = I('version');
        $rdsname = "getNewData".$caterogy_id.$page.$pagesize.$version;
        if(empty(redis($rdsname))) {//判断是否有缓存
            $data = $this->getNextCat($caterogy_id, $page, $pagesize,$version);
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            redis($rdsname, serialize($json), REDISTIME);
        } else {
            $json = unserialize(redis($rdsname));
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    function getNextCat($id,$page,$pagesize,$version)
    {
        //找到一级菜单的下级id
        $parent_cat = M('goods_category')->where('`parent_id`='.$id)->field('id')->select();
        $condition['parent_id'] =array('in',array_column($parent_cat,'id'));
        $parent_cat2 = M('goods_category')->where($condition)->field('id')->select();
        $condition2['cat_id'] =array('in',array_column($parent_cat2,'id'));
        $condition2['is_on_sale']=1;
        $condition2['is_show'] = 1;
        $condition2['show_type'] = 0;
        $condition2['is_recommend'] = 0;$order = "sales desc";//筛选条件
        $data = $this->getGoodsList($condition2,$page,$pagesize,$order);
        return $data;
    }

    /**
     * 返回兑吧的免登陆URL
     */
    public function return_Duiba_loginurl(){
        $user_id = I('user_id');

        if(!$user_id){
            exit(json_encode(array('status'=>0,'msg'=>'你还没有登录')));
        }else{
            $credits=M('users')->where(array('user_id'=>$user_id))->getField('integral');
        }

        vendor('Duiba.Duiba');

        $Duiba = new \Duiba();

        $login_url = $Duiba->buildcreditautologinrequest(C('Duiba')['AppKey'],C('Duiba')['AppSecret'],$user_id,$credits);
        echo "<script>location.href='".$login_url."'</script>";
        die;
    }

    /**
     * 兑吧扣除积分
     */
    public function Duiba_deduct_credits(){
        $data['user_id'] = $_REQUEST['uid'];
        $data['duiba_orderNum'] = $_REQUEST['orderNum'];
        $data['credits'] = $_REQUEST['credits'];
        $data['params'] = $_REQUEST['params'];
        $data['ip'] = $_REQUEST['ip'];
        $data['sign'] = $_REQUEST['sign'];
        $data['timestamp'] = $_REQUEST['timestamp'];
        $data['actualPrice'] = $_REQUEST['actualPrice'];
        $data['description'] = $_REQUEST['description'];
        $data['facePrice'] = $_REQUEST['facePrice'];
        $data['order_num'] = date('YmdHis').rand(1000,999);
        $data['datetime'] = date('Y-m-d H:i:s');
        $data['status'] = 1;

        M()->startTrans();

        $order_res = M('duiba_order')->add($data);

        if($order_res){
            $credits_res = M('users')->where(array('user_id'=>$data['user_id']))->setDec('integral',$data['credits']);
        }else{
            M()->rollback();
            $json = array('status'=>'fail', 'errorMessage'=>'添加订单失败','credits'=>$data['credits']);
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }

        if($credits_res){
            M()->commit();
            $json = array('status'=>'ok', 'errorMessage'=>'','bizId'=>$data['order_num'],'credits'=>$data['credits']);
        }else{
            M()->rollback();
            $json = array('status'=>'fail', 'errorMessage'=>'积分扣除失败','credits'=>$data['credits']);
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     *兑吧结果通知
     */
    public function Duiba_deduct_notice(){
        $request_array = $_REQUEST;
        unset($request_array['thinkphp_show_page_trace'],$request_array['PHPSESSID']);

        $data = $this->parseCreditNotify($request_array);

        if($data['success'] == 'true'){
            $res = M('duiba_order')->where(array('order_num'=>$data['bizId']))->save(array('status'=>2));
        }else{
            $duiba_order = M('duiba_order')->where(array('order_num'=>$data['bizId']))->find();

            $user_res = M('users')->where(array('user_id'=>$duiba_order['user_id']))->setInc('integral',$duiba_order['credits']);

            M('duiba_order')->where(array('order_num'=>$data['bizId']))->save(array('status'=>3));
        }
        echo 'ok';
    }

    function parseCreditNotify($request_array){
        vendor('Duiba.Duiba');

        $Duiba = new \Duiba();
        $appKey= C('Duiba')['AppKey'];
        $appSecret = C('Duiba')['AppSecret'];

        if($request_array["appKey"] != $appKey){
            E("appKey not match");
        }
        if($request_array["timestamp"] == null ){
            E("timestamp can't be null");
        }
        $verify=$Duiba->signVerify($appSecret,$request_array);
        if(!$verify){
            E("sign verify fail");
        }
        $ret=array("success"=>$request_array["success"],"errorMessage"=>$request_array["errorMessage"],
            "uid"=>$request_array["uid"],"bizId"=>$request_array["bizId"]);
        return $ret;
    }

    public function WlCallBack(){
        header("Content-Type:text/html;charset=utf-8");
        //订阅成功后，收到首次推送信息是在5~10分钟之间，在能被5分钟整除的时间点上，0分..5分..10分..15分....
        $param=$_POST['param'];

        try{
            //$param包含了文档指定的信息，...这里保存您的快递信息,$param的格式与订阅时指定的格式一致
            echo  '{"result":"true",	"returnCode":"200","message":"成功"}';
            //要返回成功（格式与订阅时指定的格式一致），不返回成功就代表失败，没有这个30分钟以后会重推
        } catch(Exception $e)
        {
            echo  '{"result":"false",	"returnCode":"500","message":"失败"}';
            //保存失败，返回失败信息，30分钟以后会重推
        }
    }

    //免单拼
    public function getFreeProm()
    {

        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $version = I('version','');
        $rdsname = "getFreeProm".$version.$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * */
            $where = '`show_type`=0 and `is_special`=6 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            /*
             * ad_id 轮播id
             * ad_link=>banner跳转链接
             * ad_name=>banner名字
             * ad_code=>图片地址
             * type=>跳转类型
             */
            $ad = M('ad')->where('pid = 6')->field('ad_id,ad_code,ad_link,type')->find();
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('banner'=>$ad,'goodsList'=>$data));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /*
	*  排行榜
	*/
    function getRankingList()
    {

        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $rdsname = "getRankingList".$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存
            $where = '`is_special` != 8 and `show_type`=0 and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,' sales desc ');
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    //为我点赞
    public function getThe_raise()
    {
        $version = I('version');
        $rdsname = "getThe_raise".$version;
        if(empty(redis($rdsname))) {//判断是否有缓存
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * the_raise 为我点赞标识
             * */
            $where = '`the_raise`=1 and `show_type`=0 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $goods = M('goods')->where($where)->order('is_recommend desc,sort asc')->field('goods_id,goods_name,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img')->select();

            foreach ($goods as $k=>$v) {
                $goods[$k]['original_img'] = empty($goods[$k]['original_img'])?$goods[$k]['original']:$goods[$k]['original_img'];
                if($goods[$k]['is_special']==8){
                    $goods[$k]['spec_key'] = M('spec_goods_price')->where('goods_id = '.$goods[$k]['goods_id'])->getField('key');
                }
            }

            $ad = M('ad')->where('pid = 4')->field('ad_id,ad_code,ad_link,type')->find();
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('banner'=>$ad,'raisegoods'=>$goods));
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        //返回给前端的库存  2017-08-12 温立涛 开始
        foreach($json['result']['raisegoods'] as $k => $v){
            $goodid = $v['goods_id'];
            $data = M('spec_goods_price')->field('store_count')->where("goods_id={$goodid}")->find();
            $json['result']['raisegoods'][$k]['store_count'] = $data['store_count'];
        }
        //返回给前端的库存  2017-08-12 温立涛 结束

        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    //热门商品
    function hot_goods(){

        $page = I('page',1);
        $pagesize = I('pagesize',30);
        $version = I('version');
        $rdsname = "hot_goods".$version.$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存
            /*
             * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
             * is_special 商品type
             * is_on_sale 是否上架 1 上架 0下架
             * is_audit 是否审核 1已审核 0未审核
             * is_show 是否显示 1 显示 0不显示  用于暂时下架
             * */
            $where = '`show_type`=0 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,'sales desc');
            $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
            redis($rdsname, serialize($json), REDISTIME);//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }
    //为我点赞顶部滚动内容
    function rolling(){

        $version = I('version');
        $rdsname = "rolling".$version;
        if(empty(redis($rdsname))){
            /*
             * group_buy 团购订单表
             * tp_users 用户表
             * tp_goods 商品表
             * is_raise 团购订单表的为我点赞标识
             * is_successful 成团标识
             * mark 团长为0  团员为团长团购订单id
             * nickname 用户昵称
             * goods_name 商品名
             * mobile 用户手机号码
             * */
            $rolling_arr = M('group_buy')->alias('gb')
                ->join('INNER JOIN tp_users u on u.user_id = gb.user_id ')
                ->join('INNER JOIN tp_goods g on g.goods_id = gb.goods_id')
                ->where('gb.is_raise = 1 and gb.is_successful = 1 and gb.mark = 0 ')
                ->field("u.nickname,g.goods_name,REPLACE(u.mobile, SUBSTR(u.mobile,4,4), '****') as mobile")
                ->order('id desc')
                ->limit('0,20')
                ->select();

            $json = array('status'=>1,'msg'=>'获取成功','result'=>$rolling_arr);
            redis($rdsname,serialize($json));
        } else {
            $json = unserialize(redis($rdsname));
        }

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示

        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     * 获取省钱大法商品列表
     */
    public function getEconomizeGoods()
    {
        $version = I('version');
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $rdsname = "getEconomizeGoods".$version.$page.$pagesize;
        if (empty(redis($rdsname))) {
            $where = 'type=2';//type=2 省钱大法的类型
            $count = M('goods_activity')->where($where)->count();
            $goodsList = M('goods_activity')->alias('ga')
                ->join('INNER JOIN tp_goods g on g.goods_id = ga.goods_id ')
                ->where($where)
                ->page($page, $pagesize)
                ->field('g.goods_id,g.goods_name,g.market_price,g.shop_price,g.original_img as original,g.prom,g.prom_price,g.is_special,g.list_img as original_img')
                ->order('g.sort asc')
                ->select();
            for($i=0;$i<count($goodsList);$i++){
                $type = M('promote_icon')->where('goods_id = '.$goodsList[$i]['goods_id'])->getField('src');
                if(!empty($type)){
                    $goodsList[$i]['icon_src'] = $type;
                }
            }
            $data = $this->listPageData($count, $goodsList);
            foreach ($data['items'] as &$v) {
                $v['original_img'] = empty($v['original_img'])?$v['original']:$v['original_img'];
            }
            $ad = M('ad')->where('pid = 3')->field('ad_id,ad_code,ad_link,type')->find();
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('banner'=>$ad,'goodsList'=>$data));
            redis($rdsname, serialize($json), REDISTIME);
        } else {
            $json = unserialize(redis($rdsname));
        }

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(I('ajax_get')) {
            $this->getJsonp($json);
        }
        exit(json_encode($json));
    }

    //版本2.0.0
    //中间展示免单的拼团
    function get_Free_Order()
    {

        $free_num = I('free_num');//免单人数
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $rdsname = "get_Free_Order".$free_num.$page.$pagesize;
        if (redis("get_Free_Order_status") == "1"){
            redisdelall("get_Free_Order"."*");
            redisdelall("get_Free_Order_status");
        }
        if (empty(redis($rdsname))) {//是否有缓存
            $condition['gb.free'] = array('eq', $free_num);//免单人数
            $condition['gb.is_successful'] = array('eq', 0);//是否成团
            $condition['gb.end_time'] = array('gt', time());//结束时间
            $condition['gb.mark'] = array('eq', 0);//mark 团长为0  团员为团长团购订单id
            $condition['gb.is_pay'] = array('eq', 1);//是否支付
            $condition['g.is_on_sale'] = array('eq', 1);//是否上架
            $condition['g.show_type'] = array('eq', 0);//是否被删除
            $condition['g.is_audit'] = array('eq', 1);//是否审核
            /*
             * group_buy 团购订单表
             * tp_goods 商品表
             * tp_order_goods  订单详情记录表
             * gb.id 团订单id
             * gb.goods_id 商品id
             * gb.price 订单支付价格
             * gb.goods_num 团满人数
             * gb.free 免单人数
             * gb.start_time 开团时间
             * gb.end_time 团结束时间
             * gb.order_id 团订单关联的订单id
             * og.spec_key 规格key
             * */
            $count = M('group_buy')->alias('gb')
                ->join('INNER JOIN tp_goods g on gb.goods_id = g.goods_id ')
                ->join('INNER JOIN tp_order_goods og on gb.order_id = og.order_id ')
                ->where($condition)->count();
            $prom = M('group_buy')->alias('gb')
                ->join('INNER JOIN tp_goods g on gb.goods_id = g.goods_id ')
                ->join('INNER JOIN tp_order_goods og on gb.order_id = og.order_id ')
                ->where($condition)
                ->field('gb.id as prom_id,gb.goods_id,gb.price,gb.goods_num as prom,gb.free,gb.start_time,gb.end_time,gb.order_id,og.spec_key')
                ->page($page, $pagesize)
                ->select();
            if(!empty($prom)){
                //将免单价格重新计算
                $goods_id = "";
                foreach ($prom as $value) {
                    $goods_id .= $value['goods_id'] . ",";
                }
                $goods_id = substr($goods_id, 0, -1);
                /*
                 * spec_goods_price 商品规格价格
                 * key 上品牌规格
                 * prom_price 团购价格
                 * */
                $spec_goods_price = M('spec_goods_price')->where(array("goods_id" => array("in", $goods_id)))->field('key,prom_price')->select();
                $arr = array();
                foreach ($prom as $v) {
                    foreach ($spec_goods_price as $value) {
                        if ($v['spec_key'] == $value['key']) {
                            $arr[]['prom_price'] = $value['prom_price'];
                        }
                    }
                }
                //将免单价格重新计算
                for ($i = 0; $i < count($arr); $i++) {
                    $price = ($arr[$i]['prom_price'] * $prom[$i]['prom']) / ($prom[$i]['prom'] - $prom[$i]['free']);
                    $c = $this->getFloatLength($price);
                    if ($c >= 3) {
                        $price = $this->operationPrice($price);
                    }
                    $prom[$i]['price'] = sprintf("%.2f", $price);
                    $prom[$i]['goods'] = $this->getGoodsInfo($prom[$i]['goods_id']);
                }

                $data = $this->listPageData($count, $prom);

                $json = array('status' => 1, 'msg' => '获取成功', 'result' => $data);
            }else{
                $json = array('status' => 1, 'msg' => '获取成功', 'result' => array('items'=>null));
            }

            redis($rdsname, serialize($json));//写入缓存
        } else {
            $json = unserialize(redis($rdsname));//读取缓存
        }
        if(I('ajax_get')) {
            $this->getJsonp($json);
        }
        exit(json_encode($json));
    }

    //获取小数点后面的长度
    private function getFloatLength($num) {
        $count = 0;
        $temp = explode ( '.', $num );
        if (sizeof ( $temp ) > 1) {
            $decimal = end ( $temp );
            $count = strlen ( $decimal );
        }
        return $count;
    }

    //操作价格
    public function operationPrice($price)
    {
	    $price = sprintf("%.2f",substr(sprintf("%.4f", $price), 0, -2));
	    $price = $price+0.01;
        return $price;
    }

    //删除缓存
    public function redisdelall($rdsname = ""){
        redisdelall($rdsname);
        echo "删除 ".$rdsname;
    }

    //趣多严选
	public function getStrict_selection(){
		$page = I('page',1);
		$pagesize = I('pagesize',10);
        $version = I('version','');
		$rdsname = "getStrict_selection".$version.$page.$pagesize;
		if(empty(redis($rdsname))) {//判断是否有缓存
            /*
            * show_type 是否展示 1不显示 0显示 1（为1时为逻辑删除状态）
            * is_special 商品type
            * is_on_sale 是否上架 1 上架 0下架
            * is_audit 是否审核 1已审核 0未审核
            * is_show 是否显示 1 显示 0不显示  用于暂时下架
            * */
			$where = '`is_special`=9 and `show_type`=0 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
			$data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            //获取轮播图 ab轮播表 pid = 1 是首页轮播id enabled = 1 是显示的
            /*
			 * ad_link=>banner跳转链接
			 * ad_name=>banner名字
			 * ad_code=>图片地址
			 * type=>跳转类型
			 */
            $ad = M('ad')->where('pid = 5')->field('ad_id,ad_code,ad_link,type')->find();
            $json = array('status'=>1,'msg'=>'获取成功','result'=>array('banner'=>$ad,'goodsList'=>$data));
			redis($rdsname, serialize($json), REDISTIME);//写入缓存
		}else{
			$json = unserialize(redis($rdsname));//读取缓存
		}
		I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
		if(!empty($ajax_get))
			$this->getJsonp($json);
		exit(json_encode($json));
	}

    function test($goods_id='5',$goods_name='湿哒哒爱上asdasd爱上asd奥术大师大叔大婶收到',$price='8.88'){
        $font = 'Public/images/yahei.ttf';//字体
        // 背景图片宽度
        $bg_w    = 600;
        // 背景图片高度
        $bg_h    = 700; // 背景图片高度
        //二维码宽
        $ewmWidth = 200;
        //二维码高
        $ewmHeight = 200;
        //商品图片宽度
        $goodWidth = 590;
        //商品图片高度
        $goodHeight = 368;
        //二维码距离右边框距离
        $ewmLeftMargin = 20;
        //二维码距离底部框距离
        $ewmBottomMargin = 24;
        // 背景图片
        $background = imagecreatetruecolor($bg_w,$bg_h);
        // 为真彩色画布创建白色背景，再设置为透明
        $color   = imagecolorallocate($background, 255, 255, 255);
        //颜色填充
        imagefill($background, 0, 0, $color);
        //透明图片
        imageColorTransparent($background, $color);

        // 开始位置X
        $start_x    = intval($bg_w-$ewmWidth-$ewmLeftMargin);
        // 开始位置Y
        $start_y    = intval($bg_h-$ewmHeight-$ewmBottomMargin);
        // 宽度
        $pic_w   = intval($ewmWidth);
        // 高度
        $pic_h   = intval($ewmHeight);
        //创建down图片资源
        $downresource = imagecreatefrompng('Public/images/down.png');
        //图片合并
        imagecopyresized($background,$downresource,480,450,0,0,18,20,imagesx($downresource),imagesy($downresource));
        //商品图片资源
        $goodresource = imagecreatefromjpeg('http://cdn.pinquduo.cn/15017401102.jpg');
        //图片合并
        imagecopyresized($background,$goodresource,5,5,0,0,$goodWidth,$goodHeight,imagesx($goodresource),imagesy($goodresource));
        //文字颜色
        $fontcolor = imagecolorallocate($background, 204,204,204);
        //商品名
        if(strlen($goods_name)>39){
            //第一行
            $one = msubstr($goods_name,0,13);
            imagettftext($background,20,0,20,503,imagecolorallocate($background, 0,0,0),$font,$one);
            $two = msubstr($goods_name,11,13);
            if(strlen($two)<36){
                imagettftext($background,20,0,20,547,imagecolorallocate($background, 0,0,0),$font,$two);
            }else{
                $two = msubstr($goods_name,11,11).'...';
                imagettftext($background,20,0,20,547,imagecolorallocate($background, 0,0,0),$font,$two);
            }
        }else{
            imagettftext($background,20,0,20,503,imagecolorallocate($background, 0,0,0),$font,$goods_name);
        }

        imagettftext($background,17,0,$start_x,intval($start_y-39),$fontcolor,$font,"长按二维码为我助力");

        imagettftext($background,20,0,20,606,imagecolorallocate($background, 226,0,37),$font,'快来拼趣多秒购0元商品');

        imagettftext($background,19,0,20,663,imagecolorallocate($background, 226,0,37),$font,'￥');

        imagettftext($background,40,0,50,663,imagecolorallocate($background, 226,0,37),$font,'0');

        imagettftext($background,19,0,90,663,$fontcolor,$font,"原价:".$price);

        if(strlen($price)==4){
            imageline($background, 90, 654, 197, 654, $fontcolor);
            imageline($background, 90, 653, 197, 653, $fontcolor);
        }elseif(strlen($price)==5){
            imageline($background, 90, 654, 220, 654, $fontcolor);
            imageline($background, 90, 653, 220, 653, $fontcolor);
        }elseif (strlen($price)==6){
            imageline($background, 90, 654, 228, 654, $fontcolor);
            imageline($background, 90, 653, 228, 653, $fontcolor);
        }elseif (strlen($price)==7){
            imageline($background, 90, 654, 240, 654, $fontcolor);
            imageline($background, 90, 653, 240, 653, $fontcolor);
        }else{
            imageline($background, 90, 654, 190, 654, $fontcolor);
            imageline($background, 90, 653, 190, 653, $fontcolor);
        }
//        header("Content-type: image/jpg");
        $path = "Public/upload/raise";
        if (!file_exists($path)){
            mkdir($path);
        }

        //拉图片传到七牛云
        $path1 = "Public/upload/raise/goods_". $goods_id .'.jpg';
        imagejpeg($background,$path1);
        $path = 'http://test.pinquduo.cn/'.$path1;
		$qiniu = new \Admin\Controller\QiniuController();
		$qiniu_result = $qiniu->fetch($path, "imgbucket", $path1);

        $url = CDN . "/" . $qiniu_result[0]["key"];

        var_dump( $url);

    }

    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     */
    function image_png_size_add($imgdst='Public/images/'){
        $imgsrc = 'http://wx.qlogo.cn/mmopen/zZSYtpeVianR8v7QHKm3qO6wydccndNKGMiclrcOwUjvicllW3ibc3Is4QBok0CyuGmF2tX1OEf95WO6umS1ol7dibfKoab8oEVlw/0' ;
        list($width,$height,$type)=getimagesize($imgsrc);
        $new_width = 50;
        $new_height = 50;
        switch($type){
            case 1:
                $giftype=check_gifcartoon($imgsrc);
                if($giftype){
                    header('Content-Type:image/gif');
                    $image_wp=imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst,75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
                header('Content-Type:image/jpeg');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp);
                imagedestroy($image_wp);
                break;
            case 3:
                header('Content-Type:image/png');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
        }
    }

    //删除缓存
    public function redisdelall_user($user_id = ""){
        $this->order_redis_status_ref($user_id);
    }

    function  t(){
        $code = 'PQD';  //客户id=APPKey
        $secretKey = 'b1fc3d0cc7e721574dbe8217099b1f8a';//安能快递秘钥
        $url = 'http://101.95.139.62:40144/aneop/services/logisticsQuery/query';
        $ewbNo = 29506100000198;//
        $digest = base64_encode(md5("{\"ewbNo\":\"$ewbNo\"}".$code.$secretKey));
        list($t1, $t2) = explode(' ', microtime());
        $timestamp = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $data = '{"timestamp":"'.$timestamp.'","digest":"'.$digest.'","params":"{\"ewbNo\":\"'.$ewbNo.'\"}","code":"'.$code.'"}';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
        $result=curl_exec($ch);
        curl_close($ch);

        $arr = object_to_array(json_decode($result));
        $arr['resultInfo'] = object_to_array(json_decode($arr['resultInfo']));
        $arr['resultInfo'] = $arr['resultInfo']['tracesList'][0];
        $new_arr = array();
        foreach ($arr['resultInfo']['traces'] as $k=>$v){
            $new_arr[$k]['ftime'] = $new_arr[$k]['time'] = $v['time'];
            $new_arr[$k]['context'] = $v['desc'];
        }
//        var_dump($new_arr);die;
//        var_dump($datas);die;
//        $cha = "{\"result\":true,\"reason\":\"成功\",\"resultCode\":\"1000\",\"resultInfo\":{\"tracesList\":[{\"mailNos\":\"29506100000198\",\"traces\":[{\"action\":\"ARRIVAL\",\"city\":\"杭州市\",\"country\":\"China\",\"desc\":\"【杭州市】快件已到达CF测试一级加盟网点A\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 09:27:17\",\"tz\":\"+8\"},{\"action\":\"GOT\",\"city\":\"杭州市\",\"country\":\"China\",\"desc\":\"【杭州市】安能CF测试一级加盟网点A收件员已揽件\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 10:17:27\",\"tz\":\"+8\"},{\"action\":\"DEPARTURE\",\"city\":\"杭州市\",\"country\":\"China\",\"desc\":\"【杭州市】CF测试一级加盟网点A已发出,下一站CF测试一级分拨中心A\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 10:20:46\",\"tz\":\"+8\"},{\"action\":\"ARRIVAL\",\"city\":\"杭州市\",\"country\":\"China\",\"desc\":\"【杭州市】CF测试一级加盟网点A快件已到达\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 10:25:49\",\"tz\":\"+8\"},{\"action\":\"SENT_SCAN\",\"city\":\"杭州市\",\"contactPhone\":\"17199750494\",\"contacter\":\"CF测试一级加盟网点员工\",\"country\":\"China\",\"desc\":\"【杭州市】CF测试一级加盟网点A派件员:CF测试一级加盟网点员工17199750494正在为您派件\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 10:27:17\",\"tz\":\"+8\"},{\"action\":\"SIGNED\",\"city\":\"杭州市\",\"country\":\"China\",\"desc\":\"【杭州市已签收,签收人是自提件,感谢使用安能,期待再次为您服务\",\"facilityName\":\"CF测试一级加盟网点A\",\"facilityNo\":\"9506002\",\"facilityType\":\"1\",\"time\":\"2017-08-07 10:50:04\",\"tz\":\"+8\"}]}]}}";
//
//        $arr = $this->object_to_array(json_decode($cha));

        $json = array('status' => 1, 'msg' => '获取成功', 'result' => $new_arr);
        exit(json_encode($json));
    }

    function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)$this->object_to_array($v);
            }
        }

        return $obj;
    }

    //PHP stdClass Object转array
    function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }

    function object2array_pre(&$object) {
        if (is_object($object)) {
            $arr = (array)($object);
        } else {
            $arr = &$object;
        }
        if (is_array($arr)) {
            foreach($arr as $varName => $varValue){
                $arr[$varName] = $this->object2array($varValue);
            }
        }
        return $arr;
    }

    function test1() {
        $custom = array('type' => '6','id'=>430634);
        var_dump(SendXinge('恭喜！您参与的免单拼团获得了免单',247,$custom));
    }
}
