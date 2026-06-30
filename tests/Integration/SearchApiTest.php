<?php

declare(strict_types=1);

final class SearchApiTest extends IntegrationTestCase
{
    public function testWorksSearchPaginationContract(): void
    {
        $userId = $this->createUser();
        $tag = $this->tagName();
        // 13件にすると per_page=10 だけ2ページ化し、default 30/50 との境界差を薄い fixture で確認できる。
        for ($i = 1; $i <= 13; $i++) {
            $this->createWork($userId, sprintf('work_%02d', $i), 'public', $tag);
        }

        $default = $this->apiGet('/api/search.php', ['tag' => $tag]);
        $this->assertSame(200, $default['status'], $default['body']);
        $this->assertSame(['type', 'results', 'total', 'page', 'perPage', 'totalPages', 'hasPrev', 'hasNext'], array_keys($default['json']));
        $this->assertSame('works', $default['json']['type']);
        $this->assertSame(13, $default['json']['total']);
        $this->assertSame(1, $default['json']['page']);
        $this->assertSame(30, $default['json']['perPage']);
        $this->assertSame(1, $default['json']['totalPages']);
        $this->assertFalse($default['json']['hasPrev']);
        $this->assertFalse($default['json']['hasNext']);
        $this->assertCount(13, $default['json']['results']);

        foreach ([10, 30, 50] as $perPage) {
            $response = $this->apiGet('/api/search.php', ['tag' => $tag, 'per_page' => $perPage]);
            $this->assertSame($perPage, $response['json']['perPage']);
            $this->assertSame((int)ceil(13 / $perPage), $response['json']['totalPages']);
            $this->assertSame($response['json']['page'] > 1, $response['json']['hasPrev']);
            $this->assertSame($response['json']['page'] < $response['json']['totalPages'], $response['json']['hasNext']);
        }

        foreach ([7, 999] as $invalidPerPage) {
            $response = $this->apiGet('/api/search.php', ['tag' => $tag, 'per_page' => $invalidPerPage]);
            $this->assertSame(30, $response['json']['perPage']);
        }

        $clamped = $this->apiGet('/api/search.php', ['tag' => $tag, 'page' => 99999, 'per_page' => 10]);
        $this->assertSame(2, $clamped['json']['page']);
        $this->assertSame(2, $clamped['json']['totalPages']);
        $this->assertTrue($clamped['json']['hasPrev']);
        $this->assertFalse($clamped['json']['hasNext']);
        $this->assertCount(3, $clamped['json']['results']);
    }

    public function testWorksSearchZeroResultsContract(): void
    {
        // 0件でも page/totalPages は1に正規化する。フロントのページャが特別扱いを増やさないための契約。
        $response = $this->apiGet('/api/search.php', ['q' => $this->marker . 'missing']);

        $this->assertSame(200, $response['status'], $response['body']);
        $this->assertSame(0, $response['json']['total']);
        $this->assertSame(1, $response['json']['page']);
        $this->assertSame(30, $response['json']['perPage']);
        $this->assertSame(1, $response['json']['totalPages']);
        $this->assertFalse($response['json']['hasPrev']);
        $this->assertFalse($response['json']['hasNext']);
        $this->assertSame([], $response['json']['results']);
    }
}
