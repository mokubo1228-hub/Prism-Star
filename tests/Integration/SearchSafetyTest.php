<?php

declare(strict_types=1);

final class SearchSafetyTest extends IntegrationTestCase
{
    public function testPrivateWorksAreNotExposedInSearch(): void
    {
        // 検索は discovery 面なので、未ログインでも「公開のみ」を守る。private は total にも含めない。
        $userId = $this->createUser();
        $tag = $this->tagName();
        $publicId = $this->createWork($userId, 'public_work', 'public', $tag);
        $privateId = $this->createWork($userId, 'private_work', 'private', $tag);

        $response = $this->apiGet('/api/search.php', ['tag' => $tag]);

        $this->assertSame(200, $response['status'], $response['body']);
        $this->assertSame(1, $response['json']['total']);
        $ids = array_column($response['json']['results'], 'id');
        $this->assertContains($publicId, $ids);
        $this->assertNotContains($privateId, $ids);
    }

    public function testLoggedInUserDoesNotSeeOwnWorksInSearch(): void
    {
        // 自作はマイページで扱う前提。ログイン状態だけを変えて、検索の自分除外が効くことを固定する。
        $userId = $this->createUser('owner');
        $tag = $this->tagName();
        $workId = $this->createWork($userId, 'own_public_work', 'public', $tag);

        $guestResponse = $this->apiGet('/api/search.php', ['tag' => $tag]);
        $this->assertSame(1, $guestResponse['json']['total']);
        $this->assertContains($workId, array_column($guestResponse['json']['results'], 'id'));

        $this->login($this->fixtureEmail('owner'));
        $ownResponse = $this->apiGet('/api/search.php', ['tag' => $tag]);

        $this->assertSame(200, $ownResponse['status'], $ownResponse['body']);
        $this->assertSame(0, $ownResponse['json']['total']);
        $this->assertSame([], $ownResponse['json']['results']);
    }
}
