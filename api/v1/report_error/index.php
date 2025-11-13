<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// 数据统计
recordApiCounter('windows_api'); // 同时记录总计数和每日计数

// 数据统计
recordApiCounter('windows_client_error_log'); // 同时记录总计数和每日计数

require_post_and_check_user_agent();

try {
    // 获取 POST 原始数据
    $input = file_get_contents('php://input');

    // 如果是 gzip 压缩，需要解压
    if (isset($_SERVER['HTTP_CONTENT_ENCODING']) && $_SERVER['HTTP_CONTENT_ENCODING'] === 'gzip') {
        $input = gzdecode($input);
    }

    $data = json_decode($input, true);
    
    if (!$data || !isset($data['card_code'], $data['machine_code'], $data['error_text'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
        exit;
    }
    
    // 鉴权
    verify_authorization($data['card_code'], $data['machine_code']);
    
    

    $stmt = $db->prepare("
        INSERT INTO error_reports (card_code, machine_code, error_text, extra)
        VALUES (:card_code, :machine_code, :error_text, :extra)
    ");
    $stmt->execute([
        ':card_code' => $data['card_code'],
        ':machine_code' => $data['machine_code'],
        ':error_text' => $data['error_text'],
        ':extra' => isset($data['extra']) ? json_encode($data['extra'], JSON_UNESCAPED_UNICODE) : null
    ]);

    echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);

} catch (Exception $e) {
    log("ErrorReport Exception: " . $e->getMessage(), "ERROR");
    echo json_encode(['status' => 'error', 'message' => 'Server exception']);
}
