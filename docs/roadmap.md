# PrismStar — 実装ロードマップ

このファイルは PrismStar（通称 Prism）の **実装計画（フェーズ分割と進捗状態）** を固定する。

- 粒度の細かいタスク：`PROJECT.md`「今後の課題」
- 各フェーズの Codex 向け実装指示：`docs/phase-*-handoff.md`
- 重要な意思決定と理由：`docs/decisions.md`
- 技術仕様（v1 現状）：`docs/spec.md`
- v2 の要件・動線：`docs/requirements-v2.md`
- 役割・フロー：`docs/ai-roles-and-workflow.md`

状態凡例：✅ 完了 / 🚧 進行中 / ⬜ 未着手

---

## Phase 0 — ベースライン確認 ✅
- `develop` を作業ベースに切替、Docker 環境を起動、API / ログイン / 投稿の疎通確認。
- 結果：`app(:8080)` + `db(MySQL)` healthy、seed 投入、主要 API 疎通 OK。

## Phase 1 — 既存機能の仕上げ ✅
API は実装済みで UI だけ無いものを埋める。
- ✅ 1-1 ログイン状態の UI 反映＋ログアウト（`common.js` / `gallery-list.html`）
  - ※ 仕様確定前に Claude Code が直接実装した**例外**（[ADR-005](decisions.md) の運用モデル確立前）。以降は Codex 経由。
- ✅ 1-2 削除ボタン（詳細ページ、所有者のみ）— Codex 実装（`docs/phase-1-handoff.md`）、Claude Code review 済。
  - 詳細 GET に `user_id` 追加 → `gallery-detail.js` で所有者判定 → DELETE 結線。
- ✅ 1-3 `login.html` / `form.html` にヘッダー / フッター追加 — Codex 実装、review 済。
- ※ 視覚（ブラウザ）確認は推奨（Codex 環境にブラウザが無く、API / 配信スモークまで検証済）。

## Phase 2 — PrismStar リブランド ✅
- 表示名 `Okubo Gallery` / `大久保の館` → **`PrismStar`** ＋ タグライン `"Shine in every color."`。全 `public/*.html` の `<title>`・ヘッダー・login/form カード・policy 本文・README を統一。Codex 実装・review 済。
- 🔭 **今後の発展**：虹色アクセントによるブランド表現（ロゴ／配色）、レスポンシブの細部調整。

## Phase 3 — 発信者オンボーディング ✅
「**全員が発信者**」を成立させる核（[ADR-008](decisions.md)）。Codex 実装（`docs/phase-3-handoff.md`）・Claude Code review 済。スキーマ変更なし。
- ✅ 3-1 オープン**新規登録**（`auth.php?action=register`：検証・重複409・`password_hash`・自動ログイン）＋ `register.html`。
- ✅ 3-2 **プロフィール / 個人ギャラリー**（`users.php?id=N` は公開情報のみ＝email/pass 返さない）＋ `profile.html`。
- ✅ 3-3 **新着フィード**：`gallery.php` GET を users JOIN＋`created_at DESC`、カード/詳細に投稿者リンク（専用 feed.php は作らず一覧を強化）。
- 🔭 **今後の発展**：登録の同時実行に対する堅牢化（重複を 409 で防ぐ）、username / slug の導入。

## Phase 4 — GitHub 連携 🎯 ✅
Codex 実装（`docs/phase-4-handoff.md`）・Claude Code review 済（SSRF・token秘匿を独立検証）。
- ✅ 4-0 `.env` 化：DB 認証情報＋`GITHUB_TOKEN` を `.env`（gitignore）に、`.env.example` をコミット、`docker-compose.yml` を `${VAR}` 参照に（平文パスワード消滅）。[ADR-003]
- ✅ 4-1 `users.github_username` 追加（`init.sql`＋冪等 `src/migrate.php`）、本人のみ設定 API（`POST users.php`）。
- ✅ 4-2 `api/github.php?user=`：**サーバ側で token 使用**（未設定でも未認証で動く）、SSRF 対策（`^[A-Za-z0-9-]+$`）、応答に token を出さない。
- ✅ 4-3 プロフィールに GitHub リポジトリのカード（⭐/言語/リンク）＋自分のプロフィールで username 設定 UI。
- 🔭 **今後の発展**：取り込んだ GitHub リポジトリの `gallery` 永続化（source 種別・OG画像）、fork 除外。
- 📌 **判断事項**：`github.php` の文字列分割の撤去＋token漏れ検査の対象見直し → [ADR-011](decisions.md)（✅ Phase 5 で実施済み）。

## Phase 5 — スター機能 ⭐ ✅
Codex 実装（`docs/phase-5-handoff.md`）・Claude Code review 済（冪等/401/404/権限/ADR-011撤去を独立検証）。
- ✅ `stars` テーブル（`id` PK＋`UNIQUE(user_id,gallery_id)`＋両FK `ON DELETE CASCADE`）＋冪等 migration。
- ✅ `stars.php`：付与/解除トグル（`INSERT IGNORE`で冪等・要ログイン・404/401）。
- ✅ `gallery.php` に `star_count`/`starred`、`users.php` に `total_stars`。
- ✅ 一覧/詳細に⭐ボタン＋数（入れ子`<a>`回避・`aria-pressed`）、プロフィールに獲得スター総数。
- ✅ ADR-011 クリーンアップ（github.php の文字列分割撤去＋token検査を配信資産に限定）。

> 🎉 **Phase 0–5 完了＝ PrismStar v1 達成。** 以降は下記ロードマップ（今後の発展）を順次進める。

## Phase 6 — PHP 部品化リファクタ（header/footer 共通化）✅
ページを `.php` 化し、header/footer（と head）を `public/includes/*.php` の partial に1箇所化（[ADR-012](decisions.md)）。挙動は不変の構造リファクタ。`base.html` はなごりとして保持。handoff：`docs/phase-6-handoff.md`。Codex 実装・Claude Code review 済。
- ✅ 7ページ `.html`→`.php`（`git mv`）、`includes/{head,header,footer}.php` ＋ `index.php`（→gallery-list.php リダイレクト）。
- ✅ 検証：`php -l` 全 OK／全 `.php` 200・旧 `.html` 404・`/`→gallery-list.php・`api/gallery.php` JSON 200。

---

> 📐 **ここから v2**（マイページ・公開非公開・作品作成/編集・タグ・検索）。要件の全体像は `docs/requirements-v2.md`。

## 認証動線・ナビ UX（フロント・独立トラック / 次に着手）⬜
「閲覧は自由・操作だけログイン」を UI で案内（`docs/requirements-v2.md` §7.1）。**ログイン/新規登録ボタンの明示**・ホーム見出し「みんなの作品」・**未ログイン操作（⭐/投稿）時のログイン誘導**。バックエンドの可視性（下記 Phase 7）と**独立**して着手可。`[マイページ]` リンクは Phase 8（マイページ）で有効化。handoff：`docs/phase-auth-ux-handoff.md`。

## Phase 7 — 公開 / 非公開（可視性の土台）⬜
作品に公開/非公開の2状態を持たせる（[ADR-014](decisions.md)）。`gallery.visibility`（既定 公開）を冪等 migration で追加 ＋ **一覧・検索・ユーザーページの全 SELECT に公開フィルタ**、詳細は「公開＝誰でも／非公開＝本人のみ」。作成時に可視性を選べる・後から切替可。**非公開を所有者以外に返さない**ことが核。

## Phase 8 — マイページ（管理画面）＋ 作品作成 / 編集 ⬜
本人専用のマイページ（=管理画面）を公開プロフィール（ユーザーページ）と別に新設（[ADR-013](decisions.md)）。本人の**全作品（非公開含む）**一覧＋新規作成・編集・削除・公開切替・GitHub 設定。作成/編集はモーダルから**専用フォーム画面**へ格上げ。ユーザーページ（`profile.php`）は**公開作品のみ**に整理。
- **公開の作品詳細ページから v1 のインライン削除ボタンを撤去**し、編集/削除/公開切替はマイページに集約（詳細は閲覧専用＋所有者には「編集」導線のみ／`requirements-v2.md` §3）。

## Phase 9 — 画像アップロード ⬜
作品画像をサーバ保存に対応（[ADR-015](decisions.md)）。`public/uploads/` 保存・`src` にパス。MIME/拡張子/サイズ検証・ファイル名サーバ生成（safety invariant 追加）。作成/編集フォームに統合（外部 URL も併存可）。

## Phase 10 — タグ ⬜
作品にタグを付与（[ADR-017](decisions.md)）。`tags` ＋ `gallery_tags`（多対多）。作成/編集にタグ入力、詳細/カードにタグ表示。

## Phase 11 — 検索 ⬜
検索画面 `search.php`（`docs/requirements-v2.md` §6）。ワード（タイトル/説明）＋タグで**公開作品**を検索（**自分の作品は除く**）、ユーザー（名前/GitHub）検索。

## 今後の発展（ロードマップ）⬜
- 一覧のページネーション、ブランド表現（虹色テーマ）の作り込み。
- GitHub 連携の発展：取り込み作品の永続化・fork 除外（現状は表示レイヤー＝[ADR-016](decisions.md)）。
- 登録の同時実行に対する堅牢化、username / slug の導入。

---

## 進め方の原則
- 各フェーズは「常に動く状態」を保つ（`0 → 1 → 2 → 3 → 4 → 5` の順を基本）。
- フェーズ着手前にこの roadmap を更新し、重要な分岐を決めたら `docs/decisions.md` に追記する。
- 実装は原則 Codex に handoff（`docs/ai-roles-and-workflow.md` §1・§7）。
