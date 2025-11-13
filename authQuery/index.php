<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
// require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../api/v1/functions.php';

// 初始化变量
$message = '';
$messageType = '';
$cardCode = trim($_POST['card_code'] ?? '');
$machineCode = trim($_POST['machine_code'] ?? '');
$password = $_POST['password'] ?? '';
$newMachineCode = trim($_POST['new_machine_code'] ?? '');
$authInfo = null;
$isLocked = false;
$isExpired = false;
$isBound = false;
$showAuthInfo = false;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 验证输入
        if (empty($cardCode)) {
            if (empty($machineCode)) {
                logSystemEvent(
                        'warning', 
                        '请至少输入授权码或机器码其中一项', 
                        $cardCode,
                        $machineCode,
                    );
                
                $message = "请至少输入授权码或机器码其中一项";
                $messageType = "danger";
                throw new Exception($message);
            }
            // 仅通过机器码查询
            $stmt = $db->prepare("SELECT * FROM auth_users WHERE machine_code = ?");
            $stmt->execute([$machineCode]);
            $authInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$authInfo) {
                logSystemEvent(
                        'warning', 
                        '该机器码未绑定任何授权', 
                        $cardCode,
                        $machineCode,
                    );
                $message = "该机器码未绑定任何授权";
                $messageType = "danger";
                $showAuthInfo = false;
            } else {
                $cardCode = $authInfo['card_code']; // 自动填充授权码
                $showAuthInfo = true;
                logSystemEvent(
                    'success', 
                    '查询成功', 
                    $cardCode,
                    $machineCode,
                );
            }
        } else {
            // 通过授权码查询
            $stmt = $db->prepare("SELECT * FROM auth_users WHERE card_code = ?");
            $stmt->execute([$cardCode]);
            $authInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$authInfo) {
                logSystemEvent(
                    'warning', 
                    '授权不存在', 
                    $cardCode,
                    $machineCode,
                    );
                $message = "授权不存在";
                $messageType = "danger";
                $showAuthInfo = false;
            } else {
                $showAuthInfo = true;
                
                logSystemEvent(
                    'success', 
                    '查询成功', 
                    $cardCode,
                    $machineCode,
                );
                
                // 如果同时提供了机器码，验证是否匹配
                if (!empty($machineCode) && $machineCode !== $authInfo['machine_code']) {
                    logSystemEvent(
                        'warning', 
                        '授权码与现有授权不匹配, 若你未设置密码, 请联系管理员提供该授权绑定的机器码以找回授权', 
                        $cardCode,
                        $machineCode,
                    );
                    $message = "授权码与现有授权不匹配, 若你未设置密码, 请联系管理员提供该授权绑定的机器码以找回授权";
                    $messageType = "warning";
                }
            }
        }

        // 如果有授权信息，初始化状态变量
        if ($showAuthInfo && $authInfo) {
            $isExpired = !empty($authInfo['expires']) && strtotime($authInfo['expires']) < time();
            $isLocked = (bool)($authInfo['is_locked'] ?? false);
            $isBound = !empty($authInfo['machine_code']);
            
            // 处理机器码修改请求
            if (isset($_POST['update_machine_code'])) {
                if (empty($authInfo['password'])) {
                    $message = "该授权未设置密码，无法修改机器码";
                    $messageType = "danger";
                    
                    logSystemEvent(
                        'warning', 
                        $message, 
                        $cardCode,
                        $machineCode,
                    );
                } elseif (!password_verify($password, $authInfo['password'])) {
                    $message = "密码错误，无法修改机器码";
                    $messageType = "danger";
                    logSystemEvent(
                        'warning', 
                        $message, 
                        $cardCode,
                        $machineCode,
                    );
                } elseif (empty($newMachineCode)) {
                    $message = "请输入新的机器码";
                    $messageType = "danger";
                    logSystemEvent(
                        'warning', 
                        $message, 
                        $cardCode,
                        $machineCode,
                    );
                } else {
                    // 更新机器码
                    $updateStmt = $db->prepare("UPDATE auth_users SET machine_code = ? WHERE card_code = ?");
                    $updateStmt->execute([$newMachineCode, $authInfo['card_code']]);
                    $message = "机器码更新成功";
                    $messageType = "success";
                    logSystemEvent(
                        'action', 
                        $message, 
                        $cardCode,
                        $machineCode,
                    );
                    // 刷新授权信息
                    $stmt->execute([$authInfo['card_code']]);
                    $authInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    $isBound = !empty($authInfo['machine_code']);
                    $machineCode = $newMachineCode; // 更新显示的机器码
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $message = "系统错误，请稍后再试";
        $messageType = "danger";
        logSystemEvent(
            'error', 
            $message, 
            $cardCode,
            $machineCode,
        );
    } catch (Exception $e) {
        // 其他验证错误
        $message = $e->getMessage();
        $messageType = "danger";
        logSystemEvent(
            'error', 
            $message, 
            $cardCode,
            $machineCode,
        );
    }
}

/**
 * 格式化机器码显示，隐藏中间部分
 */
function formatMachineCode($fullCode) {
    if (empty($fullCode)) return '';
    
    $parts = explode('-', $fullCode);
    $totalParts = count($parts);
    
    if ($totalParts >= 4) {
        return $parts[0] . '-****-****-****-' . end($parts);
    }
    
    $visibleLength = 4;
    $prefix = substr($fullCode, 0, $visibleLength);
    $suffix = substr($fullCode, -$visibleLength);
    return $prefix . '****' . $suffix;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="授权查询系统">
    <title>授权查询 | <?= htmlspecialchars(SITE_NAME, ENT_QUOTES) ?></title>
    <link href="/assets/css/style.css" rel="stylesheet">
    <?php require_once __DIR__ .  '/../includes/header.php'; ?>
</head>
<body class="bg-dark text-light">
    <div id="particles-js"></div>
    
    <?php require_once __DIR__ .  '/../includes/navbar.php'; ?>
    
    <!--加载页面-->
    <?php require_once __DIR__ .  '/../includes/loading.php'; ?>

    <section class="hero d-flex align-items-center min-vh-100">
        <div class="container position-relative z-index-1">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card bg-dark bg-opacity-75 border-primary text-white">
                        <div class="card-header bg-primary">
                            <h3 class="mb-0"><i class="bi bi-shield-lock me-2"></i>授权查询系统</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                            <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES) ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="mb-4">
                                <div class="mb-3">
                                    <label for="card_code" class="form-label">授权码</label>
                                    <input type="text" class="form-control" id="card_code" name="card_code" 
                                           value="<?= htmlspecialchars($cardCode, ENT_QUOTES) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="machine_code" class="form-label">机器码</label>
                                    <input type="text" class="form-control" id="machine_code" name="machine_code" 
                                           value="<?= htmlspecialchars($machineCode, ENT_QUOTES) ?>">
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle-fill me-2"></i>输入授权码或机器码其一即可查询授权<br>
                                    <i class="bi bi-info-circle-fill me-2"></i>上述信息全部填写可验证授权与机器码是否匹配
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>查询授权
                                </button>
                            </form>
                            
                            <?php if ($showAuthInfo && isset($authInfo)): ?>
                            <div class="auth-result mb-4">
                                <h4 class="mb-3">授权信息</h4>
                                <div class="table-responsive">
                                    <table class="table table-dark table-bordered">
                                        <tbody>
                                            <tr>
                                                <th width="30%">授权码</th>
                                                <td><?= htmlspecialchars($authInfo['card_code'], ENT_QUOTES) ?></td>
                                            </tr>
                                            <tr>
                                                <th>授权状态</th>
                                                <td>
                                                    <?php if ($isLocked): ?>
                                                    <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>已锁定</span>
                                                    <?php if (!empty($authInfo['lock_reason'])): ?>
                                                    <span class="ms-2">原因: <?= htmlspecialchars($authInfo['lock_reason'], ENT_QUOTES) ?></span>
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    <span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>正常</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>有效期</th>
                                                <td>
                                                    <?= !empty($authInfo['expires']) ? htmlspecialchars($authInfo['expires'], ENT_QUOTES) : '永久' ?>
                                                    <?php if ($isExpired): ?>
                                                    <span class="badge bg-warning text-dark ms-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>已过期</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill me-1"></i>有效</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>机器码绑定</th>
                                                <td>
                                                    <?php if ($isBound): ?>
                                                    <code title="机器码">
                                                        <?= htmlspecialchars(formatMachineCode($authInfo['machine_code']), ENT_QUOTES) ?>
                                                    </code>
                                                    <?php if (!empty($machineCode)): ?>
                                                        <?php if ($machineCode === $authInfo['machine_code']): ?>
                                                            <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill me-1"></i>匹配</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger ms-2"><i class="bi bi-x-circle-fill me-1"></i>不匹配</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary"><i class="bi bi-dash-circle-fill me-1"></i>未绑定</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>首次登录时间</th>
                                                <td><?= !empty($authInfo['first_login_time']) ? htmlspecialchars($authInfo['first_login_time'], ENT_QUOTES) : '从未登录' ?></td>
                                            </tr>
                                            <tr>
                                                <th>最后登录时间</th>
                                                <td><?= !empty($authInfo['last_login_time']) ? htmlspecialchars($authInfo['last_login_time'], ENT_QUOTES) : '从未登录' ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- 机器码修改表单 -->
                                <div class="mt-4">
                                    <h5 class="mb-3"><i class="bi bi-pencil-square me-2"></i>修改绑定机器码</h5>
                                    <form method="POST">
                                        <input type="hidden" name="card_code" value="<?= htmlspecialchars($authInfo['card_code'], ENT_QUOTES) ?>">
                                        <input type="hidden" name="machine_code" value="<?= htmlspecialchars($machineCode, ENT_QUOTES) ?>">
                                        
                                        <div class="mb-3">
                                            <label for="new_machine_code" class="form-label">新机器码</label>
                                            <input type="text" class="form-control" id="new_machine_code" name="new_machine_code" 
                                                   value="<?= htmlspecialchars($newMachineCode, ENT_QUOTES) ?>" required>
                                        </div>
                                        
                                        <?php if (!empty($authInfo['password'])): ?>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">授权密码</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="form-text text-white">修改机器码需要验证授权密码, 若未设置授权密码, 可向管理员提供完整机器码以验证身份。</div>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>该授权未设置密码，无法修改机器码
                                        </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="update_machine_code" class="btn btn-primary w-100" 
                                                <?= empty($authInfo['password']) ? 'disabled' : '' ?>>
                                            <i class="bi bi-gear-fill me-2"></i>更新机器码
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-white">
                            <small>如有问题，请联系管理员</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ .  '/../includes/footer.php'; ?>
</body>
</html>