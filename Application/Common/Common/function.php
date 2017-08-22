<?php
/**
 * redis读写删 ps:只配置键名则只读取缓存的值
 * @param $key 键名
 * @param null $value 值 可为空
 * @param string $time 有效时间 秒为单位 可为空
 * @param null $del true为删除 可为空
 * @return bool|string
 */
function redis($key, $value=null, $time="", $del=null){
    if (REDIS_SWITCH) {
        $redis = new Redis();
        $redis->connect(REDISIP, PORT);
        $redis->auth(REDISPASS);
        if ($del == true) {
            $redis->delete($key);
        }

        $result = '';
        if ($value) {
            if ($time) {
                $redis->setex($key, $time, $value);
            } else {
                $redis->set($key, $value);
            }
        } else {
            $result = $redis->get($key);
        }
        $redis->close();
        return $result;
    } else {
        redisdelall("*");
    }
}

/**
 * redis队列读写 ps:只配置键名则只读取缓存的值
 * @param $key 键名
 * @param null $value 值 可为空
 */
function redislist($key, $value=null){
    if (REDIS_SWITCH) {
        $redis = new Redis();
        $redis->connect(REDISIP, PORT);
        $redis->auth(REDISPASS);
        if ($key && $value) {
            $redis->rpush($key, $value);
        } else {
             $result =  $redis->lpop($key);
             $redis->close();
             return $result;
        }
    } else {
        redisdelall("*");
    }
}
/**
 * redis删除缓存，可以按关键字批量删除，格式“ keyname ”或“ keyname* ”
 * @param $key
 */
function redisdelall($key)
{
    $redis = new Redis();
    $redis->connect(REDISIP, PORT);
    $redis->auth(REDISPASS);
    $redis->delete($redis->keys($key));
    $redis->close();
}
/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名 
 */
function convert_arr_key($arr, $key_name)
{
	$arr2 = array();
	foreach($arr as $key => $val){
		$arr2[$val[$key_name]] = $val;        
	}
	return $arr2;
}

function encrypt($str){
	return md5(C("AUTH_CODE").$str);
}
            
/**
 * 获取数组中的某一列
 * @param type $arr 数组
 * @param type $key_name  列名
 * @return type  返回那一列的数组
 */
function get_arr_column($arr, $key_name)
{
	$arr2 = array();
	foreach($arr as $key => $val){
		$arr2[] = $val[$key_name];        
	}
	return $arr2;
}


/**
 * 获取url 中的各个参数  类似于 pay_code=alipay&bank_code=ICBC-DEBIT
 * @param type $str
 * @return type
 */
function parse_url_param($str){
    $data = array();
    $parameter = explode('&',end(explode('?',$str)));
    foreach($parameter as $val){
        $tmp = explode('=',$val);
        $data[$tmp[0]] = $tmp[1];
    }
    return $data;
}


/**
 * 二维数组排序
 * @param $arr
 * @param $keys
 * @param string $type
 * @return array
 */
function array_sort($arr, $keys, $type = 'desc')
{
    $key_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $key_value[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($key_value);
    } else {
        arsort($key_value);
    }
    reset($key_value);
    foreach ($key_value as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}


/**
 * 多维数组转化为一维数组
 * @param 多维数组
 * @return array 一维数组
 */
function array_multi2single($array)
{
    static $result_array = array();
    foreach ($array as $value) {
        if (is_array($value)) {
            array_multi2single($value);
        } else
            $result_array [] = $value;
    }
    return $result_array;
}

/**
 * 友好时间显示
 * @param $time
 * @return bool|string
 */
function friend_date($time)
{
    if (!$time)
        return false;
    $fdate = '';
    $d = time() - intval($time);
    $ld = $time - mktime(0, 0, 0, 0, 0, date('Y')); //得出年
    $md = $time - mktime(0, 0, 0, date('m'), 0, date('Y')); //得出月
    $byd = $time - mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')); //前天
    $yd = $time - mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')); //昨天
    $dd = $time - mktime(0, 0, 0, date('m'), date('d'), date('Y')); //今天
    $td = $time - mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')); //明天
    $atd = $time - mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')); //后天
    if ($d == 0) {
        $fdate = '刚刚';
    } else {
        switch ($d) {
            case $d < $atd:
                $fdate = date('Y年m月d日', $time);
                break;
            case $d < $td:
                $fdate = '后天' . date('H:i', $time);
                break;
            case $d < 0:
                $fdate = '明天' . date('H:i', $time);
                break;
            case $d < 60:
                $fdate = $d . '秒前';
                break;
            case $d < 3600:
                $fdate = floor($d / 60) . '分钟前';
                break;
            case $d < $dd:
                $fdate = floor($d / 3600) . '小时前';
                break;
            case $d < $yd:
                $fdate = '昨天' . date('H:i', $time);
                break;
            case $d < $byd:
                $fdate = '前天' . date('H:i', $time);
                break;
            case $d < $md:
                $fdate = date('m月d日 H:i', $time);
                break;
            case $d < $ld:
                $fdate = date('m月d日', $time);
                break;
            default:
                $fdate = date('Y年m月d日', $time);
                break;
        }
    }
    return $fdate;
}


/**
 * 返回状态和信息
 * @param $status
 * @param $info
 * @return array
 */
function arrayRes($status, $info, $url = "")
{
    return array("status" => $status, "info" => $info, "url" => $url);
}
       
/**
 * @param $arr
 * @param $key_name
  * @param $key_name2
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名 数组指定列为元素 的一个数组
 */
function get_id_val($arr, $key_name,$key_name2)
{
	$arr2 = array();
	foreach($arr as $key => $val){
		$arr2[$val[$key_name]] = $val[$key_name2];
	}
	return $arr2;
}

/**
 *  自定义函数 判断 用户选择 从下面的列表中选择 可选值列表：不能为空
 * @param type $attr_values
 * @return boolean
 */
function checkAttrValues($attr_values)
{        
    if((trim($attr_values) == '') && ($_POST['attr_input_type'] == '1'))        
        return false;
    else
        return true;
 }
 
 // 定义一个函数getIP() 客户端IP，
function getIP(){            
    if (getenv("HTTP_CLIENT_IP"))
         $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR"))
         $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";
    return $ip;
}
// 服务器端IP
 function serverIP(){   
  return gethostbyname($_SERVER["SERVER_NAME"]);   
 }  
 
 
 /**
  * 自定义函数递归的复制带有多级子目录的目录
  * 递归复制文件夹
  * @param type $src 原目录
  * @param type $dst 复制到的目录
  */                        
//参数说明：            
//自定义函数递归的复制带有多级子目录的目录
function recurse_copy($src, $dst)
{
	$now = time();
	$dir = opendir($src);
	@mkdir($dst);
	while (false !== $file = readdir($dir)) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				recurse_copy($src . '/' . $file, $dst . '/' . $file);
			}
			else {
				if (file_exists($dst . DIRECTORY_SEPARATOR . $file)) {
					if (!is_writeable($dst . DIRECTORY_SEPARATOR . $file)) {
						exit($dst . DIRECTORY_SEPARATOR . $file . '不可写');
					}
					@unlink($dst . DIRECTORY_SEPARATOR . $file);
				}
				if (file_exists($dst . DIRECTORY_SEPARATOR . $file)) {
					@unlink($dst . DIRECTORY_SEPARATOR . $file);
				}
				$copyrt = copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				if (!$copyrt) {
					echo 'copy ' . $dst . DIRECTORY_SEPARATOR . $file . ' failed<br>';
				}
			}
		}
	}
	closedir($dir);
}

// 递归删除文件夹
function delFile($dir,$file_type='') {
	if(is_dir($dir)){
		$files = scandir($dir);
		//打开目录 //列出目录中的所有文件并去掉 . 和 ..
		foreach($files as $filename){
			if($filename!='.' && $filename!='..'){
				if(!is_dir($dir.'/'.$filename)){
					if(empty($file_type)){
						unlink($dir.'/'.$filename);
					}else{
						if(is_array($file_type)){
							//正则匹配指定文件
							if(preg_match($file_type[0],$filename)){
								unlink($dir.'/'.$filename);
							}
						}else{
							//指定包含某些字符串的文件
							if(false!=stristr($filename,$file_type)){
								unlink($dir.'/'.$filename);
							}
						}
					}
				}else{
					delFile($dir.'/'.$filename);
					rmdir($dir.'/'.$filename);
				}
			}
		}
	}else{
		if(file_exists($dir)) unlink($dir);
	}
}

 
/**
 * 多个数组的笛卡尔积
*
* @param unknown_type $data
*/
function combineDika() {
	$data = func_get_args();
	$data = current($data);
	$cnt = count($data);
	$result = array();
    $arr1 = array_shift($data);
	foreach($arr1 as $key=>$item) 
	{
		$result[] = array($item);
	}		

	foreach($data as $key=>$item) 
	{                                
		$result = combineArray($result,$item);
	}
	return $result;
}


/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
*/
function combineArray($arr1,$arr2) {		 
	$result = array();
	foreach ($arr1 as $item1) 
	{
		foreach ($arr2 as $item2) 
		{
			$temp = $item1;
			$temp[] = $item2;
			$result[] = $temp;
		}
	}
	return $result;
}
/**
 * 将二维数组以元素的某个值作为键 并归类数组
 * array( array('name'=>'aa','type'=>'pay'), array('name'=>'cc','type'=>'pay') )
 * array('pay'=>array( array('name'=>'aa','type'=>'pay') , array('name'=>'cc','type'=>'pay') ))
 * @param $arr 数组
 * @param $key 分组值的key
 * @return array
 */
function group_same_key($arr,$key){
    $new_arr = array();
    foreach($arr as $k=>$v ){
        $new_arr[$v[$key]][] = $v;
    }
    return $new_arr;
}

/**
 * 获取随机字符串
 * @param int $randLength  长度
 * @param int $addtime  是否加入当前时间戳
 * @param int $includenumber   是否包含数字
 * @return string
 */
function get_rand_str($randLength=6,$addtime=1,$includenumber=0){
    if ($includenumber){
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
    }else {
        $chars='abcdefghijklmnopqrstuvwxyz';
    }
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$randLength;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    $tokenvalue=$randStr;
    if ($addtime){
        $tokenvalue=$randStr.time();
    }
    return $tokenvalue;
}

/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
	//return array($http_code, $response,$requestinfo);
}

/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array){
    if(!is_array($array))
        return trim($array);
    return array_map('trim_array_element',$array);
}

/**
 * 检查手机号码格式
 * @param $mobile 手机号码
 */
function check_mobile($mobile){
    if(preg_match('/1[34578]\d{9}$/',$mobile))
        return true;
    return false;
}

/**
 * 检查邮箱地址格式
 * @param $email 邮箱地址
 */
function check_email($email){
    if(filter_var($email,FILTER_VALIDATE_EMAIL))
        return true;
    return false;
}


/**
 *   实现中文字串截取无乱码的方法
 */
function getSubstr($string, $start, $length) {
      if(mb_strlen($string,'utf-8')>$length){
          $str = mb_substr($string, $start, $length,'utf-8');
          return $str.'...';
      }else{
          return $string;
      }
}


/**
 * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
 * @return boolean
 */
function isMobile(){  
    $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';  
    $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';        
    function CheckSubstrs($substrs,$text){  
        foreach($substrs as $substr)  
            if(false!==strpos($text,$substr)){  
                return true;  
            }  
            return false;  
    }
    $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
    $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');  
          
    $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||  
              CheckSubstrs($mobile_token_list,$useragent);  
          
    if ($found_mobile){  
        return true;  
    }else{  
        return false;  
    }  
}

//php获取中文字符拼音首字母
function getFirstCharter($str){
      if(empty($str))
      {
            return '';          
      }
      $fchar=ord($str{0});
      if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
      $s1=iconv('UTF-8','gb2312',$str);
      $s2=iconv('gb2312','UTF-8',$s1);
      $s=$s2==$str?$s1:$str;
      $asc=ord($s{0})*256+ord($s{1})-65536;
     if($asc>=-20319&&$asc<=-20284) return 'A';
     if($asc>=-20283&&$asc<=-19776) return 'B';
     if($asc>=-19775&&$asc<=-19219) return 'C';
     if($asc>=-19218&&$asc<=-18711) return 'D';
     if($asc>=-18710&&$asc<=-18527) return 'E';
     if($asc>=-18526&&$asc<=-18240) return 'F';
     if($asc>=-18239&&$asc<=-17923) return 'G';
     if($asc>=-17922&&$asc<=-17418) return 'H';
     if($asc>=-17417&&$asc<=-16475) return 'J';
     if($asc>=-16474&&$asc<=-16213) return 'K';
     if($asc>=-16212&&$asc<=-15641) return 'L';
     if($asc>=-15640&&$asc<=-15166) return 'M';
     if($asc>=-15165&&$asc<=-14923) return 'N';
     if($asc>=-14922&&$asc<=-14915) return 'O';
     if($asc>=-14914&&$asc<=-14631) return 'P';
     if($asc>=-14630&&$asc<=-14150) return 'Q';
     if($asc>=-14149&&$asc<=-14091) return 'R';
     if($asc>=-14090&&$asc<=-13319) return 'S';
     if($asc>=-13318&&$asc<=-12839) return 'T';
     if($asc>=-12838&&$asc<=-12557) return 'W';
     if($asc>=-12556&&$asc<=-11848) return 'X';
     if($asc>=-11847&&$asc<=-11056) return 'Y';
     if($asc>=-11055&&$asc<=-10247) return 'Z';
     return null;
}

/**
 * 为图片链接加入前缀
 */
function addHttpHead($value){
	if($value && $value!='null' && !empty($value)){
		return  C('SERVER_HTTP').$value;
	}else{
		return null;
	}
}

/**
 * 处理图片模型log
 */
function handelImage($array){
	if(!$array){
		return null;
	}
	$result=array();
	if(is_array($array[0])){
		foreach($array as $value){
			$value['origin'] = addHttpHead($value['origin']);
			$value['small'] = addHttpHead($value['small']);
			$result[]=$value;
		}
	}else{
		$array['origin'] = addHttpHead($array['origin']);
		$array['small'] = addHttpHead($array['small']);
		return $array;
	}
	return $result;
}

    /*
         ; 说明：需要包含接口声明文件，可将该文件拷贝到自己的程序组织目录下。
         $accountSid= ;  说明：主账号，登陆云通讯网站后，可在"控制台-应用"中看到开发者主账号ACCOUNT SID。
         $accountToken= ;  说明：主账号Token，登陆云通讯网站后，可在控制台-应用中看到开发者主账号AUTH TOKEN。
         $appId=;  说明：应用Id，如果是在沙盒环境开发，请配置"控制台-应用-测试DEMO"中的APPID。如切换到生产环境， 请使用自己创建应用的APPID。
         $serverIP='app.cloopen.com';  说明：生成环境请求地址：app.cloopen.com。
         $serverPort='8883';  说明：请求端口 ，无论生产环境还是沙盒环境都为8883.
         $softVersion='2013-12-26';  说明：REST API版本号保持不变。
     */

function sendMessage($to,$datas,$tempId){
	vendor('YTX.CCPRestSDK');
	// 初始化REST SDK
	global $accountSid,$accountToken,$appId,$serverIP,$serverPort,$softVersion;


	$accountSid = '8a216da857ad33250157d11a1e75206c';
	$accountToken = '88344df4b05c42ad942700eb6468329e';
	$appId = '8a216da857ad33250157d11a1f272071';
	$serverIP = 'app.cloopen.com';
	$serverPort = '8883';
	$softVersion = '2013-12-26';

	$rest = new \REST($serverIP,$serverPort,$softVersion);
	$rest->setAccount($accountSid,$accountToken);
	$rest->setAppId($appId);

	// 发送模板短信
//	echo "Sending TemplateSMS to $to";
	$result = $rest->sendTemplateSMS($to,$datas,$tempId);
	if($result == NULL ) {
		echo "result error!";
		return;
	}
	if($result->statusCode!=0) {
//		echo "模板短信发送失败!";
//		echo "error code :" . $result->statusCode . "";
//		echo "error msg :" . $result->statusMsg . "";
//		exit(json_encode(array('status'=>-1,'msg'=>'短信发送失败')));
		return false;
		//下面可以自己添加错误处理逻辑
	}else{
//		echo "模板短信发送成功!";
//		// 获取返回信息
//		$smsmessage = $result->TemplateSMS;
//		echo "dateCreated:".$smsmessage->dateCreated."";
//		echo "smsMessageSid:".$smsmessage->smsMessageSid."";
//		exit(json_encode(array('status'=>1,'msg'=>'短信发送成功','result'=>array('dateCreated'=>$smsmessage->dateCreated))));
		return true;
	}
}

/**
 * 导出数据为excel表格
 *@param $data     一个二维数组,结构如同从数据库查出来的数组 ['="8697650248373900"', '="89860616010010169817"', '="2016-03-15 05:09:07"', '45.06']
 *@param $title    excel的第一行标题,一个数组,如果为空则没有标题
 *@param $filename 下载的文件名
 */
function exportExcel($data=[], $title=[], $filename='report'){
    header("Content-type: application/octet-stream");
    header("Accept-Ranges: bytes");
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=".$filename.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!empty($title)){
        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gb2312", $v);
        }
        $title = implode("\t", $title);
        echo $title."\n";
    }
    if (!empty($data)){
        foreach($data as $key=>$val){
            foreach ($val as $ck => $cv) {
                $cv = $cv;
                $data[$key][$ck] = iconv("utf-8", "gb2312", $cv);
            }
            $data[$key] = implode("\t", $data[$key]);
        }
        echo implode("\n",$data);
    }
}

//转换图片地址
function TransformationImgurl($url) {
    if (strstr($url, "http") !== false) {
        return $url;
    } else {
        return C('HTTP_URL').$url;
    }
}

//异步获取页面内容
function async_get_url($url_array,$post_data = array(), $wait_usec = 0){
    if (!is_array($url_array))
        return false;
    $o = "";
    foreach ($post_data as $k => $v) {
        $o .= "$k=" . urlencode($v) . "&";
    }
    $post_data = substr($o, 0, -1);
    $wait_usec = intval($wait_usec);
    $data    = array();
    $handle  = array();
    $running = 0;
    $mh = curl_multi_init(); // multi curl handler
    $i = 0;
    foreach($url_array as $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return don't print
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
        curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
        curl_multi_add_handle($mh, $ch); // 把 curl resource 放进 multi curl handler 里
        $handle[$i++] = $ch;
    }
    /* 执行 */
    do {
        curl_multi_exec($mh, $running);
        if ($wait_usec > 0) /* 每个 connect 要间隔多久 */
            usleep($wait_usec); // 250000 = 0.25 sec
    } while ($running > 0);
    /* 读取资料 */
    foreach($handle as $i => $ch) {
        $content  = curl_multi_getcontent($ch);
        $data[$i] = (curl_errno($ch) == 0) ? $content : false;
    }
    /* 移除 handle*/
    foreach($handle as $ch) {
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);
    return $data;
}

//提取内容中的图片地址
function getImgs($content,$order='ALL'){
	$pattern="/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.bmp|\.GIF|\.JPG|\.PNG|\.BMP]))[\'|\"].*?[\/]?>/";
	preg_match_all($pattern,$content,$match);
	if(isset($match[1])&&!empty($match[1])){
		if($order==='ALL'){
			return $match[1];
		}
		if(is_numeric($order)&&isset($match[1][$order])){
			return $match[1][$order];
		}
	}
	return '';
}

//提取图片宽高
function getImgSize($arr)
{
	$num = count($arr);
	$res = array();
	for($i=0;$i<$num;$i++){
		$size = getimagesize($arr[$i]);
		$res[$i]['origin'] = $arr[$i];
		$res[$i]['width']=$size[0];
		$res[$i]['height']=$size[1];
	}
	return $res;
}

//获取小数点后面的长度
function getFloatLength($num) {
    $count = 0;
    $temp = explode ( '.', $num );
    if (sizeof ( $temp ) > 1) {
        $decimal = end ( $temp );
        $count = strlen ( $decimal );
    }
    return $count;
}

//取天花板值
function operationPrice($price)
{
	$price = (float)$price;
	$price = explode(".",$price);
	$price[1]=substr($price[1],0,2);
	$price = (float)($price[0].'.'.$price[1]);
	$price = $price+0.01;
	return (string)$price;
}

//混合分割中英文字符
function str_split_utf8($str){
    $split=1;
    $array=array();
    for($i=0;$i<strlen($str);){
        $value=ord($str[$i]);
        if($value>127){
            if($value>=192&&$value<=223) $split=2;
            elseif($value>=224 && $value<=239) $split=3;
            elseif($value>=240 && $value<=247) $split=4;
        }else{
            $split=1;
        }
        $key=NULL;
        for($j=0;$j<$split;$j++,$i++){
            $key.=$str[$i];
        }
        array_push($array,$key);
    }
    return $array;
}

//https请求(支持GET和POST)
function http_request($url, $data=null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

//地址处理问题
function getAdress($adress)
{
	$cha = $adress;
	//新疆维吾尔自治区伊犁哈萨克自治州霍城县
	//判断字符串里面是否包含省
	if(strstr($cha,"省")){
		//按省市区切割
		if(substr_count($cha,'省')>1){
			$cha = explode('省',$cha);
			$province = $cha[0].'省';
			$cha[1] = '省'.$cha[2];
		}else{
			$cha = explode('省',$cha);
			$province = $cha[0].'省';
		}
		if(strstr($cha[1],'自治州'))
		{
			$cha = explode('自治州',$cha[1]);
			$city = $cha[0].'自治州';
			$area = $cha[1];
		}elseif(strstr($cha[1],'地区')){
			$cha = explode('地区',$cha[1]);
			$city = $cha[0].'地区';
			$area = $cha[1];
		}elseif(strstr($cha[1],'自治区')){
			$cha = explode('自治区',$cha[1]);
			$city = $cha[0].'自治区';
			$area = $cha[1];
		}elseif(strstr($cha[1],'行政单位')){
			$cha = explode('行政单位',$cha[1]);
			$city = $cha[0].'行政单位';
			$area = $cha[1];
		}elseif(substr_count($cha[1],'市')>1){
			$cha = explode('市',$cha[1]);
			$city = $cha[0].'市';
			$area = $cha[1].'市';
		}else{
			$cha = explode('市',$cha[1]);
			$city = $cha[0].'市';
			$area = $cha[1];
		}

	}elseif(strstr($cha,"北京市") || strstr($cha,"天津市") || strstr($cha,"上海市") ||strstr($cha,"重庆市")){//判断是否为直辖市
		//按市区切割
		$cha = explode('市',$cha);
		$province = $cha[0].'市';
		$city = $cha[0].'市';
		$area = $cha[2];
	}elseif(strstr($cha,"内蒙古自治区") || strstr($cha,"广西壮族自治区") || strstr($cha,"宁夏回族自治区") || strstr($cha,"西藏自治区") || strstr($cha,"新疆维吾尔自治区")){ //判断是否为自治区
		//按自治区切割
		if(substr_count($cha,'自治区')>1){
			$cha = explode('自治区',$cha);
			$province = $cha[0].'自治区';
			$cha[1] = '自治区'.$cha[2];
		}else{
			$cha = explode('自治区',$cha);
			$province = $cha[0].'自治区';
		}

		if(strstr($cha[1],"盟")){
			$cha = explode('盟',$cha[1]);
			$city = $cha[0].'盟';
		}elseif(strstr($cha[1],"地区")){
			$cha = explode('地区',$cha[1]);
			$city = $cha[0].'地区';
		}elseif(strstr($cha[1],"自治州")){
			$cha = explode('自治州',$cha[1]);
			$city = $cha[0].'自治州';
		}elseif(strstr($cha[1],'行政单位')){
			$cha = explode('行政单位',$cha[1]);
			$city = $cha[0].'行政单位';
		}elseif(strstr($cha[1],"市")){
			$cha = explode('市',$cha[1]);
			$city = $cha[0].'市';
		}
		$area = $cha[1];
	}elseif(strstr($cha,"行政区"))
	{
		$cha = explode('行政区',$cha);
		$province = $cha[0].'行政区';
		$city = $province;
		$area = $province;
	}
	$adress_arry['province'] =$province;
	$adress_arry['city'] = $city;
	$adress_arry['district'] = $area;
	return $adress_arry;
}

function get_raise_pic($goods_id='',$goods_img='',$goods_name='',$price=''){
	$accessKeyId = C('OSSKEYID');//去阿里云后台获取秘钥
	$accessKeySecret = C("OSSKEYSECRET");//去阿里云后台获取秘钥
	$endpoint = C('OSSENDPOINT');//你的阿里云OSS地址
	$bucket= C('OSSBUCKET');//oss中的文件上传空间
	$font = 'Public/images/yahei.ttf';//字体
	// 背景图片宽度
	$bg_w    = 600;
	// 背景图片高度
	$bg_h    = 700; // 背景图片高度
	//二维码宽
	$ewmWidth = 200;
	//二维码高
	$ewmHeight = 200;
	//商品图片宽度
	$goodWidth = 590;
	//商品图片高度
	$goodHeight = 368;
	//二维码距离右边框距离
	$ewmLeftMargin = 20;
	//二维码距离底部框距离
	$ewmBottomMargin = 24;
	// 背景图片
	$background = imagecreatetruecolor($bg_w,$bg_h);
	// 为真彩色画布创建白色背景，再设置为透明
	$color   = imagecolorallocate($background, 255, 255, 255);
	//颜色填充
	imagefill($background, 0, 0, $color);
	//透明图片
	imageColorTransparent($background, $color);

	// 开始位置X
	$start_x    = intval($bg_w-$ewmWidth-$ewmLeftMargin);
	// 开始位置Y
	$start_y    = intval($bg_h-$ewmHeight-$ewmBottomMargin);
	// 宽度
	$pic_w   = intval($ewmWidth);
	// 高度
	$pic_h   = intval($ewmHeight);

	//商品图片资源
	$goodresource = imagecreatefromjpeg($goods_img);
	//图片合并
	imagecopyresized($background,$goodresource,5,5,0,0,$goodWidth,$goodHeight,imagesx($goodresource),imagesy($goodresource));
	//文字颜色
	$fontcolor = imagecolorallocate($background, 204,204,204);

	//左上角标志
	$downresource = imagecreatefrompng('Public/images/jiaobiao@2x.png');
	//图片合并
	imagecopyresized($background,$downresource,50,4,0,0,70,80,imagesx($downresource),imagesy($downresource));

	//商品名
	if(!empty(msubstr($goods_name,14,13))){
		//第一行
		$one = msubstr($goods_name,0,13);
		imagettftext($background,20,0,20,503,imagecolorallocate($background, 0,0,0),$font,$one);
		$two = msubstr($goods_name,13,13);
		if(empty(msubstr($goods_name,26,13))){
			imagettftext($background,20,0,20,547,imagecolorallocate($background, 0,0,0),$font,$two);
		}else{
			$two = msubstr($goods_name,13,11).'...';
			imagettftext($background,20,0,20,547,imagecolorallocate($background, 0,0,0),$font,$two);
		}
	}else{
		imagettftext($background,20,0,20,503,imagecolorallocate($background, 0,0,0),$font,$goods_name);
	}

	imagettftext($background,17,0,intval($start_x+28),intval($start_y-39),$fontcolor,$font,"长按二维码查看");

	imagettftext($background,20,0,20,606,imagecolorallocate($background, 226,0,37),$font,'快来拼趣多秒购0元商品');

	imagettftext($background,19,0,20,663,imagecolorallocate($background, 226,0,37),$font,'￥');

	imagettftext($background,40,0,50,663,imagecolorallocate($background, 226,0,37),$font,'0');

	imagettftext($background,19,0,90,663,$fontcolor,$font,"原价:".$price);
	//价格上的灰线
//	if(strlen($price)==4){
//		imageline($background, 90, 654, 197, 654, $fontcolor);
//		imageline($background, 90, 653, 197, 653, $fontcolor);
//	}elseif(strlen($price)==5){
//		imageline($background, 90, 654, 220, 654, $fontcolor);
//		imageline($background, 90, 653, 220, 653, $fontcolor);
//	}elseif (strlen($price)==6){
//		imageline($background, 90, 654, 228, 654, $fontcolor);
//		imageline($background, 90, 653, 228, 653, $fontcolor);
//	}elseif (strlen($price)==7){
//		imageline($background, 90, 654, 240, 654, $fontcolor);
//		imageline($background, 90, 653, 240, 653, $fontcolor);
//	}else{
//		imageline($background, 90, 654, 190, 654, $fontcolor);
//		imageline($background, 90, 653, 190, 653, $fontcolor);
//	}

	$path = "Public/upload/raise";
	if (!file_exists($path)){
		mkdir($path);
	}
	//拉图片传到七牛云
	$path1 = "Public/upload/raise/goods_". $goods_id .'.jpg';
	imagejpeg($background,$path1);
	vendor('aliyun.autoload');

	$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
	$object = "Public/upload/raise/goods_". $goods_id .'.jpg';//想要保存文件的名称
	$file = $path1;//文件路径，必须是本地的。

	try{
		$ossClient->uploadFile($bucket,$object,$file);
		$url =  "http://{$bucket}.{$endpoint}/".$object;
		$p = imagecreatefromstring(curl_file_get_contents($url));
		if(!empty($p)){
			unlink($path1);
		}
	} catch(OssException $e) {
		print $e->getMessage();
	}

	return $url;
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false){
	if(function_exists("mb_substr")){
		if($suffix)
			return mb_substr($str, $start, $length, $charset)."...";
		else
			return mb_substr($str, $start, $length, $charset);
	}elseif(function_exists('iconv_substr')) {
		if($suffix)
			return iconv_substr($str,$start,$length,$charset)."...";
		else
			return iconv_substr($str,$start,$length,$charset);
	}
	$re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
	$re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
	$re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
	$re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
	preg_match_all($re[$charset], $str, $match);
	$slice = join("",array_slice($match[0], $start, $length));
	if($suffix) return $slice."…";
	return $slice;
}

function curl_file_get_contents($durl){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $durl);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
	curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$r = curl_exec($ch);
	curl_close($ch);
	return $r;
}
/**
 * 对象 转 数组
 * 创建者 吴银海
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
	$obj = (array)$obj;
	foreach ($obj as $k => $v) {
		if (gettype($v) == 'resource') {
			return;
		}
		if (gettype($v) == 'object' || gettype($v) == 'array') {
			$obj[$k] = (array)object_to_array($v);
		}
	}

	return $obj;
}