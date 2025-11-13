<?php
// test_db_connection.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../functions.php';

// 限制访问频率（可选）
require_once __DIR__ . '/../rate_limit.php';

try {
    // 简单测试查询，不返回任何敏感数据
    $testQuery = $db->query('SELECT 1 AS connection_test');
    $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
    
    // 只检查表是否存在，不返回表结构或其他详细信息
    $tablesExist = false;
    $requiredTables = ['scripts', 'changelogs']; // 只检查必要的表
    
    foreach ($requiredTables as $table) {
        $checkStmt = $db->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$table]);
        if ($checkStmt->fetch()) {
            $tablesExist = true;
        }
    }
    
    // 获取非敏感的基本信息
    $versionStmt = $db->query("SELECT VERSION() AS db_version");
    $versionInfo = $versionStmt->fetch(PDO::FETCH_ASSOC);
    
    // 构建安全响应
    $response = [
        'success' => true,
        'message' => '服务正常运行',
        'status' => [
            'database_connection' => $testResult['connection_test'] == 1,
            'required_tables_exist' => $tablesExist,
            'service_status' => 'operational'
        ],
        'version' => '1.0' // 可以返回API版本号
    ];
    
    echo json_encode($response);
    
    // 记录简化的日志
    logSystemEvent(
        'info',
        '数据库连接测试成功',
        null,
        null,
        [
        'success' => true,
        'message' => '服务正常运行',
        'status' => [
            'database_connection' => $testResult['connection_test'] == 1,
            'required_tables_exist' => $tablesExist,
            'service_status' => 'operational'
        ],
        'version' => '1.0' // 可以返回API版本号
    ]
    );
    
} catch (PDOException $e) {
    // 返回通用错误信息，不暴露细节
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务暂时不可用',
        'service_status' => 'maintenance'
    ]);
    
    // 内部记录详细错误
    logSystemEvent(
        'error',
        '数据库连接错误: ' . $e->getMessage(),
        null,
        null,
        ['error_code' => $e->getCode()]
    );
}