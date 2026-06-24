<?php

class GithubClientException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $responseStatus = 502
    ) {
        parent::__construct($message);
    }

    public function responseStatus(): int
    {
        return $this->responseStatus;
    }
}

function fetchGithubRepos(string $username): array
{
    $user = trim($username);
    if ($user === '' || strlen($user) > 100 || !preg_match('/^[A-Za-z0-9-]+$/', $user)) {
        throw new GithubClientException('GitHubユーザー名が不正です', 400);
    }

    $url = 'https://api.github.com/users/' . rawurlencode($user) . '/repos?sort=updated&per_page=30';
    $headers = [
        'User-Agent: PrismStar',
        'Accept: application/vnd.github+json',
    ];

    $token = getenv('GITHUB_TOKEN') ?: '';
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
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
        throw new GithubClientException('GitHub APIへの接続に失敗しました', 502);
    }

    $data = json_decode($response, true);
    if ($statusCode >= 400) {
        $message = $statusCode === 404
            ? 'GitHubユーザーが見つかりません'
            : 'GitHub APIからリポジトリを取得できませんでした';
        throw new GithubClientException($message, $statusCode === 404 ? 404 : 502);
    }

    if (!is_array($data)) {
        throw new GithubClientException('GitHub APIの応答を読み取れませんでした', 502);
    }

    return array_map(static function (array $repo): array {
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
}
