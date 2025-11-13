<?php
// 鉴权
function verify_authorization(string $card_code, string $machine_code, string $auth_file_path = null): void{
    if ($auth_file_path === null) {
        $auth_file_path = $_SERVER['DOCUMENT_ROOT'] . '/project/autoFarm/api/v1/auth_data.json';
    }

    if (!file_exists($auth_file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '服务器后端错误, 未找到验证配置文件的位置']);
        exit;
    }

    $auth_data = json_decode(file_get_contents($auth_file_path), true);
    if (!isset($auth_data[$card_code]) || $auth_data[$card_code]['machine_code'] !== $machine_code) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '非法请求!']);
        
        exit;
    }
}

function require_post_and_check_user_agent(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST only']);
        exit;
    }

    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    if (stripos($userAgent, 'python-requests') === false) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '恶意请求! User-Agent 不合法']);
        exit;
    }
}

