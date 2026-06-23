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

      <h2 class="box-title">新規登録</h2>

      <form id="registerForm">
        <div>
          <label for="register-email">メールアドレス</label>
          <input id="register-email" name="register-email" type="email" autocomplete="email" placeholder="メールアドレス" spellcheck="false" required>
        </div>

        <div>
          <button type="submit">確認メールを送信</button>
        </div>

        <p id="registerMessage" class="form-message" hidden></p>
        <p id="registerError" class="form-error" hidden></p>
      </form>

      <p class="login-navigation">
        <a href="login.php">ログインはこちら</a>
      </p>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="Script/register.js"></script>
</body>
</html>
