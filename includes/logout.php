<?php
require_once 'config.php';
require_once 'functions.php';

logoutAdmin();
header('Location: /../admin/login.php');
exit;