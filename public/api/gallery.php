<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

// ---------- GET: 一覧 or 1件取得 ----------
if ($method === 'GET') {
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("
            SELECT
                g.id,
                g.user_id,
                u.name AS author,
                g.title,
                g.src,
                g.description AS `desc`,
                COUNT(s.id) AS star_count,
                MAX(CASE WHEN s.user_id = ? THEN 1 ELSE 0 END) AS starred
            FROM gallery g
            INNER JOIN users u ON u.id = g.user_id
            LEFT JOIN stars s ON s.gallery_id = g.id
            WHERE g.id = ?
            GROUP BY g.id, g.user_id, u.name, g.title, g.src, g.description
        ");
        $stmt->execute([$currentUserId, (int)$_GET['id']]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => '作品が見つかりません']);
            exit;
        }
        $row['star_count'] = (int)$row['star_count'];
        $row['starred'] = (bool)$row['starred'];
        echo json_encode($row);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                g.id,
                g.user_id,
                u.name AS author,
                g.title,
                g.src,
                g.description AS `desc`,
                COUNT(s.id) AS star_count,
                MAX(CASE WHEN s.user_id = ? THEN 1 ELSE 0 END) AS starred
            FROM gallery g
            INNER JOIN users u ON u.id = g.user_id
            LEFT JOIN stars s ON s.gallery_id = g.id
            GROUP BY g.id, g.user_id, u.name, g.title, g.src, g.description, g.created_at
            ORDER BY g.created_at DESC, g.id DESC
        ");
        $stmt->execute([$currentUserId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['star_count'] = (int)$row['star_count'];
            $row['starred'] = (bool)$row['starred'];
        }
        unset($row);
        echo json_encode($rows);
    }
    exit;
}

// ---------- POST: 新規投稿（要ログイン） ----------
if ($method === 'POST') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'ログインが必要です']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $title = trim($data['title'] ?? '');
    $src   = trim($data['src'] ?? '');
    $desc  = trim($data['desc'] ?? '');

    if ($title === '' || $src === '') {
        http_response_code(400);
        echo json_encode(['error' => 'タイトルと画像URLは必須です']);
        exit;
    }

    $parsed = parse_url($src);
    if (!$parsed || !in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
        http_response_code(400);
        echo json_encode(['error' => '画像URLはhttp://またはhttps://で始まるURLを入力してください']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO gallery (user_id, title, src, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $title, $src, $desc]);

    $newId = (int)$pdo->lastInsertId();
    echo json_encode([
        'id'      => $newId,
        'user_id' => (int)$_SESSION['user_id'],
        'author'  => $_SESSION['user_name'],
        'title'   => $title,
        'src'     => $src,
        'desc'    => $desc,
        'star_count' => 0,
        'starred'    => false,
    ]);
    exit;
}

// ---------- DELETE: 削除（要ログイン） ----------
if ($method === 'DELETE') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'ログインが必要です']);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'IDが不正です']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => '対象の作品が見つかりません']);
        exit;
    }

    echo json_encode(['deleted' => $id]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => '許可されていないメソッドです']);
