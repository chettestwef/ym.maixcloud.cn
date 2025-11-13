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


<!-- 危险操作模态框 - 自动弹出 -->
<div class="modal fade" id="dangerModal" tabindex="-1" aria-labelledby="dangerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h1 class="modal-title fs-5" id="dangerModalLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>服务停止公告
        </h1>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-heading">重要通知！</h4>
          <p class="mb-0">我们的服务将于 <strong class="fs-6">2025年12月21日 07:34:00</strong> 正式永久停止。</p>
        </div>
        
        <p class="fw-bold" style="color: black;">此次为永久性停止服务：</p>
        <ul style="color: black;">
          <li>我们将对各位星宝负责到底，结束服务时间为最后一个星宝的卡密到期时间</li>
          <li>网站将无法访问，全部服务将被停止</li>
          <li>各位星宝的数据将被永久清理</li>
          <li>不再提供任何技术支持</li>
          <li>脚本的所有功能将离线并且无法登陆和使用</li>
        </ul>
        
        <div class="bg-light p-3 rounded mt-3">
          <p class="mb-2"><strong>感谢您一直以来的支持！</strong></p>
          <p class="mb-0 text-muted small">山水有相逢，未来或有缘再见。</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">确认并告别</button>
      </div>
    </div>
  </div>
</div>

<script>
// 自动显示模态框
document.addEventListener('DOMContentLoaded', function() {
    const dangerModal = new bootstrap.Modal(document.getElementById('dangerModal'));
    dangerModal.show();
});
</script>

<!-- includes/navbar.php -->
<!-- includes/navbar.php -->
<!-- includes/navbar.php -->