<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <a class="back-menu" href="gallery-list.php">← 戻る</a>
      <img id="profileAvatar" class="profile-avatar" src="image/default-avatar.svg" alt="">
      <p id="profileUsername" class="profile-username" hidden></p>
      <h2 id="profileName" class="profile-heading"></h2>
      <p id="profileBio" class="profile-bio" hidden></p>
      <p id="profileStars" class="profile-stars"></p>
      <section id="profileEditPanel" class="profile-edit-panel" hidden>
        <h3>プロフィール編集</h3>
        <form id="profileAvatarForm" class="github-settings profile-avatar-form">
          <label for="profileAvatarInput">アイコン画像</label>
          <input id="profileAvatarInput" type="file" name="avatar" accept="image/*">
          <div class="profile-avatar-actions">
            <button type="submit">更新</button>
            <button type="button" id="profileAvatarRemove">デフォルトに戻す</button>
          </div>
          <p id="profileAvatarMessage" class="github-message" hidden></p>
        </form>
        <form id="profileEditForm" class="github-settings">
          <label for="profileEditUsername">ユーザーネーム</label>
          <input id="profileEditUsername" type="text" name="username" autocomplete="username" maxlength="20" required>
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
            <div class="card-thumb">
              <a class="work-link" href="gallery-detail.php?id={ID}">
                <figure class="gallery-item">
                  <img src="{SRC}" alt="{TITLE}">
                </figure>
              </a>
            </div>
            <div class="card-body">
              <h3 class="card-title">{TITLE}</h3>
              <p class="tag-list"></p>
            </div>
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
  <script src="<?= htmlspecialchars(asset('script/profile.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
