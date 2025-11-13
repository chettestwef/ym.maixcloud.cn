<?php
// serverStatus/index.php
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>服务器状态检测 | <?= SITE_NAME ?></title>
    <?php require_once '../includes/header.php'; ?>
    <style>
        body {
            /*background: linear-gradient(135deg, #1f1c2c, #928dab);*/
            /*background-size: 400% 400%;*/
            animation: gradientMove 15s ease infinite;
            color: #fff;
            /*font-family: "Segoe UI", sans-serif;*/
        }
    
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    
        .status-card {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            min-height: 180px;
            transition: transform .3s ease, box-shadow .3s ease;
        }
        .status-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.5);
        }
        .status-icon {
            font-size: 2rem;
            margin-right: 10px;
        }
        .status-ok {
            color: #00ff99;
            font-weight: bold;
        }
        .status-fail {
            color: #ff4444;
            font-weight: bold;
        }
    </style>

</head>
<body class="bg-dark text-light">

    <?php require_once '../includes/navbar.php'; ?>
    <!--加载页面-->
    <?php  require_once '../includes/loading.php'; ?>
    
    <section class="hero d-flex align-items-center min-vh-100">
        <div class="container position-relative z-index-1">
            <div class="row justify-content-center">
                <div class="container py-5">
                    <h1 class="text-center mb-5">服务器后端API节点状态</h1>
                    <div class="row g-4" id="statusContainer">
                        <!-- 动态插入卡片 -->
                    </div>
                </div>
                
                <script>
                const domains = {
                    "API 主用节点": "https://api.ymzxs.fun",
                    "API CF1 节点": "https://api-cf1.ymzxs.fun",
                    "API CF2 节点": "https://api-cf2.ymzxs.fun",
                    "API CF3 节点": "https://api-cf3.ymzxs.fun",
                    "API CF4 节点": "https://api-cf4.ymzxs.fun",
                    "API CF5 节点": "https://api-cf5.ymzxs.fun",
                    "API 备用节点": "https://api-back.ymzxs.fun"
                };
                
                const container = document.getElementById("statusContainer");
                
                function createCard(name) {
                    const col = document.createElement("div");
                    col.className = "col-md-4";
                    col.innerHTML = `
                        <div class="status-card h-100">
                            <h5 class="mb-3"><i class="bi bi-hdd-network status-icon"></i>${name}</h5>
                            <div class="status-body loading">检测中...</div>
                        </div>
                    `;
                    container.appendChild(col);
                    return col.querySelector(".status-body");
                }
                
                async function checkServer(name, url, box) {
                    try {
                        const res = await fetch(url + "/api/v1/connectionTest/", { method: "GET" });
                        if (!res.ok) throw new Error("网络错误 " + res.status);
                        const data = await res.json();
                
                        if (data.success) {
                            box.innerHTML = `
                                <div><i class="bi bi-check-circle-fill status-ok"></i> 状态: <span class="status-ok">${data.status}</span></div>
                                <div><i class="bi bi-server text-info"></i> 服务器: <strong>${data.server}</strong></div>
                                <div><i class="bi bi-database-check text-warning"></i> 数据库: ${data.database}</div>
                                <div><i class="bi bi-speedometer2 text-primary"></i> 负载: ${data.load}</div>
                                <div><i class="bi bi-globe2 text-light"></i> 请求IP: ${data.request_ip || "未知"}</div>
                                <div><i class="bi bi-clock text-light"></i> 时间: ${data.timestamp}</div>
                            `;
                            
                        } else if (data.success == true && data.service) {
                            box.innerHTML = `
                                <div><i class="bi bi-check-circle-fill status-fail"></i> 状态: <span class="status-fail">${data.status}</span></div>
                                <div><i class="bi bi-server text-info"></i> 服务器: <strong>${data.server}</strong></div>
                                <div><i class="bi bi-database-check text-warning"></i> 数据库: <span class="status-fail">${data.database}</span></div>
                                <div><i class="bi bi-speedometer2 text-primary"></i> 负载: ${data.load}</div>
                                <div><i class="bi bi-globe2 text-light"></i> 请求IP: ${data.request_ip || "未知"}</div>
                                <div><i class="bi bi-clock text-light"></i> 时间: ${data.timestamp}</div>
                            `;
                        } else {
                            box.innerHTML = `
                                <div><i class="bi bi-x-circle-fill status-fail"></i> 状态: <span class="status-fail">返回异常</span></div>
                            `;
                        }
                    } catch (e) {
                        box.innerHTML = `
                            <div><i class="bi bi-x-circle-fill status-fail"></i> 状态: <span class="status-fail">请求失败</span></div>
                            <div class="text-danger">${e.message}</div>
                        `;
                    }
                }
                
                window.addEventListener("DOMContentLoaded", () => {
                    for (const [name, url] of Object.entries(domains)) {
                        const box = createCard(name);
                        checkServer(name, url, box);
                    }
                });
                </script>
            </div>
        </div>
    </section>
    
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
