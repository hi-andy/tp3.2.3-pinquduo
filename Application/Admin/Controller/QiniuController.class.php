<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/4/15
 * Time: 上午10:13
 */

namespace Admin\Controller;
vendor("qiniu_sdk.autoload");
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

//七牛云
class QiniuController {

    const ACCESSKEY = '15gPbXtT9oIJ2EpAuUsHJFPcmZ68qxTXnHTpqwgG';
    const SECRETKEY = '2c1Jyq1_xt3sIbODugIWLNAGC9kwHZS9xmpHxmjm';

    /**
     * 上传文件
        $suffix = substr(strrchr($_FILES['Filedata']['name'], '.'), 1);
        $files = array(
            "key" => time().rand(0,9).".".$suffix,
            "filePath" => $_FILES['Filedata']['tmp_name'],
            "mime" => $_FILES['Filedata']['type']
        );
     * param $bucket 资源所在的空间
     * @param array $files (key上传的文件名，filePath文件的路径，mime文件的类型)
     * @return array
     */
    public function uploadfile($bucket="", $files=array()) {
        $auth = new Auth(QiniuController::ACCESSKEY, QiniuController::SECRETKEY);
        $upToken = $auth->uploadToken($bucket);
        $key = $files["key"];
        $filePath = $files["filePath"];
        $mime = $files["mime"];
        $uploadMgr = new UploadManager();
        return $uploadMgr->putFile($upToken,$key,$filePath,null,$mime);
    }

    /*
     * 从第三方获取文件上传
     */
    public function fetch($url="", $bucket="", $key=""){
        $auth = new Auth(QiniuController::ACCESSKEY, QiniuController::SECRETKEY);
        $bucketMgr = new BucketManager($auth);
        return $bucketMgr->fetch($url, $bucket, $key);
    }

    /**
     * 删除文件
     * @param $bucket 资源所在的空间
     * @param $key 文件名
     * @return mixed
     */
    public function delete($bucket="", $key="") {
        $auth = new Auth(QiniuController::ACCESSKEY, QiniuController::SECRETKEY);
        $bucketMgr = new BucketManager($auth);
        return $bucketMgr->delete($bucket, $key);
    }

    /**
     * @param string $file
     * @return mixed
     *
     * 多张图片上传方法，支持移动端
     */
    public function upload($file='')
    {
        if(!$file) $file = $_FILES;
        foreach ($file['picture']['name'] as $key => $value) {
            $suffix = substr(strrchr($value, '.'), 1);
            $files = array(
                "key" => time() . rand(100000, 999999) . "." . $suffix,
                "filePath" => $file['picture']['tmp_name'][$key],
                "mime" => $file['picture']['type'][$key]
            );
            $info = $this->uploadfile("imgbucket", $files);
            $return_data[$key]['origin'] = CDN . "/" . $info[0]["key"];
            $return_data[$key]['width'] = '100';
            $return_data[$key]['height'] = '100';
            $return_data[$key]['small'] = CDN . "/" . $info[1]["key"];
        }
        redis("mobile_uploadimage", serialize($return_data),REDISTIME);
        return $return_data;
    }

}