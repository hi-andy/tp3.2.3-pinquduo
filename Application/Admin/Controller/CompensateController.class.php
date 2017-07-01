<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/6/30
 * Time: 15:15
 */

namespace Admin\Controller;


use Think\AjaxPage;
use Think\Controller;

class CompensateController extends Controller
{

    // 显示列表页
    public function index()
    {
        $this->display();
    }

    /**
     * ajax 获取列表数据
     */
    public function ajaxIndex()
    {
        $map = array();
        if ($order_sn = I('order_sn')) {
            $map['order_sn'] = $order_sn;
        }

        $count = M('compensate')->where($map)->count();
        $page = new AjaxPage($count, 15);
        $result = M('compensate')->field('id,order_sn,goods_price,bought_date,other_name,status,create_time')->where($map)->limit($page->firstRow, $page->listRows)->select();
        foreach ($result as &$value) {
            $value['bought_date'] = date('Y-m-d H:i:s', $value['bought_date']);
            $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $value['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
            $value['transformed_status'] = $this->statusTransform($value['status']);
        }

        $this->assign('data', $result);
        $this->assign('page', $page->show());
        $this->assign('order_sn', $order_sn);
        $this->display();
    }

    // 详情
    public function detail()
    {
        $id = I('id');
        $result = M('compensate')->where('id='.$id)->find();
        $result['bought_date'] = date('Y-m-d H:i:s', $result['bought_date']);
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        $result['update_time'] = date('Y-m-d H:i:s', $result['update_time']);
        $result['prove_pic']   = json_decode($result['prove_pic']);
        $prove_pics = array();
        foreach ($result['prove_pic'] as $value) {
            $prove_pics[] = $value->origin;
        }
        $result['transformed_status'] = $this->statusTransform($result['status']);
        $result['order_id']    = M('order')->where('order_sn='.$result['order_sn'])->getField('order_id');

        $this->assign('prove_pics', $prove_pics);
        $this->assign('data', $result);
        $this->display();
    }

    // 批量删除
    public function deleteBatch()
    {
        $data = I('post.');
        foreach ($data['id'] as $value) {
            $res = M('Compensate')->where('id='.$value)->delete();
        }
        if($res)
        {
            $this->success("删除成功",U('Compensate/index'));
        }else{
            $this->success("删除失败",U('Compensate/index'));
        }
    }

    // 申请状态操作
    public function setStatus()
    {
        $id = I('id');
        $status = I('status');
        M('compensate')->where('id='.$id)->setField('status', $status);
        $this->ajaxReturn('操作成功！');
    }

    //　格式化申请处理状态
    private function statusTransform($status)
    {
        $transformed = '';
        switch ($status){
            case 0 :
                $transformed = '未处理';
                break;
            case -1 :
                $transformed = '审核不通过';
                break;
            case 1 :
                $transformed = '已确认';
                break;
            case 2 :
                $transformed = '处理中';
                break;
            case 3 :
                $transformed = '处理完成';
                break;
            default :
        }
        return $transformed;
    }
}