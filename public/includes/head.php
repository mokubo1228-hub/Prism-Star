<?php
$pageTitle = $pageTitle ?? 'PrismStar';
$pageStyles = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="Style/sanitize.css">
  <link rel="stylesheet" href="Style/body.css">
  <link rel="stylesheet" href="Style/header.css">
<?php foreach ($pageStyles as $style): ?>
  <link rel="stylesheet" href="Style/<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
  <link rel="stylesheet" href="Style/footer.css">
</head>
