# AGENTS.md

このリポジトリで作業する AI agent（特に実装者 / Codex）向けの運用ルール。

役割分担と開発フローの正本は `docs/ai-roles-and-workflow.md`。
本ファイルは repository scope・write rules・safety invariants・release policy など、
実装時に守る具体規則を扱う。Claude Code 固有のルールは `CLAUDE.md`。

## 構成の前提

本プロジェクトは **ChatGPT を使わない User / Claude Code / Codex の3者構成**。
Codex は実装者として、Claude Code の handoff docs（人間が渡す）を入口に作業する。
broad design / 方針検討には使わない。

## Repository Scope

作業ルートはこのリポジトリ（`prismstar/`）のルート。

- 作業ブランチは **`main`**（公開・リモート・履歴とも main が本体）。
- 主なディレクトリ：
  - `public/` … Apache ドキュメントルート（`*.html` / `style/` / `script/` / `api/*.php`）
  - `src/` … PHP 共通処理（`db.php`, `seed.php`）
  - `docker/` … `Dockerfile`, `init.sql`
  - `docs/` … 公開の設計doc（`adr/` 意思決定・`spec/` 仕様・`design/` 設計意図・`roadmap` 実装計画）

## Write Rules

- 成果物はプロジェクト直下に作る。公開の設計doc は `docs/`（`adr/`・`spec/` 等）に置く。handoff は公開 repo に出さない内部の作業doc（人間が Codex へ渡す）。
- **`git add .` は使わない。** 必要なファイルだけ明示 stage する。
- secret（DB パスワード、GitHub token）をコミットしない。`db_data/` は `.gitignore` 済み。
- `.gitignore` を勝手に変えない。

## コメント方針（実装意図を書く）

コードコメントは **「何をしているか」ではなく「なぜそうしたか（実装意図・安全不変条件・トレードオフ）」** を書く。

- ❌ 逐語説明：`// user_id でクエリする` のようにコードを読めば分かることの繰り返し。
- ⭕ 意図：`// user_id 込みで限定＝他人の作品 ID を渡されても 0 行で弾く（IDOR 防止）` のように、その行が存在する理由。
- 特に **安全不変条件**（可視性フィルタ・所有者限定・enumeration 対策・トークン設計・アップロード検証）には、守っている不変条件を一言添える。
- 自明なコードにコメントを足さない。意図が非自明な箇所に絞る。

## Safety Invariants（壊さない）

正本は `docs/ai-roles-and-workflow.md` §4。要約：

- ギャラリー POST / DELETE はログイン必須。
- SQL はプリペアドステートメント（文字列連結禁止）。
- 削除は所有者限定（`WHERE id = ? AND user_id = ?`）。
- パスワードは `password_hash` / `password_verify`。
- 投稿画像 URL は http / https のみ。
- GitHub token はサーバ側 env に置き、フロントに出さない。

## Role Split（要約）

正本は `docs/ai-roles-and-workflow.md`。要約：

- **User**：direction / scope / phase / release / acceptance / commit・push 判断。
- **Claude Code**：現在地整理・方針提案・設計・handoff docs 作成・design-intent review。明示 GO なしに実装 / commit / push しない。
- **Codex**：handoff docs を読んで scoped に実装・検証・報告。broad design に使わない。明示依頼なしに commit / tag / push しない。

フローは一方向の橋渡し（User ⇄ Claude Code → Codex）。
Codex への入力は常に Claude Code の handoff docs を人間が渡す。

## 実装依頼の標準テンプレート（Codex 向け）

```text
作業ブランチは main。まず docs/ai-roles-and-workflow.md, docs/spec/spec.md, README.md を読む。

指定の phase だけを実装する。scope を広げない。隣接 future phase を実装しない。
commit / tag / push はしない。

編集前に簡潔に報告：
- git status --short
- 触る予定のファイル
- 実装方針を 3-5 行

編集後に実行：
- docker compose ps
- docker compose exec -T app php -l <変更した PHP>
- 影響 API を curl でスモーク
- git diff --check

報告：
- 変更ファイル
- 実装概要 / 挙動変化
- 検証結果
- git status --short
```

docs-only の場合は php / docker チェック不要。`git diff --check` を優先する。

## Release Policy

- Phase は細かく、commit / tag は粗く扱う。
- commit / push / tag / GitHub push / デプロイ は **User のみ** が実行判断する。
- 小さな Phase ごとに毎回 tag を切らない。ユーザー体験がまとまって変わった時だけ release に進む。
