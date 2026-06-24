<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = $_GET['type'] ?? 'works';
$q = trim((string)($_GET['q'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ユーザー検索：表示名 / GitHub ユーザー名で一致。公開作品数・スター総数は
// gallery を visibility='public' で JOIN して数えるので、非公開は集計にも漏れない。
if ($type === 'users') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            u.github_username,
            COUNT(DISTINCT g.id) AS public_work_count,
            COUNT(DISTINCT s.id) AS total_stars
        FROM users u
        LEFT JOIN gallery g ON g.user_id = u.id AND g.visibility = 'public'
        LEFT JOIN stars s ON s.gallery_id = g.id
        WHERE (? = '' OR u.name LIKE ? OR u.github_username LIKE ?)
        GROUP BY u.id, u.name, u.github_username
        ORDER BY public_work_count DESC, u.created_at DESC
        LIMIT 30
    ");
    $stmt->execute([$q, $like, $like]);
    $rows = array_map(static function (array $row): array {
        $row['id'] = (int)$row['id'];
        $row['public_work_count'] = (int)$row['public_work_count'];
        $row['total_stars'] = (int)$row['total_stars'];
        return $row;
    }, $stmt->fetchAll());
    echo json_encode(['type' => 'users', 'results' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
}

// 作品検索は「他人の公開作品を見つける」場。結果は必ず公開のみ（非公開は所有者だけが見られる）で、
// ログイン中は自分の作品を除外する（自作はマイページで管理する前提）。未ログイン時は currentUserId=0 になり、
// WHERE の (? = 0 OR g.user_id <> ?) で除外条件をスキップする。q はタイトル/説明/タグ、tag はタグでの絞り込み。
$like = '%' . $q . '%';
$tagLike = '%' . $tag . '%';
$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.user_id,
        u.name AS author,
        g.title,
        g.src,
        g.description AS `desc`,
        g.source,
        g.source_url,
        COUNT(DISTINCT s.id) AS star_count,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags
    FROM gallery g
    INNER JOIN users u ON u.id = g.user_id
    LEFT JOIN stars s ON s.gallery_id = g.id
    LEFT JOIN gallery_tags gt ON gt.gallery_id = g.id
    LEFT JOIN tags t ON t.id = gt.tag_id
    WHERE g.visibility = 'public'
      AND (? = 0 OR g.user_id <> ?)
      AND (
        ? = ''
        OR g.title LIKE ?
        OR g.description LIKE ?
        OR EXISTS (
            SELECT 1
            FROM gallery_tags gt2
            INNER JOIN tags t2 ON t2.id = gt2.tag_id
            WHERE gt2.gallery_id = g.id AND t2.name LIKE ?
        )
      )
      AND (
        ? = ''
        OR EXISTS (
            SELECT 1
            FROM gallery_tags gt3
            INNER JOIN tags t3 ON t3.id = gt3.tag_id
            WHERE gt3.gallery_id = g.id AND t3.name LIKE ?
        )
      )
    GROUP BY g.id, g.user_id, u.name, g.title, g.src, g.description, g.source, g.source_url, g.created_at
    ORDER BY star_count DESC, g.created_at DESC, g.id DESC
    LIMIT 50
");
$stmt->execute([$currentUserId, $currentUserId, $q, $like, $like, $like, $tag, $tagLike]);
$rows = array_map(static function (array $row): array {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = (int)$row['user_id'];
    $row['star_count'] = (int)$row['star_count'];
    $row['tags'] = $row['tags'] === null || $row['tags'] === '' ? [] : explode(',', $row['tags']);
    return $row;
}, $stmt->fetchAll());

echo json_encode(['type' => 'works', 'results' => $rows], JSON_UNESCAPED_UNICODE);
