<?php
$config = array (
    //应用ID,您的APPID。
    'app_id' => "2016111702900487",

    //商户私钥，您的原始格式RSA私钥
    'merchant_private_key' => "MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANkVr5ECsVBjlXFW
anoMTHGFRid3hYgfyqSy2gR8UiPsj2RJt2yQ7Jw3K4kwmystM3QWoPGN7Sd6qMdL
xAGQWaOe8wCy9h+Zd4+06xhscKHxddJH47fqZvmOdP3DvVoxNNFvLQRfphKOXz5u
XDvg7yAzwwHM09E5fNWFFosIdy8pAgMBAAECgYEAnjoINZHY29c53do20a6VKUkS
0UF0ursxYMpbzlkvJbAO8/InF6KqU1KDEQO0lcvkbQDxXh8sdFIbIug+fUVRj3Cn
z5YjmYJPDtPtZyfogCqqpYi+x94SWZf4FzZlipmUmABCJk/AMtIws1FZ7xMTi+yF
4Cj0fjpPQo7HsyEz5GECQQD8BOAQeRyVMi5dvch8jqELJB0Omn+lkYFBGIwG2Ld0
4saLhNGzmJQVGFWNlV666h7vfkS4eb9CZMJuPtjTIH8TAkEA3IOKrD8akM7/1E2f
ZZLQpksasCb11MrhwnDQU2XaLSBB6dHAGlUUZBQTGQrGGS+recP2lGQmYS1xSy3y
uo2UUwJBAKMANDvzWX1WG48d9NI7HgYqsXCElRLtbYBA9DBpcx7yniAXI9rZUM3k
E1GjzsVuL9wO+zul4wJ6URclJvBHEGkCQGT2PSm8ArfGbs+PcqmY3Lsmq+N3ExsI
gPD7ogZtHcWHfWZGyMPFrH5dypiunCCv+LzZgi5S5Fed7L9VHEtZw00CQHAXeT6s
A+We4qOSUOsj4dqMGFTk+veE/C11ojodnzaoW/RTey8k01FfqFOW5jZmTK4x7xHj
4i5c9Jg74Cao8Ts=",

    //异步通知地址
    'notify_url' => "http://pinquduo.cn/alipay.trade.wap.pay-PHP-UTF-8/notify_url.php",

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


);