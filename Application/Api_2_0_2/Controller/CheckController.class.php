<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/22
 * Time: 下午3:45
 */

namespace Api_2_0_2\Controller;


class CheckController
{
    public function change(){
        $stime = time()-864000;
        $list = M('group_buy')->field('goods_num,id,order_id')
            ->where('`auto`=0 and 
                        `is_raise`=0 and 
                        `free`=0 and 
                        `is_dissolution`=0 and 
                        `is_pay`=1 and
                        `mark`=0 and 
                        `is_cancel`=0 and
                        `is_successful`=0 and 
                        `start_time`>=' . $stime)
            ->select();

        foreach($list as $key=>$value){
            $goods_num = (int)$value['goods_num'];
            $groupbuyid = (int)$value['id'];
            $tuanyuan = M('group_buy')->field('id,order_id')
                ->where("is_successful=0 and is_pay=1 and is_raise=0 and is_cancel=0 and is_return_or_exchange=0 and is_dissolution=0 and mark={$groupbuyid}")
                ->select();
            if(count($tuanyuan)+1>=$goods_num){
                echo $groupbuyid.'========<br>';
                /*
                $order_id = $value['order_id'];
                M('order')->where("order_id={$order_id} and order_type=11")->save(['order_status'=>11,'order_type'=>14]);
                M('group_buy')->where("id={$groupbuyid}")->save(['is_successful'=>1]);

                foreach($tuanyuan as $row){
                    $order_id = $row['order_id'];
                    $id = $row['id'];
                    M('order')->where("order_id={$order_id} and order_type=11")->save(['order_status'=>11,'order_type'=>14]);
                    M('group_buy')->where("id={$id}")->save(['is_successful'=>1]);
                }
                */

            }


        }
    }


}