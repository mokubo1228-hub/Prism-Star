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
            <a class="work-link" href="gallery-detail.php?id={ID}">
              <figure class="gallery-item">
                <img src="{SRC}" alt="{TITLE}">
                <figcaption>{TITLE}</figcaption>
              </figure>
            </a>
            <a class="author-link" href="profile.php?id={USER_ID}">by {AUTHOR}</a>
            <p class="tag-list"></p>
            <button type="button" class="star-button" data-gallery-id="{ID}" aria-pressed="false">
              <span class="star-icon">★</span>
              <span class="star-count">0</span>
            </button>
          </article>
        </template>
      </section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/favorites.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
