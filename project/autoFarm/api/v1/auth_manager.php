<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backdoor/includes/functions.php';


// 检查登录状态
session_start();

// 判断是否登录
if (isUserLoggedIn()){
    if (!isSessionUsername('boy') && !isSessionUsername('girl')){
        redirectWithStatus(403, "您的账号 ". $_SESSION['username'] ." 权限不足，无法查看此页");
        exit();
    };
} else {
     redirectWithStatus(401, "您还未登录 ");
}

$authFile = 'auth_data.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['card_code'] ?? '';
    $expire = $_POST['expires'] ?? '';
    if ($code && $expire) {
        $data = file_exists($authFile) ? json_decode(file_get_contents($authFile), true) : [];
        $data[$code] = ['machine_code' => '', 'expires' => $expire];
        file_put_contents($authFile, json_encode($data, JSON_PRETTY_PRINT));
        $msg = "添加成功：$code";
    } else {
        $msg = "卡密或过期时间不能为空";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权管理</title>
</head>
<body>
    <h2>添加新授权卡密</h2>
    <form method="POST">
        <label>卡密: <input name="card_code" required></label><br><br>
        <label>过期时间 (如 2025-12-31): <input name="expires" required></label><br><br>
        <input type="submit" value="添加授权">
    </form>

    <?php if (!empty($msg)) echo "<p><strong>$msg</strong></p>"; ?>

    <h3>当前授权列表</h3>
    <pre><?php echo file_get_contents($authFile); ?></pre>
</body>
</html>
