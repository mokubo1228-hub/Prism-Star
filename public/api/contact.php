<?php
require_once __DIR__ . '/../../src/session.php';
require_once __DIR__ . '/../../src/db.php';

bootSession();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです']);
    exit;
}

requireCsrf();

$data = json_decode(file_get_contents('php://input'), true);

$firstName = trim($data['first_name'] ?? '');
$lastName  = trim($data['last_name'] ?? '');
$email     = trim($data['email'] ?? '');
$message   = trim($data['message'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['error' => '必須項目を入力してください']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'メールアドレスの形式が正しくありません']);
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare("INSERT INTO contacts (first_name, last_name, email, message) VALUES (?, ?, ?, ?)");
$stmt->execute([$firstName, $lastName, $email, $message]);

echo json_encode(['ok' => true]);
