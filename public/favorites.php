<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <div class="gallery-list-heading">
        <h2>お気に入り</h2>
        <p>スターした作品</p>
      </div>

      <section id="favoritesGrid" class="gallery-grid favorites-grid">
        <template id="favorite-item-template">
          <article class="gallery-card">
            <div class="card-thumb">
              <a class="work-link" href="gallery-detail.php?id={ID}">
                <figure class="gallery-item">
                  <img src="{SRC}" alt="{TITLE}">
                </figure>
              </a>
              <button type="button" class="star-button" data-gallery-id="{ID}" aria-pressed="false">
                <span class="star-icon">★</span>
                <span class="star-count">0</span>
              </button>
            </div>
            <div class="card-body">
              <h3 class="card-title">{TITLE}</h3>
              <div class="card-meta">
                <div class="card-meta-text">
                  <a class="author-link" href="profile.php?id={USER_ID}">by {AUTHOR}</a>
                  <p class="tag-list"></p>
                </div>
                <img class="card-avatar" src="image/default-avatar.svg" alt="">
              </div>
            </div>
          </article>
        </template>
      </section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('script/favorites.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
