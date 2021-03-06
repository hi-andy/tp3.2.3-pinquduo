<?php
namespace Api_2_0_1\Controller;



class IndexController extends BaseController {
    public $version = null;
    public function _initialize() {
        $version = I('version');
//        $this->encryption();
    }

    public function index(){
        print_r(redis("wxtmplmsg"));
    }

    /*
     * 获取首页数据
     */
    public function home(){
//        var_dump($version);die;
        $page = I('page', 1);
        $pagesize = I('pagesize', 8);
        $version = I('version');
        $rdsname = "home".$page.$pagesize.$version;
        if (empty(redis($rdsname))) { //判断缓存是否存在
            //获取轮播图
            $data = M('ad')->where('pid = 1 and `enabled`=1')->field(array('ad_link', 'ad_name', 'ad_code', 'type'))->select();
            foreach ($data as & $v) {
                $v['ad_code'] = TransformationImgurl($v['ad_code']);
            }
            //中间图标
            $category = M('group_category')->where('`id` != 9 and `id` != 8')->select();
            foreach ($category as &$v) {
                $v['cat_img'] = TransformationImgurl($v['cat_img']);
            }
	        $category[3]['cat_name'] = '趣多严选';
	        $category[3]['cat_img'] = CDN .'/Public/upload/index/quduoyanxuan.jpg';
            $category[4]['cat_name'] = '为我拼';
            $category[4]['cat_img'] = CDN .'/Public/upload/index/5-weiwo.jpg';
            $category[7]['cat_name'] = '省钱大法';
            $category[7]['cat_img'] = CDN . '/Public/upload/index/8-shenqian.jpg';
            //中间活动模块
            $activity['banner_url'] = CDN . '/Public/images/daojishibanner.jpg';
            $activity['H5_url'] = 'http://pinquduo.cn/index.php?s=/Api/SecondBuy/';
//            $activity['logo_url'] = 'http://cdn.pinquduo.cn/activity.gif';

            $where = '`show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_recommend`=1 and `is_special` in (0,1) and `is_audit`=1 ';
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
                $v['logo'] = TransformationImgurl($v['logo']);
            }
                //中间分类
                $directory2 = array('id' => 0, 'name' => '海淘专区', 'logo' => CDN . '/Public/upload/category/img_international@3x.png');
                $directory2['cat2'] = M('haitao')->where('`parent_id` = 0')->field('id,name,img,logo')->limit('4')->select();
                foreach ($directory2['cat2'] as &$v) {
                    $v['img'] = TransformationImgurl($v['img']);
                }
                for ($i = 0; $i < count($directory2['cat2']); $i++) {
                    $directory2 ['cat2'][$i]['cat3'] = M('haitao')->where('`parent_id` = ' . $directory2['cat2'][$i]['id'])->field('id,name')->select();
                    array_unshift($directory2['cat2'][$i]['cat3'], array('id' => '0', 'name' => '全部'));
                }
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
            $banner = M('ad')->where('pid = 2 and `enabled`=1')->field(array('ad_name', 'ad_code', 'type'))->find();
            $banner['ad_code'] = TransformationImgurl($banner['ad_code']);
            //中间四个小块
            $banner2 = M('exclusive')->select();

            foreach ($banner2 as &$v) {
                $v['img'] = TransformationImgurl($v['img']);
            }
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
            $banner = M('exclusive')->where('id =' . $id)->field(array('banner'))->find();
            $banner['banner'] = TransformationImgurl($banner['banner']);
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
	 *  免单拼
	 */
    function getMany_people_spell_group()
    {
        $price_min = I('price_min',0.01);
        $price_max = I('price_max');
        $free_min = I('free_min',1);
        $free_max = I('free_max');
        $pagesize = I('pagesize',20);
        $page = I('page',1);

        $condition['price'] = array('between',"$price_min,$price_max");
        $condition['free'] = array('between',"$free_min,$free_max");
        $condition['is_successful'] = array('eq',0);
        $condition['end_time'] = array('gt',time());
        $condition['mark'] = array('eq',0);
        $condition['is_pay'] = array('eq',1);
        $condition['is_audit'] = array('eq',1);
        $condition['is_on_sale'] = array('eq',1);
        $condition['show_type'] = array('eq',0);

        $count = M('group_buy')->where($condition)->count();
        $prom = M('group_buy')->where($condition)->field('id,order_id,goods_id,price,goods_num,free')->page($page,$pagesize)->select();
        foreach($prom as &$v){
            $goods_info = M('goods')->where('`goods_id`='.$v['goods_id'])->field('original_img,goods_name')->find();
            $v['goods_name'] = $goods_info['goods_name'];
            $v['original'] = TransformationImgurl($goods_info['original_img']);
            $v['original_img'] = goods_thum_images($v['goods_id'],400,400);
        }
        $data=$this->listPageData($count,$prom);

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $json = array('status'=>1,'msg'=>'获取成功','result'=>$data);
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    //签到
    public function getSignIn()
    {
        $data['user_id'] = I('user_id');
        $data['datetime'] = date("Y-m-d",time());
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $signin = M('signin')->where($data)->find();
        if($signin)
        {
            $json = array('status'=>-1,'msg'=>'签到失败','result'=>'你已经签到了');
            if(!empty($ajax_get))
                $this->getJsonp($json);
            exit(json_encode($json));
        }

        $res = M('signin')->data($data)->add();
        if($res)
        {
            $json = array('status'=>1,'msg'=>'获取成功','result'=>'签到成功');
            M('users')->where('user_id='.$data['user_id'])->setInc('integral',10);
        }else{
            $json = array('status'=>-1,'msg'=>'获取失败','result'=>'签到失败');
        }

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
            $where = '`show_type`=0 and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 ';
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

    /*
     *  限时秒杀
     *
     * */
    function get_Seconds_Kill_time()
    {
        $today_zero = strtotime(date('Y-m-d', time()));
        $today_zero2 = strtotime(date('Y-m-d', (time() + 2 * 24 * 3600)));
        $sql = "SELECT FROM_UNIXTIME(`on_time`,'%Y-%m-%d %H') as datetime from " . C('DB_PREFIX') . "goods WHERE `is_on_sale`=1 and `is_audit`=1 and `is_special` = 2 and `on_time`>=$today_zero and `on_time`<$today_zero2  GROUP BY `datetime`";
        $time = M('')->query($sql);
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
        $starttime =I('starttime');
        $version= I('version');
        $page = I('page',1);
        $pagesize = I('pagesize',20);
        $rdsname = "get_Seconds_Kill".$version.$starttime.$page;
        if (redis("get_Seconds_Kill_status") == "1"){
            redisdelall("get_Seconds_Kill*");
            redisdelall("get_Seconds_Kill_status");
        }
        if(empty(redis($rdsname))) {//判断是否有缓存
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
            $where = '`show_type`=0 and `is_special`=6 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
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

    //为我点赞
    public function getThe_raise()
    {
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $version = I('version');
        $rdsname = "getThe_raise".$version.$page.$pagesize;
        if(empty(redis($rdsname))) {//判断是否有缓存
            $where = '`the_raise`=1 and `show_type`=0 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
            $data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
            $ad = M('ad')->where('pid = 4')->field('ad_id,ad_code,ad_link,type')->find();
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
                ->select();
            $data = $this->listPageData($count, $goodsList);
            foreach ($data['items'] as &$v) {
                // 修改商品列表的商品主图和商品列表图，主要是兼容新旧商家后台版本造成的图片问题
                // 这个是2_0_1的老接口，后面要调用新的接口  温立涛  2017-09-05 16:20

                // 注释掉下面的代码  温立涛 2017-09-06 19:02
                /*
                if(!empty($v['original_img'])){
                    $imgArray = getimagesize($v['original_img']);
                    if((int)$imgArray[0] == (int)$imgArray[1]){
                        $temp = $v['original'];
                        $v['original'] = $v['original_img'];  //正方形
                        $v['original_img'] = $temp; 	      //长方形
                    }
                }
                */

                // 按照正确的商品图片显示 list_img 正方形  original_img 长方形  温立涛  2017-09-06 18:57
                $temp = $v['original'];
                $v['original'] = $v['original_img'];  //正方形
                $v['original_img'] = $temp; 	      //长方形
                // 处理结束

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
        $free_num = I('free_num');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        $rdsname = "get_Free_Order".$free_num.$page.$pagesize;
        if (redis("get_Free_Order_status") == "1"){
            redisdelall("get_Free_Order"."*");
            redisdelall("get_Free_Order_status");
        }
        if (empty(redis($rdsname))) {//是否有缓存
            $condition['gb.free'] = array('eq', $free_num);
            $condition['gb.is_successful'] = array('eq', 0);
            $condition['gb.end_time'] = array('gt', time());
            $condition['gb.mark'] = array('eq', 0);
            $condition['gb.is_pay'] = array('eq', 1);
            $condition['g.is_on_sale'] = array('eq', 1);
            $condition['g.show_type'] = array('eq', 0);
            $condition['g.is_audit'] = array('eq', 1);

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

            //将免单价格重新计算
            $goods_id = "";
            foreach ($prom as $value) {
                $goods_id .= $value['goods_id'] . ",";
            }
            $goods_id = substr($goods_id, 0, -1);
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
			$where = '`is_special`=9 and `show_type`=0 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
			$data = $this->getGoodsList($where,$page,$pagesize,'is_recommend desc,sort asc');
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

    function  test1111($r){
//        $op = M('users')->where('user_id = 247')->find();
//       $wx = new WxtmplmsgController();
//        $res = $wx->spell_success($op['openid'],'拼趣多内部測試wupai勿拍-为我点赞','不靠谱，十分靠谱，靠不靠谱不靠谱，十分靠谱，靠不靠谱不靠谱，十分靠谱，靠不靠谱',20000,'就是来测222222222222222222222222222222222222222试的');
$res = M('merchant')->where(array('mobile'=>$r))->count();
        if($res){
            echo json_encode(array('status'=>0,'msg'=>'此手机号已注册商铺'));
            die;
        }
    }
}
