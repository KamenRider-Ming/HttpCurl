<?php
/**
 * Create by PhpStorm.
 * User: tmqtan
 * Date: 2016/12/20
 * Time: 18:43
 * version: 0.0.1
 */

namespace net\http\curl;

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

    private function _extract() {
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

    private function _parse_header_content($content) {
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

    private function _convert(){
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

    private function _json_val() {
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

?>
