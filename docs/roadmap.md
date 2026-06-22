# PrismStar — 実装ロードマップ

このファイルは PrismStar（通称 Prism）の **実装計画（フェーズ分割と進捗状態）** を固定する。

- 粒度の細かいタスク：`PROJECT.md`「今後の課題」
- 各フェーズの Codex 向け実装指示：`docs/phase-*-handoff.md`
- 重要な意思決定と理由：`docs/decisions.md`
- 技術仕様：`docs/spec.md`
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

## Phase 6 — PHP 部品化リファクタ（header/footer 共通化）⬜
ページを `.php` 化し、header/footer（と head）を `public/includes/*.php` の partial に1箇所化（[ADR-012](decisions.md)）。挙動は不変の構造リファクタ。`base.html` はなごりとして保持。handoff：`docs/phase-6-handoff.md`。

## Phase 7 — 今後の発展 ⬜
- 画像ファイルのアップロード（現在は外部 URL に対応）、一覧のページネーション。
- ブランド表現（虹色テーマ）の作り込み、取り込んだ GitHub リポジトリの永続化、fork 除外。

---

## 進め方の原則
- 各フェーズは「常に動く状態」を保つ（`0 → 1 → 2 → 3 → 4 → 5` の順を基本）。
- フェーズ着手前にこの roadmap を更新し、重要な分岐を決めたら `docs/decisions.md` に追記する。
- 実装は原則 Codex に handoff（`docs/ai-roles-and-workflow.md` §1・§7）。
