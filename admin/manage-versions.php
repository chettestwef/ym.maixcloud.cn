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
$versions = [];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_version':
                $version = trim($_POST['version'] ?? '');
                $downloadUrl = trim($_POST['download_url'] ?? '');
                $fileSize = trim($_POST['file_size'] ?? '');
                $isLatest = isset($_POST['is_latest']) ? 1 : 0;
                $changelog = trim($_POST['changelog'] ?? '');
                
                if (empty($version) || empty($downloadUrl) || empty($fileSize)) {
                    throw new Exception('请填写所有必填字段');
                }
                
                // 如果设置为最新版本，先取消其他版本的最新标记
                if ($isLatest) {
                    $stmt = $db->prepare("UPDATE scripts SET is_latest = 0");
                    $stmt->execute();
                }
                
                // 插入新版本
                $stmt = $db->prepare("INSERT INTO scripts (version, download_url, release_date, file_size, is_latest) 
                                     VALUES (?, ?, NOW(), ?, ?)");
                $stmt->execute([$version, $downloadUrl, $fileSize, $isLatest]);
                $scriptId = $db->lastInsertId();
                
                // 添加更新日志
                if (!empty($changelog)) {
                    $lines = explode("\n", $changelog);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $stmt = $db->prepare("INSERT INTO changelogs (script_id, description) VALUES (?, ?)");
                            $stmt->execute([$scriptId, $line]);
                        }
                    }
                }
                
                $success = '版本添加成功';
                break;
                
            case 'set_latest':
                $versionId = (int)($_POST['version_id'] ?? 0);
                
                if ($versionId > 0) {
                    $db->query("UPDATE scripts SET is_latest = 0");
                    $stmt = $db->prepare("UPDATE scripts SET is_latest = 1 WHERE id = ?");
                    $stmt->execute([$versionId]);
                    $success = '已设置为最新版本';
                }
                break;
        }
    } catch (PDOException $e) {
        $error = '数据库错误: ' . (strpos($e->getMessage(), 'Duplicate entry') !== false ? 
         '版本号已存在' : $e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 获取所有版本
try {
    $stmt = $db->query("
        SELECT s.*, 
               (SELECT COUNT(*) FROM changelogs WHERE script_id = s.id) AS changelog_count
        FROM scripts s
        ORDER BY release_date DESC
    ");
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = '获取版本列表失败: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>版本管理 - <?= htmlspecialchars(SITE_NAME) ?></title>
    <!-- 使用BootCDN资源 -->
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .badge {
            font-size: 0.85em;
        }
        .form-section {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
        }
        .version-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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
                <h1 class="h2 mb-4">版本管理</h1>
                
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
                
                <div class="row" style="">
                    <div class="col-lg-8">
                        <!-- 版本列表 -->
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-white">
                                <i class="bi bi-collection me-2"></i>版本列表
                                <span class="badge bg-light text-dark float-end"><?= count($versions) ?> 个版本</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($versions)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-exclamation-circle fs-1"></i>
                                    <p class="mt-2">暂无版本信息</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>版本号</th>
                                                <th>发布时间</th>
                                                <th>文件大小</th>
                                                <th>状态</th>
                                                <th>下载状态</th>
                                                <th>强制升级</th>  <!-- 新增的列 -->
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($versions as $version): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($version['version']) ?></strong>
                                                    <?php if ($version['is_latest']): ?>
                                                    <span class="badge bg-success ms-2">最新</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('Y-m-d H:i', strtotime($version['release_date'])) ?></td>
                                                <td><?= htmlspecialchars($version['file_size']) ?></td>
                                                <td>
                                                    <?php if ($version['is_latest']): ?>
                                                    <span class="badge bg-success">当前版本</span>
                                                    <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="set_latest">
                                                        <input type="hidden" name="version_id" value="<?= $version['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">设为最新</button>
                                                    </form>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <td>
                                                    <?php if ($version['download_disabled']): ?>
                                                    <span class="badge bg-danger">禁用下载</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-success">可下载</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <!-- 新增的强制升级状态列 -->
                                                <td>
                                                    <?php if (!empty($version['is_forced'])): ?>
                                                    <span class="badge bg-danger">强制升级</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">可选</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <td>
                                                    <a href="version-details.php?id=<?= $version['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i> 编辑
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php if ($version['changelog_count'] > 0): ?>
                                            <tr>
                                                <td colspan="6" class="small text-muted">  <!-- 将colspan从5改为6 -->
                                                    <i class="bi bi-journal-text"></i> 包含 <?= $version['changelog_count'] ?> 条更新日志
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- 添加新版本 -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-plus-circle me-2"></i>添加新版本
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_version">
                                    
                                    <div class="mb-3">
                                        <label for="version" class="form-label">版本号</label>
                                        <input type="text" class="form-control" id="version" name="version" required placeholder="例如: 1.0.0">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="download_url" class="form-label">下载地址</label>
                                        <input type="text" class="form-control" id="download_url" name="download_url" required placeholder="例如: /downloads/script_v1.0.0.zip">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="file_size" class="form-label">文件大小</label>
                                        <input type="text" class="form-control" id="file_size" name="file_size" required placeholder="例如: 5.2MB">
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_latest" name="is_latest" checked>
                                        <label class="form-check-label" for="is_latest">设为最新版本</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="changelog" class="form-label">更新日志 (每行一条)</label>
                                        <textarea class="form-control" id="changelog" name="changelog" rows="5" placeholder="例如:
新增自动农场功能
修复登录异常问题
优化性能提升"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-save me-2"></i>添加版本
                                    </button>
                                </form>
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
        // 自动设置默认值
        $(document).ready(function() {
            // 点击表格行填充表单
            $('table tbody tr').click(function() {
                const version = $(this).find('td:first').text().trim();
                $('#version').val(version.replace('最新', '').trim());
            });
        });
    </script>
</body>
</html>