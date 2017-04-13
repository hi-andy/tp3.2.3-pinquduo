<?php
/**
 * 微信交互类
 */ 
namespace Home\Controller;
use Think\Controller;
class WeixinController extends BaseController {
    public $client;

    public function _initialize(){
        parent::_initialize();
        //获取微信配置信息
        $wechat_list = M('wx_user')->select();
        $wechat_config = $wechat_list[0];
        $options = array(
 			'token'=>$wechat_config['w_token'], //填写你设定的key
 			'encodingaeskey'=>$wechat_config['aeskey'], //填写加密用的EncodingAESKey
 			'appid'=>$wechat_config['appid'], //填写高级调用功能的app id
 			'appsecret'=>$wechat_config['appsecret'], //填写高级调用功能的密钥
        		);

    }

    public function oauth(){

    }
    
    public function index(){
        //exit($_GET["echostr"]);        
        $this->responseMsg();
    }    
    
    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
      	//extract post data
	 if (empty($postStr))                 	
        	exit("");
         
                /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                if(empty($keyword))
                    exit("Input something...");
                
                // 图文回复
                $wx_img = M('wx_img')->where("keyword like '%$keyword%'")->find();
                if($wx_img)
                {
                    $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[%s]]></Title> 
                                        <Description><![CDATA[%s]]></Description>
                                        <PicUrl><![CDATA[%s]]></PicUrl>
                                        <Url><![CDATA[%s]]></Url>
                                    </item>                               
                                </Articles>
                                </xml>";                                        
                    $resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,'news','1',$wx_img['title'],$wx_img['desc']
                            , $wx_img['pic'], $wx_img['url']);
                    exit($resultStr);                   
                }
                
                
                // 文本回复
                $wx_text = M('wx_text')->where("keyword like '%$keyword%'")->find();
                if($wx_text)
                {
                    $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";                    
                    $contentStr = $wx_text['text'];
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
                    exit($resultStr);                   
                }
                
                
                	
                

      
    }    
}