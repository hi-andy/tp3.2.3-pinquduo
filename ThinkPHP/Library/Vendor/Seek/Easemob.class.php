<?php
namespace Seek;
/**
 * 环信-服务器端REST API
 */
class Easemob {
    public static $CACHE_DIR='./';
    
    public static $options = array(
        'client_id'=>'YXA6BH51YNYSEeWp0juJJQio3Q',
        'client_secret'=>'YXA6YBXJIS48iNEdRLsOPsEDu1uDh18',
        'org_name'=>'dandan9609',
        'app_name'=>'yellowish',
    );
    
	private $client_id;
	private $client_secret;
	private $org_name;
	private $app_name;
	private $url;

	/**
	 * 初始化参数
	 *
	 * @param array $options
	 * @param $options['client_id']
	 * @param $options['client_secret']
	 * @param $options['org_name']
	 * @param $options['app_name']
	 */
	public function __construct($options) {
		$this->client_id = isset ( $options ['client_id'] ) ? $options ['client_id'] : Easemob::$options['client_id'];
		$this->client_secret = isset ( $options ['client_secret'] ) ? $options ['client_secret'] : Easemob::$options['client_secret'];
		$this->org_name = isset ( $options ['org_name'] ) ? $options ['org_name'] : Easemob::$options['org_name'];
		$this->app_name = isset ( $options ['app_name'] ) ? $options ['app_name'] : Easemob::$options['app_name'];
		if (! empty ( $this->org_name ) && ! empty ( $this->app_name )) {
			$this->url = 'https://a1.easemob.com/' . $this->org_name . '/' . $this->app_name . '/';
		}
	}
	
	/**
	 * 开放注册模式
	 *
	 * @param $options['username'] 用户名
	 * @param $options['password'] 密码
	 */
	public function openRegister($options) {
		$url = $this->url . "users";
		$result = $this->postCurl ( $url, $options, $head = 0 );
		return $result;
	}

	/**
	 * 授权注册模式 || 批量注册
	 *
	 * @param $options['username'] 用户名（环形id）
	 * @param $options['password'] 密码
	 *        	批量注册传二维数组
	 *        
	 *      环信ID需要使用英文字母和（或）数字的组合
     *      环信ID不能使用中文
     *      环信ID不能使用email地址
     *      环信ID不能使用UUID
     *      环信ID中间不能有空格或者井号（#）等特殊字符
     *      允许的环信ID正则 “[a-zA-Z0-9_\-.]*” (a~z大小写字母和数字和下划线和横线和英文点) 其他都不允许
	 */
	public function accreditRegister($options) {
		$url = $this->url . "users";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, $options, $header );
		return $result;
	}

	/**
	 * 获取指定用户详情
	 *
	 * @param $username 用户名
	 */
	public function userDetails($username) {
		$url = $this->url . "users/" . $username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = 'GET' );
		return $result;
	}

	/**
	 * 重置用户密码
	 *
	 * @param $options['username'] 用户名
	 * @param $options['password'] 密码
	 * @param $options['newpassword'] 新密码
	 */
	public function editPassword($options) {
		$url = $this->url . "users/" . $options ['username'] . "/password";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, $options, $header, $type = 'PUT');
		return $result;
	}
	/**
	 * 删除用户
	 *
	 * @param $username 用户名
	 */
	public function deleteUser($username) {
		$url = $this->url . "users/" . $username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = 'DELETE' );
	}

	/**
	 * 批量删除用户
	 * 描述：删除某个app下指定数量的环信账号。上述url可一次删除300个用户,数值可以修改 建议这个数值在100-500之间，不要过大
	 *
	 * @param $limit="300" 默认为300条
	 * @param $ql 删除条件
	 *        	如ql=order+by+created+desc 按照创建时间来排序(降序)
	 */
	public function batchDeleteUser($limit = "300", $ql = '') {
		$url = $this->url . "users?limit=" . $limit;
		if (! empty ( $ql )) {
			$url = $this->url . "users?ql=" . $ql . "&limit=" . $limit;
		}
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = 'DELETE' );
	}

	/**
	 * 给一个用户添加一个好友
	 *
	 * @param
	 *        	$owner_username
	 * @param
	 *        	$friend_username
	 */
	public function addFriend($owner_username, $friend_username) {
		$url = $this->url . "users/" . $owner_username . "/contacts/users/" . $friend_username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header );
	}
	/**
	 * 删除好友
	 *
	 * @param
	 *        	$owner_username
	 * @param
	 *        	$friend_username
	 */
	public function deleteFriend($owner_username, $friend_username) {
		$url = $this->url . "users/" . $owner_username . "/contacts/users/" . $friend_username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "DELETE" );
	}
	/**
	 * 查看用户的好友
	 *
	 * @param
	 *        	$owner_username
	 */
	public function showFriend($owner_username) {
		$url = $this->url . "users/" . $owner_username . "/contacts/users/";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET" );
	}
	// +----------------------------------------------------------------------
	// | 聊天相关的方法
	// +----------------------------------------------------------------------
	/**
	 * 查看用户是否在线
	 *
	 * @param
	 *        	$username
	 */
	public function isOnline($username) {
		$url = $this->url . "users/" . $username . "/status";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET" );
		return $result;
	}
	/**
	 * 发送消息
	 *
	 * @param string $from_user
	 *        	发送方用户名
	 * @param array $username
	 *        	array('1','2')
	 * @param string $target_type
	 *        	默认为：users 描述：给一个或者多个用户(users)或者群组发送消息(chatgroups)
	 * @param string $content
	 * @param array $ext
	 *        	自定义参数
	 */
	function sendText($username, $content, $from_user = "1", $target_type = "users", $ext=array()) {  
		$option ['target_type'] = $target_type;
		$option ['target'] = (array)$username;
		$option ['msg'] = array('type'=>'txt', 'msg'=>$content);
		$option ['from'] = $from_user;
		$params ['type'] = "txt";
        if($ext){
            $option ['ext'] = $ext;
        }
		$url = $this->url . "messages";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, $option, $header );
		return $result;
	}
	
    function sendPayload($username, $data, $from_user = "1", $target_type = "users", $ext=array()) {
		$option ['target_type'] = $target_type;
		$option ['target'] = (array)$username;
		$option ['msg'] = array('type'=>'cmd', 'action'=>$data);
		$option ['from'] = $from_user."";
        if(!empty($ext)){
             $option ['ext'] = $ext;
        }
		$url = $this->url . "messages";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, $option, $header );
		return $result;
	}
	
	/**
	 * 获取app中所有的群组
	 */
	public function chatGroups() {
		$url = $this->url . "chatgroups";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET" );
		return $result;
	}
	/**
	 * 创建群组
	 *
	 * @param $option['groupname'] //群组名称,
	 *        	此属性为必须的
	 * @param $option['desc'] //群组描述,
	 *        	此属性为必须的
	 * @param $option['public'] //是否是公开群,
	 *        	此属性为必须的 true or false
	 * @param $option['approval'] //加入公开群是否需要批准,
	 *        	没有这个属性的话默认是true, 此属性为可选的
	 * @param $option['owner'] //群组的管理员,
	 *        	此属性为必须的
	 * @param $option['members'] //群组成员,此属性为可选的
	 */
	public function createGroups($option) {
		$url = $this->url . "chatgroups";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, $option, $header );
		return $result;
	}
	/**
	 * 获取群组详情
	 *
	 * @param
	 *        	$group_id
	 */
	public function chatGroupsDetails($group_id) {
		$url = $this->url . "chatgroups/" . $group_id;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET" );
		return $result;
	}
	/**
	 * 删除群组
	 *
	 * @param
	 *        	$group_id
	 */
	public function deleteGroups($group_id) {
		$url = $this->url . "chatgroups/" . $group_id;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "DELETE" );
		return $result;
	}
	/**
	 * 获取群组成员
	 *
	 * @param
	 *        	$group_id
	 */
	public function groupsUser($group_id) {
		$url = $this->url . "chatgroups/" . $group_id . "/users";
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET" );
		return $result;
	}
	/**
	 * 群组添加成员
	 *
	 * @param
	 *        	$group_id
	 * @param
	 *        	$username
	 */
	public function addGroupsUser($group_id, $username) {
		$url = $this->url . "chatgroups/" . $group_id . "/users/" . $username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "POST" );
		return $result;
	}
	/**
	 * 群组删除成员
	 *
	 * @param
	 *        	$group_id
	 * @param
	 *        	$username
	 */
	public function delGroupsUser($group_id, $username) {
		$url = $this->url . "chatgroups/" . $group_id . "/users/" . $username;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "DELETE" );
		return $result;
	}
	
	/**
	 * 修改群组简介
	 *
	 * @param
	 *        	$group_id
	 */
	public function updateGroupsDescription($group_id,$description) {
	    $url = $this->url . "chatgroups/" . $group_id ;
	    $access_token = $this->getToken ();
	    $header [] = 'Authorization: Bearer ' . $access_token;
	    $option ['description'] = $description;
	    $result = $this->postCurl ( $url, $option, $header, $type = 'PUT' );
	    return $result;
	}
	
	/**
	 * 聊天消息记录
	 *
	 * @param $ql 查询条件如：$ql
	 *        	= "select+*+where+from='" . $uid . "'+or+to='". $uid ."'+order+by+timestamp+desc&limit=" . $limit . $cursor;
	 *        	默认为order by timestamp desc
	 * @param $cursor 分页参数
	 *        	默认为空
	 * @param $limit 条数
	 *        	默认20
	 */
	public function chatRecord($ql = '', $cursor = '', $limit = 20) {
		$ql = ! empty ( $ql ) ? "ql=" . $ql : "order+by+timestamp+desc";
		$cursor = ! empty ( $cursor ) ? "&cursor=" . $cursor : '';
		$url = $this->url . "chatmessages?" . $ql . "&limit=" . $limit . $cursor;
		$access_token = $this->getToken ();
		$header [] = 'Authorization: Bearer ' . $access_token;
		$result = $this->postCurl ( $url, '', $header, $type = "GET " );
		return $result;
	}
	/**
	 * 获取Token
	 */
	public function getToken() {
		$option ['grant_type'] = "client_credentials";
		$option ['client_id'] = $this->client_id;
		$option ['client_secret'] = $this->client_secret;
		$url = $this->url . "token";
		$fp = @fopen (self::$CACHE_DIR . "/easemob.txt", 'r' );
		if ($fp) {
			$arr = unserialize ( fgets ( $fp ) );
			if ($arr ['expires_in'] < time ()) {
			    \Think\Log::record('环信token expires at '.$arr ['expires_in'],'WARN');
				$result = $this->postCurl ( $url, $option, $head = 0 );
				$result ['expires_in'] = $result ['expires_in'] + time ();
				@fwrite ( $fp, serialize ( $result ) );
				return $result ['access_token'];
				fclose ( $fp );
				exit ();
			}
			\Think\Log::record('环信token catche','WARN');
			return $arr ['access_token'];
			fclose ( $fp );
			exit ();
		}
		\Think\Log::record('环信token file unexist','WARN');
		$result = (array)$this->postCurl ( $url, $option, $head = 0 );
		$result ['expires_in'] = $result ['expires_in'] + time ();
		$fp = @fopen (self::$CACHE_DIR . "/easemob.txt", 'w' );
		@fwrite ( $fp, serialize ( $result ) );
		return $result ['access_token'];
		fclose ( $fp );
	}

	/**
	 * CURL Post
	 */
	private function postCurl($url, $option, $header = array(), $type = 'POST') {
		$curl = curl_init (); // 启动一个CURL会话
		curl_setopt ( $curl, CURLOPT_URL, $url ); // 要访问的地址
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE ); // 对认证证书来源的检查
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE ); // 从证书中检查SSL加密算法是否存在
		curl_setopt ( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
		if (! empty ( $option )) {
			$options = json_encode ( $option );
			curl_setopt ( $curl, CURLOPT_POSTFIELDS, $options ); // Post提交的数据包
		}
		curl_setopt ( $curl, CURLOPT_TIMEOUT, 15 ); // 设置超时限制防止死循环
        if($header){
		    curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header ); // 设置HTTP头
        }
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 ); // 获取的信息以文件流的形式返回
		curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, $type );
		$result = curl_exec ( $curl ); // 执行操作
		curl_close ( $curl ); // 关闭CURL会话
		
        $result=json_decode($result, true);//将结果解析成json
//         dump($result);
        if(!empty($result['error'])){
            \Think\Log::record('环信postCurl error'.$result['error'],'WARN');
           throw new \Exception($result['error_description'], -99);
        }
        if(!empty($result['entities'])){
            return $result['entities'];
        }
        if(!empty($result['data'])){
            return $result['data'];
        }
        return $result;
	}
}
