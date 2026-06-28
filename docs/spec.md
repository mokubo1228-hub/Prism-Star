# PrismStar — 技術仕様書（Specification）

PrismStar（通称 **Prism**、リポジトリ `prismstar`）の **技術仕様の単一の参照点**。
タグライン：**"Shine in every color."**（あらゆる色で、輝け。）

関連：

- 実装の段取り（フェーズ・進捗）：`docs/roadmap.md`
- 次バージョンの要件・動線：`docs/requirements-v2.md`
- 設計判断とその理由：`docs/decisions.md`
- 役割・開発フロー：`docs/ai-roles-and-workflow.md`

凡例：✅ 実装済み（develop） / 🔜 計画 / 🧪 案（未確定・将来 ADR で確定）

---

## 1. プロダクト概要

**PrismStar** は「**全員が発信者**」のマルチユーザー作品プラットフォーム。
サイトの誰もが自分の作品（画像 / 文字）を発信でき、エンジニアは GitHub リポジトリを取り込んで展示できる。

### コンセプトの3軸（名前が体現する）
- **Prism＝あらゆる色を受け入れる** … 作品も人も多様。どんな"色"も歓迎する（多様性）。
- **Star＝目指せスター／お気に入り(⭐)** … 発信して評価され、スターを集める。
- **全員が発信者** … 観る人と作る人の境界をなくす。誰もが自分の発信ページ（プロフィール）を持つ。

### スター(⭐)という共通通貨
手動投稿の作品も、取り込んだ GitHub リポジトリも、**同じ "スター(⭐)" で評価される**。
GitHub の star とサイトのお気に入りが地続きになり、GitHub 連携が"取ってつけ"でなくコンセプトの中心に溶ける（[ADR-009](decisions.md)）。

### ターゲット
- 非エンジニア … 画像 + 説明文を発信。
- エンジニア … GitHub リポジトリを発信（🔜 Phase 4）。

命名理由は [ADR-006](decisions.md)、プラットフォーム化は [ADR-008](decisions.md)。

---

## 2. 技術スタック

| 層 | 採用技術 | 備考 |
|---|---|---|
| フロント | HTML5 / CSS3 / Vanilla JS | フレームワーク・ビルド工程なし |
| 描画方式 | `<template>` + JS で DOM 生成 | データは API から取得して差し込み |
| レスポンシブ | CSS 変数 + メディアクエリ | グリッド 1〜5 列を可変。🧪 虹色アクセントでブランド表現 |
| バックエンド | PHP 8.2（Apache） | `pdo` / `pdo_mysql`、セッション認証 |
| DB | MySQL 8.0 | utf8mb4、プリペアドステートメント |
| インフラ | Docker Compose | `app`（php:8.2-apache）+ `db`（mysql:8.0）の2コンテナ |
| 開発URL | `http://localhost:8080` | app の 80 を 8080 に公開 |
| 🔜 外部連携 | GitHub REST API | **サーバ側 PHP から呼び、token は env**（[ADR-003](decisions.md)） |

技術選定の理由は [ADR-002](decisions.md)（PHP 維持）/ [ADR-003](decisions.md)（バックエンド経由）。

---

## 3. アーキテクチャ

### コンテナ構成（`docker-compose.yml`）
```
┌─────────────────────────┐     ┌─────────────────────────┐
│ app                     │     │ db                      │
│  php:8.2-apache (build) │     │  mysql:8.0              │
│  docroot: public/       │────▶│  DB: okubo_gallery      │
│  :80 → host :8080       │ PDO │  :3306 → host :3306     │
│  volumes: public/, src/ │     │  volume: db_data        │
│  env: DB_HOST=db 他     │     │  healthcheck: ping      │
└─────────────────────────┘     └─────────────────────────┘
        app は db が healthy になってから起動（depends_on）
```

### リクエストフロー
```
ブラウザ ──HTTP──▶ Apache(public/*.html, JS)
   JS fetch ──▶ public/api/*.php ──PDO──▶ MySQL ──▶ JSON 応答
```

### ディレクトリ構成
```
public/            Apache ドキュメントルート
  *.html           各ページ
  Style/           ページ別 CSS + sanitize.css（リセット）
  Script/          common.js（共通）/ ページ別 JS
  api/             gallery.php / auth.php / contact.php（🔜 github.php / stars.php / users.php）
  Image/           SNSアイコン
src/               db.php（PDO接続）/ seed.php（初期データ）
docker/            Dockerfile / init.sql
docs/              spec / roadmap / decisions / roles / handoff
```

### 認証方式 ✅
- PHP セッション（Cookie）。ログインで `$_SESSION` に `user_id` / `user_email` / `user_name`。
- ログイン時 `session_regenerate_id(true)`（セッション固定攻撃対策）。
- 書き込み系 API（投稿・削除・スター付与）はセッションの `user_id` を必須とする。

---

## 4. データモデル

### 現状スキーマ ✅（`docker/init.sql`）

**users**：id / email(UNIQUE) / password_hash(bcrypt) / name / created_at
**gallery**：id / user_id(FK) / title / src(画像URL) / description / created_at
**contacts**：id / first_name / last_name / email / message / created_at

### 計画スキーマ拡張 🧪（確定時に ADR 化）
- **stars**（お気に入り）：id / user_id（付けた人）/ gallery_id（対象）/ created_at、`UNIQUE(user_id, gallery_id)` で二重付与防止。作品ごとの star 数は集計で算出。
- `gallery.source` … `'manual'` / `'github'` の種別カラム（手動投稿と GitHub 取り込みを区別）。
- `gallery` に GitHub 由来メタ（主要言語・GitHub star 数・repo URL 等）。
- `users` にプロフィール用カラム（自己紹介 / アイコン）と `github_username`。

---

## 5. API 仕様

### 現状 ✅
| メソッド / エンドポイント | 機能 | 認証 | 主なバリデーション |
|---|---|---|---|
| `GET /api/gallery.php` | 一覧取得 | 不要 | — |
| `GET /api/gallery.php?id=N` | 1件取得 | 不要 | 無ければ 404 |
| `POST /api/gallery.php` | 新規投稿 | **要** | title/src 必須、src は http/https |
| `DELETE /api/gallery.php?id=N` | 削除 | **要** | 所有者のみ（`AND user_id=?`） |
| `POST /api/auth.php?action=login` | ログイン | — | email/password、`password_verify` |
| `POST /api/auth.php?action=logout` | ログアウト | — | セッション破棄 |
| `GET /api/auth.php?action=status` | ログイン状態 | — | `{loggedIn, user?}` |
| `POST /api/contact.php` | お問い合わせ保存 | 不要 | 姓/名/email 必須、email 形式 |

### 計画 🔜
| メソッド / エンドポイント | 機能 | フェーズ |
|---|---|---|
| `POST /api/auth.php?action=register` | ユーザー新規登録 | Phase 3 |
| `GET /api/users.php?name=...` | プロフィール＋その人の作品一覧 | Phase 3 |
| `GET /api/feed.php` | 全員の新着フィード | Phase 3 |
| `GET /api/github.php?user=...` | GitHub リポジトリ取得（サーバ側 token） | Phase 4 |
| `POST/DELETE /api/stars.php?gallery_id=N` | スター付与 / 解除（要ログイン）、star 数を返す | Phase 5 |

応答は一貫して JSON。エラー時は適切な HTTP ステータス + `{"error": "..."}`。

---

## 6. 画面構成

| 画面 | ファイル | 状態 |
|---|---|---|
| ギャラリー一覧 / フィード（グリッド + モーダル投稿） | `public/gallery-list.html` | ✅（🔜 フィード化 Phase 3 / スター表示 Phase 5） |
| 作品詳細（`?id=`） | `public/gallery-detail.html` | ✅（🔜 削除ボタン Phase 1-2 / スター Phase 5） |
| ログイン | `public/login.html` | ✅（🔜 ヘッダー追加 Phase 1-3） |
| お問い合わせ | `public/form.html` | ✅（🔜 ヘッダー追加 Phase 1-3） |
| プライバシーポリシー | `public/policy.html` | ✅ |
| ベースレイアウト | `public/base.html` | ✅ |
| 新規登録 | （`mikansei-Page/` に断片）| 🔜 Phase 3 |
| プロフィール / 個人ギャラリー（`?user=`） | 新規 | 🔜 Phase 3 |

---

## 7. 機能要件

### 実装済み ✅
- マルチユーザー認証（ログイン / ログアウト / 状態確認）。
- 作品の一覧・詳細表示（API 経由・動的描画）。
- ログイン中ユーザーによる作品投稿（モーダル）・削除（所有者のみ）。
- ログイン状態に応じた UI 出し分け（投稿ボタン / Login⇄Logout）※ Phase 1-1。
- お問い合わせ送信（DB 保存）。
- 共通ナビ生成・ハンバーガーメニュー・レスポンシブ。

### 計画 🔜
- 詳細ページの削除ボタン UI（Phase 1-2）／ login・form のヘッダー（Phase 1-3）。
- **PrismStar へのリブランド**（Phase 2）。
- **発信者オンボーディング**：オープン登録＋プロフィール／個人ギャラリー＋全員の新着フィード（Phase 3）。
- GitHub リポジトリ取り込み・展示（Phase 4）。
- **スター機能**：お気に入り(⭐)・GitHub star との統合・獲得スター集計・"スターを集める"（Phase 5）。
- 画像アップロード / ページネーション（Phase 6）。

---

## 8. 非機能要件・セキュリティ

### safety invariants（不変条件、`ai-roles-and-workflow.md` §4 と同一）
- 書き込み系（POST / DELETE / スター付与）はログイン必須。
- SQL は必ずプリペアドステートメント（文字列連結禁止）。
- パスワードは `password_hash` / `password_verify`、ログイン時 `session_regenerate_id`。
- 削除は所有者限定。投稿画像 URL は http / https のみ。
- **GitHub token はサーバ側 env、フロントに出さない。** secret はコミットしない（`db_data/` は `.gitignore`）。

### その他
- レスポンシブ（モバイル〜デスクトップ）。
- 🧪 スターは `UNIQUE(user_id, gallery_id)` で二重付与防止、ログイン必須。
- 🧪 GitHub API はレート制限対策にサーバ側キャッシュを検討（Phase 4 で確定）。

---

## 9. 実装計画（概要）

詳細・進捗は `docs/roadmap.md`。フェーズ概要：

`Phase 0 ベースライン ✅ → 1 既存機能の仕上げ 🚧 → 2 リブランド → 3 発信者オンボーディング → 4 GitHub連携 🎯 → 5 スター機能 ⭐ → 6 仕上げ`

各フェーズの実装は原則 Codex に `docs/phase-*-handoff.md` で渡す。
