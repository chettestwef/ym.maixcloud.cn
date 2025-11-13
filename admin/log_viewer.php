<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 权限检查
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 获取筛选参数
$level = $_GET['level'] ?? '';
$machineCode = $_GET['machine_code'] ?? '';
$account = $_GET['account'] ?? '';
$ip = $_GET['ip'] ?? '';
$apiPath = $_GET['api_path'] ?? '';
$method = $_GET['method'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// 构建查询条件
$conditions = [];
$params = [];

if (!empty($level)) {
    $conditions[] = "log_level = ?";
    $params[] = $level;
}

if (!empty($machineCode)) {
    $conditions[] = "machine_code LIKE ?";
    $params[] = "%$machineCode%";
}

if (!empty($account)) {
    $conditions[] = "account LIKE ?";
    $params[] = "%$account%";
}

if (!empty($ip)) {
    $conditions[] = "ip_address LIKE ?";
    $params[] = "%$ip%";
}

if (!empty($apiPath)) {
    $conditions[] = "api_path LIKE ?";
    $params[] = "%$apiPath%";
}

if (!empty($method)) {
    $conditions[] = "request_method = ?";
    $params[] = $method;
}

if (!empty($dateFrom)) {
    $conditions[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $conditions[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

// 正确的查询条件构建部分
if (isset($_GET['has_response'])) {  // 修复了这里的括号
    $hasResponse = $_GET['has_response'];
    if ($hasResponse === '1') {
        $conditions[] = "response_data IS NOT NULL AND response_data != ''";
    } elseif ($hasResponse === '0') {
        $conditions[] = "(response_data IS NULL OR response_data = '')";
    }
}


$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取总记录数
$totalQuery = "SELECT COUNT(*) FROM system_logs $where";
$totalStmt = $db->prepare($totalQuery);
$totalStmt->execute($params);
$totalLogs = $totalStmt->fetchColumn();

// 分页设置
$perPage = 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// 获取日志数据
$query = "SELECT *, 
          LENGTH(response_data) as has_response_data 
          FROM system_logs $where 
          ORDER BY created_at DESC 
          LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有日志等级和请求方法用于筛选
$levels = $db->query("SELECT DISTINCT log_level FROM system_logs ORDER BY log_level")->fetchAll(PDO::FETCH_COLUMN);
$methods = $db->query("SELECT DISTINCT request_method FROM system_logs WHERE request_method IS NOT NULL ORDER BY request_method")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>系统日志查看器</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <!-- 引入highlight.js语法高亮 -->
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <style>
        .log-info { color: #0c5460; }
        .log-warning { color: #856404; }
        .log-error { color: #721c24; }
        .log-debug { color: #383d41; }
        .table-responsive { max-height: 70vh; overflow-y: auto; }
        pre {
            max-height: 60vh;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
        }
        .hljs {
            background: transparent !important;
            padding: 0 !important;
        }
        .badge-info { background-color: #0dcaf0; }
        .badge-success { background-color: #198754; }
        .badge-action { background-color: #198754; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-danger { background-color: #dc3545; }
        .badge-secondary { background-color: #6c757d; }
        .view-detail {
            min-width: 60px;
        }
        .log-table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
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
            <!-- 侧边栏 - 桌面端显示 -->
            <div class="col-md-3 col-lg-2 d-none d-md-block px-0">
              <?php include '_sidebar.php'; ?>
            </div>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="mb-4">系统日志查看器</h2>
                
                <!-- 筛选表单 -->
                <div class="card mb-4">
                    <div class="card-header">筛选条件</div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">日志等级</label>
                                <select name="level" class="form-select">
                                    <option value="">全部</option>
                                    <?php foreach ($levels as $lvl): ?>
                                    <option value="<?= $lvl ?>" <?= $level === $lvl ? 'selected' : '' ?>><?= strtoupper($lvl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">请求方法</label>
                                <select name="method" class="form-select">
                                    <option value="">全部</option>
                                    <?php foreach ($methods as $mtd): ?>
                                    <option value="<?= $mtd ?>" <?= $method === $mtd ? 'selected' : '' ?>><?= $mtd ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">包含响应</label>
                                <select name="has_response" class="form-select">
                                    <option value="">全部</option>
                                    <option value="1" <?= isset($_GET['has_response']) && $_GET['has_response'] === '1' ? 'selected' : '' ?>>是</option>
                                    <option value="0" <?= isset($_GET['has_response']) && $_GET['has_response'] === '0' ? 'selected' : '' ?>>否</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">机器码</label>
                                <input type="text" name="machine_code" class="form-control" value="<?= htmlspecialchars($machineCode) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">卡密</label>
                                <input type="text" name="account" class="form-control" value="<?= htmlspecialchars($account) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">IP地址</label>
                                <input type="text" name="ip" class="form-control" value="<?= htmlspecialchars($ip) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">请求路径</label>
                                <input type="text" name="api_path" class="form-control" value="<?= htmlspecialchars($apiPath) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">日期范围</label>
                                <div class="input-group">
                                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <span class="input-group-text">至</span>
                                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-funnel"></i> 筛选
                                </button>
                                <a href="log_viewer.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> 重置
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 日志统计 -->
                <div class="alert alert-info mb-4">
                    共找到 <?= number_format($totalLogs) ?> 条日志记录，当前显示 <?= count($logs) ?> 条
                </div>
                
                <!-- 日志表格 -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>日志列表</span>
                        <div>
                            <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-chevron-left"></i> 上一页
                            </a>
                            <?php endif; ?>
                            <?php if (count($logs) === $perPage): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-sm btn-outline-primary">
                                下一页 <i class="bi bi-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 log-table">
                                <thead>
                                    <tr>
                                        <th width="120">时间</th>
                                        <th width="80">等级</th>
                                        <th width="100">方法</th>
                                        <th width="120">机器码</th>
                                        <th width="100">卡密</th>
                                        <th width="120">IP</th>
                                        <th width="150">请求路径</th>
                                        <th>内容</th>
                                        <th width="100">请求数据</th>
                                        <th width="100">响应数据</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr class="log-<?= $log['log_level'] ?>">
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $log['log_level'] === 'error' ? 'danger' : 
                                                ($log['log_level'] === 'warning' ? 'warning text-white' : 
                                                ($log['log_level'] === 'action' ? 'warning text-white' : 
                                                ($log['log_level'] === 'success' ? 'success text-white' : 
                                                ($log['log_level'] === 'info' ? 'info' : 'secondary'))))
                                            ?>">
                                                <?= strtoupper($log['log_level']) ?>
                                            </span>
                                        </td>
                                        <td><?= $log['request_method'] ? htmlspecialchars($log['request_method']) : '-' ?></td>
                                        <td><?= $log['machine_code'] ? htmlspecialchars(substr($log['machine_code'], 0, 4) . '...') : '-' ?></td>
                                        <td><?= $log['account'] ? htmlspecialchars($log['account']) : '-' ?></td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td>
                                            <?php 
                                            $path = htmlspecialchars($log['api_path']);
                                            $path = preg_replace('/(.*\/)/', '<span class="text-muted">$1</span>', $path);
                                            echo $path;
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['message']) ?></td>
                                        <td>
                                            <?php if ($log['request_data']): ?>
                                            <button class="btn btn-sm btn-outline-info view-detail" 
                                                    data-data="<?= htmlspecialchars($log['request_data']) ?>"
                                                    data-title="请求数据">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log['response_data']): ?>
                                            <button class="btn btn-sm btn-outline-success view-detail" 
                                                    data-data="<?= htmlspecialchars($log['response_data']) ?>"
                                                    data-title="响应数据">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- 详情模态框 -->
    <div class="modal fade" id="logDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">请求详情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <pre class="m-0"><code class="language-json" id="detailContent"></code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" id="copyDetail">
                        <i class="bi bi-clipboard"></i> 复制
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
    <!-- 引入highlight.js -->
    <script src="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
    // 初始化语法高亮
    hljs.highlightAll();
    
    // 详情查看功能
    document.querySelectorAll('.view-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const rawData = this.getAttribute('data-data');
            const title = this.getAttribute('data-title') || '详情';
            
            try {
                const data = JSON.parse(rawData);
                const prettyData = JSON.stringify(data, null, 2);
                
                // 更新模态框标题
                document.querySelector('#logDetailModal .modal-title').textContent = title;
                
                // 设置内容
                const codeBlock = document.getElementById('detailContent');
                codeBlock.textContent = prettyData;
                hljs.highlightElement(codeBlock);
                
                // 显示模态框
                const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
                modal.show();
            } catch (e) {
                alert('解析JSON失败: ' + e.message);
            }
        });
    });
    
    // 复制功能
    document.getElementById('copyDetail').addEventListener('click', function() {
        const content = document.getElementById('detailContent').textContent;
        navigator.clipboard.writeText(content).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check"></i> 已复制';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
    </script>
</body>
</html>