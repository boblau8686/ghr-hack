<?php
/**
 * Created by PhpStorm.
 * User: bob.liu
 * Date: 2018/6/14
 * Time: 18:13
 */

/**
 * 创建参数(包括签名的处理)
 */
function createParam($paramArr, $showapi_secret) {
    $paraStr = "";
    $signStr = "";
    ksort($paramArr);
    foreach ($paramArr as $key => $val) {
        if ($key != '' && $val != '') {
            $signStr .= $key . $val;
            $paraStr .= $key . '=' . urlencode($val) . '&';
        }
    }
    $signStr .= $showapi_secret;//排好序的参数加上secret,进行md5
    $sign = strtolower(md5($signStr));
    $paraStr .= 'showapi_sign=' . $sign;//将md5后的值作为参数,便于服务器的效验
    return $paraStr;
}

/**
 * 节假日接口
 * @param string $day 日期
 * @return array
 */
function holiday($day) {
    //md5签名方式--非简单签名
    header("Content-Type:text/html;charset=UTF-8");
    date_default_timezone_set("PRC");
    $showapi_appid = '67508';  //在官网的"我的应用"中找到相关值
    $showapi_secret = 'b5af7b0eef35427283d9653def443e23';  //在官网的"我的应用"中找到相关值
    $paramArr = [
        'showapi_appid' => $showapi_appid,
        'day' => $day
        //添加其他参数
    ];

    $param = createParam($paramArr, $showapi_secret);
    $url = 'http://route.showapi.com/894-3?' . $param;
    $result = file_get_contents($url);
    return json_decode($result, true);
}
