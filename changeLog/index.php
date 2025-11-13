<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$versions = getAllScriptVersions();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新日志 | <?= htmlspecialchars(SITE_NAME) ?></title>
    
    <?php require_once __DIR__ .  '/../includes/header.php'; ?>
    
    <style>
        /* 修复时间线布局 */
        .timeline {
            position: relative;
            padding-left: 120px; /* 增加左侧空间 */
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 60px; /* 调整时间线位置 */
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            min-height: 80px; /* 确保最小高度 */
        }
        
        .timeline-date {
            position: absolute;
            left: -120px; /* 与padding-left一致 */
            top: 0;
            width: 100px;
            text-align: right;
            padding-right: 20px;
            color: #6c757d;
            font-size: 0.95em;
        }
        
        /* 最新版本日期特殊样式 */
        .timeline-item.latest .timeline-date {
            color: #0d6efd;
            font-weight: bold;
        }
        
        .timeline-content {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 3px solid rgba(255, 255, 255, 0.1);
            min-height: 80px; /* 与item高度一致 */
        }
        
        .timeline-item.latest .timeline-content {
            border-left-color: #0d6efd;
            background: rgba(13, 110, 253, 0.05);
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* 顶部对齐 */
            margin-bottom: 10px;
            flex-wrap: wrap; /* 允许换行 */
        }
        
        .timeline-header h3 {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        /* 响应式调整 */
        @media (max-width: 768px) {
            .timeline {
                padding-left: 90px;
            }
            
            .timeline::before {
                left: 45px;
            }
            
            .timeline-date {
                left: -90px;
                width: 80px;
                font-size: 0.85em;
            }
        }
        
        /* 下载禁用样式 */
        .download-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* 版本号与徽章间距 */
        .version-badge {
            margin-left: 8px;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <!-- 导航栏 -->
    <?php 
        require_once __DIR__ .  '/../includes/navbar.php';
    ?>
    <!--加载页面-->
    <?php require_once __DIR__ .  '/../includes/loading.php'; ?>

    <!-- 更新日志内容 -->
    <section class="py-5">
        <div class="container">
            <h1 class="text-center mb-5">更新日志</h1>
            
            <div class="timeline">
                <?php foreach ($versions as $version): ?>
                <div class="timeline-item <?= $version['is_latest'] ? 'latest' : '' ?>">
                    <div class="timeline-date">
                        <?= date('Y-m-d', strtotime($version['release_date'])) ?>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <div class="d-flex align-items-center">
                                <h3>v<?= htmlspecialchars($version['version']) ?></h3>
                                <?php if ($version['is_latest']): ?>
                                    <span class="badge bg-primary version-badge">最新版本</span>
                                <?php endif; ?>
                                <?php if ($version['download_disabled']): ?>
                                    <span class="badge bg-danger version-badge">下载已关闭</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="timeline-body">
                            <?php 
                            $changelogs = getChangelogsForScript($version['id']);
                            if (!empty($changelogs)): 
                            ?>
                            <ul class="mb-0">
                                <?php foreach ($changelogs as $log): ?>
                                <li><?= htmlspecialchars($log['description']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="text-muted mb-0">暂无详细更新内容</p>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-footer mt-3">
                            <?php if ($version['download_disabled']): ?>
                            <span class="btn btn-sm btn-outline-secondary download-disabled" title="该版本下载已禁用">
                                <i class="bi bi-download me-1"></i>下载不可用
                            </span>
                            <?php else: ?>
                            <a href="<?= htmlspecialchars($version['download_url']) ?>" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-download me-1"></i>下载此版本
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ .  '/../includes/footer.php'; ?>
    
</body>
</html>