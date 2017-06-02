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
            "msg" => "get_server_address",
            "result" => "119.23.118.245:80"
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
            $result = $this->json("login", $result);
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
            $result = $this->json("set_private_chat", $result);
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
        echo json_encode(array(
            "status" => 1,
            "msg" => "upload",
            "result" => base64_encode(CDN."/".$info[0]["key"])
        ));
    }

    /**
     * 拉取库存消息
     * @param $class
     * @param $uid
     * @param int $page
     * @param int $pagesize
     */
    public function get_msglist($class, $uid, $page=0, $pagesize=20){
        if ($class && $uid) {
            $where["userid"] = array("eq", $class.$uid);
            $result = M("chat")->where($where)->order("time")->limit($page,$pagesize)->select();
            $id = "";
            foreach ($result as $v) {
                $id .= $v['id'].",";
            }
            $id = substr($id, 0, -1);
            $result = $this->json("get_msglist", $result);
            $update_where['id'] = array('in', $id);
            M("chat")->where($update_where)->save(array("status" => 1));
        } else {
            $result = $this->errjson("参数错误");
        }
        echo $result;
    }

    /**
     * 保存消息队列
     */
    public function set_msglist(){
        $num = 500;
        $values  = "";
        $sql = "INSERT INTO tp_chat(f_userid, userid, data, status) VALUES";
        for ($i=0; $i<$num; $i++) {
            $msg = (array) $this->get_decrypt(redislist("msglist"));
            if (!empty($msg)) {
                $values .= "({$msg['f_userid']},{$msg['userid']},{$msg['data']},{$msg['status']}),";
            }
        }
        $values = substr($values, 0, -1);
        if ($values) {
            $sql .= $values;
            M("chat")->query($sql);
        }
    }

    /**
     * 请求解密
     * @param $str
     */
    public function get_decrypt($str){
        if ($str) {
            $result = base64_json_decode($str);
            $result = $this->json("get_decrypt", $result);
        } else {
            $result = $this->errjson("参数错误");
        }
        echo $result;
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

    public function json($msg="", $result=""){
        return json_encode(array('status' => 1, 'msg' => $msg, 'result' => $result));
    }

    /**
     * 输出错误信息
     * @param string $str
     * @return string
     */
    public function errjson($msg=""){
        return json_encode(array('status' => -1, 'msg' => $msg));
    }
}