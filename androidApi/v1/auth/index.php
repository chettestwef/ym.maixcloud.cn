<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// require_post_and_check_user_agent();

$input = json_decode(file_get_contents('php://input'), true);
$machineCode = $input['machine_code'] ?? '';
$cardCode = $input['card_code'] ?? '';

// $machineCode = "4706df05-25d9-5dff-8c6a-a4b17b1ea37e";
// $cardCode = "lc";

if (!$machineCode || !$cardCode) {
    logSystemEvent(
        'warning', 
        '参数传递不正确!', 
        $cardCode,
        $machineCode,
        ['success' => false, 'message' => '参数传递不正确'],
    );
    
    exit(json_encode(['success' => false, 'message' => '参数传递不正确']));
    
    
}

try {
    // 获取客户端IP和当前时间
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentTime = date('Y-m-d H:i:s');
    
    // 查询用户
    $stmt = $db->prepare("SELECT * FROM auth_users WHERE card_code = ?");
    $stmt->execute([$cardCode]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        logSystemEvent(
            'warning', 
            '无效的授权', 
            $cardCode,
            $machineCode,
            ['success' => false, 'message' => '无效的授权，鉴权失败'],
        );
        
        exit(json_encode(['success' => false, 'message' => '无效的授权，鉴权失败']));
        
        
    }
    
    // 检查账号锁定状态
    if ($user['is_locked']) {
        logSystemEvent(
            'warning', 
            '授权已被锁定', 
            $cardCode,
            $machineCode,
            [
            'success' => false,
            'message' => "授权已被锁定, 原因: " . ($user['lock_reason'] ?? '授权已被锁定')
            ]
        );
        exit(json_encode([
            'success' => false,
            'message' => "授权已被锁定, 原因: " . ($user['lock_reason'] ?? '授权已被锁定')
        ]));
    }
    
    // 检查过期时间
    if (strtotime($currentTime) > strtotime($user['expires'])) {
        // 转换时间戳
        $expireTimestamp = strtotime($user['expires']);
        $expireReadable = date('Y-m-d H:i:s', $expireTimestamp);
        
        logSystemEvent(
            'warning', 
            '授权已过期', 
            $cardCode,
            $machineCode,
            ['success' => false, 'message' => '您的授权已于 ' . $expireReadable . ' 过期, 请续费']
        );
        exit(json_encode(['success' => false, 'message' => '您的授权已于 ' . $expireReadable . ' 过期, 请续费']));
    }
    
    
    // 代码若运行到此处，则可判定找到激活码并未到期
    set_device_type($cardCode, 2);
    update_client_ip($cardCode);
    
    // 处理首次绑定机器码
    if (empty($user['machine_code'])) {
        $stmt = $db->prepare("UPDATE auth_users SET 
                            machine_code = ?,
                            last_ip = ?,
                            first_login_time = ?,
                            last_login_time = ?
                            WHERE card_code = ?");
        $stmt->execute([$machineCode, $ip, $currentTime, $currentTime, $cardCode]);
        
        // 记录登录历史
        recordLoginHistory($db, $user['id'], $ip, $currentTime);
        
        logSystemEvent(
            'warning', 
            '未绑定机器码, 机器码已绑定成功', 
            $cardCode,
            $machineCode,
            ['success' => true, 'message' => '机器码绑定成功']
        );
        
        exit(json_encode(['success' => true, 'message' => '机器码绑定成功']));
        
    }
    
    // 检查机器码是否匹配
    if ($user['machine_code'] !== $machineCode) {
        
        logSystemEvent(
            'warning', 
            '授权与机器码绑定的授权不符', 
            $cardCode,
            $machineCode,
            ['success' => false, 'message' => '授权与机器码绑定的授权不符，鉴权失败。请联系管理员处理~' . $machineCode]
        );
        
        exit(json_encode(['success' => false, 'message' => '授权与机器码绑定的授权不符，鉴权失败。请联系管理员处理~' . $machineCode]));
        
    }
    
    // 更新最后登录信息
    $lastLoginTime = $user['last_login_time'] ?? '从未登录';
    $stmt = $db->prepare("UPDATE auth_users SET 
                        last_ip = ?,
                        last_login_time = ?
                        WHERE card_code = ?");
    $stmt->execute([$ip, $currentTime, $cardCode]);
    
    // 记录登录历史
    recordLoginHistory($db, $user['id'], $ip, $currentTime);
    
    logSystemEvent(
        'info', 
        '鉴权成功!', 
        $cardCode,
        $machineCode,
        ([
            'success' => true,
            'message' => '鉴权成功!',
            'expires' => $user['expires'],
            'ip' => $ip,
            'current_login_time' => $currentTime,
            'last_login_time' => $lastLoginTime
        ]),
    );
    
    
    exit(json_encode([
        'success' => true,
        'message' => '鉴权成功!',
        'expires' => $user['expires'],
        'ip' => $ip,
        'current_login_time' => $currentTime,
        'last_login_time' => $lastLoginTime
    ]));
    
    
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    logSystemEvent(
        'emergency', 
        '系统错误', 
        $cardCode,
        $machineCode,
        ['success' => false, 'message' => '系统错误，请稍后再试'],
        $e
    );

    exit(json_encode(['success' => false, 'message' => '系统错误，请稍后再试']));
}

/**
 * 记录登录历史
 */
function recordLoginHistory($db, $userId, $ip, $time) {
    $stmt = $db->prepare("INSERT INTO auth_login_history 
                         (auth_user_id, login_time, ip_address)
                         VALUES (?, ?, ?)");
    $stmt->execute([$userId, $time, $ip]);
}