<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/backdoor/includes/functions.php';

session_start();

if (isUserLoggedIn()) {
    if (!isSessionUsername('boy') && !isSessionUsername('girl')) {
        redirectWithStatus(403, "您的账号 " . $_SESSION['username'] . " 权限不足，无法查看此页");
        exit();
    }
} else {
    redirectWithStatus(401, "您还未登录");
    exit();
}




$auth_file = 'auth_data.json';
$log_file = 'report.log';

$auth_data = file_exists($auth_file) ? json_decode(file_get_contents($auth_file), true) : [];
$log_lines = file_exists($log_file) ? file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

date_default_timezone_set('Asia/Shanghai');

function is_online($report) {
    return isset($report['timestamp']) && (time() - intval($report['timestamp']) <= 30);
}

// 收集每个卡密的最新上报信息
$latest_reports = [];
foreach (array_reverse($log_lines) as $line) {
    $data = json_decode($line, true);
    if (!isset($data['card_code'])) continue;
    $card = $data['card_code'];
    if (!isset($latest_reports[$card])) {
        $latest_reports[$card] = $data;
    }
}

$response_data = [];
$online_count = 0;

foreach ($auth_data as $card => $info) {
    $report = $latest_reports[$card] ?? [];

    $isOnline = is_online($report);
    if ($isOnline) $online_count++;

    $response_data[$card] = [
        'card_code' => $card,
        'machine_code' => $report['machine_code'] ?? $info['machine_code'] ?? '-',
        'last_ip' => $report['ip'] ?? $info['last_ip'] ?? '-',
        'last_login_time' => $info['last_login_time'] ?? '-',
        'last_report_time' => isset($report['timestamp']) ? date('Y-m-d H:i:s', intval($report['timestamp'])) : '-',
        'online' => $isOnline,
        'system_info' => is_array($report['system_info'] ?? null) ? $report['system_info'] : []
    ];
}

echo json_encode([
    'success' => true,
    'online_count' => $online_count,
    'data' => $response_data
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
