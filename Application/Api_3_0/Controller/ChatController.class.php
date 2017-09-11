<?php
/**
 * 聊天消息控制器
 * 同步环信，保存读取聊天消息，最多保存7天。
 * 改用 Redis 数据库存储聊天数据
 * Author: yonghua
 * Time: 2017-8-7
 */
namespace Api_3_0\Controller;

use Think\Cache\Driver\Redis;
use Think\Controller;

class ChatController extends Controller
{
    private $redis = '';
    public function _initialize()
    {
        $this->redis = new Redis();
    }
	
    /**
     * 获取商家信息
     * @param string $store_id　商家id
     */	
    public function getStoreInfo()
    {
        header("Access-Control-Allow-Origin:*");
        $store_id = I('store_id');
        if(empty($store_id)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户id不能为空')));
        }
        $store_info = M('merchant')->where("id = {$store_id}")->find();
        if(empty($store_info)){
            exit(json_encode(array('status'=>-1,'msg'=>'商户不存在')));
        }else{
            $username = 'store'.$store_info['id'];
            $password = md5($username);
            exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>array('store_name'=>$store_info['store_name'],'store_password'=>$password))));
        }
    }
	
    /**
     * 存储环信聊天的对外接口
     * @param string json　聊天信息
     */	
    public function add()
    {
		//设置跨域
        header("Access-Control-Allow-Origin:*");
		//获取环信推送的消息用  file_get_contents('php://input')接收 json消息
		
		//$str = '{"timestamp":1503735237965,"host":"msync@ebs-ali-beijing-msync20","appkey":"1165160929115391#pqd","from":"88929","to":"store2","msg_id":"370099212840339488","chat_type":"chat","payload":{"bodies":[{"msg":"3","type":"txt"}],"ext":{"recevierUser":{"avatar":"http://cdn.pinquduo.cn/14993951072.jpg","userid":"store2","username":"巨树村22"},"senderUser":{"avatar":"http://wx.qlogo.cn/mmopen/waxhuHTian7KG1sluwGVgakDEQxFz76MSkDozJanYrBF2AibPVM4wJoI20ibEzJw8iaiaTvIjw5PTd56auw0gG4jyUcNtgf7Ea46U/0","userid":"88929","username":"贫道乃徐半仙"},"time":1503735239}},"callId":"1165160929115391#pqd_370099212840339488","eventType":"chat","security":"062b1a6a24b2624757ac72685e1c2018"}';
		//$chatInfo = json_decode($str,true);
		$chatInfo = json_decode(file_get_contents('php://input'),true);

		//检测获取到的消息的合法性
		if(!$chatInfo || count($chatInfo)==0){
			$data = [
				'callId' => "",
				'accept' => "false",
				'reason' => '无数据，或者数据格式不正确',
				'security' => md5("".C('hxkey')."true")	
			];
			$this->ajaxReturn($data);
		}else{
			//将数据写入到缓存
			//获取时间
			$timestamp = $chatInfo['payload']['ext']['time'];
			if(isset($chatInfo['payload']['ext']['autoReplyId']) && $chatInfo['payload']['ext']['autoReplyId'] > 0){
                // 获取商家id
                $store_id = (int)str_replace("store","",$chatInfo['to']);
                // 获取自动回复id
                $reply_id = (int)$chatInfo['payload']['ext']['autoReplyId'];
                // 获取自动回复发送给用户的内容
                $list = M('robot_reply','tp_','DB_CONFIG2')->field('reply')->where("id={$reply_id} and store_id={$store_id}")->find();
                $content = '';
                // 取得回复内容
                if(count($list) > 0){
                    $content = $list['reply'];
                }
                // 拼接数据data
                $data = [
                    'recevierUser' => $chatInfo['payload']['ext']['senderUser'],
                    'senderUser' => $chatInfo['payload']['ext']['recevierUser'],
                    'time' => time(),
                    'terminal' => 's_s',
                ];
                $appReply = new AppreplyController();
                $res = $appReply->sendText("store{$store_id}","users",[$chatInfo['from']],$content,$data);
                file_put_contents('newdata.log',$res,FILE_APPEND);

            }
			$this->set_chat($chatInfo['callId'],$chatInfo['msg_id'],$timestamp,$chatInfo['to'],$chatInfo['from'],$chatInfo['payload'],0);
		}
		
    }
	
    /**
     * 获取环信聊天的对外接口
     * @param string storeid　商家id
     */		
	public function info(){
		header("Access-Control-Allow-Origin:*");
		// 获取商家id
		$store_id = (int)I('storeId');
		// 获取用户id
		$user_id = (int)I('userId');		
		// 返回数据的变量
		$msg = [];
		//拼接需要的商家用户
		$findStore = ((int)$store_id>0) ? "store".$store_id : "";
		//获取商家的最新消息
		$getData = $this->get_chat($findStore,$user_id);
		
		//判断数据是否有数据
		if(count($getData) > 0){
			// 将数组按照时间重新排序 
			foreach ( $getData as $key => $row ){
				$timeOrder[$key] = $row['timestamp'];
			}
			array_multisort($timeOrder, SORT_DESC, $getData);	
			//获取返回数据的第一条数据
			foreach($getData as $info){
				//获取发送者信息
				$from = $info['from'];
				//获取接收者信息
				$to = $info['to'];
				//获取消息体
				$payload = $info['payload'];
				//获取具体的消息双方信息
				$ext = $payload['ext'];
				//获取具体的消息内容体
				$bodies = $payload['bodies'];
				//获取具体的消息的类型
				$type = $bodies[0]['type'];
				//消息体的内容
				$msgContent = ($type == 'txt')?(strpos( $bodies[0]['msg'],'复制链接查看' )!==false)?'[商品链接]':$bodies[0]['msg'] : '[图片]';
				//拿到用户的信息
				if($from == $findStore || $from == $user_id){
					$dataArr = $ext['recevierUser'];
				}else if($to == $findStore || $to == $user_id){
					$dataArr = $ext['senderUser'];
				}					

				//返回前端需要的数据格式	
				$data = [
					'headPic' => $dataArr['avatar'],
					'msg' => $msgContent,
					'sessionId' => $dataArr['userid'],
					'time' => intval($info['timestamp']),
					'unread' => 0,
					'userName' => $dataArr['username']						
				];
				$msg[] = $data;				
			}

			$this->ajaxReturn($msg);	
		}else{
			$this->ajaxReturn($msg);	
		}

	} 
	
    /**
     * 获取环信聊天的商家对应用户信息
	 * request string userId
	 * request string storeId
	 * request string timestamp
     */		
	public function query(){
		// 设置跨域
		header("Access-Control-Allow-Origin:*");
		// 获取用户id
		$userId = (int)I('userId');
		// 获取商家id
		$storeId = (int)I('storeId');
		// 获取用户id
		$timestamp = (int)I('timestamp');
		// 将时间戳减去1秒  因为它是获取<=的数据  避免出现重复
		$timestamp = ($timestamp==0) ? '+inf' : $timestamp-1;	
		// 拼接临时的数据
		$tempKey = "save_{$userId}_{$storeId}_{$timestamp}";
		if(false && $datalistStr=$this->redis->get($tempKey)){
			$dataList = unserialize($dataListStr);
		}else{
			$findStore = "store".$storeId; 
			// 创建商家用户关系key
			$relationKey = "relation_{$findStore}_{$userId}";
			//$this->del_chat($relationKey);
			$dataList = $this->redis->zrevrangebyscore($relationKey, $timestamp, '-inf');
			// 将数据序列化
			foreach($dataList as $k => $v){
				$dataList[$k] = unserialize($v);
			}
			$dataListStr = serialize($dataList);
			// 将查询的数据存入缓存
			$this->redis->set($tempKey,$dataListStr);
			// 两天后自动过期
			$this->redis->expireat($tempKey, time()+172800);			
		}		
		
		// 获取商家用户下该时间戳的前20条数据
		$pageSize = 10;
		// 显示的页数
		$page = ((int)I('page')>0)?(int)I('page'):1;
		// 分页开始的位置
		$start = ($page-1)*$pageSize;			
		$pageNum = ceil(count($dataList)/$pageSize);
		$showData = array_slice($dataList,$start,$pageSize);
		$showData = array_reverse($showData);
		$returnArr = [];
		$listArr = [];
		foreach($showData as $key => $value){
			//获取消息体
			$payload = $value['payload'];
			//获取具体的消息双方信息
			$ext = $payload['ext'];
			//获取具体的消息内容体
			$bodies = $payload['bodies'];
			//获取具体的消息的类型
			$type = $bodies[0]['type'];
			if($type == 'txt'){
				$dataArr = [
					'data' => $bodies[0]['msg'],
					'ext' => $ext,
					'from' => $value['from'],
					'to' => $value['to'],
					'id' => $value['msg_id'],
					'type' => 'chat'
				];
			}else if($type == "img" ){
				$dataArr = [
					'ext' => $ext,
					'file_length' => $bodies[0]['file_length'],
					'filename' => $bodies[0]['filename'],
					'from' => $value['from'],
					'to' => $value['to'],
					'width' => $bodies[0]['size']['width'],
					'height' => $bodies[0]['size']['height'],
					'url' => $bodies[0]['url'],
					'id' => $value['msg_id'],
					'type' => 'chat'
				];				
			}
			$listArr[] = $dataArr;			
		}
		// 将数据追加到数组中
		$returnArr = [
			'page' => $page,
			'pageNum' => $pageNum,
			'list' => $listArr
		];
		$this->ajaxReturn($returnArr);
	}
	
	
    /**
     * 存储聊天
     * @param string $timestamp 时间戳
     * @param string $to　接收方
     * @param string $from　发送方
     * @param string $payload　消息内容
     * @param string $status　状态：0未读，1已读
     */
    public function set_chat($callId, $msg_id, $timestamp='', $to, $from, $payload='', $status)
    {
        if ($callId && $timestamp && $to && $from  && $payload != '' && $status !== '') {
            // 存储 key
			// 接收者key
            $toUser = 'messages:' . $to;
			// 发送者key
            $fromUser = 'messages:' . $from;
			// 获取商户相关的所有用户
			if(intval($to)>0){
				// 用商户做key
				$storeKey = $from;
				$userId = $to; 
			}
			// 获取商户相关的所有用户
			if(intval($from)>0){
				$storeKey = $to;
				$userId = $from; 
			}
			// 检测商户下的用户存在不
			$idList = $this->redis->lRange("userList_{$storeKey}", 0, -1);			
			// 将跟商户相关的用户id写入列表 尾部追加  key：userList_$storeKey			
			$re = in_array($userId, $idList) ? '' : $this->redis->rpush("userList_{$storeKey}",$userId);			
			// 检测用户下的商户存在不
			$idList = $this->redis->lRange("userList_{$userId}", 0, -1);			
			// 将跟商户相关的用户id写入列表 尾部追加  key：userList_$storeKey			
			$re = in_array($storeKey, $idList) ? '' : $this->redis->rpush("userList_{$userId}",$storeKey);			
			
			// 商户和用户合起来的关系表
			$relationKey = "relation_{$storeKey}_{$userId}";
			// 检测$relationKey是否存在
            $relationFirst = $this->redis->zCard($relationKey);			
			// 需要存储的数据 数组格式
            $msgData = array(
				'timestamp' => $timestamp,
				'msg_id' => $msg_id,
                'to' => $to,
                'from' => $from,			
                'payload' => $payload,
            );
			// 将数组数据序列化成字符串
            $storeData = serialize($msgData);
			// 七天的有效时间	
            $expireTime = (int)$timestamp + 7 * 86400;			

            // 如果当天　接收商户和用户　第一次开始聊天（新增集合），为集合设置过期时间
            if (!$relationFirst) {
				//将有序的数据集合写入 排序的索引为  $timestamp
                $this->redis->zAdd($relationKey, (int)$timestamp, $storeData);
				//将有序的数据集合追加有效时间
                $this->redis->expireat($relationKey, $expireTime);
            } else {
                $this->redis->zAdd($relationKey, (int)$timestamp, $storeData);
            }
			
			$data = [
				'callId' => $callId,
				'accept' => "true",
				'reason' => '',
				'security' => md5($callId.C('hxkey')."true")	
			];
            $this->ajaxReturn($data);
        } else {
			$data = [
				'callId' => $callId,				
				'accept' => "false",
				'reason' => '缺少参数',
				'security' => md5($callId.C('hxkey')."true")	
			];
            $this->ajaxReturn($data);
        }
    }

    /**
     * 读取聊天
     * @param string $user_id　用户
	 * @param string $store_id 商户
     */
    public function get_chat($store_id='',$user_id='')
    {
		//检测用户信息的合法性
        if ($store_id || $user_id) {
			// 拼接获取跟商户相关的用户key	
            $user = ($store_id) ? "userList_{$store_id}" : "userList_{$user_id}";
			//$this->del_chat($user);
			$data = $this->redis->lRange($user, 0, -1);
			//file_put_contents('bc.log',json_encode($data),FILE_APPEND);	
			$arr = [];
			if(count($data) > 0){
				foreach($data as $k => $v){
					//$this->del_chat("relation_{$user_id}_{$v}");
					$relationKey = ($store_id) ? "relation_{$store_id}_{$v}" : "relation_{$v}_{$user_id}";
					//echo $relationKey.'<hr>';	
					//$this->del_chat($relationKey);
					$getInfo = $this->chat_info($relationKey);
					//var_dump($getInfo);
					//echo '<hr>';
					if(count($getInfo) > 0){
						$arr[] = $getInfo;
					}
				}
			}
            return $arr;			
        } else if($user_id){
			
		}else {
            return [];
        }
    }
	
    /**
     * 读取商家跟用户相关信息
     * @param string $relationkey　商家和用户
     */
    public function chat_info($relationkey)
    {
		//检测用户信息的合法性
        if ($relationkey) {			
			$data = $this->redis->zrevrangebyscore($relationkey, '+inf', '-inf',['limit'=>[0,1]]);
            foreach ($data as $key=>$value) {
                $data[$key] = unserialize($value);
            }			
            return $data[0];			
        } else {
            return [];
        }
    }


    /**
     * 删除聊天记录 删除相关用户的全部聊天记录，和任何人的已读和未读消息。
     * @param string $user_id
     */
    public function del_chat($key){
		$this->redis->del($key);
		/*
        if ($user_id){
            $key = $this->redis->keys('messages:' . $user_id.'_*');
            $this->redis->del($key);
            $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'result' => ''));
        } else {
            $this->ajaxReturn(array('status' => 1, 'msg' => '缺少参数', 'result' => ''));
        }
		*/
    }
	
	/**
	 * 获取token 需要发起post请求 
	 */
	public function get_token(){
		//环信客户端ID
		$clientId = C('clientid');
		//环信客户端secret
		$clientSecret = C('clientsecret');
		//环信orgname
		$orgName = C('orgname');
		//环信appname
		$appName = C('appname');
		//获取token地址
		$tokenUrl = "https://a1.easemob.com/{$orgName}/{$appName}/token";
		//发送的数据包
		$data = [
			'grant_type' => 'client_credentials',
			'client_id' => $clientId,
			'client_secret' => $clientSecret
		];
		//调用发起curl请求函数
		$res = $this->curl_request($tokenUrl,json_encode($data));
		return json_decode($res,true)['access_token'];		
	}	
	
	public function send_msg($to_user_id='88929',$msg='hello,saihu',$from_store='store2'){
		//获取token
		$token = $this->get_token();
		//拼接header头部信息
		$headers[] = 'Content-Type: application/json';
		$headers[] = "Authorization: Bearer {$token}";
		//发送的数据包
		$data = [
			'target_type' => 'users',
			'target' => [$to_user_id],
			'msg' => [
				'type' => 'txt',
				'msg' => $msg,				
			],
			'from' => $from_store
		];
		//环信orgname
		$orgName = C('orgname');
		//环信appname
		$appName = C('appname');		
		$sendUrl = "https://a1.easemob.com/{$orgName}/{$appName}/messages";
		$sendUrl = "http://requestb.in";
		$res = $this->curl_request($sendUrl,urlencode(json_encode($data)),$headers);
		var_dump($res);
		exit();
	}


	/**
	 * 获取远程服务器信息
     * @param string $url 远程服务器URL
     * @param string $data 发起请求传递的数据，如果有数据发起的是post请求
	 * return string $output 返回远程服务器返回的数据
	 */
	public function curl_request($url, $data=null, $headers=[]){
		$curl = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		if (!empty($data) || count($data)>0){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}


	
	
	
}