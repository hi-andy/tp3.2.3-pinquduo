<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/6/23
 * Time: 10:55
 */
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;
class ActivityiconController extends BaseController{

	function index(){

		$this->display();
	}

	public function ajaxindex()
	{

		$this->assign('icon',C('activity_icon'));
		$where = 'g.show_type=0 and g.is_audit=1 and g.is_show=1 and g.is_on_sale=1';
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$store_id = M('merchant')->where("`store_name` like '%".I('store_name')."%'")->getField('id');
			$where = "$where and g.store_id = $store_id";
		}
		if(!empty(I('icon_id')))
		{
			$icon_id = I('icon_id');
			$where = "$where and pi.type = $icon_id";
		}
		$count = M('promote_icon')->alias('pi')
			->join('INNER JOIN tp_goods g on g.goods_id = pi.goods_id ')
			->where($where)
			->count();
		$Page = new AjaxPage($count, 15);
		foreach ($where as $key => $val) {
			$Page->parameter[$key] = urlencode($val);
		}
		$show = $Page->show();
		//获取订单列表
		$goods = M('promote_icon')->alias('pi')
			->join('INNER JOIN tp_goods g on g.goods_id = pi.goods_id ')
			->where($where)
			->limit($Page->firstRow,$Page->listRows)
			->field('g.goods_id,g.goods_name,g.shop_price,g.prom_price,pi.type,g.store_id,g.cat_id,g.is_on_sale,g.is_show')
			->select();
		for ($i = 0; $i < count($goods); $i++) {
			$name = M('merchant')->where('`id`=' . $goods[$i]['store_id'])->field('store_name')->find();
			$cat_name = M('goods_category')->where('`id`=' . $goods[$i]['cat_id'])->field('name')->find();
			$goods[$i]['cat_name'] = $cat_name['name'];
			$goods[$i]['store_name'] = $name['store_name'];
		}

		$this->assign('goods', $goods);
		$this->assign('page', $show);// 赋值分页输出
		$this->display();
	}

	function goods_info(){

		$this->display();
	}

	public function search_goods(){
        $where = ' store_count>0 and is_on_sale = 1 and is_special=0 and the_raise=0 and show_type=0 and goodstatus=2';//搜索条件
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

	public function goods_save()
	{
		$src = C('activity_src');
		for($i=0;$i<count($_POST['goods_id']);$i++){
			$res = M('promote_icon')->data(array('goods_id'=>$_POST['goods_id'][$i],'type'=>$_POST['icon_id'],'src'=>$src[$_POST['icon_id']],'create_time'=>time()))->add();
			redislist("goods_refresh_id", $_POST['goods_id'][$i]);
		}
		if($res){
			$this->success("添加成功",U('Activityicon/index'));
		}else{
			$this->success("添加失败",U('Activityicon/goods_info'));
		}
	}

	public function delete_goods()
	{
		$id =I('id');
		$is_show = M('promote_icon')->where('`goods_id`='.$id)->find();
		if (empty($is_show)) {
			$return_arr = array(
				'status' => -1,
				'msg' => '水印已移除',
				'data' => array('url' => U('Admin/Activityicon/index')),
			);
			$this->ajaxReturn(json_encode($return_arr));
		}
		// 删除此商品
		M("promote_icon")->where('goods_id =' . $id)->delete();
		$return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);
		$this->ajaxReturn(json_encode($return_arr));
	}
}