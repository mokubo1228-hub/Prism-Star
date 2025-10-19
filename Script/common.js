// ヘッダー生成

const header = document.getElementById("site-header");
if (header) {
  document.getElementById("site-header").innerHTML = `
    <div class="header-inner">
      <h1 class="site-title"><a href="gallery-list.html">Okubo Gallery</a></h1>
      <nav class="header-menu">
        <img src="https://placehold.jp/100x100.png" alt="作者アイコン" class="icon">
        <ul class="header-nav">
          <li><a class="header-a" href="login.html">Login</a></li>
          <li><a class="header-a" href="gallery-list.html">Gallery</a></li>
          <li><a class="header-a" href="form.html">Contact</a></li>
          <li><a class="header-a" href="policy.html">Privacy Policy</a></li>
        </ul>
      </nav>
    </div>
  `;
}
// フッター生成
const footer = document.getElementById("site-footer");
if (footer) {
  document.getElementById("site-footer").innerHTML = `
    <div class="footer-info">
      <p class="footer-corporate">&copy; 2025 Miz..Kingdom</p>
      <p>Developer：Miz Kingdom</p>
      <p>Mail: <a class="footer-a" href="mailto:okubo@re-view.co.jp">okubo@re-view.co.jp</a></p>
    </div>

    <nav class="footer-nav">
      <ul>
        <li><a class="footer-a" href="login.html">Login</a></li>
        <li><a class="footer-a" href="gallery-list.html">Gallery</a></li>
        <li><a class="footer-a" href="form.html">Contact</a></li>
        <li><a class="footer-a" href="policy.html">Privacy Policy</a></li>
      </ul>
    </nav>

    <div class="footer-social">
      <a class="footer-a" href="https://re-view.jp/" target="_blank">
        <img src="Image/review-icon.png" alt="Review">
      </a>
      <a class="footer-a" href="https://twitter.com" target="_blank">
        <img src="Image/x-icon.png" alt="X">
      </a>
      <a class="footer-a" href="https://github.com" target="_blank">
        <img src="Image/Github-icon.png" alt="GitHub">
      </a> 
      <a class="footer-a" href="https://linkedin.com" target="_blank">
        <img src="Image/linkedin-icon.png" alt="LinkedIn">
      </a>
    </div>
  `;
}