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

    if ($action === 'register') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $name     = trim($data['name'] ?? '');

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => '名前を入力してください']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'メールアドレスの形式が正しくありません']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'パスワードは8文字以上で入力してください']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'このメールアドレスは既に登録されています']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
        $stmt->execute([$email, $passwordHash, $name]);

        $userId = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = $name;

        echo json_encode([
            'ok'   => true,
            'user' => [
                'id'    => $userId,
                'email' => $email,
                'name'  => $name,
            ],
        ]);
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
