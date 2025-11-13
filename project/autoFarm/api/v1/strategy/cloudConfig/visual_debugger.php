<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backdoor/includes/functions.php';


// 检查登录状态
session_start();

// 判断是否登录
if (isUserLoggedIn()){
    if (!isSessionUsername('boy') && !isSessionUsername('girl')){
        redirectWithStatus(403, "您的账号 ". $_SESSION['username'] ." 权限不足，无法查看此页");
        exit();
    };
} else {
     redirectWithStatus(401, "您还未登录 ");
}



$jsonFile = __DIR__ . '/cloudConfigStatus.json';
$configRules = [];

if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $configRules = json_decode($jsonContent, true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <title>点击点可视化调试器</title>
  <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    #container {
      position: relative;
      width: 1376px;
      height: 807px;
      border: 1px solid #ccc;
    }
    #game-image {
      width: 100%;
      height: 100%;
      display: block;
    }
    .point-wrapper {
      position: absolute;
      z-index: 10;
      padding: 0;
      margin: 0;
      transform: translate(-50%, -50%);
    }

    .click-marker {
      position: absolute;
      left: 0;
      top: 0;
      width: 16px;
      height: 16px;
      background: radial-gradient(circle at center, #ff4d4d, #b30000);
      border: 2px solid #fff;
      border-radius: 50%;
      opacity: 0.9;
      transform: translate(-50%, -50%);
      cursor: pointer;
      z-index: 5;
      box-shadow: 0 0 8px 2px rgba(255, 77, 77, 0.7);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .click-marker:hover {
      transform: translate(-50%, -50%) scale(1.4);
      box-shadow: 0 0 12px 4px rgba(255, 77, 77, 1);
      opacity: 1;
    }
    .point-control {
      display: none;
      position: absolute;
      top: 10px;
      left: 15px;
      background-color: rgba(0, 0, 0, 0.85);
      padding: 6px 10px;
      border-radius: 6px;
      white-space: nowrap;
      z-index: 20;
    }
    
    .point-wrapper:hover .point-control {
      display: block;
    }
    #json-output {
      white-space: pre;
      font-family: monospace;
      background: #111;
      color: #0f0;
      padding: 10px;
      margin-top: 10px;
      max-height: 300px;
      overflow-y: auto;
    }
  </style>
</head>
<body class="p-3 bg-dark text-light">
  <h3>点击点可视化调试器</h3>

  <div id="container">
    <img id="game-image" src="1748713223590.jpg" alt="Game Screenshot" />
  </div>

  <h5 class="mt-4">当前 JSON 规则数据：</h5>
  <div id="json-output" class="border rounded"></div>
  <button class="btn btn-success mt-3" onclick="saveConfig()">保存配置</button>

  <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
    let configRules = <?= json_encode($configRules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;
    const containerWidth = 1376;
    const containerHeight = 807;
    let lastClickTime = 0;
    const maxPoints = 100;

    function renderPoints() {
  $('#container .point-wrapper').remove();

  configRules.forEach((rule, index) => {
    if (rule.type === "image") return; // 跳过 type 为 image 的点

    const x = rule.click_x_ratio * containerWidth;
    const y = rule.click_y_ratio * containerHeight;

    const wrapper = $(`
      <div class="point-wrapper" style="left: ${x}px; top: ${y}px;">
        <div class="click-marker" data-index="${index}"></div>
        <div class="point-control text-light small">
          <div class="mb-1">点位 ${index + 1}</div>
          <button class="btn btn-sm btn-warning mb-1 edit-btn" data-index="${index}">修改</button>
          <button class="btn btn-sm btn-danger delete-btn" data-index="${index}">删除</button>
        </div>
      </div>
    `);
    $('#container').append(wrapper);
  });

  updateJSONOutput();
}


    function updateJSONOutput() {
      $('#json-output').text(JSON.stringify(configRules, null, 2));
    }

    function deletePoint(index) {
      configRules.splice(index, 1);
      renderPoints();
    }

    function editPoint(index) {
      const rule = configRules[index];
      const newX = prompt("请输入新的 click_x_ratio（0~1）：", rule.click_x_ratio);
      const newY = prompt("请输入新的 click_y_ratio（0~1）：", rule.click_y_ratio);
      if (newX !== null && newY !== null) {
        rule.click_x_ratio = Math.min(1, Math.max(0, parseFloat(newX)));
        rule.click_y_ratio = Math.min(1, Math.max(0, parseFloat(newY)));
        renderPoints();
      }
    }

    $('#container').on('click', function(e) {
      if ($(e.target).closest('.point-wrapper').length > 0) return;

      const now = Date.now();
      if (now - lastClickTime < 200) return; // 200ms节流
      lastClickTime = now;

      if (configRules.length >= maxPoints) {
        alert('最多只能添加 ' + maxPoints + ' 个点位');
        return;
      }

      const offset = $(this).offset();
      const clickX = e.pageX - offset.left;
      const clickY = e.pageY - offset.top;
      const ratioX = clickX / containerWidth;
      const ratioY = clickY / containerHeight;

      configRules.push({
        type: "window",
        click_x_ratio: parseFloat(ratioX.toFixed(6)),
        click_y_ratio: parseFloat(ratioY.toFixed(6)),
      });

      renderPoints();
    });

    // 事件绑定，只绑定一次
    $(document).on('click', '.edit-btn', function(e) {
      e.stopPropagation();
      const index = $(this).data('index');
      if (configRules[index].type === 'image') return; // 跳过
      editPoint(index);
    });

    $(document).on('click', '.delete-btn', function(e) {
      e.stopPropagation();
      const index = $(this).data('index');
      if (configRules[index].type === 'image') return; // 跳过
      deletePoint(index);
    });

    function saveConfig() {
      $.ajax({
        url: 'save_config.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(configRules),
        success: () => alert("保存成功！"),
        error: () => alert("保存失败！")
      });
    }

    $(document).ready(() => {
      renderPoints();
    });
  </script>

</body>
</html>
