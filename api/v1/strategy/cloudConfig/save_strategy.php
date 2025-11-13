<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/config.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

$data = file_get_contents('php://input');
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无数据']);
    exit();
}

$file = __DIR__ . '/cloudConfigStatus.json';

if (file_put_contents($file, $data) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '保存失败']);
    exit();
}

echo json_encode(['success' => true]);
