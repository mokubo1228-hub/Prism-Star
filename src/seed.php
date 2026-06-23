<?php
/**
 * DB初期データ投入スクリプト
 * docker compose up 後に実行: docker compose exec app php /var/www/html/src/seed.php
 */

require_once __DIR__ . '/db.php';

$pdo = getDb();
$pdo->beginTransaction();

try {
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $users = [
        'demo@example.com' => ['name' => 'Demo User', 'github' => 'octocat'],
        'aoi@example.com'  => ['name' => 'Aoi', 'github' => null],
        'ren@example.com'  => ['name' => 'Ren', 'github' => null],
        'mio@example.com'  => ['name' => 'Mio', 'github' => null],
    ];

    $selectUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $insertUser = $pdo->prepare("INSERT INTO users (email, password_hash, name, github_username) VALUES (?, ?, ?, ?)");
    $updateUser = $pdo->prepare("UPDATE users SET name = ?, github_username = COALESCE(github_username, ?) WHERE id = ?");
    $userIds = [];

    foreach ($users as $email => $user) {
        $selectUser->execute([$email]);
        $id = $selectUser->fetchColumn();
        if ($id) {
            $updateUser->execute([$user['name'], $user['github'], $id]);
            $userIds[$email] = (int)$id;
        } else {
            $insertUser->execute([$email, $hash, $user['name'], $user['github']]);
            $userIds[$email] = (int)$pdo->lastInsertId();
        }
    }

    $works = [
        ['demo@example.com', '作品1',  'https://placehold.jp/300x200.png', 'これは作品1の説明です。静かな湖畔をテーマに、光と影の対比を意識して制作しました。', 'public', ['風景', '光']],
        ['aoi@example.com',  '作品2',  'https://placehold.jp/200x300.png', '都市の片隅に咲く小さな花をモチーフにした作品。無機質な街と自然の生命力の対比を表現しています。', 'public', ['都市', '花']],
        ['ren@example.com',  '作品3',  'https://placehold.jp/400x250.png', '朝焼けの空をイメージした抽象作品。暖色のグラデーションを重ね、時間の移ろいを描きました。', 'public', ['抽象', '空']],
        ['mio@example.com',  '作品4',  'https://placehold.jp/250x250.png', '過去と現在の交差をテーマにした作品。幾何学的な模様の中に、曖昧な記憶の断片を散りばめています。', 'public', ['抽象']],
        ['demo@example.com', '作品5',  'https://placehold.jp/350x200.png', '水面に映る世界を描いた幻想的な一枚。現実と虚構の境界をぼかすように表現しました。', 'private', ['水', '幻想']],
        ['aoi@example.com',  '作品6',  'https://placehold.jp/200x350.png', '縦構図で表現された森の静寂。深い緑と差し込む光のコントラストで奥行きを強調しています。', 'private', ['森', '光']],
        ['ren@example.com',  '作品7',  'https://placehold.jp/400x300.png', '風の流れを線で表現した抽象作品。リズムとテンポを感じさせる構成で、動きのある印象を与えます。', 'private', ['抽象', '風']],
        ['mio@example.com',  '作品8',  'https://placehold.jp/300x300.png', '日常の中に潜む美しさをテーマにした作品。シンプルな構図の中に穏やかな感情を込めました。', 'private', ['日常']],
        ['demo@example.com', '作品9',  'https://placehold.jp/280x180.png', '海辺の夕暮れをモチーフにした小品。橙と群青のグラデーションで静かな時間の流れを表現しています。', 'public', ['風景', '海']],
        ['aoi@example.com',  '作品10', 'https://placehold.jp/200x250.png', '夜の街灯をモチーフに、孤独と温もりを同時に感じさせるようなトーンで仕上げました。', 'public', ['夜', '都市']],
        ['ren@example.com',  '作品11', 'https://placehold.jp/400x200.png', '季節の移ろいを表す抽象画。春から冬へと変わる空気感を、色彩のグラデーションで表現しました。', 'public', ['抽象', '季節']],
        ['mio@example.com',  '作品12', 'https://placehold.jp/240x300.png', '空想上の街を描いた作品。建物の形や影の配置に、童話的な雰囲気を持たせています。', 'public', ['都市', '幻想']],
        ['demo@example.com', '作品13', 'https://placehold.jp/300x240.png', '雨上がりの窓を題材にした作品。ガラス越しの世界を淡いタッチで表現しました。', 'public', ['雨', '日常']],
        ['aoi@example.com',  '作品14', 'https://placehold.jp/200x200.png', 'ミニマルな構成で描いた静物画。余白と形のバランスを重視したシンプルな一枚です。', 'public', ['静物']],
        ['ren@example.com',  '作品15', 'https://placehold.jp/320x180.png', '夜空をイメージした作品。小さな光の粒をちりばめ、静寂と広がりを感じさせる構成になっています。', 'public', ['夜', '空']],
        ['mio@example.com',  '作品16', 'https://placehold.jp/360x220.png', '公開前の構成案。色の重なりと視線誘導を検証している非公開作品です。', 'private', ['試作', '抽象']],
    ];

    $selectWork = $pdo->prepare("SELECT id FROM gallery WHERE title = ? LIMIT 1");
    $insertWork = $pdo->prepare("INSERT INTO gallery (user_id, title, src, description, visibility) VALUES (?, ?, ?, ?, ?)");
    $updateWork = $pdo->prepare("UPDATE gallery SET user_id = ?, src = ?, description = ?, visibility = ? WHERE id = ?");
    $selectTag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
    $insertTag = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
    $deleteWorkTags = $pdo->prepare("DELETE FROM gallery_tags WHERE gallery_id = ?");
    $insertWorkTag = $pdo->prepare("INSERT IGNORE INTO gallery_tags (gallery_id, tag_id) VALUES (?, ?)");

    foreach ($works as $work) {
        [$email, $title, $src, $desc, $visibility, $tags] = $work;
        $userId = $userIds[$email];

        $selectWork->execute([$title]);
        $workId = $selectWork->fetchColumn();
        if ($workId) {
            $workId = (int)$workId;
            $updateWork->execute([$userId, $src, $desc, $visibility, $workId]);
        } else {
            $insertWork->execute([$userId, $title, $src, $desc, $visibility]);
            $workId = (int)$pdo->lastInsertId();
        }

        $deleteWorkTags->execute([$workId]);
        foreach ($tags as $tagName) {
            $selectTag->execute([$tagName]);
            $tagId = $selectTag->fetchColumn();
            if (!$tagId) {
                $insertTag->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            }
            $insertWorkTag->execute([$workId, (int)$tagId]);
        }
    }

    $pdo->commit();
    echo "v2 fixtureを投入しました: users=" . count($users) . " works=" . count($works) . "（全員 password123）\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
