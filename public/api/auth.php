<?php
require_once __DIR__ . '/../../src/session.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/username.php';

bootSession();

header('Content-Type: application/json; charset=utf-8');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function authJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function smtpRead($socket): string
{
    $response = '';
    while (($line = fgets($socket, 512)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtpCommand($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
    smtpRead($socket);
}

// ローカル開発の確認/再設定メールは MailHog（docker-compose）へ直接 SMTP で投げる最小実装。
// 外部に実送信しない catch-all 前提なので、ライブラリ依存を増やさず fsockopen で済ませている。
function sendTokenMail(string $email, string $subject, string $body): bool
{
    $host = getenv('SMTP_HOST') ?: 'mailhog';
    $port = (int)(getenv('SMTP_PORT') ?: 1025);
    $from = getenv('MAIL_FROM') ?: 'no-reply@prismstar.local';

    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$socket) {
        return false;
    }

    smtpRead($socket);
    smtpCommand($socket, 'HELO prismstar.local');
    smtpCommand($socket, 'MAIL FROM:<' . $from . '>');
    smtpCommand($socket, 'RCPT TO:<' . $email . '>');
    smtpCommand($socket, 'DATA');

    $message = [
        'From: PrismStar <' . $from . '>',
        'To: <' . $email . '>',
        'Subject: ' . str_replace(["\r", "\n"], '', $subject),
        'Content-Type: text/plain; charset=UTF-8',
        '',
        $body,
    ];
    $payload = str_replace("\n.", "\n..", implode("\r\n", $message));
    fwrite($socket, $payload . "\r\n.\r\n");
    smtpRead($socket);
    smtpCommand($socket, 'QUIT');
    fclose($socket);

    return true;
}

function appUrl(): string
{
    return rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
}

function sendVerificationMail(string $email, string $token): bool
{
    $verifyUrl = appUrl() . '/verify.php?token=' . rawurlencode($token);
    $body = "PrismStarの登録を続けるには、以下のURLを開いてください。\r\n\r\n"
        . $verifyUrl . "\r\n\r\n"
        . "このURLの有効期限は24時間です。";

    return sendTokenMail($email, 'PrismStar registration verification', $body);
}

function sendResetMail(string $email, string $token): bool
{
    $resetUrl = appUrl() . '/reset.php?token=' . rawurlencode($token);
    $body = "PrismStarのパスワードを再設定するには、以下のURLを開いてください。\r\n\r\n"
        . $resetUrl . "\r\n\r\n"
        . "このURLの有効期限は1時間です。";

    return sendTokenMail($email, 'PrismStar password reset', $body);
}

if ($method === 'GET' && $action === 'status') {
    if (!empty($_SESSION['user_id'])) {
        authJson([
            'loggedIn' => true,
            'user' => [
                'id' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
            ],
        ]);
    }
    authJson(['loggedIn' => false]);
}

if ($method !== 'POST') {
    authJson(['error' => '不正なリクエストです'], 400);
}

requireCsrf();

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    authJson(['ok' => true]);
}

if ($action === 'change-password') {
    // ログイン中の本人がその場でパスワードを変える。対象は常にセッションの user_id（client の id を信用しない）。
    // 現行パスワードの再確認を必須にし、セッションだけ奪った相手や離席端末からの無断変更を防ぐ。
    if (empty($_SESSION['user_id'])) {
        authJson(['error' => 'ログインが必要です'], 401);
    }

    $data = readJsonBody();
    $currentPassword = (string)($data['current_password'] ?? '');
    $newPassword = (string)($data['new_password'] ?? '');

    if (strlen($newPassword) < 8) {
        authJson(['error' => '新しいパスワードは8文字以上で入力してください'], 400);
    }

    $userId = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $passwordHash = $stmt->fetchColumn();

    if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
        authJson(['error' => '現在のパスワードが正しくありません'], 401);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $userId]);

    // 資格情報を変えたらセッション ID を更新する（固定化対策・古い ID を無効化）。
    session_regenerate_id(true);
    authJson(['ok' => true, 'message' => 'パスワードを変更しました。']);
}

if ($action === 'delete-account') {
    if (empty($_SESSION['user_id'])) {
        authJson(['error' => 'ログインが必要です'], 401);
    }

    $data = readJsonBody();
    $currentPassword = (string)($data['current_password'] ?? '');
    $userId = (int)$_SESSION['user_id'];

    $pdo->beginTransaction();
    try {
        // 削除対象はセッションの本人だけ。リクエストから user_id を受けず、他人を消す経路を作らない。
        $stmt = $pdo->prepare("SELECT email, password_hash FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            authJson(['error' => 'ログインが必要です'], 401);
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $pdo->rollBack();
            authJson(['error' => '現在のパスワードが正しくありません'], 403);
        }

        $deletePending = $pdo->prepare("DELETE FROM pending_registrations WHERE email = ?");
        $deletePending->execute([$user['email']]);

        $galleryIdsStmt = $pdo->prepare("SELECT id FROM gallery WHERE user_id = ?");
        $galleryIdsStmt->execute([$userId]);
        $galleryIds = array_map('intval', $galleryIdsStmt->fetchAll(PDO::FETCH_COLUMN));

        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

        if ($galleryIds) {
            // 本番想定は FK cascade だが、古いローカル DB でも退会が詰まらないよう同一 transaction で明示削除する。
            $placeholders = implode(',', array_fill(0, count($galleryIds), '?'));
            $pdo->prepare("DELETE FROM gallery_tags WHERE gallery_id IN ($placeholders)")->execute($galleryIds);
            $pdo->prepare("DELETE FROM stars WHERE user_id = ? OR gallery_id IN ($placeholders)")
                ->execute(array_merge([$userId], $galleryIds));
            $pdo->prepare("DELETE FROM gallery WHERE user_id = ?")->execute([$userId]);
        } else {
            $pdo->prepare("DELETE FROM stars WHERE user_id = ?")->execute([$userId]);
        }

        $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteUser->execute([$userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $_SESSION = [];
    session_destroy();
    authJson(['ok' => true]);
}

if ($action === 'login') {
    $data = readJsonBody();
    $email = trim((string)($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
        authJson(['error' => 'IDとパスワードを入力してください'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, email, password_hash, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        authJson(['error' => 'メールアドレスまたはパスワードが正しくありません'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];

    authJson([
        'ok' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ],
    ]);
}

// 登録リクエスト（double opt-in の①）。メールアドレスの登録有無を外から探られない（enumeration 対策）よう、
// 「既に登録済み」「未登録で受理」「送信失敗」のどれでも同じ neutral 応答を返し、失敗はログにだけ残す。
if ($action === 'register-request') {
    $data = readJsonBody();
    $email = trim((string)($data['email'] ?? ''));
    $neutral = ['ok' => true, 'message' => '登録できる場合は確認メールを送信しました。'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        authJson(['error' => 'メールアドレスの形式が正しくありません'], 400);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        authJson($neutral);
    }

    // トークンは 256bit 乱数をメールで配り、DB には sha256 ハッシュだけ保存する。
    // こうすれば DB が漏れても元トークンは復元できず、URL を知る本人だけが完了できる。期限と単回使用も前提。
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare("DELETE FROM pending_registrations WHERE email = ?");
        $delete->execute([$email]);
        $insert = $pdo->prepare("INSERT INTO pending_registrations (email, token_hash, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$email, $tokenHash, $expiresAt]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    if (!sendVerificationMail($email, $token)) {
        error_log('Registration verification mail send failed for ' . $email);
    }

    authJson($neutral);
}

if ($action === 'register-complete') {
    $data = readJsonBody();
    $token = trim((string)($data['token'] ?? ''));
    $name = trim((string)($data['name'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($token === '' || $name === '' || strlen($password) < 8) {
        authJson(['error' => '表示名と8文字以上のパスワードを入力してください'], 400);
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id, email FROM pending_registrations WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    $pending = $stmt->fetch();
    if (!$pending) {
        authJson(['error' => '確認URLが無効または期限切れです'], 400);
    }

    // email の一意性は「完了時」に最終チェックする。①リクエスト〜③完了の間に同じアドレスで
    // 別経路から登録され得る（TOCTOU）ため、ここで再確認してから本登録し、二重登録を防ぐ。
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$pending['email']]);
    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM pending_registrations WHERE id = ?")->execute([$pending['id']]);
        authJson(['error' => 'このメールアドレスは既に登録されています'], 409);
    }

    $pdo->beginTransaction();
    try {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
        $insert->execute([$pending['email'], $passwordHash, $name]);
        $userId = (int)$pdo->lastInsertId();
        $username = generateUniqueUsername($pdo, $userId, $pending['email']);
        $updateUsername = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $updateUsername->execute([$username, $userId]);
        $pdo->prepare("DELETE FROM pending_registrations WHERE id = ?")->execute([$pending['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $pending['email'];
    $_SESSION['user_name'] = $name;

    authJson([
        'ok' => true,
        'user' => [
            'id' => $userId,
            'email' => $pending['email'],
            'name' => $name,
            'username' => $username,
        ],
    ]);
}

// パスワード再設定リクエスト。登録と同じ理由で、登録の有無や送信可否を漏らさない neutral 応答に揃える。
if ($action === 'reset-request') {
    $data = readJsonBody();
    $email = trim((string)($data['email'] ?? ''));
    $neutral = ['ok' => true, 'message' => '登録済みの場合は再設定メールを送信しました。'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        authJson(['error' => 'メールアドレスの形式が正しくありません'], 400);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        authJson($neutral);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $delete->execute([$user['id']]);
        $insert = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$user['id'], $tokenHash, $expiresAt]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    if (!sendResetMail($email, $token)) {
        error_log('Password reset mail send failed for user_id=' . (int)$user['id']);
    }

    authJson($neutral);
}

if ($action === 'reset-complete') {
    $data = readJsonBody();
    $token = trim((string)($data['token'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($token === '' || strlen($password) < 8) {
        authJson(['error' => '8文字以上の新しいパスワードを入力してください'], 400);
    }

    // 再設定対象のユーザーは「トークン照合で得た user_id」だけを使う（リクエスト本文に user_id を持たせない）。
    // 他人のトークンを知らない限り他人のパスワードは変えられない。期限切れトークンもここで弾く。
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch();
    if (!$reset) {
        authJson(['error' => '再設定URLが無効または期限切れです'], 400);
    }

    $pdo->beginTransaction();
    try {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $update->execute([$passwordHash, $reset['user_id']]);
        $delete = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
        $delete->execute([$reset['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    authJson(['ok' => true, 'message' => 'パスワードを再設定しました。ログインしてください。']);
}

authJson(['error' => '不正なリクエストです'], 400);
