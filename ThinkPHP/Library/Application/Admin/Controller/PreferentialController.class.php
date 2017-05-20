<?php
namespace Admin\Controller;
use Api\Controller\BaseController;
use Think\AjaxPage;

class PreferentialController extends BaseController {

	public function Goodsindex()
	{
		$exclusive = M('exclusive')->select();
		$this->assign('exclusive',$exclusive);
		$this->display();
	}

	public function ajaxindex()
	{
		$where = 'show_type=0 and is_special=4 and is_audit=1';
		I('exclusive') && $where = $where.' and exclusive_cat='.I('exclusive');
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}
		$count = M('goods')->where($where)->count();
		$Page = new AjaxPage($count, 20);
		foreach ($where as $key => $val) {
			$Page->parameter[$key] = urlencode($val);
		}
		$show = $Page->show();
		//获取订单列表
		$goods = $this->getGoodsList($where, $Page->firstRow, $Page->listRows);
		for ($i = 0; $i < count($goods); $i++) {
			$name = M('merchant')->where('`id`=' . $goods[$i]['store_id'])->field('store_name')->find();
			$cat_name = M('goods_category')->where('`id`=' . $goods[$i]['cat_id'])->field('name')->find();
			$exclusive_name = M('exclusive')->where('`id`='.$goods[$i]['exclusive_cat'])->field('name')->find();
			$goods[$i]['exclusive_name'] = $exclusive_name['name'];
			$goods[$i]['cat_name'] = $cat_name['name'];
			$goods[$i]['store_name'] = $name['store_name'];
		}

		$this->assign('goods', $goods);
		$this->assign('page', $show);// 赋值分页输出
		$this->display();
	}
}