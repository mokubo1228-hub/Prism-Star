<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

function usersJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    if (empty($_SESSION['user_id'])) {
        usersJson(['error' => 'ログインが必要です'], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $githubUsername = trim((string)($data['github_username'] ?? ''));

    if ($githubUsername !== '' && (strlen($githubUsername) > 100 || !preg_match('/^[A-Za-z0-9-]+$/', $githubUsername))) {
        usersJson(['error' => 'GitHubユーザー名は英数字とハイフンで入力してください'], 400);
    }

    $value = $githubUsername === '' ? null : $githubUsername;
    $stmt = $pdo->prepare("UPDATE users SET github_username = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    usersJson([
        'ok' => true,
        'github_username' => $value,
    ]);
}

if ($method !== 'GET') {
    usersJson(['error' => '許可されていないメソッドです'], 405);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    usersJson(['error' => 'IDが不正です'], 400);
}

$stmt = $pdo->prepare("SELECT id, name, github_username FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    usersJson(['error' => 'ユーザーが見つかりません'], 404);
}

$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.user_id,
        g.title,
        g.src,
        g.description AS `desc`,
        g.visibility,
        COUNT(DISTINCT s.id) AS star_count,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags
    FROM gallery g
    LEFT JOIN stars s ON s.gallery_id = g.id
    LEFT JOIN gallery_tags gt ON gt.gallery_id = g.id
    LEFT JOIN tags t ON t.id = gt.tag_id
    WHERE g.user_id = ? AND g.visibility = 'public'
    GROUP BY g.id, g.user_id, g.title, g.src, g.description, g.visibility, g.created_at
    ORDER BY g.created_at DESC, g.id DESC
");
$stmt->execute([$id]);
$works = array_map(static function (array $work): array {
    $work['id'] = (int)$work['id'];
    $work['user_id'] = (int)$work['user_id'];
    $work['star_count'] = (int)$work['star_count'];
    $work['tags'] = $work['tags'] === null || $work['tags'] === '' ? [] : explode(',', $work['tags']);
    return $work;
}, $stmt->fetchAll());

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stars s
    INNER JOIN gallery g ON g.id = s.gallery_id
    WHERE g.user_id = ? AND g.visibility = 'public'
");
$stmt->execute([$id]);
$totalStars = (int)$stmt->fetchColumn();

usersJson([
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'github_username' => $user['github_username'],
    'total_stars' => $totalStars,
    'works' => $works,
]);
