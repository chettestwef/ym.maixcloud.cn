<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 获取最新版本
$latestVersion = getLatestScriptVersion();

// 数据统计
echo date('Y-m-d H:i:s'); // 输出类似 2025-09-20 22:45:30

?>
1