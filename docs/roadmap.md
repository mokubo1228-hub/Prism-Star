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
- 🧪 **後で修正**（ADR-010）：虹色テーマ／ロゴ／配色、ヘッダーのモバイル微調整、login/form のタグライン重複、内部 doc（CLAUDE.md / PROJECT.md）の名称統一、フォルダ / リポジトリ名。視覚確認はブラウザ推奨。

## Phase 3 — 発信者オンボーディング ⬜
「**全員が発信者**」を成立させる核（[ADR-008](decisions.md)）。
- オープンな**新規登録**（`auth.php?action=register` ＋ 登録画面）。今はデモユーザーのみ。
- **プロフィール / 個人ギャラリー**（`?user=` で `WHERE user_id=?`。既存スキーマでほぼ作れる）。
- **全員の新着フィード**（一覧をフィード化）。

## Phase 4 — GitHub 連携 🎯 ⬜
- `api/github.php` を新設＝**サーバ側で token を隠して** GitHub API を呼ぶ（[ADR-003](decisions.md)）。
- ユーザーが GitHub ユーザー名を登録 → 自分のリポジトリを「作品」として取り込み / 表示。
- `gallery.source`（manual / github）等のスキーマ拡張（`docs/spec.md` §4）。

## Phase 5 — スター機能 ⭐ ⬜
- お気に入り＝**スター(⭐)**（[ADR-009](decisions.md)）。`stars` テーブル ＋ `stars.php`。
- 取り込んだ GitHub リポは GitHub の⭐数を表示、サイト内作品はユーザーが星を付与。
- プロフィールに**獲得スター総数** → "スターを集める" を成立させる。

## Phase 6 — 仕上げ（任意）⬜
- 画像ファイルのアップロード（現在は外部 URL のみ）、ページネーション 等。

---

## 進め方の原則
- 各フェーズは「常に動く状態」を保つ（`0 → 1 → 2 → 3 → 4 → 5` の順を基本）。
- フェーズ着手前にこの roadmap を更新し、重要な分岐を決めたら `docs/decisions.md` に追記する。
- 実装は原則 Codex に handoff（`docs/ai-roles-and-workflow.md` §1・§7）。
