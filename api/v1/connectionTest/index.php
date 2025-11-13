<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// 数据统计
recordApiCounter('windows_api'); // 同时记录总计数和每日计数
// require_post_and_check_user_agent();

// 响应模板
$response = [
    "success"   => false,
    "server"    => "unknown",
    "status"    => "offline",
    "database"  => "unknown",
    "load"      => "unknown",
];

// 检查数据库
try {
    $start = microtime(true);
    $stmt = $db->query("SELECT 1");
    $stmt->fetch();
    $db_ok = true;
    $db_status = "正常";
} catch (Exception $e) {
    $db_ok = false;
    $db_status = "fail";
}

// 检查系统负载（Linux 专用）
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    $load_str = implode(", ", $load); // 1/5/15分钟平均负载
} else {
    $load_str = "unsupported";
}

// 模拟服务器名（可以在 config.php 中定义常量）
$serverName = defined("SERVER_NAME") ? SERVER_NAME : gethostname();
$serverName = "HKserver";
// 组合响应
$response = [
    "success"   => true,
    "server"    => $serverName,
    "status"    => "online",
    "database"  => $db_status,
    "load"      => $load_str,
    "timestamp" => date("Y-m-d H:i:s"),
];

// 输出结果
echo json_encode($response, JSON_UNESCAPED_UNICODE);
