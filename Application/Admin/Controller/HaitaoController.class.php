<?php
namespace Admin\Controller;
use Admin\Logic\HaitaoLogic;
use Api\Controller\BaseController;
use Think\AjaxPage;


class HaitaoController extends BaseController{

	public function categoryList()
	{
		$HaitaoLogic = new HaitaoLogic();
		$cat_list = $HaitaoLogic->goods_cat_list();
		$this->assign('cat_list', $cat_list);
//		var_dump($cat_list);
		$this->display();
	}

	public function addEditCategory()
	{
		$HaitaoLogic = new HaitaoLogic();
		if (IS_GET) {
			$goods_category_info = M('haitao')->where('id=' . I('GET.id', 0))->find();
			$level_cat = $HaitaoLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框
			$cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单

			$this->assign('level_cat', $level_cat);
			$this->assign('cat_list', $cat_list);
			$this->assign('goods_category_info', $goods_category_info);
			$this->display('_category');
			exit;
		}
			$_POST['level']=1;
		if($_POST['parent_id_1']!=0)
			$_POST['level']=2;
		$_POST['img'] = $_POST['image'];
		$GoodsCategory = D('haitao'); //
		$type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
		//ajax提交验证
		if ($_GET['is_ajax'] == 1) {
			C('TOKEN_ON', false);

			if (!$GoodsCategory->create(NULL, $type))// 根据表单提交的POST数据创建数据对象
			{
				//  编辑
				$return_arr = array(
					'status' => -1,
					'msg' => '操作失败!',
					'data' => $GoodsCategory->getError(),
				);
				$this->ajaxReturn(json_encode($return_arr));
			} else {
				//  form表单提交
				C('TOKEN_ON', true);

				$GoodsCategory->parent_id = $_POST['parent_id_1'];
				$_POST['parent_id_2'] && ($GoodsCategory->parent_id = $_POST['parent_id_2']);

				if ($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id) {
					//  编辑
					$return_arr = array(
						'status' => -1,
						'msg' => '上级分类不能为自己',
						'data' => '',
					);
					$this->ajaxReturn(json_encode($return_arr));
				}
				if ($type == 2) {
					$GoodsCategory->save(); // 写入数据到数据库
					$HaitaoLogic->refresh_cat($_POST['id']);
				} else {
					$insert_id = $GoodsCategory->add(); // 写入数据到数据库
					$HaitaoLogic->refresh_cat($insert_id);
				}
				$return_arr = array(
					'status' => 1,
					'msg' => '操作成功',
					'data' => array('url' => U('Admin/Haitao/categoryList')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}
	}

	/**
	 *  商品列表
	 */
	public function goodsList()
	{
		$HaitaoLogic = new HaitaoLogic();
//		$brandList = $HaitaoLogic->getSortBrands();
		$categoryList = $HaitaoLogic->getSortCategory();
		$merchantList = $HaitaoLogic->getSortMerchant();
		$this->assign('categoryList', $categoryList);
//		var_dump($categoryList);
//		$this->assign('brandList', $brandList);
		$this->assign('merchantList', $merchantList);
		$this->display();
	}

	/**
	 *  商品列表
	 */
	public function ajaxGoodsList()
	{

		$where = 'show_type=0 and`is_special`=1 and `the_raise`=0 and is_audit=1'; // 搜索条件
		I('intro')    && $where = "$where and ".I('intro')." = 1" ;
		(I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}
		$cat_id = I('haitao_cat');
		// 关键词搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if($key_word)
		{
			$where = "$where and (goods_name like '%$key_word%')" ;
		}
			if($cat_id > 0)
		{
			$grandson_ids = getCatGrandson($cat_id);
			$where .= " and haitao_cat in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
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
		$goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)
			->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')
			->field('tp_goods.*,tp_merchant.id,tp_merchant.store_name')
			->select();

		$catList = D('haitao')->select();
		$catList = convert_arr_key($catList, 'id');
		$this->assign('catList',$catList);
		$this->assign('goodsList',$goodsList);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}

	/**
	 * 海淘添加修改商品
	 */
	public function addEditGoods()
	{
		$HaitaoLogic = new HaitaoLogic();
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
				$_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
				session('goods',$_POST);

				if ($type == 2)
				{
					$goods_id = $_POST['goods_id'];
					$_POST['refresh'] = 0;
					$rdsname = "getDetaile".$goods_id."*";
					redisdelall($rdsname);//删除商品详情缓存
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
					$rdsname = "getDetaile".$goods_id."*";
					redisdelall($rdsname);//删除商品详情缓存
				}
				else
				{
					$goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
					$Goods->afterSave($goods_id);

				}

//				$HaitaoLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
//				M('goods')->where('`goods_id`='.$goods_id)->save(array('cat_id'=>0,'haitao_cat'=>$_POST['cat_id_2']));
				$return_arr = array(
					'status' => 1,
					'msg'   => '操作成功',
				);
				if(I('is_special')==1)
				{
					$return_arr['data']=array('url'=>U('Admin/goods/goodsList'));
				}else{
					$return_arr['data']=array('url'=>U('Admin/Haitao/goodsList'));
				}
				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		$goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
//		$cat_list = $HaitaoLogic->goods_cat_list(); // 已经改成联动菜单
		$level_cat = $HaitaoLogic->find_parent_cat($goodsInfo['haitao_cat']); // 获取分类默认选中的下拉框
		$cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单
		$haitao_style = M('haitao_style')->select();
		$merchantList = $HaitaoLogic->getSortMerchant();
		$goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
		if(empty($goodsType))
			$goodsType = M("GoodsType")->select();
		$this->assign('level_cat',$level_cat);
		$this->assign('cat_list',$cat_list);
		$this->assign('haitao_style',$haitao_style);
		$this->assign('merchantList',$merchantList);
		$this->assign('goodsType',$goodsType);
		$this->assign('goodsInfo',$goodsInfo);  // 商品详情
		$goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
		$this->assign('goodsImages',$goodsImages);  // 商品相册
		$this->initEditor(); // 编辑器
		$this->display('_goods');
	}

	/**
	 * 删除商品
	 */
	public function delGoods()
	{
		// 判断此商品是否有订单
		$goods_count = M('goods')->where("goods_id = {$_GET['id']}")->find();
		if($goods_count['show_type']==1)
		{
			$return_arr = array('status' => -1,'msg' => '该商品已删除','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
			$this->ajaxReturn(json_encode($return_arr));
		}
		// 删除此商品
		M("Goods")->where('goods_id =' . $_GET['id'])->save(array('show_type'=>1));
		$return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
		$this->ajaxReturn(json_encode($return_arr));
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

	/**
	 * 商品规格列表
	 */
	public function specList()
	{
		$goodsTypeList = M("GoodsType")->select();
		$this->assign('goodsTypeList', $goodsTypeList);
		$this->display();
	}

	/**
	 * 删除分类
	 */
	public function delGoodsCategory()
	{
		// 判断子分类
		$GoodsCategory = M("haitao");
		$count = $GoodsCategory->where("parent_id = {$_GET['id']}")->count("id");
		$count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/haitao/categoryList'));
		// 判断是否存在商品
		$goods_count = M('Goods')->where("haitao_cat = {$_GET['id']}")->count('1');
		$goods_count > 0 && $this->error('该分类下有商品不得删除!', U('Admin/haitao/categoryList'));
		// 删除分类
		$GoodsCategory->where("id = {$_GET['id']}")->delete();
		$this->success("操作成功!!!", U('Admin/haitao/categoryList'));
	}
}