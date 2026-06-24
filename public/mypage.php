<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back mypage-back">
      <div class="gallery-list-heading">
        <h2>マイページ</h2>
        <p>自分の作品管理</p>
      </div>
      <div class="mypage-actions">
        <a class="primary-action" href="work-edit.php">新規作成</a>
      </div>
      <section class="account-settings">
        <h3>アカウント設定</h3>
        <form id="accountNameForm" class="github-settings">
          <label for="accountName">表示名</label>
          <div class="github-settings-row">
            <input id="accountName" type="text" name="name" autocomplete="name" maxlength="100" required>
            <button type="submit">保存</button>
          </div>
          <p id="accountNameMessage" class="github-message" hidden></p>
        </form>
        <form id="passwordChangeForm" class="github-settings">
          <label for="currentPassword">現在のパスワード</label>
          <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
          <label for="newPassword">新しいパスワード</label>
          <input id="newPassword" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
          <button type="submit">パスワードを変更</button>
          <p id="passwordChangeMessage" class="github-message" hidden></p>
        </form>
      </section>
      <form id="githubSettingsForm" class="github-settings">
        <label for="githubUsername">GitHub username</label>
        <div class="github-settings-row">
          <input id="githubUsername" type="text" name="github_username" autocomplete="off" placeholder="octocat">
          <button type="submit">保存</button>
        </div>
        <p id="githubSettingsMessage" class="github-message" hidden></p>
      </form>
      <section id="githubImportPanel" class="github-import-panel" hidden>
        <h3>GitHub から取り込む</h3>
        <p id="githubImportMessage" class="github-message"></p>
        <div id="githubImportRepos" class="github-import-repos"></div>
      </section>
      <section id="myWorks" class="mywork-list"></section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/mypage.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
