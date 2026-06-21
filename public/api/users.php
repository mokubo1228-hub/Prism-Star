<?php
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'IDが不正です']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'ユーザーが見つかりません']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, src, description AS `desc`
    FROM gallery
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([$id]);
$works = $stmt->fetchAll();

echo json_encode([
    'id'    => (int)$user['id'],
    'name'  => $user['name'],
    'works' => $works,
]);
