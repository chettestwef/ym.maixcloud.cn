<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 最新版本信息
$latestVersion = getLatestScriptVersion();
$totalVersions = getScriptVersionCount();

// 总计数
$totalApi      = getCounter('total_api');
$totalWindows  = getCounter('windows_api');
$totalAndroid  = getCounter('android_api');
$totalWebsite  = getCounter('website_visit');

// -------------------- 今天每5分钟统计（从今天0点开始） --------------------
function get5MinuteCountersToday($name) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            CONCAT(
                DATE_FORMAT(stat_time, '%H:'),
                LPAD(FLOOR(MINUTE(stat_time) / 5) * 5, 2, '0')
            ) as minute_label,
            SUM(counter_value) as total_value
        FROM statistics_minute
        WHERE counter_name = :name 
          AND stat_time >= DATE(NOW())
        GROUP BY HOUR(stat_time), FLOOR(MINUTE(stat_time) / 5)
        ORDER BY stat_time ASC
    ");
    $stmt->execute([':name'=>$name]);
    $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 生成完整的5分钟间隔标签
    $minutes = [];
    $start = strtotime(date('Y-m-d 00:00:00'));
    $end   = time(); // 当前本地时间
    for ($t = $start; $t <= $end; $t += 300) {
        $key = date('H:i', $t);
        $minutes[$key] = $result[$key] ?? 0;
    }
    return $minutes;
}

// -------------------- 过去7天每天每小时平均 --------------------
function getHourlyAverageLast7Days($name) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            DATE(stat_time) AS day,
            HOUR(stat_time) AS hour,
            AVG(counter_value) AS avg_value
        FROM statistics_hour
        WHERE counter_name = :name
          AND stat_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day, hour
        ORDER BY day ASC, hour ASC
    ");
    $stmt->execute([':name'=>$name]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 初始化 7天*24小时
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} day"));
        for ($h = 0; $h < 24; $h++) {
            $data[$day][$h] = 0;
        }
    }

    foreach ($rows as $r) {
        $day  = $r['day'];
        $hour = intval($r['hour']);
        $data[$day][$hour] = round(floatval($r['avg_value']), 1);
    }

    return $data;
}

// -------------------- 最近24小时每小时总请求 --------------------
function getHourlyTotalsLast24Hours($name) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(stat_time, '%Y-%m-%d %H:00:00') AS hour_label,
            SUM(counter_value) AS total_requests
        FROM statistics_hour
        WHERE counter_name = :name
          AND stat_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY hour_label
        ORDER BY hour_label
    ");
    $stmt->execute([':name'=>$name]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 生成完整的最近24小时序列
    $data = [];
    $end = strtotime(date('Y-m-d H:00:00')); // 当前整点
    $start = $end - 23 * 3600;
    for ($t = $start; $t <= $end; $t += 3600) {
        $label = date('Y-m-d H:00:00', $t);
        $data[$label] = isset($rows[$label]) ? intval($rows[$label]) : 0;
    }

    return $data;
}


// -------------------- 获取数据 --------------------
$totalData5min   = get5MinuteCountersToday('total_api');
$windowsData5min = get5MinuteCountersToday('windows_api');
$androidData5min = get5MinuteCountersToday('android_api');
$websiteData5min = get5MinuteCountersToday('website_visit');

$totalAvg7d     = getHourlyAverageLast7Days('total_api');
$windowsAvg7d   = getHourlyAverageLast7Days('windows_api');
$androidAvg7d   = getHourlyAverageLast7Days('android_api');
$websiteAvg7d   = getHourlyAverageLast7Days('website_visit');

$hourlyTotals = getHourlyTotalsLast24Hours('total_api');

// -------------------- 图表标签 --------------------
$minuteLabels5min = [];
for ($i = 0; $i < 288; $i++) $minuteLabels5min[] = date('H:i', strtotime("00:00") + $i*300);

$hourLabels = [];
for ($h = 0; $h < 24; $h++) $hourLabels[] = sprintf('%02d:00', $h);


// 过去7天标签 (周一 00:00 ~ 周日 23:00)
$labels7d = [];
foreach ($totalAvg7d as $day => $hours) {
    $weekday = ['周日','周一','周二','周三','周四','周五','周六'][date('w', strtotime($day))];
    foreach ($hours as $h => $val) {
        $labels7d[] = $weekday . ' ' . sprintf('%02d:00', $h);
    }
}

// 最近24小时标签
$labels24h = array_keys($hourlyTotals);



?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台管理 - <?= SITE_NAME ?></title>
<link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.bootcdn.net/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
body { background-color: #ffffff; color: #212529; }
.card { background-color: #ffffff; color: #212529; border: 1px solid #dee2e6; }
.progress-bar-gradient { background: linear-gradient(90deg, #007bff, #00c6ff); height: 25px; border-radius: 5px; }
.hour-tick { font-weight: bold; background-color: rgba(0,0,0,0.1); }
</style>
</head>
<body>
<button class="btn btn-primary d-md-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
    <i class="bi bi-list"></i> 菜单
</button>
<div class="container-fluid">
    <div class="row" style="flex-wrap: nowrap;">
        <?php include '_sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h2 class="mb-4">后台首页</h2>

            <div class="row mb-4">
                <!-- 最新版本 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">最新版本</h5>
                            <?php if ($latestVersion): ?>
                                <p class="card-text">
                                    <strong>v<?= htmlspecialchars($latestVersion['version']) ?></strong><br>
                                    发布于: <?= date('Y-m-d', strtotime($latestVersion['release_date'])) ?>
                                </p>
                            <?php else: ?>
                                <p class="card-text">暂无版本信息</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 总统计 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">总统计</h5>
                            <p class="card-text mb-2">总版本数: <strong><?= $totalVersions ?></strong></p>
                            <p class="card-text mb-2">总API调用: <strong><?= $totalApi ?></strong></p>
                            <p class="card-text mb-2">Windows API: <strong><?= $totalWindows ?></strong></p>
                            <p class="card-text mb-2">Android API: <strong><?= $totalAndroid ?></strong></p>
                            <p class="card-text mb-2">网站访问: <strong><?= $totalWebsite ?></strong></p>
                        </div>
                    </div>
                </div>

                <!-- 进度条 -->
                <div class="col-md-12 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">API调用进度</h5>
                            <div class="progress mb-2">
                                <div class="progress-bar-gradient" role="progressbar" style="width: <?= min($totalApi/1000,100) ?>%;" aria-valuenow="<?= $totalApi ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small><?= $totalApi ?> 次调用</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 今天每5分钟折线图 -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">最近24小时API调用趋势（5分钟粒度）</h5>
                            <canvas id="apiChart5min" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 最近24小时每小时总请求趋势 -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">最近24小时总请求趋势</h5>
                            <canvas id="totals24h" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>


            <!-- 过去7天每小时平均折线图 -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">过去7天每小时API平均调用趋势</h5>
                            <canvas id="avgChart7d" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

<!-- 每小时总请求次数表格 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">最近24小时每小时总请求次数</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <?php 
                                $end   = strtotime(date('Y-m-d H:00:00')); // 当前整点
                                $start = $end - 23 * 3600;
                                $nowHour = intval(date('H')); // 当前小时
                                for ($t = $start; $t <= $end; $t += 3600): 
                                    $hour = intval(date('H', $t));
                                ?>
                                    <th class="text-center"><?= date('H:00', $t) ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php 
                                for ($t = $start; $t <= $end; $t += 3600): 
                                    $label = date('Y-m-d H:00:00', $t);
                                    $hour  = intval(date('H', $t));
                                    
                                    // 超过当前小时的数据强制置 0
                                    if ($hour > $nowHour) {
                                        $value = 0;
                                    } else {
                                        $value = $hourlyTotals[$label] ?? 0;
                                    }
                                ?>
                                    <td class="text-center <?= $value > 0 ? 'table-success' : '' ?>">
                                        <?= $value ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



        </main>
    </div>
</div>
<script>
// 5分钟粒度图表
const labels5min = <?= json_encode($minuteLabels5min) ?>;
const totalData5minJs   = <?= json_encode(array_values($totalData5min)) ?>;
const windowsData5minJs = <?= json_encode(array_values($windowsData5min)) ?>;
const androidData5minJs = <?= json_encode(array_values($androidData5min)) ?>;
const websiteData5minJs = <?= json_encode(array_values($websiteData5min)) ?>;

const ctx5min = document.getElementById('apiChart5min').getContext('2d');
new Chart(ctx5min, {
    type: 'line',
    data: {
        labels: labels5min,
        datasets: [
            { label: '总API', data: totalData5minJs, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.2)', tension: 0.4, fill: true },
            { label: 'Windows API', data: windowsData5minJs, borderColor: '#6610f2', backgroundColor: 'rgba(102,16,242,0.2)', tension: 0.4, fill: true },
            { label: 'Android API', data: androidData5minJs, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.2)', tension: 0.4, fill: true },
            { label: '网站访问', data: websiteData5minJs, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.2)', tension: 0.4, fill: true }
        ]
    },
    options: {
        responsive: true,
        plugins: { 
            legend: { labels: { color: '#212529' } },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: { 
            x: { 
                ticks: { 
                    color: '#212529',
                    callback: function(value, index) {
                        return this.getLabelForValue(index).endsWith('00') ? this.getLabelForValue(index) : '';
                    }
                },
                grid: {
                    color: function(context) {
                        return context.tick && context.tick.label && context.tick.label.endsWith('00') ? 
                            'rgba(0,0,0,0.3)' : 'rgba(0,0,0,0.1)';
                    },
                    lineWidth: function(context) {
                        return context.tick && context.tick.label && context.tick.label.endsWith('00') ? 2 : 1;
                    }
                }
            }, 
            y: { 
                beginAtZero: true,
                ticks: { color: '#212529' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            } 
        }
    }
});



// 过去7天每小时平均
const labels7d = <?= json_encode($labels7d) ?>;
const totalAvg7dJs   = <?= json_encode(array_merge(...array_values($totalAvg7d))) ?>;
const windowsAvg7dJs = <?= json_encode(array_merge(...array_values($windowsAvg7d))) ?>;
const androidAvg7dJs = <?= json_encode(array_merge(...array_values($androidAvg7d))) ?>;
const websiteAvg7dJs = <?= json_encode(array_merge(...array_values($websiteAvg7d))) ?>;

const ctx7d = document.getElementById('avgChart7d').getContext('2d');
new Chart(ctx7d, {
    type: 'line',
    data: {
        labels: labels7d,
        datasets: [
            { label: '总API', data: totalAvg7dJs, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.2)', fill: true, tension: 0.4 },
            { label: 'Windows API', data: windowsAvg7dJs, borderColor: '#6610f2', backgroundColor: 'rgba(102,16,242,0.2)', fill: true, tension: 0.4 },
            { label: 'Android API', data: androidAvg7dJs, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.2)', fill: true, tension: 0.4 },
            { label: '网站访问', data: websiteAvg7dJs, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.2)', fill: true, tension: 0.4 }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: { mode: 'index', intersect: false },
            legend: { labels: { color: '#212529' } }
        },
        scales: {
            x: { ticks: { color: '#212529', maxRotation: 90, minRotation: 45 } },
            y: { beginAtZero: true, ticks: { color: '#212529' } }
        }
    }
});

// 最近24小时每小时总请求
const labels24h = <?= json_encode($labels24h) ?>;
const totals24h = <?= json_encode(array_values($hourlyTotals)) ?>;

const ctx24h = document.getElementById('totals24h').getContext('2d');
new Chart(ctx24h, {
    type: 'bar',
    data: {
        labels: labels24h,
        datasets: [
            { label: '总请求次数', data: totals24h, backgroundColor: 'rgba(0,123,255,0.6)' }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: { mode: 'index', intersect: false },
            legend: { display: false }
        },
        scales: {
            x: { ticks: { color: '#212529', maxRotation: 90, minRotation: 45 } },
            y: { beginAtZero: true, ticks: { color: '#212529' } }
        }
    }
});

</script>

<script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js"></script>
</body>
</html>