<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 获取最新版本
$latestVersion = getLatestScriptVersion();
?>
<!--若您正在进行代码审查, 请查看console log thanks~-->
<!--
   _____          __        ___________                     
  /  _  \  __ ___/  |_  ____\_   _____/____ _______  _____  
 /  /_\  \|  |  \   __\/  _ \|    __) \__  \\_  __ \/     \ 
/    |    \  |  /|  | (  <_> )     \   / __ \|  | \/  Y Y  \
\____|__  /____/ |__|  \____/\___  /  (____  /__|  |__|_|  /
 ________  ___  ___  _________  ________          ________ ________  ________  _____ ______      
|\   __  \|\  \|\  \|\___   ___\\   __  \        |\  _____\\   __  \|\   __  \|\   _ \  _   \    
\ \  \|\  \ \  \\\  \|___ \  \_\ \  \|\  \       \ \  \__/\ \  \|\  \ \  \|\  \ \  \\\__\ \  \   
 \ \   __  \ \  \\\  \   \ \  \ \ \  \\\  \       \ \   __\\ \   __  \ \   _  _\ \  \\|__| \  \  
  \ \  \ \  \ \  \\\  \   \ \  \ \ \  \\\  \       \ \  \_| \ \  \ \  \ \  \\  \\ \  \    \ \  \ 
   \ \__\ \__\ \_______\   \ \__\ \ \_______\       \ \__\   \ \__\ \__\ \__\\ _\\ \__\    \ \__\
    \|__|\|__|\|_______|    \|__|  \|_______|        \|__|    \|__|\|__|\|__|\|__|\|__|     \|__|
-->
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="description" content="元梦之星专业农场，提供自动农场等功能">
    <meta property="og:title" content="元梦之星农场脚本官网">
    <meta name="keywords" content="元梦之星工具,元梦之星自动农场,元梦之星辅助工具,元梦之星自动任务,元梦之星安全工具">
    <title>主页 | <?= SITE_NAME ?></title>
    
    <?php 
        require_once 'includes/header.php';
    ?>

</head>
<body class="bg-dark text-light">
    <!-- 粒子背景 -->
    <!--<div id="particles-js"></div>-->
    
    <!-- 导航栏 -->
    <?php 
        require_once 'includes/navbar.php';
    ?>
    
    <!--加载页面-->
    <?php 
        require_once 'includes/loading.php';
    ?>

    <!-- 区域 -->
    <section class="hero d-flex align-items-center min-vh-100">
      <div class="container text-center position-relative z-index-1">
        <h1 class="display-3 fw-bold mb-4">
          元梦之星 <span class="text-primary">自动农场</span>
        </h1>
        <p class="lead mb-5">提升游戏体验，解锁更多可能~</p>
    
        <!-- 版本显示 -->
        <div class="version-badge mb-4">
          <span class="badge bg-primary fs-4 px-4 py-2">
            最新版本: v<?= htmlspecialchars($latestVersion['version']) ?>
          </span>
        </div>
    
        <!-- 操作按钮组 -->
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a href="#download" class="btn btn-outline-light btn-lg px-4 py-2">
            <i class="bi bi-download me-2"></i>立即下载
          </a>
          
          <a href="/goto/?url=https%3A%2F%2Fh5.m.taobao.com%2Fawp%2Fcore%2Fdetail.htm%3Fft%3Dt%26id%3D948386350217" target="_blank" class="btn btn-gradient btn-lg px-4 py-2 fw-semibold">
            <i class="bi bi-cart-check me-2"></i>申请试用
          </a>
          
          <a href="/changeLog/" class="btn btn-outline-light btn-lg px-4 py-2">
            <i class="bi bi-journal-text me-2"></i>更新日志
          </a>
        </div>
        


<div class="modal-dialog modal-xl">...</div>
<div class="modal-dialog modal-lg">...</div>
<div class="modal-dialog modal-sm">...</div>
        
    
        <!-- 一言 -->
        <div class="mt-4">
            <div class="rth5f02e937"><span>「</span>加载中...<span>」</span></div>
            <div class="rth777063aa">—— 正在获取</div>
        </div>
      
         <!-- 往下滑动提示 -->
        <div id="scrollHint" class="scroll-down mt-5">
            <span class="text-light d-block mb-2">向下滑动</span>
            <i class="bi bi-chevron-double-down fs-2 text-light"></i>
        </div>


    </section>



    <!-- 功能特点 -->
    <section id="features" class="py-5 bg-dark bg-opacity-50">
        <div class="container">
            
            <h2 class="text-center mb-5">强大功能</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <h3>自动任务</h3>
                        <p>自动在多少秒触发无人机，节省时间精力，轻松获取游戏资源。</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h3>安全稳定</h3>
                        <p>采用系统底层触发按键操作，完美适配 Unity/Unreal 引擎游戏</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-alarm"></i>
                        </div>
                        <h3>定时开关</h3>
                        <p>在全天的任意一个时间段，停止或开启脚本，优化吞吞花逻辑</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-cloud"></i>
                        </div>
                        <h3>云端配置</h3>
                        <p>鱼卡通知、其他人拉你、断网掉线提示等，脚本采用云端下发配置功能，每日调整策略</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-airplane"></i>
                        </div>
                        <h3>轻量快速化</h3>
                        <p>脚本大小不超过60M，策略文件不超过1M，整体CPU占用不超过1%，精准控制时间</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <div class="icon mb-3 text-primary fs-1">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <h3>持续更新</h3>
                        <p>紧跟游戏版本更新，定期优化功能，保持最佳兼容性。</p>
                    </div>
                </div>
            </div>
            
            <!-- 加入QQ群 -->
            <div class="mt-5 d-flex justify-content-center">
              <div class="rounded-4 text-white p-4 shadow-lg"
                   style="background: linear-gradient(135deg, #0d6efd, #6610f2); max-width: 600px; width: 100%;">
                
                <p class="mb-3 fs-5">
                  🎉 欢迎加入我们的 <strong>AutoFarm 官方售后交流群</strong>，一起探索更多可能！
                </p>
            
                <a href="/groupLink/qqGroup/" target="_blank"
                   class="btn btn-lg fw-semibold px-4 py-2"
                   style="
                     background: linear-gradient(135deg, #6fcbff 0%, #57e2ff 100%);
                     border: 2px solid white;
                     color: #ffffff;
                     text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
                     box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
                     border-radius: 0.8rem;
                     transition: all 0.25s ease-in-out;
                   "
                   onmouseover="this.style.filter='brightness(1.15)'; this.style.transform='scale(1.05)'"
                   onmouseout="this.style.filter='brightness(1)'; this.style.transform='scale(1)'">
                  <i class="bi bi-people-fill me-2"></i> 立即加入售后群
                </a>
            
              </div>
            </div>


            
            <!-- 新增宣传内容0 - 页面介绍 -->
            <div class="row align-items-center mt-5 pt-5">
                <div class="col-md-6">
                    <h2 class="mb-4">程序状态主页</h2>
                    <p class="lead">专为《元梦之星》玩家打造的专业自动牧场脚本，助你轻松养殖、稳定收益！</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-speedometer2 text-info me-2"></i>实时状态监控，操作透明可视化</li>
                        <li><i class="bi bi-cloud-arrow-up text-primary me-2"></i>云端配置实时更新下发，智能同步，安全可靠</li>
                        <li><i class="bi bi-stopwatch text-success me-2"></i>精准循环控制，每秒运作尽在掌控</li>
                        <li><i class="bi bi-shield-lock text-warning me-2"></i>授权保障，安心使用无忧</li>
                        <li><i class="bi bi-clock-history text-purple me-2"></i>支持定时开关脚本，灵活自如</li>
                        从此告别繁琐手工操作，尽享游戏乐趣！
                        立即体验，开启高效牧场新时代！
                    </ul>
                    <div class="mt-4">
                        <a href="#download" class="btn btn-primary btn-lg">立即体验</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="assets/img/main.jpg" alt="主程序介绍展示" class="img-fluid rounded shadow" loading="lazy">
                </div>
            </div>
            
            <!-- 新增宣传内容1  -->
            <div class="row align-items-center mt-5 pt-5">
                <div class="col-md-6 order-md-2">
                    <h2 class="mb-4">守护检查机制</h2>
                    <p class="lead">我们将在脚本运行时，自动进行守护检查：</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-wifi text-success me-2"></i>掉线重连：自动进行掉线重连</li>
                        <li><i class="bi bi-person-x-fill text-danger me-2"></i>智能防拉：自动拒绝其他好友拉你</li>
                        <li><i class="bi bi-cloud-arrow-down text-info me-2"></i>云端下发：云端配置实时调整拦截策略</li>
                        <li><i class="bi bi-x-square-fill text-warning me-2"></i>窗口关闭：自动关闭鱼卡页面防止无法操作</li>
                        <li><i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>自动跳跃：防止有星宝卡在前往无人机的必经之路上</li>
                        以及更多~ 我们会一直收集各个玩家的bug报告! 搭配云端配置下发功能, 实时调整策略
                    </ul>
                </div>
                <div class="col-md-6 order-md-1">
                    <img src="assets/img/efficiency-chart.jpg" alt="守护检查机制展示" class="img-fluid rounded" loading="lazy">
                </div>
            </div>
            
            
            <!-- 新增宣传内容2 - 效率对比 -->
            <div class="row align-items-center mt-5 pt-5">
                <div class="col-md-6">
                    <h2 class="mb-4">全区服支持</h2>
                    <p class="lead">支持官方高清模拟器, 完美支持Android / IOS 系统的QQ / 微信登录</p>
                        <ul class="list-unstyled">
                            <li>
                                <i class="bi bi-tencent-qq me-2" style="color: #12b7f5;"></i>
                                <i class="bi bi-check-circle-fill me-2" style="color: green;"></i>支持QQ区服登录
                            </li> 
                            <li>
                                <i class="bi bi-wechat me-2" style="color: #07c160;"></i>
                                <i class="bi bi-check-circle-fill me-2" style="color: green;"></i>支持微信区服登录
                            </li>
                            <li>
                                <i class="bi bi-apple me-2" style="color: #b4b4b4;"></i>
                                <i class="bi bi-check-circle-fill me-2" style="color: green;"></i>支持Android系统QQ/微信区服登录
                            </li> 
                            <li>
                                <i class="bi bi-android2 me-2" style="color: #a4c639;"></i>
                                <i class="bi bi-check-circle-fill me-2" style="color: green;"></i>支持IOS系统 QQ/微信区服登录
                            </li> 
                        </ul>

                </div>
                <div class="col-md-6">
                    <img src="assets/img/multi-platform-support.png" alt="全区服支持介绍展示" class="img-fluid rounded shadow" loading="lazy">
                </div>
            </div>
            
            <!-- 新增宣传内容3 - 安全特性 -->
            <div class="row align-items-center mt-5 pt-5">
                <div class="col-md-6 order-md-2">
                    <h2 class="mb-4">100%安全可靠</h2>
                    <p class="lead">我们采用多重防护机制，全方位保障您的账号与数据安全：</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-lock-fill text-success me-2"></i>纯模拟操作，无需修改游戏文件或内存</li>
                        <li><i class="bi bi-clock-history text-info me-2"></i>智能操作间隔，精准还原真人行为</li>
                        <li><i class="bi bi-eye-slash-fill text-danger me-2"></i>内置反检测机制，调用系统底层进行按键触发</li>
                        <li><i class="bi bi-shield-lock-fill text-primary me-2"></i>授权保护技术，避免非授权设备登陆</li>
                        <li><i class="bi bi-incognito text-warning me-2"></i>不上传任何账号数据，隐私零泄露</li>
                        <li><i class="bi bi-toggle2-on text-secondary me-2"></i>可随时启用/禁用脚本功能，自由可控</li>
                    </ul>
                </div>
                <div class="col-md-6 order-md-1">
                    <img src="assets/img/security.png" alt="安全特性展示" class="img-fluid rounded" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- 下载区域 -->
    <div id="download" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="mb-4">下载最新版本</h2>
                    
                    <?php if ($latestVersion['download_disabled']): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> 当前版本已不提供下载
                    </div>
                    <?php else: ?>
                    <p class="lead mb-4">立即体验元梦之星脚本的强大功能，提升您的游戏效率。</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= htmlspecialchars($latestVersion['download_url']) ?>" class="btn btn-primary btn-lg px-4 py-2">
                            <i class="bi bi-download me-2"></i>下载 v<?= htmlspecialchars($latestVersion['version']) ?>
                        </a>
                        <div>
                            <div class="text-mute">文件大小: <?= htmlspecialchars($latestVersion['file_size']) ?></div>
                            <div class="text-mute">更新日期: <?= date('Y-m-d', strtotime($latestVersion['release_date'])) ?></div>
                        </div>
                    </div>
                    <i class="bi bi-windows text-success me-2"></i>系统版本：WIndows 10/11<br>
                    <i class="bi bi-check-circle-fill text-success me-2"></i>所需空间：60MB<br>
                    <i class="bi bi-cpu text-success me-2"></i>CPU要求：支持主流64位处理器<br>
                    <i class="bi bi-memory text-success me-2"></i>内存需求：推荐至少 2GB 可用内存<br>
                    <i class="bi bi-shield-check text-success me-2"></i>安全无毒<br>

                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="ratio ratio-16x9">
                        <img src="assets/img/preview.jpg" alt="元梦之星自动农场脚本界面预览 - 配置项目设置" class="img-fluid rounded shadow" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
        require_once 'includes/footer.php';
    ?>
    
    <script src="assets/js/index.js" async></script>


    <!--主页诗词显示-->
    <script>
        async function fetchHitokoto() {
          try {
            const response = await fetch("https://v1.hitokoto.cn/?c=i&encode=json");
            const data = await response.json();
            document.querySelector(".rth5f02e937").innerHTML = `「${data.hitokoto}」`;
            document.querySelector(".rth777063aa").innerText = `—— ${data.from || "佚名"}`;
          } catch (e) {
            document.querySelector(".rth5f02e937").innerHTML = "";
            document.querySelector(".rth777063aa").innerText = "";
          }
        }
    
        // 页面加载时先请求一次
        fetchHitokoto();
    </script>

  
</body>
</html>