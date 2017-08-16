<?php
namespace Admin\Model;
use Think\Model;
class CouponCategoryModel extends Model {
    //protected $tablePrefix = 'tp_';
//    protected $patchValidate = true; // 系统支持数据的批量验证功能，
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

    private $category=[
        [
            "id"=>1,
            "name"=>"平台优惠券",
            "level"=>1,
            "child"=>[]
        ],
        [
            "id"=>2,
            "name"=>"店铺优惠券",
            "level"=>1,
            "child"=>[]
        ],
        [
            "id"=>3,
            "name"=>"活动优惠券",
            "level"=>1,
            "child"=>[]
        ],
        [
            "id"=>4,
            "name"=>"商品优惠券",
            "level"=>1,
            "child"=>[]
        ],
    ];

    protected $_validate = array(
    );

    /**
     * 获取 分类树状结构
     * @return array
     */
    public function getTree(){
        foreach ($this->category as $k=>$v){
            $this->category[$k]["child"]=$this->where(["pid"=>$v["id"]])->field("id,name")->select();
        }
        return $this->category;
    }

}
