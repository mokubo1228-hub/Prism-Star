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
| おすすめ一覧 | public/gallery-list.php | 新着・人気の作品グリッド + モーダル投稿 |
| 作品詳細 | public/gallery-detail.php | id 指定で作品表示・スター・タグ・戻る |
| 検索結果 | public/search.php | 作品 / ユーザー / タグ検索 |
| 公開プロフィール | public/profile.php | ?id= / ?u=@handle・bio・その人の作品 |
| マイページ | public/mypage.php | 自分の作品管理（作成/編集/公開切替/GitHub取込） |
| お気に入り | public/favorites.php | スターした作品一覧 |
| アカウント設定 | public/settings.php | 表示名・パスワード 等 |
| 作品 作成/編集 | public/work-edit.php | 専用フォーム |
| ログイン/登録/確認 | public/login.php / register.php / verify.php | 認証（登録は double opt-in） |
| パスワード再設定 | public/forgot.php / reset.php | メールトークン方式 |
| お問い合わせ/ポリシー | public/form.php / policy.php | 送信フォーム / 個人情報保護方針 |

## API

| エンドポイント | 機能 |
|---|---|
| GET/POST/PATCH/DELETE /api/gallery.php | 作品 CRUD（?action=import-github で GitHub 取込） |
| GET /api/search.php | 作品 / ユーザー / タグ検索 |
| POST/DELETE /api/stars.php | スター付与 / 解除 |
| GET /api/users.php | プロフィール＋その人の作品（?id= / ?u=） |
| GET /api/github.php | GitHub リポジトリ取得（サーバ側 token） |
| /api/auth.php（login/logout/register/verify/reset・?action=status） | 認証（登録 double opt-in・パスワード再設定） |
| POST /api/contact.php | お問い合わせ送信 |

## ディレクトリ

```
public/          … Apache ドキュメントルート
  api/           … PHP API（gallery, auth, contact, github, stars, users, search）
  includes/      … head/header/footer/asset の partial（共通化）
  Script/        … JS（common.js で共通ナビ生成）
  Style/         … CSS（ページごとに分割、sanitize.css でリセット）
  Image/         … SNSアイコン画像
src/             … PHP共通処理（db, seed, migrate, session, username, github_client）
docker/          … Docker設定（Dockerfile, init.sql）
```

## 起動方法

```bash
docker compose up -d
docker compose exec app php /var/www/html/src/seed.php   # 初回のみ
# http://localhost:8080/ でアクセス（gallery-list.php にリダイレクト）
```

デモユーザー: `demo@example.com` / `password123`
