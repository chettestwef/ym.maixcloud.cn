<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ========== 内置 RateLimiter 类 ==========
class RateLimiter {
    private $limit;
    private $window;
    private $storageDir;

    public function __construct($limit = 3, $window = 3600, $storageDir = null) {
        $this->limit = $limit;
        $this->window = $window;
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/rate_limit/';
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'invalid_ip';
    }

    public function check($ip) {
        $filename = $this->storageDir . md5($ip) . '.json';
        $now = time();
        $data = ['count' => 0, 'start' => $now];
    
        if (file_exists($filename)) {
            $fileData = json_decode(file_get_contents($filename), true);
            if ($now - $fileData['start'] <= $this->window) {
                $data = $fileData;
            }
        }
    
        $data['count']++;
    
        file_put_contents($filename, json_encode($data));
    
        if ($data['count'] > $this->limit) {
            // 超限时，不再递增，只锁定窗口
            $retryAfter = ($data['start'] + $this->window) - $now;
            return [
                'blocked' => true,
                'retry_after' => max(1, $retryAfter),
                'attempts' => $this->limit,   // 固定显示最大尝试次数
                'window' => $this->window
            ];
        }
    
        return [
            'blocked' => false,
            'attempts' => $data['count'],
            'window' => $this->window,
            'remaining_attempts' => $this->limit - $data['count']
        ];
    }


}
// =========================================

// 记录访问日志
logSystemEvent(
    'info',
    '后台登录页面访问', 
    null, null, null,
    ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
);

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$rateLimiter = new RateLimiter(3, 3600, __DIR__ . '/../storage/rate_limit/login/');
$clientIP = $rateLimiter->getClientIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check = $rateLimiter->check($clientIP);

    // ========== 触发速率限制 ==========
    if ($check['blocked']) {
        $error = "由于非法尝试过多, 你的登录已被禁用, 请在 {$check['retry_after']} 秒后再试。";
        $currentAttempts = $check['attempts'];

        // 记录安全日志
        logSystemEvent(
            'error',
            '登录请求触发速率限制',
            null,
            null,
            [
                'client_ip' => $clientIP,
                'retry_after' => $check['retry_after'],
                'attempts' => $check['attempts'],
                'max_attempts' => 5,
                'window' => $check['window']
            ],
            ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
        );
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';

            logSystemEvent(
                'warning',
                '空提交登录请求',
                $username,
                null,
                ['client_ip' => $clientIP],
                ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
            );
        } else {
            $admin = getAdminByUsername($username);
            if ($admin && password_verify($password, $admin['password_hash'])) {
                setAdminAuthCookie($admin['id'], $admin);

                logSystemEvent(
                    'warning',
                    '管理员登录成功',
                    $username,
                    null,
                    [
                        'admin_id' => $admin['id'],
                        'login_time' => date('Y-m-d H:i:s'),
                        'session_id' => session_id()
                    ],
                    ['client_ip' => $clientIP, 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
                );

                header('Location: index.php');
                exit;
            } else {
                $error = '非法登录！您的IP及请求已被记录';

                logSystemEvent(
                    'error',
                    '登录失败',
                    $username,
                    null,
                    [
                        'client_ip' => $clientIP,
                        'failed_attempts' => $check['attempts'],
                        'max_attempts' => 5,
                        'remaining_attempts' => max(0, 5 - $check['attempts']),
                        'lockout' => ($check['attempts'] >= 5) ? '5分钟' : '无'
                    ],
                    ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
                );
            }
        }
    }
}
?>
<!--若您正在进行代码审查, 请查看console log thanks~-->
<!--
   _____          __        ___________                     
  /  _  \  __ ___/  |_  ____\_   _____/____ _______  _____  
 /  /_\  \|  |  \   __\/  _ \|    __) \__  \\_  __ \/     \ 
/    |    \  |  /|  | (  <_> )     \   / __ \|  | \/  Y Y  \
\____|__  /____/ |__|  \____/\___  /  (____  /__|  |__|_|  /
 ________  ___  ___  _________  ________          ________ ________  ________  _____ ______      
|\   __  \|\  \|\  \|\___   ___\\   __  \        |\  _____\\   __  \|\   __  \|\   _ \  _   \    
\ \  \|\  \ \  \\\  \|___ \  \_\ \  \|\  \       \ \  \__/\ \  \|\  \ \  \|\  \ \  \\\__\ \  \   
 \ \   __  \ \  \\\  \   \ \  \ \ \  \\\  \       \ \   __\\ \   __  \ \   _  _\ \  \\|__| \  \  
  \ \  \ \  \ \  \\\  \   \ \  \ \ \  \\\  \       \ \  \_| \ \  \ \  \ \  \\  \\ \  \    \ \  \ 
   \ \__\ \__\ \_______\   \ \__\ \ \_______\       \ \__\   \ \__\ \__\ \__\\ _\\ \__\    \ \__\
    \|__|\|__|\|_______|    \|__|  \|_______|        \|__|    \|__|\|__|\|__|\|__|\|__|     \|__|
-->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台登录 - <?= SITE_NAME ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <style>
        body {background-color: #f5f5f5; height: 100vh; display: flex; align-items: center;}
        .login-card {max-width: 400px; margin: auto; box-shadow: 0 0 20px rgba(0,0,0,0.1);}
        .rate-limit-info {font-size: 0.875rem; color: #6c757d; text-align: center; margin-top: 1rem;}
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">管理控制台 - 登录</h2>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                    <?php if (isset($currentAttempts)): ?>
                    <div class="mt-2"><small>当前尝试次数：<?= $currentAttempts ?></small></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <input type="text" class="form-control" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密码</label>
                        <input type="password" class="form-control" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                        <span class="spinner-border spinner-border-sm d-none" id="loginSpinner"></span>
                        登录
                    </button>
                </form>

                <div class="rate-limit-info mt-3">
                    <i class="bi bi-shield-check text-success"></i> 安全提示：登录保护通道已启动<br>
                    <i class="bi-heart text-success"></i> 欢迎进行网络渗透测试, 若您愿意提供复现方式, 我们将不胜感激~
                </div>

            </div>
        </div>
    </div>
    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        const spinner = document.getElementById('loginSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');
    });
    </script>

    <!--hackegg-->
    <script src="/assets/js/easter-egg/HackWelcome.js" charset="utf-8" defer></script>
</body>
</html>
