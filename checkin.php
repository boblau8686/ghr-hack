<?php
// 使用ShowApi接口获取法定节假日安排
include "extend/showapi.php";
$todayInfo = holiday2018(date('Ymd'));
// 判断今天是否是工作日，非工作日则退出。
if ($todayInfo['showapi_res_body']['type'] != 1) {
    exit;
}

// 脚本不超时
set_time_limit(0);

// 基本配置
$baseUrl = 'http://hr.baodao.com.cn:1001/AppWebService/GhrApp.asmx/';
$loginMethod = 'Login';
$checkinMethod = 'InsertStaffCardRecord';

// 初始化CURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

# 登录
$loginUrl = $baseUrl . $loginMethod;
curl_setopt($ch, CURLOPT_URL, $loginUrl);
// 登录数据
$loginPostData = [
    'userNo' => '1045079',
    'appID' => 'A|MI 5-8.0.0|2.2.3|9103|862033034169365|0.0.0.0',
    'company' => '10',
    'pwd' => 'bobliu86'
];

// 开始请求
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginPostData));
$response = curl_exec($ch);

// 解析出AppToken
$loginResult = json_decode(simplexml_load_string($response), 1);
$appToken = $loginResult['Data']['AppToken'];

# 签到
$checkinUrl = $baseUrl . $checkinMethod;
curl_setopt($ch, CURLOPT_URL, $checkinUrl);

// 随机签到分钟数
$amRandMinute = rand(1, 58);
$pmRandMinute = rand($amRandMinute, 59);

// 签到日志字符串
$logStr = date('Y-m-d') . ':' . '09' . str_pad($amRandMinute, 2, '0', STR_PAD_LEFT) . '&19' . str_pad($pmRandMinute, 2, '0', STR_PAD_LEFT);

// 获取签到记录
$checkinFilename = dirname(__FILE__) . '/checkin.log';
if (!file_exists($checkinFilename)) {
    file_put_contents($checkinFilename, $logStr);
} else {
    $checkinLog = file_get_contents($checkinFilename);
    $checkinLogInfo = explode(':', $checkinLog);
    if (strtotime($checkinLogInfo[0]) < strtotime(date('Y-m-d'))) {
        file_put_contents($checkinFilename, $logStr);
    } elseif (in_array(date('Hi'), explode('&', trim($checkinLogInfo[1])))) { // 等于当前的随机时间才签到
        // 签到数据
        $checkinPostData = [
            'ValidYN' => 'Y',
            'AppToken' => $appToken,
            'CardTime' => date('Y-m-d H:i'),
            'Address' => '潮州路办公室考勤点',
            'AppID' => 'A|MI 5-8.0.0|2.2.3|9103|862033034169365|0.0.0.0',
            'StaffID' => '794514',
            'UserID' => '9103',
            'Dimension' => '31.2650' . rand(10, 99),
            'Longitude' => '121.4141' . rand(10, 99),
            'MobileID' => '862033034169365',
            'CardRemarkSZ' => ''
        ];
        // 延迟随机秒
        sleep(rand(1, 59));
        // 开始请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($checkinPostData));
        $response = curl_exec($ch);

        $checkResult = json_decode(simplexml_load_string($response), 1);
        echo date('Y-m-d H:i:s') . ':' . json_encode($checkResult) . "\n";

        // 签到不成功则报警
        if ($checkResult['Msg'] != 'ok') {
            // TODO
        }
    }
}

curl_close($ch);
