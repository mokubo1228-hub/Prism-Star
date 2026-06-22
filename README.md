# PrismStar

Shine in every color.

「全員が発信者」のマルチユーザー作品プラットフォーム。非エンジニアは画像・文字を、エンジニアは GitHub リポジトリを発信でき、評価はスター(⭐)で集まる。

## 必要なもの

- Docker Desktop

## セットアップ

```bash
# 1. リポジトリをクローン
git clone <リポジトリURL>
cd myport-site

# 2. コンテナを起動
docker compose up -d

# 3. 初期データを投入（初回のみ）
docker compose exec app php /var/www/html/src/seed.php
```

## 使い方

ブラウザで http://localhost:8080/gallery-list.html を開く。

### ログイン

| 項目 | 値 |
|---|---|
| メールアドレス | demo@example.com |
| パスワード | password123 |

### できること

- **ギャラリー閲覧** — 一覧ページで作品サムネイルをクリックすると詳細ページへ
- **作品投稿** — ログイン後、ヘッダー右の「＋」ボタンからモーダルで投稿
- **お問い合わせ** — ナビゲーションの「Contact」から送信フォームへ

### コンテナの停止・削除

```bash
# 停止
docker compose down

# データも含めて完全削除
docker compose down -v
```

## 技術構成

- フロント: HTML / CSS / Vanilla JS
- バックエンド: PHP 8.2 / MySQL 8.0
- 開発環境: Docker Compose

選定理由・設計のこだわり・判断軸は **[docs/highlights.md](docs/highlights.md)** を参照。
