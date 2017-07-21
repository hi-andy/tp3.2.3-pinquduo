<?php
namespace Admin\Controller;

class UploadifyqqController extends BaseController{
   
    public function upload(){
        $func = I('func');
        $path = I('path','temp');
        $info = array(
        	'num'=> I('num'),
            'title' => '',       	
        	'upload' =>U('Admin/Ueditor/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'images')),
            'size' => '20M',
            'type' =>'jpg,png,gif,jpeg,apk',
            'input' => I('input'),
            'func' => empty($func) ? 'undefined' : $func,
        );
        $this->assign('info',$info);
        $this->display();
    }
    
    /*
              删除上传的图片
     */
    /*
              删除上传的图片
     */
    public function delupload(){

        $action=isset($_GET['action']) ? $_GET['action'] : null;
        $filename= isset($_GET['filename']) ? $_GET['filename'] : null;
        /*
        $filename= str_replace('../','',$filename);
        $filename= trim($filename,'.');
        $filename= trim($filename,'/');
        */
        if($action=='del' && !empty($filename)){

            //删除七牛云图片
            $key = str_replace(CDN."/", "", $filename);
            $qiniu = new QiniuController();
            print_r($qiniu->delete("imgbucket", $key));
            /*
            $size = getimagesize($filename);
            $filetype = explode('/',$size['mime']);
            if($filetype[0]!='image'){
                return false;
                exit;
            }
            */
            if(!empty($_GET['goods_id']))
            {
                $res = M('goods_images')->where('goods_id = '.$_GET['goods_id'].' and image_url = "'.CDN."/".$filename.'"')->delete();
            }
            //unlink($filename);
            exit;
        }
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
        //echo "<script language=javascript>alert('".json_encode($result)."');</script>";
    }

}