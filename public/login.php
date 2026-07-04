<?php
$pageStyles = ['login-main.css'];
include __DIR__ . '/includes/head.php';
?>

<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="login-page">

    <section class="login-box">

        <div class="site-title">
            <h1>PrismStar</h1>
            <p class="card-tagline">Shine in every color.</p>
        </div>

        <h2 class="box-title">ログイン</h2>

        <form id="loginForm">
          <div>
              <label for="login-id">ID</label>
              <input id="login-id" name="login-id" type="email" autocomplete="user-name" placeholder="メールアドレス"
              spellcheck="false" required>
          </div>

            <div class="form-row">
              <label for="login-pw">パスワード</label>
              <input id="login-pw" name="login-pw" type="password" autocomplete="current-password" placeholder="ご自身で設定したパスワード">
            </div>

            <div>
              <button type="submit">ログイン</button>
            </div>
          </form>
          <p id="loginError" class="form-error" hidden></p>

          <p class="login-navigation">
            <a href="forgot.php">パスワードをお忘れの場合</a> /
            <a href="register.php">新規登録</a>
          </p>

    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="<?= htmlspecialchars(asset('script/login.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
