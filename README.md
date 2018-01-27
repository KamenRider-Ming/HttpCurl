# HttpCurl
PHP语言下，实现对CURL的封装，暂时只支持GET和POST，尽快将所有操作和相关注解补齐，还会将一些明显的BUG给修复一下，同时会适当重构代码，将一些重复性或者功能类似的代码进行优化

### 使用方式说明
1. 对于请求，可以直接链式设置相关数据，发送数据和获取数据
2. 当调用获取响应数据时，返回的是一个HttpResponse类
3. 每一次新的请求时需要调用reset_environment()将所有环境进行重置

### 类方法说明

#### HttpCurl类

1. set_post() 设置post数据
2. set_cookies() 设置cookies数据
3. set_header() 设置头信息
4. post() 发送POST请求
5. get() 发送GET请求
6. response() 得到响应数据
...

#### HttpResponse类

1. body() 获取响应内容
2. raw_data() 获取响应原数据
3. header() 获取响应头消息
4. error() 获取操作错误码
5. http_code() 获取HTTP请求错误码
...

### 简单例子

```php
// post
$http_curl = new tmqtan\net\HttpCurl();
$http_rsp = $http_curl -> set_header($header)
                  -> set_cookies($cookie_fields)
                  -> set_post($post_fields)
                  -> post($url)
                  -> response()
echo $http_rsp -> body();
// get
$http_rsp = $http_curl -> reset_environment()
                  -> set_header($header)
                  -> set_cookies($cookie_fields)
                  -> get("http://127.0.0.1:8080")
                  -> response()
echo $http_rsp -> header();
```
