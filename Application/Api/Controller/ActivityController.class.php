<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace Api\Controller;
use Think\Controller;
class ActivityController extends Controller {
	function index()
	{
		//控制时间的显示问题
		//choose => 0未开始 1=>正在抢 2=>已开抢
		$times[0]['time'] = '10:00';
		$times[0]['choose'] = 0;
		$times[1]['time'] = '12:00';
		$times[1]['choose'] = 0;
		$times[2]['time'] = '16:00';
		$times[2]['choose'] = 0;
		$times[3]['time'] = '20:00';
		$times[3]['choose'] = 0;
		$times[4]['time'] = '24:00';

<<<<<<< HEAD
=======
		

>>>>>>> 0b7f13d20f77f1260095c707f48567c3375029f4
		for($i=0;$i<4;$i++)
		{
			$time = strtotime(date('Y-m-d',time()).$times[$i]['time']);
			$time2 = strtotime(date('Y-m-d',time()).$times[$i+1]['time']);
			$time3 = strtotime(date('Y-m-d',time()).$times[$i-1]['time']);
			if($time<time() && time()<$time2 && $time>$time3){//正在抢
				$times[$i]['choose'] = 1;
				$times[$i]['choose_name'] = '正在抢';
			}else if(time()<$time && $time<$time2 && $time>$time3){//未开抢
				$times[$i]['choose'] = 0;
				$times[$i]['choose_name'] = '未开抢';
			}else{//已开抢
				$times[$i]['choose'] = 2;
				$times[$i]['choose_name'] = '已开抢';
			}
		}
		unset($times[4]);
		$this->assign('time',$times);

		$goodsList = D('goods')->field('market_price')->where('is_special=7')->select();
//print_r($goodsList);

		$this->display();
	}
}