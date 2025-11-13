<?php
// 安全检测
if (basename($_SERVER['PHP_SELF']) == '_sidebar.php') {
    header('HTTP/1.0 403 Forbidden');
    logSystemEvent(
        'warning',
        '禁止直接访问', 
        $cardCode,
        $machineCode,
    );
        
    exit('禁止直接访问');
}

// 安全获取待处理数量
$pendingAuthCount = 0;
if (function_exists('getPendingAuthCount')) {
    $pendingAuthCount = getPendingAuthCount();
}
?>
<!-- 侧边栏导航 - 使用Offcanvas实现响应式 -->
<div class="offcanvas-md offcanvas-start bg-dark" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header bg-dark">
        <!-- LOGO部分 -->
        <div class="d-flex align-items-center">
            <i class="bi bi-stars fs-3 text-white me-2"></i>
            <h5 class="offcanvas-title text-white mb-0" id="sidebarLabel">
                <?= htmlspecialchars(SITE_NAME) ?>
            </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body d-flex flex-column px-0">
        <!-- 导航菜单 -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" 
                   href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>控制面板
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'manage-auth.php' ? 'active' : '' ?>" 
                   href="manage-auth.php">
                    <i class="bi bi-shield-lock me-2"></i>授权管理
                    <?php if ($pendingAuthCount > 0): ?>
                    <span class="badge bg-danger float-end"><?= $pendingAuthCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'manage-versions.php' ? 'active' : '' ?>" 
                   href="manage-versions.php">
                    <i class="bi bi-collection me-2"></i>版本管理
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'log_viewer.php' ? 'active' : '' ?>" 
                   href="log_viewer.php">
                    <i class="bi bi-collection me-2"></i>日志管理
                </a>
            </li>
            
            
            <li class="nav-item">
                <a class="nav-link text-white px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'log_viewer.php' ? 'active' : '' ?>" 
                   href="error_log_viewer.php">
                    <i class="bi bi-collection me-2"></i>上报错误日志管理
                </a>
            </li>
            
            
            
        </ul>
        
        <!-- 底部用户菜单 -->
        <div class="mt-auto px-3 pb-3">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
                   id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2"></i>
                    <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? '管理员') ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>个人资料</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出登录</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    /* 侧边栏样式 */
    .offcanvas-md {
        width: 280px;
        background-color: #212529;
        background-image: linear-gradient(180deg, #212529 10%, #343a40 100%);
    }
    
    .offcanvas-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .nav-link {
        border-radius: 0;
        margin: 0.1rem 0;
        transition: all 0.3s;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link.active {
        background-color: rgba(13, 110, 253, 0.9);
        border-left: 3px solid white;
    }
    
    @media (min-width: 768px) {
        .offcanvas-md {
            transform: none;
            visibility: visible !important;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .nav-link {
            border-radius: 0.25rem;
            margin: 0.25rem 0.5rem;
        }
    }
</style>