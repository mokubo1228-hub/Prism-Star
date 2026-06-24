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

      <h2 class="box-title">パスワード再設定</h2>

      <form id="forgotForm">
        <div>
          <label for="forgot-email">メールアドレス</label>
          <input id="forgot-email" name="forgot-email" type="email" autocomplete="email" placeholder="メールアドレス" spellcheck="false" required>
        </div>

        <div>
          <button type="submit">再設定メールを送信</button>
        </div>

        <p id="forgotMessage" class="form-message" hidden></p>
        <p id="forgotError" class="form-error" hidden></p>
      </form>

      <p class="login-navigation">
        <a href="login.php">ログインへ戻る</a>
      </p>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="<?= htmlspecialchars(asset('Script/forgot.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
