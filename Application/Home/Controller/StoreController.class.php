<?php
namespace Home\Controller;
use Think\Page;
use Think\Verify;
class StoreController extends BaseController {

    /**
     * 商家入驻首页
     */
    public function index(){
        $this->display();
    }

    /**
     * 入驻第一步 --选择身份
     */
    public function join_first(){
        $this->display();
    }

    /**
     * 个人入驻  选择身份
     */
    public function join_second_person(){
        $from = $_GET['from'];

        $this->display();
    }

    /**
     * 个人入驻第二步提交
     */
    public function join_second_person_post($data){

        if(!$data['show_ower_idcard']){
            echo json_encode(array('status'=>0,'msg'=>'身份证不能为空'));
            die;
        }

        //检测身份证是不是已经超过三个店铺
        $count = M('store_detail')->where(array('show_ower_idcard'=>$data['show_ower_idcard']))->count();

        if($count >=3){
            echo json_encode(array('status'=>0,'msg'=>'一个身份证号只能申请三个店铺'));
            die;
        }

        if(M('merchant')->where(array('mobile'=>$data['show_ower_mobile']))->count()){
            echo json_encode(array('status'=>0,'msg'=>'此手机号已注册商铺'));
            die;
        }

        M()->startTrans();

        $store_data['merchant_name'] = $data['show_ower_mobile'];
        $store_data['password'] = md5(substr($data['show_ower_idcard'],-6));
        $store_data['store_name'] = $data['show_ower_name'];
        $store_data['add_time'] = time();
        $store_data['email'] = $data['show_ower_mail'];
        $store_data['mobile'] = $data['show_ower_mobile'];
        $store_data['state'] = 0;
        $store_data['is_show'] = 0;
        $store_data['store_from'] = 0;
        $store_data['store_type'] = 0;

        $res = M('merchant')->add($store_data);

        if(!$data['is_haitao'])
            $data['is_haitao'] = 0;

        $detail['storeid'] = $res;
        $detail['store_from'] = 0;
        $detail['is_haitao'] = $data['is_haitao'];
        $detail['show_ower_name'] = $data['show_ower_name'];
        $detail['show_ower_mail'] = $data['show_ower_mail'];
        $detail['show_ower_idcard'] = $data['show_ower_idcard'];
        $detail['show_ower_mobile'] = $data['show_ower_mobile'];
        $detail['idcard_starttime'] = $data['idcard_starttime'];
        $detail['idcard_endtime'] = $data['idcard_endtime'];
        $detail['idcard_img_1']  = $data['idcard_img_1'];
        $detail['idcard_img_2']  = $data['idcard_img_2'];
        $detail['idcard_img_3']  = $data['idcard_img_3'];
        $detail['idcard_img_4']  = $data['idcard_img_4'];
        $res2 = M('store_detail')->add($detail);

        if($res && $res2){
            M()->commit();
            return $res;
        }else{
            M()->rollback();
            return false;
        }
    }

    /**
     * 填写店铺信息
     */
    public function fill_store_info(){
        $this->display();
    }

    /**
     * 提交店铺信息
     */
    public function post_store_info(){
        $data = $_POST;
        $first_data = $data['first_data'];
        $first_data= $first_data[0];

        if($first_data['store_from'] == 1){
            $store_id =$this->join_second_company_post($first_data);
        }elseif($first_data['store_from'] == 0){
            $store_id =$this->join_second_person_post($first_data);
        }else{
            echo json_encode(array('status'=>0,'msg'=>'参数错误'));
            die;
        }

        if(!$store_id){
            echo json_encode(array('status'=>0,'msg'=>'信息提交失败！'));
            die;
        }

        unset($data['store_id']);

        $res = M('merchant')->where(array('id'=>$store_id))->save($data);

        if($res){
            echo json_encode(array('status'=>1,'msg'=>'店铺信息添加成功'));
            die;
        }else{
            echo json_encode(array('status'=>0,'msg'=>'店铺信息添加失败'));
            die;
        }
    }

    /**
     * 公司入驻  选择身份
     */
    public function join_second_company(){
        $store_type = $_GET['store_type'];
        $this->assign('store_type',$store_type);
        $this->display();
    }

    /**
     * 公司入驻第二步提交
     */
    public function join_second_company_post($data){

        M()->startTrans();

        if(!$data['show_ower_idcard']){
            echo json_encode(array('status'=>0,'msg'=>'身份证不能为空'));
            die;
        }
        //检测身份证是不是已经超过三个店铺
        $count = M('store_detail')->where(array('show_ower_idcard'=>$data['show_ower_idcard']))->count();

        if($count >=3){
            echo json_encode(array('status'=>0,'msg'=>'一个身份证号只能申请三个店铺'));
            die;
        }

        if(M('merchant')->where(array('mobile'=>$data['show_ower_mobile']))->count()){
            echo json_encode(array('status'=>0,'msg'=>'此手机号已注册商铺'));
            die;
        }

        $store_data['merchant_name'] = $data['show_ower_mobile'];
        $store_data['password'] = md5(substr($data['show_ower_idcard'],-6));
        $store_data['store_name'] = $data['company_name'];
        $store_data['add_time'] = time();
        $store_data['email'] = $data['show_ower_mail'];
        $store_data['mobile'] = $data['show_ower_mobile'];
        $store_data['state'] = 0;
        $store_data['is_show'] = 0;
        $store_data['store_from'] = 1;
        $store_data['store_type'] = $data['store_type'];
        $res = M('merchant')->add($store_data);

        if(!$data['is_haitao'])
            $data['is_haitao'] = 0;

        $detail['storeid'] = $res;
        $detail['store_from'] = 1;
        $detail['is_haitao'] = $data['is_haitao'];
        $detail['store_type'] = $data['store_type'];
        $detail['company_user_name'] = $data['company_user_name'];
        $detail['company_name'] = $data['company_name'];
        $detail['show_ower_mail'] = $data['show_ower_mail'];
        $detail['show_ower_idcard'] = $data['show_ower_idcard'];
        $detail['show_ower_mobile'] = $data['show_ower_mobile'];
        $detail['idcard_starttime'] = $data['idcard_starttime'];
        $detail['idcard_endtime'] = $data['idcard_endtime'];
        $detail['company_regiter_num'] = $data['company_regiter_num'];
        $detail['company_organ_code'] = $data['company_organ_code'];
        $detail['company_identi_code'] = $data['company_identi_code'];
        $detail['company_credit_code'] = $data['company_credit_code'];
        $detail['idcard_img_1']  = $data['idcard_img_1'];
        $detail['idcard_img_2']  = $data['idcard_img_2'];
        $detail['idcard_img_3']  = $data['idcard_img_3'];
        $detail['idcard_img_4']  = $data['idcard_img_4'];
        $detail['sbzm_imgs'] = json_encode($data['sbzm_imgs']);
        $detail['ppsq_imgs'] = json_encode($data['ppsq_imgs']);
        $detail['zjbg_imgs'] = json_encode($data['zjbg_imgs']);
        $detail['yyzz_img'] = json_encode($data['yyzz_img']);
        $detail['zzjg_img'] = json_encode($data['zzjg_img']);
        $detail['shxy_img'] = json_encode($data['shxy_img']);

        $res2 = M('store_detail')->add($detail);

        if($res && $res2){
            M()->commit();
           return $res;
        }else{
            M()->rollback();
            return false;
        }
    }

    /**
     * 入驻成功
     */
    public function join_success(){
        $this->display();
    }
}