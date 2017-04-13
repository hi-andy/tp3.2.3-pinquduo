<?php
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Api\Controller\BaseController;
use Think\AjaxPage;

class JiujiuController extends BaseController{

	public function SpeciallyList()
	{
		$this->show();
	}

	public function ajaxSpeciallyList()
	{
		$exclusive = M('exclusive');
		$count = $exclusive->count();
		$Page = new AjaxPage($count, 10);
		$show = $Page->show();
		$SpeciallyList = $exclusive->order('id desc')->limit($Page->firstRow,$Page->listRows)->select();

		$this->assign('page',$show);
		$this->assign('SpeciallyList',$SpeciallyList);
		$this->display();
	}

	public function addSpecially()
	{
		if($_POST) {
			$data['name'] = $_POST['name'];
			$data['Introduction'] = $_POST['introduction'];
			$data['img'] = $_POST['image'];
			$data['banner'] = $_POST['banner'];
			$res = M('exclusive')->data($data)->add();
			if ($res) {
				$return_arr = array(
					'status' => 1,
					'msg' => '添加成功',
					'data' => array('url' => U('Admin/Jiujiu/SpeciallyList')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			} else {
				$return_arr = array(
					'status' => -1,
					'msg' => '添加失败',
					'data' => array('url' => U('Admin/Jiujiu/addSpecially')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}
		$this->display();
	}

	public function EditSpecially()
	{
		$id = $_GET['id'];
		if(!empty($id))
		{
			$exclusive = M('exclusive')->where('`id`='.$id)->find();
			$this->assign('exclusive',$exclusive);
		}

		if($_POST['type']>0)
		{
			$data['name'] = $_POST['name'];
			$data['Introduction'] = $_POST['introduction'];
			$data['img'] = $_POST['image'];
			$data['banner'] = $_POST['banner'];
			$res = M('exclusive')->where('`id`='.$_POST['id'])->data($data)->save();
			if($res)
			{
				$return_arr = array(
					'status' => 1,
					'msg'   => '修改成功',
					'data'  => array('url'=>U('Admin/Jiujiu/SpeciallyList')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}else{
				$return_arr = array(
					'status' => -1,
					'msg'   => '修改失败',
					'data'  => array('url'=>U('Admin/Jiujiu/EditSpecially')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		$this->display();
	}

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

	public function getGoodsList($condition, $start = 0, $page_size = 20)
	{
		$res = M('goods')->where($condition)->order('goods_id desc')->limit("$start,$page_size")->select();
		return $res;
	}
	/**
	 * 添加修改商品
	 */
	public function addEditGoods()
	{
		$GoodsLogic = new GoodsLogic();
		$Goods = D('Goods'); //
		$type = $_POST['goods_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
		//ajax提交验证
		if(($_GET['is_ajax'] == 1) && IS_POST)
		{
			C('TOKEN_ON',false);
			if(!$Goods->create(NULL,$type))// 根据表单提交的POST数据创建数据对象
			{
				//  编辑
				$return_arr = array(
					'status' => -1,
					'msg'   => '操作失败',
					'data'  => $Goods->getError(),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}else {
				//  form表单提交
				// C('TOKEN_ON',true);
				$Goods->on_time = time(); // 上架时间
				//$Goods->cat_id = $_POST['cat_id_1'];
				$_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
				$_POST['cat_id_3'] && ($Goods->cat_id = $_POST['cat_id_3']);
				session('goods',$_POST);

				if ($type == 2)
				{
					$goods_id = $_POST['goods_id'];
					$goods = M('goods')->where("goods_id = $goods_id")->find();
					if($_POST['original_img']!=$goods['original_img'])
					{
						$link =  C('DATA_URL').goods_thum_images($_POST['goods_id'],400,400);
						$res = unlink($link);
						$link1 = C('DATA_URL').$goods['original_img'];
						$res1 = unlink($link1);
					}
					$Goods->save(); // 写入数据到数据库
					$Goods->afterSave($goods_id);
				}
				else
				{
					$goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
					$Goods->afterSave($goods_id);
				}

				$GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性

				$return_arr = array(
					'status' => 1,
					'msg'   => '操作成功',
					'data'  => array('url'=>U('Admin/Jiujiu/Goodsindex')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		$goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
//		$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
		$level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
		$cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
//		$brandList = $GoodsLogic->getSortBrands();
		$exclusive = M('exclusive')->select();
		$merchantList = $GoodsLogic->getSortMerchant();
		$goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
		if(empty($goodsType))
			$goodsType = M("GoodsType")->select();
		$this->assign('level_cat',$level_cat);
		$this->assign('cat_list',$cat_list);
		$this->assign('exclusive',$exclusive);
		$this->assign('merchantList',$merchantList);
		$this->assign('goodsType',$goodsType);
		$this->assign('goodsInfo',$goodsInfo);  // 商品详情
		$goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
		$this->assign('goodsImages',$goodsImages);  // 商品相册
		$this->initEditor(); // 编辑器
		$this->display('_goods');
	}

	/**
	 * 初始化编辑器链接
	 * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
	 */
	private function initEditor()
	{
		$this->assign("URL_upload", U('Admin/Ueditor/imageUp', array('savepath' => 'goods'))); // 图片上传目录
		$this->assign("URL_imageUp", U('Admin/Ueditor/imageUp', array('savepath' => 'article'))); //  不知道啥图片
		$this->assign("URL_fileUp", U('Admin/Ueditor/fileUp', array('savepath' => 'article'))); // 文件上传s
		$this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp', array('savepath' => 'article')));  //  图片流
		$this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage', array('savepath' => 'article'))); // 远程图片管理
		$this->assign("URL_imageManager", U('Admin/Ueditor/imageManager', array('savepath' => 'article'))); // 图片管理
		$this->assign("URL_getMovie", U('Admin/Ueditor/getMovie', array('savepath' => 'article'))); // 视频上传
		$this->assign("URL_Home", "");
	}

	function delSpecial()
	{
		$id = I('id');
		$count = M('goods')->where('`is_special`=4 and `exclusive_cat`='.$id)->count();

		if (!empty($count)) {
			$return_arr = array('status' => -1, 'msg' => '该专场下还有分类商品，不能删除!', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
			$this->ajaxReturn(json_encode($return_arr));
		}
		M('exclusive')->where('`id`=' . $id)->delete();
		$return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
		$this->ajaxReturn(json_encode($return_arr));
	}

	public function jiujiu_info()
	{
		$exclusive = M('exclusive')->select();
		$this->assign('exclusive',$exclusive);
		$this->display();
	}

	public function search_goods(){
		$store_name = I('store_name');
		$this->assign('store_name', $store_name);
		$goods_id = I('goods_id');
		$where = ' is_on_sale=1 and is_special=0 and the_raise=0';//搜索条件
		if (!empty($goods_id)) {
			$where .= " and goods_id not in ($goods_id) ";
		}
		if (!empty($_REQUEST['keywords'])) {
			$this->assign('keywords', I('keywords'));
			$where = "$where and (goods_name like '%" . I('keywords'). "%')";
		}
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}
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

	function getSortCategory()
	{
		$categoryList = M("GoodsCategory")->where('`is_show`=1')->getField('id,name,parent_id,level');
		$nameList = array();
		foreach ($categoryList as $k => $v) {

			//$str_pad = str_pad('',($v[level] * 5),'-',STR_PAD_LEFT);
			$name = getFirstCharter($v['name']) . ' ' . $v['name']; // 前面加上拼音首字母
			//$name = getFirstCharter($v['name']) .' '. $v['name'].' '.$v['level']; // 前面加上拼音首字母
			/*
			// 找他老爸
			$parent_id = $v['parent_id'];
			if($parent_id)
				$name .= '--'.$categoryList[$parent_id]['name'];
			// 找他 爷爷
			$parent_id = $categoryList[$v['parent_id']]['parent_id'];
			if($parent_id)
				$name .= '--'.$categoryList[$parent_id]['name'];
			*/
			$nameList[] = $v['name'] = $name;
			$categoryList[$k] = $v;
		}
		array_multisort($nameList, SORT_STRING, SORT_ASC, $categoryList);

		return $categoryList;
	}

	public function goods_save()
	{
		$data['exclusive_cat'] = $_POST['exclusive'];
		$data['is_special'] = 4;
		for($i=0;$i<count($_POST['goods_id']);$i++)
		{
			$res = M('goods')->where('`goods_id`='.$_POST['goods_id'][$i])->data($data)->save();
		}
		if($res)
		{
			$this->success("添加成功",U('Jiujiu/Goodsindex'));
		}else{
			$this->success("添加失败",U('Jiujiu/jiujiu_info'));
		}
	}

	public function delete_goods()
	{
		$id =I('id');
		$is_show = M('goods')->where('`goods_id`='.$id)->find();
		if (empty($is_show)) {
			$return_arr = array(
				'status' => -1,
				'msg' => '该商品已被删除',
				'data' => array('url' => U('Admin/Jiujiu/Goodsindex')),
			);
			$this->ajaxReturn(json_encode($return_arr));
		}
//		// 判断此商品是否有订单
//		$goods_count = M('OrderGoods')->where("goods_id = {$_GET['id']} and `is_pay`=1 and `is_cancel`=0")->count('1');
//		if (!empty($goods_count)) {
//			$return_arr = array(
//				'status' => -1,
//				'msg' => '该商品已被删除',
//				'data' => array('url' => U('Admin/Jiujiu/Goodsindex')),
//			);
//			$this->ajaxReturn(json_encode($return_arr));
//		}
//		M('goods')->where('`goods_id`='.$id)->delete();
//		M('goods_images')->where('`goods_id`='.$id)->delete();
//		M('spec_goods_price')->where('`goods_id`='.$id)->delete();
//		$res = M('spec_image')->where('`goods_id`='.$id)->delete();
//		if($res)
//		{
//			$return_arr = array(
//				'status' => 1,
//				'msg' => '删除成功',
//				'data' => array('url' => U('Admin/Jiujiu/Goodsindex')),
//			);
//			$this->ajaxReturn(json_encode($return_arr));
//		}
		// 删除此商品
		M("Goods")->where('goods_id =' . $id)->save(array('show_type'=>1));
		$return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
		$this->ajaxReturn(json_encode($return_arr));
	}
}