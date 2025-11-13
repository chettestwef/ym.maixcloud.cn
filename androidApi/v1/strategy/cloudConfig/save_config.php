<?php
$jsonFile = __DIR__ . '/cloudConfigStatus.json';
$rawData = file_get_contents('php://input');
file_put_contents($jsonFile, $rawData);
echo 'ok';
