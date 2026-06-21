<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['POST', 'DELETE'], true)) {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$galleryId = (int)($_GET['gallery_id'] ?? 0);
if ($galleryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'IDが不正です']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM gallery WHERE id = ?");
$stmt->execute([$galleryId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => '作品が見つかりません']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

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

echo json_encode([
    'ok'         => true,
    'gallery_id' => $galleryId,
    'star_count' => $starCount,
    'starred'    => $starred,
]);
