<?php
function getLatestScriptVersion() {
    global $db;
    $stmt = $db->query("SELECT * FROM scripts WHERE is_latest = 1 LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllScriptVersions() {
    global $db;
    $stmt = $db->query("SELECT * FROM scripts ORDER BY release_date DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChangelogsForScript($scriptId) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM changelogs WHERE script_id = ? ORDER BY id");
    $stmt->execute([$scriptId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllScriptVersionsWithChangelogs() {
    $versions = getAllScriptVersions();
    
    foreach ($versions as &$version) {
        $version['changelogs'] = getChangelogsForScript($version['id']);
    }
    
    return $versions;
}

function getScriptVersionCount() {
    global $db;
    $stmt = $db->query("SELECT COUNT(*) FROM scripts");
    return $stmt->fetchColumn();
}

function getAdminByUsername($username) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isAdminLoggedIn() {
    if (!isset($_COOKIE[ADMIN_COOKIE])) {
        return false;
    }
    
    $token = $_COOKIE[ADMIN_COOKIE];
    // 这里应该有更严格的token验证逻辑
    return !empty($token);
}

function setAdminAuthCookie($adminId, $adminUserName) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + 3600 * 24 * 7; // 7天有效期
    
    setcookie(ADMIN_COOKIE, $token, $expires, '/', '', false, true);
    $_SESSION['admin_username'] = $adminUserName;
    
    // 实际项目中应该将token存入数据库并与用户关联
}

function logoutAdmin() {
    setcookie(ADMIN_COOKIE, '', time() - 3600, '/');
}

/**
 * 获取待处理授权数量（未绑定机器码的授权数）
 */
function getPendingAuthCount() {
    global $db;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM auth_users WHERE machine_code IS NULL OR machine_code = ''");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("获取待处理授权数失败: " . $e->getMessage());
        return 0;
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
    
    // 检查 $jsonData 是否已定义，如果未定义则初始化为 null（或其他默认值）
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




