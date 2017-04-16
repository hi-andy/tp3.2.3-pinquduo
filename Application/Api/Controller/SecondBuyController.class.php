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

use Think\AjaxPage;
use Think\Controller;
class SecondBuyController extends Controller {
	public function index()
	{
		$times          = array('10:00', '12:00', '16:00', '20:00'); //抢购时间段
		$currentTime    = 0;   //当前抢购时间
		for ($i=0; $i<count($times); $i++) {
		    $startTime = strtotime(date('Y-m-d',time()).$times[$i]);
		    if (time() > $startTime && time() < $startTime + 7200) {
                $currentTime = intval($times[$i]);
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

        $count = M('goods_promotion')->where($where)->count();
        $Page = new AjaxPage($count, 20);
        $show = $Page->show();

        $sql = 'SELECT gp.id,gp.start_time,g.goods_name,g.shop_price,g.prom_price,c.name cat_name,m.store_name FROM tp_goods_promotion gp 
                LEFT JOIN tp_goods g ON g.goods_id=gp.goods_id
                LEFT JOIN tp_goods_category c ON g.cat_id=c.id
                LEFT JOIN tp_merchant m ON g.store_id=m.id 
                WHERE ' . $where . ' LIMIT ' .$Page->firstRow.','.$Page->listRows;
        $goodsList = M()->query($sql);
        print_r($goodsList);

        $this->assign('current',$startTime.':00');
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('time',$times);
		$this->display();
	}

	/**
     * ajax 返回秒杀商品列表
     */
    public function ajaxGetList ()
    {
        $filter = I('get.');
        $count = M('goods_promotion')->where($filter)->count();
        $pageNumber = 3;
        $Page  = new \Think\Page($count, $pageNumber, $filter);

print_r($filter);

        $this->assign('pages', $filter['p']);
        $this->assign('list', $article_list);
        $this->assign('type', $filter['type']);
        $this->display('load_more');
    }
}

// TODO 在 goods_promotion 表加入 status 字段，控制某一商品的当前状态（是否可购买），正常可用：1 ，不可用为：0 今日限定的商品数量已抢光