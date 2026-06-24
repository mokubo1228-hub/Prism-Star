<?php
$pageStyles = ['login-main.css', 'gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="login-page">
    <section class="login-box work-edit-box">
      <div class="site-title">
        <h1>PrismStar</h1>
        <p class="card-tagline">Shine in every color.</p>
      </div>

      <h2 id="workEditTitle" class="box-title">作品作成</h2>

      <form id="workEditForm" enctype="multipart/form-data">
        <label for="workTitle">タイトル</label>
        <input id="workTitle" name="title" type="text" required>

        <label for="workDesc">説明</label>
        <textarea id="workDesc" name="desc" rows="5"></textarea>

        <label for="workImage">画像アップロード</label>
        <input id="workImage" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">

        <label for="workImageUrl">画像URL</label>
        <input id="workImageUrl" name="image_url" type="url" placeholder="https://example.com/image.png">

        <label for="workTags">タグ</label>
        <input id="workTags" name="tags" type="text" placeholder="風景, 夜, 抽象">

        <label for="workVisibility">公開設定</label>
        <select id="workVisibility" name="visibility">
          <option value="public">公開</option>
          <option value="private">非公開</option>
        </select>

        <div class="work-edit-actions">
          <button type="submit">保存</button>
          <a href="mypage.php">戻る</a>
          <button id="deleteWorkButton" type="button" class="danger-action" hidden>削除</button>
        </div>
        <p id="workEditError" class="form-error" hidden></p>
      </form>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('Script/work-edit.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
