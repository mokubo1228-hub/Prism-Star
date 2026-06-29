<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <div class="gallery-list-heading">
        <h2>おすすめ</h2>
        <p>新着と人気</p>
      </div>

      <section class="rank-lane" aria-labelledby="newestHeading">
        <h2 id="newestHeading">新着</h2>
        <div id="newestLane" class="rank-row"></div>
      </section>

      <section class="rank-lane" aria-labelledby="popularHeading">
        <h2 id="popularHeading">人気</h2>
        <div id="popularLane" class="rank-row"></div>
      </section>

      <template id="gallery-item-template">
        <article class="gallery-card">
          <div class="card-thumb">
            <span class="rank-badge"></span>
            <a class="work-link" href="gallery-detail.php?id={ID}" data-gated-link>
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
                <a class="author-link" href="profile.php?id={USER_ID}" data-gated-link>by {AUTHOR}</a>
                <p class="tag-list"></p>
              </div>
              <img class="card-avatar" src="Image/default-avatar.svg" alt="">
            </div>
          </div>
        </article>
      </template>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/gallery-list.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
