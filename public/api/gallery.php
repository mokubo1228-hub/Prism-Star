<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];

// ---------- GET: 一覧 or 1件取得 ----------
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT id, user_id, title, src, description AS `desc` FROM gallery WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => '作品が見つかりません']);
            exit;
        }
        echo json_encode($row);
    } else {
        $rows = $pdo->query("SELECT id, title, src, description AS `desc` FROM gallery ORDER BY id")->fetchAll();
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
    echo json_encode(['id' => $newId, 'title' => $title, 'src' => $src, 'desc' => $desc]);
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
