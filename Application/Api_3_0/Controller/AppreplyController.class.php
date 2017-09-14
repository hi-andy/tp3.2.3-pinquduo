<?php
/**
 * 聊天消息客户端控制器
 * Author: yonghua
 * Time: 2017-8-7
 */
namespace Api_3_0\Controller;

use Think\Cache\Driver\Redis;
use Think\Controller;

class AppreplyController extends Controller
{
    // 设置判断多长时间重发自动回复数据
    const REPLYTIME = 43200;
    // 环信客户端id
    private $client_id;
    // 环信客户端密钥
    private $client_secret;
    // 环信orgname
    private $org_name;
    // 环信appname
    private $app_name;
    // 环信请求地址
    private $url;
    // redis对象
    private $redis;
    // 用户信息
    private $userInfo;
    // 商家信息
    private $storeInfo;

    /**
     * 获取token 需要发起post请求
     */
    public function __construct() {
        $this->client_id = C('clientid');
        $this->client_secret = C('clientsecret');
        $this->org_name = C('orgname');
        $this->app_name = C('appname');
        $this->url = 'https://a1.easemob.com/' . $this->org_name . '/' . $this->app_name . '/';
        $this->redis = new Redis();
    }
    // 控制器初始化函数
    public function _initialize()
    {
        header("Access-Control-Allow-Origin:*");
    }

    /**
     * 获取自动回复列表信息
     * @param string $user_id
     * @param string $store_id
     */
    public function replyData(){
        // 获取用户id
        $user_id = (int)I('user_id',0);
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测用户ID和商家ID
        $data = $this->check($user_id,$store_id);

        // 满足条件
        if($data){
            $this->startSend($user_id,$store_id);
        }

    }


    /**
     * 获取自动回复内容
     * @param string $user_id
     * @param string $store_id
     * @param string $reply_id
     */
    public function replyInfo(){
        // 临时注释掉下面代码 备用
        /*
        // 获取用户id
        $user_id = (int)I('user_id',0);
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 获取回复id
        $reply_id = (int)I('reply_id',0);
        // 检测数据是否合法
        $data = $this->check($user_id,$store_id);

        // 满足条件
        if($data && $reply_id > 0){
            // 获取自动回复发送给用户的内容
            $list = M('robot_reply','tp_','DB_CONFIG2')->field('reply')->where("id={$reply_id} and store_id={$store_id}")->find();
            $content = '';
            // 取得回复内容
            if(count($list) > 0){
                $content = $list['reply'];
            }
            $data = [
                'recevierUser' => [
                    'avatar' => $this->userInfo['head_pic'],
                    'userid' => $user_id,
                    'username' => $this->userInfo['nickname'],
                ],
                'senderUser' => [
                    'avatar' => $this->storeInfo['store_logo'],
                    'userid' => "store{$store_id}",
                    'username' => $this->storeInfo['store_name'],
                ],
                'time' => time(),
                'terminal' => 's_s',
            ];
            $res = $this->sendText("store{$store_id}","users",[$user_id],$content,$data);

        }
        */

    }

    /**
     * 获取自动回复列表，12小时后没有手动请求自动回复列表，将自动回复列表发送给用户
     * @param string $user_id
     * @param string $store_id
     */
    public function getReply(){
        // 获取用户id
        $user_id = (int)I('user_id',0);
        // 获取商家id
        $store_id = (int)I('store_id',0);
        // 检测用户ID和商家ID
        $data = $this->check($user_id,$store_id);
        // 满足条件后
        if($data){
            // 生成获取自动回复的缓存key
            $redisKey = "replyData_{$store_id}_{$user_id}";
            // 获取自动回复的缓存设置的时间
            $setTime = $this->redis->get($redisKey);
            $setTime = (int)$setTime;
            // 获取距离当前时间的时间差
            $chaTime = time() - $setTime;
            // 判断是否满足条件
            if($chaTime > self::REPLYTIME){
                // 发送自动回复数据列表给用户
                $this->startSend($user_id,$store_id);
            }

        }

    }

    /**
     * 检测用户和商家是否合法
     * @param int $store_id
     * @param int $user_id
     */
    private function startSend($user_id,$store_id){
        // 获取自动回复启用的数据
        $list = M('robot_reply','tp_','DB_CONFIG2')->field('id,title')->where("store_id={$store_id} and enable=1")->order('sort asc')->limit(5)->select();
        $content = '亲，很高兴为您服务，请问您要咨 询什么问题呢！';

        $data = [
            'recevierUser' => [
                'avatar' => $this->userInfo['head_pic'],
                'userid' => $user_id,
                'username' => $this->userInfo['nickname'],
            ],
            'senderUser' => [
                'avatar' => $this->storeInfo['store_logo'],
                'userid' => "store{$store_id}",
                'username' => $this->storeInfo['store_name'],
            ],
            'time' => time(),
            'terminal' => 's_s',
            'autoReply' => $list,
        ];
        // 生成获取自动回复的缓存时间
        $redisKey = "replyData_{$store_id}_{$user_id}";
        // 设置缓存
        $this->redis->set($redisKey,time());
        // 自动回复的信息发送给环信
        $res = $this->sendText("store{$store_id}","users",[$user_id],$content,$data);

    }

    /**
     * 检测用户和商家是否合法
     * @param int $store_id
     * @param int $user_id
     */
    private function check($user_id,$store_id){
        // 检测商家id非法
        if($store_id <= 0){
            return false;
        }
        // 检测用户id非法
        if($user_id <= 0){
            return false;
        }
        // 查询商家信息
        $this->storeInfo = M('merchant','tp_','DB_CONFIG2')->where("id = {$store_id}")->find();
        // 商家信息没有数据记录
        if(empty($this->storeInfo)){
            return false;
        }

        // 查询用户信息
        $this->userInfo = M('users','tp_','DB_CONFIG2')->where("user_id = {$user_id}")->find();
        // 商家信息没有数据记录
        if(empty($this->userInfo)){
            return false;
        }
        // 商家和用户合法
        return true;
    }


    /**
     * 获取token 需要发起post请求
     */
    private function get_token(){
        //发送的数据包
        $options = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];
        // json_encode()函数，可将PHP数组或对象转成json字符串，使用json_decode()函数，可以将json字符串转换为PHP数组或对象
        $body=json_encode($options);
        $url=$this->url.'token';
        $tokenResult = $this->postCurl($url,$body,$header=array());
        return "Authorization:Bearer ".$tokenResult['access_token'];
    }

    /**
     * 发送文本消息
     * @param string $from
     * @param string $target_type
     * @param string $target
     * @param string $content
     * @param string $ext
     */
    public function sendText($from="admin",$target_type='users',$target,$content,$ext){
        // 发送文本消息地址
        $url = $this->url.'messages';
        // 发送文本消息接收者类型
        $body['target_type'] = $target_type;
        // 接收者
        $body['target'] = $target;
        // 发送消息类型 txt
        $options['type'] = "txt";
        // 发送消息的内容
        $options['msg'] = $content;
        // 发送消息的完整内容
        $body['msg'] = $options;
        // 发送者
        $body['from'] = $from;
        // 接收者需要的扩展信息
        $body['ext'] = $ext;
        // 将数据json格式
        $b = json_encode($body);
        // 获取token拼接头部信息
        $header = array($this->get_token());
        // 发起请求
        $result = $this->postCurl($url,$b,$header);
        return $result;
    }



    /**
     * 远程调用接口
     * @param string $url  远程接口地址
     * @param string $body 数据消息体
     * @param array $header 数据头
     * @param string $type 请求方式
     */
    function postCurl($url,$body,$header,$type="POST"){
        //1.创建一个curl资源
        $ch = curl_init();
        //2.设置URL和相应的选项
        curl_setopt($ch,CURLOPT_URL,$url);//设置url
        //1)设置请求头
        //array_push($header, 'Accept:application/json');
        //array_push($header,'Content-Type:application/json');
        //array_push($header, 'http:multipart/form-data');
        //设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
        curl_setopt($ch,CURLOPT_HEADER,0);
        //curl_setopt ( $ch, CURLOPT_TIMEOUT,5); // 设置超时限制防止死循环
        //设置发起连接前的等待时间，如果设置为0，则无限等待。
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //2)设备请求体
        if (count($body)>0) {
            //$b=json_encode($body,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
        }
        //设置请求头
        if(count($header)>0){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        }
        //上传文件相关设置
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算

        //3)设置提交方式
        switch($type){
            case "GET":
                curl_setopt($ch,CURLOPT_HTTPGET,true);
                break;
            case "POST":
                curl_setopt($ch,CURLOPT_POST,true);
                break;
            case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
                break;
            case "DELETE":
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
                break;
        }


        //4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设

        //curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
        //curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
        //5)


        //3.抓取URL并把它传递给浏览器
        $res=curl_exec($ch);

        $result=json_decode($res,true);
        //4.关闭curl资源，并且释放系统资源
        curl_close($ch);
        if(empty($result))
            return $res;
        else
            return $result;

    }


}