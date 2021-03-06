<?php
namespace Admin\Controller;
use Admin\Logic\OrderLogic;
use Admin\Logic\GoodsLogic;
use Api_2_0_2\Controller\QQPayController;
use Think\AjaxPage;

class OrderController extends BaseController {
    public  $order_status;
    public  $shipping_status;
    public  $pay_status;
	public  $order_type;
	/*
	 * 初始化操作
	 */
	public function _initialize()
	{
	    parent::_initialize();
		C('TOKEN_ON', false); // 关闭表单令牌验证
		// 订单 支付 发货状态
		$this->assign('order_type', C('SINGLE_BUY'));
		$this->assign('pay_status', C('PAY_STATUS'));
		$this->assign('shipping_status', C('SHIPPING_STATUS'));
		$this->order_type = C('SINGLE_BUY');
		$this->pay_status = C('PAY_STATUS');
		$this->shipping_status = C('SHIPPING_STATUS');
	}
    /*
     *订单首页
     */
    public function index(){
    	$begin = date('Y/m/d',(time()-30*60*60*24));//30天前
    	$end = date('Y/m/d',strtotime('+1 days'));

        $GoodsLogic = new GoodsLogic();
        $merchantList = $GoodsLogic->getSortMerchant();

        $merchant_id = I('merchant_id')?I('merchant_id'):0;
		$this->assign('order_type',$this->order_type);
	    if(!empty($merchant_id))
	    {
		    session('store_merchant_id',$merchant_id);
	    }
        $this->assign('merchantList',$merchantList);
    	$this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex(){
	    $orderLogic = new OrderLogic();
	    $timegap = I('timegap');
	    if($timegap){
		    $gap = explode('-', $timegap);
		    $begin = strtotime($gap[0]);
		    $end = strtotime($gap[1]);
	    }
	    // 搜索条件
	    $condition = array();
	    if(!empty($_SESSION['store_merchant_id']))
	    {
			$condition['o.store_id'] = array('eq',$_SESSION['store_merchant_id']);
	    }

	    I('consignee') ? $condition['o.consignee'] = trim(I('consignee')) : false;
	    if($begin && $end){
		    $condition['o.add_time'] = array('between',"$begin,$end");
	    }
	    if(!empty(I('order_type'))){
		    $condition['order_type'] = array('eq',I('order_type'));
		    if(I('order_type')==10)
			    $condition['o.order_type'] = array('eq',16);
	    }
	    if(!empty(I('get.merchant_id')))
	    {
		    $condition['o.store_id'] = array('eq',I('get.merchant_id'));
	    }
	    if(!empty(I('store_name')))
	    {
		    $this->assign('store_name', I('store_name'));
		    $store_id = M('merchant')->where("store_name = '".I('store_name')."'")->getField('id');
		    $condition['o.store_id'] = array('eq',$store_id);
	    }
	    I('order_sn') ? $condition['o.order_sn'] = trim(I('order_sn')) : false;
	    I('pay_code') != '' ? $condition['o.pay_code'] = I('pay_code') : false;
	    $condition['o.is_show'] = 1;

	    $sort_order = I('order_by','DESC').' '.I('sort');
	    $count = M('order')->alias('o')->where($condition)->where('`prom_id` is null')->count();
	    $Page  = new AjaxPage($count,20);
	    //  搜索条件下 分页赋值
	    foreach($condition as $key=>$val) {
		    $Page->parameter[$key]   =  urlencode($val);
	    }
	    $show = $Page->show();
	    //获取订单列表
	    $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);

	    $this->assign('orderList',$orderList);
	    $this->assign('page',$orderList);// 赋值分页输出
	    $this->assign('page',$show);
	    $this->assign('order_type',C('ORDER_TYPE'));

	    $this->display();
    }

	public function getStoreWhere($store_name)
	{
		$store_id = M('merchant')->where("`store_name` like '%".$store_name."%'")->select();
		$store_ids =null;
		$num = count($store_id);
		for($i=0;$i<$num;$i++)
		{
			if($num==1){
				$store_ids = $store_ids."'".$store_id[$i]['id']."'";
			}elseif($i==$num-1)
			{
				$store_ids = $store_ids."'".$store_id[$i]['id']."'";
			}elseif($i==0){
				$store_ids = $store_ids."'".$store_id[$i]['id']."',";
			}else{
				$store_ids = $store_ids."'".$store_id[$i]['id']."',";
			}
		}
		$where = $store_ids;
		return $where;
	}

    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery(){
//    	$orderLogic = new OrderLogic();
//	    protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
    	$condition = array();
	    $consignee = I('consignee');
	    $order_sn = I('order_sn');
	    $consignee && $condition['consignee'] = array('like',$consignee);
	    $order_sn && $condition['order_sn'] = array('like',$order_sn);
	    $condition['is_return_or_exchange'] = array('eq',0);
	    if(I('shipping_status')==0 ||I('shipping_status')==1)
	    {
		    $condition['order_type'] = array('eq',2);
	    }else{
		    $condition['order_type'] = array('eq',3);
	    }

    	$count = M('order')->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	//搜索条件下 分页赋值
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key]   =   urlencode($val);
    	}
    	$show = $Page->show();
    	$orderList = M('order')->where($condition)->where('`prom_id` is null')->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
    	$this->assign('orderList',$orderList);
    	$this->assign('page',$show);// 赋值分页输出
    	$this->display();
    }
    
    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $group_res = M('group_buy')->where(array('order_id'=>$order_id))->count();
        if($group_res){
            $button = $orderLogic->getOrderButton_group($order,$group_res);
        }else{
            $button = $orderLogic->getOrderButton($order);
        }
	    
        // 获取操作记录
        $action_log = M('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
            if($val['goods_num']>1){
                $split = 1;
            }
        }
        $this->assign('split',$split);
        $this->assign('button',$button);
        $this->display();
    }

    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function edit_order(){
    	$order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
    
        $orderGoods = $orderLogic->getOrderGoods($order_id);
                
       	if(IS_POST)
        {
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            //  获取省份
            $province = M('region')->where(array('parent_id'=>0,'level'=>1,'id'=>I('province')))->find();
            //  获取订单城市
            $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2,'id'=>I('city')))->find();
            //  获取订单地区
            $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3,'id'=>I('district')))->find();
            $order['address_base'] = $province['name'].$city['name'].$area['name'];
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //                  
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');            
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');                            
            $goods_id_arr = I("goods_id");
            $new_goods = $old_goods_arr = array();
            //################################订单添加商品
            if($goods_id_arr){
            	$new_goods = $orderLogic->get_spec_goods($goods_id_arr);
            	foreach($new_goods as $key => $val)
            	{
            		$val['order_id'] = $order_id;
            		$rec_id = M('order_goods')->add($val);//订单添加商品
            		if(!$rec_id)
            			$this->error('添加失败');
            	}
            }
            
            //################################订单修改删除商品
            $old_goods = I('old_goods');
            foreach ($orderGoods as $val){
            	if(empty($old_goods[$val['rec_id']])){
            		M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
            	}else{
            		//修改商品数量
            		if($old_goods[$val['rec_id']] != $val['goods_num']){
            			$val['goods_num'] = $old_goods[$val['rec_id']];
            			M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
            		}
            		$old_goods_arr[] = $val;
            	}
            }
            
            $goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
            	$this->error($result['msg']);
            }
       
            //################################修改订单费用
            $order['goods_price']    = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['shipping_price'];//物流费
            $order['order_amount']   = $result['result']['order_amount']; // 应付金额
            $order['total_amount']   = $result['result']['total_amount']; // 订单总价           
            $o = M('order')->where('order_id='.$order_id)->save($order);
            
            $l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            if($o && $l){
            	$this->success('修改成功',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
            	$this->success('修改失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        // 获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        //获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        
        $this->assign('order',$order);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        $this->display();
    }
    
    /*
     * 拆分订单
     */
    public function split_order(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	if($order['shipping_status'] != 0){
    		$this->error('已发货订单不允许编辑');
    		exit;
    	}
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	if(IS_POST){
    		$data = I('post.');
    		//################################先处理原单剩余商品和原订单信息
    		$old_goods = I('goods');
    		foreach ($orderGoods as $val){
    			if(empty($old_goods[$val['rec_id']])){
    				M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
    			}else{
    				//修改商品数量
    				if($old_goods[$val['rec_id']] != $val['goods_num']){
    					$val['goods_num'] = $old_goods[$val['rec_id']];
    					M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
    				}
    				$oldArr[] = $val;//剩余商品
    			}
    			$all_goods[$val['rec_id']] = $val;//所有商品信息
    		}
    		$result = calculate_price($order['user_id'],$oldArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    		if($result['status'] < 0)
    		{
    			$this->error($result['msg']);
    		}
    		//修改订单费用
    		$res['goods_price']    = $result['result']['goods_price']; // 商品总价
    		$res['order_amount']   = $result['result']['order_amount']; // 应付金额
    		$res['total_amount']   = $result['result']['total_amount']; // 订单总价
    		M('order')->where("order_id=".$order_id)->save($res);
			//################################原单处理结束
			
    		//################################新单处理
    		for($i=1;$i<20;$i++){
    			if(empty($_POST[$i.'_goods'])){
    				break;
    			}else{
    				$split_goods[] = $_POST[$i.'_goods'];
    			}
    		}

    		foreach ($split_goods as $key=>$vrr){
    			foreach ($vrr as $k=>$v){
    				$all_goods[$k]['goods_num'] = $v;
    				$brr[$key][] = $all_goods[$k];
    			}
    		}
    		
    		foreach($brr as $goods){
    			$result = calculate_price($order['user_id'],$goods,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    			if($result['status'] < 0)
    			{
    				$this->error($result['msg']);
    			}
    			$new_order = $order;
    			$new_order['order_sn'] = date('YmdHis').mt_rand(1000,9999);
    			$new_order['parent_sn'] = $order['order_sn'];
    			//修改订单费用
    			$new_order['goods_price']    = $result['result']['goods_price']; // 商品总价
    			$new_order['order_amount']   = $result['result']['order_amount']; // 应付金额
    			$new_order['total_amount']   = $result['result']['total_amount']; // 订单总价
    			$new_order['add_time'] = time();
    			unset($new_order['order_id']);
    			$new_order_id = M('order')->add($new_order);//插入订单表
    			foreach ($goods as $vv){
    				$vv['order_id'] = $new_order_id;
    				$nid = M('order_goods')->add($vv);//插入订单商品表
    			}
    		}
    		//################################新单处理结束
    		$this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
    	}
    	
    	foreach ($orderGoods as $val){
    		$brr[$val['rec_id']] = array('goods_num'=>$val['goods_num'],'goods_name'=>getSubstr($val['goods_name'], 0, 35).$val['spec_key_name']);
    	}
    	$this->assign('order',$order);
    	$this->assign('goods_num_arr',json_encode($brr));
    	$this->assign('orderGoods',$orderGoods);
    	$this->display();
    }
    
    /*
     * 价钱修改
     */
    public function editprice($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $this->editable($order);
        if(IS_POST){
        	$admin_id = session('admin_id');
            if(empty($admin_id)){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
			$update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = M('order')->where(array('order_id'=>$order_id))->save($update);
            if(!$row){
                $this->success('没有更新数据',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        $this->assign('order',$order);
        $this->display();
    }

    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order($order_id){
    	$orderLogic = new OrderLogic();
    	$del = $orderLogic->delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
        	$this->error('订单删除失败');
        }
    }
    
    /**
     * 订单取消付款
     */
    public function pay_cancel($order_id){
    	if(I('post.')){
    		$data = I('post.');
		    if($data['refundType']==1)
		    {
			    $order = M('order')->where(array('order_id'=>$data['order_id']))->find();
			    //检查是否未支付订单 已支付联系客服处理退款
			    if(empty($order))
				    return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
			    //检查是否未支付的订单
			    if($order['pay_status']>0)
				    return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');

			    $row = M('order')->where(array('order_id'=>$data['order_id']))->save(array('order_status'=>3,'is_cancel'=>1,'order_type'=>5));
			    if($order['prom_id']!=null) {
				    $res = M('group_buy')->where(array('order_id' =>$data['order_id']))->save(array('is_cancel' => 1));
			    }else{
				    $res =1;
			    }
			    if(!$row || !$res) {
	                exit("<script>window.parent.pay_callback(0);</script>");
	            }else{
	                exit("<script>window.parent.pay_callback(1);</script>");
	            }
		    }else{
			    exit("<script>window.parent.pay_callback(0);</script>");
		    }
    	}else{
    		$order = M('order')->where("order_id=$order_id")->find();
    		$this->assign('order',$order);
    		$this->display();
    	}
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

    /**
     * 快递单打印
     */
    public function shipping_print(){
        $code = I('get.code');
        $id = I('get.order_id');
        //查询是否存在订单及物流
        $shipping = M('plugin')->where(array('code'=>$code,'type'=>'shipping'))->find();
        if(!$shipping)
            $this->error('物流插件不存在',U('Admin/Index/index'));
        	$orderLogic = new OrderLogic();
        	$order = $orderLogic->getOrderInfo($id);
        if(!$order)
            $this->error('订单不存在');
        //检查模板是否存在
        if(!file_exists(APP_PATH."Admin/View/Plugin/shipping/{$code}_print.html"))
            $this->error('请先在插件中心设置打印模板',U('Admin/Index/index'));
        //获取商店信息
        $shop = tpCache('shop_info');
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $this->assign('shop',$shop);
        $this->assign('order',$order);
        $this->display("Plugin/shipping/{$code}_print");
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
	    $orderLogic = new OrderLogic();
	    $data = I('post.');
	    $res = M('delivery_doc')->where('`order_id`='.$data['order_id'])->find();
	    if(!empty($res))
	    {
		    $this->success('已经发货了',U('Store/Order/delivery_list',array('order_id'=>$data['order_id'])));
		    exit();
	    }
	    $res = $orderLogic->deliveryHandle($data);
	    if($res){
		    reserve_logistics($data['order_id']);
		    $this->success('操作成功',U('Store/Order/delivery_info',array('order_id'=>$data['order_id'])));
	    }else{
		    $this->success('操作失败',U('Store/Order/delivery_list',array('order_id'=>$data['order_id'])));
	    }
    }

    
    public function delivery_info(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id);

    	$this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
		$delivery_record = M('delivery_doc')->where('order_id='.$order_id)->select();
	    $logistics = M('logistics')->select();
	    $this->assign('logistics',$logistics);
	    $this->assign('order_id',$order_id);
//	    var_dump($logistics);die;
		$this->assign('delivery_record',$delivery_record);//发货记录
    	$this->display();
    }

    /**
     * 发货单列表
     */
    public function delivery_list(){
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

	    $where = " rg.`is_prom`=0 ";
	    $order_sn && $where.= " and rg.order_sn like '%$order_sn%' ";
	    empty($order_sn) && $where.= " and rg.`status` = '$status' ";

	    if (!empty(I('store_name'))) {
		    $this->assign('store_name', I('store_name'));
		    $store_id = M('merchant')->where("store_name = '".I('store_name')."'")->getField('id');
		    if(empty($store_id)){
			    $store_id = M('merchant')->where("store_name like '%".I('store_name')."%'")->getField('id');
		    }
		    $where = $where." and rg.store_id = $store_id ";
	    }

	    if (!empty(I('store_id'))) {
		    $store_id = I('store_id');
		    $where = $where." and rg.store_id = $store_id ";
	    }
	    $count = M('return_goods')
		    ->alias('rg')
		    ->where($where)
		    ->count();

	    $Page  = new AjaxPage($count,13);
	    $show = $Page->show();

	    $list = M('return_goods')
		    ->alias('rg')
		    ->join(array(" LEFT JOIN tp_group_buy gb ON gb.order_id = rg.order_id "))
		    ->join("LEFT JOIN tp_merchant m ON m.id = rg.store_id ")
		    ->order("gb.id $sort_order")
		    ->where($where)
		    ->order("$order_by $sort_order")
		    ->limit("{$Page->firstRow},{$Page->listRows}")
		    ->field('rg.*,m.store_name')
		    ->select();

	    $goods_id_arr = get_arr_column($list, 'goods_id');
	    if(!empty($goods_id_arr))
		    $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
	    $this->assign('goods_list',$goods_list);
	    $this->assign('list',$list);
	    $this->assign('page',$show);// 赋值分页输出
	    $this->display();
    }
    
    /**
     * 删除某个退换货申请
     */
    public function return_del(){
        $id = I('get.id');
        M('return_goods')->where("id = $id")->delete(); 
        $this->success('成功删除!');
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

	    $user = M('users')->where("user_id = {$return_goods['user_id']}")->find();
	    $goods = M('goods')->where("goods_id = {$return_goods['goods_id']}")->find();
	    $type_msg = array('退换','换货');
	    $status_msg = array('拒绝退款','未处理','已确认','处理中','已完成');
	    if(IS_POST)
	    {
//		    $data['type'] = I('type');
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
//		    $data['type'] = I('type');
//		    $data['status'] = I('status');
		    $data['remark'] = I('remark');
//		    $note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
		    $result = M('return_goods')->where("id= $id")->save($data);
		    if($result)
		    {
//			    $type = empty($data['type']) ? 2 : 3;
//			    $where = " order_id = ".$return_goods['order_id']." and goods_id=".$return_goods['goods_id'];
//			    M('order_goods')->where($where)->save(array('is_send'=>$type));//更改商品状态
//			    $orderLogic = new OrderLogic();
//			    $status = array();
//			    $status[-1] ='拒绝退款';
//			    $status[1]='已确认';
//			    $status[2]='处理中';
//			    $status[3]='已完成';
//			    $log = $orderLogic->orderActionLog($return_goods[order_id],$status[$data['status']],$note);
                $base = new \Api_2_0_0\Controller\BaseController();
                $base->order_redis_status_ref($return_goods['user_id']);
			    $this->success('修改成功!');
			    exit;
		    }
	    }
        $return_goods['imgs'] = str_replace("\\","",$return_goods['imgs']);
	    $this->assign('id',$id); // 用户
	    $this->assign('user',$user); // 用户
	    $this->assign('goods',$goods);// 商品
	    $this->assign('return_goods',$return_goods);// 退换货
//	      var_dump($return_goods);die;
	    $this->display();
    }
    
    /**
     * 管理员生成申请退货单
     */
    public function add_return_goods()
   {                
            $order_id = I('order_id'); 
            $goods_id = I('goods_id');
                
            $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id")->find();            
            if(!empty($return_goods))
            {
                $this->error('已经提交过退货申请!',U('Admin/Order/return_list'));
                exit;
            }
            $order = M('order')->where("order_id = $order_id")->find();
            
            $data['order_id'] = $order_id; 
            $data['order_sn'] = $order['order_sn']; 
            $data['goods_id'] = $goods_id; 
            $data['addtime'] = time(); 
            $data['user_id'] = $order[user_id];            
            $data['remark'] = '管理员申请退换货'; // 问题描述            
            M('return_goods')->add($data);            
            $this->success('申请成功,现在去处理退货',U('Admin/Order/return_list'));
            exit;
    }

    /**
     * 订单操作
     * @param $id
     */
    public function order_action(){    	
        $orderLogic = new OrderLogic();
        $action = I('get.type');
        $order_id = I('get.order_id');
        if($action && $order_id){
        	 $a = $orderLogic->orderProcessHandle($order_id,$action);       	
        	 $res = $orderLogic->orderActionLog($order_id,$action,I('note'));
        	 if($res && $a){
        	 	exit(json_encode(array('status' => 1,'msg' => '操作成功')));
        	 }else{
        	 	exit(json_encode(array('status' => 0,'msg' => '操作失败')));
        	 }
        }else{
        	$this->error('参数错误',U('Admin/Order/detail',array('order_id'=>$order_id)));
        }
    }
    
    public function order_log(){
    	$timegap = I('timegap');
    	if($timegap){
    		$gap = explode('-', $timegap);
    		$begin = strtotime($gap[0]);
    		$end = strtotime($gap[1]);
    	}
    	$condition = array();
    	$log =  M('order_action');
    	if($begin && $end){
    		$condition['log_time'] = array('between',"$begin,$end");
    	}
    	$admin_id = I('admin_id');
		if($admin_id >0 ){
			$condition['action_user'] = $admin_id;
		}
    	$count = $log->where($condition)->count();
    	$Page = new \Think\Page($count,20);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list',$list);
    	$this->assign('page',$show);   	
    	$admin = M('admin')->getField('admin_id,user_name');
    	$this->assign('admin',$admin);    	
    	$this->display();
    }

    /**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order){
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    public function export_order()
    {
    	//搜索条件
		$where = 'where 1=1 ';
		$consignee = I('consignee');
		if($consignee){
			$where .= "AND consignee like '%$consignee%' ";
		}
		$order_sn =  I('order_sn');
		if($order_sn){
			$where .= "AND order_sn = '$order_sn' ";
		}
		if(I('order_status')){
			$where .= "AND order_status = ".I('order_status');
		}

        if(I('merchant_id')){
            $where .= "AND store_id =".I('merchant_id');
        }

		$add_time = I('add_time');
		$sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__order $where order by order_id";
		if($add_time){
			$sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__order $where and create_time='$add_time' order by order_id";
		}
    	$orderList = D()->query($sql);
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
    	$strTable .= '</tr>';
    	
    	foreach($orderList as $k=>$val){
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['address_base'].$val['address'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';    
    		$orderGoods = D('order_goods')->where('order_id='.$val['order_id'])->select();
    		$strGoods="";
    		foreach($orderGoods as $goods){
    			$strGoods .= " 商品名称：".$goods['goods_name'];
    			if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
    			$strGoods .= "<br />";
    		}
    		unset($orderGoods);
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
    		$strTable .= '</tr>';
    	}
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
    	exit();
    }
    
    /**
     * 退货单列表
     */
    public function return_list(){
        $this->display();
    }
    
    /**
     * 添加一笔订单
     */
    public function add_order()
    {
        $order = array();
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //  获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //  获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        //  获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        if(IS_POST)
        {
            $order['user_id'] = I('user_id');// 用户id 可以为空
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县

            //  获取省份
            $province = M('region')->where(array('parent_id'=>0,'level'=>1,'id'=>I('province')))->find();
            //  获取订单城市
            $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2,'id'=>I('city')))->find();
            //  获取订单地区
            $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3,'id'=>I('district')))->find();
            $order['address_base'] = $province['name'].$city['name'].$area['name'];
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注            
            $order['order_sn'] = date('YmdHis').mt_rand(1000,9999); // 订单编号;
            $order['admin_note'] = I('admin_note'); // 
            $order['add_time'] = time(); //                    
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');            
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');            
                            
            $goods_id_arr = I("goods_id");
            $orderLogic = new OrderLogic();
            $order_goods = $orderLogic->get_spec_goods($goods_id_arr);          
            $result = calculate_price($order['user_id'],$order_goods,$order['shipping_code'],0,$order[province],$order[city],$order[district],0,0,0,0);      
            if($result['status'] < 0)	
            {
                 $this->error($result['msg']);      
            } 
           
           $order['goods_price']    = $result['result']['goods_price']; // 商品总价
           $order['shipping_price'] = $result['result']['shipping_price']; //物流费
           $order['order_amount']   = $result['result']['order_amount']; // 应付金额
           $order['total_amount']   = $result['result']['total_amount']; // 订单总价
           
            // 添加订单
            $order_id = M('order')->add($order);
            if($order_id)
            {
                foreach($order_goods as $key => $val)
                {
                    $val['order_id'] = $order_id;
                    $rec_id = M('order_goods')->add($val);
                    if(!$rec_id)                 
                        $this->error('添加失败');                                  
                }
                $this->success('添加商品成功',U("Admin/Order/detail",array('order_id'=>$order_id)));
                exit();
            }
            else{
                $this->error('添加失败');
            }                
        }     
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);        
        $this->display();
    }
    
    /**
     * 选择搜索商品
     */
    public function search_goods()
    {
    	$brandList =  M("brand")->select();
    	$categoryList =  M("goods_category")->select();
    	$this->assign('categoryList',$categoryList);
    	$this->assign('brandList',$brandList);   	
    	$where = ' is_on_sale = 1 ';//搜索条件
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));    		
            $grandson_ids = getCatGrandson(I('cat_id')); 
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条
    	}
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}  	
    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit(10)->select();
                
        foreach($goodsList as $key => $val)
        {
            $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
            $goodsList[$key]['spec_goods'] = $spec_goods;            
        }
    	$this->assign('goodsList',$goodsList);
    	$this->display();        
    }
    
    public function ajaxOrderNotice(){
        $order_amount = M('order')->where(array('order_status'=>0))->count();
        echo $order_amount;
    }

    /**
     * 退款按钮操作
     */
    public function account_edit(){
	    $order_id = I('order_id');
	    $return_goods = M('return_goods')->where('`order_id`='.$order_id)->field('order_sn,gold,pay_code')->find();
	    $order = M('order')->where('`order_id`='.$order_id)->find();
	    if($order['order_type']==9 || $order['order_type']==7)
	    {
		    echo json_encode(array('status'=>2,'msg'=>'已退款'));
		    die;
	    }

	    if($order['order_type']==8){
		    $Order_Logic = new OrderLogic();
		    if($return_goods['pay_code']=='weixin'){
			    if ($order['is_jsapi']==1){
				    $res = $Order_Logic->weixinJsBackPay($return_goods['order_sn'], $return_goods['gold']);
			    }else{
				    $res = $Order_Logic->weixinBackPay($return_goods['order_sn'], $return_goods['gold']);
			    }
		    }elseif($return_goods['pay_code']=='alipay' || $return_goods['pay_code']=='alipay_wap'){
			    $res = $Order_Logic->alipayBackPay($return_goods['order_sn'],$return_goods['gold']);
		    }elseif($return_goods['pay_code'] == 'qpay'){
			    $qqPay = new QQPayController();
			    $res = $qqPay->doRefund($return_goods['order_sn'], $return_goods['gold']);
		    }
	    }else{
		    $res['status'] = 1;
	    }

	    //找到退款的类型
	    $result = M('return_goods')->where('order_id='.$order_id)->field('type')->find();
	    if($res['status'] == 1){
		    if($result['type']==0)
		    {//退货
			    $data['order_status'] = 7;
			    $data['order_type'] = 9;
			    $this->fallback($order);
		    }elseif($result['type']==1)
		    {
			    //换货
			    $data['order_status'] = 5;
			    $data['order_type'] = 7;
		    }
		    $base = new \Api_2_0_0\Controller\BaseController();
		    $base->order_redis_status_ref($order['user_id']);
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

	public function punishment()
	{
		if($_GET)
		{
			$order = M('order')->where('order_id='.$_GET['order_id'])->find();
			$store = M('merchant')->where('id='.$order['store_id'])->getField('store_name');
			$user_name =M('users')->where('user_id='.$order['user_id'])->getField('nickname');
			$this->assign('user_name',$user_name);
			$this->assign('store',$store);
			$this->assign('order',$order);
			$this->display();
		}
		if($_POST)
		{
			$info = M('store_punishment')->where('order_sn='.$_POST['order_sn'])->find();
			if(!empty($info)&&$info['status']==1){
				$this->success('已惩罚!',U("Admin/Order/index"));
			}

			$order = M('order')->where('order_sn='.$_POST['order_sn'])->find();
			$user_info = M('users')->where('user_id='.$order['user_id'])->field("REPLACE(mobile, SUBSTR(mobile,4,4), '****') as mobile,nickname")->find();
			$store = M('merchant')->where('id='.$order['store_id'])->field('id,store_name')->find();
			$admin =M('admin')->where('admin_id='.session('admin_id'))->find();
			$data['store_id'] = $store['id'];
			$data['store_name'] = $store['store_name'];
			$data['order_id'] = $order['order_id'];
			$data['user_id'] = $order['user_id'];
			if(!empty($user_info['nickname'])){
				$data['user_name'] = $user_info['nickname'];
			}else{
				$data['user_name'] = $user_info['mobile'];
			}
			$data['order_add_time'] = $order['add_time'];
			$data['order_sn'] = $order['order_sn'];
			$data['order_amount'] = $order['order_amount'];
			$data['sp_penal_sum'] = (float)$_POST['money'];
			$data['reason'] = (string)$_POST['text'];
			$data['admin_id'] = $admin['admin_id'];
			$data['admin_name'] = $admin['user_name'];
			$data['datetime'] = date('Y-m-d H:m:s',time());
			$data['type']=I('type');//后台惩罚增加type保存 2017-8-28 14:59:57 李则云
			$res = M('store_punishment')->data($data)->add();
			$res1 = M('store_detail')->where('storeid = ' . $order['store_id'])->setDec('margin', $data['sp_penal_sum']);
			if($res && $res1)
			{
				$this->success('修改成功!',U("Admin/Order/index"));
			}else{
				$this->success('修改失败!');
			}
		}
	}
}
