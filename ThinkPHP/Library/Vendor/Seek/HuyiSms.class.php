<?php
namespace Seek;

class HuyiSms
{

    public $auth;

    public $cookiefile;

    public $extra = array();

    public $ua = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";

    public static function send($mobile, $captcha)
    {
        // curl -i \
        // -F 'account=cf_bhwd' \
        // -F 'password=cl198982666' \
        // -F 'mobile=13882249210' \
        // -F 'content=尊敬的用户您好，您本次操作的验证码是123456，请不要把验证码泄漏给其他人，如非本人操作，可不用理会' \
        // 'http://106.ihuyi.cn/webservice/sms.php?method=Submit'
        $http = new self();
        $account = C('HUYI_ACCOUNT');
        $password = C('HUYI_PASSWORD');
        $extra_http_params = array();
        if ($proxy = C('HTTP_PROXY')) {
            $extra_http_params['proxy'] = $proxy;
        }
        $sms_content = sprintf('尊敬的用户您好，您本次操作的验证码是%s，请不要把验证码泄漏给其他人，如非本人操作，可不用理会', $captcha);
        $response = $http->post('http://106.ihuyi.cn/webservice/sms.php?method=Submit', compact('account', 'password', 'mobile', 'sms_content'), array(), $extra_http_params);
        $xml_response = simplexml_load_string($response['body']);
        if ($xml_response->code == 2) {
            return $xml_response->smsid;
        } else {
            throw new \Exception($xml_response->msg);
        }
        // ->code
        // ->msg
        // ->smsid
        // list:
        // 0 提交失败
        // 2 提交成功
        // 400 非法ip访问
        // 401 帐号不能为空
        // 402 密码不能为空
        // 403 手机号码不能为空
        // 4030 手机号码已被列入黑名单
        // 404 短信内容不能为空
        // 405 用户名或密码不正确
        // 4050 账号被冻结
        // 4051 剩余条数不足
        // 4052 访问ip与备案ip不符
        // 406 手机格式不正确
        // 407 短信内容含有敏感字符
        // 4070 签名格式不正确
        // 4071 没有提交备案模板
        // 4072 短信内容与模板不匹配
        // 4073 短信内容超出长度限制
        // 408 您的帐户疑被恶意利用，已被自动冻结，如有疑问请与客服联系。
    }

    function __construct()
    {
        $this->cookiefile = tempnam(sys_get_temp_dir(), uniqid("", true));
    }

    function get($url, $params = array(), $extra = array())
    {
        return $this->_request($url, 'GET', $params, array(), array(), $extra);
    }

    function post($url, $data = array(), $files = array(), $extra = array())
    {
        return $this->_request($url, 'POST', array(), $data, $files, $extra);
    }

    function _request($url, $method = 'GET', $params = array(), $data = array(), $files = array(), $extra = array())
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($files) {
                foreach ($files as $k => $file) {
                    $data[$k] = '@' . $file;
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->ua);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookiefile);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookiefile);
        if ($this->auth) {
            curl_setopt($curl, CURLOPT_USERPWD, is_array($this->auth) ? implode(':', $this->auth) : '');
        }
        foreach ((array) $this->extra as $k => $v) {
            curl_setopt($curl, constant('CURLOPT_' . strtoupper($k)), $v);
        }
        foreach ($extra as $k => $v) {
            curl_setopt($curl, constant('CURLOPT_' . strtoupper($k)), $v);
        }
        if ($params) {
            $conn = strpos($url, '?') === False ? '?' : '&';
            $url .= $conn . http_build_query($params);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        
        $body = curl_exec($curl);
        curl_close($curl);
        $header_history = array();
        do {
            list ($header, $body) = explode("\r\n\r\n", $body, 2);
            $headers = array();
            $protocal = 'HTTP/1.1';
            $status = 200;
            $status_message = '';
            foreach (explode("\r\n", $header) as $i => $header_line) {
                if ($i == 0) {
                    list ($protocal, $status, $status_message) = explode(" ", $header_line, 3);
                } else {
                    list ($k, $v) = explode(": ", $header_line);
                    $headers[$k] = $v;
                }
            }
            $header_history[] = compact('protocal', 'status', 'status_message', 'headers');
        } while ($status > 300 and $status < 400);
        // var_dump($protocal, $status, $status_message, $headers, $header_history, $body);
        // return $body;
        return compact('protocal', 'status', 'status_message', 'headers', 'header_history', 'body');
    }
}
