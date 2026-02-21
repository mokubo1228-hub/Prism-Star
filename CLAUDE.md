# Okubo Gallery

Pixivのようなイラスト投稿サイトを模したポートフォリオサイト。

## 技術構成

- フロント: HTML / CSS / Vanilla JS（フレームワークなし）
- バックエンド: PHP 8.2 + MySQL 8.0
- 開発環境: Docker Compose（app + db）
- テンプレート方式: `<template>` タグ + JS でDOM生成
- レスポンシブ対応: CSS変数 + メディアクエリ（1〜5列グリッド）

## ページ構成

| ページ | ファイル | 概要 |
|---|---|---|
| ギャラリー一覧 | public/gallery-list.html | 作品サムネイルのグリッド表示 + モーダル投稿 |
| 作品詳細 | public/gallery-detail.html | URLパラメータ(id)で作品を表示 |
| ログイン | public/login.html | メール/パスワード認証（セッション管理） |
| お問い合わせ | public/form.html | 名前・メール・メッセージの送信フォーム |
| プライバシーポリシー | public/policy.html | 個人情報保護方針 |
| ベースレイアウト | public/base.html | ヘッダー/フッター付きの基本構造 |

## API

| エンドポイント | 機能 |
|---|---|
| GET/POST/DELETE /api/gallery.php | ギャラリーCRUD |
| POST /api/auth.php?action=login | ログイン |
| POST /api/auth.php?action=logout | ログアウト |
| GET /api/auth.php?action=status | ログイン状態確認 |
| POST /api/contact.php | お問い合わせ送信 |

## ディレクトリ

```
public/          … Apache ドキュメントルート
  api/           … PHP API（gallery.php, auth.php, contact.php）
  Script/        … JS（common.js で共通ナビ生成）
  Style/         … CSS（ページごとに分割、sanitize.css でリセット）
  Image/         … SNSアイコン画像
src/             … PHP共通処理（db.php, seed.php）
docker/          … Docker設定（Dockerfile, init.sql）
```

## 起動方法

```bash
docker compose up -d
docker compose exec app php /var/www/html/src/seed.php   # 初回のみ
# http://localhost:8080/gallery-list.html でアクセス
```

デモユーザー: `demo@example.com` / `password123`
