<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

function starsJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($method, ['POST', 'DELETE'], true)) {
    starsJson(['error' => '許可されていないメソッドです'], 405);
}

if (empty($_SESSION['user_id'])) {
    starsJson(['error' => 'ログインが必要です'], 401);
}

$galleryId = (int)($_GET['gallery_id'] ?? 0);
if ($galleryId <= 0) {
    starsJson(['error' => 'IDが不正です'], 400);
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM gallery WHERE id = ? AND (visibility = 'public' OR user_id = ?)");
$stmt->execute([$galleryId, $userId]);
if (!$stmt->fetch()) {
    starsJson(['error' => '作品が見つかりません'], 404);
}

if ($method === 'POST') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO stars (user_id, gallery_id) VALUES (?, ?)");
    $stmt->execute([$userId, $galleryId]);
    $starred = true;
} else {
    $stmt = $pdo->prepare("DELETE FROM stars WHERE user_id = ? AND gallery_id = ?");
    $stmt->execute([$userId, $galleryId]);
    $starred = false;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM stars WHERE gallery_id = ?");
$stmt->execute([$galleryId]);
$starCount = (int)$stmt->fetchColumn();

starsJson([
    'ok' => true,
    'gallery_id' => $galleryId,
    'star_count' => $starCount,
    'starred' => $starred,
]);
