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
namespace Admin\Controller;
use Think\Controller;
class ActivityController extends Controller {
	function snap_up_list()
	{
		$times[0]['time'] = '10:00';
		$times[0]['choose'] = 0;
		$times[1]['time'] = '12:00';
		$times[1]['choose'] = 0;
		$times[2]['time'] = '16:00';
		$times[2]['choose'] = 0;
		$times[3]['time'] = '20:00';
		$times[3]['choose'] = 0;



		$this->assign('time',$times);
		$this->display();
	}

	function ajax_snap_up_list()
	{
		$goods = M('goods')->where('is_special=7')->select();
		$this->assign('goods',$goods);
		$this->display();
	}

	public function prom_goods_save2()
	{
		$date = I('date');
		$time = I('time');
		$all_time = $date . ' ' . $time;
		$data['on_time'] = strtotime($all_time);
		$data['is_special'] = 2;
		for($i=0;$i<count($_POST['goods_id']);$i++)
		{
			$res = M('goods')->where('`goods_id`='.$_POST['goods_id'][$i])->data($data)->save();
		}
		if($res)
		{
			$this->success("添加成功",U('Secondskill/Seconds_kill_goods'));
		}else{
			$this->success("添加失败",U('Secondskill/Seconds_kill_info'));
		}
	}

	public function search_goods()
	{
		$where = ' is_on_sale = 1 and is_special=0 and the_raise=0 and show_type=0';//搜索条件
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}
		$goods_id = I('goods_id');
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
			$where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
		}
		I('store_id') && $where = "$where and `store_id`=".I('store_id');
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

	public function Seconds_kill_info()
	{
		$times[0]['time'] = '10:00';
		$times[0]['choose'] = 0;
		$times[1]['time'] = '12:00';
		$times[1]['choose'] = 0;
		$times[2]['time'] = '16:00';
		$times[2]['choose'] = 0;
		$times[3]['time'] = '20:00';
		$times[3]['choose'] = 0;
		$this->assign('time',$times);
		$store = M('merchant')->where('`is_show`=1')->field('id,store_name')->select();
		$this->assign('store', $store);
		$this->display();
	}
}