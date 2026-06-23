<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && strtoupper($_GET['_method'] ?? '') === 'PATCH') {
    $method = 'PATCH';
}

function respondJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function requireLogin(): int
{
    $userId = currentUserId();
    if ($userId <= 0) {
        respondJson(['error' => 'ログインが必要です'], 401);
    }
    return $userId;
}

function readInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function normalizeVisibility(string $visibility): string
{
    return $visibility === 'private' ? 'private' : 'public';
}

function normalizeTags(mixed $raw): array
{
    if (is_string($raw)) {
        $raw = preg_split('/[,、\s]+/u', $raw);
    }
    if (!is_array($raw)) {
        return [];
    }

    $tags = [];
    foreach ($raw as $tag) {
        $name = trim((string)$tag);
        if ($name === '') {
            continue;
        }
        if (strlen($name) > 80) {
            $name = substr($name, 0, 80);
        }
        $tags[$name] = true;
        if (count($tags) >= 10) {
            break;
        }
    }
    return array_keys($tags);
}

function validateImageUrl(string $url): bool
{
    $parsed = parse_url($url);
    return $parsed && in_array($parsed['scheme'] ?? '', ['http', 'https'], true);
}

function storeUpload(string $fieldName): ?string
{
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respondJson(['error' => '画像アップロードに失敗しました'], 400);
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        respondJson(['error' => '画像サイズは5MB以下にしてください'], 400);
    }

    $original = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];
    if (!isset($allowed[$ext])) {
        respondJson(['error' => 'アップロードできる画像は jpg/png/webp/gif です'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== $allowed[$ext]) {
        respondJson(['error' => '画像ファイルの形式が不正です'], 400);
    }

    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        respondJson(['error' => 'アップロード先を作成できません'], 500);
    }

    $name = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    $path = $uploadDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        respondJson(['error' => '画像を保存できませんでした'], 500);
    }

    return 'uploads/' . $name;
}

function syncTags(PDO $pdo, int $galleryId, array $tags): void
{
    $pdo->prepare("DELETE FROM gallery_tags WHERE gallery_id = ?")->execute([$galleryId]);
    if (!$tags) {
        return;
    }

    $select = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
    $insert = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
    $link = $pdo->prepare("INSERT IGNORE INTO gallery_tags (gallery_id, tag_id) VALUES (?, ?)");

    foreach ($tags as $tag) {
        $select->execute([$tag]);
        $tagId = $select->fetchColumn();
        if (!$tagId) {
            $insert->execute([$tag]);
            $tagId = $pdo->lastInsertId();
        }
        $link->execute([$galleryId, (int)$tagId]);
    }
}

function mapRows(array $rows): array
{
    return array_map(static function (array $row): array {
        $row['id'] = (int)$row['id'];
        $row['user_id'] = (int)$row['user_id'];
        $row['star_count'] = (int)$row['star_count'];
        $row['starred'] = (bool)$row['starred'];
        $row['is_owner'] = (bool)$row['is_owner'];
        $row['tags'] = $row['tags'] === null || $row['tags'] === '' ? [] : explode(',', $row['tags']);
        return $row;
    }, $rows);
}

function baseSelectSql(string $where): string
{
    return "
        SELECT
            g.id,
            g.user_id,
            u.name AS author,
            g.title,
            g.src,
            g.description AS `desc`,
            g.visibility,
            g.created_at,
            COUNT(DISTINCT s.id) AS star_count,
            MAX(CASE WHEN s.user_id = ? THEN 1 ELSE 0 END) AS starred,
            CASE WHEN g.user_id = ? THEN 1 ELSE 0 END AS is_owner,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags
        FROM gallery g
        INNER JOIN users u ON u.id = g.user_id
        LEFT JOIN stars s ON s.gallery_id = g.id
        LEFT JOIN gallery_tags gt ON gt.gallery_id = g.id
        LEFT JOIN tags t ON t.id = gt.tag_id
        {$where}
        GROUP BY g.id, g.user_id, u.name, g.title, g.src, g.description, g.visibility, g.created_at
    ";
}

if ($method === 'GET') {
    $userId = currentUserId();

    if (isset($_GET['mine'])) {
        $userId = requireLogin();
        $stmt = $pdo->prepare(baseSelectSql("WHERE g.user_id = ?") . " ORDER BY g.created_at DESC, g.id DESC");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode(mapRows($stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare(baseSelectSql("WHERE g.id = ? AND (g.visibility = 'public' OR g.user_id = ?)"));
        $stmt->execute([$userId, $userId, $id, $userId]);
        $rows = mapRows($stmt->fetchAll());
        if (!$rows) {
            respondJson(['error' => '作品が見つかりません'], 404);
        }
        echo json_encode($rows[0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare(baseSelectSql("WHERE g.visibility = 'public'") . " ORDER BY star_count DESC, g.created_at DESC, g.id DESC");
    $stmt->execute([$userId, $userId]);
    echo json_encode(mapRows($stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $userId = requireLogin();
    $data = readInput();
    $title = trim((string)($data['title'] ?? ''));
    $desc = trim((string)($data['desc'] ?? $data['description'] ?? ''));
    $visibility = normalizeVisibility((string)($data['visibility'] ?? 'public'));
    $tags = normalizeTags($data['tags'] ?? '');
    $src = storeUpload('image');
    $imageUrl = trim((string)($data['src'] ?? $data['image_url'] ?? ''));

    if ($src === null && $imageUrl !== '') {
        if (!validateImageUrl($imageUrl)) {
            respondJson(['error' => '画像URLはhttp://またはhttps://で始まるURLを入力してください'], 400);
        }
        $src = $imageUrl;
    }

    if ($title === '' || $src === null) {
        respondJson(['error' => 'タイトルと画像は必須です'], 400);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO gallery (user_id, title, src, description, visibility) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $src, $desc, $visibility]);
        $newId = (int)$pdo->lastInsertId();
        syncTags($pdo, $newId, $tags);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $stmt = $pdo->prepare(baseSelectSql("WHERE g.id = ?"));
    $stmt->execute([$userId, $userId, $newId]);
    echo json_encode(mapRows($stmt->fetchAll())[0], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'PATCH') {
    $userId = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        respondJson(['error' => 'IDが不正です'], 400);
    }

    $data = readInput();
    $title = trim((string)($data['title'] ?? ''));
    $desc = trim((string)($data['desc'] ?? $data['description'] ?? ''));
    $visibility = normalizeVisibility((string)($data['visibility'] ?? 'public'));
    $tags = normalizeTags($data['tags'] ?? '');

    if ($title === '') {
        respondJson(['error' => 'タイトルは必須です'], 400);
    }

    $src = storeUpload('image');
    $imageUrl = trim((string)($data['src'] ?? $data['image_url'] ?? ''));
    if ($src === null && $imageUrl !== '') {
        if (!validateImageUrl($imageUrl)) {
            respondJson(['error' => '画像URLはhttp://またはhttps://で始まるURLを入力してください'], 400);
        }
        $src = $imageUrl;
    }

    $pdo->beginTransaction();
    try {
        if ($src === null) {
            $stmt = $pdo->prepare("UPDATE gallery SET title = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $desc, $visibility, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE gallery SET title = ?, src = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $src, $desc, $visibility, $id, $userId]);
        }

        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare("SELECT id FROM gallery WHERE id = ? AND user_id = ?");
            $check->execute([$id, $userId]);
            if (!$check->fetch()) {
                $pdo->rollBack();
                respondJson(['error' => '対象の作品が見つかりません'], 404);
            }
        }
        syncTags($pdo, $id, $tags);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $stmt = $pdo->prepare(baseSelectSql("WHERE g.id = ?"));
    $stmt->execute([$userId, $userId, $id]);
    echo json_encode(mapRows($stmt->fetchAll())[0], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'DELETE') {
    $userId = requireLogin();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        respondJson(['error' => 'IDが不正です'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) {
        respondJson(['error' => '対象の作品が見つかりません'], 404);
    }
    respondJson(['deleted' => $id]);
}

respondJson(['error' => '許可されていないメソッドです'], 405);
