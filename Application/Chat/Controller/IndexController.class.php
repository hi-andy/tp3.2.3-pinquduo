<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/2
 * Time: 上午11:12
 */

namespace Chat\Controller;
use Think\Controller;

class IndexController
{
    /**
     * 服务器地址
     */
    public function get_server_address(){
        $result = array(
            "status" => 1,
            "action" => "get_server_address",
            "address" => "119.23.118.245:80"
        );
        echo json_encode($result);
    }

    /**
     * 登录
     * @param string $class
     * @param string $uid
     */
    public function login($class="", $uid=""){
        if ($class && $uid) {
            $result = array(
                "action" => "login",
                "userid" => $class . $uid,
            );
            $result = $this->base64_json($result);
        } else {
            $result = $this->errjson("参数错误");
        }
        echo $result;
    }

    /**
     * 发送信息
     * @param string $class
     * @param string $uid
     * @param string $data
     */
    public function set_private_chat($class="", $uid="", $data=""){
        if ($class && $uid && $data){
            $result = array(
                "action" => "private_chat",
                "userid" => $class . $uid,
                "data" => $data
            );
            $result = $this->base64_json($result);
        } else {
            $result = $this->errjson("参数错误");
        }
        echo $result;
    }

    /**
     * 上传
     * @return array
     */
    public function upload(){
        //调用七牛云上传
        $suffix = substr(strrchr($_FILES['Filedata']['name'], '.'), 1);
        $files = array(
            "key" => time().rand(0,9).".".$suffix,
            "filePath" => $_FILES['Filedata']['tmp_name'],
            "mime" => $_FILES['Filedata']['type']
        );
        $qiniu = new \Admin\Controller\QiniuController();
        $info = $qiniu->uploadfile("imgbucket", $files);
        return array(
            "status" => 1,
            "action" => "upload",
            "url" => CDN."/".$info[0]["key"]
        );
    }

    /**
     * 请求解密
     * @param $str
     */
    public function get_decrypt($str){
        $str = base64_json_decode($str);
        echo json_encode($str);
    }

    /**
     * 加密
     * @param string $str
     * @return string
     */
    public function base64_json($str=""){
        return transposition(base64_encode(json_encode($str)));
    }

    /**
     * 解密
     * @param string $str
     * @return string
     */
    public function base64_json_decode($str=""){
        return json_decode(base64_decode(transposition($str)));
    }

    /**
     * 输出错误信息
     * @param string $str
     * @return string
     */
    public function errjson($str=""){
        return json_encode(array('status' => -1, 'msg' => $str));
    }
}