<?php
// 使用ShowApi接口获取法定节假日安排
include "extend/showapi.php";

// 创建Runtime目录
$runtimeDir = dirname(__FILE__) . '/runtime/';
if (!is_dir($runtimeDir)) {
    mkdir($runtimeDir, 0755);
}

// 生成当天假日信息缓存
$todayNum = date('Ymd');
$holidayFilename = $runtimeDir . 'holiday' . $todayNum;
if (!file_exists($holidayFilename)) {
    $todayInfo = holiday($todayNum);
    // 将今天的假日信息写入缓存文件
    file_put_contents($holidayFilename, $todayInfo['showapi_res_body']['type']);
}
// 判断今天是否非工作日，如果是则退出。
$holidayInfo = file_get_contents($holidayFilename);
if ($holidayInfo != '1') {
    exit;
}


// GHR配置
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
$todayDate = date('Y-m-d');
$logStr = $todayDate . ':' . '09' . str_pad($amRandMinute, 2, '0', STR_PAD_LEFT) . '&18' . str_pad($pmRandMinute, 2, '0', STR_PAD_LEFT);

// 获取签到记录
$checkinFilename = $runtimeDir . 'datetime';
if (!file_exists($checkinFilename)) {
    file_put_contents($checkinFilename, $logStr);
} else {
    $checkinLog = file_get_contents($checkinFilename);
    $checkinLogInfo = explode(':', $checkinLog);
    if (strtotime($checkinLogInfo[0]) < strtotime($todayDate)) {
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
        $log = date('Y-m-d H:i:s') . ':' . json_encode($checkResult) . "\n";
        echo $log;

        // 签到不成功则报警
        if ($checkResult['Msg'] != 'ok') {
            // 发送QQ邮件
            require_once 'extend/QQMailer.php';
            $mailer = new QQMailer();
            $title = $todayNum . ' GHR签到失败';
            $content = $log;
            try {
                $mailer->send('boblau8686@qq.com', $title, $content);
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                echo 'Send Mail Fail: ' . $e->getMessage() . "\n";
            }
        }
    }
}

curl_close($ch);
