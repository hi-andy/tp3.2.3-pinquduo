<?php
//  加载常量配置文件
header("Content-type:text/html;charset=utf-8");
//redis 开关、服务器IP、密码、失效时间 20170411 simon
define("REDIS_SWITCH", true); //true、false
define("REDISIP", "localhost");
define("PORT", 6379);
//define("REDISPASS", "Wang1314");
define("REDISTIME", 120);
//define("CDN", "http://cdn.pinquduo.cn"); //七牛云CDN加速域名
define("CDN", "https://cdn2.pinquduo.cn");  //七牛云CDN加速域名; 升级 https 2017-8-19　Hua
define("ORDER_END_TIME",150);//未支付订单过期时间 单位秒
define("SCWS","http://39.108.12.198");//SCWS 分词服务器地址


return array(

    //　缓存配置 2017-8-26 Hua
    'DATA_CACHE_TIME' => 0, // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS' => false, // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK' => false, // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX' => '', // 缓存前缀
    'DATA_CACHE_TYPE' => 'Redis', // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'REDIS_HOST' => 'r-wz9266773b1c1894.redis.rds.aliyuncs.com',
    'REDIS_PASSWORD' => 'Wang1314',

    'SERVER_HTTP' => 'http://api.hn.pinquduo.cn/',
    'HTTP_URL'=>'http://api.hn.pinquduo.cn/',
    'PAY_URL'=>'https://pinquduo.cn/',
    /* 加载公共函数 */
    'LOAD_EXT_FILE' =>'common',
    'AUTH_CODE' => "TPSHOP", //安装完毕之后不要改变，否则所有密码都会出错
    //'URL_CASE_INSENSITIVE' => false, //URL大小写不敏感
    'LOAD_EXT_CONFIG'=>'db,route', // 加载数据库配置文件
    'LOAD_EXT_CONFIG'=>'db', // 加载数据库配置文件
    'URL_MODEL'=>3, // 如果需要 隐藏 index.php  打开这行"URL_MODEL"注释 同时在apache环境下 开启 伪静态模块，  如果在nginx 下需要另外配置，参考thinkphp官网手册
    /*
     * RBAC认证配置信息
     */

    'SESSION_AUTO_START'        => true,
    'USER_AUTH_ON'              => true,
    'USER_AUTH_TYPE'            => 1,         // 默认认证类型 1 登录认证 2 实时认证
    'USER_AUTH_KEY'             => 'authId',  // 用户认证SESSION标记
    'ADMIN_AUTH_KEY'            => 'administrator',
    'USER_AUTH_MODEL'           => 'User',    // 默认验证数据表模型
    'AUTH_PWD_ENCODER'          => 'md5',     // 用户认证密码加密方式
    'USER_AUTH_GATEWAY'         => '/Public/login',// 默认认证网关
    'NOT_AUTH_MODULE'           => 'Public',  // 默认无需认证模块
//     'REQUIRE_AUTH_MODULE'       => '',        // 默认需要认证模块
//     'NOT_AUTH_ACTION'           => '',        // 默认无需认证操作
//     'REQUIRE_AUTH_ACTION'       => '',        // 默认需要认证操作
    'GUEST_AUTH_ON'             => false,     // 是否开启游客授权访问
    'GUEST_AUTH_ID'             => 0,         // 游客的用户ID
    'DB_LIKE_FIELDS'            => 'title|remark',
    'RBAC_ROLE_TABLE'           => 'think_role',
    'RBAC_USER_TABLE'           => 'think_role_user',
    'RBAC_ACCESS_TABLE'         => 'think_access',
    'RBAC_NODE_TABLE'           => 'think_node',
    'SHOW_PAGE_TRACE'           =>false,         //显示调试信息
    //'RBAC_ERROR_PAGE'         => '/Public/tp404.html',
    //'ERROR_PAGE'=>'/Index/Index/error_page.html',
    //'ERROR_PAGE'=>'/index.php/Home/Tperror/tp404.html',
    // 表单令牌验证相关的配置参数
    'TOKEN_ON'      =>    true,  // 是否开启令牌验证 默认关闭
    'TOKEN_NAME'    =>    '__hash__',    // 令牌验证的表单隐藏字段名称，默认为__hash__
    'TOKEN_TYPE'    =>    'md5',  //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET'   =>    true,  //令牌验证出错后是否重置令牌 默认为true 
    'TAGLIB_LOAD'   => true,
    'APP_AUTOLOAD_PATH'  =>'@.TagLib',
    'TAGLIB_BUILD_IN'  =>  'cx,tpshop', // tpshop 为自定义标签类名称
    'TMPL_TEMPLATE_SUFFIX'  =>  '.html',     // 默认模板文件后缀
    'URL_HTML_SUFFIX'       =>  'html',  // URL伪静态后缀设置  默认为html  去除默认的 否则很多地址报错

    'ORDER_STATUS' => array(
        0 => '待确认',
        1 => '已确认',
        2 => '已收货',
        3 => '已取消',
        4 => '已完成',//评价完
    ),
    'SHIPPING_STATUS' => array(
        0 => '未发货',
        1 => '已发货',
        2 => '部分发货'
    ),
    'PAY_STATUS' => array(
        0 => '未支付',
        1 => '已支付',
    ),
    'IS_SHOW' => array(
        0 => '显示',
        1 => '未显示',
    ),
    'IS_AUDIT' => array(
        0 => '未审核',
        1 => '已审核',
        2 => '已驳回',
    ),
    'IS_ON_SALE' => array(
        0 => '下架',
        1 => '上架',
    ),
    'IS_HAITAO' => array(
        0 => '非海淘',
        1 => '海淘店',
    ),
    'ORDER_TYPE' => array(
        1 => '未付款',
        2 => '待发货',
        3 => '待收货',
        4 => '已完成',
        5 => '已取消',
        6 => '待退货',
        7 => '已退货',
        8 => '待退货',
        9 => '已退货',
        10 => '拼团中,待付款',
        11 => '拼团中，已付款',
        12 => '未成团,待退款',
        13 => '未成团，已退款',
        14 => '已成团，待发货',
        15 => '已成团，待收货',
        16 => '拒绝受理'
    ),
    'IS_SPECIAL' => array(
        0 => '普通商品',
        1 => '海淘商品',
        2 => '限时秒杀',
        4 => '9.9专场',
        5 => '多人拼',
        6 => '免单拼',
        7 => '0.1秒杀',
        8 => '为我拼',
        9 => '趣多严选'
    ),

    'SINGLE_BUY' => array(
        1 => '未付款',
        2 => '待发货',
        3 => '待收货',
        4 => '已完成',
        5 => '已取消',
        6 => '待退货',
        7 => '已退货',
        8 => '待退货',
        9 => '已退货',
        16 => '拒绝受理'
    ),
    'GROUP_BUY' => array(
        4 => '已完成',
        5 => '已取消',
        10 => '拼团中-待付款',
        11 => '拼团中-已付款',
        12 => '未成团-待退款',
        13 => '未成团-已退款',
        14 => '已成团-待发货',
        15 => '已成团-待收货',
        16 => '拒绝受理'
    ),
    'SEX' => array(
        0 => '保密',
        1 => '男',
        2 => '女'
    ),
    'COUPON_TYPE' => array(
        0 => '面额模板',
        1 => '按用户发放',
        2 => '注册发放',
        3 => '邀请发放',
        4 => '线下发放'
    ),
    'PROM_TYPE' => array(
        0 => '默认',
        1 => '抢购',
        2 => '团购',
        3 => '优惠'
    ),

    // 订单用户端显示状态
    'WAITPAY'=>' AND pay_status = 0 AND order_status = 0 AND pay_code !="cod" ', //订单查询状态 待支付
    'WAITSEND'=>' AND (pay_status=1 OR pay_code="cod") AND shipping_status !=1 AND order_status in(0,1) ', //订单查询状态 待发货
    'WAITRECEIVE'=>' AND shipping_status=1 AND order_status = 1 ', //订单查询状态 待收货    
    'WAITCCOMMENT'=> ' AND order_status=2 ', // 待评价 确认收货     //'FINISHED'=>'  AND order_status=1 ', //订单查询状态 已完成 
    'FINISH'=> ' AND order_status = 4 ', // 已完成
    'CANCEL'=> ' AND order_status = 3 ', // 已取消

    'ORDER_STATUS_DESC' => array(
        'WAITPAY' => '待支付',
        'WAITSEND'=>'待发货',
        'WAITRECEIVE'=>'待收货',
        'WAITCCOMMENT'=>'待评价',
        'CANCEL'=>'已取消',
        'FINISH'=>'已完成', //
    ),
    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
    =======

    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
    退货按钮（联系客服）  所有退换货操作， 都需要人工介入   不支持在线退换货
     */

    // 'site_url'=>'http://www.tp-shop.cn', // tpshop 网站域名 已经改写入数据库
    'MODULE_ALLOW_LIST' => array('Home','Admin','Store','Api','Chat','Api_2_0_0','Api_2_0_1','Api_2_0_2','Api_3_0','Storeapp'),

    'DEFAULT_MODULE'        =>  'Home',  // 默认模块
    //'DEFAULT_MODULE'        =>  'Index',  // 默认模块
    'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
    'DEFAULT_ACTION'        =>  'index', // 默认操作名称    

    'APP_SUB_DOMAIN_DEPLOY'   =>    0, // 开启子域名或者IP配置
    'APP_SUB_DOMAIN_RULES'    =>    array(
        'm.tpshop.com'   => 'Mobile/',  // 手机访问网站
    ),

    'DEFAULT_FILTER'        => 'trim',   // 系统默认的变量过滤机制\

    //信鸽推送
    'Xinge' => array(
        'AD_ACCESSID' =>'2100231415',     //用户端
        'AD_SECRETKEY' => 'c1909e4753e1b23bd75e9ec0c9b43d63',//用户端
        'IOS_ACCESSID' =>'2200231416',    //用户端
        'IOS_SECRETKEY' => 'dec2efb82a46bfe7c8a83cf1dc3ecd9d'//用户端
    ),

    //兑吧
    'Duiba' => array(
        'AppKey'=>'3vkLv2J1UNYbXaL3TzximCeymvLd',
        'AppSecret'=>'uxpcaPdUTNV8o1nCWmKNSgGBxkd'
    ),
    //上传图片
    //
    'UPLOADPATH' =>'Uploads/return/',

    'STORE_FROM' => array('0'=>'个人','1'=>'企业'),
    'STORE_TYPE' => array('0'=>'个人','1'=>'旗舰店','2'=>'专卖店','3'=>'专营店','4'=>'普通店'),
    'STORE_SHOW' => array('0'=>'营业中','1'=>'停业'),
    'STORE_STATUS' => array('1'=>'营业中','0'=>'停业'),
    'Check_STATUS' => array('0'=>'未审核','1'=>'审核通过','2'=>'审核未通过'),

    //支付宝配置参数
    'alipay_config'=>array(
        'partner' =>'2088521292269473',     //这里是你在成功申请支付宝接口后获取到的PID
        'private_key_path'  => getcwd().'/Application/Common/Conf/alipaykey/rsa_private_key.pem', //商户的私钥（后缀是.pen）文件相对路径
        'ali_public_key_path'=> getcwd().'/Application/Common/Conf/alipaykey/rsa_public_key.pem', //支付宝公钥（后缀是.pen）文件相对路径
        'sign_type'=>strtoupper('RSA'),
        'input_charset'=> strtolower('utf-8'),
        'cacert'=> getcwd().'\\cacert.pem',
        'transport'=> 'http',
        'key'=>'e399tx04dtzbuhx7p1v4jvkakkpcd2sd',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'http://pinquduo.cn/Store/Alipayapi/notify_url',
        //这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
        'return_url'=>'http://pinquduo.cn/Store/Alipayapi/returnurl',
        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_email'=>'2660357732@qq.com',
        //支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
        'successpage'=>'Index/index',
        //支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
        'errorpage'=>'Admin/login',
    ),
    // 支付宝手机 wap 支付配置参数
    'alipay_wap' => array (
        //应用ID,您的APPID。
        'app_id' => "2016111702900487",

        //商户私钥，您的原始格式RSA私钥
        'merchant_private_key' => "MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANkVr5ECsVBjlXFWanoMTHGFRid3hYgfyqSy2gR8UiPsj2RJt2yQ7Jw3K4kwmystM3QWoPGN7Sd6qMdLxAGQWaOe8wCy9h+Zd4+06xhscKHxddJH47fqZvmOdP3DvVoxNNFvLQRfphKOXz5uXDvg7yAzwwHM09E5fNWFFosIdy8pAgMBAAECgYEAnjoINZHY29c53do20a6VKUkS0UF0ursxYMpbzlkvJbAO8/InF6KqU1KDEQO0lcvkbQDxXh8sdFIbIug+fUVRj3Cnz5YjmYJPDtPtZyfogCqqpYi+x94SWZf4FzZlipmUmABCJk/AMtIws1FZ7xMTi+yF4Cj0fjpPQo7HsyEz5GECQQD8BOAQeRyVMi5dvch8jqELJB0Omn+lkYFBGIwG2Ld04saLhNGzmJQVGFWNlV666h7vfkS4eb9CZMJuPtjTIH8TAkEA3IOKrD8akM7/1E2fZZLQpksasCb11MrhwnDQU2XaLSBB6dHAGlUUZBQTGQrGGS+recP2lGQmYS1xSy3yuo2UUwJBAKMANDvzWX1WG48d9NI7HgYqsXCElRLtbYBA9DBpcx7yniAXI9rZUM3kE1GjzsVuL9wO+zul4wJ6URclJvBHEGkCQGT2PSm8ArfGbs+PcqmY3Lsmq+N3ExsIgPD7ogZtHcWHfWZGyMPFrH5dypiunCCv+LzZgi5S5Fed7L9VHEtZw00CQHAXeT6sA+We4qOSUOsj4dqMGFTk+veE/C11ojodnzaoW/RTey8k01FfqFOW5jZmTK4x7xHj4i5c9Jg74Cao8Ts=",

        //异步通知地址
        'notify_url' => "https://pinquduo.cn/alipay.trade.wap.pay-PHP-UTF-8/notify_url.php",

        //同步跳转
        'return_url' => "http://mitsein.com/alipay.trade.wap.pay-PHP-UTF-8/return_url.php",

        //编码格式
        'charset' => "UTF-8",

        //签名方式
        'sign_type'=>"RSA",

        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB",


    ),
    // 分享地址
    'SHARE_URL' => 'http://wx.pinquduo.cn',

    'UPLOAD_FILE_QINIU'     => array (
        'maxSize'           => 20*1024*1024,//文件大小
        'rootPath'          => './',
        'savePath'          => 'img',// 文件上传的保存路径
        'saveName'          => array ('uniqid', ''),
        'exts'              => ['jpg', 'jpeg', 'bmp', 'gif', 'png'],  // 设置附件上传类型
        'driver'            => 'Qiniu',//七牛驱动
        'driverConfig'      => array (
            'accessKey'        => '15gPbXtT9oIJ2EpAuUsHJFPcmZ68qxTXnHTpqwgG',
            'secretKey'        => '2c1Jyq1_xt3sIbODugIWLNAGC9kwHZS9xmpHxmjm',
            'domain'           => 'ooc3vwe04.bkt.clouddn.com',
            'bucket'           => 'imgbucket',
        )
    ),

    // 0.1秒杀时间段，时间/日期
    'SecondBuy' => array(
        'dates' => array('2017-5-13', '2017-5-14', '2017-5-15','2017-5-16', '2017-5-17', '2017-5-18','2017-5-19', '2017-5-20', '2017-5-22','2017-5-23', '2017-5-24', '2017-5-25','2017-5-26', '2017-5-27', '2017-5-28','2017-5-29', '2017-5-30', '2017-5-31'),
        'times' => array('10:00', '13:00', '16:00', '19:00')
    ),

    //　五折专享开始时间
    'DiscountTime' => '2017-07-08',

    'TYPE_STATUS' => array(
        array('id'=>'0','name'=>'处理中'),//延迟发货
        array('id'=>'1','name'=>'已执行'),//虚假发货
        array('id'=>'2','name'=>'已撤回'),//假货处理
    ),
    'OSSKEYID' => 'LTAIKMnJ6nkDRxyb',
    'OSSKEYSECRET' => 'bInE4qodURKwdtfuFlOLpkAfUjzfl1',
    'OSSENDPOINT' => 'oss-cn-shenzhen.aliyuncs.com',
    'OSSBUCKET' => 'pqd',

);


