# PrismStar

「全員が発信者」のマルチユーザー作品プラットフォーム ＋ GitHub 連携。タグライン "Shine in every color."

## AI 役割・ワークフロー（重要・最初に読む）

役割分担と開発フローの正本は **`docs/ai-roles-and-workflow.md`**。実装者 / Codex 向け repo 規則は `AGENTS.md`。

本プロジェクトは **ChatGPT を使わない User / Claude Code / Codex の3者構成**。
フローは `User ⇄ Claude Code → Codex`（Codex へは Claude Code の `docs/*-handoff.md` を人間が渡す）。

Claude Code は **designer / architect / PM ＋ 整理役**。必ず守る：

- **GO なしにコードを変更しない / git を mutate しない**（commit / tag / branch / push）。docs 記述は可。
- **方向指示 ≠ 実装許可。** 実装が必要なら handoff 化して GO を待つ（実装は通常 Codex へ）。
- **作る前に「本当にその実装が要るか / 操作モデルは正しいか」を疑う。** 違和感は実装前に出す。
- **過剰に質問しない。** 自明な default は明言して進め、本当に User が決めるべき分岐だけ確認する。

safety invariants（壊さない不変条件）と検証コマンドは正本 §4 / §5 を参照。

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
# http://localhost:8080/ でアクセス（gallery-list.php にリダイレクト）
```

デモユーザー: `demo@example.com` / `password123`
