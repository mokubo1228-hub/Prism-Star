<?php require_once __DIR__ . '/asset.php'; ?>

  <footer id="site-footer" class="site-footer">
    <div class="footer-brand">
      <p class="footer-brand-name"><a href="gallery-list.php">PrismStar</a></p>
      <p class="footer-brand-tagline">Shine in every color.</p>
    </div>

    <nav class="footer-links" aria-label="Footer">
      <a class="footer-a" href="form.php">Contact</a>
      <a class="footer-a" href="policy.php">Privacy Policy</a>
    </nav>

    <div class="footer-maker">
      <p class="footer-corporate">&copy; <?= date('Y') ?> Miz Kingdom</p>
      <p class="footer-mail">Mail: <a class="footer-a" href="mailto:okubo.m.jobs@gmail.com">okubo.m.jobs@gmail.com</a></p>
      <div class="footer-social">
        <a class="footer-a" href="https://github.com" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
          <img src="image/Github-icon.png" alt="GitHub">
        </a>
        <a class="footer-a" href="https://twitter.com" target="_blank" rel="noopener noreferrer" aria-label="X">
          <img src="image/x-icon.png" alt="X">
        </a>
      </div>
    </div>
  </footer>

  <script src="<?= htmlspecialchars(asset('script/common.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
