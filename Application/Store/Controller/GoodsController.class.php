<?php
/**
 */
namespace Store\Controller;
use Admin\Logic\HaitaoLogic;
use Store\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class GoodsController extends BaseController {


    /*
     * 初始化操作
     */
    public function _initialize() {
        C('TOKEN_ON',false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->assign('is_show',C('IS_SHOW'));
        $this->assign('is_audit',C('IS_AUDIT'));
        $this->assign('is_on_sale',C('IS_ON_SALE'));
        $this->assign('is_special',C('IS_SPECIAL'));

        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        $haitao = M('store_detail')->where('storeid='.$_SESSION['merchant_id'])->find();
        if($haitao['is_pay']==0)
        {
            $this->error("尚未缴纳保证金，现在前往缴纳",U('Store/Index/pay_money'));
        }

    }

    /**
     *  商品分类列表
     */
    public function categoryList(){
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $this->assign('cat_list',$cat_list);
        $this->display();
    }

    /**
     * 添加修改商品分类
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'),
     * select * from tp_goods_category where id = 393
    select * from tp_goods_category where parent_id = 393
    update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
    insert into `tp_goods_category` (`parent_id`,`name`) values
    ('393','时尚饰品'),
     */
    public function addEditCategory(){

        $GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $goods_category_info = D('GoodsCategory')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框

            $cat_list = M('goods_category')->where("parent_id = 0" )->select(); // 已经改成联动菜单

            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('goods_category_info',$goods_category_info);
            $this->display('_category');
            exit;
        }

        $GoodsCategory = D('GoodsCategory'); //

        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if($_GET['is_ajax'] == 1)
        {
            C('TOKEN_ON',false);

            if(!$GoodsCategory->create(NULL,$type))// 根据表单提交的POST数据创建数据对象
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '操作失败!',
                    'data'  => $GoodsCategory->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }else {
                //  form表单提交
                C('TOKEN_ON',true);

                $GoodsCategory->parent_id = $_POST['parent_id_1'];
                $_POST['parent_id_2'] && ($GoodsCategory->parent_id = $_POST['parent_id_2']);

                if($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id)
                {
                    //  编辑
                    $return_arr = array(
                        'status' => -1,
                        'msg'   => '上级分类不能为自己',
                        'data'  => '',
                    );
                    $this->ajaxReturn(json_encode($return_arr));
                }
                if ($type == 2)
                {
                    $GoodsCategory->save(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat($_POST['id']);
                }
                else
                {
                    $insert_id = $GoodsCategory->add(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/categoryList')),
                );
                $this->ajaxReturn(json_encode($return_arr));

            }
        }

    }

    /**
     * 获取商品规格 的筛选规格 复选框
     */
    public function ajaxGetSpecList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_spec = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_spec');
        $filter_spec_arr = explode(',',$filter_spec);
        $str = $GoodsLogic->GetSpecCheckboxList($_REQUEST['type_id'],$filter_spec_arr);
        $str = $str ? $str : '没有可筛选的商品规格';
        exit($str);
    }

    /**
     * 获取商品属性 的筛选属性 复选框
     */
    public function ajaxGetAttrList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_attr');
        $filter_attr_arr = explode(',',$filter_attr);
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'],$filter_attr_arr);
        $str = $str ? $str : '没有可筛选的商品属性';
        exit($str);
    }

    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");
        $count = $GoodsCategory->where("parent_id = {$_GET['id']}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',U('Store/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id = {$_GET['id']}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',U('Store/Goods/categoryList'));
        // 删除分类
        $data['is_show'] = 0 ;
        $GoodsCategory->data($data)->where("id = {$_GET['id']}")->save();
        $this->success("操作成功!!!",U('Store/Goods/categoryList'));
    }


    /**
     *  商品列表
     */
    public function goodsList(){
//        var_dump($_SESSION['is_haitao']);die;
        if($_SESSION['is_haitao']==1)
        {
            $cat1 = M('haitao')->where('`parent_id`=0')->select();
            $where['parent_id'] = array('IN',array_column($cat1,'id'));
            $cat2 = M('haitao')->where($where)->select();
        }else{
            $cat1 = M('GoodsCategory')->where('`parent_id`=0')->select();
            $where['parent_id'] = array('IN',array_column($cat1,'id'));
            $cat2 = M('GoodsCategory')->where($where)->select();
        }
        $store_state = M('merchant')->where('id = '.$_SESSION['merchant_id'])->find();
        $this->assign('store',$store_state);
        $this->assign('cat2',$cat2);
        $this->assign('cat1',$cat1);
        $this->display();
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList(){

        $where = ' show_type = 0 '. ' and store_id = '.$_SESSION['merchant_id'] ; // 搜索条件
        I('intro') && $where = "$where and ".I('intro')." = 1" ;
        I('is_on_sale') != null && $where = "$where and `is_on_sale`= ".I('is_on_sale') ;
        (I('merchant_id') !=0) && $where = "$where and FIND_IN_SET(".I('merchant_id').',tp_merchant.id)';

        if($_REQUEST['is_audit']!=''){
//            $where = "$where and is_on_sale = ".$_REQUEST['is_on_sale'] ;
            $where = "$where and is_audit = ".$_REQUEST['is_audit'];
        }
        if(!empty(I('store_name')))
        {
            $this->assign('store_name', I('store_name'));
            $where = $this->getStoreWhere($where,I('store_name'));
        }

        if($_SESSION['is_haitao']==1)
        {
            if(I('cat_id_2')){
                $where = $where = "$where and haitao_cat =".I('cat_id_2');
            }elseif(I('cat_id_1')){
                $cat = M('haitao')->where('`parent_id`='.I('cat_id_1'))->field('id')->select();
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
                $where = "$where and haitao_cat IN $cats";
            }
        }else{
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
        }

        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%')" ;
        }

        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        if(!empty($cats))
        {
            $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)->select();
        }else{
            $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)->select();
        }
        $haitao = $_SESSION['is_haitao'];
        if(!empty($haitao))
        {
            $this->assign('haitao',$haitao);
        }

        $catList = D('haitao')->select();
        $catList1 = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $catList1 = convert_arr_key($catList1, 'id');
        $this->assign('catList',$catList);
        $this->assign('catList1',$catList1);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }


    /**
     * 添加修改商品
     */
    public function addEditGoods(){
        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        $GoodsLogic = new GoodsLogic();
        $Goods = D('Goods'); //

        if(IS_POST)
        {
            $min_num = key($_POST['item']);
            $price = $_POST['item'][$min_num]['price'];
            if($price==0||empty($price))
            {
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '规格价格没有正确填写',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if($_POST['cat_id']==0 || $_POST['cat_id_2']==0 || $_POST['cat_id_3']==0){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '有分类尚未选择，请检查后提交',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if($_POST['prom']<2 && $_POST['is_special']!=6){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '团购人数不能低于两人',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if(empty($_POST['goods_images'][0])){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '请上传商品轮播图！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if(empty($_POST['goods_content'][0])){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '请填上传商品详细描述图片！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }

        $type = $_POST['goods_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(($_GET['is_ajax'] == 1) && IS_POST)
        {
            C('TOKEN_ON',false);
            if(!$Goods->create(NULL,$type))  // 根据表单提交的POST数据创建数据对象
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

                //详情图片
                $Goods->goods_content = null;
                $goodscontent = "";
                foreach ($_POST['goods_content'] as $v){
                    $goodscontent .= '<img src="'.$v.'">';
                }
                $goodscontent = str_replace('<img src="">','',$goodscontent);
                $Goods->goods_content = $goodscontent;

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
                    $rdsname = "getGoodsDetails".$goods_id."*";
                    REDIS_SWITCH && redisdelall($rdsname);//删除商品详情缓存
                }
                else
                {
                    $Goods->is_on_sale = 0 ;    // 默认
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                }

                $GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/goodsList')),
                );

                $this->ajaxReturn(json_encode($return_arr));
                echo M()->getLastSql();die;
            }
        }

        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框

        $goodsType = M("GoodsType")->where('`store_id`='.$_SESSION['merchant_id'])->select();
        $haitao = $goodsInfo['is_special'];
        if($haitao==1) {
            $cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单
        }else{
            $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        }

        if(!empty($haitao))
        {
            $haitao_style = M('haitao_style')->select();
            $this->assign('haitao',$haitao);
            $this->assign('haitao_style',$haitao_style);
        }
        $level_cat = array_merge($level_cat);
        $level_cat = array_reverse($level_cat, TRUE);
        array_unshift($level_cat,array('id'=>'0','name'=>'null'));

        $this->assign('goodsContent',getImgs($goodsInfo['goods_content']));
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_goods');
    }

    /**
     * 商品类型  用于设置商品的类型
     */
    public function goodsTypeList(){
        $model = M("GoodsType");

        $where ='store_id = '.$_SESSION['merchant_id'];
        $count = $model->where($where)->count();
        $Page  = new Page($count,100);
        $show  = $Page->show();
        $goodsTypeList = $model->where($where)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display('goodsTypeList');
    }


    /**
     * 添加修改编辑  商品类型
     */
    public  function addEditGoodsType(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $model = M("GoodsType");
        if(IS_POST)
        {
            $_POST['store_id'] = $_SESSION['merchant_id'];
            $model->create();
            if($_GET['id'])
                $model->save();
            else
                $model->add();

            $this->success("操作成功!!!",U('Store/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('goodsType',$goodsType);
        $this->display('_goodsType');
    }

    /**
     * 商品属性列表
     */
    public function goodsAttributeList(){
        $goodsTypeList = M("GoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    /**
     *  商品属性列表
     */
    public function ajaxGoodsAttributeList(){
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索
        $model = M('GoodsAttribute');
        $count = $model->where($where.' and is_show =1 and store_id ='.$_SESSION['merchant_id'])->count();
        $Page = new AjaxPage($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where.' and is_show =1 and store_id ='.$_SESSION['merchant_id'])->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
//        var_dump(m()->getLastSql());die;
        $goodsTypeList = M("GoodsType")->getField('id,name');
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 添加修改编辑  商品属性
     */
    public  function addEditGoodsAttribute(){

        $model = D("GoodsAttribute");
        $type = $_POST['attr_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $_POST['attr_values'] = str_replace('_', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = str_replace('@', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = trim($_POST['attr_values']);

        if(($_GET['is_ajax'] == 1) && IS_POST)//ajax提交验证
        {
            C('TOKEN_ON',false);
            if(!$model->create(NULL,$type))// 根据表单提交的POST数据创建数据对象
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '',
                    'data'  => $model->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }else {
                // C('TOKEN_ON',true); //  form表单提交
                if ($type == 2)
                {
                    $model->save(); // 写入数据到数据库
                }
                else
                {
                    $insert_id = $model->add(); // 写入数据到数据库
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/goodsAttributeList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        // 点击过来编辑时
        $_GET['attr_id'] = $_GET['attr_id'] ? $_GET['attr_id'] : 0;
        $goodsTypeList = M("GoodsType")->select();
        $goodsAttribute = $model->find($_GET['attr_id']);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('goodsAttribute',$goodsAttribute);
        $this->display('_goodsAttribute');
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
            'goods' => 'goods_id',
            'goods_category' => 'id',
            'brand' => 'id',
            'goods_attribute' => 'attr_id',
            'ad' =>'ad_id',
        );
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];
        $model->save();
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',
            'data'  => array('url'=>U('Store/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn(json_encode($return_arr));
    }
    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
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
//        M('goods')->where('`goods_id`='.$_GET['id'])->delete();
//        M('goods_images')->where('`goods_id`='.$_GET['id'])->delete();
//        M('spec_goods_price')->where('`goods_id`='.$_GET['id'])->delete();
//        M('spec_image')->where('`goods_id`='.$_GET['id'])->delete();
//        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);
        M('goods')->where("goods_id = {$_GET['id']}")->save(array('show_type'=>1));
        $return_arr = array('status' => -1,'msg' => '删除成功','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 删除商品类型
     */
    public function delGoodsType()
    {
        $id = I('id');
        // 判断 商品规格
        $count = M("Spec")->where("type_id = $id")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',U('Store/Goods/goodsTypeList'));
        // 判断 商品属性
        $count = M("GoodsAttribute")->where("type_id =$id")->count("1");
        $count > 0 && $this->error('该类型下有商品属性不得删除!',U('Store/Goods/goodsTypeList'));
        // 删除分类

        M('GoodsType')->where("id = $id")->delete();
        $this->success("操作成功!!!",U('Store/Goods/goodsTypeList'));
    }

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        // 判断 有无商品使用该属性
        $count = M("GoodsAttr")->where("attr_id = {$_GET['id']}")->count("1");
        $count > 0 && $this->error('有商品使用该属性,不得删除!',U('Store/Goods/goodsAttributeList'));
        // 删除 属性
        $data['is_show'] = 0 ;
        M('GoodsAttribute')->data($data)->where("attr_id = {$_GET['id']}")->save();
        $this->success("操作成功!!!",U('Store/Goods/goodsAttributeList'));
    }

    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        // 判断 商品规格项
        $id = I('id');
        $count = M("SpecItem")->where("spec_id = $id and is_show = 1")->count("1");
        $count > 0 && $this->error('清空规格项后才可以删除!',U('Store/Goods/specList'));
        // 删除分类
        //将它下面的所有规格删除
        $id = M('spec')->where("id = $id")->find();
        M('SpecItem')->where('spec_id = '.$id['id'])->delete();
        M('spec')->where("id = ".$id['id'])->delete();
        $this->success("操作成功!!!",U('Store/Goods/specList'));
    }

    /**
     * 品牌列表
     */
    public function brandList(){
        $model = M("Brand");
        $keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
        $count = $model->where($where.'is_show =1 and store_id ='.$_SESSION['merchant_id'])->count();
        $Page  = new Page($count,10);
        $brandList = $model->where($where .'is_show =1 and store_id ='.$_SESSION['merchant_id'])->order("`sort` asc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $show  = $Page->show();
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $this->assign('show',$show);
        $this->assign('brandList',$brandList);
        $this->display('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand(){
        $id = I('id');
        $model = M("Brand");
        if(IS_POST)
        {
            $model->create();
            if($id)
                $model->save();
            else
                $id = $model->add();

            $this->success("操作成功!!!",U('Store/Goods/brandList',array('p'=>$_GET['p'])));
            exit;
        }
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $brand = $model->find($id);
        $this->assign('brand',$brand);
        $this->display('_brand');
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {
        // 判断此品牌是否有商品在使用
        $goods_count = M('Goods')->where("brand_id = {$_GET['id']}")->count('1');
        if($goods_count)
        {
            $return_arr = array('status' => -1,'msg' => '此品牌有商品在用不得删除!','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn(json_encode($return_arr));
        }

        $model = M("Brand");
        $data['is_show'] = 0 ;
        $model->data($data)->where('id ='.$_GET['id'])->save();
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Store/Ueditor/imageUpText',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('Store/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('Store/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('Store/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('Store/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('Store/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", U('Store/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }



    /**
     * 商品规格列表
     */
    public function specList(){
        $condition['store_id'] = $_SESSION['merchant_id'];
        $goodsTypeList = M("GoodsType")->where($condition)->select();

        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }


    /**
     *  商品规格列表
     */
    public function ajaxSpecList(){
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索
        $model = D('spec');

        $where .= 'and store_id='.$_SESSION['merchant_id'];
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count,100);
        $show = $Page->show();
        $specList = $model->where($where)->order('`type_id` desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $GoodsLogic = new GoodsLogic();
        foreach ($specList as $k => $v) {       // 获取规格项
            $arr = $GoodsLogic->getSpecItem($v['id']);
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }

        $this->assign('specList',$specList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = M("GoodsType")->where(array('store_id'=>$_SESSION['merchant_id']))->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');

        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }
    /**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec(){
        $model = D("spec");
        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        if(($_GET['is_ajax'] == 1) && IS_POST)//ajax提交验证
        {
            C('TOKEN_ON',false);
            if(!$model->create(NULL,$type))// 根据表单提交的POST数据创建数据对象
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '',
                    'data'  => $model->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }else {
                // C('TOKEN_ON',true); //  form表单提交
                if ($type == 2)
                {
                    $model->save(); // 写入数据到数据库
                    $model->afterSave($_POST['id']);
                }
                else
                {
                    $insert_id = $model->add(); // 写入数据到数据库
                    $model->afterSave($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/specList')),
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
        $this->assign('spec',$spec);

        $goodsTypeList = M("GoodsType")->where(array('store_id'=>$_SESSION['merchant_id']))->select();
        $this->assign('store_id',$_SESSION['merchant_id']);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display('_spec');
    }


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('Spec')->field('id,name,type_id')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v){
            $specList[$k]['spec_item'] = D('SpecItem')->where("is_show = 1 and spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        }

        $items_id = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->field('key')->select();
        $count = count($items_id);
        $ids = null;
        for($i=0;$i<$count;$i++)
        {
            $ids = $ids.'_'. $items_id[$i]['key'];
        }
        $items_ids = explode('_', $ids);
        if(empty($items_ids[0]))
        {
            array_shift($items_ids);
        }
        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
        $GoodsLogic = new GoodsLogic();
        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        //print_r($_REQUEST);exit;
        $str = $GoodsLogic->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }

    public function Goodsindex()
    {
        $exclusive = M('exclusive')->select();
        $this->assign('exclusive',$exclusive);
        $this->display();
    }

    public function ajaxindex()
    {
        $store_id = $_SESSION['merchant_id'];
        $where = "is_show=1 and is_special=4 and store_id=$store_id ";
        I('exclusive') && $where = $where.' and exclusive_cat='.I('exclusive');

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
            if($_SESSION['is_haitao']==1){
                $cat_name = M('haitao')->where('`id`='.$goods[$i]['cat_id'])->field('name')->find();
            }else{
                $cat_name = M('goods_category')->where('`id`=' . $goods[$i]['cat_id'])->field('name')->find();
            }
            $exclusive_name = M('exclusive')->where('`id`='.$goods[$i]['exclusive_cat'])->field('name')->find();
            $goods[$i]['exclusive_name'] = $exclusive_name['name'];
            $goods[$i]['cat_name'] = $cat_name['name'];
            $goods[$i]['store_name'] = $name['store_name'];
        }
        if(!empty($haitao))
        {
            $haitao = $_SESSION['is_haitao'];
            $this->assign('haitao',$haitao);
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

    public function haitaogoodsList()
    {
        $categoryList = $this->getSortCategory();
        $this->assign('categoryList', $categoryList);
        $this->display();
    }

    /**
     *  获取排好序的分类列表
     */
    function getSortCategory()
    {
        $categoryList =  M("haitao")->getField('id,name,parent_id,level');
//        var_dump(M()->getLastSql());
        foreach($categoryList as $k => $v)
        {

            //$str_pad = str_pad('',($v[level] * 5),'-',STR_PAD_LEFT);
            $name = getFirstCharter($v['name']) .' '. $v['name']; // 前面加上拼音首字母
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
        array_multisort($nameList,SORT_STRING,SORT_ASC,$categoryList);

        return $categoryList;
    }

    /**
     *  商品列表
     */
    public function ajaxHaitaoGoodsList()
    {
//		$HaitaoLogic = new HaitaoLogic();
        $store_id = $_SESSION['merchant_id'];
        $where = "`show_type`=0  `is_special`=1 and `the_raise`=0 and store_id=$store_id  "; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
//		I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
//        (I('merchant_id') !=0) && $where = "$where and FIND_IN_SET(".I('merchant_id').',tp_merchant.id)';

        $cat_id = I('haitao_cat');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
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
    public function addEditHaitaoGoods()
    {

        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        $HaitaoLogic = new HaitaoLogic();
        $Goods = D('Goods'); //

        if(IS_POST)
        {
            $min_num = key($_POST['item']);
            $price = $_POST['item'][$min_num]['prom_price'];
            if($price==0||empty($price))
            {
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '规格价格没有正确填写',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if($_POST['cat_id']==0 || $_POST['cat_id_2']==0){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '有分类尚未选择，请检查后提交',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if($_POST['prom']<2){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '团购人数不能低于两人',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }


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
                    M('spec_goods_price')->where('`goods_id`='.$goods_id)->delete();
                    M('spec_image')->where('`goods_id`='.$goods_id)->delete();
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
                    $rdsname = "getGoodsDetails".$goods_id."*";
                    redisdelall($rdsname);//删除商品详情缓存
//					M('goods')->where('`goods_id`='.$goods_id)->save(array('cat_id'=>0,'haitao_cat'=>$_POST['cat_id_2']));
                }
                else
                {
                    $Goods->is_on_sale = 0 ;
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);

                }
                $HaitaoLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
                M('goods')->where('`goods_id`='.$goods_id)->save(array('cat_id'=>0,'haitao_cat'=>$_POST['cat_id_2']));
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/goodsList')),
                );

                $this->ajaxReturn(json_encode($return_arr));
            }
        }

        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
//		$cat_list = $HaitaoLogic->goods_cat_list(); // 已经改成联动菜单
        $level_cat = $HaitaoLogic->find_parent_cat($goodsInfo['haitao_cat']); // 获取分类默认选中的下拉框
        $cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $haitao_style = M('haitao_style')->select();
        $goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
        if(empty($goodsType))
            $goodsType = M("GoodsType")->where('`store_id`='.$_SESSION['merchant_id'])->select();
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
        $this->assign('haitao_style',$haitao_style);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_Haitao_goods');
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
        $this->assign('time', $time);
        $this->assign('date', $date);
        $this->display();
    }

    public function ajaxSeconds_kill_List()
    {
//		var_dump($_POST);
//		var_dump(strtotime(I('time')));die;
        $store_id = $_SESSION['merchant_id'];
        $where = "`is_special`=2 and `on_time`>0 and `is_show`=1 and `store_id`=$store_id ";
        I('store') && $where = $where . ' and `store_id`=' . I('store');
//		I('date') && $where = $where.' and ``'
        if (I('date') && I('time')) {
            $all_time = I('date') . ' ' . I('time');
            $where = $where . ' and `on_time`=' . strtotime($all_time);
        } elseif (I('time')) {
//            $times = strtotime(I('time'));
            $where = $where . " and FROM_UNIXTIME(`on_time`,'%H:%i:%S')='" . I('time')."'";
        } elseif (I('date')) {
            $times = strtotime(I('date'));
            $where = $where . ' and `on_time`>=' . $times . ' and `on_time`<' . ($times + 24 * 60 * 60);
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
            $cat_name = M('goods_category')->where('`id`=' . $goods[$i]['cat_id'])->field('name')->find();
            $goods[$i]['cat_name'] = $cat_name['name'];
        }
        $this->assign('goods', $goods);
        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }

    /**
     * 添加修改秒杀商品
     */
    public function addEditSecondsGoods()
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
                    M('spec_goods_price')->where('`goods_id`='.$goods_id)->delete();
                    M('spec_image')->where('`goods_id`='.$goods_id)->delete();
                    $Goods->save(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                    $this->prom_goods_save($_POST['date'],$_POST['time'],$goods_id);
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
                    'data'  => array('url'=>U('Store/Goods/Seconds_kill_goods')),
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
//        var_dump(date("Y-m-d",$goodsInfo['on_time']));
//		var_dump(date("H-i-s",$goodsInfo['on_time']));
//		var_dump($time[3]['time']);
//		var_dump(($time[4]['time'])==(date("H:i:s",$goodsInfo['on_time'])));
//		var_dump(('2016-11-09')==(date("Y-m-d",$goodsInfo['on_time'])));
//		var_dump($date[0]['date']);
//		var_dump($goodsInfo['on_time']);die;
//		$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
//        $brandList = $GoodsLogic->getSortBrands();
//        $merchantList = $GoodsLogic->getSortMerchant();
        $goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
//        $this->assign('brandList',$brandList);
//        $this->assign('merchantList',$merchantList);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_Secondskill_goods');
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

    /**
     * 添加修改商品
     */
    public function addEditJiujiuGoods(){

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

                if ($type == 2)
                {
                    $goods_id = $_POST['goods_id'];
                    M('spec_goods_price')->where('`goods_id`='.$goods_id)->delete();
                    M('spec_image')->where('`goods_id`='.$goods_id)->delete();
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
                    'data'  => array('url'=>U('Store/Goods/Goodsindex')),
                );

                $this->ajaxReturn(json_encode($return_arr));
                echo M()->getLastSql();die;
            }
        }

        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
        //$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = M("GoodsType")->where('`store_id`='.$goodsInfo['store_id'])->select();
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
        $this->assign('brandList',$brandList);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_Jiujiu_goods');
    }

    public function delete_goods()
    {
        $id =I('id');
        $is_show = M('goods')->where('`goods_id`='.$id)->field('is_show')->find();
        if ($is_show['is_show']==0) {
            $return_arr = array(
                'status' => -1,
                'msg' => '该商品已被删除',
                'data' => array('url' => U('Store/Goods/goods_list')),
            );
            $this->ajaxReturn(json_encode($return_arr));
        }
        $res = M('goods')->where('`goods_id`='.$id)->data(array('is_show'=>0))->save();
        if($res)
        {
            $return_arr = array(
                'status' => 1,
                'msg' => '删除成功',
                'data' => array('url' => U('Store/Goods/goods_list')),
            );
            $this->ajaxReturn(json_encode($return_arr));
        }
    }

    public function get_category_haitao(){
        $parent_id = I('get.parent_id'); // 商品分类 父id
        $list = M('haitao')->where("parent_id = $parent_id")->select();

        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
        exit($html);
    }
}