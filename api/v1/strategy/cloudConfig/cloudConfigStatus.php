<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../../../includes/config.php';
require_once __DIR__ . '/../../../../includes/db.php';
// require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../functions.php';

$machine_code = $_POST['machine_code'] ?? '';
$card_code = $_POST['card_code'] ?? '';

// 数据统计
recordApiCounter('windows_api'); // 同时记录总计数和每日计数

// $card_code = "lc";
// $machine_code = "4706df05-25d9-5dff-8c6a-a4b17b1ea37e";

// require_post_and_check_user_agent();
verify_authorization($card_code, $machine_code);


// 读取 JSON 文件内容
$json_file = __DIR__ . '/cloudConfigStatus_grehjkarvnj.json';

if (!file_exists($json_file)) {
    http_response_code(500);
    echo json_encode([
            'success' => false,
            'message' => '策略文件不存在'
        ]);
    logSystemEvent(
        'error', 
        '策略文件不存在', 
        $card_code,
        $machine_code,
        [
            'success' => false,
            'message' => '策略文件不存在'
        ],
    );
    exit;
}

$json_content = file_get_contents($json_file);

// 检查 JSON 格式有效性
$data = json_decode($json_content, true);
if ($data === null) {
    http_response_code(500);
    echo json_encode([
            'success' => false,
            'message' => '策略文件格式错误'
        ]);
    logSystemEvent(
        'error', 
        '策略文件格式错误', 
        $card_code,
        $machine_code,
        [
            'success' => false,
            'message' => '策略文件格式错误'
        ],
    );
    exit;
}

logSystemEvent(
        'info', 
        '已返回策略数据', 
        $card_code,
        $machine_code,
        $data,
    );
// 输出 JSON 内容
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
