<?php
require_once __DIR__ . '/asset.php';
require_once __DIR__ . '/../../src/session.php';

bootSession();

$pageTitle = $pageTitle ?? 'PrismStar';
$pageStyles = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('Style/sanitize.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('Style/body.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('Style/header.css'), ENT_QUOTES, 'UTF-8') ?>">
<?php foreach ($pageStyles as $style): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('Style/' . $style), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset('Style/footer.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
