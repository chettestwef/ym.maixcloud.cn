<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backdoor/includes/functions.php';

session_start();

if (!isUserLoggedIn() || (!isSessionUsername('boy') && !isSessionUsername('girl'))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '无权限']);
    exit();
}

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
