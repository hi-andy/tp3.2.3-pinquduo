<?php
namespace Store\Controller;
use Store\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class TraceabilityController extends BaseController{

	/*
	 * 商品追溯列表
	 */
	public function trace_list(){

		$this->display();
	}


	/**
	 * 添加商品追溯列表
	 */
	public function trace_add(){

		echo "添加商品追溯";

	}

	/**
	 * 编辑商品追溯
	 */
	public function trace_edit(){
		echo "编辑商品追溯";
	}

	/**
	 * 更新商品追溯
	 */
	public function update(){
		echo "更新商品追溯";
	}

	public function trace_delete(){
		echo "删除商品追溯";
	}

	/**
	 *  商品列表
	 */
	public function ajaxGoodsList(){

		$where = ' 1 = 1 '; // 搜索条件
		I('intro')    && $where = "$where and ".I('intro')." = 1" ;
		I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
		(I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
		$cat_id = I('cat_id');
		// 关键词搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if($key_word)
		{
			$where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
		}

		if($cat_id > 0)
		{
			$grandson_ids = getCatGrandson($cat_id);
			$where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
		}
		$model = M('Goods');
		$count = $model->where($where)->count();
		$Page  = new AjaxPage($count,10);
		/**  搜索条件下 分页赋值
		foreach($condition as $key=>$val) {
		$Page->parameter[$key]   =   urlencode($val);
		}
		 */
		$show = $Page->show();
		$order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
		$goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

		$catList = D('goods_category')->select();
		$catList = convert_arr_key($catList, 'id');
		$this->assign('catList',$catList);
		$this->assign('goodsList',$goodsList);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}

}