<?php
namespace Store\Controller;
use Admin\Controller\ApiController;
use Api\Controller\QQPayController;
use Store\Logic\OrderLogic;
use Store\Logic\PromLogic;
use Think\AjaxPage;

class PromController extends BaseController {
	public  $order_type;
	public  $shipping_status;
	public  $pay_status;
	/*
	 * 初始化操作
	 */
	public function _initialize() {
		C('TOKEN_ON',false); // 关闭表单令牌验证
		// 订单 支付 发货状态
		$this->assign('order_type',C('GROUP_BUY'));
		$this->assign('pay_status',C('PAY_STATUS'));
		$this->assign('shipping_status',C('SHIPPING_STATUS'));
		$this->order_type = C('GROUP_BUY');
		$this->pay_status = C('PAY_STATUS');
		$this->shipping_status = C('SHIPPING_STATUS');

		if(empty($_SESSION['merchant_id']))
		{
			session_unset();
			session_destroy();
			$this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
		}
		$haitao = M('store_detail')->where('storeid='.$_SESSION['merchant_id'])->find();
		if($haitao['is_pay']==0)
		{
			$this->error("尚未缴纳保证金，现在前往缴纳",U('Store/Index/pay_money'));
		}
	}

	/*
	 *订单首页
	 */
	public function index(){
		$begin = date('Y/m/d',(time()-30*60*60*24));//30天前
		$end = date('Y/m/d',strtotime('+1 days'));
		$this->assign('timegap',$begin.'-'.$end);
		$this->assign('order_type',$this->order_type);
		$this->display();
	}

	/*
	 *Ajax首页
	 */
	public function ajaxindex(){
		$timegap = I('timegap');
		if($timegap){
			$gap = explode('-', $timegap);
			$begin = strtotime($gap[0]);
			$end = strtotime($gap[1]);
		}
		// 搜索条件
		$condition = array();
		$condition['g.is_raise'] = 0 ;
		$condition['g.store_id'] = $_SESSION['merchant_id'];
		$condition['o.is_show'] = 1 ;
		$condition['g.auto'] = 0;
		if($begin && $end){
			$condition['o.add_time'] = array('GT',$begin);
			$where['o.add_time'] = array('LT',$end);
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
			$condition['o.order_sn'] = array('like',I('order_sn'));
		}
		if(I('consignee'))
		{
			$condition['o.consignee'] = array('like',I('consignee'));
		}
		$count = M('group_buy')->alias('g')
			->join('LEFT JOIN __USERS__ u on g.user_id = u.user_id ')
			->join('LEFT JOIN __ORDER__ o on o.order_id = g.order_id')
			->where($condition)
			->where($where)
			->field('g.*,u.nickname,o.order_sn,o.pay_time,o.add_time')
			->order('o.add_time desc')
			->count();

		$Page  = new AjaxPage($count,20);
		//  搜索条件下 分页赋值
		foreach($condition as $key=>$val) {
			$Page->parameter[$key]   =  urlencode($val);
		}
		$show = $Page->show();

		$grouplist = M('group_buy')->alias('g')
			->join('LEFT JOIN __USERS__ u on g.user_id = u.user_id ')
			->join('LEFT JOIN __ORDER__ o on o.order_id = g.order_id')
			->where($condition)
			->where($where)
			->field('g.*,u.nickname,o.order_sn,o.pay_time,o.add_time')
			->order('o.add_time desc')
			->limit($Page->firstRow,$Page->listRows)
			->select();

        M('admin_log')->data([
            'admin_id' => 999,
            'log_info' => 'aaa',
            'log_ip' => '127.0.0.1',
            'log_url' => M()->getLastSql()
        ])->add();

		$this->assign('grouplist',$grouplist);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}
	/*
	 * ajax 发货订单列表
	*/
	public function ajaxdelivery(){
//		$orderLogic = new OrderLogic();
		//	    protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
		$condition = array();
		$order_sn = I('order_sn');
		$where = '1=1 and consignee is not null ';
		I('consignee') && $where = $where." and consignee like '%".I('consignee')."%'";
		$order_sn && $where = $where." and order_sn like '%$order_sn%' ";
		if(I('shipping_status')==0||I('shipping_status')==1)
		{
			$where = $where." and order_type = 14 ";
		}elseif(I('shipping_status')==2){
			$where = $where." and shipping_status = 1 ";
		}
		$where = $where." and store_id = ".$_SESSION['merchant_id']." ";
		$count = M('order')->where($where)->count();
		$Page  = new AjaxPage($count,10);
		//搜索条件下 分页赋值
		foreach($condition as $key=>$val) {
			$Page->parameter[$key]   =   urlencode($val);
		}
		$show = $Page->show();
		$orderList = M('order')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
		$this->assign('orderList',$orderList);
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
				->join('inner JOIN __USERS__ u on g.user_id = u.user_id ')
				->join('inner JOIN __ORDER__ o on o.order_id = g.order_id')
				->where(array('mark'=>$group_info['mark']))
				->field('g.id,g.start_time,g.end_time,g.goods_num,g.order_num,g.price,g.mark,g.is_pay,g.is_free,g.is_successful,u.nickname,o.order_sn,o.pay_time')
				->order('g.id desc')
				->select();
		}else{
			//如果是团员  获取团员和团长的信息
			$head_info = M('group_buy')->alias('g')
				->join('inner JOIN __USERS__ u on g.user_id = u.user_id ')
				->join('inner JOIN __ORDER__ o on o.order_id = g.order_id')
				->where(array('id'=>$group_info['mark']))
				->field('g.*,u.nickname,o.order_sn,o.pay_time')
				->order('g.id desc')
				->select();

			$member_infos = M('group_buy')->alias('g')
				->join('inner JOIN __USERS__ u on g.user_id = u.user_id ')
				->join('inner JOIN __ORDER__ o on o.order_id = g.order_id')
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

		$this->assign('button',$button);
		$this->assign('order',$order);
		$this->assign('orderGoods',$orderGoods);
		$this->assign('head_info',$head_info);
		$this->assign('member_infos',$member_infos);
		$this->assign('group_info',$group_info);
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
				$this->success('修改成功',U('Store/Prom/editprice',array('order_id'=>$order_id)));
			}else{
				$this->success('修改失败',U('Store/Prom/detail',array('order_id'=>$order_id)));
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
	 * 价钱修改
	 */
	public function editprice($order_id){
		$orderLogic = new PromLogic();
		$order = $orderLogic->getOrderInfo($order_id);
		$this->editable($order);
		if(IS_POST){
			$update['coupon_price'] = I('post.discount');
			$update['shipping_price'] = I('post.shipping_price');
			$update['order_amount'] = $order['total_amount'] + $update['shipping_price'] - $update['coupon_price'];
			$row = M('order')->where(array('order_id'=>$order_id))->save($update);

			if(!$row){

				$this->success('没有更新数据',U('Store/Prom/editprice',array('order_id'=>$order_id)));
			}else{

				$this->success('操作成功',U('Store/Prom/detail',array('order_id'=>$order_id)));
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
		if(I('remark')){
			$data = I('post.');
			$note = array('退款到用户余额','已通过其他方式退款','不处理，误操作项');
			if($data['refundType'] == 0 && $data['amount']>0){
				accountLog($data['user_id'], $data['amount'], 0,  '退款到用户余额');
			}
			$orderLogic = new OrderLogic();
			$d = $orderLogic->orderActionLog($data['order_id'],'pay_cancel',$data['remark'].':'.$note[$data['refundType']]);
			if($d){
				exit("<script>window.parent.pay_callback(1);</script>");
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
		$order = $orderLogic->getOrderInfo($order_id);
		$order['province'] = getRegionName($order['province']);
		$order['city'] = getRegionName($order['city']);
		$order['district'] = getRegionName($order['district']);
		$order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
		$orderGoods = $orderLogic->getOrderGoods($order_id);
		$shop = tpCache('shop_info');
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
			$this->error('物流插件不存在',U('Store/Index/index'));
		$orderLogic = new OrderLogic();
		$order = $orderLogic->getOrderInfo($id);
//	    var_dump($code);die;
		if(!$order)
			$this->error('订单不存在');
		//检查模板是否存在
		if(!file_exists(APP_PATH."Store/View/Plugin/shipping/{$code}_print.html"))
			$this->error('请先在插件中心设置打印模板',U('Store/Index/index'));
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
		$promLogic = new PromLogic();
		$data = I('post.');
		if($_POST['shipping_code']=='选择物流方式' || empty($_POST['shipping_order']))
		{
			$this->success('物流信息不全',U('Store/Prom/delivery_info',array('order_id'=>$_POST['order_id'])));
			exit();
		}
		$res = M('delivery_doc')->where('`order_id`='.$data['order_id'])->find();
		$res1 = M('order')->where('order_id='.$data['order_id'])->find();
		if(!empty($res) && !empty($res1['shipping_code']) && !empty($res1['shipping_order']) && !empty($res1['shipping_name']))
		{
			$this->success('已经发货了',U('Store/Order/delivery_list',array('order_id'=>$data['order_id'])));
			exit();
		}elseif(!empty($res) && empty($res1['shipping_code']) && empty($res1['shipping_order']) && empty($res1['shipping_name'])){
			$res = $promLogic->buchongfahuoxinxi($data);
		}else{
			$res = $promLogic->deliveryHandle($data);
		}
		if($res){
			reserve_logistics($data['order_id']);
			$custom = array('type' => '3','id'=>$data['order_id']);
			$user_id = $data['user_id'];
			SendXinge('卖家已经发货，请点击此处查看',"$user_id",$custom);
			$this->success('操作成功',U('Store/Prom/delivery_info',array('order_id'=>$data['order_id'])));
		}else{
			$this->success('操作失败',U('Store/Prom/delivery_info',array('order_id'=>$data['order_id'])));
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
		$this->assign('delivery_record',$delivery_record);//发货记录
		$logistics = M('logistics')->select();
		$this->assign('logistics',$logistics);
		$this->assign('order_id',$order_id);
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

		$where = "tp_return_goods.`store_id`=".$_SESSION['merchant_id']." and tp_return_goods.`is_prom`=1 ";
		$order_sn && $where.= " and tp_return_goods.order_sn like '%$order_sn%' ";
		empty($order_sn) && $where.= " and tp_return_goods.`status` = '$status' ";

		$count = M('return_goods')
			->join(" LEFT JOIN tp_group_buy ON tp_group_buy.order_id = tp_return_goods.order_id ")
			->field("tp_return_goods.*,tp_group_buy.`is_successful`")
			->where($where)
			->count();

		$Page  = new AjaxPage($count,13);
		$show = $Page->show();

		$list = M('return_goods')->where($where)
			->join(" LEFT JOIN tp_group_buy ON tp_group_buy.order_id = tp_return_goods.order_id ")
			->field("tp_return_goods.*,tp_group_buy.`is_successful`")
			->order("$order_by $sort_order")
			->limit("{$Page->firstRow},{$Page->listRows}")
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
		$status_msg = array('未处理','处理中','已完成');
		if(IS_POST) {
			$data['type'] = I('type');
			$data['status'] = I('status');
			$data['remark'] = I('remark');
			if ($data['status']==1&&empty($return_goods['one_time'])) {
				$data['one_time'] = time();
			}elseif($data['status']==2&&empty($return_goods['two_time'])){
				if(empty($return_goods['one_time'])){
					$data['one_time'] = time();
				}
				$custom = array('type' => '3','id'=>$return_goods['order_id']);
				$user_id = $return_goods['user_id'];
				SendXinge('卖家已同意退款，请点击此处查看',"$user_id",$custom);
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
                $base = new \Api_2_0_0\Controller\BaseController();
                $base->order_redis_status_ref($return_goods['user_id']);
			}
			$data['remark'] = I('remark');
			$note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
			$result = M('return_goods')->where("id= $id")->save($data);
		}
		$this->assign('id',$id); // 用户
		$this->assign('user',$user); // 用户
		$this->assign('goods',$goods);// 商品
		$this->assign('return_goods',$return_goods);// 退换货
		$this->display();
	}

	public function account_edit(){
		$order_id = I('order_id');
		$order = M('order')->where('`order_id`='.$order_id)->find();
		if($order['order_type']==9 || $order['order_type']==7)
		{
			echo json_encode(array('status'=>2,'msg'=>'已退款'));
			die;
		}
		if($order['order_type']==8){
			$Order_Logic = new OrderLogic();
			if($order['pay_code']=='weixin'){
				if ($order['is_jsapi']==1){
					$res = $Order_Logic->weixinJsBackPay($order['order_sn'], $order['order_amount']);
				}else{
					$res = $Order_Logic->weixinBackPay($order['order_sn'], $order['order_amount']);
				}
			}elseif($order['pay_code']=='alipay'){
				$res = $Order_Logic->alipayBackPay($order['order_sn'],$order['order_amount']);
			}elseif($order['pay_code'] == 'qpay'){
				$qqPay = new QQPayController();
				$res = $qqPay->doRefund($order['order_sn'], $order['order_amount']);
			}
		}else{
			$res['status'] = 1;
		}
		$result = M('return_goods')->where('order_id='.$order_id)->field('type')->find();
		if($res['status']==1){
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
			$base = new \Api_2_0_0\Controller\BaseController();
			$base->order_redis_status_ref($order['user_id']);
			M('return_goods')->where('order_id='.$order_id)->save(array('status'=>3));
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
			$this->error('已经提交过退货申请!',U('Store/Order/return_list'));
			exit;
		}
		$order = M('order')->where("order_id = $order_id")->find();

		$data['order_id'] = $order_id;
		$data['order_sn'] = $order['order_sn'];
		$data['goods_id'] = $goods_id;
		$data['addtime'] = time();
		$data['user_id'] = $order['user_id'];
		$data['remark'] = '管理员申请退换货'; // 问题描述
		M('return_goods')->add($data);
		$this->success('申请成功,现在去处理退货',U('Store/Order/return_list'));
		exit;
	}

	/**
	 * 订单操作
	 * @param $id
	 */
//ajax_submit_form('order-action','/index.php?s=/store/order/order_action/order_id/300/type/remove');
//array('status' => 0,'msg' => '操作失败'))
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
				exit(json_encode(array('status' => 0,'msg' => $a)));
			}
		}else{
			$this->error('参数错误',U('Store/Order/detail',array('order_id'=>$order_id)));
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
		$store_id = $_SESSION['merchant_id'];
		$where = ' where 1=1 and o.prom_id is not Null and o.the_raise = 0';
		$consignee = I('consignee');
		$timegap = I('timegap');
		if($timegap){
			$gap = explode('-', $timegap);
			$begin = strtotime($gap[0]);
			$end = strtotime($gap[1]);
			$where .= " and o.add_time<=$end and o.add_time>=$begin ";
		}
		if($consignee){
			$where .= "AND consignee like '%$consignee%' ";
		}
		$pay = $_POST['pay_code'];
		if($pay)
		{
			$where .= " AND pay_code = '$pay' ";
		}
		$order_sn =  I('order_sn');
		if($order_sn){
			$where .= "AND order_sn = '$order_sn' ";
		}
		if(!empty(I('order_type'))){
			$t = I('order_type');
			if($t==1){
				$where .= " AND o.order_type = 4 ";
			}elseif($t==2){
				$where .= " AND o.order_type = 5 ";
			}elseif($t==3){
				$where .= " AND o.order_type = 10 ";
			}elseif($t==4){
				$where .= " AND o.order_type = 11 ";
			}elseif($t==5){
				$where .= " AND o.order_type = 12 ";
			}elseif($t==6){
				$where .= " AND o.order_type = 13 ";
			}elseif($t==7){
				$where .= " AND o.order_type = 14 ";
			}elseif($t==8){
				$where .= " AND o.order_type = 15 ";
			}else{
				$where .= " AND o.order_type = 16 ";
			}
		}
		$sql = "select o.*,FROM_UNIXTIME(o.add_time,'%Y-%m-%d') as create_time from __PREFIX__order o LEFT JOIN __PREFIX__group_buy as g ON o.order_id = g.order_id LEFT JOIN tp_users u ON g.user_id = u.user_id $where and o.is_show = 1 and o.store_id='$store_id' order by order_id";

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
				$strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
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
			$order['store_id'] = $_SESSION['merchant_id'];

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

				$this->success('添加商品成功',U("Store/Order/detail",array('order_id'=>$order_id)));
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
			$where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件

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

}
