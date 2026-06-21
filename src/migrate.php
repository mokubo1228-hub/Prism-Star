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

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'stars'
");
$stmt->execute();

if ((int)$stmt->fetchColumn() === 0) {
    $pdo->exec("
        CREATE TABLE stars (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            gallery_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_gallery (user_id, gallery_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (gallery_id) REFERENCES gallery(id) ON DELETE CASCADE
        )
    ");
    echo "Added stars table\n";
} else {
    echo "stars table already exists\n";
}
