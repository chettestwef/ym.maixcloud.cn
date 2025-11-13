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
$cardCode = $_GET['card_code'] ?? '';
$machineCode = $_GET['machine_code'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// 构建查询条件
$conditions = [];
$params = [];

if (!empty($cardCode)) {
    $conditions[] = "card_code LIKE ?";
    $params[] = "%$cardCode%";
}

if (!empty($machineCode)) {
    $conditions[] = "machine_code LIKE ?";
    $params[] = "%$machineCode%";
}

if (!empty($dateFrom)) {
    $conditions[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $conditions[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取总记录数
$totalQuery = "SELECT COUNT(*) FROM error_reports $where";
$totalStmt = $db->prepare($totalQuery);
$totalStmt->execute($params);
$totalLogs = $totalStmt->fetchColumn();

// 分页设置
$perPage = 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// 获取日志数据
$query = "SELECT * FROM error_reports $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>错误日志查看器</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <style>
        .table-responsive { max-height: 70vh; overflow-y: auto; }
        pre { max-height: 60vh; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; background: #f8f9fa; padding: 1rem; border-radius: 0.25rem; }
        .hljs { background: transparent !important; padding: 0 !important; }
        .view-detail { min-width: 60px; }
        .log-table th { position: sticky; top: 0; background: white; z-index: 10; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4">错误日志查看器</h2>
    

    <!-- 筛选表单 -->
    <div class="card mb-4">
        <div class="card-header">筛选条件</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">卡密</label>
                    <input type="text" name="card_code" class="form-control" value="<?= htmlspecialchars($cardCode) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">机器码</label>
                    <input type="text" name="machine_code" class="form-control" value="<?= htmlspecialchars($machineCode) ?>">
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
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> 筛选</button>
                    <a href="error_logs.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> 重置</a>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mb-4">
        共找到 <?= number_format($totalLogs) ?> 条日志记录，当前显示 <?= count($logs) ?> 条
    </div>

    <!-- 日志表格 -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 log-table">
                    <thead>
                        <tr>
                            <th width="120">时间</th>
                            <th width="120">卡密</th>
                            <th width="120">机器码</th>
                            <th>错误内容</th>
                            <th width="100">额外信息</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                            <td><?= htmlspecialchars($log['card_code']) ?></td>
                            <td><?= htmlspecialchars($log['machine_code']) ?></td>
                            <td><?= htmlspecialchars($log['error_text']) ?></td>
                            <td>
                                <?php if ($log['extra']): ?>
                                <button class="btn btn-sm btn-outline-info view-detail" 
                                        data-data="<?= htmlspecialchars($log['extra']) ?>"
                                        data-title="额外信息">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 详情模态框 -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">详情</h5>
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
<script src="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script>
hljs.highlightAll();

// 查看详情
document.querySelectorAll('.view-detail').forEach(btn => {
    btn.addEventListener('click', function() {
        const rawData = this.getAttribute('data-data');
        const title = this.getAttribute('data-title') || '详情';
        document.querySelector('#logDetailModal .modal-title').textContent = title;
        try {
            const data = JSON.parse(rawData);
            document.getElementById('detailContent').textContent = JSON.stringify(data, null, 2);
        } catch(e) {
            document.getElementById('detailContent').textContent = rawData;
        }
        hljs.highlightElement(document.getElementById('detailContent'));
        new bootstrap.Modal(document.getElementById('logDetailModal')).show();
    });
});

// 复制
document.getElementById('copyDetail').addEventListener('click', function() {
    const content = document.getElementById('detailContent').textContent;
    navigator.clipboard.writeText(content).then(() => {
        const original = this.innerHTML;
        this.innerHTML = '<i class="bi bi-check"></i> 已复制';
        setTimeout(() => { this.innerHTML = original; }, 2000);
    });
});
</script>
</body>
</html>
