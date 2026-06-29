<?php
$pageStyles = ['gallery-detail.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-detail-page">
    <article id="work-detail" class="detail-page">
      <template id="detail-template">
        <a class="back-menu" href="gallery-list.php">← 戻る</a>
        <figure class="detail-img">
          <img src="{SRC}" alt="{TITLE}">
        </figure>
        <div class="detail-head">
          <h1 class="gallery-title">{TITLE}</h1>
          <button type="button" class="star-button detail-star-button" aria-pressed="false">
            <span class="star-icon">★</span>
            <span class="star-count">0</span>
          </button>
        </div>
        <p class="detail-author"><img class="author-avatar" src="Image/default-avatar.svg" alt=""><a href="profile.php?id={USER_ID}">{AUTHOR}</a></p>
        <p class="detail-meta"></p>
        <section>
          <p class="detail-txt">{DESC}</p>
        </section>
        <div class="detail-actions"></div>
      </template>
    </article>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/gallery-detail.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
