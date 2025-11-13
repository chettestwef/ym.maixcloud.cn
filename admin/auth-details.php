<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$cardCode = $_GET['card_code'] ?? '';
if (empty($cardCode)) {
    header('Location: manage-auth.php');
    exit;
}

// 获取用户基本信息
try {
    $stmt = $db->prepare("
            SELECT *, 
            CASE WHEN expires < NOW() THEN 1 ELSE 0 END AS is_expired 
            FROM auth_users 
            WHERE card_code = ?
        ");
    $stmt->execute([$cardCode]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: manage-auth.php');
        exit;
    }
    
    // 获取登录历史
    $stmt = $db->prepare("
        SELECT * FROM auth_login_history 
        WHERE auth_user_id = ?
        ORDER BY login_time DESC
        LIMIT 50
    ");
    $stmt->execute([$user['id']]);
    $loginHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('数据库错误: ' . $e->getMessage());
}

// 处理密码修改请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword)) {
        $passwordError = "密码不能为空";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "两次输入的密码不一致";
    } else {
        try {
            // 加密密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE auth_users SET password = ? WHERE card_code = ?");
            $stmt->execute([$hashedPassword, $cardCode]);
            
            $passwordSuccess = "密码更新成功";
            // 刷新用户数据
            $stmt = $db->prepare("SELECT * FROM auth_users WHERE card_code = ?");
            $stmt->execute([$cardCode]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $passwordError = "数据库错误: " . $e->getMessage();
        }
    }
}

// 修改机器码和心跳间隔
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_machine_heartbeat') {
    $newMachineCode = trim($_POST['new_machine_code'] ?? '');
    $newHeartbeatInterval = intval($_POST['new_heartbeat_interval'] ?? 0);

    $stmt = $db->prepare("UPDATE auth_users SET machine_code = ?, next_heartbeat_interval = ? WHERE card_code = ?");
    $stmt->execute([$newMachineCode, $newHeartbeatInterval, $cardCode]);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// 锁定账号附带原因
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'lock_account') {
    $reason = trim($_POST['lock_reason'] ?? '管理员手动锁定');
    $stmt = $db->prepare("UPDATE auth_users SET is_locked = 1, lock_reason = ? WHERE card_code = ?");
    $stmt->execute([$reason, $cardCode]);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// 解锁账号
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unlock_account') {
    $stmt = $db->prepare("UPDATE auth_users SET is_locked = 0, lock_reason = NULL WHERE card_code = ?");
    $stmt->execute([$cardCode]);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// 修改过期时间
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_expires') {
    $newExpires = $_POST['new_expires'] ?? '';
    
    if (empty($newExpires)) {
        $expiresError = "过期时间不能为空";
    } else {
        try {
            $stmt = $db->prepare("UPDATE auth_users SET expires = ? WHERE card_code = ?");
            $stmt->execute([$newExpires, $cardCode]);
            
            // 刷新用户数据
            $stmt = $db->prepare("SELECT * FROM auth_users WHERE card_code = ?");
            $stmt->execute([$cardCode]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $expiresSuccess = "过期时间更新成功";
        } catch (PDOException $e) {
            $expiresError = "数据库错误: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权详情 - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    
    <style>
        .thread-status-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            background-color: #f8f9fa;
        }
        .thread-status-container table {
            margin-bottom: 0;
            font-size: 0.85rem;
        }
        .thread-status-container table th {
            font-weight: 500;
            color: #6c757d;
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
        }
        .thread-status-container table td {
            padding: 0.3rem 0.5rem;
            vertical-align: middle;
        }
        .thread-status-container table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .thread-status-container .badge {
            font-size: 0.8em;
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="flex-wrap: nowrap;">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">授权详情</h1>
                    <a href="manage-auth.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if (isset($passwordSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($passwordSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($passwordError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($passwordError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($expiresSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($expiresSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($expiresError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($expiresError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-person-badge"></i> 基本信息
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">卡密:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($user['card_code']) ?></dd>
                                    
                                    <dt class="col-sm-4">密码状态:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['password']): ?>
                                        <span class="badge bg-success">已设置</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">未设置</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">机器码:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['machine_code']): ?>
                                        <code><?= htmlspecialchars($user['machine_code']) ?></code>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">未绑定</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">过期时间:</dt>
                                    <dd class="col-sm-8 <?= isset($user['is_expired']) && $user['is_expired'] ? 'text-danger' : '' ?>">
                                        <?= htmlspecialchars($user['expires'] ?? '无记录') ?>
                                        <?php if (isset($user['is_expired']) && $user['is_expired']): ?>
                                            <span class="badge bg-warning text-dark ms-1">已过期</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">状态:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['is_locked'] ?? false): ?>
                                            <span class="badge bg-danger">已锁定</span>
                                            <?php if (!empty($user['lock_reason'])): ?>
                                            <div class="text-muted small mt-1">原因: <?= htmlspecialchars($user['lock_reason']) ?></div>
                                            <?php endif; ?>
                                        <?php elseif (isset($user['is_expired']) && $user['is_expired']): ?>
                                            <span class="badge bg-warning text-dark">已过期</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">正常</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    
                                    
                                    <dt class="col-sm-4">设备类型:</dt>
                                    <dd class="col-sm-8">
                                        <?php if (isset($user['device_type'])): ?>
                                            <?php switch($user['device_type']): 
                                                case 1: ?>
                                                    <span class="badge bg-primary"><i class="bi bi-windows"></i> Windows</span>
                                                <?php break; ?>
                                                <?php case 2: ?>
                                                    <span class="badge bg-success"><i class="bi bi-android2"></i> Android</span>
                                                <?php break; ?>
                                                <?php case 3: ?>
                                                    <span class="badge bg-info">iOS</span>
                                                <?php break; ?>
                                                <?php default: ?>
                                                    <span class="badge bg-secondary">未知设备</span>
                                            <?php endswitch; ?>
                                            
                                            <?php if (isset($user['last_version'])): ?>
                                                <div class="text-muted small mt-1">
                                                    版本: <?= htmlspecialchars($user['last_version']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">未记录</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    
                                    <dt class="col-sm-4">首次登录:</dt>
                                    <dd class="col-sm-8">
                                        <?= $user['first_login_time'] ? htmlspecialchars($user['first_login_time']) : '从未登录' ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">最后登录:</dt>
                                    <dd class="col-sm-8">
                                        <?= $user['last_login_time'] ? htmlspecialchars($user['last_login_time']) : '从未登录' ?>
                                        <?php if ($user['last_ip']): ?>
                                        <div class="text-muted small mt-1">IP: <?= htmlspecialchars($user['last_ip']) ?></div>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">创建时间:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($user['created_at']) ?></dd>
                                    
                                    
                                    <dt class="col-sm-4">最近心跳:</dt>
                                    <dd class="col-sm-8">
                                        <?= $user['last_heartbeat_time'] ? htmlspecialchars($user['last_heartbeat_time']) : '无记录' ?>
                                        <?php if ($user['last_heartbeat_ip']): ?>
                                        <div class="text-muted small mt-1">IP: <?= htmlspecialchars($user['last_heartbeat_ip']) ?></div>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <span class="badge <?= ($user['last_heartbeat_time'] && strtotime($user['last_heartbeat_time']) >= strtotime('-60 seconds')) ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= ($user['last_heartbeat_time'] && strtotime($user['last_heartbeat_time']) >= strtotime('-60 seconds')) ? '在线' : '离线' ?>
                                            </span>
                                        </div>
                                    </dd>
                                    
                                    <dt class="col-sm-4">下次心跳时间:</dt>
                                    <dd class="col-sm-8">
                                        <?php
                                        $interval = $user['next_heartbeat_interval'] ?? null;
                                        $displayValue = (empty($interval) && $interval !== '0') ? '默认' : htmlspecialchars($interval . '秒');
                                        ?>
                                        <span class="badge bg-secondary">
                                            <?= $displayValue ?>
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4">最近状态:</dt>
                                    <dd class="col-sm-8">
                                        <?= $user['last_main_status'] ? htmlspecialchars($user['last_main_status']) : '无记录' ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">主线程状态:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['last_main_status']): ?>
                                            <?php 
                                            // 解析主线程状态
                                            $mainStatus = str_replace(', ', '<br>', htmlspecialchars($user['last_main_status']));
                                            echo $mainStatus;
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">无记录</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">线程状态:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['last_threads_status']): ?>
                                            <div class="thread-status-container">
                                                <?php 
                                                // 按行分割线程状态
                                                $threads = explode("\n", trim($user['last_threads_status']));
                                                ?>
                                                <table class="table table-sm table-borderless">
                                                    <thead>
                                                        <tr>
                                                            <th>线程名</th>
                                                            <th>ID</th>
                                                            <th>状态</th>
                                                            <th>上次心跳</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($threads as $thread): ?>
                                                            <?php 
                                                            $thread = trim($thread);
                                                            if (empty($thread)) continue;
                                                            
                                                            // 尝试两种解析方式
                                                            if (strpos($thread, "\t") !== false) {
                                                                // 表格格式解析（制表符分隔）
                                                                $parts = explode("\t", $thread);
                                                                if (count($parts) >= 4) {
                                                                    $threadName = $parts[0];
                                                                    $threadId = $parts[1];
                                                                    $threadStatus = $parts[2];
                                                                    $lastHeartbeat = $parts[3];
                                                                }
                                                            } else {
                                                                // 原始格式解析（逗号分隔）
                                                                preg_match('/线程名: ([^,]+), ID: (\d+), 状态: ([^,]+(?:\([^)]+\))?), 上次心跳: (.+)/', $thread, $matches);
                                                                if (count($matches) >= 5) {
                                                                    $threadName = $matches[1];
                                                                    $threadId = $matches[2];
                                                                    $threadStatus = $matches[3];
                                                                    $lastHeartbeat = $matches[4];
                                                                }
                                                            }
                                                            
                                                            if (isset($threadName)):
                                                                // 根据状态设置徽章颜色
                                                                $badgeClass = 'bg-secondary';
                                                                if (strpos($threadStatus, '活跃') !== false) {
                                                                    $badgeClass = 'bg-success';
                                                                } elseif (strpos($threadStatus, '冻结') !== false) {
                                                                    $badgeClass = 'bg-danger';
                                                                } elseif (strpos($threadStatus, '无心跳记录') !== false) {
                                                                    $badgeClass = 'bg-warning';
                                                                }
                                                            ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($threadName) ?></td>
                                                                <td><code><?= htmlspecialchars($threadId) ?></code></td>
                                                                <td>
                                                                    <span class="badge <?= $badgeClass ?>">
                                                                        <?= htmlspecialchars($threadStatus) ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= htmlspecialchars($lastHeartbeat) ?></td>
                                                            </tr>
                                                            <?php else: ?>
                                                            <tr>
                                                                <td colspan="4" class="text-muted"><?= htmlspecialchars($thread) ?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">无记录</span>
                                        <?php endif; ?>
                                    </dd>

                                </dl>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <?php if ($user['is_locked']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="unlock_account">
                                        <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-unlock"></i> 解锁账号
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <!-- 锁定按钮 -->
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#lockAccountModal">
                                            <i class="bi bi-lock"></i> 锁定账号
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#updateExpiresModal">
                                        <i class="bi bi-calendar-check"></i> 修改过期时间
                                    </button>
                                    
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updatePasswordModal">
                                        <i class="bi bi-key"></i> 修改密码
                                    </button>
                                    
                                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#updateMachineModal">
                                        <i class="bi bi-cpu"></i> 修改机器码/心跳
                                    </button>

                                    
                                    <form method="POST" action="manage-auth.php" onsubmit="return confirm('确定要删除此授权吗？此操作不可恢复！');" class="d-inline">
                                        <input type="hidden" name="action" value="delete_auth">
                                        <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> 删除授权
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-clock-history"></i> 最近登录记录
                                <span class="badge bg-primary rounded-pill float-end"><?= count($loginHistory) ?></span>
                            </div>
                            <div class="card-body">
                                <?php if ($loginHistory): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>时间</th>
                                                <th>IP地址</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($loginHistory as $log): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($log['login_time']) ?></td>
                                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle fs-1"></i>
                                    <p class="mt-2">暂无登录记录</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 修改过期时间模态框 -->
                <div class="modal fade" id="updateExpiresModal" tabindex="-1" aria-labelledby="updateExpiresModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="updateExpiresModalLabel">修改过期时间</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_expires">
                                <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                                
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="modal_new_expires" class="form-label">新过期时间</label>
                                        <input type="datetime-local" class="form-control" id="modal_new_expires" name="new_expires" required
                                               value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($user['expires'] ?? 'now'))) ?>">
                                    </div>
                                    <div class="mb-3 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDays(1)">+1 天</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDays(7)">+1 周</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDays(30)">+1 月</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDays(90)">+1 季</button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-primary">保存更改</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- 修改密码模态框 -->
                <div class="modal fade" id="updatePasswordModal" tabindex="-1" aria-labelledby="updatePasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="updatePasswordModalLabel">修改密码</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_password">
                                <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                                
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">新密码</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">确认密码</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-primary">保存更改</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- 修改机器码和心跳模态框 -->
                <div class="modal fade" id="updateMachineModal" tabindex="-1" aria-labelledby="updateMachineModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <form method="POST" class="modal-content">
                      <input type="hidden" name="action" value="update_machine_heartbeat">
                      <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                      <div class="modal-header">
                        <h5 class="modal-title" id="updateMachineModalLabel">修改机器码和心跳间隔</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label for="new_machine_code" class="form-label">机器码</label>
                          <input type="text" class="form-control" id="new_machine_code" name="new_machine_code" value="<?= htmlspecialchars($user['machine_code'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                          <label for="new_heartbeat_interval" class="form-label">心跳间隔（秒）</label>
                          <input type="number" class="form-control" id="new_heartbeat_interval" name="new_heartbeat_interval" value="<?= htmlspecialchars($user['next_heartbeat_interval'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存更改</button>
                      </div>
                    </form>
                  </div>
                </div>
                
                <!-- 锁定账号模态框 -->
                <div class="modal fade" id="lockAccountModal" tabindex="-1" aria-labelledby="lockAccountModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <form method="POST" class="modal-content">
                      <input type="hidden" name="action" value="lock_account">
                      <input type="hidden" name="card_code" value="<?= htmlspecialchars($user['card_code']) ?>">
                      <div class="modal-header">
                        <h5 class="modal-title" id="lockAccountModalLabel">锁定账号</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label for="lock_reason" class="form-label">锁定原因</label>
                          <input type="text" class="form-control" id="lock_reason" name="lock_reason" placeholder="例：异常行为或违规使用" required>
                        </div>
                        <div class="alert alert-warning">此操作将立即锁定该账号，请谨慎操作！</div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认锁定</button>
                      </div>
                    </form>
                  </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
    <script>
        function addDays(days) {
            const input = document.getElementById('modal_new_expires');
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