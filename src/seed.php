<?php
/**
 * DB初期データ投入スクリプト
 * docker compose up 後に一度だけ実行: docker compose exec app php /var/www/html/src/seed.php
 */

require_once __DIR__ . '/db.php';

$pdo = getDb();

// 既にデータがあればスキップ
$count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count > 0) {
    echo "データは既に投入済みです。\n";
    exit(0);
}

// デモユーザー作成
$hash = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)")
    ->execute(['demo@example.com', $hash, 'Demo User']);

echo "デモユーザーを作成しました: demo@example.com / password123\n";

// ギャラリー初期データ
$works = [
    ['作品1',  'https://placehold.jp/300x200.png', 'これは作品1の説明です。静かな湖畔をテーマに、光と影の対比を意識して制作しました。'],
    ['作品2',  'https://placehold.jp/200x300.png', '都市の片隅に咲く小さな花をモチーフにした作品。無機質な街と自然の生命力の対比を表現しています。'],
    ['作品3',  'https://placehold.jp/400x250.png', '朝焼けの空をイメージした抽象作品。暖色のグラデーションを重ね、時間の移ろいを描きました。'],
    ['作品4',  'https://placehold.jp/250x250.png', '過去と現在の交差をテーマにした作品。幾何学的な模様の中に、曖昧な記憶の断片を散りばめています。'],
    ['作品5',  'https://placehold.jp/350x200.png', '水面に映る世界を描いた幻想的な一枚。現実と虚構の境界をぼかすように表現しました。'],
    ['作品6',  'https://placehold.jp/200x350.png', '縦構図で表現された森の静寂。深い緑と差し込む光のコントラストで奥行きを強調しています。'],
    ['作品7',  'https://placehold.jp/400x300.png', '風の流れを線で表現した抽象作品。リズムとテンポを感じさせる構成で、動きのある印象を与えます。'],
    ['作品8',  'https://placehold.jp/300x300.png', '日常の中に潜む美しさをテーマにした作品。シンプルな構図の中に穏やかな感情を込めました。'],
    ['作品9',  'https://placehold.jp/280x180.png', '海辺の夕暮れをモチーフにした小品。橙と群青のグラデーションで静かな時間の流れを表現しています。'],
    ['作品10', 'https://placehold.jp/200x250.png', '夜の街灯をモチーフに、孤独と温もりを同時に感じさせるようなトーンで仕上げました。'],
    ['作品11', 'https://placehold.jp/400x200.png', '季節の移ろいを表す抽象画。春から冬へと変わる空気感を、色彩のグラデーションで表現しました。'],
    ['作品12', 'https://placehold.jp/240x300.png', '空想上の街を描いた作品。建物の形や影の配置に、童話的な雰囲気を持たせています。'],
    ['作品13', 'https://placehold.jp/300x240.png', '雨上がりの窓を題材にした作品。ガラス越しの世界を淡いタッチで表現しました。'],
    ['作品14', 'https://placehold.jp/200x200.png', 'ミニマルな構成で描いた静物画。余白と形のバランスを重視したシンプルな一枚です。'],
    ['作品15', 'https://placehold.jp/320x180.png', '夜空をイメージした作品。小さな光の粒をちりばめ、静寂と広がりを感じさせる構成になっています。'],
];

$stmt = $pdo->prepare("INSERT INTO gallery (user_id, title, src, description) VALUES (1, ?, ?, ?)");
foreach ($works as $w) {
    $stmt->execute($w);
}

echo "ギャラリーデータ " . count($works) . " 件を投入しました。\n";
