<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$version = getLatestScriptVersion();

if ($version) {
    echo json_encode([
        'success' => true,
        'version' => $version['version'],
        'download_url' => $version['download_url'],
        'release_date' => $version['release_date'],
        'file_size' => $version['file_size']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No version found'
    ]);
}