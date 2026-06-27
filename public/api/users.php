<?php
require_once __DIR__ . '/../../src/session.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/username.php';

bootSession();

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
    requireCsrf();

    if (empty($_SESSION['user_id'])) {
        usersJson(['error' => 'ログインが必要です'], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    $userId = (int)$_SESSION['user_id'];
    $response = ['ok' => true];
    $pendingUsername = null;

    if (array_key_exists('username', $data)) {
        $pendingUsername = normalizeUsername((string)$data['username']);
        if (!isValidUsername($pendingUsername)) {
            usersJson(['error' => 'username は3〜20文字の英小文字・数字・_で入力してください'], 400);
        }
        if (isReservedUsername($pendingUsername)) {
            usersJson(['error' => 'その username は使えません'], 400);
        }
        if (usernameExists($pdo, $pendingUsername, $userId)) {
            usersJson(['error' => 'その username は既に使われています'], 409);
        }
    }

    if (array_key_exists('name', $data)) {
        $name = trim((string)$data['name']);
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($name === '' || $nameLength > 100) {
            usersJson(['error' => '表示名は1文字以上100文字以下で入力してください'], 400);
        }

        // アカウント更新は常にセッションの user_id だけに限定し、client から id を受け取らない。
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);
        $_SESSION['user_name'] = $name;
        $response['name'] = $name;
    }

    if (array_key_exists('github_username', $data)) {
        $githubUsername = trim((string)$data['github_username']);

        if ($githubUsername !== '' && (strlen($githubUsername) > 100 || !preg_match('/^[A-Za-z0-9-]+$/', $githubUsername))) {
            usersJson(['error' => 'GitHubユーザー名は英数字とハイフンで入力してください'], 400);
        }

        $value = $githubUsername === '' ? null : $githubUsername;
        $stmt = $pdo->prepare("UPDATE users SET github_username = ? WHERE id = ?");
        $stmt->execute([$value, $userId]);
        $response['github_username'] = $value;
    }

    if (array_key_exists('bio', $data)) {
        $bio = trim((string)$data['bio']);
        $bioLength = function_exists('mb_strlen') ? mb_strlen($bio) : strlen($bio);
        if ($bioLength > 1000) {
            usersJson(['error' => '自己紹介は1000文字以下で入力してください'], 400);
        }

        // 公開プロフィールの編集も対象はセッション user_id に固定し、client の id では他人を書き換えない。
        $value = $bio === '' ? null : $bio;
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([$value, $userId]);
        $response['bio'] = $value;
    }

    if ($pendingUsername !== null) {
        try {
            // username も本人の公開識別子。更新対象は常にセッション user_id に限定する。
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$pendingUsername, $userId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                usersJson(['error' => 'その username は既に使われています'], 409);
            }
            throw $e;
        }
        $response['username'] = $pendingUsername;
    }

    usersJson($response);
}

if ($method !== 'GET') {
    usersJson(['error' => '許可されていないメソッドです'], 405);
}

if (isset($_GET['u'])) {
    $lookupUsername = normalizeUsername((string)$_GET['u']);
    if (!isValidUsername($lookupUsername)) {
        usersJson(['error' => 'ユーザーが見つかりません'], 404);
    }
    $stmt = $pdo->prepare("SELECT id, name, username, github_username, bio FROM users WHERE username = ?");
    $stmt->execute([$lookupUsername]);
} else {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        usersJson(['error' => 'IDが不正です'], 400);
    }
    $stmt = $pdo->prepare("SELECT id, name, username, github_username, bio FROM users WHERE id = ?");
    $stmt->execute([$id]);
}
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
        g.source,
        g.source_url,
        COUNT(DISTINCT s.id) AS star_count,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags
    FROM gallery g
    LEFT JOIN stars s ON s.gallery_id = g.id
    LEFT JOIN gallery_tags gt ON gt.gallery_id = g.id
    LEFT JOIN tags t ON t.id = gt.tag_id
    WHERE g.user_id = ? AND g.visibility = 'public'
    GROUP BY g.id, g.user_id, g.title, g.src, g.description, g.visibility, g.source, g.source_url, g.created_at
    ORDER BY g.created_at DESC, g.id DESC
");
$id = (int)$user['id'];
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
    'username' => $user['username'],
    'github_username' => $user['github_username'],
    'bio' => $user['bio'],
    'total_stars' => $totalStars,
    'works' => $works,
]);
