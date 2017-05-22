<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace Store\Model;
use Think\Model;
class SpecModel extends Model {
    //protected $tablePrefix = 'tp_'; 
    protected $patchValidate = true; // 系统支持数据的批量验证功能，
    protected $items = '';
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
        array('name','require','规格名称必须填写！',1 ,'',3),         
        array('type_id','require','商品类型必须选择！',1 ,'',3),
        array('items','require','规格项不能为空！',1 ,'',1), // 编辑的时候可以为空  才可以删除规格
        array('order','number','排序必须为数字！',2,'',3), //                
     );
    
   /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $id 规格id
     */
    public function afterSave($id)
    {
        
        $model = M("SpecItem"); // 实例化User对象
        $post_items = explode(PHP_EOL, $_POST['items']);        
        foreach ($post_items as $key => $val)  // 去除空格
        {
            $val = str_replace('_', '', $val); // 替换特殊字符
            $val = str_replace('@', '', $val); // 替换特殊字符
            
            $val = trim($val);
            if(empty($val)) 
                unset($post_items[$key]);
            else
                $post_items[$key] = $val;
        }
//        $model->where("spec_id = $id")->delete();

        $all = $model->where("spec_id = $id and is_show = 1")->select();
        $all_num = count($all);
        $new_num = count($post_items);
        if($all_num>$new_num) {
            for ($i = 0; $i < $all_num; $i++) {
                for ($j = 0; $j < $new_num; $j++) {
                    if ($all[$i]['item'] == $post_items[$j]) {
                        unset($all[$i]);
                        break;
                    }
                }
            }
            $all = array_merge($all);
            for($k=0;$k<count($all);$k++)
            {
                $model->where('id = '.$all[$k]['id'])->save(array('is_show'=>0));
            }
        }elseif($all_num==$new_num)
        {
            for ($i = 0; $i < $new_num; $i++) {
                for ($j = 0; $j < $all_num; $j++) {
                    if ($post_items[$i] == $all[$j]['item']) {
                        unset($post_items[$i]);
                        break;
                    }
                }
            }
            $post_items = array_merge($post_items);
            for($k=0;$k<count($post_items);$k++)
            {
                $model->add(array('spec_id'=>$id,'item'=>$post_items[$k]));
            }
        }elseif($all_num<$new_num)
        {
            for ($i = 0; $i < $new_num; $i++) {
                for ($j = 0; $j < $all_num; $j++) {
                    if ($post_items[$i] == $all[$j]['item']) {
                        unset($post_items[$i]);
                        break;
                    }
                }
            }
            $post_items = array_merge($post_items);
            for($k=0;$k<count($post_items);$k++)
            {
                $model->add(array('spec_id'=>$id,'item'=>$post_items[$k]));
            }
        }
        // 两边 比较两次
        // 批量添加数据

//        foreach($post_items as $v)
//        {
//            foreach($all as $value)
//            {
//                if(!in_array($v['item'],$all))
//                {
//                    $data['spec_id']=$id;
//                    $data['item']=$v ;
//                    $sp = M('SpecItem')->add($data);
//                    $data['item']=null;
//                }
//            }
//        }
//
//        /* 数据库中的 跟提交过来的比较 不存在删除*/
//        foreach($db_items as $key => $val)
//        {
//            if(!in_array($val, $post_items))
//            {
//                //  SELECT * FROM `tp_spec_goods_price` WHERE `key` REGEXP '^11_' OR `key` REGEXP '_13_' OR `key` REGEXP '_21$'
//                M("SpecGoodsPrice")->where("`key` REGEXP '^{$key}_' OR `key` REGEXP '_{$key}_' OR `key` REGEXP '_{$key}$'")->delete(); // 删除规格项价格表
//                $model->where('id='.$key)->delete(); // 删除规格项
//            }
//        }
    }    
}