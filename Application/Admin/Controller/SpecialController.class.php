<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/4/25
 * Time: 18:08
 *
 * 商品规格控制器
 */
namespace Admin\Controller;

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
                    'data'  => array('url'=>U('Admin/Special/specList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }
        }
        // 点击过来编辑时
        $id = $_GET['id'] ? $_GET['id'] : 0;
        $spec = $model->find($id);
        $GoodsLogic = new \Admin\Logic\GoodsLogic();
        $items = $GoodsLogic->getSpecItem($id);
        $spec[items] = implode(PHP_EOL, $items);
        $this->assign('spec',$spec);

        $goodsTypeList = M("GoodsType")->where(array('store_id'=>$_SESSION['merchant_id']))->select();
        $this->assign('store_id',$_SESSION['merchant_id']);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display('_spec');
    }

    /**
     * 获取商品规格 的筛选规格 复选框
     */
    public function ajaxGetSpecList(){
        $GoodsLogic = new \Admin\Logic\GoodsLogic();
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
        $specList = D('Spec')->field('id,name,type_id')->where("type_id = ".$_GET['spec_type']." AND is_show = 1")->order('`order` desc')->select();
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