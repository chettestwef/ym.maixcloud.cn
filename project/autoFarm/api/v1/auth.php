<?php
header('Content-Type: application/json');
require_once __DIR__ . '/functions.php';

require_post_and_check_user_agent();

$authFile = 'auth_data.json';
$input = json_decode(file_get_contents('php://input'), true);
$machineCode = $input['machine_code'] ?? '';
$cardCode = $input['card_code'] ?? '';

if (!$machineCode || !$cardCode) {
    exit(json_encode(['success' => false, 'message' => '参数传递不正确']));
}

$data = json_decode(file_get_contents($authFile), true);

// 获取客户端IP和当前时间
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$currentTime = date('Y-m-d H:i:s');

if (isset($data[$cardCode])) {
    if (empty($data[$cardCode]['machine_code'])) {
        $data[$cardCode]['machine_code'] = $machineCode;
        $data[$cardCode]['last_ip'] = $ip;
        $data[$cardCode]['first_login_time'] = $currentTime;
        $data[$cardCode]['last_login_time'] = $currentTime;
        file_put_contents($authFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    if ($data[$cardCode]['machine_code'] !== $machineCode) {
        exit(json_encode(['success' => false, 'message' => '授权与机器码绑定的授权不符，鉴权失败。请联系获得此程序的人员处理~' . $machineCode]));
    }

    if (time() > strtotime($data[$cardCode]['expires'])) {
        exit(json_encode(['success' => false, 'message' => '授权已过期，鉴权失败。请联系获得此程序的宝处理~']));
    }

    // 如果存在lock字段，返回lock和lock.message
    if (isset($data[$cardCode]['lock'])) {
        exit(json_encode([
            'success' => false,
            'lock' => [
                'message' => $data[$cardCode]['lock']['message'] ?? '该账号已被锁定'
            ]
        ]));
    }

    $lastLoginTime = $data[$cardCode]['last_login_time'] ?? '从未登录';
    $data[$cardCode]['last_ip'] = $ip;
    $data[$cardCode]['last_login_time'] = $currentTime;
    file_put_contents($authFile, json_encode($data, JSON_PRETTY_PRINT));

    exit(json_encode([
        'success' => true,
        'message' => 'login use',
        'expires' => $data[$cardCode]['expires'],
        'ip' => $ip,
        'current_login_time' => $currentTime,
        'last_login_time' => $lastLoginTime
    ]));
} else {
    exit(json_encode(['success' => false, 'message' => '无效的授权，鉴权失败。请联系获得此程序的宝处理~']));
}
