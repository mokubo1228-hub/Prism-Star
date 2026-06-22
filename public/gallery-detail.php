<?php
$pageStyles = ['gallery-detail.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

    <main class="gallery-detail-page">
        <article id="work-detail" class="detail-page">
          <template id="detail-template">
            <h1 class="gallery-title">{TITLE}</h1>
            <figure class="detail-img">
              <img src="{SRC}" alt="{TITLE}">
              <figcaption>
                <h2>{TITLE}</h2>
                <p class="detail-author">投稿者：<a href="profile.php?id={USER_ID}">{AUTHOR}</a></p>
                <button type="button" class="star-button detail-star-button" aria-pressed="false">
                  <span class="star-icon">⭐</span>
                  <span class="star-count">0</span>
                </button>
              </figcaption>
            </figure>
            <section>
              <p class="detail-txt">{DESC}</p>
            </section>
            <p class="delete-error" hidden></p>
            <div class="detail-actions">
              <button type="button" class="delete-work-btn" hidden>削除</button>
              <a class="back-menu" href="gallery-list.php">戻る</a>
            </div>
          </template>
        </article>
    </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="Script/gallery-detail.js"></script>
</body>
</html>
