<?php
require_once __DIR__ . '/../../src/github_client.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '許可されていないメソッドです']);
    exit;
}

$user = trim($_GET['user'] ?? '');
try {
    echo json_encode(fetchGithubRepos($user));
} catch (GithubClientException $e) {
    http_response_code($e->responseStatus());
    echo json_encode(['error' => $e->getMessage()]);
}
