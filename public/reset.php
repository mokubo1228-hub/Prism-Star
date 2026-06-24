<?php
require_once __DIR__ . '/../src/db.php';

$token = trim($_GET['token'] ?? '');
$isValid = false;
if ($token !== '') {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->execute([hash('sha256', $token)]);
    $isValid = (bool)$stmt->fetch();
}

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

<?php if ($isValid): ?>
      <h2 class="box-title">新しいパスワード</h2>
      <form id="resetForm">
        <input id="resetToken" type="hidden" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <div>
          <label for="resetPassword">新しいパスワード</label>
          <input id="resetPassword" type="password" autocomplete="new-password" placeholder="8文字以上" required>
        </div>
        <div class="form-row">
          <label for="resetPasswordConfirm">新しいパスワード（確認）</label>
          <input id="resetPasswordConfirm" type="password" autocomplete="new-password" placeholder="もう一度入力" required>
        </div>
        <button type="submit">パスワードを再設定</button>
        <p id="resetError" class="form-error" hidden></p>
      </form>
<?php else: ?>
      <h2 class="box-title">再設定URLが無効です</h2>
      <p class="form-error">再設定URLが無効または期限切れです。</p>
      <p class="login-navigation"><a href="forgot.php">再設定メールを送り直す</a></p>
<?php endif; ?>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/reset.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
