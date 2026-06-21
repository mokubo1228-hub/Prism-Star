<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'ログインが必要です']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $githubUsername = trim($data['github_username'] ?? '');

    if ($githubUsername !== '') {
        if (strlen($githubUsername) > 100 || !preg_match('/^[A-Za-z0-9-]+$/', $githubUsername)) {
            http_response_code(400);
            echo json_encode(['error' => 'GitHubユーザー名は英数字とハイフンで入力してください']);
            exit;
        }
    }

    $value = $githubUsername === '' ? null : $githubUsername;
    $stmt = $pdo->prepare("UPDATE users SET github_username = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    echo json_encode([
        'ok'              => true,
        'github_username' => $value,
    ]);
    exit;
}

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

$stmt = $pdo->prepare("SELECT id, name, github_username FROM users WHERE id = ?");
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

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stars s
    INNER JOIN gallery g ON g.id = s.gallery_id
    WHERE g.user_id = ?
");
$stmt->execute([$id]);
$totalStars = (int)$stmt->fetchColumn();

echo json_encode([
    'id'              => (int)$user['id'],
    'name'            => $user['name'],
    'github_username' => $user['github_username'],
    'total_stars'     => $totalStars,
    'works'           => $works,
]);
