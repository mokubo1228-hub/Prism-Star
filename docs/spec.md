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
- エンジニア … GitHub リポジトリを発信。

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
| 外部連携 | GitHub REST API | **サーバ側 PHP から呼び、token は env**（[ADR-003](decisions.md)） |

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
ブラウザ ──HTTP──▶ Apache(public/*.php, JS)
   JS fetch ──▶ public/api/*.php ──PDO──▶ MySQL ──▶ JSON 応答
```

### ディレクトリ構成
```
public/            Apache ドキュメントルート
  *.php            各ページ（header/footer は includes/ の partial で共通化）
  includes/        head / header / footer / asset の partial
  Style/           ページ別 CSS + sanitize.css（リセット）
  Script/          common.js（共通）/ ページ別 JS
  api/             gallery / auth / contact / github / stars / users / search
  Image/ uploads/  SNSアイコン / アップロード画像（.htaccess で実行抑止）
src/               db.php / seed.php / migrate.php / session.php / username.php / upload.php（画像アップロード共通検証） / github_client.php
docker/            Dockerfile / init.sql
docs/              spec / roadmap / decisions / roles / handoff
```

### 認証方式 ✅
- PHP セッション（Cookie）。ログインで `$_SESSION` に `user_id` / `user_email` / `user_name`。
- ログイン時 `session_regenerate_id(true)`（セッション固定攻撃対策）。
- 書き込み系 API（投稿・削除・スター付与）はセッションの `user_id` を必須とする。

---

## 4. データモデル

### スキーマ ✅（`docker/init.sql` ＋ `src/migrate.php`）

**users**：id / email(UNIQUE) / password_hash(bcrypt) / name / **username(UNIQUE・@ハンドル)** / **github_username** / **bio** / **avatar_path（アイコン画像・未設定は NULL）** / created_at
**gallery**：id / user_id(FK) / title / src / description / **visibility(public｜private)** / **source(manual｜github)** / **source_url** / created_at
**tags** / **gallery_tags**：タグを正規化テーブルで多対多に保持（[ADR-017](decisions.md)）
**stars**：id / user_id / gallery_id / created_at、`UNIQUE(user_id, gallery_id)` で二重付与防止（star 数は集計で算出）
**contacts**：id / first_name / last_name / email / message / created_at
**pending_registrations**：id / email / token_hash / expires_at / created_at（登録 double opt-in。[ADR-018](decisions.md)）
**password_resets**：id / user_id / token_hash / expires_at / created_at（パスワード再設定。[ADR-021](decisions.md)）

---

## 5. API 仕様

応答は一貫して JSON。エラー時は適切な HTTP ステータス + `{"error": "..."}`。

| メソッド / エンドポイント | 機能 | 認証 |
|---|---|---|
| `GET /api/gallery.php` / `?id=N` | 一覧 / 1件取得（おすすめは自分の作品を除外・[ADR-027](decisions.md)） | 一部要 |
| `POST /api/gallery.php` | 新規投稿（画像アップロード対応） | 要 |
| `POST /api/gallery.php?_method=PATCH&id=N` | 編集（所有者のみ） | 要 |
| `DELETE /api/gallery.php?id=N` | 削除（所有者のみ・`AND user_id=?`） | 要 |
| `POST /api/gallery.php?action=import-github` | GitHub リポジトリを作品として取り込み | 要 |
| `GET /api/search.php?q=&type=works｜users&tag=` | 作品 / ユーザー / タグ検索（[ADR-025](decisions.md)） | 要 |
| `POST｜DELETE /api/stars.php?gallery_id=N` | スター付与 / 解除（star 数を返す） | 要 |
| `GET /api/users.php?id=｜u=｜name=` | プロフィール＋その人の公開作品 | 要 |
| `POST /api/users.php`（`?action=avatar｜avatar-remove` 含む） | プロフィール更新（名前 / bio / username）＋**アイコン画像のアップロード・デフォルト復帰**（本人のみ） | 要 |
| `GET /api/github.php?user=` | GitHub リポジトリ取得（token はサーバ側・[ADR-003](decisions.md)） | 要 |
| `/api/auth.php` | ログイン / ログアウト / `?action=status` / 新規登録〔double opt-in・[ADR-018](decisions.md)〕/ メール確認 / パスワード再設定 | — |
| `POST /api/contact.php` | お問い合わせ保存（姓/名/email 必須・形式チェック） | 不要 |

---

## 6. 画面構成

| 画面 | ファイル | 状態 |
|---|---|---|
| おすすめ一覧（新着・人気） | `public/gallery-list.php` | ✅ |
| 作品詳細（`?id=`・スター・タグ・戻る） | `public/gallery-detail.php` | ✅ |
| 検索結果（作品 / ユーザー / タグ） | `public/search.php` | ✅ |
| 公開プロフィール（`?id=` / `?u=@handle`） | `public/profile.php` | ✅ |
| マイページ（自分の作品管理） | `public/mypage.php` | ✅ |
| お気に入り（スターした作品） | `public/favorites.php` | ✅ |
| アカウント設定 | `public/settings.php` | ✅ |
| 作品の作成 / 編集 | `public/work-edit.php` | ✅ |
| ログイン / 新規登録（double opt-in）/ メール確認 | `public/login.php` / `register.php` / `verify.php` | ✅ |
| パスワード再設定（申請 / 設定） | `public/forgot.php` / `reset.php` | ✅ |
| お問い合わせ / プライバシーポリシー | `public/form.php` / `policy.php` | ✅ |

---

## 7. 機能要件

### 実装済み ✅
- マルチユーザー認証＋**登録 double opt-in**（メールトークン）＋**パスワード再設定**。
- 作品の投稿（画像アップロード）/ 編集 / 削除（所有者のみ）/ **公開・非公開**切替。
- **おすすめ**（新着・人気の2軸ランキング）/ **タグ検索** / **ユーザー検索**。
- **スター(⭐)** 付与・解除と**お気に入り**一覧。
- **GitHub リポジトリ取り込み**・展示（token はサーバ側）。
- **プロフィール**（`@username` ハンドル・自己紹介 bio・**アイコン画像のアップロード/差し替え**）/ **マイページ**（作品管理）/ **アカウント設定**。
- **検索の番号付きページング**（総件数・表示件数 10/30/50 切替・URL で状態復元・[ADR-044](decisions.md)）/ 共通ナビ・ハンバーガー・レスポンシブ。

### 今後の発展 🔜
- pretty URL（`/@handle` の Apache rewrite）、獲得スター集計の強化など。詳細は `docs/roadmap.md`「今後の発展」。

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
- スターは `UNIQUE(user_id, gallery_id)` で二重付与防止、ログイン必須。
- GitHub API はサーバ側 token で 5000 req/h。さらなる負荷時はキャッシュが発展余地。

---

## 9. 実装計画（概要）

詳細・進捗は `docs/roadmap.md`。フェーズ概要：

`Phase 0〜7 ✅（ベースライン → 既存機能の仕上げ → リブランド → 発信者オンボーディング → GitHub連携 → スター → 検索/発見 → 個人系IA・回遊）`。以降の発展は `docs/roadmap.md`「今後の発展」。

各フェーズの実装は原則 Codex に `docs/phase-*-handoff.md` で渡す。
