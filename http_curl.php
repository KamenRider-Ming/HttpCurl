<?php
/**
 * Create by PhpStorm.
 * User: tmqtan
 * Date: 2016/12/20
 * Time: 18:43
 * version: 0.0.1
 */

// 命令空间类调用方式
// new tmqtan\net\HttpCurl()
namespace tmqtan\net;

// Response类，用于对响应数据进行一定的处理，比如转换json字符串进行输出等.
class HttpResponse {
    private $_result = array(
        'error_code' => 0,
        'error_message' => 'success'
    );
    private $_raw_data = '';
    private $_extract_data = '';
    private $_info = '';
    private $_data_type = 'text';
    private $_open_parse_header = 0;

    function __construct() { }

    function init($curl, $data) {
        $this -> _raw_data = $data;
        $this -> _info = curl_getinfo($curl);
        $this -> _result['error_code'] = curl_errno($curl);
        $this -> _result['error_message'] =
            $this -> _result['error_code'] ? curl_error($curl) : 'success';

        return $this -> _extract() -> _convert();
    }

    function _extract() {
        $header_size = $this -> _info['header_size'];

        if ($this -> _open_parse_header) {
            $this ->_extract_data['header_content'] = substr($this -> _raw_data, 0, $header_size);
            $this -> _extract_data['header'] =
                $this -> _parse_header_content($this -> _extract_data['header_content']);
            $this -> _extract_data['body'] = substr($this -> _raw_data, $header_size);
        }else {
            $this -> _extract_data['body'] = $this -> _raw_data;
        }
        return $this;
    }

    function _parse_header_content($content) {
        $header_ct = explode("\r\n\r\n", trim($content));
        $header_ct = array_shift($header_ct);
        $header_ct = explode("\r\n", $header_ct);
        array_shift($header_ct);

        $header = array();
        foreach($header_ct as $line_header) {
            $key_value = explode(': ', $line_header);
            if ($key_value[0] == 'Set-Cookie') {
                $header['Set-Cookie'][] = $key_value[1];
            }else {
                $header[$key_value[0]] = $key_value[1];
            }
        }

        return $header;
    }

    function _convert(){
        switch($this -> _data_type) {
            case 'json' :
                $this -> _json_val();
        }
        return $this;
    }

    function open_parse_header($flag) {
        $this -> _open_parse_header = $flag;
        return $this;
    }

    function set_data_type($data_type) {
        $this -> _data_type = $data_type;
        return $this;
    }

    function _json_val() {
        $this -> _extract_data['body'] = json_decode($this -> _extract_data['body']);
        return $this;
    }

    function raw_data() {
        return $this -> _raw_data;
    }

    function extract_data() {
        return $this -> _extract_data;
    }

    function result() {
        return $this -> _result;
    }

    function info() {
        return $this -> _info;
    }

    function error() {
        return $this -> _result['error_code'];
    }

    function http_code() {
        return $this -> _info['http_code'];
    }

    function body() {
        return $this -> _extract_data['body'];
    }

    function header_content() {
        return $this -> _extract_data['header_content'];
    }

    function data_type() {
        return $this -> _data_type;
    }

    function header() {
        return $this -> _extract_data['header'];
    }
};

class HttpCurl{
    // private field members
    private static $_version = '0.0.1';
    private static $_default_options = array(
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

    private $_options = array();
    private $_http_header = array();
    private $_post_data = '';
    private $_cookies = '';
    private $_curl = null;
    private $_retry_threshold = 0;
    private $_http_response = null;

    // private function members

    function __construct() {
        $this -> _http_response = new HttpResponse();
        $this -> _options = array_merge(array(), HttpCurl::$_default_options);
    }

    function _init_curl($url) {
        $this -> _curl = curl_init();
        $this -> _options['CURLOPT_URL'] = $url;

        foreach($this -> _options as $key => $value) {
            if (is_string($key)) {
                $key = constant(strtoupper($key));
            }
            @curl_setopt($this -> _curl, $key, $value);
        }

        return $this;
    }

    function _exec_curl() {
        $this -> _http_response -> init($this -> _curl, (string) curl_exec($this -> _curl));
        return $this;
    }

    function _end_curl() {
        curl_close($this -> _curl);
        $this -> _curl = null;
        return $this;
    }

    // public functions
    // request functions

    function _reset_retry_threshold() {
        return $this -> set_retry_threshold(0);
    }

    function _set_http_header() {
        curl_setopt($this -> _curl, CURLOPT_HTTPHEADER, $this -> _http_header);
        return $this;
    }

    function _set_http_cookies() {
        curl_setopt($this -> _curl, CURLOPT_COOKIE, $this -> _cookies);
        return $this;
    }

    function _set_http_post() {
        curl_setopt($this -> _curl, CURLOPT_POST, 1);
        curl_setopt($this -> _curl, CURLOPT_POSTFIELDS, $this->_post_data);
        return $this;
    }

    function _try_retry($url, $method) {
        if ($this -> _http_response -> error() && $this -> _retry_threshold > 0) {
            $this -> _retry_threshold --;
            $method($url);
        }

        return $this -> _reset_retry_threshold();
    }

    function set_options($opts, $value = null) {
        if(is_array($opts)) {
            foreach($opts as $field => $val) {
                $this -> _options[$field] = $val;
            }
        }else {
            if (!empty($opts)) {
                $this -> _options[$opts] = $value;
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
        return $this -> set_options('CURLOPT_HEADER', 1);
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
                     -> _try_retry($url, $this -> post);
    }

    function get($url) {
        return $this -> _init_curl($url)
                     -> _set_http_cookies()
                     -> _set_http_header()
                     -> _exec_curl()
                     -> _end_curl()
                     -> _try_retry($url, $this -> get);
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
    function open_post_fast_channel() {
        return $this->set_http_header('Expect');
    }

    function reset_environment() {
        $this -> _http_response = new HttpResponse();
        $this -> _options = array_merge(array(), HttpCurl::$_default_options);
        $this -> _cookies = '';
        $this -> _post_data = '';
        $this -> _http_header = array();
        return $this;
    }

    function response() {
        return $this -> _http_response;
    }

    function version() {
        return $this -> _version;
    }
};

?>
