<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$versions = getAllScriptVersionsWithChangelogs();

if ($versions) {
    echo json_encode([
        'success' => true,
        'data' => $versions
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No versions found'
    ]);
}