<?php
header('Content-Type: application/json');

$configPath = __DIR__ . '/strategy/cloudConfig/cloudConfigStatus_grehjkarvnj.json';
if (!file_exists($configPath)) {
    echo json_encode(['error' => 'default_config.json not found']);
    exit;
}

$configArray = json_decode(file_get_contents($configPath), true);
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$basePath = $domain . "/project/autoFarm/api/v1/assets/";

// 用于存储唯一 png 路径
$pngPaths = [];

/**
 * 递归提取所有 png 文件路径
 */
function extractPngPaths($data, &$results) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_string($value) && str_ends_with($value, '.png')) {
                $results[$value] = true;
            } elseif (is_array($value) || is_object($value)) {
                extractPngPaths($value, $results);
            }
        }
    } elseif (is_object($data)) {
        foreach (get_object_vars($data) as $key => $value) {
            if (is_string($value) && str_ends_with($value, '.png')) {
                $results[$value] = true;
            } elseif (is_array($value) || is_object($value)) {
                extractPngPaths($value, $results);
            }
        }
    }
}

// 外层是数组，每个条目进入递归提取
foreach ($configArray as $item) {
    extractPngPaths($item, $pngPaths);
}

// 构造输出
$output = [];
foreach ($pngPaths as $relativePath => $_) {
    $output[] = [
        'file' => $relativePath,
        'url' => $basePath . $relativePath
    ];
}

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
