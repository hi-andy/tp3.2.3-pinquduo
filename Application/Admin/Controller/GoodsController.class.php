<?php
namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class GoodsController extends BaseController
{

    /**
     *  商品分类列表
     */
    public function categoryList()
    {
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
//        var_dump($cat_list);die;
        $this->assign('cat_list', $cat_list);
        $this->display();
    }

    /**
     * 添加修改商品分类
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'),
     * select * from tp_goods_category where id = 393
     * select * from tp_goods_category where parent_id = 393
     * update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
     * insert into `tp_goods_category` (`parent_id`,`name`) values
     * ('393','时尚饰品'),
     */
    public function addEditCategory()
    {
        $GoodsLogic = new GoodsLogic();
        if (IS_GET) {
            $goods_category_info = D('GoodsCategory')->where('id=' . I('GET.id', 0))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框

            $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            $this->assign('level_cat', $level_cat);
            $this->assign('cat_list', $cat_list);
            $this->assign('goods_category_info', $goods_category_info);
            $this->display('_category');
            exit;
        }
        $_POST['img'] = $_POST['image'];
        $GoodsCategory = D('GoodsCategory'); //
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
                    $GoodsLogic->refresh_cat($_POST['id']);
                } else {
                    $insert_id = $GoodsCategory->add(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('Admin/Goods/categoryList')),
                );
                redisdelall("getexplore");
                $this->ajaxReturn(json_encode($return_arr));

            }
        }

    }

    /**
     * 获取商品分类 的筛选规格 复选框
     */
    public function ajaxGetSpecList()
    {
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_spec = M('GoodsCategory')->where("id = " . $_REQUEST['category_id'])->getField('filter_spec');
        $filter_spec_arr = explode(',', $filter_spec);
        $str = $GoodsLogic->GetSpecCheckboxList($_REQUEST['type_id'], $filter_spec_arr);
        $str = $str ? $str : '没有可筛选的商品规格';
        exit($str);
    }

    /**
     * 获取商品分类 的筛选属性 复选框
     */
    public function ajaxGetAttrList()
    {
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = " . $_REQUEST['category_id'])->getField('filter_attr');
        $filter_attr_arr = explode(',', $filter_attr);
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'], $filter_attr_arr);
        $str = $str ? $str : '没有可筛选的商品属性';
        exit($str);
    }

    /**
     * 删除分类
     */
    public function delGoodsCategory()
    {
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");
        $count = $GoodsCategory->where("parent_id = {$_GET['id']}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id = {$_GET['id']}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!', U('Admin/Goods/categoryList'));
        // 删除分类
        $GoodsCategory->where("id = {$_GET['id']}")->delete();
        $this->success("操作成功!!!", U('Admin/Goods/categoryList'));
    }

    /**
     *  商品列表
     */
    public function goodsList()
    {
        $GoodsLogic = new GoodsLogic();
      $cat1 = M('GoodsCategory')->where('`parent_id`=0')->select();
        $where['parent_id'] = array('IN',array_column($cat1,'id'));
        $cat2 = M('GoodsCategory')->where($where)->select();
        $this->assign('cat2',$cat2);
        $this->assign('cat1',$cat1);
        $merchantList = $GoodsLogic->getSortMerchant();
        $this->assign('merchantList', $merchantList);
        $this->display();
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList()
    {
        $where = 'show_type=0 and `the_raise`=0 '; // 搜索条件
        I('intro') && $where = "$where and ".I('intro')." = 1" ;
        I('is_on_sale') != null && $where = "$where and `is_on_sale`= ".I('is_on_sale') ;
        (I('merchant_id') !=0) && $where = "$where and FIND_IN_SET(".I('merchant_id').',tp_merchant.id)';

        if($_REQUEST['is_audit']!=''){
            $where = "$where and is_audit = ".$_REQUEST['is_audit'];
        }
        if(!empty(I('store_name')))
        {
            $this->assign('store_name', I('store_name'));
            $where = $this->getStoreWhere($where,I('store_name'));
        }
        if(!empty(I('goods_id')))
        {
            $this->assign('goods_id', I('goods_id'));
            $where = " $where and goods_id = ".I('goods_id');
        }
        if(I('cat_id_2'))
        {
            $cat_id = M('GoodsCategory')->where('`parent_id`='.I('cat_id_2'))->field('id')->select();
            $cats =null;
            $num = count($cat_id);
            for($i=0;$i<$num;$i++)
            {
                if($i==$num-1)
                {
                    $cats = $cats."'".$cat_id[$i]['id']."')";
                }elseif($i==0){
                    $cats = $cats."('".$cat_id[$i]['id']."',";
                }else{
                    $cats = $cats."'".$cat_id[$i]['id']."',";
                }
            }
            $where = "$where and cat_id IN $cats";
        }elseif(I('cat_id_1')){
            $cat1 = M('GoodsCategory')->where('`parent_id`='.I('cat_id_1'))->field('id')->select();
            $cat2['parent_id'] = array('IN',array_column($cat1,'id'));
            $cat = M('GoodsCategory')->where($cat2)->field('id')->select();
            $cats =null;
            $num = count($cat);
            for($i=0;$i<$num;$i++)
            {
                if($i==$num-1)
                {
                    $cats = $cats."'".$cat[$i]['id']."')";
                }elseif($i==0){
                    $cats = $cats."('".$cat[$i]['id']."',";
                }else{
                    $cats = $cats."'".$cat[$i]['id']."',";
                }
            }
            $where = "$where and cat_id IN $cats";
        }
        $cat_id = I('cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%')" ;
        }

        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        $is_check = 'true';
        if($_REQUEST['is_check']=='false'){
            $is_check = $_REQUEST['is_check'];
            $where .= " and tp_merchant.state = 1";
        }

        $model = M('Goods');
        $count = $model->where($where)->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = " sort asc , `{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)
            ->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')
            ->field('tp_goods.*,tp_merchant.id,tp_merchant.store_name,tp_merchant.state')
            ->select();

        $catList1 = D('goods_category')->select();
        $catList = D('haitao')->select();
        $catList = convert_arr_key($catList, 'id');
        $catList1 = convert_arr_key($catList1, 'id');
        $this->assign('is_check',$is_check);
        $this->assign('catList',$catList);
        $this->assign('catList1',$catList1);
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
        session('goodstype',$_POST['goods_type']);
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
                    $rdsname = "getDetaile_".$goods_id;
                    redisdelall($rdsname);//删除商品详情缓存
                }
                else
                {
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    M('goods')->where('goods_id='.$goods_id)->save(array('goods_type'=>$_SESSION['goodstype']));
                    $Goods->afterSave($goods_id);
                }

                $GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
                if($_POST['the_raise'] ==1){
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',
                        'data'  => array('url'=>U('Admin/Crowdfund/goods_list')),
                    );
                }else
                {
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',
                        'data'  => array('url'=>U('Admin/Goods/goodsList')),
                    );
                }

                $this->ajaxReturn(json_encode($return_arr));
            }
        }

        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
        $cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框

//        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单

        $merchantList = $GoodsLogic->getSortMerchant();
        $goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
        if(empty($goodsType))
            $goodsType = M("GoodsType")->select();

        $level_cat = array_merge($level_cat);
        $level_cat = array_reverse($level_cat, TRUE);
        array_unshift($level_cat,array('id'=>'0','name'=>'null'));
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
        $this->assign('merchantList',$merchantList);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_goods');
    }

    /**
     * 商品类型  用于设置商品的属性
     */
    public function goodsTypeList()
    {
        $model = M("GoodsType");
        $count = $model->count();
        $Page = new Page($count, 100);
        $show = $Page->show();
        $goodsTypeList = $model->where(array('store_id'=>0))->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('show', $show);
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display('goodsTypeList');
    }


    /**
     * 添加修改编辑  商品属性类型
     */
    public function addEditGoodsType()
    {
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $model = M("GoodsType");
        if (IS_POST) {
            $_POST['store_id'] = 0;
            $model->create();
            if ($_GET['id'])
                $model->save();
            else
                $model->add();
            if($_POST['the_'])
            $this->success("操作成功!!!", U('Admin/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('goodsType', $goodsType);
        $this->display('_goodsType');
    }

    /**
     * 商品属性列表
     */
    public function goodsAttributeList()
    {
        $goodsTypeList = M("GoodsType")->where(array('store_id'=>0))->select();
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display();
    }

    /**
     *  商品属性列表
     */
    public function ajaxGoodsAttributeList()
    {
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 and `store_id`=0'; // 搜索条件
        I('type_id') && $where = "$where and type_id = " . I('type_id');
        // 关键词搜索
        $model = M('GoodsAttribute');
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count, 13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $goodsTypeList = M("GoodsType")->where(array('store_id'=>0))->getField('id,name');
        $attr_input_type = array(0 => '手工录入', 1 => ' 从列表中选择', 2 => ' 多行文本框');
        $this->assign('attr_input_type', $attr_input_type);
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->assign('goodsAttributeList', $goodsAttributeList);
        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }

    /**
     * 添加修改编辑  商品属性
     */
    public function addEditGoodsAttribute()
    {

        $model = D("GoodsAttribute");
        $type = $_POST['attr_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $_POST['attr_values'] = str_replace('_', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = str_replace('@', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = trim($_POST['attr_values']);

        if (($_GET['is_ajax'] == 1) && IS_POST)//ajax提交验证
        {
            C('TOKEN_ON', false);
            if (!$model->create(NULL, $type))// 根据表单提交的POST数据创建数据对象
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg' => '',
                    'data' => $model->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            } else {
                // C('TOKEN_ON',true); //  form表单提交
                if ($type == 2) {
                    $model->save(); // 写入数据到数据库
                } else {
                    $insert_id = $model->add(); // 写入数据到数据库
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('Admin/Goods/goodsAttributeList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        // 点击过来编辑时
        $_GET['attr_id'] = $_GET['attr_id'] ? $_GET['attr_id'] : 0;
        $goodsTypeList = M("GoodsType")->select();
        $goodsAttribute = $model->find($_GET['attr_id']);
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->assign('goodsAttribute', $goodsAttribute);
        $this->display('_goodsAttribute');
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField()
    {
        $primary = array(
            'goods' => 'goods_id',
            'goods_category' => 'id',
            'brand' => 'id',
            'goods_attribute' => 'attr_id',
            'ad' => 'ad_id',
        );
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];
        $model->save();
        $return_arr = array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array('url' => U('Admin/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput()
    {
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'], $_REQUEST['type_id']);
        exit($str);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        // 判断此商品是否有订单
        $goods_count = M('OrderGoods')->where("goods_id = {$_GET['id']}")->count('1');
        if ($goods_count) {
            $return_arr = array('status' => -1, 'msg' => '此商品有订单,不得删除!', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }

        // 删除此商品        
        M('goods')->where('`goods_id`='.$_GET['id'])->delete();
        M('goods_images')->where('`goods_id`='.$_GET['id'])->delete();
        M('spec_goods_price')->where('`goods_id`='.$_GET['id'])->delete();
        M('spec_image')->where('`goods_id`='.$_GET['id'])->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    function dels()
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
     * 删除商品类型
     */
    public function delGoodsType()
    {
        // 判断 商品规格        
        $count = M("Spec")->where("type_id = {$_GET['id']}")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!', U('Admin/Goods/goodsTypeList'));
        // 判断 商品属性        
        $count = M("GoodsAttribute")->where("type_id = {$_GET['id']}")->count("1");
        $count > 0 && $this->error('该类型下有商品属性不得删除!', U('Admin/Goods/goodsTypeList'));
        // 删除分类
        M('GoodsType')->where("id = {$_GET['id']}")->delete();
        $this->success("操作成功!!!", U('Admin/Goods/goodsTypeList'));
    }

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        // 判断 有无商品使用该属性
        $count = M("GoodsAttr")->where("attr_id = {$_GET['id']}")->count("1");
        $count > 0 && $this->error('有商品使用该属性,不得删除!', U('Admin/Goods/goodsAttributeList'));
        // 删除 属性
        M('GoodsAttribute')->where("attr_id = {$_GET['id']}")->delete();
        $this->success("操作成功!!!", U('Admin/Goods/goodsAttributeList'));
    }

    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        // 判断 商品规格项
        $count = M("SpecItem")->where("spec_id = {$_GET['id']}")->count("1");
        $count > 0 && $this->error('清空规格项后才可以删除!', U('Admin/Goods/specList'));
        // 删除分类
        M('Spec')->where("id = {$_GET['id']}")->delete();
        $this->success("操作成功!!!", U('Admin/Goods/specList'));
    }

    /**
     * 品牌列表
     */
    public function brandList()
    {
        $model = M("Brand");
        $where = "";
        $keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
        $count = $model->where($where)->count();
        $Page = new Page($count, 10);
        $brandList = $model->where($where)->order("`sort` asc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $this->assign('show', $show);
        $this->assign('brandList', $brandList);
        $this->display('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public function addEditBrand()
    {
        $id = I('id');
        $model = M("Brand");
        if (IS_POST) {
            $model->create();
            if ($id)
                $model->save();
            else
                $id = $model->add();

            $this->success("操作成功!!!", U('Admin/Goods/brandList', array('p' => $_GET['p'])));
            exit;
        }
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $brand = $model->find($id);
        $this->assign('brand', $brand);
        $this->display('_brand');
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {
        // 判断此品牌是否有商品在使用
        $goods_count = M('Goods')->where("brand_id = {$_GET['id']}")->count('1');
        if ($goods_count) {
            $return_arr = array('status' => -1, 'msg' => '此品牌有商品在用不得删除!', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }

        $model = M("Brand");
        $model->where('id =' . $_GET['id'])->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUpText', array('savepath' => 'goods'))); // 图片上传目录
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
        $goodsTypeList = M("GoodsType")->where(array('store_id'=>0))->select();
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display();
    }


    /**
     *  商品规格列表
     */
    public function ajaxSpecList()
    {
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 and `store_id`=0 '; // 搜索条件
        I('type_id') && $where = "$where and type_id = " . I('type_id');
        // 关键词搜索               
        $model = D('spec');
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count, 13);
        $show = $Page->show();
        $specList = $model->where($where)->order('`type_id` desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $GoodsLogic = new GoodsLogic();
        foreach ($specList as $k => $v) {       // 获取规格项
            $arr = $GoodsLogic->getSpecItem($v['id']);
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList', $specList);
        $this->assign('page', $show);// 赋值分页输出
        $goodsTypeList = M("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display();
    }

    /**
     * 添加修改编辑  商品规格
     */
    public function addEditSpec()
    {

        $model = D("spec");
        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        if (($_GET['is_ajax'] == 1) && IS_POST)//ajax提交验证
        {
            C('TOKEN_ON', false);
            if (!$model->create(NULL, $type))// 根据表单提交的POST数据创建数据对象
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg' => '',
                    'data' => $model->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            } else {
                // C('TOKEN_ON',true); //  form表单提交
                if ($type == 2) {
                    $model->save(); // 写入数据到数据库
                    $model->afterSave($_POST['id']);
                } else {
                    $insert_id = $model->add(); // 写入数据到数据库
                    $model->afterSave($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('Admin/Goods/specList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        // 点击过来编辑时
        $id = $_GET['id'] ? $_GET['id'] : 0;
        $spec = $model->find($id);
        $GoodsLogic = new GoodsLogic();
        $items = $GoodsLogic->getSpecItem($id);
        $spec[items] = implode(PHP_EOL, $items);
        $this->assign('spec', $spec);

        $goodsTypeList = M("GoodsType")->select();
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display('_spec');
    }


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $GoodsLogic = new GoodsLogic();
        $specList = D('Spec')->where("type_id = " . $_GET['spec_type'])->order('`order` desc')->select();
        foreach ($specList as $k => $v)
            $specList[$k]['spec_item'] = D('SpecItem')->where("is_show = 1 and spec_id = " . $v['id'])->getField('id,item'); // 获取规格项

        $items_id = M('SpecGoodsPrice')->where('goods_id = ' . $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片                
        if ($goods_id) {
            $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }

        $this->assign('specImageList', $specImageList);
        $this->assign('items_ids', $items_ids);
        $this->assign('specList', $specList);
        $this->display('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
        $GoodsLogic = new GoodsLogic();
        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $str = $GoodsLogic->getSpecInput($goods_id, $_POST['spec_arr']);
        exit($str);
    }

//    /*
//     * 展示标签列表
//     */
//    public function labelList()
//    {
//        $GoodsLogic = new GoodsLogic();
//        $label_list = $GoodsLogic->goods_label_list();
//        $this->assign('label_list', $label_list);
//        $this->display();
//    }
//
//    /**
//     * 删除标签
//     */
//    public function delGoodsLabel()
//    {
//        // 判断子标签
//        $GoodsLabel = M("goods_label");
//        $count = $GoodsLabel->where("parent_id = {$_GET['id']}")->count("id");
//        $count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/Goods/labelList'));
//        // 删除标签
//        $data['is_show'] = 0;
//        $GoodsLabel->where("id = {$_GET['id']}")->data($data)->save();
//        $this->success("操作成功!!!", U('Admin/Goods/labelList'));
//    }
//
//    public function addEditLabel()
//    {
//
//        $GoodsLogic = new GoodsLogic();
//        if (IS_GET) {
//            $goods_label_info = D('Goods_label')->where('id=' . I('GET.id', 0))->find();//找到自己
//            $level_cat = $GoodsLogic->find_parent_label($goods_label_info['id']); // 获取分类默认选中的下拉框
//            $cat_list = M('Goods_label')->where("parent_id = 0")->select(); // 已经改成联动菜单
//
//            $this->assign('level_cat', $level_cat);
//            $this->assign('cat_list', $cat_list);
//            $this->assign('goods_label_info', $goods_label_info);
//            session('id', $goods_label_info['id']);
//            $this->display('_label');
//            exit;
//        }
//
//        $GoodsLabel = M('Goods_label'); //
//        // 标识自动验证时的 ,有值就是更新，没有就是添加
//        $type = $_SESSION['id'];
//        $children = $GoodsLabel->where(' id = ' . $type)->find();//找他下一级的子类
//        if($type > 0) {
//            if ($children['parent_id'] == 0) {
//                $children2 = $GoodsLabel->where('parent_id =' . $type)->select();
//                if (!empty($children2)) {
//                    $return_arr = array(
//                        'status' => -1,
//                        'msg' => '操作失败!',
//                        'data' => $GoodsLabel->getError(),
//                    );
//                    $this->ajaxReturn(json_encode($return_arr));
//                } else {
//                    $data['name'] = $_POST['name'];
//                    $data['parent_id'] = $_POST['parent_id'];
//                    $data['level'] = 2;
//                    $res = $GoodsLabel->data($data)->where('id =' . $type)->save();
//                    if ($res > 0) {
//                        $return_arr = array(
//                            'status' => 1,
//                            'msg' => '操作成功',
//                            'data' => array('url' => U('Admin/Goods/labelList')),
//                        );
//                        $this->ajaxReturn(json_encode($return_arr));
//                    } else {
//                        $return_arr = array(
//                            'status' => -1,
//                            'msg' => '操作失败!',
//                            'data' => $GoodsLabel->getError(),
//                        );
//                        $this->ajaxReturn(json_encode($return_arr));
//                    }
//                }
//            } else {
//                $data['name'] = $_POST['name'];
//                $data['parent_id'] = $_POST['parent_id'];
//                if ($_POST['parent_id'] == 0) $data['level'] = 1;
//                $res = $GoodsLabel->data($data)->where('id =' . $type)->save();
//                if ($res > 0) {
//                    $return_arr = array(
//                        'status' => 1,
//                        'msg' => '操作成功',
//                        'data' => array('url' => U('Admin/Goods/labelList')),
//                    );
//                    $this->ajaxReturn(json_encode($return_arr));
//                } else {
//                    $return_arr = array(
//                        'status' => -1,
//                        'msg' => '操作失败!',
//                        'data' => $GoodsLabel->getError(),
//                    );
//                    $this->ajaxReturn(json_encode($return_arr));
//                }
//            }
//        }
//        else
//           {
//               $data['name'] = $_POST['name'];
//               $data['parent_id'] = $_POST['parent_id'];
//               if($_POST['parent_id'] == 0) $data['level'] = 1;else $data['level'] = 2;
//               $res = $GoodsLabel->data($data)->add();
//               if ($res > 0) {
//                   $return_arr = array(
//                       'status' => 1,
//                       'msg' => '操作成功',
//                       'data' => array('url' => U('Admin/Goods/labelList')),
//                   );
//                   $this->ajaxReturn(json_encode($return_arr));
//               } else {
//                   $return_arr = array(
//                       'status' => -1,
//                       'msg' => '操作失败!',
//                       'data' => $GoodsLabel->getError(),
//                   );
//                   $this->ajaxReturn(json_encode($return_arr));
//               }
//           }
//    }
//public function in()
//{
//    $this->display('include_label');
//}

    public function Seconds_kill_list()
    {
        $this->display();
    }

    public function ajaxSeconds_kill_List()
    {
        $data = M('Seconds_kill_time')->where('`is_show`=1')->select();
        for($i=0;$i<count($data);$i++)
        {
            $data[$i]['add_time'] = date("Y-m-d H:i:s",$data[$i]['add_time']);
        }
        $this->assign('data',$data);
        $this->display();
    }

    public function addSeconds_kill()
    {
        if(!empty($_POST))
        {
            $data['time'] = $_POST['time'];
            $data['add_time'] = time();
            $data['is_show'] = $_POST['is_show'];
            $res =M('Seconds_kill_time')->data($data)->add();
            if ($res) {
                $return_arr = array(
                    'status' => 1,
                    'msg' => '添加成功',
                    'data' => array('url' => U('Admin/Goods/Seconds_kill_list')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            } else {
                $return_arr = array(
                    'status' => -1,
                    'msg' => '添加失败',
                    'data' => array('url' => U('Admin/Goods/addSeconds_kill')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        $this->display();
    }

    public function EditSeconds_kill()
    {
        $id = $_GET['id'];
        if(!empty($id))
        {
            $data = M('Seconds_kill_time')->where('`id`='.$id.' and `is_show`=1')->find();
            if(empty($data))
            {
                $return_arr = array(
                    'status' => -1,
                    'msg' => '修改失败',
                    'data' => array('url' => U('Admin/Goods/EditSeconds_kill')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            $this->assign('data',$data);
        }
        if($_POST)
        {
            $data['time'] = $_POST['time'];
            $data['add_time'] = time();
            $data['is_show'] = $_POST['is_show'];
            $res =M('Seconds_kill_time')->where('`id`='.$_POST['id'])->data($data)->save();
            if ($res) {
                $return_arr = array(
                    'status' => 1,
                    'msg' => '修改成功',
                    'data' => array('url' => U('Admin/Goods/Seconds_kill_list')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            } else {
                $return_arr = array(
                    'status' => -1,
                    'msg' => '修改失败',
                    'data' => array('url' => U('Admin/Goods/addSeconds_kill')),
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
        if(empty($res))
        {
            $return_arr = array('status' => -1, 'msg' => '该时间段已经被删除了', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }
        M('Seconds_kill_time')->where('`id`='.$id)->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 商品审核列表
     */
    public function goods_check_list(){
//        $GoodsLogic = new GoodsLogic();
//        $brandList = $GoodsLogic->getSortBrands();
//        $categoryList = $GoodsLogic->getSortCategory();
//        $merchantList = $GoodsLogic->getSortMerchant();
//        $this->assign('categoryList', $categoryList);
//        $this->assign('brandList', $brandList);
//        $this->assign('merchantList', $merchantList);
        $this->display();
    }

    public function no_audit()
    {
        $id = I('id');
        $res = M('goods')->where('goods_id='.$id)->save(array('is_audit'=>2));
        if ($res) {
            $return_arr = array('status' => 1, 'msg' => '已驳回该商品审核', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }else{
            $return_arr = array('status' => -1, 'msg' => '驳回失败', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }
    }
}

