<?php
// 鉴权
/**
 * 验证授权信息
 * 
 * @param string $card_code 卡号/授权码
 * @param string $machine_code 机器码
 * @throws PDOException 数据库异常
 * @return void
 */
function verify_authorization(string $card_code, string $machine_code): void {
    global $db; // 使用全局数据库连接
    
    try {
        // 1. 查询授权信息
        $stmt = $db->prepare("
            SELECT 
                machine_code, 
                is_locked,
                lock_reason,
                expires
            FROM auth_users 
            WHERE card_code = ?
            LIMIT 1
        ");
        $stmt->execute([$card_code]);
        $auth_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. 检查授权是否存在
        if (!$auth_data) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => '授权码无效或未找到'
            ]);
            logSystemEvent(
                'warning', 
                '授权码无效或未找到', 
                $card_code,
                $machine_code,
                [
                    'success' => false, 
                    'message' => '授权码无效或未找到'
                ]
            );
            exit;
        }

        // 3. 检查账号是否被锁定
        if ($auth_data['is_locked']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'lock' => true,
                'message' => $auth_data['lock_reason'] ?? '该账号已被锁定'
            ]);
            logSystemEvent(
                'warning', 
                '该账号已被锁定', 
                $card_code,
                $machine_code,
                [
                    'success' => false,
                    'lock' => true,
                    'message' => $auth_data['lock_reason'] ?? '该账号已被锁定'
                ]
            );
            exit;
        }

        // 4. 检查授权是否过期
        if (strtotime($auth_data['expires']) < time()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => '授权已过期，请联系管理员续期'
            ]);
            logSystemEvent(
                'warning', 
                '授权已过期', 
                $card_code,
                $machine_code,
                [
                    'success' => false,
                    'message' => '授权已过期，请联系管理员续期'
                ]
            );
            exit;
        }

        // 5. 验证机器码
        if (empty($auth_data['machine_code'])) {
            // 首次绑定机器码
            $updateStmt = $db->prepare("
                UPDATE auth_users 
                SET machine_code = ?,
                    first_login_time = NOW(),
                    last_login_time = NOW(),
                    last_ip = ?
                WHERE card_code = ?
            ");
            $updateStmt->execute([
                $machine_code,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $card_code
            ]);
        } elseif ($auth_data['machine_code'] !== $machine_code) {
            // 机器码不匹配
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => '授权与当前设备不匹配'
            ]);
            logSystemEvent(
                'warning', 
                '授权与当前设备不匹配', 
                $card_code,
                $machine_code,
                [
                    'success' => false,
                    'message' => '授权与当前设备不匹配'
                ]
            );
            exit;
        }

        // 6. 更新最后登录信息
        $updateStmt = $db->prepare("
            UPDATE auth_users 
            SET last_login_time = NOW(),
                last_ip = ?
            WHERE card_code = ?
        ");
        $updateStmt->execute([
            $_SERVER['REMOTE_ADDR'] ?? '',
            $card_code
        ]);

    } catch (PDOException $e) {
        error_log('授权验证数据库错误: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '服务器内部错误，请稍后再试'
        ]);
        logSystemEvent(
            'emergency', 
            '服务器内部错误', 
            $card_code,
            $machine_code,
            [
                'success' => false,
                'message' => '服务器内部错误，请稍后再试',
            ],
            $e->getMessage()
        );
        exit;
    }
}
function require_post_and_check_user_agent(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST only']);
        logSystemEvent(
            'error', 
            '仅允许POST请求',
            null,
            null,
            ['success' => false, 'message' => 'POST only'],
        );
        exit;
    }

    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    if (stripos($userAgent, 'python-requests') === false) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'bad request User-Agent 不合法']);
        logSystemEvent(
            'error', 
            'User-Agent 不合法', 
            $card_code,
            $machine_code,
            ['success' => false, 'message' => 'bad request User-Agent 不合法'],
        );
        exit;
    }
}


/**
 * 记录系统事件日志（支持手动传入响应数据）
 * 
 * @param string $level 日志等级（error/warning/info/debug）
 * @param string $message 日志消息
 * @param string|null $card_code 卡号/账号
 * @param string|null $machine_code 机器码
 * @param array|string|null $responseData 专门传递的响应数据（可选）
 * @param array|null $extraData 额外数据（包括响应数据）
 * @return bool 是否记录成功
 */
function logSystemEvent($level, $message, $card_code = null, $machine_code = null, $responseData = null, $extraData = null) {
    global $db;
    
    // 1. 基本信息处理
    $account = $card_code;
    $level = strtolower($level);
    
    // 2. 获取调用位置信息
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1] ?? $backtrace[0];
    $projectRoot = realpath(__DIR__ . '/..');
    $filePath = str_replace($projectRoot, '', $caller['file']);
    $apiPath = ltrim($filePath, '/\\');
    
    if (isset($caller['function']) && $caller['function'] != 'logSystemEvent') {
        $apiPath .= '::' . $caller['function'];
    }
    if (isset($caller['line'])) {
        $apiPath .= ':' . $caller['line'];
    }
    
    // 检查 $jsonData 是否已定义，如果未定义则初始化为 null
    if (!isset($jsonData)) {
        $jsonData = null; // 或者初始化为空数组 []、空字符串 '' 等
    }

    // 3. 获取请求信息
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $requestData = [
        'GET' => $_GET,
        'POST' => $_POST,
        'JSON_BODY' => $jsonData, // 专门存储JSON请求体数据
        'headers' => function_exists('getallheaders') ? getallheaders() : []
    ];

    // 4. 处理响应数据（新增参数）
    if ($responseData !== null) {
        $requestData['response'] = is_string($responseData) ? 
            (json_decode($responseData, true) ?: $responseData) : 
            $responseData;
    }

    // 5. 合并额外数据
    if ($extraData && is_array($extraData)) {
        $requestData['extra'] = $extraData;
    }

    // 6. 获取客户端IP（优化版）
    $ip = 'unknown';
    foreach ([
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ] as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            break;
        }
    }

    // 7. 数据库记录
    try {
        // $stmt = $db->prepare("
        //     INSERT INTO system_logs 
        //     (log_level, machine_code, account, ip_address, api_path, 
        //      request_method, request_data, message)
        //     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        // ");
        $stmt = $db->prepare("
                INSERT INTO system_logs 
                (log_level, machine_code, account, ip_address, api_path, 
                 request_method, request_data, response_data, message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        
        // return $stmt->execute([
        //     $level,
        //     $machine_code,
        //     $account,
        //     $ip,
        //     $apiPath,
        //     $requestMethod,
        //     json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        //     $message
        // ]);
        
        return $stmt->execute([
            strtolower($level),
            $machine_code,
            $account,
            $ip,
            $apiPath,
            $requestMethod,
            json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($responseData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $message
        ]);
    } catch (PDOException $e) {
        // 8. 失败时记录文件日志
        error_log(sprintf(
            "[%s] [%s] 日志记录失败: %s\n调用位置: %s\n请求数据: %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $e->getMessage(),
            $apiPath,
            json_encode($requestData)
        ), 3, __DIR__.'/db_error.log');
        return false;
    }
}

/**
 * 设置用户设备类型
 * 
 * @param string $card_code 卡号/授权码
 * @param int $device_type 设备类型 (1=电脑, 2=安卓, 3=iOS)
 * @throws PDOException 数据库异常
 * @return bool 是否更新成功
 */
function set_device_type(string $card_code, int $device_type): bool {
    global $db;
    
    // 验证设备类型范围
    if (!in_array($device_type, [1, 2, 3])) {
        throw new InvalidArgumentException('设备类型无效，请使用1(电脑)/2(安卓)/3(iOS)');
    }

    try {
        $stmt = $db->prepare("
            UPDATE auth_users 
            SET device_type = ?
            WHERE card_code = ?
        ");
        
        $success = $stmt->execute([$device_type, $card_code]);
        
        // // 记录日志
        // logSystemEvent(
        //     'info',
        //     '更新设备类型',
        //     $card_code,
        //     null,
        //     [
        //         'success' => $success,
        //         'device_type' => $device_type,
        //         'affected_rows' => $stmt->rowCount()
        //     ]
        // );
        
        return $success;
        
    } catch (PDOException $e) {
        logSystemEvent(
            'error',
            '更新设备类型失败',
            $card_code,
            null,
            [
                'success' => false,
                'error' => $e->getMessage()
            ]
        );
        throw $e;
    }
}



/**
 * 获取客户端真实IP地址
 * @return string 客户端IP地址
 */
function get_client_ip(): string {
    foreach ([
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ] as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'unknown';
}

/**
 * 更新用户IP地址
 * @param string $card_code 用户卡号/授权码
 * @param string|null $ip 指定IP地址（可选，不传则自动获取）
 * @return bool 是否更新成功
 * @throws PDOException
 */
function update_client_ip(string $card_code, ?string $ip = null): bool {
    global $db;
    
    // 获取IP地址
    $client_ip = $ip ?? get_client_ip();
    
    try {
        $stmt = $db->prepare("
            UPDATE auth_users 
            SET client_ip = ?,
                last_ip = ?,
                last_login_time = NOW()
            WHERE card_code = ?
        ");
        
        $success = $stmt->execute([$client_ip, $client_ip, $card_code]);
        
        // 记录日志
        // logSystemEvent(
        //     'info',
        //     '更新客户端IP',
        //     $card_code,
        //     null,
        //     [
        //         'success' => $success,
        //         'ip_address' => $client_ip,
        //         'affected_rows' => $stmt->rowCount()
        //     ]
        // );
        
        return $success;
        
    } catch (PDOException $e) {
        logSystemEvent(
            'error',
            '更新客户端IP失败',
            $card_code,
            null,
            [
                'success' => false,
                'error' => $e->getMessage()
            ]
        );
        throw $e;
    }
}






















// // 每次 API 调用时
// incrementDailyCounter('total_api');
// incrementDailyCounter('windows_api');
// incrementDailyCounter('android_api');

// // 网站访问
// incrementDailyCounter('website_visit');

// // 获取今日统计
// $todayTotalApi = getDailyCounter('total_api');
// $todayAll = getDailyCounters();

/**
 * 增加每日计数
 * @param string $name 计数器名称
 * @param int $value 增加的值（默认 1）
 */
function incrementDailyCounter($name, $value = 1) {
    global $db;

    $today = date('Y-m-d');

    $stmt = $db->prepare("
        INSERT INTO daily_statistics (stat_date, counter_name, counter_value)
        VALUES (:date, :name, :value)
        ON DUPLICATE KEY UPDATE counter_value = counter_value + VALUES(counter_value)
    ");
    $stmt->execute([
        ':date'  => $today,
        ':name'  => $name,
        ':value' => $value
    ]);
}

/**
 * 获取某日某计数器的值
 */
function getDailyCounter($name, $date = null) {
    global $db;

    if ($date === null) {
        $date = date('Y-m-d');
    }

    $stmt = $db->prepare("SELECT counter_value FROM daily_statistics WHERE stat_date = :date AND counter_name = :name");
    $stmt->execute([':date' => $date, ':name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['counter_value'] : 0;
}

/**
 * 获取某日所有计数
 */
function getDailyCounters($date = null) {
    global $db;

    if ($date === null) {
        $date = date('Y-m-d');
    }

    $stmt = $db->prepare("SELECT counter_name, counter_value FROM daily_statistics WHERE stat_date = :date");
    $stmt->execute([':date' => $date]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}



// // 总API调用
// incrementCounter('total_api');

// // Windows API调用
// incrementCounter('windows_api');

// // Android API调用
// incrementCounter('android_api');

// // 网站访问
// incrementCounter('website_visit');

// // 其他计数也可以随时添加，比如 iOS API
// incrementCounter('ios_api');


/**
 * 增加计数
 * @param string $name 计数器名称
 * @param int $value 增加的值（默认 1）
 */
function incrementCounter($name, $value = 1) {
    global $db;

    $stmt = $db->prepare("
        INSERT INTO statistics (counter_name, counter_value) 
        VALUES (:name, :value)
        ON DUPLICATE KEY UPDATE counter_value = counter_value + VALUES(counter_value)
    ");
    $stmt->execute([
        ':name' => $name,
        ':value' => $value
    ]);
}

/**
 * 获取所有计数器的总计数
 * @return array [counter_name => counter_value, ...]
 */
function getCounters() {
    global $db;
    $stmt = $db->query("SELECT counter_name, counter_value FROM statistics");
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$row['counter_name']] = (int)$row['counter_value'];
    }
    return $results;
}


/**
 * 获取单个计数
 * @param string $name
 * @return int
 */
function getCounter($name) {
    global $db;

    $stmt = $db->prepare("SELECT counter_value FROM statistics WHERE counter_name = :name");
    $stmt->execute([':name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['counter_value'] : 0;
}

/**
 * 获取所有计数
 * @return array
 */
function getAllCounters() {
    global $db;

    $stmt = $db->query("SELECT counter_name, counter_value FROM statistics");
    $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    return $result;
}




// // Windows API 请求
// recordApiCounter('windows_api');

// // Android API 请求
// recordApiCounter('android_api');
/**
 * 同时记录总计数和每日计数
 * @param string $name
 * @param int $value
 */
function recordApiCounter($name, $value = 1) {
    // 排除 BackendHealthCheck
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($name === 'website_visit' && strpos($ua, 'BackendHealthCheck') !== false) {
        return;
    }

    // 总计数
    incrementCounter('total_api', $value);
    incrementCounter($name, $value);

    // 每日计数
    incrementDailyCounter('total_api', $value);
    incrementDailyCounter($name, $value);

    // 每小时统计
    incrementHourlyCounter('total_api', $value);
    incrementHourlyCounter($name, $value);
    
    // 每分钟统计
    incrementMinuteCounter($name, $value);
}


function incrementMinuteCounter($name, $value = 1) {
    global $db;

    $minute = date('Y-m-d H:i:00');

    $stmt = $db->prepare("
        INSERT INTO statistics_minute (stat_time, counter_name, counter_value)
        VALUES (:time, :name, :value)
        ON DUPLICATE KEY UPDATE counter_value = counter_value + VALUES(counter_value)
    ");
    $stmt->execute([
        ':time'  => $minute,
        ':name'  => $name,
        ':value' => $value
    ]);
}


/**
 * 增加每小时计数
 */
function incrementHourlyCounter($name, $value = 1) {
    global $db;
    $hour = date('Y-m-d H:00:00'); // 只取到小时
    $stmt = $db->prepare("
        INSERT INTO statistics_hour (stat_time, counter_name, counter_value)
        VALUES (:hour, :name, :value)
        ON DUPLICATE KEY UPDATE counter_value = counter_value + VALUES(counter_value)
    ");
    $stmt->execute([
        ':hour'  => $hour,
        ':name'  => $name,
        ':value' => $value
    ]);
}


