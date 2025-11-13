<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backdoor/includes/functions.php';

session_start();

if (isUserLoggedIn()) {
    if (!isSessionUsername('boy') && !isSessionUsername('girl')) {
        redirectWithStatus(403, "您的账号 " . $_SESSION['username'] . " 权限不足，无法查看此页");
        exit();
    }
} else {
    redirectWithStatus(401, "您还未登录");
    exit();
}

$auth_file = __DIR__ . '/auth_data.json';
$log_file = __DIR__ . '/report.log';

$auth_data = file_exists($auth_file) ? json_decode(file_get_contents($auth_file), true) : [];
$log_lines = file_exists($log_file) ? file($log_file) : [];

date_default_timezone_set('Asia/Shanghai');

function is_online($report)
{
    return isset($report['timestamp']) && (time() - intval($report['timestamp']) <= 30);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"/>
    <title>用户状态监控</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        body {
            background-color: #121212;
            color: #f8f9fa;
        }

        .online-badge {
            background-color: #28a745;
        }

        .offline-badge {
            background-color: #6c757d;
        }

        .search-box {
            max-width: 300px;
            margin-bottom: 1rem;
        }

        .table-dark th,
        .table-dark td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container my-4">
    <h2 class="mb-4 text-center">用户状态面板</h2>

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 搜索卡密或IP..."/>
        <div>
            <span class="badge bg-info text-dark me-2">总用户数: <?= count($auth_data) ?></span>
            <span class="badge bg-success" id="online-count">在线: 0</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover text-center align-middle" id="userTable">
            <thead>
            <tr>
                <th>卡密</th>
                <th>状态</th>
                <th>机器码</th>
                <th>IP</th>
                <th>最后登录</th>
                <th>最近上报时间</th>
                <th>系统信息</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <!-- 通过 JS 填充 -->
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById("searchInput");

    searchInput.addEventListener("input", function () {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll("#userTable tbody tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

        const translated = {
        // 原有字段...
        'os': '操作系统',
        'name': '名称',
        'version': '版本',
        'platform': '平台',
        'cpu_info': 'CPU信息',
        'physical_cores': '物理核心数',
        'total_cores': '总核心数',
        'max_freq_mhz': '最大频率(MHz)',
        'current_freq_mhz': '当前频率(MHz)',
        'cpu_percent': 'CPU使用率(%)',
        'processor_name': '处理器名称',
        'gpu_info': 'GPU信息',
        'name': '名称',
        'load_percent': '负载(%)',
        'memory_total_mb': '显存总量(MB)',
        'memory_used_mb': '已用显存(MB)',
        'temperature_c': '温度(℃)',
        'disk_serials_hashed': '硬盘序列(哈希)',
        'network_info': '网络信息',
        'local_ip': '本地IP',
        'mac_address_hashed': 'MAC地址(哈希)',
        'installed_software_top': '主要软件',
        'ram_detail_raw': '内存详细信息'
    };


        function renderSystemInfo(obj) {
        if (typeof obj !== 'object' || obj === null) {
            return String(obj);
        }
        if (Array.isArray(obj)) {
            // 多行数组支持优化：显示为 <ul>
            if (obj.length > 0 && typeof obj[0] === 'string') {
                return `<ul class="mb-0 text-start small">` +
                    obj.map(item => `<li>${item}</li>`).join('') +
                    `</ul>`;
            } else {
                return obj.map(renderSystemInfo).join(', ');
            }
        }
        let html = '<ul class="mb-0 text-start small">';
        for (const [key, val] of Object.entries(obj)) {
            const label = translated[key] || key;
            html += `<li><strong>${label}:</strong> ${renderSystemInfo(val)}</li>`;
        }
        html += '</ul>';
        return html;
    }


    async function refreshData() {
        try {
            const res = await fetch("/project/autoFarm/api/v1/status_api.php");
            if (!res.ok) throw new Error('网络错误');
            const json = await res.json();
            if (!json.success) throw new Error(json.message || '获取数据失败');

            document.getElementById("online-count").innerText = '在线: ' + json.online_count;

            const tbody = document.querySelector("#userTable tbody");
            tbody.innerHTML = '';

            for (const [card, info] of Object.entries(json.data)) {
                const online = info.online;
                const statusClass = online ? 'online-badge' : 'offline-badge';
                const statusText = online ? '在线' : '离线';
                const sysinfo = info.system_info || {};

                let sysinfoHtml = '无';
                if (Object.keys(sysinfo).length > 0) {
                    sysinfoHtml = renderSystemInfo(sysinfo);
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${card}</td>
                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                    <td>${info.machine_code || '-'}</td>
                    <td>${info.last_ip || '-'}</td>
                    <td>${info.last_login_time || '-'}</td>
                    <td>${info.last_report_time || '-'}</td>
                    <td>${sysinfoHtml}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editUser('${card}')">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser('${card}')">删除</button>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        } catch (err) {
            console.error(err);
            // 你也可以在页面显示错误提示
        }
    }

    function editUser(card) {
        alert("点击了编辑按钮: " + card);
        // TODO: 弹出模态框，加载用户信息并保存
    }

    function deleteUser(card) {
        if (!confirm("确认要删除用户 " + card + " 吗？")) return;
        fetch("/delete_user.php", {
            method: "POST",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({card})
        }).then(res => res.json())
            .then(json => {
                if (json.success) {
                    refreshData();
                } else {
                    alert("删除失败: " + json.message);
                }
            }).catch(() => alert("请求失败"));
    }

    refreshData();
    setInterval(refreshData, 3000); // 3秒刷新一次
</script>
</body>
</html>
