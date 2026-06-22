<?php
$showAddPostButton = $showAddPostButton ?? false;
?>
  <header id="site-header" class="site-header">
    <div class="header-inner">
      <button id="hamburger" class="hamburger">
        <span></span><span></span><span></span>
      </button>

      <div class="site-brand">
        <h1 class="site-title"><a href="gallery-list.php">PrismStar</a></h1>
        <p class="site-tagline">Shine in every color.</p>
      </div>

      <nav class="header-menu">
        <ul class="header-nav">
          <li class="nav-icon">
            <img src="https://placehold.jp/100x100.png" alt="作者アイコン" class="icon">
          </li>

          <template id="nav-item-template">
            <li><a class="header-a" href="{HREF}">{LABEL}</a></li>
          </template>
        </ul>
      </nav>

      <div class="header-auth-actions" aria-label="認証メニュー">
        <div class="auth-guest-actions">
          <a class="auth-action auth-action-login" href="login.php">ログイン</a>
          <a class="auth-action auth-action-register" href="register.php">新規登録</a>
        </div>
        <button type="button" class="auth-action auth-action-logout" hidden>ログアウト</button>
      </div>

<?php if ($showAddPostButton): ?>
      <a href="#" class="add-post-btn" title="新規投稿" style="display: none;">
        <span>＋</span>
      </a>
<?php endif; ?>
    </div>
  </header>
