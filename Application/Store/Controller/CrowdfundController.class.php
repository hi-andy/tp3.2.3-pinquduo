<?php
namespace Store\Controller;

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
        $condition['g.store_id'] = $_SESSION['merchant_id'];
        $condition['g.mark'] = 0;
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
            ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
            ->join('INNER JOIN tp_merchant m on o.store_id = m.id')
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
            ->field('g.*,u.nickname,o.order_sn,o.pay_time,o.consignee')
            ->limit($Page->firstRow,$Page->listRows)
            ->order('g.id desc')
            ->select();
        foreach ($grouplist as $k=>$v){
            $count = M('group_buy')->where("mark = {$v['id']}")->count();
            $grouplist[$k]['order_num'] = $count+1;
        }

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
        $data['is_prom_buy']=1;
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
            $arr = $this->getStoreWhere($where,I('store_name'));
            $where = $arr['where'];
        }
        if(I('store_id')){
            $store_id = I('store_id');
            $where = "$where and store_id = $store_id";
        }
        $count = M('goods')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $goodsList = M('goods')->where($where)->order('addtime DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        for($i=0;$i<count($goodsList);$i++)
        {
            $store_name = M('merchant')->where('`id`='.$goodsList[$i]['store_id'])->field('store_name')->find();
            $goodsList[$i]['store_name'] = $store_name['store_name'];
        }

            $show = $Page->show($arr['store_id']);//分页显示输出
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

    function delivery_list(){
        $this->display();
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $promLogic = new PromLogic();
        $data = I('post.');
        $res = M('delivery_doc')->where('`order_id`='.$data['order_id'])->find();
        if(!empty($res))
        {
            $this->success('已经发货了',U('Store/Crowdfund/delivery_list',array('order_id'=>$data['order_id'])));
            exit();
        }
        $res = $promLogic->deliveryHandle($data);
        //
        if($res){
            reserve_logistics($data['order_id']);
            $this->success('操作成功',U('Store/Crowdfund/delivery_info',array('order_id'=>$data['order_id'])));
        }else{
            $this->success('操作失败',U('Store/Crowdfund/delivery_info',array('order_id'=>$data['order_id'])));
        }
    }

    /*
	 * ajax 发货订单列表
	*/
    public function ajaxdelivery(){
//		$orderLogic = new OrderLogic();
//	    protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
        $condition = array();
        $consignee = I('consignee');
        $order_sn = I('order_sn');
        $consignee && $condition['consignee'] = array('like',$consignee);
        $order_sn && $condition['order_sn'] = array('like',$order_sn);
        $condition['prom_id'] = array('neq',0);
        $condition['the_raise'] = array('eq',1);
        if(empty($consignee) && empty($order_sn)){
            if((I('shipping_status')==0 || I('shipping_status')==1))
            {
                $condition['order_type']=array('eq',14);
            }else{
                $condition['order_type']=array('eq',15);
            }
        }
        $condition['store_id'] = $_SESSION['merchant_id'];
        $count = M('order')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        $show = $Page->show();
        $orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function delivery_info()
    {
        $order_id = I('order_id');
        $orderLogic = new \Store\Logic\OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $this->assign('order', $order);
        $this->assign('orderGoods', $orderGoods);
        $delivery_record = M('delivery_doc')->where('order_id=' . $order_id)->select();
        $this->assign('delivery_record', $delivery_record);//发货记录
        $logistics = M('logistics')->select();
        $this->assign('logistics', $logistics);
        $this->assign('order_id', $order_id);
        $this->display();
    }
}