<?php
// 数据统计
recordApiCounter('website_visit'); // 同时记录总计数和每日计数
?>
<!--includes/footer.php -->
<!--includes/footer.php -->
<!--includes/footer.php -->

<footer class="py-3 bg-dark bg-opacity-75 text-light border-0 position-relative">
  <div class="container text-center d-flex flex-column align-items-center gap-2">

    <small class="text-secondary">
      &copy; 2025-<span id="currentYear"></span> <?= htmlspecialchars(SITE_NAME) ?> 保留所有权利
    </small>

    <a href="https://ipw.cn/ssl/?site=ymzxs.fun" title="本站支持SSL安全访问" target="_blank">
      <img class="footer-icon" alt="本站支持SSL安全访问" src="https://static.ipw.cn/icon/ssl-s6.svg">
    </a>

    <ul class="list-inline mb-1 small">
      <li class="list-inline-item"><a href="/goto/?url=https%3A%2F%2Fstatus.ymzxs.fun%2F" target="_blank" class="footer-link">服务状态</a></li>
      <li class="list-inline-item text-secondary">|</li>
      <li class="list-inline-item"><a href="/groupLink/qqGroup/" target="_blank" class="footer-link">售后QQ群</a></li>
      <li class="list-inline-item text-secondary">|</li>
      <li class="list-inline-item"><a href="/authQuery/" target="_blank" class="footer-link">授权查询</a></li>
    </ul>
    
    <!-- 统计信息 -->
    <div id="api-counter" class="small text-secondary mt-2"></div>


    <span id="jinrishici-sentence" class="fst-italic text-info small"></span>

    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a href="https://hopedomain.com/zh/age/ymzxs.fun" target="_blank">
        <img class="footer-badge" src="https://hopedomain.com/api/badge/domain-age/ymzxs.fun?theme=whatsapp&lang=zh" alt="域名年龄" />
      </a>
      <a href="https://zssl.com/zh/ssl-cert-verify?domain=ymzxs.fun" target="_blank">
        <img class="footer-badge" src="https://zssl.com/api/badge/ssl-cert/ymzxs.fun?style=full&lang=zh" alt="SSL证书状态" />
      </a>
    </div>

  </div>
</footer>

<!-- 脚本 -->
<script src="/assets/library/bootstrap-5.3.7-dist/js/bootstrap.bundle.js" charset="utf-8" defer></script>
<script src="https://sdk.jinrishici.com/v2/browser/jinrishici.js" charset="utf-8" defer></script>
<script>
  document.getElementById("currentYear").textContent = new Date().getFullYear();
</script>
<!-- 统计信息JS -->
<script>
async function loadApiCounter() {
  const counterEl = document.getElementById("api-counter");
  try {
    const res = await fetch("https://ymzxs.fun/api/count/");
    const json = await res.json();
    if (json.status === "success") {
      const d = json.data;

      counterEl.innerHTML = `
        今日请求：总 ${d.daily.total_api} | Windows ${d.daily.windows_api} | Android ${d.daily.android_api} | 网站访问 ${d.daily.website_visit} <br>
        累计请求：总 ${d.total.total_api} | Windows ${d.total.windows_api} | Android ${d.total.android_api} | 网站访问 ${d.total.website_visit}
      `;
    } else {
      counterEl.textContent = "统计数据加载失败";
    }
  } catch (e) {
    console.error("加载计数失败:", e);
    counterEl.textContent = "统计请求错误";
  }
}
loadApiCounter();
</script>

<!--hackegg-->
<script src="/assets/js/easter-egg/HackWelcome.js" charset="utf-8" defer></script>



<!-- 样式 -->
<style>
  html, body {height: 100%;}
  body {
    display: flex;
    flex-direction: column;
    background-color: #121212;
  }
  main {flex: 1 0 auto;}
  footer {flex-shrink: 0;}

  /* 渐变分割线 */
  footer::before {
    content: "";
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 2px;
    background: linear-gradient(90deg, #4da6ff, #00d4ff, #a26bff);
  }

  /* 链接 hover */
  .footer-link {
    color: #aaa;
    text-decoration: none;
    transition: color 0.3s ease;
  }
  .footer-link:hover {color: #4da6ff;}

  /* 图标 hover */
  .footer-icon {
    display: inline-block;
    vertical-align: middle;
    transition: transform 0.3s ease, filter 0.3s ease;
  }
  .footer-icon:hover {
    transform: scale(1.1);
    filter: drop-shadow(0 0 6px rgba(77,166,255,0.6));
  }

  /* 徽章 hover */
  .footer-badge {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 6px;
  }
  .footer-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(77,166,255,0.35);
  }
</style>


<!--includes/footer.php -->
<!--includes/footer.php -->
<!--includes/footer.php -->