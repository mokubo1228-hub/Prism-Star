<?php

declare(strict_types=1);

final class CsrfTest extends IntegrationTestCase
{
    public function testStateChangingRequestWithoutCsrfTokenIsRejected(): void
    {
        // 状態変更はログイン以前に CSRF で止める。拒否時に star が副作用として残らないことまで見る。
        $ownerId = $this->createUser('owner');
        $galleryId = $this->createWork($ownerId, 'csrf_target', 'public', $this->tagName());

        $response = $this->apiPostJson('/api/stars.php', ['gallery_id' => $galleryId]);

        $this->assertSame(403, $response['status'], $response['body']);
        $this->assertSame(0, $this->starCount($galleryId));
    }

    public function testStateChangingRequestWithCsrfButWithoutLoginIsRejected(): void
    {
        // 正しい CSRF token だけでは足りない。書き込み系は必ずログイン gate を通ることを固定する。
        $ownerId = $this->createUser('owner');
        $galleryId = $this->createWork($ownerId, 'auth_target', 'public', $this->tagName());
        $token = $this->fetchCsrfToken();

        $response = $this->apiPostJson('/api/stars.php', ['gallery_id' => $galleryId], [], $token);

        $this->assertSame(401, $response['status'], $response['body']);
        $this->assertSame(0, $this->starCount($galleryId));
    }

    public function testLoggedInRequestWithCsrfTokenCanStarPublicWork(): void
    {
        // 拒否系だけだと token 取得経路の破損に気づきにくいので、実ブラウザ経路の成功系も1本だけ置く。
        $ownerId = $this->createUser('owner');
        $actorId = $this->createUser('actor');
        $galleryId = $this->createWork($ownerId, 'star_target', 'public', $this->tagName());
        $token = $this->login($this->fixtureEmail('actor'));

        $response = $this->apiPostJson('/api/stars.php', ['gallery_id' => $galleryId], [], $token);

        $this->assertSame(200, $response['status'], $response['body']);
        $this->assertTrue($response['json']['ok'] ?? false);
        $this->assertSame($galleryId, $response['json']['gallery_id']);
        $this->assertSame(1, $this->starCount($galleryId, $actorId));
    }

    private function starCount(int $galleryId, ?int $userId = null): int
    {
        if ($userId === null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM stars WHERE gallery_id = ?");
            $stmt->execute([$galleryId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM stars WHERE gallery_id = ? AND user_id = ?");
            $stmt->execute([$galleryId, $userId]);
        }

        return (int)$stmt->fetchColumn();
    }
}
