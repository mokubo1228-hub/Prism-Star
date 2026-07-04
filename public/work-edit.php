<?php
$pageStyles = ['gallery-list.css'];
include __DIR__ . '/includes/head.php';
?>
<body class="site-body">
<?php include __DIR__ . '/includes/header.php'; ?>

  <main class="gallery-list">
    <div class="gallery-back">
      <a class="settings-back-link" href="mypage.php">← マイページ</a>
      <div class="gallery-list-heading">
        <h2 id="workEditTitle">作品作成</h2>
        <p>作品を作成・編集</p>
      </div>

      <section class="settings-section">
        <form id="workEditForm" class="github-settings work-edit-form" enctype="multipart/form-data">
          <p id="workSourceNote" class="form-note" hidden></p>

          <div class="work-edit-layout">
            <div class="work-edit-visual">
              <label for="workImage">画像アップロード</label>
              <input id="workImage" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">

              <label for="workImageUrl">画像URL</label>
              <input id="workImageUrl" name="image_url" type="url" placeholder="https://example.com/image.png">
            </div>

            <div class="work-edit-fields">
              <label for="workTitle">タイトル</label>
              <input id="workTitle" name="title" type="text" required>

              <label for="workDesc">説明</label>
              <textarea id="workDesc" name="desc" rows="5"></textarea>

              <label for="workTags">タグ</label>
              <input id="workTags" name="tags" type="text" placeholder="風景, 夜, 抽象">

              <label for="workVisibility">公開設定</label>
              <select id="workVisibility" name="visibility">
                <option value="public">公開</option>
                <option value="private">非公開</option>
              </select>

              <div class="work-edit-actions">
                <button type="submit">保存</button>
                <button id="deleteWorkButton" type="button" class="danger-button" hidden>削除</button>
              </div>
              <p id="workEditError" class="github-message is-error" hidden></p>
            </div>
          </div>
        </form>
      </section>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="<?= htmlspecialchars(asset('script/work-edit.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
