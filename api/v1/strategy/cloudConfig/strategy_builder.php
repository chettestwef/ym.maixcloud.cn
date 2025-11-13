<?php
require_once __DIR__ . '/../../../../includes/config.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';


// 判断是否登录
if (isUserLoggedIn()){
    if (!isSessionUsername('boy') && !isSessionUsername('girl')){
        redirectWithStatus(403, "您的账号 ". $_SESSION['username'] ." 权限不足，无法查看此页");
        exit();
    };
} else {
     redirectWithStatus(401, "您还未登录 ");
}



// 读取当前目录下的 cloudConfigStatus.json 文件
$jsonFile = __DIR__ . '/cloudConfigStatus.json';
$jsonData = file_exists($jsonFile) ? file_get_contents($jsonFile) : '[]';
?>
<!DOCTYPE html>
<html lang="zh-CN" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>策略生成器</title>
  <!-- 引入 Bootstrap 5 的 CSS 和 JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      padding: 20px;
    }
    .strategy-item {
      margin-bottom: 15px;
      padding: 15px;
      border: 1px solid #444;
      border-radius: 5px;
      background-color: #2c2c2c;
    }
    .strategy-item input {
      margin-right: 10px;
    }
    .strategy-item .btn {
      margin-top: 10px;
    }
    textarea {
      width: 100%;
      height: 300px;
      margin-top: 20px;
      font-family: monospace;
      background-color: #1e1e1e;
      color: #cfcfcf;
      padding: 10px;
      border: none;
      border-radius: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="mb-4">策略生成器</h2>
    <div id="strategy-list"></div>
    <button class="btn btn-primary mt-3" onclick="addStrategy()">添加策略</button>
    <button class="btn btn-success mt-3 ms-2" onclick="saveStrategies()">保存策略</button>
    <h3 class="mt-5">JSON 输出</h3>
    <textarea id="output" readonly></textarea>
  </div>

  <script>
  let strategies = <?php echo $jsonData; ?>;

  function renderStrategies() {
    const list = document.getElementById('strategy-list');
    list.innerHTML = '';
    strategies.forEach((strategy, index) => {
      const item = document.createElement('div');
      item.className = 'strategy-item';

      // 类型选择
      const typeSelect = document.createElement('select');
      typeSelect.className = 'form-select mb-2';
      typeSelect.innerHTML = `
        <option value="image">image</option>
        <option value="window">window</option>
        <option value="check_then_click">check_then_click</option>`;
      typeSelect.value = strategy.type;
      typeSelect.onchange = () => {
        strategy.type = typeSelect.value;
        renderStrategies();
      };
      item.appendChild(typeSelect);

      // 根据类型渲染输入框
      if (strategy.type === 'image') {
        const valueInput = document.createElement('input');
        valueInput.type = 'text';
        valueInput.className = 'form-control mb-2';
        valueInput.placeholder = 'value (路径)';
        valueInput.value = strategy.value || '';
        valueInput.oninput = () => strategy.value = valueInput.value;
        item.appendChild(valueInput);

        const thresholdInput = document.createElement('input');
        thresholdInput.type = 'number';
        thresholdInput.className = 'form-control mb-2';
        thresholdInput.placeholder = 'threshold';
        thresholdInput.step = '0.01';
        thresholdInput.value = strategy.threshold ?? 0.85;
        thresholdInput.oninput = () => strategy.threshold = parseFloat(thresholdInput.value);
        item.appendChild(thresholdInput);

      } else if (strategy.type === 'window') {
        const xInput = document.createElement('input');
        xInput.type = 'number';
        xInput.className = 'form-control mb-2';
        xInput.placeholder = 'click_x_ratio';
        xInput.step = '0.01';
        xInput.value = strategy.click_x_ratio ?? 0.5;
        xInput.oninput = () => strategy.click_x_ratio = parseFloat(xInput.value);
        item.appendChild(xInput);

        const yInput = document.createElement('input');
        yInput.type = 'number';
        yInput.className = 'form-control mb-2';
        yInput.placeholder = 'click_y_ratio';
        yInput.step = '0.01';
        yInput.value = strategy.click_y_ratio ?? 0.5;
        yInput.oninput = () => strategy.click_y_ratio = parseFloat(yInput.value);
        item.appendChild(yInput);

      } else if (strategy.type === 'check_then_click') {
        const valueInput = document.createElement('input');
        valueInput.type = 'text';
        valueInput.className = 'form-control mb-2';
        valueInput.placeholder = 'value (第一张图路径)';
        valueInput.value = strategy.value || '';
        valueInput.oninput = () => strategy.value = valueInput.value;
        item.appendChild(valueInput);

        const thresholdInput = document.createElement('input');
        thresholdInput.type = 'number';
        thresholdInput.className = 'form-control mb-2';
        thresholdInput.placeholder = 'threshold (第一张图置信度)';
        thresholdInput.step = '0.01';
        thresholdInput.value = strategy.threshold ?? 0.85;
        thresholdInput.oninput = () => strategy.threshold = parseFloat(thresholdInput.value);
        item.appendChild(thresholdInput);

        const thenValueInput = document.createElement('input');
        thenValueInput.type = 'text';
        thenValueInput.className = 'form-control mb-2';
        thenValueInput.placeholder = 'then_click_value (第二张图路径)';
        thenValueInput.value = strategy.then_click_value || '';
        thenValueInput.oninput = () => strategy.then_click_value = thenValueInput.value;
        item.appendChild(thenValueInput);

        const thenThresholdInput = document.createElement('input');
        thenThresholdInput.type = 'number';
        thenThresholdInput.className = 'form-control mb-2';
        thenThresholdInput.placeholder = 'then_threshold (第二张图置信度)';
        thenThresholdInput.step = '0.01';
        thenThresholdInput.value = strategy.then_threshold ?? 0.85;
        thenThresholdInput.oninput = () => strategy.then_threshold = parseFloat(thenThresholdInput.value);
        item.appendChild(thenThresholdInput);
      }

      // 删除按钮
      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'btn btn-danger';
      deleteBtn.textContent = '删除';
      deleteBtn.onclick = () => {
        strategies.splice(index, 1);
        renderStrategies();
      };
      item.appendChild(deleteBtn);

      list.appendChild(item);
    });

    document.getElementById('output').value = JSON.stringify(strategies, null, 2);
  }

  function addStrategy() {
    strategies.push({ type: 'image', value: '', threshold: 0.85 });
    renderStrategies();
  }

  renderStrategies();
  
  function saveStrategies() {
  fetch('save_strategy.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(strategies)
  })
  .then(response => {
    if (!response.ok) throw new Error('网络错误');
    return response.json();
  })
  .then(result => {
    if (result.success) {
      alert('保存成功！');
    } else {
      alert('保存失败: ' + result.message);
    }
  })
  .catch(error => {
    alert('请求失败: ' + error.message);
  });
}
</script>

</body>
</html>
