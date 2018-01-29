<?php
/**
 * Create by PhpStorm.
 * User: tmqtan
 * Date: 2016/12/20
 * Time: 18:43
 * version: 0.0.1
 */

// 命令空间类调用方式
// new net\http\curl\HttpCurl();
namespace net\http\curl;

require('HttpResponse.class.php');

// HttpCurl 操作类
class HttpCurl{
    // private field members
    private static $_VERSION = '0.0.1';
    private static $_default_opts = array(
        // 不处理HTTP header.
        'CURLOPT_HEADER' => 0,
        // curl操作超时时间为10秒.
        'CURLOPT_TIMEOUT' => 10,
        // 支持所有编码方式
        'CURLOPT_ENCODING' => '',
        // 针对IPv6的兼容处理，强制IPv4，需要用@屏蔽.
        'CURLOPT_IPRESOLVE' => CURL_IPRESOLVE_V4,
        // curl获取的数据只存储在变量中，不显示.
        'CURLOPT_RETURNTRANSFER' => 1,
        // 不验证书和host.
        'CURLOPT_SSL_VERIFYPEER' => 0,
        'CURLOPT_SSL_VERIFYHOST' => 0,
        // 连接服务器超时时间为5秒.
        'CURLOPT_CONNECTTIMEOUT' => 5,
        // 可以调用getinfo获取header消息.
        'CURLINFO_HEADER_OUT' => 1
    );

    private $_opts = array();
    private $_http_header = array();
    private $_post_data = '';
    private $_cookies = '';
    private $_curl = null;
    private $_retry_threshold = 0;
    private $_http_response = null;

    // private function members

    function __construct() {
        $this -> reset_environment();
    }

    private function _init_curl($url) {
        $this -> _curl = curl_init();
        $this -> _opts['CURLOPT_URL'] = $url;

        foreach($this -> _opts as $key => $value) {
            $this -> _set_http_opt($key, $value);
        }

        return $this;
    }

    private function _exec_curl() {
        $this -> _http_response -> init($this -> _curl, (string) curl_exec($this -> _curl));
        return $this;
    }

    private function _end_curl() {
        curl_close($this -> _curl);
        $this -> _curl = null;
        return $this;
    }

    // public functions
    // request functions

    private function _reset_retry_threshold() {
        return $this -> set_retry_threshold(0);
    }

    private function _set_http_header() {
        return $this -> _set_http_opt('CURLOPT_HTTPHEADER', $this -> _http_header);
    }

    private function _set_http_cookies() {
        return $this -> _set_http_opt('CURLOPT_COOKIE', $this -> _cookies);
    }

    private function _set_http_opt($key, $value) {
        $key = constant(strtoupper($key));
        @curl_setopt($this -> _curl, $key, $value);
        return $this;
    }

    private function _set_http_post() {
        return $this -> _set_http_opt('CURLOPT_POST', 1)
                     -> _set_http_opt('CURLOPT_POSTFIELDS', $this -> _post_data);
    }

    private function _try_retry($url, $method) {
        if ($this -> _http_response -> error() && $this -> _retry_threshold > 0) {
            $this -> _retry_threshold --;
            call_user_func(array($this, $method), $url);
        }

        return $this -> _reset_retry_threshold();
    }

    function set_opts($opts, $value = null) {
        if(is_array($opts)) {
            foreach($opts as $field => $val) {
                $this -> _opts[$field] = $val;
            }
        }else {
            if (!empty($opts)) {
                $this -> _opts[$opts] = $value;
            }
        }
        return $this;
    }

    function set_cookies($cookies = '') {
        if(is_array($cookies)) {
            foreach($cookies as $key => $value) {
                $this -> _cookies .= $key . '=' . $value . ';';
            }
        }else {
            $this -> _cookies .= $cookies . ';';
        }
        return $this;
    }

    function open_parse_header() {
        $this -> _http_response -> open_parse_header(1);
        return $this -> _set_http_opt('CURLOPT_HEADER', 1);
    }

    function set_retry_threshold($retry_threshold = 0) {
        $this -> _retry_threshold = $retry_threshold;
        return $this;
    }

    function post($url) {
        return $this -> _init_curl($url)
                     -> _set_http_cookies()
                     -> _set_http_header()
                     -> _set_http_post()
                     -> _exec_curl()
                     -> _end_curl()
                     -> _try_retry($url, 'post');
    }

    function get($url) {
        return $this -> _init_curl($url)
                     -> _set_http_cookies()
                     -> _set_http_header()
                     -> _exec_curl()
                     -> _end_curl()
                     -> _try_retry($url, 'get');
    }

    // 支持字符串和数组类型
    function set_post($data) {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        if (!empty($data)) {
            $this -> _post_data = $data;
        }
        return $this;
    }

    function set_header($key, $value = '') {
        if (is_array($key)) {
            foreach($key as $field => $val) {
                $this -> _http_header[] = $field . ': '. $val;
            }
        }else {
            if (!empty($key)) {
                $this -> _http_header[] =  $key . ':' . $value;
            }
        }

        return $this;
    }

    function set_content_type($value = 'application/x-www-form-urlencoded') {
        return $this -> set_http_header('Content-Type', $value);
    }

    function set_response_type($value = 'text') {
        $this -> _http_response -> set_data_type($value);
        return $this;
    }

    // 服务器不支持Expect字段会报错，谨慎使用.
    function open_post_rapid_channel() {
        return $this->set_http_header('Expect');
    }

    function reset_environment() {
        $this -> _http_response = new HttpResponse();
        $this -> _opts = array_merge(array(), HttpCurl::$_default_opts);
        $this -> _cookies = '';
        $this -> _post_data = '';
        $this -> _http_header = array();
        return $this;
    }

    function response() {
        return $this -> _http_response;
    }

    function version() {
        return HttpCurl::$_VERSION;
    }
};
?>
