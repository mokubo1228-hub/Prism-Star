<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;
    protected string $marker;
    private ?string $cookieJar = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = getDb();
        // seed に依存せず、失敗再実行でも同じテストが自分の fixture だけを掃除できるよう一意 prefix を持つ。
        $this->marker = '__it_' . strtolower((new ReflectionClass($this))->getShortName()) . '_' . strtolower($this->name()) . '_';
        $this->cleanupMarker();
    }

    protected function tearDown(): void
    {
        $this->cleanupMarker();
        if ($this->cookieJar !== null && file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
        parent::tearDown();
    }

    protected function createUser(string $suffix = 'user'): int
    {
        $email = $this->marker . $suffix . '@example.test';
        $username = 'it-' . substr(hash('sha256', $this->marker . $suffix), 0, 20);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, name, username) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $email,
            password_hash('password123', PASSWORD_DEFAULT),
            'Integration ' . $suffix,
            $username,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    protected function createWork(int $userId, string $suffix, string $visibility, string $tagName): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO gallery (user_id, title, src, description, visibility, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $this->marker . $suffix,
            'https://placehold.jp/300x200.png',
            'Integration test fixture',
            $visibility,
            '2024-01-01 00:00:00',
        ]);
        $galleryId = (int)$this->pdo->lastInsertId();
        $tagId = $this->tagId($tagName);

        $stmt = $this->pdo->prepare("INSERT INTO gallery_tags (gallery_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$galleryId, $tagId]);

        return $galleryId;
    }

    protected function tagName(string $suffix = 'tag'): string
    {
        return $this->marker . $suffix;
    }

    protected function apiGet(string $path, array $query = []): array
    {
        $url = $this->baseUrl() . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->request('GET', $url);
    }

    protected function apiPostJson(string $path, array $query = [], array $payload = [], ?string $csrfToken = null): array
    {
        $url = $this->baseUrl() . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = ['Content-Type: application/json'];
        if ($csrfToken !== null) {
            $headers[] = 'X-CSRF-Token: ' . $csrfToken;
        }

        return $this->request('POST', $url, $headers, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    protected function fetchCsrfToken(): string
    {
        // CSRF token は本番ページの meta から取る。テスト専用 API を足さず、実際のブラウザ経路を固定する。
        $response = $this->request('GET', $this->baseUrl() . '/gallery-list.php');
        $this->assertSame(200, $response['status'], $response['body']);
        $matched = preg_match('/<meta name="csrf-token" content="([^"]+)">/', $response['body'], $matches);
        $this->assertSame(1, $matched, 'CSRF token meta tag was not found.');

        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }

    protected function login(string $email, string $password = 'password123'): string
    {
        $token = $this->fetchCsrfToken();
        $response = $this->apiPostJson('/api/auth.php', ['action' => 'login'], [
            'email' => $email,
            'password' => $password,
        ], $token);

        $this->assertSame(200, $response['status'], $response['body']);
        $this->assertTrue($response['json']['ok'] ?? false);

        return $token;
    }

    protected function fixtureEmail(string $suffix = 'user'): string
    {
        return $this->marker . $suffix . '@example.test';
    }

    private function tagId(string $name): int
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);

        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);

        return (int)$stmt->fetchColumn();
    }

    private function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        // API ファイルを直接 include せず HTTP で通すことで、session/cookie/CSRF/header を本番と同じ境界で検証する。
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_COOKIEJAR => $this->cookieJar(),
            CURLOPT_COOKIEFILE => $this->cookieJar(),
        ]);

        if ($headers !== []) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);
        if ($responseBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            $this->fail($error);
        }

        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $json = json_decode($responseBody, true);

        return [
            'status' => $status,
            'body' => $responseBody,
            'json' => is_array($json) ? $json : null,
        ];
    }

    private function cookieJar(): string
    {
        if ($this->cookieJar === null) {
            $this->cookieJar = tempnam(sys_get_temp_dir(), 'prismstar-cookie-');
        }

        return $this->cookieJar;
    }

    private function baseUrl(): string
    {
        return rtrim(getenv('APP_BASE_URL') ?: 'http://localhost', '/');
    }

    private function cleanupMarker(): void
    {
        // "_" は LIKE のワイルドカードなので必ず escape する。seed の通常タグを巻き込まないための後始末ガード。
        $like = $this->escapedLikePrefix($this->marker);
        $this->pdo->prepare("DELETE FROM gallery WHERE title LIKE ? ESCAPE '\\\\'")->execute([$like]);
        $this->pdo->prepare("DELETE FROM users WHERE email LIKE ? ESCAPE '\\\\'")->execute([$like]);
        $this->pdo->prepare("DELETE FROM tags WHERE name LIKE ? ESCAPE '\\\\'")->execute([$like]);
    }

    private function escapedLikePrefix(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '%' => '\\%',
            '_' => '\\_',
        ]) . '%';
    }
}
