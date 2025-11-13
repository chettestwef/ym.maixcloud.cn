<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 初始化变量
$error = '';
$success = '';
$users = [];
$onlineCount = 0;
$offlineCount = 0;
$activeCount = 0;
$expiredCount = 0;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_auth':
                // 添加新授权
                $cardCode = trim($_POST['card_code'] ?? '');
                $expires = trim($_POST['expires'] ?? '');
                
                if (empty($cardCode) || empty($expires)) {
                    throw new Exception('请填写卡号和过期时间');
                }
                
                $stmt = $db->prepare("INSERT INTO auth_users (card_code, expires) VALUES (?, ?)");
                $stmt->execute([$cardCode, $expires]);
                $success = '授权添加成功';
                break;
                
            case 'delete_auth':
                // 删除授权
                $cardCode = trim($_POST['card_code'] ?? '');
                
                try {
                    $db->beginTransaction();
                    
                    // 1. 先获取用户ID
                    $stmt = $db->prepare("SELECT id FROM auth_users WHERE card_code = ? LIMIT 1");
                    $stmt->execute([$cardCode]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        $error = '未找到对应的授权卡号';
                        $db->rollBack();
                        break;
                    }
                    
                    $userId = $user['id'];
                    
                    // 2. 删除关联的登录历史记录
                    $stmt = $db->prepare("DELETE FROM auth_login_history WHERE auth_user_id = ?");
                    $stmt->execute([$userId]);
                    
                    // 3. 最后删除用户主体
                    $stmt = $db->prepare("DELETE FROM auth_users WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $db->commit();
                    $success = '授权已删除';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = '删除授权失败: ' . $e->getMessage();
                }
                break;
        }
    } catch (PDOException $e) {
        $error = '数据库错误: ' . (strpos($e->getMessage(), 'Duplicate entry') !== false ? 
                 '卡号已存在' : $e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 获取所有授权用户并统计状态
try {
    $stmt = $db->query("
        SELECT 
            au.*, 
            (SELECT COUNT(*) FROM auth_login_history WHERE auth_user_id = au.id) AS login_count,
            (SELECT MAX(login_time) FROM auth_login_history WHERE auth_user_id = au.id) AS last_login,
            CASE 
                WHEN au.last_heartbeat_time IS NOT NULL AND TIMESTAMPDIFF(SECOND, au.last_heartbeat_time, NOW()) <= 60 
                THEN 1 ELSE 0 
            END AS is_online,
            CASE 
                WHEN au.expires < NOW() THEN 1 ELSE 0 
            END AS is_expired
        FROM auth_users au
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 统计各种状态数量
    foreach ($users as $user) {
        if ($user['is_online']) {
            $onlineCount++;
        } else {
            $offlineCount++;
        }
        
        if ($user['is_expired']) {
            $expiredCount++;
        } else {
            $activeCount++;
        }
    }

} catch (PDOException $e) {
    $error = '获取用户列表失败: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权管理 - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <style>
        .table-responsive { overflow-x: auto; }
        .badge { font-size: 0.85em; }
        .form-section { margin-bottom: 2rem; padding: 1rem; background-color: #f8f9fa; border-radius: 0.25rem; }
        .nav-link.active { background-color: #0d6efd; color: white !important; }
        .status-badge { min-width: 60px; display: inline-block; text-align: center; }
    </style>
</head>
<body>
    <button class="btn btn-primary d-md-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
        <i class="bi bi-list"></i> 菜单
    </button>

    <div class="container-fluid">
        <div class="row" style="flex-wrap: nowrap;">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h1 class="h2 mb-4">授权管理</h1>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- 授权用户列表 -->
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-people me-2"></i>授权用户列表
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> 正常: <?= $activeCount ?>
                            </span>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-exclamation-circle"></i> 过期: <?= $expiredCount ?>
                            </span>
                            <span class="badge bg-primary">
                                <i class="bi bi-check-circle"></i> 在线: <?= $onlineCount ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="bi bi-x-circle"></i> 离线: <?= $offlineCount ?>
                            </span>
                            <span class="badge bg-light text-dark"><?= count($users) ?> 条记录</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- 添加授权表单 -->
                        <div class="mb-4 p-3 border rounded">
                            <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>添加新授权</h5>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add_auth">
                                
                                <div class="col-md-6">
                                    <label for="card_code" class="form-label">卡号</label>
                                    <input type="text" class="form-control" id="card_code" name="card_code" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="expires" class="form-label">过期时间</label>
                                    <input type="datetime-local" class="form-control" id="expires" name="expires" required
                                           value="<?= date('Y-m-d\TH:i') ?>">
                                </div>
                
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAuthDays(1)">+1 天</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAuthDays(7)">+1 周</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAuthDays(30)">+1 月</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addAuthDays(90)">+1 季</button>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-save me-2"></i>添加授权
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>卡号</th>
                                        <th>机器码</th>
                                        <th>过期时间</th>
                                        <th>登录次数</th>
                                        <th>最后登录</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['card_code']) ?></td>
                                        <td>
                                            <?php if ($user['machine_code']): ?>
                                            <span class="text-monospace small"><?= htmlspecialchars($user['machine_code']) ?></span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">未绑定</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?= $user['is_expired'] ? 'text-danger' : '' ?>">
                                            <?= htmlspecialchars($user['expires']) ?>
                                            <?php if ($user['is_expired']): ?>
                                                <span class="badge bg-warning text-dark ms-1">已过期</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['login_count'] ?? 0) ?></td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                            <?= htmlspecialchars($user['last_login']) ?>
                                            <?php else: ?>
                                            <span class="text-muted">从未登录</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge badge <?= $user['is_locked'] ? 'bg-danger' : ($user['is_expired'] ? 'bg-warning text-dark' : 'bg-success') ?>">
                                                <?= $user['is_locked'] ? '已锁定' : ($user['is_expired'] ? '已过期' : '正常') ?>
                                            </span>
                                            <span class="status-badge badge <?= $user['is_online'] ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= $user['is_online'] ? '在线' : '离线' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="auth-details.php?card_code=<?= urlencode($user['card_code']) ?>" 
                                               class="btn btn-sm btn-outline-primary me-1"
                                               title="查看详情">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('确定要删除此授权吗？此操作不可恢复！');">
                                                <input type="hidden" name="action" value="delete_auth">
                                                <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
    <script>
        $(document).ready(function() {
            // 设置默认过期时间为明天
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().slice(0, 16);
            $('#expires').val(tomorrowStr);
        });
        
        function addAuthDays(days) {
            const input = document.getElementById('expires');
            let current = input.value ? new Date(input.value) : new Date();
        
            current.setDate(current.getDate() + days);
        
            const year = current.getFullYear();
            const month = String(current.getMonth() + 1).padStart(2, '0');
            const day = String(current.getDate()).padStart(2, '0');
            const hours = String(current.getHours()).padStart(2, '0');
            const minutes = String(current.getMinutes()).padStart(2, '0');
        
            input.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    </script>
</body>
</html>