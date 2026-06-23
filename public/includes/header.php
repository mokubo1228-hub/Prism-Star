  <header id="site-header" class="site-header">
    <div class="header-inner">
      <button id="hamburger" class="hamburger">
        <span></span><span></span><span></span>
      </button>

      <div class="site-brand">
        <h1 class="site-title"><a href="gallery-list.php">PrismStar</a></h1>
        <p class="site-tagline">Shine in every color.</p>
      </div>

      <form id="headerSearchForm" class="header-search" role="search">
        <input id="headerSearchInput" type="search" name="q" placeholder="作品・タグ・ユーザーを検索" autocomplete="off">
        <button type="submit">検索</button>
      </form>

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
        <a class="auth-action auth-action-mypage" href="mypage.php" hidden>マイページ</a>
        <button type="button" class="auth-action auth-action-logout" hidden>ログアウト</button>
      </div>
    </div>
  </header>

  <div id="authGateModal" class="auth-gate-modal" hidden>
    <div class="auth-gate-dialog" role="dialog" aria-modal="true" aria-labelledby="authGateTitle">
      <button type="button" class="auth-gate-close" aria-label="閉じる">&times;</button>
      <h2 id="authGateTitle">ログイン</h2>
      <form id="authGateLoginForm">
        <label>
          メールアドレス
          <input id="authGateEmail" type="email" autocomplete="email" required>
        </label>
        <label>
          パスワード
          <input id="authGatePassword" type="password" autocomplete="current-password" required>
        </label>
        <button type="submit">ログイン</button>
        <p id="authGateError" class="auth-gate-error" hidden></p>
      </form>
      <p class="auth-gate-register"><a href="register.php">新規登録</a></p>
    </div>
  </div>
