<?php
return array(
   'API_SECRET_KEY'=>'zuoapp.la', // app 调用的签名秘钥// app 调用的签名秘钥
	'PAGE_SIZE' =>20,
	'HTTP_URL'=>'http://pinquduo.cn',
	'SIGN_KEY' => 'pinquduo',
	'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
	'automatic_time' => 5*24*60*60,
	
	'alipay_config'=>array(
		'partner' =>'2088521292269473',     //这里是你在成功申请支付宝接
		//口后获取到的PID；
                'private_key_path' => getcwd().'/Application/Common/Conf/alipaykey/rsa_private_key.pem', //商户的私钥（后缀是.pen）文件相对路径
                'ali_public_key_path'=> getcwd().'/Application/Common/Conf/alipaykey/rsa_public_key.pem', //支付宝公钥（后缀是.pen）文件相对路径
                'sign_type'=>strtoupper('RSA'),
                'input_charset'=> strtolower('utf-8'),
                'cacert'=> getcwd().'\\cacert.pem',
                'transport'=> 'http',
        ),
    'mobile_white_list'=>[
//        '13138166196',
//        '13138166197',
    ],
    'ip_white_list'=>[
//        ip2long('127.0.0.1'),
//        ip2long('192.168.1.198'),
    ],
);