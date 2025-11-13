<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$today = date('Y-m-d');

// 每日计数
$dailyTotal      = getDailyCounter('total_api');
$dailyWindows    = getDailyCounter('windows_api');
$dailyAndroid    = getDailyCounter('android_api');
$dailyWebsite    = getDailyCounter('website_visit');
$dailyOthers     = getDailyCounters($today);

// 总计数（全局）
$totalTotal      = getCounter('total_api');
$totalWindows    = getCounter('windows_api');
$totalAndroid    = getCounter('android_api');
$totalWebsite    = getCounter('website_visit');
$totalOthers     = getCounters(); // 全部总计

// 返回数据
$data = [
    'date'          => $today,
    'daily' => [
        'total_api'     => $dailyTotal,
        'windows_api'   => $dailyWindows,
        'android_api'   => $dailyAndroid,
        'website_visit' => $dailyWebsite,
        'others'        => $dailyOthers
    ],
    'total' => [
        'total_api'     => $totalTotal,
        'windows_api'   => $totalWindows,
        'android_api'   => $totalAndroid,
        'website_visit' => $totalWebsite,
        'others'        => $totalOthers
    ]
];

// 输出 JSON
echo json_encode([
    'status' => 'success',
    'data'   => $data
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
