<?php
// check_main_update.php
header('Content-Type: application/json');

$latest_version = "1.9.3";
$latest_release_time = "2025-06-10 12:00:00";
$force_update = false;
$download_url = "https://yourserver.com/files/main_program_v1.7.3.exe";
$message = "检测到新的版本, 更新页面请前往";
$current_version = $_GET['current'] ?? '1.0.0';

function version_compare_custom($a, $b) {
    return version_compare($a, $b);
}

$need_update = version_compare_custom($current_version, $latest_version) < 0;

echo json_encode([
    "latest_version" => $latest_version,
    "release_time" => $latest_release_time,
    "need_update" => $need_update,
    "force_update" => $force_update,
    "url" => $download_url,
    "message" => $need_update ? $message : "已是最新版本。"
]);
