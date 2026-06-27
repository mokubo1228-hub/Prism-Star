<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <h2 id="profileName" class="profile-heading"></h2>
      <p id="profileBio" class="profile-bio" hidden></p>
      <p id="profileStars" class="profile-stars"></p>
      <section id="profileEditPanel" class="profile-edit-panel" hidden>
        <h3>プロフィール編集</h3>
        <form id="profileEditForm" class="github-settings">
          <label for="profileEditName">表示名</label>
          <input id="profileEditName" type="text" name="name" autocomplete="name" maxlength="100" required>
          <label for="profileEditBio">自己紹介</label>
          <textarea id="profileEditBio" name="bio" rows="5" maxlength="1000"></textarea>
          <button type="submit">保存</button>
          <p id="profileEditMessage" class="github-message" hidden></p>
        </form>
      </section>
      <section id="profileWorks" class="gallery-grid">
        <template id="profile-item-template">
          <article class="gallery-card">
            <a class="work-link" href="gallery-detail.php?id={ID}">
              <figure class="gallery-item">
                <img src="{SRC}" alt="{TITLE}">
                <figcaption>{TITLE}</figcaption>
              </figure>
            </a>
            <p class="tag-list"></p>
          </article>
        </template>
      </section>
      <section class="github-section">
        <h2 class="profile-heading">GitHub Repositories</h2>
        <div id="githubRepos" class="github-repos"></div>
      </section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/profile.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
