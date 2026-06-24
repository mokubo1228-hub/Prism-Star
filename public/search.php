<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back search-back">
      <div class="gallery-list-heading">
        <h2>検索</h2>
      </div>
      <form id="searchForm" class="search-form">
        <input id="searchQuery" type="search" name="q" placeholder="キーワード">
        <input id="searchTag" type="search" name="tag" placeholder="タグ">
        <select id="searchType" name="type">
          <option value="works">作品</option>
          <option value="users">ユーザー</option>
        </select>
        <button type="submit">検索</button>
      </form>
      <section id="searchResults" class="gallery-grid search-results"></section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/search.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
