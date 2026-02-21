<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ---------- GET: ログイン状態確認 ----------
if ($method === 'GET' && $action === 'status') {
    if (!empty($_SESSION['user_id'])) {
        echo json_encode([
            'loggedIn' => true,
            'user'     => [
                'id'    => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name'  => $_SESSION['user_name'],
            ],
        ]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
    exit;
}

// ---------- POST: ログイン / ログアウト ----------
if ($method === 'POST') {
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'login') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'IDとパスワードを入力してください']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, email, password_hash, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'メールアドレスまたはパスワードが正しくありません']);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['name'];

        echo json_encode([
            'ok'   => true,
            'user' => [
                'id'    => $user['id'],
                'email' => $user['email'],
                'name'  => $user['name'],
            ],
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => '不正なリクエストです']);
