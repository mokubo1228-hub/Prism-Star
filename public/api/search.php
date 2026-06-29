<?php
require_once __DIR__ . '/../../src/session.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/upload.php';

bootSession();

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
const PER_PAGE = 12;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = $_GET['type'] ?? 'works';
$q = trim((string)($_GET['q'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
// タグは表示上 "#雨" だが DB には "雨" で入る。ユーザーが表示どおり # を付けて打っても
// 当たるよう、先頭の #（半角/全角）を落として正規化する（キーワードも tag に当たるので同様に）。
$q = preg_replace('/^[#＃]+\s*/u', '', $q);
$tag = preg_replace('/^[#＃]+\s*/u', '', $tag);
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
// WHERE の (? = 0 OR g.user_id <> ?) で除外条件をスキップする。q はタイトル/説明の文字列検索（タグは含めない）、
// tag はタグ検索。フロントは入力が # 始まりなら tag に、それ以外は q に振り分ける（[ADR-025]）。
$like = '%' . $q . '%';
$tagLike = '%' . $tag . '%';
// 検索は網羅的に辿る場なので「もっと見る」で段階読み込みする（[ADR-029]）。総件数 COUNT は撃たず、
// PER_PAGE+1 件取れたら「次がある（hasMore）」と判定して余り1件は捨てる。LIMIT/OFFSET は int 化した
// 値だけを文字列に埋める＝外部入力を SQL に渡さない（page は最小1にクランプ）。
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;
$limit = PER_PAGE + 1;
$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.user_id,
        u.name AS author,
        u.avatar_path AS author_avatar,
        g.title,
        g.src,
        g.description AS `desc`,
        g.source,
        g.source_url,
        COUNT(DISTINCT s.id) AS star_count,
        EXISTS(SELECT 1 FROM stars s2 WHERE s2.gallery_id = g.id AND s2.user_id = ?) AS starred,
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
    GROUP BY g.id, g.user_id, u.name, u.avatar_path, g.title, g.src, g.description, g.source, g.source_url, g.created_at
    ORDER BY star_count DESC, g.created_at DESC, g.id DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute([$currentUserId, $currentUserId, $currentUserId, $q, $like, $like, $tag, $tagLike]);
$rows = $stmt->fetchAll();
$hasMore = count($rows) > PER_PAGE;
if ($hasMore) {
    $rows = array_slice($rows, 0, PER_PAGE);
}
$rows = array_map(static function (array $row): array {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = (int)$row['user_id'];
    $row['star_count'] = (int)$row['star_count'];
    $row['starred'] = (bool)$row['starred'];
    $row['author_avatar'] = avatarUrl($row['author_avatar']);
    $row['tags'] = $row['tags'] === null || $row['tags'] === '' ? [] : explode(',', $row['tags']);
    return $row;
}, $rows);

echo json_encode(['type' => 'works', 'results' => $rows, 'hasMore' => $hasMore, 'page' => $page], JSON_UNESCAPED_UNICODE);
