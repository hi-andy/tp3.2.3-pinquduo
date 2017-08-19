<?php
return array(
	'HTTP_URL'=>'http://pinquduo.cn',
	'URL_HTML_SUFFIX'       =>  '',  // URL伪静态后缀设置
    // 'OUTPUT_ENCODE' =>  true, //页面压缩输出支持   配置了 没鸟用
    'PAYMENT_PLUGIN_PATH' =>  PLUGIN_PATH.'payment',
    'LOGIN_PLUGIN_PATH' =>  PLUGIN_PATH.'login',
    'SHIPPING_PLUGIN_PATH' => PLUGIN_PATH.'shipping',
    'FUNCTION_PLUGIN_PATH' => PLUGIN_PATH.'function',
	'SHOW_PAGE_TRACE' => false,
	'CFG_SQL_FILESIZE'=>5242880,
    //'URL_MODEL'=>1, // 
    //默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' => 'Public:dispatch_jump',
    //默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'Public:dispatch_jump',


    'activity_icon' => array('1'=>'618',
        '2' => '双11',
        '3' => '优惠券',
	    '4' => '5折活动'
    ),
    'activity_src' => array('1'=>'618',
        '2' => '双11',
        '3' => 'https://cdn2.pinquduo.cn/activity_3.png',
	    '4' => 'https://cdn2.pinquduo.cn/5zhe.png'
    ),
	'msg_logo' => array(
	array('id'=>'1','url'=>'https://cdn2.pinquduo.cn/fa@2x.png','name'=>'罚款'),//罚款
	array('id'=>'2','url'=>'https://cdn2.pinquduo.cn/huo@2x.png','name'=>'活动'),//活动
	array('id'=>'3','url'=>'https://cdn2.pinquduo.cn/xin@2x.png','name'=>'新版本'),//新版本
	array('id'=>'4','url'=>'https://cdn2.pinquduo.cn/gong@2x.png','name'=>'公告'),//公告
),
);
