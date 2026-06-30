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
        ['aoi@example.com',  '検索検証 雨の都市 01', 'https://placehold.jp/300x200.png', '雨の都市を歩く人影を描いた検索検証用作品。雨と都市のキーワードで複数ページを確認します。', 'public', ['雨', '都市'], '2024-01-01 09:00:00'],
        ['ren@example.com',  '検索検証 雨の都市 02', 'https://placehold.jp/310x200.png', '雨の都市に反射する看板の光をテーマにした検索検証用作品です。', 'public', ['雨', '都市', '光'], '2024-01-02 09:00:00'],
        ['mio@example.com',  '検索検証 雨の都市 03', 'https://placehold.jp/320x210.png', '都市の路地に降る雨を、淡い色面で構成した作品です。', 'public', ['雨', '都市'], '2024-01-03 09:00:00'],
        ['user05@example.com', '検索検証 雨の都市 04', 'https://placehold.jp/330x210.png', '雨の駅前広場と都市のざわめきを検索確認用に描きました。', 'public', ['雨', '都市', '夜'], '2024-01-04 09:00:00'],
        ['user06@example.com', '検索検証 雨の都市 05', 'https://placehold.jp/340x220.png', '都市の窓に残る雨粒をモチーフにした検索検証用の一枚です。', 'public', ['雨', '都市', '日常'], '2024-01-05 09:00:00'],
        ['user07@example.com', '検索検証 雨の都市 06', 'https://placehold.jp/350x220.png', '雨の歩道橋から見下ろす都市の線を描いた作品です。', 'public', ['雨', '都市'], '2024-01-06 09:00:00'],
        ['user08@example.com', '検索検証 雨の都市 07', 'https://placehold.jp/360x230.png', '都市の夕暮れに降る雨を、青い影と橙色の光で表現しました。', 'public', ['雨', '都市', '光'], '2024-01-07 09:00:00'],
        ['user09@example.com', '検索検証 雨の都市 08', 'https://placehold.jp/370x230.png', '雨上がりの都市公園を題材にした検索検証用作品です。', 'public', ['雨', '都市', '風景'], '2024-01-08 09:00:00'],
        ['user10@example.com', '検索検証 雨の都市 09', 'https://placehold.jp/380x240.png', '夜の都市に落ちる雨と反射光を、幾何学的な構図で描きました。', 'public', ['雨', '都市', '夜'], '2024-01-09 09:00:00'],
        ['user11@example.com', '検索検証 雨の都市 10', 'https://placehold.jp/390x240.png', '雨の都市を横切る電車の窓明かりをテーマにした作品です。', 'public', ['雨', '都市'], '2024-01-10 09:00:00'],
        ['user12@example.com', '検索検証 雨の都市 11', 'https://placehold.jp/300x210.png', '都市のビル群に降る細い雨を、線の重なりで表現しました。', 'public', ['雨', '都市', '抽象'], '2024-01-11 09:00:00'],
        ['user13@example.com', '検索検証 雨の都市 12', 'https://placehold.jp/310x220.png', '雨の交差点と都市の信号を検索確認用に描いた作品です。', 'public', ['雨', '都市'], '2024-01-12 09:00:00'],
        ['user14@example.com', '検索検証 雨の都市 13', 'https://placehold.jp/320x230.png', '都市の屋上から見る雨雲をテーマにした検索検証用作品です。', 'public', ['雨', '都市', '空'], '2024-01-13 09:00:00'],
        ['aoi@example.com',  '検索検証 雨の抽象 14', 'https://placehold.jp/330x240.png', '雨の音を抽象的な線と点で表現した検索検証用作品です。', 'public', ['雨', '抽象'], '2024-01-14 09:00:00'],
        ['ren@example.com',  '検索検証 雨の抽象 15', 'https://placehold.jp/340x250.png', '雨が水面に広がる様子を抽象化し、検索結果の件数確認に使います。', 'public', ['雨', '抽象'], '2024-01-15 09:00:00'],
        ['mio@example.com',  '検索検証 雨の抽象 16', 'https://placehold.jp/350x260.png', '雨のリズムを色の反復で描いた抽象作品です。', 'public', ['雨', '抽象'], '2024-01-16 09:00:00'],
        ['user05@example.com', '検索検証 雨の空 17', 'https://placehold.jp/360x240.png', '雨雲の切れ間から見える空を描き、雨と空の検索確認に使います。', 'public', ['雨', '空'], '2024-01-17 09:00:00'],
        ['user06@example.com', '検索検証 雨の空 18', 'https://placehold.jp/370x240.png', '雨上がりの空に残る光をテーマにした作品です。', 'public', ['雨', '空', '光'], '2024-01-18 09:00:00'],
        ['user07@example.com', '検索検証 雨の空 19', 'https://placehold.jp/380x250.png', '空から落ちる雨を、縦の線と淡い青で表現しました。', 'public', ['雨', '空'], '2024-01-19 09:00:00'],
        ['user08@example.com', '検索検証 都市の抽象 20', 'https://placehold.jp/390x250.png', '都市のビルの形を抽象化した検索検証用作品です。', 'public', ['都市', '抽象'], '2024-01-20 09:00:00'],
        ['user09@example.com', '検索検証 都市の抽象 21', 'https://placehold.jp/300x240.png', '都市の移動と光を抽象的な面で構成しました。', 'public', ['都市', '抽象', '光'], '2024-01-21 09:00:00'],
        ['user10@example.com', '検索検証 都市の抽象 22', 'https://placehold.jp/310x240.png', '都市の地図を抽象模様として描いた検索確認用作品です。', 'public', ['都市', '抽象'], '2024-01-22 09:00:00'],
        ['user11@example.com', '検索検証 都市の空 23', 'https://placehold.jp/320x250.png', '都市の狭い路地から見える空を描いた作品です。', 'public', ['都市', '空'], '2024-01-23 09:00:00'],
        ['user12@example.com', '検索検証 都市の空 24', 'https://placehold.jp/330x250.png', '都市の高架越しに広がる空をテーマにしています。', 'public', ['都市', '空'], '2024-01-24 09:00:00'],
        ['user13@example.com', '検索検証 都市の夜 25', 'https://placehold.jp/340x260.png', '夜の都市と雨上がりの路面を描いた検索検証用作品です。', 'public', ['都市', '夜'], '2024-01-25 09:00:00'],
        ['user14@example.com', '検索検証 都市の花 26', 'https://placehold.jp/350x260.png', '都市の隙間に咲く花を題材にした検索検証用作品です。', 'public', ['都市', '花'], '2024-01-26 09:00:00'],
        ['aoi@example.com',  '検索検証 雨の花 27', 'https://placehold.jp/360x260.png', '雨に濡れた花を中心に、検索語の分布確認用として制作した作品です。', 'public', ['雨', '花'], '2024-01-27 09:00:00'],
        ['ren@example.com',  '検索検証 抽象の空 28', 'https://placehold.jp/370x260.png', '空の色を抽象的に分解した検索検証用作品です。', 'public', ['抽象', '空'], '2024-01-28 09:00:00'],
        ['mio@example.com',  '検索検証 雨の日常 29', 'https://placehold.jp/380x260.png', '雨の日常を静かな室内の視点で描いた作品です。', 'public', ['雨', '日常'], '2024-01-29 09:00:00'],
        ['user05@example.com', '検索検証 都市の日常 30', 'https://placehold.jp/390x260.png', '都市の日常と雨上がりの空気を検索確認用に描きました。', 'public', ['都市', '日常'], '2024-01-30 09:00:00'],
        ['demo@example.com', '検索検証 非公開 雨 31', 'https://placehold.jp/300x200.png', '非公開作品が検索結果に出ないことを確認するための雨の作品です。', 'private', ['雨', '検証'], '2024-01-31 09:00:00'],
        ['aoi@example.com',  '検索検証 非公開 都市 32', 'https://placehold.jp/310x200.png', '非公開作品が検索件数に混ざらないことを確認する都市の作品です。', 'private', ['都市', '検証'], '2024-02-01 09:00:00'],
        ['ren@example.com',  '検索検証 非公開 抽象 33', 'https://placehold.jp/320x200.png', '非公開の抽象作品が検索に出ないことを確認するための fixture です。', 'private', ['抽象', '検証'], '2024-02-02 09:00:00'],
        ['mio@example.com',  '検索検証 非公開 空 34', 'https://placehold.jp/330x200.png', '非公開の空の作品が検索結果に含まれないことを確認します。', 'private', ['空', '検証'], '2024-02-03 09:00:00'],
    ];

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

    // 人気ランキングを「人気順に見える」ようにするための星の作り込み（[ADR-029]）。星が無いと全作品が
    // 同点で順位が出ないため、上位ほど多く付くよう降順で配分する。星は作者以外のユーザーから付け（自然）、
    // INSERT IGNORE＋UNIQUE(user_id,gallery_id) で何度流しても増えない（冪等）。
    $starTargets = [
        '作品24' => 13,
        '作品23' => 12,
        '作品21' => 11,
        '作品18' => 10,
        '作品20' => 8,
        '作品22' => 7,
        '作品19' => 6,
        '作品15' => 5,
        '作品14' => 4,
        '作品12' => 3,
        '作品10' => 2,
        '作品2'  => 1,
    ];
    $insertStar = $pdo->prepare("INSERT IGNORE INTO stars (user_id, gallery_id) VALUES (?, ?)");
    $starFixtureCount = 0;
    foreach ($starTargets as $title => $targetCount) {
        if (!isset($workIds[$title], $workAuthors[$title])) {
            continue;
        }

        $given = 0;
        foreach ($users as $email => $_user) {
            if ($email === $workAuthors[$title]) {
                continue;
            }
            $insertStar->execute([$userIds[$email], $workIds[$title]]);
            $given++;
            if ($given >= $targetCount) {
                break;
            }
        }
        $starFixtureCount += $given;
    }

    $pdo->commit();
    echo "v2 fixtureを投入しました: users=" . count($users) . " works=" . count($works) . " stars=" . $starFixtureCount . "（全員 password123）\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
