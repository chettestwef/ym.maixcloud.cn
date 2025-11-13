<?php
// check_main_update.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// 数据统计
recordApiCounter('windows_api'); // 同时记录总计数和每日计数

// 获取客户端当前版本
$current_version = urldecode($_GET['current'] ?? '1.0.0');

if (empty($current_version)) {
    exit(json_encode(['success' => false, 'message' => '当前版本参数不能为空']));
}

try {
    // 从数据库获取最新版本信息
    $stmt = $db->prepare("SELECT 
                         id,
                         version, 
                         download_url, 
                         release_date,
                         is_forced,
                         file_size
                         FROM scripts 
                         WHERE is_latest = 1 
                         LIMIT 1");
    $stmt->execute();
    $latestVersion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latestVersion) {
        exit(json_encode([
            'success' => false,
            'message' => '系统中暂无可用版本'
        ]));
        // 自动记录当前请求的所有可用信息
        logSystemEvent('info', '系统暂无可用版本');
    }

    // 获取该版本的更新日志
    $changelogStmt = $db->prepare("SELECT description FROM changelogs WHERE script_id = ? ORDER BY id DESC");
    $changelogStmt->execute([$latestVersion['id']]);
    $changelogs = $changelogStmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // 比较版本
    // $need_update = version_compare($current_version, $latestVersion['version']) < 0;
    
    // 直接字符串比较
    $need_update = ($current_version !== $latestVersion['version']);
    $force_update = (bool)$latestVersion['is_forced'];


    // 构建响应数据
    $response = [
        'success' => true,
        'latest_version' => $latestVersion['version'],
        'release_time' => $latestVersion['release_date'],
        'file_size' => $latestVersion['file_size'],
        'need_update' => $need_update,
        'force_update' => (bool)$latestVersion['is_forced'],
        'download_url' => $latestVersion['download_url'],
        'changelog' => $changelogs,
        'message' => $need_update ? 
            ($latestVersion['is_forced'] ? '当前版本已停止支持，请立即升级到最新版本' : '检测到新的版本，请及时更新') : 
            '您当前使用的是最新版本'
    ];

    echo json_encode($response);
    logSystemEvent(
        'info',
        '已获取到版本', 
        null,
        null,
        $response
    );

} catch (PDOException $e) {
    error_log('数据库错误: ' . $e->getMessage());
    logSystemEvent(
        'info',
        '数据库错误' . $e->getMessage(), 
        null,
        null,
        ['success' => false,'message' => '系统错误，请稍后再试']
    );
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后再试'
    ]);
}