<?php
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Api\Controller\BaseController;
use Api\Controller\QQPayController;
use Store\Logic\PromLogic;
use Think\AjaxPage;
use Admin\Logic\OrderLogic;
/*
 * 团购订单管理
 */
class GroupController extends BaseController {

    public  $order_status;
    public  $shipping_status;
    public  $pay_status;
    public  $order_type;
    /*
     * 初始化操作
     */
    public function _initialize() {
        C('TOKEN_ON',false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->order_type = C('GROUP_BUY');
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
    }


    /**
     * 显示团长订单列表
     */
    public function group_list(){
        if(I('merchant_id')){
            session('m_id',I('merchant_id'));
        }
        $this->assign('order_type',$this->order_type);
        $this->show();
    }

    /**
     * AJAX加载团购订单
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
        if($_SESSION['m_id'])
        {
            $condition['o.store_id'] = array('eq',$_SESSION['m_id']);
        }

        $condition['g.is_raise'] = 0 ;
        $condition['o.is_show'] = 1 ;
        if($begin && $end){
            $condition['o.add_time'] = array('GT',$begin);
            $condition['o.add_time'] = array('LT',$end);
        }
        if(!empty(I('order_type'))){
            $t = I('order_type');
            if($t==1){
                $condition['o.order_type'] = array('eq',4);
            }elseif($t==2){
                $condition['o.order_type'] = array('eq',5);
            }elseif($t==3){
                $condition['o.order_type'] = array('eq',10);
            }elseif($t==4){
                $condition['o.order_type'] = array('eq',11);
            }elseif($t==5){
                $condition['o.order_type'] = array('eq',12);
            }elseif($t==6){
                $condition['o.order_type'] = array('eq',13);
            }elseif($t==7){
                $condition['o.order_type'] = array('eq',14);
            }elseif($t==8){
                $condition['o.order_type'] = array('eq',15);
            }else{
                $condition['o.order_type'] = array('eq',16);
            }
        }
        if(I('Open_group')){
            if(I('Open_group')==1){
                $condition['_string'] = " g.goods_num = g.order_num ";
            }elseif(I('Open_group')==2){
                $condition['_string'] = " g.goods_num != g.order_num ";
            }
        }

        if(I('pay_code'))
        {
            $condition['o.pay_code'] = array('eq',I('pay_code'));
        }
        if(I('order_sn'))
        {
            $condition['o.order_sn'] = array('eq',I('order_sn'));
        }
        if(!empty(I('store_name')))
        {
            $this->assign('store_name', I('store_name'));
            $store_id = M('merchant')->where("store_name like '%".I('store_name')."%'")->getField('id');
            $condition['o.store_id'] = array('eq',$store_id);
        }
        $count = M('group_buy')->alias('g')
            ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
            ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
            ->join('INNER JOIN tp_order_goods d on d.order_id = g.order_id')
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
            ->join('INNER JOIN tp_order_goods d on d.order_id = g.order_id')
            ->join('INNER JOIN tp_merchant m on o.store_id = m.id')
            ->where($condition)
            ->field('g.id,g.start_time,g.end_time,g.mark,g.free,g.is_successful,g.is_pay,g.goods_name,g.price,u.nickname,o.order_sn,o.pay_time,o.add_time,m.store_name,d.goods_price')
            ->order('o.add_time desc')
            ->limit($Page->firstRow,$Page->listRows)
            ->select();

        $this->assign('grouplist',$grouplist);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 显示团购订单详情
     */
    public function detail(){

        $group_id = $_REQUEST['group_id'];

        $group_info = M('group_buy')->where(array('id'=>$group_id))->find();
        $head_info=array();
        //如果是团长 获取团员信息
        if($group_info['mark'] == 0){
            $member_infos = M('group_buy')->alias('g')
                ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
                ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
                ->where(array('mark'=>$group_info['mark']))
                ->field('g.*,u.nickname,o.order_sn,o.pay_time')
                ->order('g.id desc')
                ->select();
        }else{
            //如果是团员  获取团员和团长的信息
            $head_info = M('group_buy')->alias('g')
                ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
                ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
                ->where(array('id'=>$group_info['mark']))
                ->field('g.*,u.nickname,o.order_sn,o.pay_time')
                ->order('g.id desc')
                ->select();

            $member_infos = M('group_buy')->alias('g')
                ->join('INNER JOIN __USERS__ u on g.user_id = u.user_id ')
                ->join('INNER JOIN __ORDER__ o on o.order_id = g.order_id')
                ->where(array('mark'=>$group_info['mark']))
                ->field('g.*,u.nickname,o.order_sn,o.pay_time')
                ->order('g.id desc')
                ->select();
        }
        $order_id = $group_info['order_id'];
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);

        $group_res = M('group_buy')->where(array('order_id'=>$order_id))->count();
        if($group_res){
            $button = $orderLogic->getOrderButton_group($order,$group_info);
        }else{
            $button = $orderLogic->getOrderButton($order);
        }
//        var_dump($button);die;
        $this->assign('button',$button);
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('head_info',$head_info);
        $this->assign('member_infos',$member_infos);
        $this->assign('group_info',$group_info);
        $this->display();
    }

    /*
	 * ajax 发货订单列表
	*/
    public function ajaxdelivery(){
//		$orderLogic = new OrderLogic();
        //	    protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
        $condition = array();
        $consignee = "%".I('consignee')."%";
        $order_sn = I('order_sn');
        $consignee && $condition['consignee'] = array('like',$consignee);
        $order_sn && $condition['order_sn'] = array('like',$order_sn);
        $condition['order_status'] = array('eq',11);
        $condition['pay_status'] = array('eq',1);
        $shipping_status = I('shipping_status');
        $condition['prom_id'] = array('neq',0);
        if(I('shipping_status')==0)
        {
            $condition['automatic_time']=array('eq',0);
        }else{
            $condition['automatic_time']=array('neq',0);
        }
        $condition['shipping_status'] = empty($shipping_status) ? array('neq',1) : $shipping_status;

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

    public function account_edit(){
        $order_id = I('order_id');
        $order = M('order')->where('`order_id`='.$order_id)->find();
        $Order_Logic = new OrderLogic();
        if($order['order_type']==9 || $order['order_type']==7)
        {
            echo json_encode(array('status'=>2,'msg'=>'已退款'));
            die;
        }
        if($order['pay_code']=='weixin')
        {
            if ($order['is_jsapi']==1){
                $res = $Order_Logic->weixinJsBackPay($order['order_sn'], $order['order_amount']);
            }else{
                $res = $Order_Logic->weixinBackPay($order['order_sn'], $order['order_amount']);
            }
        }elseif($order['pay_code']=='alipay'){
            $res = $Order_Logic->alipayBackPay($order['order_sn'],$order['order_amount']);
        }elseif($order['pay_code'] == 'qpay'){
            // Begin code by lcy
            $qqPay = new QQPayController();
            $res = $qqPay->doRefund($order['order_sn'], $order['order_amount']);
            // End code by lcy
        }
        $result = M('order_goods')->where('order_id='.$order_id)->field('type')->find();
        if($res['status'] == 1){
            if($result['type']==0)
            {
                //退货
                $data['order_status'] = 7;
                $data['order_type'] = 9;
                $this->fallback($order);
            }elseif($result['type']==1)
            {
                //换货
                $data['order_status'] = 5;
                $data['order_type'] = 7;
            }
//            M('return_goods')->where('order_id='.$order_id)->save(array('status'=>3));
            M('order')->where('`order_id`='.$order_id)->data($data)->save();
            echo json_encode(array('status'=>1,'msg'=>'退款成功'));
        }else{
            echo json_encode(array('status'=>0,'msg'=>'退款失败'));
        }
        die;
    }



    public function fallback($orders)
    {
        //商品销量减去订单中的数量
        M('goods')->where('`goods_id`='.$orders['goods_id'])->setDec('sales',$orders['num']);
        //门店总销量减去订单中的数量
        M('merchant')->where('`id`='.$orders['store_id'])->setDec('sales',$orders['num']);
        //规格库存回复到原来的样子
        $spec_name = M('order_goods')->where('`order_id`='.$orders['order_id'])->field('spec_key')->find();
        M('spec_goods_price')->where('`goods_id`='.$orders['goods_id']." and `key`='".$spec_name['spec_key']."'")->setInc('store_count',$orders['num']);
    }

    public function delivery_info(){
        $order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
        $delivery_record = M('delivery_doc')->where('order_id='.$order_id)->select();
        $this->assign('delivery_record',$delivery_record);//发货记录
        $logistics = M('logistics')->select();
        $this->assign('logistics',$logistics);
        $this->assign('order_id',$order_id);
        $this->display();
    }

    /*
	 * ajax 退货订单列表
	 */
    public function ajax_return_list(){
        // 搜索条件
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');

        $where = "`is_prom`=1 and tp_group_buy.is_successful=1 ";
        $order_sn && $where.= " and tp_return_goods.order_sn like '%$order_sn%' ";
        empty($order_sn) && $where.= " and tp_return_goods.status = '$status' ";

        $count = M('return_goods')->
        join(array(" LEFT JOIN tp_group_buy ON tp_group_buy.order_id = tp_return_goods.order_id "))->
        field("tp_return_goods.*,tp_group_buy.`is_successful`")->
        where($where)->
        count();

        $Page  = new AjaxPage($count,13);
        $show = $Page->show();
        $list = M('return_goods')->where($where)->
        join(array(" LEFT JOIN tp_group_buy ON tp_group_buy.order_id = tp_return_goods.order_id "))->
        field("tp_return_goods.*,tp_group_buy.`is_successful`")->
        order("$order_by $sort_order")->
        limit("{$Page->firstRow},{$Page->listRows}")->
        select();

        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 退换货操作
     */
    public function return_info()
    {
        $id = I('id');
        $return_goods = M('return_goods')->where("id= $id")->find();
        if($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);

        $num = count($return_goods['imgs']);
        $return_goods = $this->getIMG($return_goods,$num);

        $user = M('users')->where("user_id = {$return_goods[user_id]}")->find();
        $goods = M('goods')->where("goods_id = {$return_goods[goods_id]}")->find();
        $type_msg = array('退换','换货');
        $status_msg = array('未处理','处理中','已完成');
        if(IS_POST) {
//            $data['type'] = I('type');
            $data['status'] = I('status');
            $data['remark'] = I('remark');
            if ($data['status']==1&&empty($return_goods['one_time'])) {
                $data['one_time'] = time();
            }elseif($data['status']==2&&empty($return_goods['two_time'])){
                if(empty($return_goods['one_time']))
                {
                    $data['one_time'] = time();
                }
                $data['two_time'] = time();
            }elseif($data['status']==3&&empty($return_goods['ok_time'])){
                $order = M('order')->where('order_id='.$return_goods['order_id'])->find();
                if($order['order_type']==8)
                {
                    if($order['order_type']!=9)
                    {
                        $this->error('请先退款！');
                        die;
                    }
                }
                $data['ok_time'] = time();
            }elseif($data['status']==-1&&empty($return_goods['ok_time']))
            {
                $data['ok_time'] = time();//和完成共用一个时间
                //将order状态改变
                M('order')->where('order_id='.$return_goods['order_id'])->save(array('order_type'=>16,'order_status'=>15));
            }
            $data['remark'] = I('remark');
//            $note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
            $result = M('return_goods')->where("id= $id")->save($data);
            if($result)
            {
//                $type = empty($data['type']) ? 2 : 3;
//                $where = " order_id = ".$return_goods['order_id']." and goods_id=".$return_goods['goods_id'];
//                M('order_goods')->where($where)->save(array('is_send'=>$type));//更改商品状态
//                $promLogic = new PromLogic();
//                $status = array();
//                $status[1]='已确认';
//                $status[2]='处理中';
//                $status[3]='已完成';
//                $log = $promLogic->orderActionLog($return_goods[order_id],$status[$data['status']],$note);
                $this->success('修改成功!');
                exit;
            }
        }
        $this->assign('id',$id); // 用户
        $this->assign('user',$user); // 用户
        $this->assign('goods',$goods);// 商品
        $this->assign('return_goods',$return_goods);// 退换货
        $this->display();
    }


    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print($order_id){
        $orderLogic = new OrderLogic();
        $order = M('order')->where('order_id='.$order_id)->find();
        $order['full_address'] = $order['address_base'].' '. $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $orderGoods[0]['order_amount'] = $order['order_amount'];
        $shop = M('merchant')->where('id='.$order['store_id'])->find();
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        $this->display('print');
    }

    /*
	 * 对退货的图片进行操作
	 * */
    public function getIMG($return_goods,$num)
    {
        for($i=0;$i<$num;$i++)
        {
            if(strstr($return_goods['imgs'][$i],'"width"')||strstr($return_goods['imgs'][$i],'height'))
            {
                unset($return_goods['imgs'][$i]);
            }
            elseif(strstr($return_goods['imgs'][$i],'{"origin":"')||strstr($return_goods['imgs'][$i],'small')||strstr($return_goods['imgs'][$i],'"}')||strstr($return_goods['imgs'][$i],'"}')||strstr($return_goods['imgs'][$i],']')||strstr($return_goods['imgs'][$i],'jpg"'))
            {
                $return_goods['imgs'][$i] = str_replace(array('[{"origin":"','"small":"','{"origin":"','"}',']','"'),"",$return_goods['imgs'][$i]);
            }
        }
        $return_goods['imgs'] = array_values($return_goods['imgs']);
        foreach($return_goods['imgs'] as &$v)
        {
            $v = C('HTTP_URL').$v;
        }
        $nums = count($return_goods['imgs']);
        for($j=0;$j<$nums;$j++)
        {
            if($j%2==0)
            {
                unset($return_goods['imgs'][$j]);
            }
        }
        $return_goods['imgs'] = array_values($return_goods['imgs']);
        return $return_goods;
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
            $this->success('已经发货了',U('Admin/Group/delivery_list',array('order_id'=>$data['order_id'])));
            exit();
        }
        $res = $promLogic->deliveryHandle($data);
        //
        if($res){
            reserve_logistics($data['order_id']);
            $this->success('操作成功',U('Admin/Group/delivery_info',array('order_id'=>$data['order_id'])));
        }else{
            $this->success('操作失败',U('Admin/Group/delivery_info',array('order_id'=>$data['order_id'])));
        }
    }
}