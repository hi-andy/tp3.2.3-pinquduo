<?php
function jsonReturn($status=0,$msg='',$data=''){
    if(empty($data))
        $data = '';
    $info['status'] = $status ? 1 : $status;
    $info['msg'] = $msg;
    $info['result'] = $data;
    exit(json_encode($info));
}

/**
 * 短信发送接口
 */
function AliSendSms($mobile,$code){
    include_once 'AliSMS/aliyun-php-sdk-core/Config.php';
    include_once 'AliSMS/aliyun-php-sdk-sms/Sms/Request/V20160927/SingleSendSmsRequest.php';

    $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", "LTAIKMnJ6nkDRxyb", "bInE4qodURKwdtfuFlOLpkAfUjzfl1");
    $client = new DefaultAcsClient($iClientProfile);
    $request = new Sms\Request\V20160927\SingleSendSmsRequest();
    $request->setSignName("拼趣多");/*签名名称*/
    $request->setTemplateCode("SMS_16750536");/*模板code*/
    $request->setRecNum($mobile);/*目标手机号*/
    $request->setParamString("{\"code\":\"'$code'\"}");/*模板变量，数字一定要转换为字符串*/
    try {
        $response = $client->getAcsResponse($request);
        print_r($response);
    }
    catch (ClientException  $e) {
        print_r($e->getErrorCode());
        print_r($e->getErrorMessage());
    }
    catch (ServerException  $e) {
        print_r($e->getErrorCode());
        print_r($e->getErrorMessage());
    }
}

function saveimage($path) {

    if ($path == '') return false;
    $pathArr = array();
    $url = $path; 
    if(stripos($url,'http://')!== false or stripos($url,'ftp://')!== false){ //仅处理外部路径
        $filename = substr($url, strripos($url, '/')); //图片名.后缀
        $ext = substr($url, strripos($url, '.')); //图片后缀
        $picdir = './' . C("UPLOADPATH") . 'avatar/other/'; //组合图片路径

        //缩略图所需文件夹不存在就生成下
        if(!file_exists($picdir)){$t='生成文件夹失败 请检查权限 生成路径='.$picdir;}
        $filename=strtotime("now") . '.jpg';
        $savepath = $picdir.$filename; //保存新图片路径
//        $img = file_get_contents($path);
//        $res = file_put_contents($savepath,$img);

        ob_start(); //开启缓冲
        readfile($url); //读取图片
        $img = ob_get_contents(); //保存到缓冲区
        ob_end_clean(); //关闭缓冲
        $fp2 = @fopen($savepath, "a"); //打开本地保存图片文件
        fwrite($fp2, $img); //写入图片
        fclose($fp2);

        $src = str_ireplace('./', '', $savepath);
        $image = new \Think\Image();
        $image->open($src);
        $namearr = explode('.', $filename);
        $thumb_url = C("UPLOADPATH") . '/avatar/other/'  . $namearr[0] . '200_200.' . $namearr[1];
        // 生成一个居中裁剪为200*200的缩略图并保存为thumb.jpg
        $image->thumb(200, 200, \Think\Image::IMAGE_THUMB_SCALE)->save($thumb_url);

    } else {
        $thumb_url = $path;
    }
    return str_ireplace('./', '/', '/'.$thumb_url);
}

/**
 * 物流订阅
 */
function reserve_logistics($order_id){
    $post_data = array();
    $post_data["schema"] = 'json' ;

    $delivery_info = M('delivery_doc')->where(array('order_id'=>$order_id))->find();

    //callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
    $post_data["param"] = '{"company":"'.$delivery_info['shipping_code'].'", "number":"'.$delivery_info['shipping_order'].'","from":"广东深圳",
 "to":"", "key":"DLTlUmMA8292", "parameters":{"callbackurl":"http://pinquduo.cn/Api/Base/obtain_logistics"}}';

    $url='http://www.kuaidi100.com/poll';

    $o="";
    foreach ($post_data as $k=>$v)
    {
        $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
    }

    $post_data=substr($o,0,-1);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
//    $result = curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）
    //return $result;
}