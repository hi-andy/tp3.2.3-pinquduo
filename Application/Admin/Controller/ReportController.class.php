<?php
/**
 */

namespace Admin\Controller;

use Admin\Logic\GoodsLogic;
use Think\AjaxPage;

class ReportController extends BaseController{
	public $begin;
	public $end;
	public $merchant_id;
	public $order_type;
	public function _initialize(){
		$timegap = I('timegap');
		$gap = I('gap',7);
		$merchant_id = I('merchant_id');

		if($timegap){
			$gap = explode('-', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y/m/d',(time()-$gap*60*60*24));//30天前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y/m/d'));
		}

		if($merchant_id){
			$this->merchant_id = $merchant_id;
		}else{
			$merchant_id = 0;
			$this->merchant_id = 0;
		}
		if($end!=null)
			session('end',$end);
		$this->assign('timegap',$begin.'-'.$_SESSION['end']);
		$this->assign('merchant_id',$merchant_id);
		$this->assign('order_type',C('ORDER_TYPE'));
		$this->begin = strtotime($begin);
		$this->end = strtotime($end);
	}
	
	public function index(){
		//统计各种参数
		$today = strtotime(date('Y-m-d'));
//		$month = strtotime(date('Y-m-01'));
//		$nextmonth = strtotime(date('Y-m-01',strtotime('+1 month')));
		$data['ri'] = M('order')->where(' add_time>'.$today.' and add_time<'.($today+24*3600).' and pay_status=1')->sum('order_amount');//日销售总额
		$data['zong'] = M('order')->where('pay_status=1')->sum('order_amount');//总销售额


		$data['ding'] = M('order')->where(' add_time>'.$today.' and add_time<'.($today+24*3600).' and  pay_status=1')->count();//日订单数
		$data['cancel'] = M('order')->where(' add_time>'.$today.' and add_time<'.($today+24*3600).' and  is_cancel=1')->count();//日取消订单数
//		$data['month'] = M('order')->where(' add_time>'.$month.' and add_time<'.$nextmonth.' and  order_type=4')->sum('order_amount');//月销售额
		$data['ti'] = M('store_withdrawal')->where('status=1')->sum('withdrawal_money');//提现金额
		$data['ti'] = $this->operationPrice($data['ti']);
		$keti = M('order')->where(' add_time>'.$today.' and add_time<'.($today+24*3600).' and (order_type=4 or order_type = 16)')->sum('order_amount');
		$data['keti'] = $keti-$data['ti'];
		$data['keti'] = $this->operationPrice($data['keti']);
		$data['tuikuan'] = M('order')->where("order_type IN ('8','9','13','14')")->sum('order_amount');
//		var_dump(M()->getLastSql());die;
		$this->assign('data',$data);

		$sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order ";
		$sql .= "where add_time>=$this->begin and add_time<($this->end+24*3600) AND pay_status=1  group by gap";
		$res = M()->query($sql);//订单数,交易额

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

	public function saleTop()
	{
		$type = I('get.type');
		if (empty($type))
		{
			$type=1;
		}
		if($type==1)
		{
			$sql = 	"SELECT	SUM(o.order_amount) AS q,m.id,m.store_name as name,	m.sales FROM __PREFIX__order o LEFT JOIN  __PREFIX__merchant m on  o.store_id = m.id WHERE	m.state = 1 AND o.order_type = 4 GROUP BY	o.store_id ORDER BY	m.sales DESC limit 50";
		}elseif($type==2){
			$sql = "SELECT SUM(o.order_amount) AS q,g.goods_id,g.goods_name as name,g.sales FROM tp_order o LEFT JOIN tp_goods g ON o.goods_id = g.goods_id WHERE o.order_type = 4 GROUP BY	g.goods_id ORDER BY	g.sales DESC";
		}
		$res = M()->query($sql);
		$this->assign('list',$res);
		$this->assign('type',I('get.type'));
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
	
	public function userTop(){
		$sql = "select count(a.user_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.mobile,b.email from __PREFIX__order as a left join __PREFIX__users as b ";
		$sql .= "  on a.user_id = b.user_id where a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 order by amount DESC limit 100";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}


	public function saleList(){
		$begin = I('begin');
		$end = I('end');
		session('begin',$begin);
		session('ends',$end);
		session('id_on_store',I('store_id'));
		$this->display();
	}

	public function ajaxsaleList()
	{
		I('store_name') ? $store_name = I('store_name') : false;
		$id = $_SESSION['id_on_store'];
		$begin = strtotime($_SESSION['begin']);
		$end = strtotime($_SESSION['ends']);
		if(!empty($store_name) || !empty($id))
		{
			if(!empty($store_name))
			{
				$store_id = M('merchant')->where("`store_name` = '$store_name'")->getField('id');
			}else{
				$store_id = $id;
			}
			$count =M('order')->alias('o')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where("o.add_time>$begin and o.add_time<$end and o.store_id=".$store_id)
				->field('o.*,g.gooda_name')
				->count();

			$Page  = new AjaxPage($count,20);
			$show = $Page->show();

			$order_List = M('order')->alias('o')
				->join('INNER JOIN tp_goods g on o.goods_id = g.goods_id')
				->where("o.add_time>$begin and o.add_time<$end and o.store_id=".$store_id)
				->field('o.*,g.goods_name')
				->order('o.order_id desc')
				->limit($Page->firstRow,$Page->listRows)
				->select();

			$this->assign('list',$order_List);
			$this->assign('page',$show);// 赋值分页输出
		}else{
			$count =M('order')
				->where('add_time<'.$end.' and add_time>'.$begin)
				->count();

			$Page  = new AjaxPage($count,20);
			$show = $Page->show();

			$order_List = M('order')
				->where('add_time<'.$end.' and add_time>'.$begin)
				->order('order_id desc')
				->limit($Page->firstRow,$Page->listRows)
				->select();
			$where['goods_id'] = array('IN',array_column($order_List,'goods_id'));
			$goods_name = M('goods')->where($where)->field('goods_id,goods_name')->select();
			for($i=0;$i<count($order_List);$i++)			{
				for($j=0;$j<count($goods_name);$j++){
					if($order_List[$i]['goods_id']==$goods_name[$j]['goods_id']){
						$order_List[$i]['goods_name'] = $goods_name[$j]['goods_name'];
						continue ;
					}
				}
			}

			$this->assign('list',$order_List);
			$this->assign('page',$show);// 赋值分页输出
		}

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
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where reg_time>$this->begin and reg_time<$this->end group by gap";
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

		$condition = "";
		if(I('store_name'))
		{
			$store_id = M('merchant')->where("store_name='".I('store_name')."'")->find();
			$condition  =  ' and a.store_id='.$store_id['id'];
			$this->assign('store_id',$store_id);
			$this->assign('store_name',I('store_name'));
		}

		//统计各种参数
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$nextmonth = strtotime(date('Y-m-01',strtotime('+1 month')));
		$data['ri'] = M('order')->where('store_id='.$store_id['id'].' and add_time>'.$today.' and add_time<'.($today+24*3600).' and  pay_status=1')->sum('order_amount');//日销售总额
		$data['zong'] = M('order')->where('store_id='.$store_id['id'].' and pay_status=1')->sum('order_amount');//总销售额
		$data['ding'] = M('order')->where('store_id='.$store_id['id'].' and add_time>'.$today.' and add_time<'.($today+24*3600).' and  pay_status=1')->count();//日订单数
		$data['cancel'] = M('order')->where('store_id='.$store_id['id'].' and add_time>'.$today.' and add_time<'.($today+24*3600).' and  is_cancel=1')->count();//日取消订单数
		$data['month'] = M('order')->where('store_id='.$store_id['id'].' and add_time>'.$month.' and add_time<'.$nextmonth.' and  (order_type=4 or order_type = 19)')->sum('order_amount');//月销售额
		$data['ti'] = M('store_withdrawal')->where('store_id='.$store_id['id'].' and status=1')->sum('withdrawal_money');//提现金额
		$data['ti']>0 && $data['ti']=$this->operationPrice($data['ti']);

        //拿到总共能体现的资金
		$one = M('order')->where('order_type in (4,16) and confirm_time is not null and store_id='.$store_id['id'])->select();
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
		$withdrawal_total = M('store_withdrawal')->where(array('store_id'=>$store_id['id'],'status'=>1))->field('withdrawal_money')->select();

		foreach($withdrawal_total as $v)
		{
			$total = $total+$v['withdrawal_money'];
		}
		$data['reflect'] = $reflect-$total;
		if(empty($data['reflect']))
			$data['reflect'] = 0;
		$data['keti']>0&& $data['keti']=$this->operationPrice($data['keti']);
		$data['tuikuan'] = M('return_goods')->where('store_id='.$store_id['id'].' and status=3')->sum('gold');

		$this->assign('data',$data);

		$sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order ";
		$sql .= "where store_id=".$store_id['id']." and add_time>=$this->begin and add_time<$this->end AND pay_status=1  group by gap";
		$res = M()->query($sql);//订单数,交易额

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

	//操作价格
	public function operationPrice($price)
	{
		$price = sprintf('%.2f', $price);
		$fix = floatval(pow(10, strlen(explode('.', strval($price))[1])));
		$price = ($price*$fix)/$fix;
		return $price;
	}

	//百度统计
	public function baidu()
	{
		echo ("<script> window.location.href='http://tongji.baidu.com/web/welcome/login'; </script>") ;
	}

	public function select()
	{

		$this->display();
	}
}