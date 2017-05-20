<?php
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class SecondskillController extends BaseController
{
	public function Seconds_kill_list()
	{
		$this->display();
	}

	public function ajaxSeconds_kill_List()
	{
		$data = M('Seconds_kill_time')->where('`is_show`=1')->select();
		for ($i = 0; $i < count($data); $i++) {
			$data[$i]['add_time'] = date("Y-m-d H:i:s", $data[$i]['add_time']);
		}
		$this->assign('data', $data);
		$this->display();
	}

	public function addSeconds_kill()
	{
		if (!empty($_POST)) {
			$data['time'] = $_POST['time'];
			$data['add_time'] = time();
			$data['is_show'] = $_POST['is_show'];
			$res = M('Seconds_kill_time')->data($data)->add();
			if ($res) {
				$return_arr = array(
					'status' => 1,
					'msg' => '添加成功',
					'data' => array('url' => U('Admin/Secondskill/Seconds_kill_list')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			} else {
				$return_arr = array(
					'status' => -1,
					'msg' => '添加失败',
					'data' => array('url' => U('Admin/Secondskill/addSeconds_kill')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}
		$this->display();
	}

	public function EditSeconds_kill()
	{
		$id = $_GET['id'];
		if (!empty($id)) {
			$data = M('Seconds_kill_time')->where('`id`='.$id)->find();
			if (empty($data)) {
				$return_arr = array(
					'status' => -1,
					'msg' => '没有该时间',
					'data' => array('url' => U('Admin/Secondskill/EditSeconds_kill')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
			$this->assign('data', $data);
		}
		if ($_POST) {
			$data['time'] = $_POST['time'];
			$data['add_time'] = time();
			$data['is_show'] = $_POST['is_show'];
			$res = M('Seconds_kill_time')->where('`id`='.$_POST['id'])->data($data)->save();
			if ($res) {
				$return_arr = array(
					'status' => 1,
					'msg' => '修改成功',
					'data' => array('url' => U('Admin/Secondskill/Seconds_kill_list')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			} else {
				$return_arr = array(
					'status' => -1,
					'msg' => '修改失败',
					'data' => array('url' => U('Admin/Secondskill/addSeconds_kill')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		$this->display();
	}

	public function delSeconds_kill_time()
	{
		$id = $_GET['id'];
		$res = M('Seconds_kill_time')->where('`id`='.$id)->find();
		if (empty($res)) {
			$return_arr = array('status' => -1, 'msg' => '该时间段已经被删除了', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
			$this->ajaxReturn(json_encode($return_arr));
		}
		M('Seconds_kill_time')->where('`id`='.$id)->delete();
		$return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
		$this->ajaxReturn(json_encode($return_arr));
	}

	public function Seconds_kill_goods()
	{
		for ($i = 0; $i < 5; $i++) {
			$date[$i]['id'] = $i + 1;
			$date1 = time();
			if($i==0)  {
				$day = $date1 - (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i==1) {
				$day = $date1 - (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i == 2) {
				$date[$i]['date'] = date("Y-m-d", time());
			}elseif($i==3){
				$day = $date1 + (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			}else{
				$day = $date1 + (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			}
		}
		$time = M('seconds_kill_time')->where('`is_show`=1')->order('time asc')->select();
		for ($i = 0; $i < count($time); $i++) {
			$time[$i]['time'] = $time[$i]['time'] . ':00:00';
		}
		$store = M('merchant')->where('`state`=1')->field('id,store_name')->select();
		$this->assign('store', $store);
		$this->assign('time', $time);
		$this->assign('date', $date);
		$this->display();
	}

	public function Seconds_kill_info()
	{
		for ($i = 0; $i < 5; $i++) {
			$date[$i]['id'] = $i + 1;
			$date1 = time();
			if($i==0)  {
				$day = $date1 - (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i==1) {
				$day = $date1 - (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i == 2) {
				$date[$i]['date'] = date("Y-m-d", time());
			}elseif($i==3){
				$day = $date1 + (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			}else{
				$day = $date1 + (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			}
		}
		$time = M('seconds_kill_time')->where('`is_show`=1')->order('time asc')->select();
		for ($i = 0; $i < count($time); $i++) {
			$time[$i]['time'] = $time[$i]['time'] . ':00:00';
		}
		$store = M('merchant')->where('`is_show`=1')->field('id,store_name')->select();
		$this->assign('store', $store);
		$this->assign('time', $time);
		$this->assign('date', $date);
		$this->display();
	}

	public function ajaxindex()
	{
		$where = '`is_special`=2 and `on_time`>0 and show_type=0 ';
		if(!empty(I('store_name')))
		{
			$this->assign('store_name', I('store_name'));
			$where = $this->getStoreWhere($where,I('store_name'));
		}
		if (I('date') && I('time')) {
			$all_time = I('date') . ' ' . I('time');
			$where = $where . ' and `on_time`='.strtotime($all_time);
		} elseif (I('time')) {
			$times = strtotime(I('time'));
			$where = $where . ' and `on_time`='.$times;
		} elseif (I('date')) {
			$times = strtotime(I('date'));
			$where = $where . ' and `on_time`>='.$times.' and `on_time`<'.($times + 24 * 60 * 60);
		}
		$count = M('goods')->where($where)->count();
		$Page = new AjaxPage($count, 20);
		foreach ($where as $key => $val) {
			$Page->parameter[$key] = urlencode($val);
		}
		$show = $Page->show();
		//获取订单列表
		$goods = $this->getGoodsList($where, $Page->firstRow, $Page->listRows);
//		var_dump($goods);die;
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

	public function getGoodsList($condition, $start = 0, $page_size = 20)
	{
		$res = M('goods')->where($condition)->limit("$start,$page_size")->select();
		return $res;
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

	public function prom_goods_save($date,$time,$goods_id)
	{
		$date = I('date');
		$time = I('time');
		$all_time = $date . ' ' . $time;
		$data['on_time'] = strtotime($all_time);
		$data['is_special'] = 2;
		$res = M('goods')->where('`goods_id`=' .$goods_id )->data($data)->save();
		return $res;
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

    public function delete_goods()
    {
        // 判断此商品是否有订单
        $goods_count = M('goods')->where("goods_id = {$_GET['id']}")->find();
        if($goods_count['is_special']==0)
        {
            $return_arr = array('status' => -1,'msg' => '该商品已移除该活动','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }
        // 删除此商品
        M("Goods")->where('goods_id =' . $_GET['id'])->save(array('is_special'=>0));
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

	/**
	 * 添加修改秒杀商品
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
					$this->prom_goods_save($_POST['date'],$_POST['time'],$goods_id);
					redislist("goods_refresh_id", $goods_id);
				}
				else
				{
					$goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
					$Goods->afterSave($goods_id);
					$this->prom_goods_save($_POST['date'],$_POST['time'],$goods_id);
				}

				$GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性

				$return_arr = array(
					'status' => 1,
					'msg'   => '操作成功',
					'data'  => array('url'=>U('Admin/Secondskill/Seconds_kill_goods')),
				);
				$this->ajaxReturn(json_encode($return_arr));
			}
		}

		for ($i = 0; $i < 5; $i++) {
			$date[$i]['id'] = $i + 1;$date1 = time();
			if($i==0)  {
				$day = $date1 - (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i==1) {
				$day = $date1 - (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			} elseif($i == 2) {
				$date[$i]['date'] = date("Y-m-d", time());
			}elseif($i==3){
				$day = $date1 + (24 * 60 * 60);
				$date[$i]['date'] = date("Y-m-d", $day);
			}else{
				$day = $date1 + (24 * 60 * 60 * 2);
				$date[$i]['date'] = date("Y-m-d", $day);
			}
		}
		$time = M('seconds_kill_time')->where('`is_show`=1')->order('time asc')->select();
		for ($i = 0; $i < count($time); $i++) {
			$time[$i]['time'] = $time[$i]['time'] . ':00:00';
		}

		$store = M('merchant')->where('`is_show`=1')->field('id,store_name')->select();
		$this->assign('store', $store);
		$this->assign('time', $time);
		$this->assign('date', $date);
		$goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
		$level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
		$cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
		//$brandList = $GoodsLogic->getSortBrands();
		$merchantList = $GoodsLogic->getSortMerchant();
		$goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
		if(empty($goodsType))
			$goodsType = M("GoodsType")->select();
		$this->assign('level_cat',$level_cat);
		$this->assign('cat_list',$cat_list);
		//$this->assign('brandList',$brandList);
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

}