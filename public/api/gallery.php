<?php
require_once __DIR__ . '/../../src/session.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/github_client.php';

bootSession();

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
// 編集は画像ファイルを伴う multipart 送信になり得るが、PHP は multipart の PATCH 本文を
// $_POST に展開してくれない。そのため編集は POST＋?_method=PATCH で送らせ、ここで PATCH に正規化する。
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

// 可視性はクライアント由来。ENUM の 2 値だけを許し、未知の値は既定の public に倒す
// （不正な文字列をそのまま DB に渡さないための whitelist）。
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

function findGithubRepoByName(array $repos, string $repoName): ?array
{
    foreach ($repos as $repo) {
        if (($repo['name'] ?? '') === $repoName) {
            return $repo;
        }
    }
    return null;
}

// 画像アップロードの安全弁（[ADR-015]）。「偽装した実行可能ファイルの設置」を 3 段で防ぐ：
//   ① 拡張子 allowlist ② finfo による実体 MIME と拡張子の一致確認
//   ③ 保存名はサーバ生成の乱数（元のファイル名を信用せず、上書き・パス操作・.php 偽装を断つ）。
// 保存先 public/uploads/ は .htaccess で PHP 実行を無効化済みで、これと二重の防御になる。
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

// タグは差分更新でなく「全削除→張り直し」。送られてきたタグ集合を最終状態としてそのまま反映する方が、
// 編集時の付け外しを取りこぼさず単純（1作品あたり最大10タグ前提なので張り直しコストも問題にならない）。
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
            g.source,
            g.source_url,
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
        GROUP BY g.id, g.user_id, u.name, g.title, g.src, g.description, g.visibility, g.source, g.source_url, g.created_at
    ";
}

if ($method === 'GET') {
    $userId = currentUserId();

    // マイページ（管理画面）専用。本人の作品だけを、非公開も含めて返す（要ログイン）。
    if (isset($_GET['mine'])) {
        $userId = requireLogin();
        $stmt = $pdo->prepare(baseSelectSql("WHERE g.user_id = ?") . " ORDER BY g.created_at DESC, g.id DESC");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode(mapRows($stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 詳細：公開は誰でも、非公開は所有者だけ。条件に合致しない非公開作品はそもそも行が返らず、
    // 「非公開」とも「存在しない」とも区別させずに一律 404（safety invariant：非公開を所有者以外に返さない）。
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

    // おすすめは公開作品のみ。ログイン中は自分の作品を除外する（発見系＝他人の作品を見つける場。
    // 自作はマイページ/プロフィールで見る。検索と同じ方針＝[ADR-027]）。未ログイン（userId=0）は
    // (? = 0) が真になり除外をスキップ＝teaser には全公開作品が出る。
    $stmt = $pdo->prepare(baseSelectSql("WHERE g.visibility = 'public' AND (? = 0 OR g.user_id <> ?)") . " ORDER BY star_count DESC, g.created_at DESC, g.id DESC");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    echo json_encode(mapRows($stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
    exit;
}

if (in_array($method, ['POST', 'PATCH', 'DELETE'], true)) {
    requireCsrf();
}

if ($method === 'POST') {
    $userId = requireLogin();
    if (($_GET['action'] ?? '') === 'import-github') {
        $data = readInput();
        $repoName = trim((string)($data['repo'] ?? ''));
        if ($repoName === '' || strlen($repoName) > 100) {
            respondJson(['error' => 'リポジトリ名が不正です'], 400);
        }

        $stmt = $pdo->prepare("SELECT github_username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $githubUsername = trim((string)$stmt->fetchColumn());
        if ($githubUsername === '') {
            respondJson(['error' => '先に GitHub ユーザー名を設定してください'], 400);
        }

        try {
            $repos = fetchGithubRepos($githubUsername);
        } catch (GithubClientException $e) {
            respondJson(['error' => $e->getMessage()], $e->responseStatus());
        }

        $repo = findGithubRepoByName($repos, $repoName);
        if ($repo === null) {
            respondJson(['error' => 'リポジトリが見つかりません'], 404);
        }
        if (!empty($repo['fork'])) {
            respondJson(['error' => 'fork は取り込めません'], 400);
        }

        $title = (string)$repo['name'];
        $desc = (string)($repo['description'] ?? '');
        $sourceUrl = (string)$repo['html_url'];
        if ($title === '' || $sourceUrl === '') {
            respondJson(['error' => 'GitHub リポジトリ情報が不完全です'], 502);
        }
        $src = 'https://opengraph.githubassets.com/prismstar/' . rawurlencode($githubUsername) . '/' . rawurlencode($title);

        $pdo->beginTransaction();
        try {
            // de-dupe は server 確定の source_url とセッション user_id で限定する。
            // visibility はユーザーの公開判断なので、再取り込みでは上書きしない。
            $select = $pdo->prepare("SELECT id FROM gallery WHERE user_id = ? AND source_url = ? FOR UPDATE");
            $select->execute([$userId, $sourceUrl]);
            $galleryId = (int)$select->fetchColumn();

            if ($galleryId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE gallery
                    SET title = ?, description = ?, src = ?, source = 'github'
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$title, $desc, $src, $galleryId, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO gallery (user_id, title, src, description, visibility, source, source_url)
                    VALUES (?, ?, ?, ?, 'public', 'github', ?)
                ");
                $stmt->execute([$userId, $title, $src, $desc, $sourceUrl]);
                $galleryId = (int)$pdo->lastInsertId();
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $stmt = $pdo->prepare(baseSelectSql("WHERE g.id = ?"));
        $stmt->execute([$userId, $userId, $galleryId]);
        echo json_encode(mapRows($stmt->fetchAll())[0], JSON_UNESCAPED_UNICODE);
        exit;
    }

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

    $stmt = $pdo->prepare("SELECT source FROM gallery WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $source = $stmt->fetchColumn();
    if ($source === false) {
        respondJson(['error' => '対象の作品が見つかりません'], 404);
    }

    $src = null;
    if ($source !== 'github') {
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
    }

    $pdo->beginTransaction();
    try {
        if ($source === 'github') {
            // GitHub 由来の title/description/src は import endpoint だけが更新する。
            // 編集では公開状態とタグだけを扱い、client 由来の repo メタ情報を保存しない。
            $stmt = $pdo->prepare("UPDATE gallery SET visibility = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$visibility, $id, $userId]);
        } elseif ($src === null) {
            // 更新条件に user_id を必ず含める＝他人の作品 ID を渡されても 0 行で弾く（IDOR 防止）。
            // 画像を差し替えていない（$src === null）ときは src を更新せず既存画像を保持する。
            $stmt = $pdo->prepare("UPDATE gallery SET title = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $desc, $visibility, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE gallery SET title = ?, src = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $src, $desc, $visibility, $id, $userId]);
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

    // 削除も user_id 込みで限定。所有者でなければ 0 行＝404（他人の作品は消せず、存在も示唆しない）。
    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) {
        respondJson(['error' => '対象の作品が見つかりません'], 404);
    }
    respondJson(['deleted' => $id]);
}

respondJson(['error' => '許可されていないメソッドです'], 405);
