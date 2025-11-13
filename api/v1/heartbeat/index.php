<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405);
//     echo json_encode(['status' => 'error', 'message' => '仅支持 POST 请求']);
//     exit;
// }

$cardCode = $_POST['cardCode'] ?? '';
$machineCode = $_POST['machineCode'] ?? '';
$version = $_POST['version'] ?? '';
$mainStatus = $_POST['mainStatus'] ?? '';
$threadsStatus = $_POST['threadsStatus'] ?? '';

// 数据统计
recordApiCounter('windows_api'); // 同时记录总计数和每日计数


require_post_and_check_user_agent();
verify_authorization($cardCode, $machineCode);

if (empty($cardCode) || empty($machineCode)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '缺少必要参数']);
    exit;
}

try {
    // 更新心跳记录
    $stmt = $db->prepare("
        UPDATE auth_users 
        SET last_heartbeat_time = NOW(),
            last_heartbeat_ip = :ip,
            last_main_status = :mainStatus,
            last_threads_status = :threadsStatus,
            last_version = :version
        WHERE card_code = :cardCode AND machine_code = :machineCode
    ");
    $stmt->execute([
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':mainStatus' => $mainStatus,
        ':threadsStatus' => $threadsStatus,
        ':version' => $version,
        ':cardCode' => $cardCode,
        ':machineCode' => $machineCode
    ]);

    if ($stmt->rowCount() > 0) {
        // 查询 next_heartbeat_interval
        $query = $db->prepare("
            SELECT next_heartbeat_interval 
            FROM auth_users 
            WHERE card_code = :cardCode AND machine_code = :machineCode
            LIMIT 1
        ");
        $query->execute([
            ':cardCode' => $cardCode,
            ':machineCode' => $machineCode
        ]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $interval = isset($result['next_heartbeat_interval']) ? (int)$result['next_heartbeat_interval'] : null;

        $response = [
            'status' => 'success',
            'message' => '与客户端通信成功',
            'serverTime' => date('Y-m-d H:i:s')
        ];
        if ($interval !== null && $interval > 0) {
            $response['nextHeartbeatInterval'] = $interval;
        }

        echo json_encode($response);

        logSystemEvent(
            'info',
            '心跳包已接收', 
            $cardCode,
            $machineCode,
            $response
        );
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '未找到匹配用户'
        ]);

        logSystemEvent(
            'info',
            '未找到匹配用户', 
            $cardCode,
            $machineCode,
            ['status' => 'error', 'message' => '未找到匹配用户']
        );
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => '数据库错误',
        'error' => $e->getMessage()
    ]);
}
