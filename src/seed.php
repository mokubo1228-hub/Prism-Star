<?php
/**
 * DB初期データ投入スクリプト
 * docker compose up 後に実行: docker compose exec app php /var/www/html/src/seed.php
 */

require_once __DIR__ . '/db.php';

$pdo = getDb();
$pdo->beginTransaction();

function pick(array $items, int $index): string
{
    return $items[$index % count($items)];
}

function dateFromIndex(int $index): string
{
    $day = intdiv($index, 12);
    $hour = 8 + ($index % 10);
    $minute = ($index * 7) % 60;
    return date('Y-m-d H:i:s', strtotime("2023-01-01 +{$day} days {$hour} hours {$minute} minutes"));
}

function imageUrl(int $index): string
{
    $widths = [300, 320, 340, 360, 380, 400];
    $heights = [200, 220, 240, 260, 280];
    return 'https://placehold.jp/' . pick($widths, $index) . 'x' . pick($heights, intdiv($index, 2)) . '.png';
}

try {
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $users = [
        'demo@example.com' => ['name' => 'Demo User', 'username' => 'demo', 'github' => 'octocat'],
        'aoi@example.com'  => ['name' => 'Aoi', 'username' => 'aoi', 'github' => null],
        'ren@example.com'  => ['name' => 'Ren', 'username' => 'ren', 'github' => null],
        'mio@example.com'  => ['name' => 'Mio', 'username' => 'mio', 'github' => null],
        'user05@example.com' => ['name' => 'Haru', 'username' => 'haru', 'github' => null],
        'user06@example.com' => ['name' => 'Sora', 'username' => 'sora', 'github' => null],
        'user07@example.com' => ['name' => 'Yui', 'username' => 'yui', 'github' => null],
        'user08@example.com' => ['name' => 'Kaito', 'username' => 'kaito', 'github' => null],
        'user09@example.com' => ['name' => 'Nana', 'username' => 'nana', 'github' => null],
        'user10@example.com' => ['name' => 'Riku', 'username' => 'riku', 'github' => null],
        'user11@example.com' => ['name' => 'Akari', 'username' => 'akari', 'github' => null],
        'user12@example.com' => ['name' => 'Toma', 'username' => 'toma', 'github' => null],
        'user13@example.com' => ['name' => 'Mei', 'username' => 'mei', 'github' => null],
        'user14@example.com' => ['name' => 'Itsuki', 'username' => 'itsuki', 'github' => null],
        'user15@example.com' => ['name' => 'Hina', 'username' => 'hina', 'github' => null],
        'user16@example.com' => ['name' => 'Yuna', 'username' => 'yuna', 'github' => null],
        'user17@example.com' => ['name' => 'Minato', 'username' => 'minato', 'github' => null],
        'user18@example.com' => ['name' => 'Shion', 'username' => 'shion', 'github' => null],
        'user19@example.com' => ['name' => 'Ayaka', 'username' => 'ayaka', 'github' => null],
        'user20@example.com' => ['name' => 'Nao', 'username' => 'nao', 'github' => null],
        'user21@example.com' => ['name' => 'Koharu', 'username' => 'koharu', 'github' => null],
        'user22@example.com' => ['name' => 'Hayate', 'username' => 'hayate', 'github' => null],
        'user23@example.com' => ['name' => 'Rin', 'username' => 'rin', 'github' => null],
        'user24@example.com' => ['name' => 'Yuto', 'username' => 'yuto', 'github' => null],
        'user25@example.com' => ['name' => 'Sena', 'username' => 'sena', 'github' => null],
        'user26@example.com' => ['name' => 'Noa', 'username' => 'noa', 'github' => null],
        'user27@example.com' => ['name' => 'Hinata', 'username' => 'hinata', 'github' => null],
        'user28@example.com' => ['name' => 'Kaede', 'username' => 'kaede', 'github' => null],
        'user29@example.com' => ['name' => 'Ema', 'username' => 'ema', 'github' => null],
        'user30@example.com' => ['name' => 'Nagi', 'username' => 'nagi', 'github' => null],
        'user31@example.com' => ['name' => 'Rio', 'username' => 'rio', 'github' => null],
        'user32@example.com' => ['name' => 'Yuina', 'username' => 'yuina', 'github' => null],
        'user33@example.com' => ['name' => 'Taiga', 'username' => 'taiga', 'github' => null],
        'user34@example.com' => ['name' => 'Maki', 'username' => 'maki', 'github' => null],
        'user35@example.com' => ['name' => 'Rina', 'username' => 'rina', 'github' => null],
        'user36@example.com' => ['name' => 'Sou', 'username' => 'sou', 'github' => null],
        'user37@example.com' => ['name' => 'Fuka', 'username' => 'fuka', 'github' => null],
        'user38@example.com' => ['name' => 'Asahi', 'username' => 'asahi', 'github' => null],
        'user39@example.com' => ['name' => 'Chika', 'username' => 'chika', 'github' => null],
        'user40@example.com' => ['name' => 'Keito', 'username' => 'keito', 'github' => null],
    ];

    $selectUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $insertUser = $pdo->prepare("INSERT INTO users (email, password_hash, name, username, github_username) VALUES (?, ?, ?, ?, ?)");
    $updateUser = $pdo->prepare("UPDATE users SET name = ?, username = ?, github_username = COALESCE(github_username, ?) WHERE id = ?");
    $userIds = [];

    foreach ($users as $email => $user) {
        $selectUser->execute([$email]);
        $id = $selectUser->fetchColumn();
        if ($id) {
            $updateUser->execute([$user['name'], $user['username'], $user['github'], $id]);
            $userIds[$email] = (int)$id;
        } else {
            $insertUser->execute([$email, $hash, $user['name'], $user['username'], $user['github']]);
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
        ['aoi@example.com',  '作品17', 'https://placehold.jp/300x210.png', '朝の商店街を切り取った作品。看板の色と人影のリズムで、歩き出す前の都市の空気を表現しました。', 'public', ['都市', '朝']],
        ['ren@example.com',  '作品18', 'https://placehold.jp/360x240.png', '雨粒が水面に広がる瞬間を抽象化した作品。円の重なりで静かな波紋を描いています。', 'public', ['雨', '抽象']],
        ['mio@example.com',  '作品19', 'https://placehold.jp/280x220.png', '夕方の海沿いをモチーフにした作品。遠くの雲と水平線を淡い色で重ねました。', 'public', ['海', '空']],
        ['aoi@example.com',  '作品20', 'https://placehold.jp/260x300.png', '路地裏の鉢植えを題材にした小品。限られた光の中で花が立ち上がる様子を描いています。', 'public', ['花', '光']],
        ['ren@example.com',  '作品21', 'https://placehold.jp/400x240.png', '夜の駅前を線と面で再構成した作品。反射する照明と人の流れを幾何学的に表現しました。', 'public', ['夜', '都市']],
        ['mio@example.com',  '作品22', 'https://placehold.jp/300x260.png', '小さな窓から見える空を描いた作品。室内の静けさと外の広がりを対比させています。', 'public', ['空', '日常']],
        ['aoi@example.com',  '作品23', 'https://placehold.jp/340x220.png', '公園の木漏れ日をテーマにした作品。緑の重なりと白い余白で風景の奥行きを作りました。', 'public', ['風景', '光']],
        ['ren@example.com',  '作品24', 'https://placehold.jp/320x240.png', '夜明け前の雨雲をイメージした抽象作品。青と灰色の層で湿度のある空気を表現しています。', 'public', ['雨', '空', '抽象']],
    ];

    $themes = ['雨', '都市', '空', '抽象', '光', '夜', '花', '風景', '海', '森', '静物', '記憶', '余白', '輪郭', '水', '朝', '季節', '風', '幻想', '日常'];
    $motifs = ['窓辺', '路地', '水面', '駅前', '庭', '屋上', '机上', '海岸', '森の奥', '市場', '階段', '橋', '部屋', '雲間', '影'];
    $moods = ['静かな', '淡い', '透明な', '深い', 'やわらかな', '鮮やかな', '遠い', '穏やかな', '冷たい', '暖かな'];
    $forms = ['スケッチ', '習作', '構成', '小景', '断片', '連作', '色面', '記録', 'ドローイング', 'レイヤー'];
    $secondaryTags = ['透明感', '静けさ', 'リズム', '層', '反射', '温度', '距離', '質感', '線', '面', '陰影', '余韻', '粒子', '流れ', '対比', '調和', '奥行き', '気配', '視線', '時間'];

    $initialWorkCounts = array_fill_keys(array_keys($users), 0);
    foreach ($works as $work) {
        $initialWorkCounts[$work[0]]++;
    }

    $authorPool = [];
    $userEmails = array_keys($users);
    $assignedWorkCounts = array_fill_keys($userEmails, 0);
    for ($round = 1; $round <= 14; $round++) {
        foreach ($userEmails as $index => $email) {
            $target = $index < 30 ? 14 : 13;
            if (($initialWorkCounts[$email] ?? 0) + $assignedWorkCounts[$email] < $target) {
                $authorPool[] = $email;
                $assignedWorkCounts[$email]++;
            }
        }
    }

    $bulkPublicTarget = 482;
    $bulkPrivateTarget = 44;
    $bulkTotal = $bulkPublicTarget + $bulkPrivateTarget;
    $publicBulkTitles = [];

    for ($i = 1; $i <= $bulkTotal; $i++) {
        $theme = pick($themes, $i - 1);
        $motif = pick($motifs, $i + intdiv($i, 3));
        $mood = pick($moods, $i + intdiv($i, 5));
        $form = pick($forms, $i + intdiv($i, 7));
        $title = "{$theme}の{$motif} {$form} " . sprintf('%03d', $i);
        $description = "{$mood}{$theme}を{$motif}の気配と重ね、{$form}としてまとめた作品です。色の間合いと視線の流れで、発見の余白が残るように構成しています。";
        $visibility = $i <= $bulkPrivateTarget ? 'private' : 'public';
        $tagBlock = intdiv($i - 1, count($themes));
        $tagSecondIndex = (($i - 1) * 7 + $tagBlock) % count($secondaryTags);
        $tagSecond = $secondaryTags[$tagSecondIndex];
        $tagThird = $secondaryTags[($tagSecondIndex + 10) % count($secondaryTags)];
        $tags = [$theme, $tagSecond, $tagThird];
        $work = [
            $authorPool[$i - 1],
            $title,
            imageUrl($i),
            $description,
            $visibility,
            array_slice($tags, 0, 3),
            dateFromIndex($i),
        ];
        $works[] = $work;
        if ($visibility === 'public') {
            $publicBulkTitles[] = $title;
        }
    }

    // 旧検索検証 fixture は UI に見えるとテスト臭が強いため、新しい自然名の大量 seed に置き換える。
    $pdo->prepare("DELETE FROM gallery WHERE title LIKE ?")->execute(['検索検証 %']);

    $selectWork = $pdo->prepare("SELECT id FROM gallery WHERE title = ? LIMIT 1");
    $insertWork = $pdo->prepare("INSERT INTO gallery (user_id, title, src, description, visibility, created_at) VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))");
    $updateWork = $pdo->prepare("UPDATE gallery SET user_id = ?, src = ?, description = ?, visibility = ?, created_at = COALESCE(?, created_at) WHERE id = ?");
    $selectTag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
    $insertTag = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
    $deleteWorkTags = $pdo->prepare("DELETE FROM gallery_tags WHERE gallery_id = ?");
    $insertWorkTag = $pdo->prepare("INSERT IGNORE INTO gallery_tags (gallery_id, tag_id) VALUES (?, ?)");
    $workIds = [];
    $workAuthors = [];

    foreach ($works as $work) {
        [$email, $title, $src, $desc, $visibility, $tags, $createdAt] = array_pad($work, 7, null);
        $userId = $userIds[$email];

        $selectWork->execute([$title]);
        $workId = $selectWork->fetchColumn();
        if ($workId) {
            $workId = (int)$workId;
            $updateWork->execute([$userId, $src, $desc, $visibility, $createdAt, $workId]);
        } else {
            $insertWork->execute([$userId, $title, $src, $desc, $visibility, $createdAt]);
            $workId = (int)$pdo->lastInsertId();
        }
        $workIds[$title] = $workId;
        $workAuthors[$title] = $email;

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

    $insertStar = $pdo->prepare("INSERT IGNORE INTO stars (user_id, gallery_id) VALUES (?, ?)");
    $addStars = static function (string $title, int $targetCount, int $offset) use ($users, $userIds, $workIds, $workAuthors, $insertStar): void {
        if (!isset($workIds[$title], $workAuthors[$title])) {
            return;
        }

        $emails = array_keys($users);
        $given = 0;
        for ($i = 0; $i < count($emails) * 2 && $given < $targetCount; $i++) {
            $email = $emails[($i + $offset) % count($emails)];
            if ($email === $workAuthors[$title]) {
                continue;
            }
            $insertStar->execute([$userIds[$email], $workIds[$title]]);
            $given++;
        }
    };

    // 人気レーンは「スターが集まった作品」が明確に見える必要があるため、上位だけ強めに傾斜させる。
    $starTargets = [
        '作品24' => 36,
        '作品23' => 34,
        '作品21' => 32,
        '作品18' => 30,
        '作品20' => 28,
        '作品22' => 26,
        '作品19' => 24,
        '作品15' => 22,
        '作品14' => 20,
        '作品12' => 18,
        '作品10' => 16,
        '作品2'  => 14,
    ];
    foreach ($starTargets as $title => $targetCount) {
        $addStars($title, $targetCount, $targetCount);
    }

    foreach (array_slice($publicBulkTitles, 0, 90) as $index => $title) {
        $addStars($title, max(1, 8 - intdiv($index, 15)), $index + 5);
    }

    $counts = [
        'users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'works' => (int)$pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn(),
        'public' => (int)$pdo->query("SELECT COUNT(*) FROM gallery WHERE visibility = 'public'")->fetchColumn(),
        'private' => (int)$pdo->query("SELECT COUNT(*) FROM gallery WHERE visibility = 'private'")->fetchColumn(),
        'stars' => (int)$pdo->query("SELECT COUNT(*) FROM stars")->fetchColumn(),
    ];

    $pdo->commit();
    echo "v3 fixtureを投入しました: users={$counts['users']} works={$counts['works']} public={$counts['public']} private={$counts['private']} stars={$counts['stars']}（全員 password123）\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
