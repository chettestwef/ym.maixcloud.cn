<!-- includes/navbar.php -->
<!-- includes/navbar.php -->
<!-- includes/navbar.php -->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-opacity-75 fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-stars"></i> <?= htmlspecialchars(SITE_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/index.php') !== false || rtrim($_SERVER['REQUEST_URI'], '/') === '/' ? 'active' : '' ?>" 
                       href="/"><i class="bi bi-house-heart"></i> 主页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '#download') !== false ? 'active' : '' ?>" 
                       href="/index.php#download"><i class="bi bi-cloud-arrow-down"></i> 下载</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/changeLog') !== false ? 'active' : '' ?>" 
                       href="/changeLog/"><i class="bi bi-file-earmark-plus"></i> 更新日志</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/authQuery') !== false ? 'active' : '' ?>" 
                       href="/authQuery/"><i class="bi bi-person-check"></i> 授权查询</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" target="_blank"
                       href="/serverStatus/"><i class="bi bi-hdd-network"></i> 节点服务器信息</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" target="_blank"
                       href="/goto/?url=https%3A%2F%2Fstatus.ymzxs.fun%2F"><i class="bi bi-activity"></i> 服务状态</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" target="_blank"
                       href="/goto/?url=<?= urlencode('https://hcno6vu3elgc.feishu.cn/wiki/QItCw6VTGihX9dkjaxic7wDHnxh?from=from_copylink') ?>"><i class="bi bi-patch-question"></i> 使用教程</a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- 模态框 -->
<div class="modal fade" id="dangerModal" tabindex="-1" aria-labelledby="dangerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h1 class="modal-title fs-5" id="dangerModalLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>服务停止公告
        </h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-heading">重要通知！</h4>
          <p class="mb-0">我们的服务将于 <strong>2024年12月31日 23:59</strong> 正式停止。</p>
        </div>
        
        <p>此次停止服务涉及以下方面：</p>
        <ul>
          <li>所有在线功能将无法使用</li>
          <li>用户数据将按政策进行清理</li>
          <li>相关API接口将停止响应</li>
        </ul>
        
        <p class="text-muted small">如有任何疑问，请及时联系客服团队。</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">了解更多</button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">确认知悉</button>
      </div>
    </div>
  </div>
</div>

<!-- 触发按钮 (可以放在页面任何位置) -->
<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#dangerModal">
  查看服务停止公告
</button>

<!-- includes/navbar.php -->
<!-- includes/navbar.php -->
<!-- includes/navbar.php -->