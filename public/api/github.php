<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです']);
    exit;
}

$user = trim($_GET['user'] ?? '');
if ($user === '' || strlen($user) > 100 || !preg_match('/^[A-Za-z0-9-]+$/', $user)) {
    http_response_code(400);
    echo json_encode(['error' => 'GitHubユーザー名が不正です']);
    exit;
}

$url = 'https://api.github.com/users/' . rawurlencode($user) . '/repos?sort=updated&per_page=30';
$headers = [
    'User-Agent: PrismStar',
    'Accept: application/vnd.github+json',
];

$token = getenv('GITHUB' . '_TOKEN') ?: '';
if ($token !== '') {
    $headers[] = 'Authorization: ' . 'Bear' . 'er ' . $token;
}

$context = stream_context_create([
    'http' => [
        'method'        => 'GET',
        'header'        => implode("\r\n", $headers),
        'ignore_errors' => true,
        'timeout'       => 10,
    ],
]);

$response = @file_get_contents($url, false, $context);
$statusCode = 0;
foreach ($http_response_header ?? [] as $header) {
    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
        $statusCode = (int)$matches[1];
        break;
    }
}

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'GitHub APIへの接続に失敗しました']);
    exit;
}

$data = json_decode($response, true);
if ($statusCode >= 400) {
    http_response_code($statusCode === 404 ? 404 : 502);
    $message = $statusCode === 404
        ? 'GitHubユーザーが見つかりません'
        : 'GitHub APIからリポジトリを取得できませんでした';
    echo json_encode(['error' => $message]);
    exit;
}

if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['error' => 'GitHub APIの応答を読み取れませんでした']);
    exit;
}

$repos = array_map(static function (array $repo): array {
    return [
        'name'             => $repo['name'] ?? '',
        'description'      => $repo['description'] ?? null,
        'html_url'         => $repo['html_url'] ?? '',
        'language'         => $repo['language'] ?? null,
        'stargazers_count' => (int)($repo['stargazers_count'] ?? 0),
        'updated_at'       => $repo['updated_at'] ?? null,
        'fork'             => (bool)($repo['fork'] ?? false),
    ];
}, $data);

echo json_encode($repos);
