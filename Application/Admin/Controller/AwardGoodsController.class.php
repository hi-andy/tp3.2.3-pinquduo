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
use Think\AjaxPage;
class AwardGoodsController extends Controller {

    // 商品列表
    public function index()
    {
        $this->display();
    }

    // 选择商品页
    public function selectGoods()
    {
        $store = M('merchant')->where('`is_show`=1')->field('id,store_name')->select();
        $this->assign('store', $store);
        $this->display();
    }

    // 保存商品
    public function save()
    {
        $data['type'] = 3; // 抽奖商品
        $goods = I('post.goods')['goods'];
        //print_r($goods);exit;
        foreach ($goods as $value) {
            // 添加到商品活动表
            $data = array_merge($data, $value);
            $res = M('goods_activity')->data($data)->add();
        }
        $this->success("添加成功",U('AwardGoods/goodsList'));
    }

    // ajax 返回商品列表数据
    public function ajaxindex()
    {
        $where = 'WHERE ga.type=3';
        if($store_name = I('store_name')) {
            $this->assign('store_name', I('store_name'));
            $store_id = M('merchant')->where("`store_name` like '%".$store_name."%'")->getField('id');
            $where .= ' AND g.store_id='.$store_id;
        }

        $sqlCount = 'SELECT COUNT(*) count FROM tp_goods_activity ga LEFT JOIN tp_goods g ON g.goods_id=ga.goods_id  LEFT JOIN tp_merchant m ON g.store_id=m.id '.$where;
        $count = M()->query($sqlCount)[0]['count'];
        $Page = new AjaxPage($count, 20);
        $show = $Page->show();

        $sql = 'SELECT ga.id,ga.start_date,ga.start_time,g.goods_id,g.goods_name,g.shop_price,g.prom_price,gc.name cat_name,m.store_name FROM tp_goods_activity ga 
                LEFT JOIN tp_goods g ON g.goods_id=ga.goods_id
                LEFT JOIN tp_goods_category gc ON g.cat_id=gc.id
                LEFT JOIN tp_merchant m ON g.store_id=m.id '.$where.' LIMIT ' .$Page->firstRow.','.$Page->listRows;
        $goodsList = M()->query($sql);

        foreach ($goodsList as $key => $value) {
            $goodsList[$key]['start_time'] = date('Y-m-d', $value['start_date']) . ' ' . $value['start_time'] . ':00';
            $goodsList[$key]['end_time'] = empty($value['end_time']) ? date('Y-m-d ',$value['start_date']) . ' 00:00' : date('Y-m-d ', $value['start_date']) . $value['start_time'] . ':00';
        }

        $this->assign('goods', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }

    // 搜索商品，以添加
    public function search_goods()
    {
        $where = ' store_count>0 and is_on_sale = 1 and is_special=0 and the_raise=0 and show_type=0';//搜索条件
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

    // 删除单个商品
    public function delete()
    {
        // 判断此商品是否有订单
        $goods_count = M('goods_activity')->where("id = {$_GET['id']}")->find();
        if($goods_count)
        {
            // 删除此商品
            M("Goods_activity")->where('id =' . $_GET['id'])->delete();
            $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }
        $return_arr = array('status' => -1,'msg' => '该商品已删除','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    // 批量删除商品
    public function deleteBatch()
    {
        $data = I('post.');
        foreach ($data['id'] as $value) {
            $res = M('goods_activity')->where('id='.$value)->delete();
        }
        if($res)
        {
            $this->success("删除成功",U('AwardGoods/goodsList'));
        }else{
            $this->success("删除失败",U('AwardGoods/goodsList'));
        }
    }
}