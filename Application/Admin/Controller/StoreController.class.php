<?php
/**
 * 商户管理
 */
namespace Admin\Controller;
use Think\AjaxPage;

class StoreController extends BaseController{

	/*
	* 初始化操作
	*/
	public function _initialize() {
		C('TOKEN_ON',false); // 关闭表单令牌验证
		$this->assign('haitao',C('IS_HAITAO'));
	}

	public function index()
	{
		$this->display('storeList');
	}

	public function ajaxStoreList()
	{
		$where = " 1=1 ";
		//$where = ' is_show = 1 '; // 搜索条件
		if(I('state') != null)
		{
			$where = "$where and s.state = ".I('state');
		}
		//关键字搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if ($key_word) {
			$where = "$where and s.store_name like '%$key_word%'";
		}

		$mobile = I('mobile') ? trim(I('mobile')) : '';
		if ($mobile) {
			$where = "$where and s.mobile like '%$mobile%'";
		}
		$is_haitao = I('is_haitao');
		if($is_haitao==1) {
			$where = "$where and sd.is_haitao=1";
		}elseif($is_haitao==0){
			$where = "$where and (sd.is_haitao=0 or sd.is_haitao is null)";
		}
		$merchant = M('merchant');
		$count = $merchant->where($where)->join(' LEFT JOIN tp_store_detail sd on s.id=sd.storeid ')->alias('s')->count();
		$Page = new AjaxPage($count, 10);
		$show = $Page->show();
		$order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
		$storesList = $merchant
			->alias('s')
			->where($where)
			->join(' LEFT JOIN tp_store_detail sd on s.id=sd.storeid ')
			->field('s.*,sd.is_haitao')
			->order($order_str)
			->limit($Page->firstRow . ',' . $Page->listRows)->select();
//		var_dump(M()->getLastSql());die;
		$this->assign('page',$show);
		$this->assign('storesList',$storesList);
		$this->display();
	}

	public function editStore()
	{
		session('id',I('id'));
		$store = M('merchant')->where('id = '.I('id'))->find();
		$detail = M('store_detail')->where(array('storeid'=>I('id')))->find();
		$detail['sbzm_imgs'] = json_decode($detail['sbzm_imgs']);
		$detail['ppsq_imgs'] = json_decode($detail['ppsq_imgs']);
		$detail['zjbg_imgs'] = json_decode($detail['zjbg_imgs']);
		$detail['yyzz_img'] = json_decode($detail['yyzz_img']);
		$detail['zzjg_img'] = json_decode($detail['zzjg_img']);
		$detail['shxy_img'] = json_decode($detail['shxy_img']);

		$this->assign('detail',$detail);
		$store['margin'] = $detail['margin'];
		$store['trade_no'] = $detail['trade_no'];
		$this->assign('store',$store);
		$this->display();
	}
	public function addStore()
	{
		if($_POST){
			$base = $_POST['base'];
			$detail = $_POST['detail'];

			M()->startTrans();
			$base['password'] = encrypt($_POST['password']);
			$base['add_time'] = time();
			$res = M('merchant')->add($base);

			$detail['storeid'] = $res;

			$detail_res = M('store_detail')->add($detail);
			if($res && $detail_res){
				M()->commit();
				echo json_encode(array('status'=>1,'msg'=>'添加成功'));
			}else{
				M()->rollback();
				echo json_encode(array('status'=>0,'msg'=>'添加失败'));
			}
			die;
		}else{
			$this->display();
		}
	}

	public function delStore()
	{
		M()->startTrans();
		$data['is_show'] = 0;
		$row = M('merchant')->where("id = {$_GET['id']}")->delete();
		$detail =  M('store_detail')->where(array('storeid'=>$_GET['id']))->delete();

		if($row || $detail){
			M()->commit();
			exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
		}else{
			M()->rollback();
			exit(json_encode(array('status'=>0,'msg'=>'删除失败')));
		}

	}

	/**
	 * 商户信息保存
	 */
	public function Savestore(){
		$data = $_REQUEST;
		$store_id = $_GET['store_id'];
		$base = $data['base'];
		$type = $data['type'];
		$detail = $data['detail'];

			if($base){
				$merchant = M('merchant')->where(array('id'=>$store_id))->find();
				if(!empty($base['password2']))
				{
					if($merchant['passwrod'] == $base['password']){
						unset($base['password']);
					}elseif(md5($base['password'])==$merchant['passwrod']){
						unset($base['password']);
					}else{
						$base['password'] = md5($base['password']);
					}
				}
				if($merchant['state'] != $base['state']){
					if($base['state'] == 1){
						M('goods')->where(array('store_id'=>$store_id,'is_audit'=>1))->save(array('is_on_sale'=>1));
					}elseif($base['state'] == 0){
						M('goods')->where(array('store_id'=>$store_id))->save(array('is_on_sale'=>0));
					}
				}

				$res = M('merchant')->where(array('id'=>$store_id))->save($base);
			}

		if($detail){
			$detail['store_from'] = $base['store_from'];
			$detail['store_type'] = $data['store_type'];
			if(M('store_detail')->where(array('storeid'=>$store_id))->count()){
				$detail_res = M('store_detail')->where(array('storeid'=>$store_id))->save($detail);
			}else{
				$detail['storeid'] = $store_id;
				$detail_res = M('store_detail')->add($detail);
			}
		}

		if($res || $detail_res){
			echo json_encode(array('status'=>1,'msg'=>'修改成功'));
		}else{
			echo json_encode(array('status'=>0,'msg'=>'修改失败'));
		}
}

	/**
	 * 商户提现申请列表
	 */
	public function withdrawal_index(){
		$this->display();
	}

	/**
	 * 提现申请ajax列表
	 */
	public function withdrawal_ajax(){
		$where = " 1=1 ";
		if(I('status') != null && I('status')!=99)
		{
			$where = "status = ".I('status');
		}
		if($_REQUEST['store_id']){
			$where .= " and store_id = ".$_REQUEST['store_id'];
		}

		//关键字搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if ($key_word) {
			$where = "$where and store_name like '%$key_word%'";
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
	 * 修改提现状态
	 */
	public function changeWithdrawalStatus(){
		$sw_id = I('sw_id');
		$status = I('status');

		$data['status'] = $status;
		$data['adminid'] = $_SESSION['admin_info']['admin_id'];
		$data['admin_name'] = $_SESSION['admin_info']['user_name'];
		$data['handletime'] = date('Y-m-d H:i:s');

		$res = M('store_withdrawal')->where(array('sw_id'=>$sw_id))->save($data);

		if($res){
			echo json_encode(array('status'=>1,'msg'=>'修改成功'));
		}else{
			echo json_encode(array('status'=>0,'msg'=>'修改失败'));
		}
	}

	/**
	 *下载对账单
	 */
	public function Download_statements(){

		$store_id = $_GET['store_id'];

		//商户名称 销售总额 退款金额 罚款金额 待处理订单金额 已提现金额 可提现金额 本次提现金额 剩余可提现金额 提现时间
		$withdrawal_info = M('store_withdrawal')->where('store_id='.$store_id.' and status IN (0,1)')->order('sw_id asc')->select();

		$datas = array();
		$store_name = M('merchant')->where('id='.$withdrawal_info[0]['store_id'])->getField('store_name');
		$datas[0]['store_name'] = $store_name;

		$num = count($withdrawal_info);
		for($i=0;$i<$num;$i++)
		{
			if($withdrawal_info[$i]['status']==1)
				$a = strtotime(date('Y-m-d H:m:s',strtotime($withdrawal_info[$i]['datetime'])));
		}
		if(empty($a))
			$a = 0;
		$b = strtotime(date('Y-m-d H:m:s',strtotime($withdrawal_info[$num-1]['datetime'])));
		$datas[0]['all_buy'] = M('order')->where('order_type=4 and store_id='.$store_id.' and add_time>='.$a.' and add_time<'.$b)->sum('order_amount');
		empty($datas[0]['all_buy']) && $datas[0]['all_buy']=0;
		//提现余额
		//先拿到上次提现成功的总提现额
		$tixian=M('store_withdrawal')->where(array('store_id'=>$store_id,'status'=>1))->order('sw_id asc')->sum('withdrawal_money');
		$all = M('order')->where('order_type=4 and store_id='.$store_id)->sum('order_amount');
		$datas[0]['yue'] = $all-$datas[0]['all_buy']-$tixian;
		empty($datas[0]['yue']) &&$datas[0]['yue'] =0;
		$datas[0]['return_buy'] = M('order')->where('order_type=9 and store_id='.$store_id.' and add_time>='.$a.' and add_time<'.$b)->sum('order_amount');
		empty($datas[0]['return_buy']) && $datas[0]['return_buy']=0;
		$datas[0]['p_buy'] = M('store_punishment')->where('store_id='.$store_id.' and order_add_time>='.$a.' and order_add_time<'.$b)->sum('sp_penal_sum');
		empty($datas[0]['p_buy']) && $datas[0]['p_buy']=0;
		$datas[0]['benti']=$withdrawal_info[$num-1]['withdrawal_money'];

		$datetime = strtotime(date('Y-m-d H:m:s',strtotime($withdrawal_info[0]['datetime'])));

		if(count($withdrawal_info)==1)
		{
			$where = "o.store_id=$store_id and o.order_type=4 and o.confirm_time<=".$datetime;
		}else{
			$datetime2 = strtotime(date('Y-m-d H:m:s',strtotime($withdrawal_info[1]['datetime'])));
			$where = "o.store_id=$store_id and o.order_type=4 and o.confirm_time<=".$datetime2." and o.confirm_time>=".$datetime;
		}
		$order_res = M('order')->alias('o')
			->join('tp_goods g on o.goods_id=g.goods_id')
			->where($where)
			->field("g.goods_name,o.order_sn,o.num,o.order_amount,from_unixtime(o.confirm_time,'%Y-%m-%d %H:%m:%s') as time")
			->select();

		$one=M('order')->alias('o')
			->join('tp_goods g on o.goods_id=g.goods_id')
			->where($where)
			->field("g.goods_name,o.order_sn,o.num,o.order_amount,from_unixtime(o.confirm_time,'%Y-%m-%d %H:%m:%s') as time")
			->select();
		foreach($one as $v)
		{
			$confirm_time = strtotime(date('Y-m-d H:m:s',strtotime($v['time'])));
			$temp = 2*3600*24;
			$cha = time()-$confirm_time;
			if($cha>=$temp)
			{
				$datas[0]['keti'] = $datas[0]['keti']+$v['order_amount'];
			}
		}

		$expTitle1 = '对账账单';
		$expCellName = array(
			array('store_name','商户名称'),
			array('all_buy','已完成订单金额'),
			array('yue','提现余额'),
			array('return_buy','退款'),
			array('p_buy','罚款'),
			array('keti','本次可提现金额'),
			array('benti','本次提现金额'),
		);
		$xlsTitle = iconv('utf-8', 'gb2312', $expTitle1);//文件名称
		$fileName = '拼趣多对账单'.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
		$cellNum = count($expCellName);
		$dataNum = count($datas);
		vendor("phpExcel.PHPExcel");
		$objPHPExcel = new \PHPExcel();

		$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

		$objPHPExcel->getActiveSheet(0)->setTitle('对账账单');
		$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
		$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
		$objPHPExcel->getActiveSheet(0)->getRowDimension(1)->setRowHeight(40);

		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle1);

		for($i=0;$i<$cellNum;$i++){
			$objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i].'2')->setAutoSize(true);
			$objPHPExcel->getActiveSheet(0)->getDefaultColumnDimension($cellName[$i].'2')->setWidth(20);

			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
		}

		for($i=0;$i<$dataNum;$i++){
			for($j=0;$j<$cellNum;$j++){
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),' '.$datas[$i][$expCellName[$j][0]]);
			}
		}

		$expCellName2 = array(
			array('goods_name','商品名称'),
			array('order_sn','订单编号'),
			array('num','数量'),
			array('order_amount','售价'),
			array('time','出售日期'),
		);
		$cellNum2 = count($expCellName2);
		$dataNum2 = count($order_res);
		for($i=0;$i<$cellNum2;$i++){
			$objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i].'20')->setAutoSize(true);
			$objPHPExcel->getActiveSheet(0)->getDefaultColumnDimension($cellName[$i].'10')->setWidth(20);

			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'10', $expCellName2[$i][1]);
		}
		for($i=0;$i<$dataNum2;$i++){
			for($j=0;$j<$cellNum2;$j++){
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+11),' '.$order_res[$i][$expCellName2[$j][0]]);
			}
		}

//		$hebing = 'A'.($dataNum+3).':'.$cellName[$cellNum-1].($dataNum+3);
//		$objPHPExcel->getActiveSheet(0)->getRowDimension($dataNum+3)->setRowHeight(25);
//
//		$objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
//
//		$objPHPExcel->getActiveSheet(0)->mergeCells($hebing);//合并单元格
//
//		$order_str_total = "订单数:".$dataNum.'张   /  '.'订单总额:  ￥'.$order_total.' RMB   / 商户惩罚金额:'.$punishment_count.'  /  本次可提现金额:'.($order_total-$punishment_count);
//
//		$objPHPExcel->getActiveSheet(0)->setCellValue('A7',$order_str_total);
//
//
//		//退款订单导出数据
//		$expCellName2 = array(
//			array('order_id','订单id'),
//			array('order_sn','订单编号'),
//			array('add_time','下单日期'),
//			array('consignee','收货人'),
//			array('mobile','手机号码'),
//			array('goods_price','商品价格'),
//			array('order_amount','应付价格'),
//			array('pay_name','支付方式'),
//			array('pay_status','支付状态'),
//			array('shipping_status','发货状态'),
//			array('confirm_time','收货时间'),
//			array('back_pay_name','退款方式'),
//			array('back_order_amount','退款金额'),
//			array('status','退款状态'),
//			array('reason','退款原因'),
//			array('addtime','申请退款时间'),
//			array('remark','客服备注'),
//			array('goods_desc','商品信息')
//		);
//		$cellNum2 = count($expCellName2);
//
//		$back_status = array('0'=>'申请中','1'=>'客服处理中','2'=>'已完成');
//
//		$expTitle2 = '提现对账表--退款订单';
//		//创建一个新的工作空间(sheet)
//		$objPHPExcel->createSheet();
//		$objPHPExcel->setactivesheetindex(1);
//		$objPHPExcel->setactivesheetindex(1)->mergeCells('A1:'.$cellName[$cellNum2-1].'1');//合并单元格
//		$objPHPExcel->setactivesheetindex(1)->setCellValue('A1', $expTitle2.'  Export time:'.date('Y-m-d H:i:s'));
//		$objPHPExcel->getActiveSheet(1)->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
//		$objPHPExcel->getActiveSheet(1)->getRowDimension(1)->setRowHeight(40);
//		$objPHPExcel->setActiveSheetIndex(1)->setTitle('退货订单列表');
//
//		for($i=0;$i<$cellNum2;$i++){
//			$objPHPExcel->setActiveSheetIndex(1)->setCellValue($cellName[$i].'2', $expCellName2[$i][1]);
//		}
//
//		unset($order_condition['_string']);
//		$expTableData2 = M('order')->alias('o')->join(' INNER JOIN tp_return_goods gr on o.order_id = gr.order_id ')->where($order_condition)->order('add_time asc')
//			->field('o.order_id,o.order_sn,o.add_time,o.consignee,o.address,o.mobile,o.goods_price,o.order_amount,
//						o.pay_name,o.pay_status,shipping_status,o.confirm_time,gr.id,gr.status,gr.reason,gr.addtime,gr.remark')
//			->select();
//
//		$dataNum2 = count($expTableData2);
//
//		//退款总价
//		$back_total = 0;
//
//		// Miscellaneous glyphs, UTF-8
//		for($i=0;$i<$dataNum2;$i++){
//			for($j=0;$j<$cellNum2;$j++){
//				$objPHPExcel->getActiveSheet(1)->getColumnDimension($cellName[$j])->setAutoSize(true);
//				$objPHPExcel->getActiveSheet(1)->getRowDimension($i+3)->setRowHeight(25);
//
//				switch ($expCellName2[$j][0]){
//
//					case 'back_pay_name':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$expTableData2[$i]['pay_name']);
//						break;
//					case 'back_order_amount':
//						$back_total += $expTableData2[$i]['order_amount'];
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i]['order_amount']);
//						break;
//					case 'status':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$back_status[$expTableData2[$i]['status']]);
//						break;
//					case 'addtime':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),date('Y-m-d H:i:s',$expTableData2[$i]['status']));
//						break;
//					case 'pay_status':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$Pay_status[$expTableData2[$i][$expCellName2[$j][0]]]);
//						break;
//					case 'order_amount':
//						$order_total += $expTableData2[$i]['order_amount'];
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i][$expCellName2[$j][0]]);
//						break;
//					case 'shipping_status':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$Shipping_status[$expTableData2[$i][$expCellName[$j][0]]]);
//						break;
//					case 'confirm_time':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData2[$i][$expCellName2[$j][0]]));
//						break;
//					case 'add_time':
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.date('Y-m-d H:i:s',$expTableData2[$i][$expCellName2[$j][0]]));
//						break;
//					case 'goods_desc':
//						$orderGoods = D('order_goods')->where('order_id='.$expTableData2[$i]['order_id'])->select();
//						$strGoods="";
//						foreach($orderGoods as $goods){
//							$strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
//							if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
//						}
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),$strGoods);
//						break;
//					default :
//						$objPHPExcel->getActiveSheet(1)->setCellValue($cellName[$j].($i+3),' '.$expTableData2[$i][$expCellName2[$j][0]]);
//						break;
//				}
//			}
//		}
//
//		$hebing2 = 'A'.($dataNum2+3).':'.$cellName[$cellNum2-1].($dataNum2+3);
//		$objPHPExcel->getActiveSheet()->getRowDimension($dataNum2+3)->setRowHeight(25);
//
//		$objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum2+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
//
//		$objPHPExcel->getActiveSheet()->mergeCells($hebing2);//合并单元格
//
//		$back_order_str_total = "退款订单数:".$dataNum2.'张   /  '.'退款订单总额:  ￥'.$back_total.' RMB ';
//
//		$objPHPExcel->getActiveSheet()->setCellValue('A'.($dataNum2+3),$back_order_str_total);
//
//		//商户惩罚明细
////退款订单导出数据
//		$expCellName3 = array(
//			array('sp_id','惩罚记录id'),
//			array('order_id','订单id'),
//			array('order_sn','订单编号'),
//			array('order_add_time','下单时间'),
//			array('order_amount','订单应付金额'),
//			array('sp_penal_sum','罚金'),
//			array('store_name','商户名称'),
//			array('reason','罚款缘由'),
//			array('admin_name','平台操作员'),
//			array('datetime','惩罚时间'),
//		);
//		$cellNum3 = count($expCellName3);
//
//		$punishment_status = array('1'=>'已处理','2'=>'已撤销');
//
//		$expTitle3 = '商户惩罚记录';
//
//		//创建一个新的工作空间(sheet)
//		$objPHPExcel->createSheet();
//		$objPHPExcel->setactivesheetindex(2);
//		$objPHPExcel->setactivesheetindex(2)->mergeCells('A1:'.$cellName[$cellNum3-1].'1');//合并单元格
//		$objPHPExcel->setactivesheetindex(2)->setCellValue('A1', $expTitle3.'  Export time:'.date('Y-m-d H:i:s'));
//		$objPHPExcel->getActiveSheet(2)->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
//		$objPHPExcel->getActiveSheet(2)->getRowDimension(1)->setRowHeight(40);
//		$objPHPExcel->setActiveSheetIndex(2)->setTitle('商户惩罚记录');
//
//		for($i=0;$i<$cellNum3;$i++){
//			$objPHPExcel->setActiveSheetIndex(2)->setCellValue($cellName[$i].'2', $expCellName3[$i][1]);
//		}
//
//		$expTableData3 = M('store_punishment')->where($punishment_Condition)->select();
//
//		$dataNum3 = count($expTableData3);
//
//		//处罚总价
//		$punishment_total = 0;
//
//		// Miscellaneous glyphs, UTF-8
//		for($i=0;$i<$dataNum3;$i++){
//			for($j=0;$j<$cellNum3;$j++){
//				$objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$j])->setAutoSize(true);
//				$objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(25);
//				$punishment_total += $expTableData3[$i]['sp_penal_sum'];
//				switch ($expCellName3[$j][0]){
//					case 'order_add_time':
//						$objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),date('Y-m-d H:i:s',$expTableData3[$i]['order_add_time']));
//						break;
//					default :
//						$objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),' '.$expTableData3[$i][$expCellName3[$j][0]]);
//						break;
//				}
//			}
//		}
//		$hebing3 = 'A'.($dataNum3+3).':'.$cellName[$cellNum3-1].($dataNum3+3);
//		$objPHPExcel->getActiveSheet()->getRowDimension($dataNum3+3)->setRowHeight(25);
//
//		$objPHPExcel->getActiveSheet()->getStyle('A'.($dataNum3+3))->applyFromArray(array('font' => array ('bold' => true,'size' => 16),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));
//
//		$objPHPExcel->getActiveSheet()->mergeCells($hebing3);//合并单元格
//
//		$punishment_str_total = "惩罚次数:".$dataNum3.'次   /  '.'惩罚金额总计:  ￥'.$punishment_total.' RMB ';
//
//		$objPHPExcel->getActiveSheet()->setCellValue('A'.($dataNum3+3),$punishment_str_total);

		header('pragma:public');
		header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
		header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	/**
	 * 商户惩罚记录
	 */
	public function punishment_index(){
		$this->display();
	}

	/**
	 * ajax获取商户惩罚记录
	 */
	public function ajax_punishment_list(){

		$where = " 1=1 ";

		//关键字搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if ($key_word) {
			$where = "$where and store_name like '%$key_word%'";
		}

		$STORE_PUMISHMENT = M('store_punishment');
		$count = $STORE_PUMISHMENT->where($where)->count();
		$Page = new AjaxPage($count, 10);
		$show = $Page->show();
		$order_str = " `datetime` desc ";
		$List = $STORE_PUMISHMENT->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('page',$show);
		$this->assign('List',$List);
		$this->display();
	}

	/**
	 * 修改惩罚记录状态
	 */
	public function changePunishmentStatus(){
		$sp_id = I('sp_id');
		$status = I('status');

		$data['status'] = $status;
		$data['admin_id'] = $_SESSION['admin_info']['admin_id'];
		$data['admin_name'] = $_SESSION['admin_info']['user_name'];
		$data['datetime'] = date('Y-m-d H:i:s');

		$res = M('store_punishment')->where(array('sp_id'=>$sp_id))->save($data);

		if($res){
			echo json_encode(array('status'=>1,'msg'=>'修改成功'));
		}else{
			echo json_encode(array('status'=>0,'msg'=>'修改失败'));
		}
	}

	/**
	 * 修改商户状态
	 */
	public function Change_store_status(){
		$storeid = I('storeid');
		$state = I('state');
		$is_check = $state==0?2:1;

		$res = M('merchant')->where(array('id'=>$storeid))->save(array('is_check'=>$is_check));

		$store_info = M('merchant')->where(array('id'=>$storeid))->field('mobile,store_name')->find();
		if($is_check == 1){
			sendMessage($store_info['mobile'],array($store_info['store_name']),'163900');
		}else{
			sendMessage($store_info['mobile'],array($store_info['store_name']),'138884');
		}
		echo json_encode(array('status'=>1,'msg'=>'修改成功'));
	}

	/**
	 * 商户审核列表
	 */
	public function Checklist(){
		$this->display();
	}

	/**
	 * ajax 审核列表 ajax
	 */
	public function ajaxCheckList()
	{
		$where = " 1=1 ";
		//$where = ' is_show = 1 '; // 搜索条件
		if(I('state') != null)
		{
			$where = "$where and state = ".I('state');
		}
		//关键字搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if ($key_word) {
			$where = "$where and store_name like '%$key_word%'";
		}

		$address = I('address') ? trim(I('address')) : '';
		if ($address) {
			$where = "$where and mobile like '%$address%'";
		}

		$merchant = M('merchant');
		$count = $merchant->where($where)->count();
		$Page = new AjaxPage($count, 10);
		$show = $Page->show();
		$order_str = " `is_check` asc,id desc ";
		$storesList = $merchant->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();

		$this->assign('page',$show);
		$this->assign('storesList',$storesList);
		$this->display();
	}

	public function del_punishment()
	{
		$id = $_GET['id'];
		$res = M('store_punishment')->where('sp_id='.$id)->find();
		if(empty($res))
		{
			$return_arr = array('status' => -1, 'msg' => '该惩罚已被清除', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
			$this->ajaxReturn(json_encode($return_arr));
		}
		$ress = M('store_punishment')->where('sp_id='.$id)->delete();
		if(!empty($ress))
		{
			$return_arr = array('status' => 1, 'msg' => '删除成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
			$this->ajaxReturn(json_encode($return_arr));
		}

	}

	function openedlist()
	{
		$this->display();
	}

	function ajaxopenedlist()
	{
		$where = " d.is_opened = 0 ";
		//$where = ' is_show = 1 '; // 搜索条件
		if(I('state') != null)
		{
			$where = "$where and m.state = ".I('state');
		}
		//关键字搜索
		$key_word = I('key_word') ? trim(I('key_word')) : '';
		if ($key_word) {
			$where = "$where and m.store_name like '%$key_word%'";
		}

		$address = I('address') ? trim(I('address')) : '';
		if ($address) {
			$where = "$where and m.mobile like '%$address%'";
		}
		$where .= "and m.is_check=1";
		$count = M('store_detail')->alias('d')
			->join('INNER JOIN tp_merchant m on d.storeid = m.id ')
			->where($where)->count();
		$Page = new AjaxPage($count, 10);
		$show = $Page->show();
		$order_str = " m.`is_check` asc,m.id desc ";
		$storesList = M('store_detail')->alias('d')
			->join('INNER JOIN tp_merchant m on m.id = d.storeid ')
			->where($where)
			->order($order_str)
			->field('d.storeid,m.*')
			->limit($Page->firstRow . ',' . $Page->listRows)
			->select();

		$this->assign('page',$show);
		$this->assign('storesList',$storesList);
		$this->display();
	}

	function EditOpenedinfo()
	{
		$storeid = $_GET['id'];
		if(IS_POST)
		{

			$storeid = $_POST['id'];
			if(empty($_POST['notify_time']) || empty($_POST['trade_no']) || empty($_POST['margin']))
			{
				$this->success('数据不正确',U('Admin/store/EditOpenedinfo',array('id'=>$storeid)));
				die;
			}
			$data['is_opened'] = 1;
			$data['is_pay'] = 1;
			$data['notify_time'] = $_POST['notify_time'];
			$data['trade_no'] = $_POST['trade_no'];
			$data['buyer_email'] = $_POST['buyer_email'];
			$data['margin'] = $_POST['margin'];
			$res = M('store_detail')->where('storeid = '.$storeid)->save($data);
			M('merchant')->where('id = '.$storeid)->save(array('state'=>1));
			if($res){
				$this->success('更改成功',U('Admin/store/openedlist'));
			}else{
				$this->success('更改失败',U('Admin/store/EditOpenedinfo',array('id'=>$storeid)));
			}
		}

		$store = M('store_detail')->where('storeid = '.$storeid)->find();

		$this->assign('store',$store);
		$this->display();
	}
}