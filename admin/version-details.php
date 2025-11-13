<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$versionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// 获取版本信息
$version = [];
$changelogs = [];
try {
    $stmt = $db->prepare("SELECT * FROM scripts WHERE id = ?");
    $stmt->execute([$versionId]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$version) {
        header('Location: manage-versions.php');
        exit;
    }
    
    // 获取更新日志
    $stmt = $db->prepare("SELECT * FROM changelogs WHERE script_id = ? ORDER BY id");
    $stmt->execute([$versionId]);
    $changelogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = '获取版本信息失败: ' . $e->getMessage();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_version':
                $newVersion = trim($_POST['version'] ?? '');
                $newDownloadUrl = trim($_POST['download_url'] ?? '');
                $newFileSize = trim($_POST['file_size'] ?? '');
                $newIsLatest = isset($_POST['is_latest']) ? 1 : 0;
                $newDownloadDisabled = isset($_POST['download_disabled']) ? 1 : 0;
                $newIsForced = isset($_POST['is_forced']) ? 1 : 0;
                
                if (empty($newVersion) || empty($newDownloadUrl) || empty($newFileSize)) {
                    throw new Exception('请填写所有必填字段');
                }
                
                // 如果设置为最新版本，先取消其他版本的最新标记
                if ($newIsLatest) {
                    $stmt = $db->prepare("UPDATE scripts SET is_latest = 0 WHERE id != ?");
                    $stmt->execute([$versionId]);
                    
                    // 如果是设置为最新版本，同时取消其他版本的强制升级标记
                    $stmt = $db->prepare("UPDATE scripts SET is_forced = 0 WHERE id != ?");
                    $stmt->execute([$versionId]);
                }
                
                // 更新版本信息
                $stmt = $db->prepare("UPDATE scripts SET 
                                    version = ?, 
                                    download_url = ?, 
                                    file_size = ?, 
                                    is_latest = ?,
                                    download_disabled = ?,
                                    is_forced = ?
                                    WHERE id = ?");
                $stmt->execute([
                    $newVersion, 
                    $newDownloadUrl, 
                    $newFileSize, 
                    $newIsLatest,
                    $newDownloadDisabled,
                    $newIsForced,
                    $versionId
                ]);
                
                $success = '版本信息更新成功';
                break;
                
            case 'add_changelog':
                $description = trim($_POST['description'] ?? '');
                
                if (!empty($description)) {
                    $stmt = $db->prepare("INSERT INTO changelogs (script_id, description) VALUES (?, ?)");
                    $stmt->execute([$versionId, $description]);
                    $success = '更新日志添加成功';
                }
                break;
                
            case 'delete_changelog':
                $changelogId = (int)($_POST['changelog_id'] ?? 0);
                
                if ($changelogId > 0) {
                    $stmt = $db->prepare("DELETE FROM changelogs WHERE id = ?");
                    $stmt->execute([$changelogId]);
                    $success = '更新日志删除成功';
                }
                break;
        }
        
        // 刷新数据
        $stmt = $db->prepare("SELECT * FROM scripts WHERE id = ?");
        $stmt->execute([$versionId]);
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT * FROM changelogs WHERE script_id = ? ORDER BY id");
        $stmt->execute([$versionId]);
        $changelogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = '数据库错误: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>版本详情 - <?= htmlspecialchars(SITE_NAME) ?></title>
    <!-- 使用BootCDN资源 -->
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <style>
        .version-header {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .changelog-item {
            border-left: 3px solid #0d6efd;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .changelog-actions {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .changelog-item:hover .changelog-actions {
            opacity: 1;
        }
        .force-update-badge {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- 移动端侧边栏切换按钮 -->
    <button class="btn btn-primary d-md-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
        <i class="bi bi-list"></i> 菜单
    </button>

    <div class="container-fluid">
        <div class="row" style="flex-wrap: nowrap;">
            <!-- 引入侧边栏 -->
            <?php include '_sidebar.php'; ?>
            
            <!-- 主内容区 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">版本详情</h1>
                    <a href="manage-versions.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <!-- 消息提示 -->
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
                
                <!-- 版本基本信息 -->
                <div class="version-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>
                                <?= htmlspecialchars($version['version']) ?>
                                <?php if ($version['is_latest']): ?>
                                <span class="badge bg-success">最新版本</span>
                                <?php endif; ?>
                                <?php if ($version['is_forced']): ?>
                                <span class="badge force-update-badge">强制升级</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-muted mb-1">
                                <i class="bi bi-calendar"></i> 发布时间: <?= date('Y-m-d H:i', strtotime($version['release_date'])) ?>
                            </p>
                            <p class="text-muted">
                                <i class="bi bi-file-earmark-arrow-down"></i> 文件大小: <?= htmlspecialchars($version['file_size']) ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="<?= htmlspecialchars($version['download_url']) ?>" class="btn btn-primary">
                                <i class="bi bi-download"></i> 下载此版本
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <!-- 编辑版本信息 -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-pencil"></i> 编辑版本信息
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_version">
                                    
                                    <div class="mb-3">
                                        <label for="version" class="form-label">版本号</label>
                                        <input type="text" class="form-control" id="version" name="version" 
                                               value="<?= htmlspecialchars($version['version']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="download_url" class="form-label">下载地址</label>
                                        <input type="text" class="form-control" id="download_url" name="download_url" 
                                               value="<?= htmlspecialchars($version['download_url']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="file_size" class="form-label">文件大小</label>
                                        <input type="text" class="form-control" id="file_size" name="file_size" 
                                               value="<?= htmlspecialchars($version['file_size']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_latest" name="is_latest" 
                                               <?= $version['is_latest'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_latest">设为最新版本</label>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="download_disabled" name="download_disabled" 
                                               <?= $version['download_disabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="download_disabled">禁用下载</label>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_forced" name="is_forced" 
                                               <?= $version['is_forced'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_forced">强制升级</label>
                                        <small class="form-text text-muted">勾选后，客户端必须升级到此版本才能继续使用</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-save"></i> 保存更改
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <!-- 更新日志管理 -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-journal-text"></i> 更新日志
                            </div>
                            <div class="card-body">
                                <!-- 添加新日志 -->
                                <form method="POST" class="mb-4">
                                    <input type="hidden" name="action" value="add_changelog">
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">添加更新日志</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" 
                                                  placeholder="输入更新内容..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-plus-circle"></i> 添加日志
                                    </button>
                                </form>
                                
                                <!-- 日志列表 -->
                                <h5 class="mb-3">当前更新日志</h5>
                                <?php if (empty($changelogs)): ?>
                                    <div class="alert alert-info">暂无更新日志</div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($changelogs as $log): ?>
                                        <div class="list-group-item changelog-item">
                                            <div class="d-flex justify-content-between">
                                                <p class="mb-1"><?= htmlspecialchars($log['description']) ?></p>
                                                <div class="changelog-actions">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_changelog">
                                                        <input type="hidden" name="changelog_id" value="<?= $log['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('确定要删除这条更新日志吗？')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <small class="text-muted">ID: <?= $log['id'] ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- 使用BootCDN的JS资源 -->
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
    <script>
        // 自动聚焦到第一个输入框
        $(document).ready(function() {
            $('form:first').find('input[type="text"]:first').focus();
            
            // 如果勾选"强制升级"，自动勾选"设为最新版本"
            $('#is_forced').change(function() {
                if ($(this).is(':checked')) {
                    $('#is_latest').prop('checked', true);
                }
            });
        });
    </script>
</body>
</html>