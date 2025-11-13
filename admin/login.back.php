<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 限制访问频率（可选）
require_once __DIR__ . '/../api/v1/rate_limit.php';


logSystemEvent(
    'warning',
    '后台登录页面访问', 
    null,
    null,
    null
);

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '空提交, 请输入用户名和密码';
        logSystemEvent(
            'warning',
            $error, 
            null,
            null,
            null
        );
    } else {
        $admin = getAdminByUsername($username);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            setAdminAuthCookie($admin['id'], $admin);
            header('Location: index.php');
            logSystemEvent(
                'warning',
                '登录成功', 
                $username,
                null,
                null
            );
            exit;
        } else {
            $error = '非法登录! 你的IP及请求方法已被记录';
            logSystemEvent(
                'error',
                $error,
                $username,
                null,
                null
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台登录 - <?= SITE_NAME ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">管理控制台 - 登录</h2>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
</body>
</html>