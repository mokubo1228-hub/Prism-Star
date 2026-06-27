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
        <a class="secondary-action" href="settings.php">設定</a>
      </div>
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
