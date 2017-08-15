<?php
// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);
// 定义应用目录
define('APP_PATH','./Application/');
//  定义插件目录
define('PLUGIN_PATH','plugins/');

define('UPLOAD_PATH','Public/upload/'); // 编辑器图片上传路径
define('TPSHOP_CACHE_TIME',86400); // TPshop 缓存时间
define('SITE_URL','http://'.$_SERVER['HTTP_HOST']); // 网站域名
define('HTML_PATH','./Application/Runtime/Html/');
//静态缓存文件目录，HTML_PATH可任意设置，此处设为当前项目下新建的html目录
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
header("Access-Control-Allow-Origin:*");