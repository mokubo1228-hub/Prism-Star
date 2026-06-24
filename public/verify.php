<?php
require_once __DIR__ . '/../src/db.php';

$token = trim($_GET['token'] ?? '');
$isValid = false;
if ($token !== '') {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT id FROM pending_registrations WHERE token_hash = ? AND expires_at > NOW()");
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
      <h2 class="box-title">登録を完了</h2>
      <form id="verifyForm">
        <input id="verifyToken" type="hidden" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <div>
          <label for="verifyName">表示名</label>
          <input id="verifyName" type="text" autocomplete="name" required>
        </div>
        <div class="form-row">
          <label for="verifyPassword">パスワード</label>
          <input id="verifyPassword" type="password" autocomplete="new-password" placeholder="8文字以上" required>
        </div>
        <button type="submit">登録して始める</button>
        <p id="verifyError" class="form-error" hidden></p>
      </form>
<?php else: ?>
      <h2 class="box-title">確認URLが無効です</h2>
      <p class="form-error">確認URLが無効または期限切れです。</p>
      <p class="login-navigation"><a href="register.php">新規登録へ戻る</a></p>
<?php endif; ?>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/verify.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
