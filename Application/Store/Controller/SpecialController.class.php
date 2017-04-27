<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/4/25
 * Time: 18:08
 *
 * 商品规格控制器
 */
namespace Store\Controller;

use Store\Logic\GoodsLogic;
use Think\AjaxPage;
class SpecialController extends BaseController
{

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
                    'data'  => array('url'=>U('Store/Special/specList')),
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
     * 商品规格列表
     */
    public function specList(){
        $condition['store_id'] = $_SESSION['merchant_id'];
        $goodsTypeList = M("GoodsType")->where($condition)->select();

        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }
    /**
     *  ajax 返回商品规格列表
     */
    public function ajaxSpecList(){
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' is_show=1 '; // 搜索条件
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
     * 删除商品规格
     */
    public function delSpecial()
    {
        // 判断 商品规格项
        $id = I('id');
        $store_id = $_SESSION['merchant_id'];
        $count = M("SpecItem")->where("spec_id = $id and is_show = 1")->count("1");
        echo $store_id;exit;
        if ($count > 0 ) {
            //$this->error('清空规格项后才可以删除!',U('Store/Goods/specList'));
            $data[is_show] = 0;
            M('Spec')->where("id = $id AND store_id = $store_id")->save($data);
            M('SpecItem')->where('spec_id = '.$id)->save($data);
        } else {
            //删除分类 将它下面的所有规格删除
            $id = M('spec')->where("id = $id AND store_id = $store_id")->find();
            M('SpecItem')->where('spec_id = '.$id['id'])->delete();
            M('spec')->where("id = ".$id['id'])->delete();
        }
        $this->success("操作成功!!!",U('Store/Special/specList'));
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
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
        $GoodsLogic = new GoodsLogic();
        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        //print_r($_REQUEST);exit;
        $str = $GoodsLogic->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
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

}