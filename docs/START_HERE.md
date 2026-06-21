# PrismStar — START HERE（ゼロコンテキスト復帰の入口）

新しいセッションや、記憶が無い状態で再開するときは、まずこのファイルを読む。

**PrismStar**（通称 Prism）— 「**全員が発信者**」のマルチユーザー作品プラットフォーム ＋ GitHub 連携。
タグライン **"Shine in every color."**

---

## どこに何があるか
- **技術仕様**（スタック/構成/データモデル/API/要件）：`docs/spec.md`
- **実装計画と進捗**：`docs/roadmap.md`
- **意思決定と理由**（ADR-001〜011）：`docs/decisions.md`
- **AI 開発の役割・フロー**（User / Claude Code / Codex の3者）：`docs/ai-roles-and-workflow.md`
- **実装者（Codex）向け repo 規則**：`AGENTS.md`
- **各フェーズの Codex 実装指示**：`docs/phase-*-handoff.md`
- **既存コードのロジック詳細**：`PROJECT.md`
- **使い方 / 起動**：`README.md`

## 現在地（2026-06-21 時点）
- **Phase 0–5 完了＝「一回完成」（v1 MVP）達成。** 作業ブランチは **`develop`**。
  - 0 ベースライン / 1 既存機能の仕上げ / 2 PrismStar リブランド / 3 発信者オンボーディング（登録・プロフィール・新着フィード）/ 4 GitHub 連携（`.env` 化・サーバ側 token・SSRF対策）/ 5 スター ⭐（付与・獲得数）。
- **技術**：HTML / CSS / Vanilla JS ＋ PHP 8.2(Apache) ＋ MySQL 8.0 ＋ Docker Compose。
- **AI 運用**：ChatGPT 無し。`User ⇄ Claude Code → Codex`。実装は Codex に `docs/phase-*-handoff.md` で渡し、Claude Code が design-intent review、User が commit。

## 起動（詳細は README.md）
```bash
cp .env.example .env      # ← 必須（DB認証情報。任意で GITHUB_TOKEN）
docker compose up -d
docker compose exec app php /var/www/html/src/seed.php     # 初回
docker compose exec app php /var/www/html/src/migrate.php  # 冪等
# http://localhost:8080/gallery-list.html
```
※ **Docker 等プロセスの起動は User の許可を取ってから**（`docs/ai-roles-and-workflow.md` §3 共通）。

## 次にやること（「後で修正」/ ADR-010 MVP-first の残）
`docs/roadmap.md` の Phase 6 ＋ 各フェーズの 🧪後で修正：
- 視覚 polish（虹色テーマ・カード/スター/プロフィールの見た目）
- `register` の重複 INSERT を try/catch で 409 に（TOCTOU）
- README の起動手順に `cp .env.example .env` を追記
- GitHub repos の `gallery` 永続化（source 種別・OG画像・重複処理）
- 画像アップロード、ページネーション、fork 除外
