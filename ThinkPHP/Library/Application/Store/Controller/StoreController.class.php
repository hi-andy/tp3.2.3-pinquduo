<?php
/**
 * 商户管理
 */
namespace Store\Controller;
use Think\AjaxPage;

class StoreController extends BaseController{

    /*
     * 初始化操作
     */
    public function _initialize() {
        if(empty($_SESSION['merchant_id']))
        {
            session_unset();
            session_destroy();
            $this->error("登录超时或未登录，请登录",U('Store/Admin/login'));
        }
        $haitao = M('store_detail')->where('storeid='.$_SESSION['merchant_id'])->field('is_pay')->find();
        if($haitao['is_pay']==0)
        {
            $this->error("尚未缴纳保证金，现在前往缴纳",U('Store/Index/pay_money'));
        }
    }

    public function withdrawal_index()
    {
        $this->display('withdrawal_index');
    }

    /**
     * 提现申请ajax列表
     */
    public function withdrawal_ajax(){
        $store_id = $_SESSION['merchant_id'];

        $where = " `store_id`=".$store_id;
        if(I('status') != null && I('status')!=99)
        {
            $where .= " and status = ".I('status');
        }

        $STORE_WITHDRAWAL = M('store_withdrawal');
        $count = $STORE_WITHDRAWAL->where($where)->count();
        $Page = new AjaxPage($count, 10);
        $show = $Page->show();
        $order_str = " `datetime` desc ";
        $List = $STORE_WITHDRAWAL->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page',$show);
        $this->assign('List',$List);
        $this->display();
    }

    /**
     * 提现申请显示
     */
    public function withdrawal_add(){
        //	    protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
        $store_info = M('merchant')->where(array('id'=>$_SESSION['merchant_id']))->field('store_name')->find();
        $reflect = $this->cash_available($_SESSION['merchant_id']);
        $this->assign('reflect',$reflect);
        session('reflect',$reflect);
        $this->assign('store_name',$store_info['store_name']);
        $this->display();
    }

    /*
     *提现申请提交
     */
    public function post_withdrawal(){
        if(empty(redis("post_withdrawal".$_SESSION['merchant_id']))) {//判断是否有锁
            redis("post_withdrawal".$_SESSION['merchant_id'], "1", 20);//写入锁
            $data = $_POST;
            if ($data['withdrawal_money'] < 1 || $data['withdrawal_money'] % 500 != 0) {
                $result = json_encode(array('status' => 0, 'msg' => '请输入500的倍数的提现金额'));
            } elseif ($data['withdrawal_money'] > $this->cash_available($_SESSION['merchant_id'])) {
                $result = json_encode(array('status' => 0, 'msg' => '提现余额不足'));
            } else {
                $withdrawal_total = M('store_withdrawal')->where('store_id=' . $_SESSION['merchant_id'])->field('datetime')->order('id desc')->find();
                $datetime = strtotime(date('Y-m-d', strtotime($withdrawal_total['datetime'])));
                $new = strtotime(date('Y-m-d', time()));
                if ($datetime == $new) {
                    $result = json_encode(array('status' => 0, 'msg' => '一天只能提现一次'));
                } else {

                    $store_info = M('merchant')->where(array('id' => $_SESSION['merchant_id']))->field('id,store_name')->find();
                    $data['store_id'] = $store_info['id'];
                    $data['store_name'] = $store_info['store_name'];
                    $data['datetime'] = date('Y-m-d H:i:s');
                    $data['end_time'] = time();
                    $data['status'] = 0;
                    $order_total = M('order')->where(array('store_id' => $store_info['id']))->sum('order_amount');
                    $withdrawal_total = M('store_withdrawal')->where(array('store_id' => $store_info['id']))->sum('order_amount');
                    $data['total_money'] = $order_total;
                    $data['balance_money'] = $order_total - $withdrawal_total;

                    $res = M('store_withdrawal')->add($data);
                    if ($res) {
                        $result = json_encode(array('status' => 1, 'msg' => '申请成功,请等待平台审核'));
                    } else {
                        $result = json_encode(array('status' => 0, 'msg' => '提交失败，请联系管理员'));
                    }
                }
            }
            redisdelall("post_withdrawal".$_SESSION['merchant_id']);//删除锁
        } else {
            $result = json_encode(array('status' => 0, 'msg' => '客官别太着急'));
        }
        echo $result;
    }

    /**
     *下载对账单
     */
    public function Download_statements(){

        $sw_id = $_GET['sw_id'];

        $withdrawal_info = M('store_withdrawal')->where(array('sw_id'=>$sw_id))->find();

        $order_condition['o.confirm_time'] = array('neq',0);
        $order_condition['o.add_time'] = array('between',$withdrawal_info['start_time'].','.$withdrawal_info['end_time']);
        $order_condition['o.store_id'] = $withdrawal_info['store_id'];
        $order_condition['_string'] = " gr.id IS NULL ";
        //获取所有符合条件的所有订单
        $expTableData = M('order')->alias('o')->join(' LEFT JOIN tp_return_goods gr on o.order_id = gr.order_id ')->where($order_condition)->order('add_time asc')
            ->field('o.order_id,o.order_sn,o.add_time,o.consignee,o.address,o.mobile,o.goods_price,o.order_amount,
						o.pay_name,o.pay_status,shipping_status,o.confirm_time,gr.id')
            ->select();

        //商户惩罚
        $punishment_Condition['order_add_time'] = array('between',$withdrawal_info['start_time'].','.$withdrawal_info['end_time']);
        $punishment_Condition['store_id'] = $withdrawal_info['store_id'];
        $punishment_Condition['status'] = 1;

        $punishment_count = M('store_punishment')->where($punishment_Condition)->field('sum(sp_penal_sum) as sum_penal')->find();
        $punishment_count = $punishment_count['sum_penal'];

        $expTitle1 = '提现对账表--正常订单';
        $expCellName = array(
            array('order_id','订单id'),
            array('order_sn','订单编号'),
            array('add_time','下单日期'),
            array('consignee','收货人'),
            array('address','收货地址'),
            array('mobile','手机号码'),
            array('goods_price','商品价格'),
            array('order_amount','应付价格'),
            array('pay_name','支付方式'),
            array('pay_status','支付状态'),
            array('shipping_status','发货状态'),
            array('confirm_time','收货时间'),
            array('goods_desc','商品信息'),
        );
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle1);//文件名称
        $fileName = '拼趣多对账单'.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("phpExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();

        //订单总数
        $order_count = count($expTableData);
        //总价
        $order_total = 0;

        //正常订单总价
        $nomal_total = 0;

        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $objPHPExcel->getActiveSheet(0)->setTitle('正常订单列表');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
        $objPHPExcel->getActiveSheet(0)->getRowDimension(1)->setRowHeight(40);

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle1);

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }

        $Shipping_status = array('0'=>'未发货','1'=>'已发货');
        $Pay_status = array('0'=>'未支付','1'=>'已支付');

        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){

                $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$j])->setAutoSize(true);
                $objPHPExcel->getActiveSheet(0)->getRowDimension($i+3)->setRowHeight(25);

                switch ($expCellName[$j][0]){

                    case 'pay_status':
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.$Pay_status[$expTableData[$i][$expCellName[$j][0]]]);
                        break;
                    case 'order_amount':
                        $order_total += $expTableData[$i]['order_amount'];
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.$expTableData[$i][$expCellName[$j][0]]);
                        break;
                    case 'shipping_status':
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.$Shipping_status[$expTableData[$i][$expCellName[$j][0]]]);
                        break;
                    case 'confirm_time':
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData[$i][$expCellName[$j][0]]));
                        break;
                    case 'add_time':
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData[$i][$expCellName[$j][0]]));
                        break;
                    case 'goods_desc':
                        $orderGoods = D('order_goods')->where('order_id='.$expTableData[$i]['order_id'])->select();
                        $strGoods="";
                        foreach($orderGoods as $goods){
                            $strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
                            if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
                            //$strGoods .= "\t";
                        }

                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),$strGoods);
                        break;
                    default :
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.$expTableData[$i][$expCellName[$j][0]]);
                        break;
                }
            }
        }

        $hebing = 'A'.($dataNum+3).':'.$cellName[$cellNum-1].($dataNum+3);
        $objPHPExcel->getActiveSheet(0)->getRowDimension($dataNum+3)->setRowHeight(25);

        $objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));

        $objPHPExcel->getActiveSheet(0)->mergeCells($hebing);//合并单元格

        $order_str_total = "订单数:".$dataNum.'张   /  '.'订单总额:  ￥'.$order_total.' RMB   / 商户惩罚金额:'.$punishment_count.'  /  本次可提现金额:'.($order_total-$punishment_count);

        $objPHPExcel->getActiveSheet(0)->setCellValue('A7',$order_str_total);


        //退款订单导出数据
        $expCellName2 = array(
            array('order_id','订单id'),
            array('order_sn','订单编号'),
            array('add_time','下单日期'),
            array('consignee','收货人'),
            array('mobile','手机号码'),
            array('goods_price','商品价格'),
            array('order_amount','应付价格'),
            array('pay_name','支付方式'),
            array('pay_status','支付状态'),
            array('shipping_status','发货状态'),
            array('confirm_time','收货时间'),
            array('back_pay_name','退款方式'),
            array('back_order_amount','退款金额'),
            array('status','退款状态'),
            array('reason','退款原因'),
            array('addtime','申请退款时间'),
            array('remark','客服备注'),
            array('goods_desc','商品信息')
        );
        $cellNum2 = count($expCellName2);

        $back_status = array('0'=>'申请中','1'=>'客服处理中','2'=>'已完成');

        $expTitle2 = '提现对账表--退款订单';
        //创建一个新的工作空间(sheet)
        $objPHPExcel->createSheet();
        $objPHPExcel->setactivesheetindex(1);
        $objPHPExcel->setactivesheetindex(1)->mergeCells('A1:'.$cellName[$cellNum2-1].'1');//合并单元格
        $objPHPExcel->setactivesheetindex(1)->setCellValue('A1', $expTitle2.'  Export time:'.date('Y-m-d H:i:s'));
        $objPHPExcel->getActiveSheet(1)->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
        $objPHPExcel->getActiveSheet(1)->getRowDimension(1)->setRowHeight(40);
        $objPHPExcel->setActiveSheetIndex(1)->setTitle('退货订单列表');

        for($i=0;$i<$cellNum2;$i++){
            $objPHPExcel->setActiveSheetIndex(1)->setCellValue($cellName[$i].'2', $expCellName2[$i][1]);
        }

        unset($order_condition['_string']);
        $expTableData2 = M('order')->alias('o')->join(' INNER JOIN tp_return_goods gr on o.order_id = gr.order_id ')->where($order_condition)->order('add_time asc')
            ->field('o.order_id,o.order_sn,o.add_time,o.consignee,o.address,o.mobile,o.goods_price,o.order_amount,
						o.pay_name,o.pay_status,shipping_status,o.confirm_time,gr.id,gr.status,gr.reason,gr.addtime,gr.remark')
            ->select();

        $dataNum2 = count($expTableData2);

        //退款总价
        $back_total = 0;

        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum2;$i++){
            for($j=0;$j<$cellNum2;$j++){
                $objPHPExcel->getActiveSheet(1)->getColumnDimension($cellName[$j])->setAutoSize(true);
                $objPHPExcel->getActiveSheet(1)->getRowDimension($i+3)->setRowHeight(25);

                switch ($expCellName2[$j][0]){

                    case 'back_pay_name':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$expTableData2[$i]['pay_name']);
                        break;
                    case 'back_order_amount':
                        $back_total += $expTableData2[$i]['order_amount'];
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i]['order_amount']);
                        break;
                    case 'status':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$back_status[$expTableData2[$i]['status']]);
                        break;
                    case 'addtime':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),date('Y-m-d H:i:s',$expTableData2[$i]['status']));
                        break;
                    case 'pay_status':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$Pay_status[$expTableData2[$i][$expCellName2[$j][0]]]);
                        break;
                    case 'order_amount':
                        $order_total += $expTableData2[$i]['order_amount'];
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i][$expCellName2[$j][0]]);
                        break;
                    case 'shipping_status':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$Shipping_status[$expTableData2[$i][$expCellName[$j][0]]]);
                        break;
                    case 'confirm_time':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData2[$i][$expCellName2[$j][0]]));
                        break;
                    case 'add_time':
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData2[$i][$expCellName2[$j][0]]));
                        break;
                    case 'goods_desc':
                        $orderGoods = D('order_goods')->where('order_id='.$expTableData2[$i]['order_id'])->select();
                        $strGoods="";
                        foreach($orderGoods as $goods){
                            $strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
                            if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
                            //$strGoods .= "\t";
                        }
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$strGoods);
                        break;
                    default :
                        $objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i][$expCellName2[$j][0]]);
                        break;
                }
            }
        }

        $hebing2 = 'A'.($dataNum2+3).':'.$cellName[$cellNum2-1].($dataNum2+3);
        $objPHPExcel->getActiveSheet()->getRowDimension($dataNum2+3)->setRowHeight(25);

        $objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum2+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));

        $objPHPExcel->getActiveSheet()->mergeCells($hebing2);//合并单元格

        $back_order_str_total = "退款订单数:".$dataNum2.'张   /  '.'退款订单总额:  ￥'.$back_total.' RMB ';

        $objPHPExcel->getActiveSheet()->setCellValue('A'.($dataNum2+3),$back_order_str_total);

        //商户惩罚明细
//退款订单导出数据
        $expCellName3 = array(
            array('sp_id','惩罚记录id'),
            array('order_id','订单id'),
            array('order_sn','订单编号'),
            array('order_add_time','下单时间'),
            array('order_amount','订单应付金额'),
            array('sp_penal_sum','罚金'),
            array('store_name','商户名称'),
            array('reason','罚款缘由'),
            array('admin_name','平台操作员'),
            array('datetime','惩罚时间'),
        );
        $cellNum3 = count($expCellName3);

        $punishment_status = array('1'=>'已处理','2'=>'已撤销');

        $expTitle3 = '商户惩罚记录';

        //创建一个新的工作空间(sheet)
        $objPHPExcel->createSheet();
        $objPHPExcel->setactivesheetindex(2);
        $objPHPExcel->setactivesheetindex(2)->mergeCells('A1:'.$cellName[$cellNum3-1].'1');//合并单元格
        $objPHPExcel->setactivesheetindex(2)->setCellValue('A1', $expTitle3.'  Export time:'.date('Y-m-d H:i:s'));
        $objPHPExcel->getActiveSheet(2)->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
        $objPHPExcel->getActiveSheet(2)->getRowDimension(1)->setRowHeight(40);
        $objPHPExcel->setActiveSheetIndex(2)->setTitle('商户惩罚记录');

        for($i=0;$i<$cellNum3;$i++){
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue($cellName[$i].'2', $expCellName3[$i][1]);
        }

        $expTableData3 = M('store_punishment')->where($punishment_Condition)->select();

        $dataNum3 = count($expTableData3);

        //处罚总价
        $punishment_total = 0;

        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum3;$i++){
            for($j=0;$j<$cellNum3;$j++){
                $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$j])->setAutoSize(true);
                $objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(25);
                $punishment_total += $expTableData3[$i]['sp_penal_sum'];
                switch ($expCellName3[$j][0]){
                    case 'order_add_time':
                        $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),date('Y-m-d H:i:s',$expTableData3[$i]['order_add_time']));
                        break;
                    default :
                        $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),' '.$expTableData3[$i][$expCellName3[$j][0]]);
                        break;
                }
            }
        }
        $hebing3 = 'A'.($dataNum3+3).':'.$cellName[$cellNum3-1].($dataNum3+3);
        $objPHPExcel->getActiveSheet()->getRowDimension($dataNum3+3)->setRowHeight(25);

        $objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum3+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));

        $objPHPExcel->getActiveSheet()->mergeCells($hebing3);//合并单元格

        $punishment_str_total = "惩罚次数:".$dataNum3.'次   /  '.'惩罚金额总计:  ￥'.$punishment_total.' RMB ';

        $objPHPExcel->getActiveSheet()->setCellValue('A'.($dataNum3+3),$punishment_str_total);

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function assistant()
    {
        $this->display();
    }

    function receiptindex()
    {
        $this->display();
    }

    function ajaxreceipt()
    {
        $store_id = $_SESSION['merchant_id'];
        $store_info = M('merchant')->alias('m')
            ->Join(' INNER JOIN tp_store_detail d ON d.storeid = m.id ')
            ->where('m.id = '.$store_id)
            ->field('m.id,m.store_name,d.margin,d.is_pay,d.margin_order,d.buyer_email,d.trade_no,d.notify_time')
            ->find();
        if(file_exists('Public/upload/receipt/'.$store_id.'.jpg'))
        {
            $receipt = C('HTTP_URL').'/Public/upload/receipt/'.$store_id.'.jpg';
        }elseif(file_exists('Public/upload/receipt/'.$store_id.'.png')){
            $receipt = C('HTTP_URL').'/Public/upload/receipt/'.$store_id.'.png';
        }elseif(file_exists('Public/upload/receipt/'.$store_id.'.gif')){
            $receipt = C('HTTP_URL').'/Public/upload/receipt/'.$store_id.'.gif';
        }else{
            $pic = $this->getReceipt($store_info);
            $receipt = C('HTTP_URL').$pic;
        }

        $this->assign('receipt',$receipt);
        $this->display();
    }

    function getReceipt($store)
    {
        $bigImgPath = 'Public/images/shouju.jpg';
        $img = imagecreatefromstring(file_get_contents($bigImgPath));

        $font = 'Public/images/yahei.ttf';//字体
        $black = imagecolorallocate($img, 0, 0, 0);//字体颜色 RGB

        $fontSize = 11;   //字体大小
        $left = 805;      //左边距
        $top = 108;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, mb_substr($store['notify_time'] , 0 , 10));//交纳时间
        $fontSize = 11;   //字体大小
        $left = 175;      //左边距
        $top = 168;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, $store['store_name']);//商户名
        $fontSize = 11;   //字体大小
        $left = 90;      //左边距
        $top = 168;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, $store['id']);//商户id
        $fontSize = 11;   //字体大小
        $left = 470;      //左边距
        $top = 168;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, $store['trade_no']);//交易单号
        $fontSize = 11;   //字体大小
        $left = 790;      //左边距
        $top = 168;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, $store['margin']);//交纳金额
        $fontSize = 11;   //字体大小
        $left = 340;      //左边距
        $top = 402;       //顶边距
        imagefttext($img, $fontSize, 0, $left, $top, $black, $font, $store['store_name']."   "."ID:".$store['id']);//交纳汇总
        list($bgWidth, $bgHight, $bgType) = getimagesize($bigImgPath);
        switch ($bgType) {
            case 1: //gif
                $pic = '/sites/pqd/Public/upload/receipt/'.$store['id'].'.gif';
                $pic2 = '/Public/upload/receipt/'.$store['id'].'.gif';
                imagejpeg($img,$pic);
                break;
            case 2: //jpg
                $pic = '/sites/pqd/Public/upload/receipt/'.$store['id'].'.jpg';
                $pic2 = '/Public/upload/receipt/'.$store['id'].'.jpg';
                imagejpeg($img,$pic);
                break;
            case 3: //png
                $pic = '/sites/pqd/Public/upload/receipt/'.$store['id'].'.png';
                $pic2 = '/Public/upload/receipt/'.$store['id'].'.png';
                imagejpeg($img,$pic);
                break;
            default:
                break;
        }
        return $pic2;
    }
}