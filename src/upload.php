<?php

const DEFAULT_AVATAR_PATH = 'image/default-avatar.svg';

function uploadError(string $message, int $status): void
{
    if (function_exists('respondJson')) {
        respondJson(['error' => $message], $status);
    }
    if (function_exists('usersJson')) {
        usersJson(['error' => $message], $status);
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
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
        uploadError('画像アップロードに失敗しました', 400);
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        uploadError('画像サイズは5MB以下にしてください', 400);
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
        uploadError('アップロードできる画像は jpg/png/webp/gif です', 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== $allowed[$ext]) {
        uploadError('画像ファイルの形式が不正です', 400);
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        uploadError('アップロード先を作成できません', 500);
    }

    $name = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    $path = $uploadDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        uploadError('画像を保存できませんでした', 500);
    }

    return 'uploads/' . $name;
}

function avatarUrl(?string $path): string
{
    return ($path !== null && $path !== '') ? $path : DEFAULT_AVATAR_PATH;
}

function deleteStoredUpload(?string $path): void
{
    if ($path === null || !str_starts_with($path, 'uploads/')) {
        return;
    }

    $name = basename($path);
    if ($name === '' || $name !== substr($path, strlen('uploads/'))) {
        return;
    }

    $fullPath = dirname(__DIR__) . '/public/uploads/' . $name;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}
