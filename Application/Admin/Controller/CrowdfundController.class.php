<?php
namespace Admin\Controller;
use Api\Controller\BaseController;
use Think\AjaxPage;
use Admin\Logic\GoodsLogic;
use Admin\Logic\OrderLogic;

class CrowdfundController extends BaseController {

    public  $order_status;
    public  $shipping_status;
    public  $pay_status;
    /*
     * 初始化操作
     */
    public function _initialize() {
        C('TOKEN_ON',false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
    }

    /**
     * 显示众筹管理商品列表
     */
    public function goods_list(){
        $this->display();
    }

    /**
     * AJAX获取商品列表
     */
    public function ajaxGoodsList()
    {
        $where = ' show_type=0 '; // 搜索条件

        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%')" ;
        }
        $where .= " and  the_raise = 1";
        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);

        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)
            ->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')
            ->field('tp_goods.*,tp_merchant.id')
            ->select();

        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }


    /**
     * 显示众筹订单列表
     */
    public function group_list(){
        $begin = date('Y/m/d',(time()-30*60*60*24));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }

    /**
     *AJAx获取众筹订单列表
     */
    public function ajax_group_list(){

        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }
        // 搜索条件
        $condition = array();
        $condition['g.is_raise'] = 1;
        if($begin && $end){
            $condition['g.start_time'] = array('GT',$begin);
            $condition['g.end_time'] = array('LT',$end);
        }

        if(I('Open_group')){
            if(I('Open_group')==1){
                $condition['_string'] = " g.goods_num = g.order_num ";
            }elseif(I('Open_group')==2){
                $condition['_string'] = " g.goods_num != g.order_num ";
            }
        }

        if(I('consignee')){
            $condition['u.nickname'] =array('LIKE',"%".I('consignee')."%");
        }
        $count = M('group_buy')->alias('g')
                 ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
                 ->where($condition)
                 ->count();

        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();

        $grouplist = M('group_buy')->alias('g')
                     ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
                     ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
                     ->where($condition)
                     ->field('g.*,u.nickname,o.order_sn,o.pay_time')
                     ->limit($Page->firstRow,$Page->listRows)
                     ->select();

        $this->assign('grouplist',$grouplist);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 显示众筹详情
     */
    public function group_detail(){

        $groupid = $_REQUEST['groupid'];

        //众筹信息
        $group_info =M('group_buy')->where(array('id'=>$groupid))->find();
        //用户信息
        $user_info = M('users')->where(array('user_id'=>$group_info['user_id']))->find();

        $orderLogic = new OrderLogic();
        $order_id = $group_info['order_id'];
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);

        // 获取操作记录
        $action_log = M('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();

        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $split = count($orderGoods) > 1 ? 1 : 0;
        foreach ($orderGoods as $val){
            if($val['goods_num']>1){
                $split = 1;
            }
        }

        $Base = new BaseController();
        $ordertype = $Base->getPromStatus($order,$group_info,$group_info['order_num']);

        $order['order_type'] = $ordertype['annotation'];
        $order['order_type_id'] = $ordertype['order_type'];
        $button = $orderLogic->getOrderButton_group($order);

        $this->assign('ordertype',$ordertype['annotation']);
        $this->assign('split',$split);
        $this->assign('button',$button);
        $this->assign('group_info',$group_info);
        $this->assign('user_info',$user_info);
        $this->show();
    }

    /**
     * 众筹订单状态
     */
    public function format_order_status($order,$group){
        $shipping_status = $order['shipping_status'];
        $pay_status = $order['pay_status'] ;
        $goods_num = $group['goods_num'] ;
        $order_num = $group['order_num'] ;

        $status_str = "";
        if($pay_status == 1 ){
            $status_str .= '已支付';
        }else{
            $status_str .= '未支付';
        }

        if($shipping_status==1){
            $status_str .= '/已发货';
        }else{
            $status_str .= '/未发货';
        }

        if($goods_num == $order_num){
            $status_str .= '/众筹成功';
        }else{
            $status_str .= '/众筹中';
        }
        return $status_str;
    }

    /**
     *众筹支付订单列表
     */
    public function order_list(){
        $this->show();
    }

    /**
     *AJAX获取订单列表
     */
    public function ajax_order_list(){
        $orderLogic = new OrderLogic();
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }
        // 搜索条件
        $condition = array();
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
        $condition['the_raise'] = 1;
        I('order_sn') ? $condition['order_sn'] = trim(I('order_sn')) : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;

        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = M('order')->where($condition)->where('order_type=14 or order_type=15')->count();

        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();
        //获取订单列表
//        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $orderList =  M('order')->where($condition)->where('order_type=14 or order_type=15')->limit("$sort_order,$Page->firstRow,$Page->listRows")->select();
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function Crowdfund_info()
    {
        $exclusive = M('exclusive')->select();
        $this->assign('exclusive',$exclusive);
        $this->display();
    }

    public function goods_save()
    {   //狗哥说的不用改
        $data['is_support_buy']=0;
        $data['is_special']=8;
        $data['the_raise']=1;
        for($i=0;$i<count($_POST['goods_id']);$i++){
            $res = M('goods')->where('`goods_id`='.$_POST['goods_id'][$i])->data($data)->save();
            redislist("goods_refresh_id", $_POST['goods_id'][$i]);
        }
        if($res){
            $this->success("添加成功",U('Crowdfund/goods_list'));
        }else{
            $this->success("添加失败",U('Crowdfund/Crowdfund_info'));
        }
    }

    public function search_goods(){
        $goods_id = I('goods_id');
        $where = ' is_on_sale=1 and is_special=0 and the_raise=0 ';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (I('cat_id')) {
            $this->assign('cat_id', I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }

        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords')."%')";
        }
        if(!empty(I('store_name')))
        {
            $this->assign('store_name', I('store_name'));
            $where = $this->getStoreWhere($where,I('store_name'));
        }
        $count = M('goods')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $goodsList = M('goods')->where($where)->order('addtime DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        for($i=0;$i<count($goodsList);$i++)
        {
            $store_name = M('merchant')->where('`id`='.$goodsList[$i]['store_id'])->field('store_name')->find();
            $goodsList[$i]['store_name'] = $store_name['store_name'];
        }

        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $tpl = I('get.tpl', 'search_goods');
        $this->display($tpl);
    }

    function getSortCategory()
    {
        $categoryList = M("GoodsCategory")->where('`is_show`=1')->getField('id,name,parent_id,level');
        $nameList = array();
        foreach ($categoryList as $k => $v) {

            //$str_pad = str_pad('',($v[level] * 5),'-',STR_PAD_LEFT);
            $name = getFirstCharter($v['name']) . ' ' . $v['name']; // 前面加上拼音首字母
            //$name = getFirstCharter($v['name']) .' '. $v['name'].' '.$v['level']; // 前面加上拼音首字母
            /*
			// 找他老爸
			$parent_id = $v['parent_id'];
			if($parent_id)
				$name .= '--'.$categoryList[$parent_id]['name'];
			// 找他 爷爷
			$parent_id = $categoryList[$v['parent_id']]['parent_id'];
			if($parent_id)
				$name .= '--'.$categoryList[$parent_id]['name'];
			*/
            $nameList[] = $v['name'] = $name;
            $categoryList[$k] = $v;
        }
        array_multisort($nameList, SORT_STRING, SORT_ASC, $categoryList);

        return $categoryList;
    }

}