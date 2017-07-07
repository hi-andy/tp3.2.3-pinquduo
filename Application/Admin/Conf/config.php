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
        '3' => 'http://cdn.pinquduo.cn/activity_3.png',
	    '4' => 'http://cdn.pinquduo.cn/5zhe.png'
    )

);
