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
            setcookie('storeid',null);
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

        $where = ' g.goodstatus = 2 and g.show_type = 0 '. ' and g.store_id = '.$_SESSION['merchant_id'] ; // 搜索条件
        if ($intro = I('intro')){
            $where .= " and g.intro like %$intro%'";
        }
        I('is_on_sale') != null && $where .= " and `g.is_on_sale`= ".I('is_on_sale') ;
        (I('merchant_id') !=0) && $where .= " and FIND_IN_SET(".I('merchant_id').',tp_merchant.id)';

        if($_REQUEST['is_audit']!=''){
            $where .= " and g.is_audit = ".$_REQUEST['is_audit'];
        }
        if(!empty(I('store_name')))
        {
            $this->assign('store_name', I('store_name'));
            $where = $this->getStoreWhere($where,I('store_name'));
        }

        if($_SESSION['is_haitao']==1)
        {
            if(I('cat_id_2')){
                $where .= " and haitao_cat =".I('cat_id_2');
            }elseif(I('cat_id_1')){
                $cat = M('haitao')->where('`parent_id`='.I('cat_id_1'))->field('id')->select();
                $cats =null;
                $num = count($cat);
                for($i=0;$i<$num;$i++){
                    if($i==$num-1){
                        $cats = $cats."'".$cat[$i]['id']."')";
                    }elseif($i==0){
                        $cats = $cats."('".$cat[$i]['id']."',";
                    }else{
                        $cats = $cats."'".$cat[$i]['id']."',";
                    }
                }
                $where .= " and g.haitao_cat IN $cats";
            }
        }else{
            if(I('cat_id_2')){
                $cat_id = M('GoodsCategory')->where('`parent_id`='.I('cat_id_2'))->field('id')->select();
                $cats =null;
                $num = count($cat_id);
                for($i=0;$i<$num;$i++){
                    if($i==$num-1){
                        $cats = $cats."'".$cat_id[$i]['id']."')";
                    }elseif($i==0){
                        $cats = $cats."('".$cat_id[$i]['id']."',";
                    }else{
                        $cats = $cats."'".$cat_id[$i]['id']."',";
                    }
                }
                $where .= " and g.cat_id IN $cats";
            }elseif(I('cat_id_1')){
                $cat1 = M('GoodsCategory')->where('`parent_id`='.I('cat_id_1'))->field('id')->select();
                $cat2['parent_id'] = array('IN',array_column($cat1,'id'));
                $cat = M('GoodsCategory')->where($cat2)->field('id')->select();
                $cats =null;
                $num = count($cat);
                for($i=0;$i<$num;$i++){
                    if($i==$num-1){
                        $cats = $cats."'".$cat[$i]['id']."')";
                    }elseif($i==0){
                        $cats = $cats."('".$cat[$i]['id']."',";
                    }else{
                        $cats = $cats."'".$cat[$i]['id']."',";
                    }
                }
                $where .= " and g.cat_id IN $cats";
            }
        }

        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word){
            $where = "$where and (g.goods_name like '%$key_word%')" ;
        }

        $model = M('Goods');
        $count = $model->alias('g')->join('left join tp_goods_activity ga on ga.goods_id = g.goods_id')->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";

        /**
         * 关联查询 goods_activity 表，取出参加活动商品的类型 type
         * 如果 type=4 五折专享活动，限制编辑商品
         */
        if(!empty($cats)){
            $goodsList = $model->alias('g')
                                ->field('g.goods_id,g.goods_name,g.is_special,g.cat_id,g.shop_price,g.store_count,g.is_on_sale,g.is_show,is_audit,ga.type activity_type')
                                ->join('left join tp_goods_activity ga on ga.goods_id = g.goods_id')
                                ->where($where)
                                ->order($order_str)
                                ->limit($Page->firstRow,$Page->listRows)
                                ->select();
        }else{
            $goodsList = $model->alias('g')
                                ->field('g.goods_id,g.goods_name,g.is_special,g.cat_id,g.shop_price,g.store_count,g.is_on_sale,g.is_show,is_audit,ga.type activity_type')
                                ->join('left join tp_goods_activity ga on ga.goods_id = g.goods_id')
                                ->where($where)
                                ->order($order_str)
                                ->limit($Page->firstRow,$Page->listRows)
                                ->select();
        }
        $haitao = $_SESSION['is_haitao'];
        if(!empty($haitao)){
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
     * 修改：单买价格不得低于团购价格  2017/07/05 刘亚豪
     */
    public function addEditGoods(){
        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        // 五折专享活动商品，禁止编辑
        if ($goods_id = I('id')) {
            $isExist = M('goods_activity')->where('goods_id='.$goods_id.' and type=4')->count();
            if ($isExist) {
                $this->error("商品处于活动状态，禁止编辑！",U('Store/Goods/goodsList'));
            }
        }
        $GoodsLogic = new GoodsLogic();
        $Goods = D('Goods'); //
        if(IS_POST)
        {
            $min_num = key($_POST['item']);
            $price = $_POST['item'][$min_num]['price'];
            $prom_price = $_POST['item'][$min_num]['prom_price'];
            if($price==0||empty($price))
            {
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '规格价格没有正确填写',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }

            if($prom_price > $price){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '团购价格不能高于单买价格',
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
                    'msg'   => $_POST['goods_images'][0].'请上传商品轮播图！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if(empty($_POST['goods_content'])){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '请填写商品详细描述！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
//            if(empty($_POST['goods_content'][0])){
//                $return_arr = array(
//                    'status' => -1,
//                    'msg'   => '请上传商品详细描述图片！',
//                    'data'  => $Goods->getError(),
//                );
//                $this->ajaxReturn(json_encode($return_arr));
//            }
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
//                $Goods->on_time = time(); // 上架时间
                //$Goods->cat_id = $_POST['cat_id_1'];
                $_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
                $_POST['cat_id_3'] && ($Goods->cat_id = $_POST['cat_id_3']);

                //详情图片
//                $Goods->goods_content = null;
//                $goodscontent = "";
//                foreach ($_POST['goods_content'] as $v){
//                    $goodscontent .= '<img src="'.$v.'">';
//                }
//                $goodscontent = str_replace('<img src="">','',$goodscontent);
//                $Goods->goods_content = $goodscontent;
                if ($type == 2)
                {
                    $Goods->refresh = 0 ;
                    $Goods->goodstatus = 2;
                    $goods_id = $_POST['goods_id'];
                    $goods = M('goods')->where("goods_id = $goods_id")->find();
                    // 如果上传新图，删除旧图
                    if($_POST['original_img']!=$goods['original_img'])
                    {
                        $link =  C('DATA_URL').goods_thum_images($_POST['goods_id'],400,400);
                        $res = unlink($link);
                        $link1 = C('DATA_URL').$goods['original_img'];
                        $res1 = unlink($link1);
                    }
                    if($_POST['list_img']!=$goods['list_img'])
                    {
                        $llink =  C('DATA_URL').goods_thum_images($_POST['goods_id'],640,300);
                        $res = unlink($llink);
                        $llink1 = C('DATA_URL').$goods['list_img'];
                        $res1 = unlink($llink1);
                    }
                    $Goods->save(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                }else{
                    $Goods->goodstatus = 2;
                    $Goods->is_on_sale = 0 ;    // 默认
                    $Goods->is_show = 0 ;
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                }
                redisdelall("getDetaile_".$goods_id);

                $GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/goodsList')),
                );

                $this->ajaxReturn(json_encode($return_arr));

            }
        }
        if(!empty(I('GET.id'))) {
            $goodsInfo = D('Goods')->where('goods_id=' . I('GET.id'))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框



            if (!empty($haitao)) {
                $haitao_style = M('haitao_style')->select();
                $this->assign('haitao', $haitao);
                $this->assign('haitao_style', $haitao_style);
            }
            $level_cat = array_merge($level_cat);
            $level_cat = array_reverse($level_cat, TRUE);
            array_unshift($level_cat, array('id' => '0', 'name' => 'null'));
            $this->assign('goodsContent',getImgs($goodsInfo['goods_content']));
            $this->assign('level_cat',$level_cat);

            $this->assign('goodsInfo',$goodsInfo);  // 商品详情
            $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id'))->select();
            $this->assign('goodsImages',$goodsImages);  // 商品相册
        }
        $goodsType = M("GoodsType")->where('`store_id`=' . $_SESSION['merchant_id'])->select();
        $haitao = $goodsInfo['is_special'];
        if ($haitao == 1) {
            $cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单
        } else {
            $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        }
        $this->assign('cat_list',$cat_list);
        $this->assign('goodsType',$goodsType);
        $this->initEditor(); // 编辑器 //
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
        $count = M("Spec")->where("type_id = $id and is_show=1")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',U('Store/Goods/goodsTypeList'));
        // 删除分类
        $spec_id_arr = M('spec')->where('is_show = 0 and type_id = '.$id )->delete();
        M('GoodsType')->where("id = $id")->delete();
        $this->success("操作成功!!!",U('Store/Goods/goodsTypeList'));
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
        foreach($categoryList as $k => $v){
            $name = getFirstCharter($v['name']) .' '. $v['name']; // 前面加上拼音首字母
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
        $store_id = $_SESSION['merchant_id'];
        $where = "`show_type`=0 and `is_special`=1 and `the_raise`=0 and store_id=$store_id  "; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;

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
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow,$Page->listRows)
            ->join('tp_merchant ON tp_merchant.id = tp_goods.store_id')
            ->field('tp_goods.*,tp_merchant.store_name')
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
        if(!empty(I('parent_id'))){
            $parent_id = I('get.parent_id'); // 商品分类 父id
            $list = M('haitao')->where("parent_id = $parent_id")->select();
            $html = '';
            foreach($list as $k => $v)
                $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
            exit($html);
        }
        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        $HaitaoLogic = new HaitaoLogic();
        $Goods = D('Goods');
        if(IS_POST)
        {
            $min_num = key($_POST['item']);
            $price = $_POST['item'][$min_num]['price'];
            $prom_price = $_POST['item'][$min_num]['prom_price'];
            if($prom_price==0||empty($prom_price))
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
            if(empty($_POST['goods_images'])){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '请上传商品轮播图！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if($prom_price > $price){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '单买价格不得低于团购价格',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
            if(empty($_POST['goods_content'])){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '请填写商品详细描述！',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        $_POST['haitao_cat'] = $_POST['cat_id'];
        $_POST['category_id'] = $_POST['cat_id_2'];
        $type = $_POST['goods_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(($_GET['is_ajax'] == 1) && IS_POST)
        {
            C('TOKEN_ON',false);
            if(!$Goods->create(NULL,$type)){// 根据表单提交的POST数据创建数据对象
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '操作失败',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }else{
                //  form表单提交
                // C('TOKEN_ON',true);
                $Goods->on_time = time(); // 上架时间
                $_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
                session('goods',$_POST);
                $Goods->goodstatus = 2 ;
                if ($type == 2){
                    $Goods->refresh = 0 ;
                    $Goods->goodstatus = 2;
                    $goods_id = $_POST['goods_id'];
                    M('spec_goods_price')->where('`goods_id`='.$goods_id)->delete();
                    M('spec_image')->where('`goods_id`='.$goods_id)->delete();
                    $goods = M('goods')->where("goods_id = $goods_id")->find();
                    // 如果上传新图，删除旧图
                    if($_POST['original_img']!=$goods['original_img']){
                        $link =  C('DATA_URL').goods_thum_images($_POST['goods_id'],400,400);
                        $res = unlink($link);
                        $link1 = C('DATA_URL').$goods['original_img'];
                        $res1 = unlink($link1);
                    }
                    if($_POST['list_img']!=$goods['list_img'])
                    {
                        $llink =  C('DATA_URL').goods_thum_images($_POST['goods_id'],640,300);
                        $res = unlink($llink);
                        $llink1 = C('DATA_URL').$goods['list_img'];
                        $res1 = unlink($llink1);
                    }
                    $Goods->save(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
//                    redislist("goods_refresh_id", $goods_id);
                    redisdelall("getDetaile_".$goods_id);
                }else{
                    $Goods->is_on_sale = 0 ;
                    $Goods->is_show = 0;
                    $Goods->goodstatus = 2;
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                }
                redislist("goods_refresh_id", $goods_id);
                $HaitaoLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性
                M('goods')->where('`goods_id`='.$goods_id)->save(array('cat_id'=>0,'haitao_cat'=>$_POST['cat_id_2']));
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>U('Store/Goods/haitaogoodsList')),
                );

                $this->ajaxReturn(json_encode($return_arr));
            }
        }

        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id'))->find();
        $level_cat = $HaitaoLogic->find_parent_cat($goodsInfo['haitao_cat']); // 获取分类默认选中的下拉框
        $cat_list = M('haitao')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $haitao_style = M('haitao_style')->select();
        $goodsType = M("GoodsType")->where('`store_id`='.$_SESSION['merchant_id'])->select();
        $this->assign('level_cat',$level_cat);
        $this->assign('cat_list',$cat_list);
        if(!empty(I('GET.id'))){
            $cat_list2 = M('haitao')->where("parent_id = ".$goodsInfo['haitao_cat'])->select();
            $this->assign('cat_list2',$cat_list2);
        }
        $this->assign('haitao_style',$haitao_style);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id'))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_Haitao_goods');
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
        if($res){
            $return_arr = array(
                'status' => 1,
                'msg' => '删除成功',
                'data' => array('url' => U('Store/Goods/goods_list')),
            );
            $this->ajaxReturn(json_encode($return_arr));
        }
    }

    function addeditSecondskill()
    {
        {
            $GoodsLogic = new GoodsLogic();
            $Goods = D('Goods'); //
            $type = $_POST['goods_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
            //ajax提交验证
            $_POST['refresh'] = 0;
            if (($_GET['is_ajax'] == 1) && IS_POST) {
                C('TOKEN_ON', false);
                if (!$Goods->create(NULL, $type))// 根据表单提交的POST数据创建数据对象
                {
                    //  编辑
                    $return_arr = array(
                        'status' => -1,
                        'msg' => '操作失败',
                        'data' => $Goods->getError(),
                    );
                    $this->ajaxReturn(json_encode($return_arr));
                } else {
                    //  form表单提交
                    // C('TOKEN_ON',true);
                    $Goods->on_time = time(); // 上架时间
                    $_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
                    $_POST['cat_id_3'] && ($Goods->cat_id = $_POST['cat_id_3']);
                    session('goods', $_POST);

                    if ($type == 2) {
                        $goods_id = $_POST['goods_id'];
                        $goods = M('goods')->where("goods_id = $goods_id")->find();
                        if ($_POST['original_img'] != $goods['original_img']) {
                            $link = C('DATA_URL') . goods_thum_images($_POST['goods_id'], 400, 400);
                            $res = unlink($link);
                            $link1 = C('DATA_URL') . $goods['original_img'];
                            $res1 = unlink($link1);
                        }
                        $Goods->save(); // 写入数据到数据库
                        $Goods->afterSave($goods_id);
                        $this->prom_goods_save($_POST['date'], $_POST['time'], $goods_id);
                        $rdsname = "getDetaile_" . $goods_id;
                        redisdelall($rdsname);//删除商品详情缓存
                    } else {
                        $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                        $Goods->afterSave($goods_id);
                        $this->prom_goods_save($_POST['date'], $_POST['time'], $goods_id);
                    }

                    $GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type']); // 处理商品 属性

                    $return_arr = array(
                        'status' => 1,
                        'msg' => '操作成功',
                        'data' => array('url' => U('Admin/Secondskill/Seconds_kill_goods')),
                    );
                    $this->ajaxReturn(json_encode($return_arr));
                }
            }

            for ($i = 0; $i < 5; $i++) {
                $date[$i]['id'] = $i + 1;
                $date1 = time();
                if ($i == 0) {
                    $day = $date1 - (24 * 60 * 60 * 2);
                    $date[$i]['date'] = date("Y-m-d", $day);
                } elseif ($i == 1) {
                    $day = $date1 - (24 * 60 * 60);
                    $date[$i]['date'] = date("Y-m-d", $day);
                } elseif ($i == 2) {
                    $date[$i]['date'] = date("Y-m-d", time());
                } elseif ($i == 3) {
                    $day = $date1 + (24 * 60 * 60);
                    $date[$i]['date'] = date("Y-m-d", $day);
                } else {
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
            $goodsInfo = D('Goods')->where('goods_id=' . I('GET.id', 0))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
            $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            //$brandList = $GoodsLogic->getSortBrands();
            $merchantList = $GoodsLogic->getSortMerchant();
            $goodsType = M("GoodsType")->where('`store_id`=' . $goodsInfo['store_id'])->select();
            if (empty($goodsType))
                $goodsType = M("GoodsType")->select();
            $this->assign('level_cat', $level_cat);
            $this->assign('cat_list', $cat_list);
            //$this->assign('brandList',$brandList);
            $this->assign('merchantList', $merchantList);
            $this->assign('goodsType', $goodsType);
            $this->assign('goodsInfo', $goodsInfo);  // 商品详情
            $goodsImages = M("GoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
            $this->assign('goodsImages', $goodsImages);  // 商品相册
            $this->initEditor(); // 编辑器
            $this->display('_Secondskill_goods');
        }
    }
}