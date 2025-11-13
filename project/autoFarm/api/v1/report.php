<?php
header('Content-Type: application/json');
require_once __DIR__ . '/functions.php';


// 获取 POST 原始 JSON 数据
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => '无效的 JSON']);
    exit;
}

// 验证字段
$required = ['machine_code', 'card_code', 'timestamp', 'status'];
foreach ($required as $key) {
    if (empty($data[$key])) {
        echo json_encode(['success' => false, 'message' => "参数不足"]);
        exit;
    }
}

// 验证合法性
$card_code = $data['card_code'];
$machine_code = $data['machine_code'];
verify_authorization($card_code, $machine_code);







$system_info = isset($data['system_info']) && is_array($data['system_info']) ? $data['system_info'] : [];

// 写入日志
$log_entry = [
    'time' => date('Y-m-d H:i:s'),
    'machine_code' => $data['machine_code'],
    'card_code' => $data['card_code'],
    'status' => $data['status'],
    'timestamp' => $data['timestamp'],
    'system_info' => $system_info
];

$log_file = __DIR__ . '/report.log';
file_put_contents($log_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

// 自动更新 auth_data.json
$auth_path = __DIR__ . '/auth_data.json';
$client_ip = $_SERVER['REMOTE_ADDR'];
$now = date('Y-m-d H:i:s');

if (file_exists($auth_path)) {
    $auth_data = json_decode(file_get_contents($auth_path), true);

    $card_code = $data['card_code'];
    if (isset($auth_data[$card_code])) {
        // 更新 machine_code、IP 和时间
        $auth_data[$card_code]['machine_code'] = $data['machine_code'];
        $auth_data[$card_code]['last_ip'] = $client_ip;
        $auth_data[$card_code]['last_login_time'] = $now;

        // 写回文件
        file_put_contents($auth_path, json_encode($auth_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

echo json_encode(['success' => true, 'message' => '上报成功']);
