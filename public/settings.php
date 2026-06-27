<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back settings-back">
      <a class="settings-back-link" href="mypage.php">← マイページ</a>
      <div class="gallery-list-heading">
        <h2>設定</h2>
        <p>アカウントと連携</p>
      </div>

      <section class="account-settings settings-section">
        <h3>アカウント</h3>
        <form id="passwordChangeForm" class="github-settings">
          <label for="currentPassword">現在のパスワード</label>
          <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
          <label for="newPassword">新しいパスワード</label>
          <input id="newPassword" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
          <button type="submit">パスワードを変更</button>
          <p id="passwordChangeMessage" class="github-message" hidden></p>
        </form>
      </section>

      <section class="settings-section">
        <h3>連携</h3>
        <form id="githubSettingsForm" class="github-settings">
          <label for="githubUsername">GitHub username</label>
          <div class="github-settings-row">
            <input id="githubUsername" type="text" name="github_username" autocomplete="off" placeholder="octocat">
            <button type="submit">保存</button>
          </div>
          <p id="githubSettingsMessage" class="github-message" hidden></p>
        </form>
      </section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/settings.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
