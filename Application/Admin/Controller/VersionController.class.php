<?php

/**
 * 版本接口
 */
namespace Admin\Controller;
use Think\Controller;
use Think\AjaxPage;

class VersionController extends BaseController {

	/**
	 * 获取最新的android版本信息
	 */
	public function index()
	{
		$this->display();
	}

	public function ajaxindex()
	{
		$count = M('version')->count();
		$Page  = new AjaxPage($count,20);
		$show = $Page->show();
//		$orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
		$list = M('version')->limit($Page->firstRow,$Page->listRows)->order('id desc')->select();
		$this->assign('List',$list);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}


	public function delete()
	{
//		var_dump($_GET);
		$id = I('id');

		$res = M('version')->where('`id`='.$id)->find();
		if(empty($res))
		{
			$this->error('该版本已不存在!', U('Admin/Version/EditVersion'));
		}

		$res1 = M('version')->where('`id`='.$id)->delete();
		if($res1)
		{
			$this->success("已删除!!!", U('Admin/Version/index'));
		}
	}

	public function addVersion()
	{
		if(!empty($_POST)) {
			$admin = M('admin')->where('`admin_id`='.$_SESSION['admin_id'])->find();

			$data['version'] = $_POST['version'];
			$data['userid'] = $admin['admin_id'];
			$data['username'] = $admin['user_name'];
			$data['file'] = $_POST['file'];
			$data['message'] = $_POST['message'];
			$data['createtime'] = time();
			$res = M('version')->data($data)->add();
			if($res) {
				$this->ajaxReturn('添加成功');
			} else {
				$this->ajaxReturn('添加失败');
			}
		}
		$this->display();
	}

	/**
	 * 文件上传方法
	 */
	public function uploadfile(){
		$upload = new \Think\Upload();
		//设置上传文件大小
		$upload->maxSize=30120000;

		$upload->rootPath = './'.C("UPLOADPATH") .'file/' ; // 设置附件上传目录

		//设置上传文件规则
		$upload->saveRule='uniqid';

		$result=$upload->upload();

		if(!$result )
		{
			$this->ajaxReturn(array('status'=>0,'msg'=>$upload->getError(),'data'=>array('src'=>'')));
		}else{
			$src=$result['Filedata']['savepath'].$result['Filedata']['savename'];
			$returnData=array('src'=>'/'.C("UPLOADPATH") .'file/'.$src,'name'=>$result['Filedata']['name']);
			$this->ajaxReturn(array('status'=>0,'msg'=>'上传成功','data'=>$returnData));
		}
	}


//	public function EditSpecially()
//	{
//		$id = $_GET['id'];
//		if(!empty($id))
//		{
//			$exclusive = M('exclusive')->where('`id`='.$id)->find();
//			$this->assign('exclusive',$exclusive);
//		}
//
//		if($_POST['type']>0)
//		{
//			$data['name'] = $_POST['name'];
//			$data['Introduction'] = $_POST['introduction'];
//			$data['img'] = $_POST['image'];
//			$res = M('exclusive')->where('`id`='.$_POST['id'])->data($data)->save();
//			if($res)
//			{
//				$return_arr = array(
//					'status' => 1,
//					'msg'   => '修改成功',
//					'data'  => array('url'=>U('Admin/Jiujiu/SpeciallyList')),
//				);
//				$this->ajaxReturn(json_encode($return_arr));
//			}else{
//				$return_arr = array(
//					'status' => -1,
//					'msg'   => '修改失败',
//					'data'  => array('url'=>U('Admin/Jiujiu/EditSpecially')),
//				);
//				$this->ajaxReturn(json_encode($return_arr));
//			}
//		}
//
//		$this->display();
//	}
}