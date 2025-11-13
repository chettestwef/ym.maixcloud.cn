<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

logoutAdmin();
header('Location: /../admin/login.php');
exit;