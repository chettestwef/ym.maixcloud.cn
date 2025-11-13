<?php
// 获取目标地址参数
$target = $_GET['url'] ?? '/';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>正在跳转...</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php require_once __DIR__ .  '/../includes/header.php'; ?>

  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .loading-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      animation: spin 1.5s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .fade-text {
      opacity: 0.85;
    }
  </style>
</head>
<body>
<!--加载页面-->
<?php require_once __DIR__ .  '/../includes/loading.php'; ?>

  <div class="text-center">
    <div class="loading-icon text-info">
      <i class="bi bi-arrow-repeat"></i>
    </div>
    <h2>正在加载中，请稍候...</h2>
    <p class="fade-text">如果未自动跳转，请<a href="<?= htmlspecialchars($target) ?>" class="text-decoration-underline text-info">点击这里</a></p>
  </div>

  <script>
    // 页面加载完成后进行跳转
    window.onload = function () {
      setTimeout(function () {
        window.location.href = <?= json_encode($target) ?>;
      }, 500); // 延迟 500ms 更自然
    };
  </script>
</body>
</html>
