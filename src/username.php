<?php

function normalizeUsername(string $username): string
{
    return strtolower(trim($username));
}

function isValidUsername(string $username): bool
{
    return preg_match('/^[a-z0-9_]{3,20}$/', $username) === 1;
}

function isReservedUsername(string $username): bool
{
    static $reserved = [
        'admin',
        'api',
        'root',
        'login',
        'logout',
        'register',
        'verify',
        'reset',
        'forgot',
        'settings',
        'mypage',
        'favorites',
        'profile',
        'search',
        'gallery-list',
        'gallery-detail',
        'work-edit',
        'index',
        'contact',
        'policy',
        'base',
        'about',
        'user',
        'null',
    ];

    return in_array($username, $reserved, true);
}

function usernameExists(PDO $pdo, string $username, ?int $excludeUserId = null): bool
{
    if ($excludeUserId === null) {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $stmt->execute([$username, $excludeUserId]);
    }

    return (bool)$stmt->fetchColumn();
}

function generateUniqueUsername(PDO $pdo, int $userId, string $email): string
{
    $localPart = explode('@', $email)[0] ?? '';
    $base = preg_replace('/[^a-z0-9_]/', '', strtolower($localPart)) ?? '';
    $base = substr($base, 0, 20);

    if (strlen($base) < 3 || isReservedUsername($base)) {
        $base = 'user' . $userId;
    }

    $candidate = $base;
    $n = 1;
    while (usernameExists($pdo, $candidate, $userId)) {
        $n++;
        $candidate = substr($base, 0, 18) . $n;
        if ($n > 50) {
            $candidate = 'user' . $userId;
            break;
        }
    }

    return $candidate;
}
