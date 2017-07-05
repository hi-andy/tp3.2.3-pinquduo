<?php
/**
 *
 * 补差价接口控制器
 */

namespace Api_2_0_1\Controller;

use Admin\Controller\QiniuController;
use Think\Controller;

class CompensateController extends Controller
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = D('compensate');
    }

    /**
     * 用户提交补差价申请接口
     */
    public function apply()
    {
        C('TOKEN_ON', false);
        header("Access-Control-Allow-Origin:*");
        $data['order_sn']       = I('order_sn');
        $data['user_id']        = I('user_id');
        $data['goods_price']    = I('goods_price', 0.00);
        $data['bought_date']    = strtotime(I('bought_date'));
        $data['other_name']     = I('other_name');
        $data['other_price']    = I('other_price', 0.00);
        $data['other_date']     = strtotime(I('other_date'));
        $data['mobile']         = I('mobile');
        $data['qq']             = I('qq');
        $data['alipay']         = I('alipay');

        // 订单是否存在,　并且为已确认收货状态。
        $existOrder = M('order')->where('order_sn='.$data['order_sn'].' and (confirm_time>0 or automatic_time>0)')->find();
        if (!$existOrder) {
            exit(json_encode(array('code'=>0, 'msg'=>'此订单不存在或暂不能提交补差价申请！')));
        }
        //　同一订单号的申请记录是否已存在
        $record = $this->model->where('order_sn='.$data['order_sn'])->find();
        if ($record) {
            exit(json_encode(array('code'=>0, 'msg'=>'此订单已提交过申请，请不要重复提交！')));
        }
        //　是否已超过可提交申请时间限制
        $sevenDay = 7 * 24 * 3600;
        if ((time()-$sevenDay) > $existOrder['add_time']) {
            exit(json_encode(array('code'=>0, 'msg'=>'您的订单已超过7日可申请日期')));
        }
        //　本平台价格是否大于其它平台价
        if ($data['other_price'] > $data['goods_price']) {
            exit(json_encode(array('code'=>0, 'msg'=>'其它平台购买价格必须要低于本平台价格')));
        }

        // 处理图片上传
        $image_arr = array();
        if($_FILES['picture']){
            $imgUpload = new QiniuController();
            $image_arr = $imgUpload->upload();
        }
        $data['prove_pic'] = json_encode($image_arr);

        // 入库
        if ($data = $this->model->create($data)) {
            $this->model->add($data);
            $json = array('code'=>200, 'msg'=>'提交申请成功，请等待审核处理');
        } else {
            $json = array('code'=>0, 'msg'=>$this->model->getError());
        }

        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     * 用户中心显示，申请列表
     */
    public function applyList()
    {
        if ($user_id = I('user_id')) {

            $page = I('page',1);
            $pageSize = I('pageSize',10);

            $count = $this->model->where('user_id='.$user_id)->count();
            $result = $this->model->field('id,order_sn,goods_price,bought_date,other_name,status,create_time')->where('user_id='.$user_id)->page($page, $pageSize)->select();
            foreach ($result as &$value) {
                $value['bought_date'] = date('Y-m-d H:i:s', $value['bought_date']);
                $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                $value['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
                $value['status'] = $this->statusTransform($value['status']);
            }
            $result = $this->listPageData($count, $result);
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $result);
        } else {
            $json = array('status' => 0, 'msg' => '您未登录，请先登录', 'result' => '');
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
    }

    /**
     * 申请记录详情
     */
    public function detail()
    {
        if ($user_id = I('user_id')) {
            $id = I('id', 0);
            $result = $this->model->where('id='.$id.' and user_id='.$user_id)->find();
            $result['bought_date']  = date('Y-m-d H:i:s', $result['bought_date']);
            $result['create_time']  = date('Y-m-d H:i:s', $result['create_time']);
            $result['update_time']  = date('Y-m-d H:i:s', $result['update_time']);
            $result['status']       = $this->statusTransform($result['status']);
            $json = array('status' => 1, 'msg' => '获取成功', 'result' => $result);
        } else {
            $json = array('status' => 0, 'msg' => '您未登录，请先登录', 'result' => '');
        }
        I('ajax_get') &&  $ajax_get = I('ajax_get');//网页端获取数据标示
        if(!empty($ajax_get))
            $this->getJsonp($json);
        exit(json_encode($json));
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

    // h5数据返回封装
    public function getJsonp($data)
    {
        $b = json_encode($data);
        echo "{$_GET['jsoncallback']}({$b})";
        exit;
    }

    /**
     * 数据分页处理模型
     * @param int $total
     * @param array $items
     * @return array
     * author Fox
     */
    function listPageData($total=0,$items=array(),$pagesize=null) {
        if(empty($pagesize)){
            $pagesize = I('request.pagesize', C('PAGE_SIZE'), 'intval');
        }
        $totalpage = ceil($total/$pagesize);
        $currentpage = I('request.page', 1, 'intval');
        if( I('request.page')==0){
            $currentpage = 1;
        }
        if(empty($items))
        {
            $items=array();
        }
        if(empty($total))
        {
            $total = 0;
        }
        $currentpage = max(1, $currentpage);
        $currentpage = min($currentpage, $totalpage);
        $nextpage = min($currentpage+1, $totalpage);
        return  compact('total', 'totalpage', 'pagesize', 'currentpage', 'nextpage', 'items');
    }
}