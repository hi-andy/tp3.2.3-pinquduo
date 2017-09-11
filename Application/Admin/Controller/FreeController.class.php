<?php
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class FreeController extends BaseController
{
	public function goodsList()
	{
//        $brandList = $GoodsLogic->getSortBrands();
		$categoryList = $this->getSortCategory();
		$merchantList = $this->getSortMerchant();
		$this->assign('categoryList', $categoryList);
		$this->assign('merchantList', $merchantList);
		$this->display();
	}

	/**
	 *  获取排好序的分类列表
	 */
	function getSortCategory()
	{
		$categoryList =  M("GoodsCategory")->getField('id,name,parent_id,level');
		$nameList = array();
		foreach($categoryList as $k => $v)
		{
			$name = getFirstCharter($v['name']) .' '. $v['name']; // 前面加上拼音首字母
			$nameList[] = $v['name'] = $name;
			$categoryList[$k] = $v;
		}
		array_multisort($nameList,SORT_STRING,SORT_ASC,$categoryList);

		return $categoryList;
	}

	/**
	 *  获取排好序的商户列表
	 */
	function getSortMerchant()
	{
		$merchantList = M("merchant")->where('`state`=1')->select();
		return $merchantList;
	}


		/**
		 *  商品列表
		 */
		public function ajaxGoodsList()
	{
		$where = '`show_type`=0 and `cat_id` != 0 and `the_raise`=0 and `is_special`=6 and is_audit=1 '; // 搜索条件
		I('intro')    && $where = "$where and ".I('intro')." = 1" ;
		I('cat') && $where = "$where and cat_id = ".I('cat') ;
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}

		if($_REQUEST['is_on_sale']!=''){
			$where = "$where and is_on_sale = ".$_REQUEST['is_on_sale'] ;
		}
		// 关键词搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if($key_word)
		{
			$where = "$where and (goods_name like '%$key_word%' " ;
		}
		$cat_id = I('cat');
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
		$goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)
			->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')
			->field('tp_goods.*,tp_merchant.id,tp_merchant.store_name')
			->select();

		$is_check = false;

		for ($i = 0; $i < count($goodsList); $i++) {
			$cat_name = M('goods_category')->where('`id`=' . $goodsList[$i]['cat_id'])->field('name')->find();
			$goodsList[$i]['cat_name'] = $cat_name['name'];
		}

		if($_REQUEST['is_check'])
			$is_check = $_REQUEST['is_check'];

		$catList = D('goods_category')->select();
		$catList = convert_arr_key($catList, 'id');
		$this->assign('is_check',$is_check);
		$this->assign('catList',$catList);
		$this->assign('goodsList',$goodsList);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
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
		$_POST['refresh'] = 0;
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
					$Goods->refresh = 0 ;
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
					redislist("goods_refresh_id", $goods_id);
				}
				else
				{
					$goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
					$Goods->afterSave($goods_id);
				}
				redisdelall("getDetaile_".$goods_id);
				
				$GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
				if($_POST['the_raise'] ==1){
					$return_arr = array(
						'status' => 1,
						'msg'   => '操作成功',
						'data'  => array('url'=>U('Admin/Free/goodsList')),
					);
				}else
				{
					$return_arr = array(
						'status' => 1,
						'msg'   => '操作成功',
						'data'  => array('url'=>U('Admin/Free/goodsList')),
					);
				}

				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		$goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
//		$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
		$level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框

		$cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
		$brandList = $GoodsLogic->getSortBrands();
		$merchantList = $GoodsLogic->getSortMerchant();
		$goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
		if(empty($goodsType))
			$goodsType = M("GoodsType")->select();
		$this->assign('level_cat',$level_cat);
		$this->assign('cat_list',$cat_list);
		$this->assign('brandList',$brandList);
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

	public function jiujiu_info()
	{
		$this->display();
	}

	public function goods_save()
	{
		$data['is_special'] = 6;
		$data['prom'] = 0;
		$data['free'] = 0;
		$data['is_support_buy'] = 0;
		for($i=0;$i<count($_POST['goods_id']);$i++){
			$res = M('goods')->where('`goods_id`='.$_POST['goods_id'][$i])->data($data)->save();
			redislist("goods_refresh_id", $_POST['goods_id'][$i]);
		}
		if($res){
			$this->success("添加成功",U('Free/goodsList'));
		}else{
			$this->success("添加失败",U('Free/free_info'));
		}
	}

	public function search_goods(){
		$goods_id = I('goods_id');
		$where = 'is_on_sale = 1 and is_special=0 and goodstatus=2';//搜索条件
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
		if(!empty(I('store_name'))){
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

	/**
	 * 删除商品
	 */
    public function delGoods()
    {
        // 判断此商品是否有订单
        $goods_count = M('goods')->where("goods_id = {$_GET['id']}")->find();
        if($goods_count['show_type']==1)
        {
            $return_arr = array('status' => -1,'msg' => '该商品已删除','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }
        // 删除此商品
        M("Goods")->where('goods_id =' . $_GET['id'])->save(array('is_special'=>0));
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);
        $this->ajaxReturn(json_encode($return_arr));
    }
}