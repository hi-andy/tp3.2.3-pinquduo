<<<<<<< HEAD
<?php
/**
 */

namespace Store\Controller;

class ReportController extends BaseController{
	public $begin;
	public $end;
	public $order_type;
	public function _initialize(){
		$timegap = I('timegap');
		$gap = I('gap',7);
		if($timegap){
			$gap = explode('-', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y/m/d',(time()-$gap*60*60*24));//30天前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y/m/d'));
		}
		$this->assign('timegap',$begin.'-'.$end);
		$this->assign('order_type',C('ORDER_TYPE'));
		$this->begin = strtotime($begin);
		$this->end = strtotime($end);
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
	
	public function index(){
		//拿到凌晨的时间戳和第二天的凌晨时间戳
		$now = strtotime(date('Y-m-d'));
		$tomorrow = $now+24*3600;
		$where = " store_id = ".$_SESSION['merchant_id'] ." and ";
		$today['today_amount'] = M('order')->where($where."add_time>$now and add_time<$tomorrow AND pay_status=1")->sum('order_amount');//今日销售总额

		//计算今日订单数
		$today['today_order']= $single = M('order')->where($where."add_time>$now and add_time<$tomorrow and pay_status=1")->count();
		$today['cancel_order'] = M('order')->where($where."add_time>$now and add_time<$tomorrow AND is_cancel=1")->count();//今日取消订单
		//拿到总共能体现的资金
		$one = M('order')->where('order_type in (4,16) and store_id='.$_SESSION['merchant_id'])->select();
		$reflect = null;
		foreach($one as $v)
		{
			$temp = 2*3600*24;
			$cha = time()-$v['confirm_time'];
			if($cha>=$temp)
			{
				$reflect = $reflect+$v['order_amount'];
			}
		}
		//获取以前的提取记录
		$total = 0;
		$withdrawal_total = M('store_withdrawal')->where('store_id='.$_SESSION['merchant_id'].' and (status=1 or status=0 )')->field('withdrawal_money')->select();

		foreach($withdrawal_total as $v)
		{
			$total = $total+$v['withdrawal_money'];
		}
		$today['reflect'] = $reflect-$total;
		if(empty($today['reflect']))
			$today['reflect'] = 0;

		$today['sign'] = M('order')->where('pay_status=1 and store_id = '.$_SESSION['merchant_id'])->sum('order_amount');//总销售额

		$this->assign('today',$today);

		$sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order ";
		$sql .= "where ".$where." add_time>$this->begin and add_time<($this->end+24*3600) AND pay_status=1  group by gap";
		$res = M()->query($sql);//订单数,交易额

//		//获取取消订单数
//		$cancel = "SELECT count(*) AS cancel_num,FROM_UNIXTIME(add_time, '%Y-%m-%d') AS gap FROM __PREFIX__order WHERE store_id = ".$_SESSION['merchant_id']." AND add_time > $this->begin AND add_time < $this->end AND is_cancel = 1  GROUP BY	gap";
//		$cancel_res = M()->query($cancel);

		for($i=0;$i<count($res);$i++)
		{
			if($i==0){
				$res[$i]['all'] = $res[$i]['amount'];
			}else{
				$res[$i]['all'] = $res[$i-1]['all']+$res[$i]['amount'];
			}

		}

		$tamount = null;
		$tnum = null;
		$all = null;
		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$crr[$val['gap']] = $val['all'];
			$all += $val['all'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$all_num = empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);
			$all_arr[] = $all_num;
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'all'=>$all_num,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		for($i=0;$i<count($list);$i++)
		{
			if($list[$i]['all']==0 && $i!=0){
				$list[$i]['all'] = $list[$i-1]['all'];
			}
		}
		//倒叙
		rsort($list);

		$this->assign('list',$list);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'all'=>$all_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}

	//处理数组
	public function merge_arr_1($arr1,$arr2)
	{
		for($i=0;$i<count($arr1);$i++)
		{
			for($j=0;$j<count($arr2);$j++)
			{
				if($arr1[$i]['gap']==$arr2[$j]['gap'])
				{
					$arr1[$i]['cancel_num'] = $arr2[$j]['cancel_num'];
				}
			}
		}

		return $arr1;
	}

	public function merge_arr_2($arr1,$arr2)
	{
		//先将数组长度变的等长
		for($i=0;$i<count($arr2);$i++)
		{
			if($arr1[$i]['gap']!=$arr2[$i]['gap'])
			{
				$arr1[$i]['tnum'] = 0;
				$arr1[$i]['amount'] = 0;
				$arr1[$i]['gap'] = $arr2[$i]['gap'];
				$arr1[$i]['cancel_num'] = $arr2[$i]['cancel_num'];
			}else{
				$arr1[$i]['gap'] = $arr2[$i]['gap'];
				$arr1[$i]['cancel_num'] = $arr2[$i]['cancel_num'];
			}
		}

		return $arr1;
	}


	public function saleTop(){
		$sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods ";
		$sql .=" where is_send = 1 group by goods_id order by sale_amount DESC limit 100";
		$res = M()->cache(true,3600)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}
	
	public function userTop(){
		$sql = "select count(a.user_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.mobile,b.email from __PREFIX__order as a left join __PREFIX__users as b ";
		$sql .= "  on a.user_id = b.user_id where  store_id = ".$_SESSION['merchant_id']." and  a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 order by amount DESC limit 100";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}
	
	public function saleList(){
		$p = I('p',0);
		$end = $p*20;
		if($p==1)
		{
			$start = 0;
		}elseif($p>1){
			$start = ($p-1)*20;
		}else{
			$end = 20;
			$start = 0;
		}

		$sql = "select a.*,b.order_sn,b.order_amount,b.shipping_name,b.pay_name,b.add_time,b.order_type from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql .= " where b.store_id = ".$_SESSION['merchant_id']." and b.add_time>$this->begin and b.add_time<$this->end order by add_time limit $start,$end";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);

		$sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql2 .= " where b.store_id = ".$_SESSION['merchant_id']." and  b.add_time>$this->begin and b.add_time<$this->end  ";
		$total = M()->query($sql2);
		$count =  $total[0]['tnum'];
		$Page = new \Think\Page($count,20);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->display();
	}
	
	public function user(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$user['today'] = D('users')->where("reg_time>$today")->count();//今日新增会员
		$user['month'] = D('users')->where("reg_time>$month")->count();//本月新增会员
		$user['total'] = D('users')->count();//会员总数
		$user['user_money'] = D('users')->sum('user_money');//会员余额总额
		$res = M('order')->cache(true)->distinct(true)->field('user_id')->select();
		$user['hasorder'] = count($res);
		$this->assign('user',$user);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where store_id = ".$_SESSION['merchant_id']." and reg_time>$this->begin and reg_time<$this->end group by gap";
		$new = M()->query($sql);//新增会员趋势		
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		$this->display();
	}
	
	//财务统计
	public function finance(){
		$sql = "SELECT sum(a.order_amount) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= " FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.order_status in (1,2,4) group by gap order by a.add_time";
		$res = M()->cache(true)->query($sql);//物流费,交易额,成本价
		
		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
		}
			
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_goods_amount = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_shipping_amount =  empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
			$goods_arr[] = $tmp_goods_amount;
			$amount_arr[] = $tmp_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_amount,'shipping_amount'=>$tmp_shipping_amount,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		
		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'amount'=>$amount_arr,'shipping_arr'=>$shipping_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	
=======
<?php
/**
 */

namespace Store\Controller;

class ReportController extends BaseController{
	public $begin;
	public $end;
	public $order_type;
	public function _initialize(){
		$timegap = I('timegap');
		$gap = I('gap',7);
		if($timegap){
			$gap = explode('-', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y/m/d',(time()-$gap*60*60*24));//30天前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y/m/d'));
		}
		$this->assign('timegap',$begin.'-'.$end);
		$this->assign('order_type',C('ORDER_TYPE'));
		$this->begin = strtotime($begin);
		$this->end = strtotime($end);
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
	
	public function index(){
		//拿到凌晨的时间戳和第二天的凌晨时间戳
		$now = strtotime(date('Y-m-d'));
		$tomorrow = $now+24*3600;
		$where = " store_id = ".$_SESSION['merchant_id'] ." and ";
		$today['today_amount'] = M('order')->where($where."add_time>$now and add_time<$tomorrow AND pay_status=1")->sum('order_amount');//今日销售总额

		//计算今日订单数
		$today['today_order']= $single = M('order')->where($where."add_time>$now and add_time<$tomorrow and pay_status=1")->count();
		$today['cancel_order'] = M('order')->where($where."add_time>$now and add_time<$tomorrow AND is_cancel=1")->count();//今日取消订单
		//拿到总共能体现的资金
		$one = M('order')->where('order_type in (4,16) and store_id='.$_SESSION['merchant_id'])->select();
		$reflect = null;
		foreach($one as $v)
		{
			$temp = 2*3600*24;
			$cha = time()-$v['confirm_time'];
			if($cha>=$temp)
			{
				$reflect = $reflect+$v['order_amount'];
			}
		}
		//获取以前的提取记录
		$total = 0;
		$withdrawal_total = M('store_withdrawal')->where('store_id='.$_SESSION['merchant_id'].' and (status=1 or status=0 )')->field('withdrawal_money')->select();

		foreach($withdrawal_total as $v)
		{
			$total = $total+$v['withdrawal_money'];
		}
		$today['reflect'] = $reflect-$total;
		if(empty($today['reflect']))
			$today['reflect'] = 0;

		$today['sign'] = M('order')->where('pay_status=1 and store_id = '.$_SESSION['merchant_id'])->sum('order_amount');//总销售额

		$this->assign('today',$today);

		$sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order ";
		$sql .= "where ".$where." add_time>$this->begin and add_time<($this->end+24*3600) AND pay_status=1  group by gap";
		$res = M()->query($sql);//订单数,交易额

//		//获取取消订单数
//		$cancel = "SELECT count(*) AS cancel_num,FROM_UNIXTIME(add_time, '%Y-%m-%d') AS gap FROM __PREFIX__order WHERE store_id = ".$_SESSION['merchant_id']." AND add_time > $this->begin AND add_time < $this->end AND is_cancel = 1  GROUP BY	gap";
//		$cancel_res = M()->query($cancel);

		for($i=0;$i<count($res);$i++)
		{
			if($i==0){
				$res[$i]['all'] = $res[$i]['amount'];
			}else{
				$res[$i]['all'] = $res[$i-1]['all']+$res[$i]['amount'];
			}

		}

		$tamount = null;
		$tnum = null;
		$all = null;
		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$crr[$val['gap']] = $val['all'];
			$all += $val['all'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$all_num = empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);
			$all_arr[] = $all_num;
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'all'=>$all_num,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		for($i=0;$i<count($list);$i++)
		{
			if($list[$i]['all']==0 && $i!=0){
				$list[$i]['all'] = $list[$i-1]['all'];
			}
		}
		//倒叙
		rsort($list);

		$this->assign('list',$list);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'all'=>$all_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}

	//处理数组
	public function merge_arr_1($arr1,$arr2)
	{
		for($i=0;$i<count($arr1);$i++)
		{
			for($j=0;$j<count($arr2);$j++)
			{
				if($arr1[$i]['gap']==$arr2[$j]['gap'])
				{
					$arr1[$i]['cancel_num'] = $arr2[$j]['cancel_num'];
				}
			}
		}

		return $arr1;
	}

	public function merge_arr_2($arr1,$arr2)
	{
		//先将数组长度变的等长
		for($i=0;$i<count($arr2);$i++)
		{
			if($arr1[$i]['gap']!=$arr2[$i]['gap'])
			{
				$arr1[$i]['tnum'] = 0;
				$arr1[$i]['amount'] = 0;
				$arr1[$i]['gap'] = $arr2[$i]['gap'];
				$arr1[$i]['cancel_num'] = $arr2[$i]['cancel_num'];
			}else{
				$arr1[$i]['gap'] = $arr2[$i]['gap'];
				$arr1[$i]['cancel_num'] = $arr2[$i]['cancel_num'];
			}
		}

		return $arr1;
	}


	public function saleTop(){
		$sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods ";
		$sql .=" where is_send = 1 group by goods_id order by sale_amount DESC limit 100";
		$res = M()->cache(true,3600)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}
	
	public function userTop(){
		$sql = "select count(a.user_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.mobile,b.email from __PREFIX__order as a left join __PREFIX__users as b ";
		$sql .= "  on a.user_id = b.user_id where  store_id = ".$_SESSION['merchant_id']." and  a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 order by amount DESC limit 100";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}
	
	public function saleList(){
		$p = I('p',0);
		$end = $p*20;
		if($p==1)
		{
			$start = 0;
		}elseif($p>1){
			$start = ($p-1)*20;
		}else{
			$end = 20;
			$start = 0;
		}

		$sql = "select a.*,b.order_sn,b.order_amount,b.shipping_name,b.pay_name,b.add_time,b.order_type from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql .= " where b.store_id = ".$_SESSION['merchant_id']." and b.add_time>$this->begin and b.add_time<$this->end order by add_time limit $start,$end";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);

		$sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql2 .= " where b.store_id = ".$_SESSION['merchant_id']." and  b.add_time>$this->begin and b.add_time<$this->end  ";
		$total = M()->query($sql2);
		$count =  $total[0]['tnum'];
		$Page = new \Think\Page($count,20);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->display();
	}
	
	public function user(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$user['today'] = D('users')->where("reg_time>$today")->count();//今日新增会员
		$user['month'] = D('users')->where("reg_time>$month")->count();//本月新增会员
		$user['total'] = D('users')->count();//会员总数
		$user['user_money'] = D('users')->sum('user_money');//会员余额总额
		$res = M('order')->cache(true)->distinct(true)->field('user_id')->select();
		$user['hasorder'] = count($res);
		$this->assign('user',$user);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where store_id = ".$_SESSION['merchant_id']." and reg_time>$this->begin and reg_time<$this->end group by gap";
		$new = M()->query($sql);//新增会员趋势		
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		$this->display();
	}
	
	//财务统计
	public function finance(){
		$sql = "SELECT sum(a.order_amount) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= " FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.order_status in (1,2,4) group by gap order by a.add_time";
		$res = M()->cache(true)->query($sql);//物流费,交易额,成本价
		
		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
		}
			
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_goods_amount = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_shipping_amount =  empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
			$goods_arr[] = $tmp_goods_amount;
			$amount_arr[] = $tmp_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_amount,'shipping_amount'=>$tmp_shipping_amount,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		
		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'amount'=>$amount_arr,'shipping_arr'=>$shipping_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	
>>>>>>> 0b7f13d20f77f1260095c707f48567c3375029f4
}