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
namespace Api_2_0_0\Controller;

use Think\AjaxPage;
use Think\Controller;
class SecondBuyController extends Controller {

    public function _initialize() {
        $this->encryption();
    }

	public function index()
	{
        $times = C('SecondBuy')['times']; //抢购时间段
		$currentTime    = 0;   //当前抢购时间
		for ($i=0; $i<count($times); $i++) {
		    $startTime = strtotime(date('Y-m-d').$times[$i]);
		    if (time() > $startTime && time() < $startTime + 3600 * 3) {
                $currentTime = intval($times[$i]);
                //echo $times[$i];exit;
		        $times[$i] = array('time'=>$times[$i]);
                $times[$i]['notice'] = '正在抢';
            } elseif (time() < $startTime) {
                $times[$i] = array('time'=>$times[$i]);
                $times[$i]['notice'] = '未开抢';
            } else {
                $times[$i] = array('time'=>$times[$i]);
                $times[$i]['notice'] = '已开抢';
            }
        }

        $getTime = intval(I('get.start'));
        $startTime = $getTime ? $getTime : $currentTime;
        $where = ' start_date=' . strtotime(date('Y-m-d')) . ' AND start_time=' . $startTime;
        //$where = ' start_date=' . strtotime('2017-04-20') . ' AND start_time=' . $startTime;
        $count = M('goods_activity')->where($where)->count();

        $sql = 'SELECT ga.id,ga.start_time,ga.status,g.goods_id,g.goods_name,g.shop_price,g.prom_price,g.original_img,c.name cat_name,m.id store_id FROM tp_goods_activity ga 
                LEFT JOIN tp_goods g ON g.goods_id=ga.goods_id
                LEFT JOIN tp_goods_category c ON g.cat_id=c.id
                LEFT JOIN tp_merchant m ON g.store_id=m.id 
                WHERE ' . $where . ' LIMIT 6';
        $goodsList = M()->query($sql);

        // 时间未到不可购买
        $notBuy = 0;
        $buyTime = strtotime(date('Y-m-d ').$startTime.':00');
        if ($buyTime > time()) {
            $notBuy = 1;
        }

        $this->assign('current',$startTime.':00');
        $this->assign('lists', $goodsList);// 赋值分页输出
        $this->assign('pages', $count);// 赋值分页输出
        $this->assign('time',$times);
        $this->assign('notBuy',$notBuy);
		$this->display();
	}

	/**
     * ajax 返回秒杀商品列表
     */
    public function ajaxGetList ()
    {
        $startTime = intval(I('get.start'));
        $where = ' start_date=' . strtotime(date('Y-m-d')) . ' AND start_time=' . $startTime;
        //$where = ' start_date=' . strtotime('2017-04-20') . ' AND start_time=' . $startTime; //测试代码

        $count = M('goods_activity')->where($where)->count();
        $pages = 6;
        $Page = new AjaxPage($count, $pages, $_GET['p']);

        $sql = 'SELECT ga.id,ga.start_time,ga.status,g.goods_id,g.goods_name,g.shop_price,g.prom_price,g.original_img,c.name cat_name,m.id store_id FROM tp_goods_activity ga 
                LEFT JOIN tp_goods g ON g.goods_id=ga.goods_id
                LEFT JOIN tp_goods_category c ON g.cat_id=c.id
                LEFT JOIN tp_merchant m ON g.store_id=m.id 
                WHERE ' . $where . ' LIMIT ' .$Page->firstRow.','.$Page->listRows;
        $goodsList = M()->query($sql);

        // 时间未到不可购买
        $notBuy = 0;
        $buyTime = strtotime(date('Y-m-d ').$startTime.':00');
        if ($buyTime > time()) {
            $notBuy = 1;
        }

        $this->assign('lists', $goodsList);// 赋值分页输出
        $this->assign('pages', $count);// 赋值分页输出
        $this->assign('notBuy',$notBuy);
        $this->display('load_more');
    }
}
