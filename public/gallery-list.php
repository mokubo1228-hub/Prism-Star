<?php
$pageStyles = ['gallery-list.css', 'modal.css'];
$showAddPostButton = true;
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>


  <main class="gallery-list">
    <div class="gallery-back">
      <div class="gallery-list-heading">
        <h2>みんなの作品</h2>
        <p>新着</p>
      </div>
      <section id="gallery" class="gallery-grid">
        <template id="gallery-item-template">
          <article class="gallery-card">
            <a class="work-link" href="gallery-detail.php?id={ID}">
              <figure class="gallery-item">
                <img src="{SRC}" alt="{TITLE}">
                <figcaption>{TITLE}</figcaption>
              </figure>
            </a>
            <a class="author-link" href="profile.php?id={USER_ID}">by {AUTHOR}</a>
            <button type="button" class="star-button" data-gallery-id="{ID}" aria-pressed="false">
              <span class="star-icon">⭐</span>
              <span class="star-count">0</span>
            </button>
          </article>
        </template>
      </section>
    </div>
  </main>


<?php include __DIR__ . '/includes/footer.php'; ?>

  <div id="postModal" class="modal-overlay">
    <div class="modal-dialog">
      <button class="modal-close">&times;</button>
      <h2>新規投稿</h2>
      <form id="postForm">
        <label>
          タイトル：
          <input type="text" id="postTitle" required>
        </label>
        <label>
          画像URL：
          <input type="url" id="postImage" required>
        </label>
        <label>
          説明：
          <textarea id="postDesc" rows="3" required></textarea>
        </label>
        <button type="submit" class="post-submit">投稿する</button>
      </form>
    </div>
  </div>

  <script src="Script/gallery-list.js"></script>

</body>
</html>
