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
- **意思決定と理由**（ADR-001〜017）：`docs/adr/decisions.md`
- **AI 開発の役割・フロー**（User / Claude Code / Codex の3者）：`docs/ai-roles-and-workflow.md`
- **実装者（Codex）向け repo 規則**：`AGENTS.md`
- **各フェーズの Codex 実装指示（handoff）**：公開 repo に出さない内部の作業doc（人間が Codex へ渡す）
- **使い方 / 起動**：`README.md`

## 現在地（2026-06-23 時点）
- 作業ブランチは **`develop`**。技術：HTML / CSS / Vanilla JS ＋ PHP 8.2(Apache) ＋ MySQL 8.0 ＋ Docker Compose。
- **完了・コミット済**：
  - **v1（Phase 0–5）**：認証 / 既存機能仕上げ / PrismStar リブランド / 発信者オンボーディング（登録・プロフィール・新着）/ GitHub 連携（サーバ側 token・SSRF対策）/ スター ⭐。
  - **Phase 6**：PHP 部品化（`.html`→`.php`、header/footer/head を `public/includes/*.php` に共通化）。
  - **認証動線 UX**：ヘッダーにログイン/新規登録/ログアウト・「みんなの作品」見出し・未ログイン⭐でログイン誘導。
- **完了・未コミット**：所有モデル修正 ＝ 公開詳細から削除ボタン撤去（管理はマイページへ）＋ `seed.php` 複数ユーザー化（demo/Aoi/Ren/Mio・全員 password123）。未コミット＝ `gallery-detail.php` / `gallery-detail.js` / `src/seed.php` / `docs/highlights.md`。
- **AI 運用**：ChatGPT 無し。`User ⇄ Claude Code → Codex`。**実装は Codex へ handoff、Claude Code は設計・整理・review のみ（コードを直接書かない）**。commit / push は User が区切りで判断。

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
次セッションは **START_HERE ＋ メモリ** を読んでから、最初の一手を1つ選ぶ。
※ どちらも最初は **Claude Code が spec/handoff を書く（実装はしない）**。

- **A. デザイン方針を固める**（レイアウト要改善・推奨）：
  Claude Code が design 方針を1枚化（虹色ブランド／配色トークン／グリッド・カード／ヘッダー／余白・タイポ／レスポンシブ）＝"ターゲット" → 実装ツールを選ぶ（視覚ツール＝Antigravity/Claudeでスクショ反復、または Codex＋目視QA）。
- **B. Phase 7（公開/非公開）**：
  Phase 7 の handoff を書く → Codex 実装 → review。その後 8 マイページ → 9 画像アップロード → 10 タグ → 11 検索（`docs/spec/requirements-v2.md`）。

いつでも（User の区切りで）：**未コミットの所有モデル修正**をコミット。

その先：ページネーション、GitHub 取り込み作品の永続化（[ADR-016](adr/decisions.md)）、登録の堅牢化、`[マイページ]` リンク（Phase 8 で有効化）。

> 細かな作業メモ（既知の粗・ドキュメント不備など）は repo に置かず、運用側（Claude Code のメモリ）で管理する。
