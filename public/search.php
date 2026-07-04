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
      <div class="search-summary">
        <p id="searchCount" class="search-count" aria-live="polite"></p>
        <label class="per-page-control" for="searchPerPage">
          <span>表示件数</span>
          <select id="searchPerPage">
            <option value="10">10</option>
            <option value="30">30</option>
            <option value="50">50</option>
          </select>
        </label>
      </div>
      <section id="searchResults" class="gallery-grid search-results"></section>
      <nav id="searchPager" class="search-pager" aria-label="検索結果のページ"></nav>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('script/search.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
