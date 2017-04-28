<?php
namespace Store\Controller;

class UploadifyController extends BaseController{
   
    public function upload(){
        $func = I('func');
        $path = I('path','temp');
        $info = array(
        	'num'=> I('num'),
            'title' => '',       	
        	'upload' =>U('Admin/Ueditor/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'images')),
            'size' => '4M',
            'type' =>'jpg,png,gif,jpeg',
            'input' => I('input'),
            'func' => empty($func) ? 'undefined' : $func,
        );
        $this->assign('info',$info);
        $this->display();
    }
    
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
            $qiniu = new \Admin\Controller\QiniuController();
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
            unlink($filename);
            exit;
        }
    }

}