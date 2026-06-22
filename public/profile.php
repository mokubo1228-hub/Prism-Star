<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <h2 id="profileName" class="profile-heading"></h2>
      <p id="profileStars" class="profile-stars"></p>
      <form id="githubSettingsForm" class="github-settings" hidden>
        <label for="githubUsername">GitHub username</label>
        <div class="github-settings-row">
          <input id="githubUsername" type="text" name="github_username" autocomplete="off" placeholder="octocat">
          <button type="submit">保存</button>
        </div>
        <p id="githubSettingsMessage" class="github-message" hidden></p>
      </form>
      <section id="profileWorks" class="gallery-grid">
        <template id="profile-item-template">
          <article class="gallery-card">
            <a class="work-link" href="gallery-detail.php?id={ID}">
              <figure class="gallery-item">
                <img src="{SRC}" alt="{TITLE}">
                <figcaption>{TITLE}</figcaption>
              </figure>
            </a>
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

  <script src="Script/profile.js"></script>
</body>
</html>
