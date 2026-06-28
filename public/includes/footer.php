<?php require_once __DIR__ . '/asset.php'; ?>

  <footer id="site-footer" class="site-footer">
    <div class="footer-info">
      <p class="footer-corporate">&copy; 2025 Miz..Kingdom</p>
      <p>Developer：Miz Kingdom</p>
      <p>Mail: <a class="footer-a" href="mailto:okubo.m.jobs@gmail.com">okubo.m.jobs@gmail.com</a></p>
    </div>

    <nav class="footer-nav">
      <ul>
        <template id="nav-item-template">
          <li><a class="footer-a" href="{HREF}">{LABEL}</a></li>
        </template>
      </ul>
    </nav>

    <div class="footer-social">
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
  </footer>

  <script src="<?= htmlspecialchars(asset('Script/common.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
