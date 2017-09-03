<?php
namespace Admin\Model;
use Think\Model;
class GoodsModel extends Model {
    protected $patchValidate = true; // 系统支持数据的批量验证功能，
    /**
     *     
        self::EXISTS_VALIDATE 或者0 存在字段就验证（默认）
        self::MUST_VALIDATE 或者1 必须验证
        self::VALUE_VALIDATE或者2 值不为空的时候验证
     * 
     * 
        self::MODEL_INSERT或者1新增数据时候验证
        self::MODEL_UPDATE或者2编辑数据时候验证
        self::MODEL_BOTH或者3全部情况下验证（默认）       
     */
    protected $_validate = array(
        array('goods_name','require','商品名称必须填写！',1 ,'',3),
        array('prom','require','团购人数必须填写！',1,'',3),
        array('shop_price','/\d{1,10}(\.\d{1,2})?$/','本店售价格式不对。',2,'regex'),
        array('prom','/\d{1,10}(\.\d{1,2})?$/','开团人数格式不对。',2,'regex')
     );



    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id)
    {
        // 商品货号
        $goods_sn = "TP".str_pad($goods_id,7,"0",STR_PAD_LEFT);
        $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录

        // 商品图片相册  图册
        if(count($_POST['goods_images']) > 1)
        {
            $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片

            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $_POST['goods_images']))
                    M('GoodsImages')->where("img_id = {$key}")->delete(); // 删除所有状态为0的用户数据
            }
            // 添加图片
            foreach($_POST['goods_images'] as $key => $val)
            {
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array(
                        'goods_id' => $goods_id,
                        'image_url' => $val,
                    );
                    M("GoodsImages")->data($data)->add();; // 实例化User对象
                }
            }
        }


        // 商品规格价钱处理
        if($_POST['item'])
        {
            $specGoodsPrice = M("SpecGoodsPrice"); // 实例化 商品规格 价格对象
            $specGoodsPrice->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象

            //将上商品的规格价格调试为APP选项的价格
            //筛选出最低的价格
            //如果勾选了批量填写就将某个价格锁定不进行筛选了
            $min_num = key($_POST['item']);
            $min_prom = $_POST['item'][$min_num]['prom_price'];
            $min_show = $_POST['item'][$min_num]['price'];

            if($_POST['price']=='on')
            {
                foreach($_POST['item'] as $k=>$v)
                {
                    $_POST['item'][$k]['price'] = $min_show;
                }
                $d['shop_price'] = $min_show;
            }else{
                foreach($_POST['item'] as $k=>$v)
                {
                    if($v['price']<$min_show){
                        $min_show=$v['price'];
                    }
                }
                $d['shop_price'] = $min_show;
            }
            if($_POST['prom_price']=='on')
            {
                foreach($_POST['item'] as $k=>$v)
                {
                    $_POST['item'][$k]['prom_price'] =$min_prom;
                }
                $d['prom_price'] = $min_prom;
            }else{
                foreach($_POST['item'] as $k=>$v)
                {
                    if($v['prom_price']<$min_prom){
                        $min_prom=$v['prom_price'];
                    }
                }
                $d['prom_price'] = $min_prom;
            }
            if($_POST['count']=='on')
            {
                $count = $_POST['item'][$min_num]['store_count'];
                foreach($_POST['item'] as $k=>$v)
                {
                    $_POST['item'][$k]['store_count'] =$count;
                }
            }

            foreach($_POST['item'] as $k => $v)
            {
                // 批量添加数据
                $v['price'] = trim($v['price']);
                $v['prom_price'] = trim($v['prom_price']);
                $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                $dataList[] = array('goods_id'=>$goods_id, 'img'=>isset($v['img'])?$v['img']:'', 'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'prom_price'=>$v['prom_price'],'is_del'=>0,'store_count'=>$v['store_count']);
            }

            M('admin_log')->data(array('admin_id'=>1,'log_url'=>json_encode($dataList)))->add();
            //规格名称
            $keys = array_keys($_POST['item']);
            for($i=0;$i<count($_POST['item']);$i++)
            {
                $key_word = explode('_',$keys[$i]);
                $key_name = null;
                for($j=0;$j<count($key_word);$j++)
                {
                    $Item= M('SpecItem')->where('`id`='.$key_word[$j])->find();
                    $spec = M('spec')->where('`id`='.$Item['spec_id'])->find();
                    $name[] = $spec['name'].':'.$Item['item'].' ';
                }
                for($z=0;$z<count($name);$z++)
                {
                    $key_name = $key_name.$name[$z];
                }
                $dataList[$i]['key_name'] = $key_name;

                $name=null;
            }

            $specGoodsPrice->addAll($dataList);
            //M('Goods')->where("goods_id = 1")->save(array('store_count'=>10)); // 修改总库存为各种规格的库存相加
        }

        $res = M('goods')->where('goods_id='.$goods_id)->data($d)->save();

        // 商品规格图片处理
        if($_POST['item_img'])
        {
            M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($_POST['item_img'] as $key => $val)
            {
                M('SpecImage')->data(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val))->add();
            }
        }
        refresh_stock($goods_id); // 刷新商品库存
    }
}
