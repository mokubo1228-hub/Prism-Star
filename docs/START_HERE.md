# PrismStar — START HERE（ゼロコンテキスト復帰の入口）

新しいセッションや、記憶が無い状態で再開するときは、まずこのファイルを読む。

**PrismStar**（通称 Prism）— 「**全員が発信者**」のマルチユーザー作品プラットフォーム ＋ GitHub 連携。
タグライン **"Shine in every color."**

---

## どこに何があるか
- **読みどころ**（こだわり・判断軸・技術選定の理由）：`docs/highlights.md`
- **技術仕様**（スタック/構成/データモデル/API/要件）：`docs/spec/spec.md`
- **v2 の要件・動線**（マイページ/公開非公開/作成編集/タグ/検索）：`docs/spec/requirements-v2.md`
- **実装計画と進捗**：`docs/roadmap.md`
- **意思決定と理由**（ADR-001〜047）：`docs/adr/decisions.md`
- **AI 開発の役割・フロー**（User / Claude Code / Codex の3者）：`docs/ai-roles-and-workflow.md`
- **実装者（Codex）向け repo 規則**：`AGENTS.md`
- **各フェーズの Codex 実装指示（handoff）**：公開 repo に出さない内部の作業doc（人間が Codex へ渡す）
- **使い方 / 起動**：`README.md`

## 現在地（2026-07-04 時点）
- 本体ブランチは **`main`**（公開・履歴とも main）。技術：HTML / CSS / Vanilla JS ＋ PHP 8.2(Apache) ＋ MySQL 8.0 ＋ Docker Compose。
- **PrismStar として公開済み** — 元「Okubo Gallery（大久保の館）」からの継続開発。DB 名も `prismstar` に統一。
- **機能はひと通り完成**（v1 ＋ v2 ＋ 堅牢性 ＋ 個人系IA ＋ 発見系）：
  - **v1（Phase 0–5）**：認証 / 既存機能仕上げ / リブランド / 発信者オンボーディング / GitHub 連携（サーバ側 token・SSRF対策）/ スター ⭐ / PHP 部品化。
  - **v2**：公開・非公開／マイページ・作品作成編集／画像アップロード／タグ／検索／登録 double opt-in／パスワード再設定／ゲート型回遊（メンバー制・ADR-046）／cache-busting。
  - **堅牢性 ①②③**：CSRF・セッション Cookie ／ アカウント設定（表示名・パスワード）／ 発見系の役割分担（検索＝番号付きページング＋総件数・表示件数切替／おすすめ＝新着・人気の2軸ランキング）。
  - **個人系IA**：マイページ分割／お気に入り／プロフィール（bio・アバター）／`@username`／作品詳細の「戻る」を直前画面へ。
  - **その他**：GitHub 取り込みの `gallery` 永続化、seed を ~500 公開作品 / ~40 ユーザーに増量、作品カード UI 統一、安全不変条件＋検索契約の統合テスト（PHPUnit）。
- **意思決定は ADR-001〜047**（`docs/adr/decisions.md`）。作業は基本 commit 済み。
- **AI 運用**：ChatGPT 無し。`User ⇄ Claude Code → Codex`。実装は Codex へ handoff（handoff は非公開の内部作業doc・人間が渡す）、Claude Code は設計・整理・review のみ（コードを直接書かない）。commit / push は User が区切りで判断。

## 起動（詳細は README.md）
```bash
cp .env.example .env      # ← 必須（DB認証情報。任意で GITHUB_TOKEN）
docker compose up -d
docker compose exec app php /var/www/html/src/seed.php     # 初回
docker compose exec app php /var/www/html/src/migrate.php  # 冪等
# http://localhost:8080/  （または gallery-list.php）
```
※ **Docker 等プロセスの起動は User の許可を取ってから**（`docs/ai-roles-and-workflow.md` §3 共通）。

## 次にやること
機能はひと通り完成。以降は **polish と「今後の発展」バックログ**（`docs/roadmap.md` 末尾）から、User の区切りで一つずつ。機能価値を優先し、視覚調整で手を止めない方針。

- **視覚 polish**：虹色ブランド表現（ロゴ・配色トークン）、レスポンシブ細部。
- **今後の発展**：タグ予測（`#` 入力中の候補表示）、pretty URL（`/@handle` の Apache rewrite）、検索の微調整。
- **小残務**：気づいた粗は運用側メモに控え、区切りで一括で当てる。

実装が要るものは Claude Code が handoff 化 → Codex 実装 → review の順（Claude Code はコードを直接書かない）。

> 細かな作業メモ（既知の粗・ドキュメント不備など）は repo に置かず、運用側（Claude Code のメモリ）で管理する。
