<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back search-back">
      <div class="gallery-list-heading">
        <h2 id="searchHeading">検索</h2>
      </div>
      <section id="searchResults" class="gallery-grid search-results"></section>
      <div class="load-more">
        <button type="button" id="searchLoadMore" hidden>もっと見る</button>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/search.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
