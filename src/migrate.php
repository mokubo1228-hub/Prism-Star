<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

if (!columnExists($pdo, 'users', 'username')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(30) NULL AFTER name");
    echo "Added users.username\n";
} else {
    echo "users.username already exists\n";
}

$backfilledUsernames = $pdo->exec("UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL");
echo "Backfilled users.username rows: " . (int)$backfilledUsernames . "\n";

if (!indexExists($pdo, 'users', 'uniq_users_username')) {
    $pdo->exec("ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)");
    echo "Added uniq_users_username\n";
} else {
    echo "uniq_users_username already exists\n";
}

if (!columnExists($pdo, 'users', 'github_username')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN github_username VARCHAR(100) NULL AFTER name");
    echo "Added users.github_username\n";
} else {
    echo "users.github_username already exists\n";
}

if (!columnExists($pdo, 'users', 'bio')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER github_username");
    echo "Added users.bio\n";
} else {
    echo "users.bio already exists\n";
}

if (!tableExists($pdo, 'stars')) {
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

if (!columnExists($pdo, 'gallery', 'visibility')) {
    $pdo->exec("ALTER TABLE gallery ADD COLUMN visibility ENUM('public','private') NOT NULL DEFAULT 'public' AFTER description");
    echo "Added gallery.visibility\n";
} else {
    echo "gallery.visibility already exists\n";
}

if (!columnExists($pdo, 'gallery', 'source')) {
    $pdo->exec("ALTER TABLE gallery ADD COLUMN source ENUM('manual','github') NOT NULL DEFAULT 'manual' AFTER visibility");
    echo "Added gallery.source\n";
} else {
    echo "gallery.source already exists\n";
}

if (!columnExists($pdo, 'gallery', 'source_url')) {
    $pdo->exec("ALTER TABLE gallery ADD COLUMN source_url VARCHAR(255) NULL AFTER source");
    echo "Added gallery.source_url\n";
} else {
    echo "gallery.source_url already exists\n";
}

if (!indexExists($pdo, 'gallery', 'uniq_gallery_user_source')) {
    $pdo->exec("ALTER TABLE gallery ADD UNIQUE KEY uniq_gallery_user_source (user_id, source_url)");
    echo "Added uniq_gallery_user_source\n";
} else {
    echo "uniq_gallery_user_source already exists\n";
}

if (!tableExists($pdo, 'tags')) {
    $pdo->exec("
        CREATE TABLE tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Added tags table\n";
} else {
    echo "tags table already exists\n";
}

if (!tableExists($pdo, 'gallery_tags')) {
    $pdo->exec("
        CREATE TABLE gallery_tags (
            gallery_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (gallery_id, tag_id),
            FOREIGN KEY (gallery_id) REFERENCES gallery(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");
    echo "Added gallery_tags table\n";
} else {
    echo "gallery_tags table already exists\n";
}

if (!tableExists($pdo, 'pending_registrations')) {
    $pdo->exec("
        CREATE TABLE pending_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pending_email (email),
            INDEX idx_pending_expires_at (expires_at)
        )
    ");
    echo "Added pending_registrations table\n";
} else {
    echo "pending_registrations table already exists\n";
}

if (!tableExists($pdo, 'password_resets')) {
    $pdo->exec("
        CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_user_id (user_id),
            INDEX idx_password_resets_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "Added password_resets table\n";
} else {
    echo "password_resets table already exists\n";
}
