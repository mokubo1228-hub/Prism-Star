<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'github_username'
");
$stmt->execute();

if ((int)$stmt->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE users ADD COLUMN github_username VARCHAR(100) NULL AFTER name");
    echo "Added users.github_username\n";
} else {
    echo "users.github_username already exists\n";
}
