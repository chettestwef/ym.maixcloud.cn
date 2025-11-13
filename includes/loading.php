<div id="loading-root"></div>

<style>
  #loading-root #page-loader {
    position: fixed;
    inset: 0;
    background: rgba(18, 18, 18, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #fff;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    animation: loadingFadeIn 0.6s ease forwards;
  }
  #loading-root #page-loader .loader-spinner {
    position: relative;
    width: 80px;
    height: 80px;
  }
  #loading-root #page-loader .loader-spinner div {
    position: absolute;
    border: 4px solid #0dcaf0;
    border-radius: 50%;
    opacity: 1;
    animation: loadingPulse 1.2s cubic-bezier(0, 0.2, 0.8, 1) infinite;
  }
  #loading-root #page-loader .loader-spinner div:nth-child(2) {
    animation-delay: -0.6s;
  }
  #loading-root #page-loader .loader-text {
    margin-top: 20px;
    font-size: 1.1rem;
    opacity: 0.85;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
  }

  /* 对勾容器 */
  #loading-root #checkmark {
    display: none;
    margin-top: 30px;
    width: 80px;
    height: 80px;
  }
  /* SVG路径动画 */
  #loading-root #checkmark path {
    stroke: #0dcaf0;
    stroke-width: 6;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: drawCheck 0.8s ease forwards;
  }

  @keyframes loadingPulse {
    0% {
      top: 36px; left: 36px;
      width: 0; height: 0;
      opacity: 1;
    }
    100% {
      top: 0; left: 0;
      width: 72px; height: 72px;
      opacity: 0;
    }
  }
  @keyframes loadingFadeIn {
    to { opacity: 1; }
  }
@keyframes loadingFadeOut {
  from {
    background-color: rgba(18, 18, 18, 0.8);
    opacity: 1;
  }
  to {
    background-color: rgba(18, 18, 18, 0);
    opacity: 0;
    visibility: hidden;
  }
}
  @keyframes drawCheck {
    to {
      stroke-dashoffset: 0;
    }
  }
</style>

<script>
  (function(){
    const root = document.getElementById('loading-root');
    if(!root) return;

    root.innerHTML = `
      <div id="page-loader">
        <div class="loader-spinner">
          <div></div><div></div>
        </div>
        <p class="loader-text">页面加载中，请稍候...</p>
        <svg id="checkmark" viewBox="0 0 24 24">
          <path d="M4 12l6 6L20 6" />
        </svg>
      </div>
    `;

    const loader = root.querySelector('#page-loader');
    const spinner = loader.querySelector('.loader-spinner');
    const checkmark = loader.querySelector('#checkmark');

    document.addEventListener("DOMContentLoaded", () => {
      setTimeout(() => {
        // 隐藏spinner，显示对勾
        spinner.style.display = 'none';
        loader.querySelector('.loader-text').textContent = '加载完成！';
        checkmark.style.display = 'block';

        // 对勾动画结束后淡出loading
        setTimeout(() => {
          loader.style.animation = "loadingFadeOut 1s ease forwards";
          setTimeout(() => root.innerHTML = '', 1000);
        }, 1000); // 给对勾动画留足时间
      }, 300);
    });
  })();
</script>
