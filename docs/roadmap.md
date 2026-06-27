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

## 認証動線・ナビ UX（フロント・独立トラック）✅ **ゲート型（[ADR-019](decisions.md)）**
ヘッダーの**ログイン/新規登録ボタン**・**検索バー常設**・**未ログイン操作時のモーダル誘導**は維持。旧前提「閲覧は自由」は **[ADR-019](decisions.md) のゲート型回遊で上書き**：未ログインはスタート画面の teaser まで、詳細/検索結果/ユーザーページ等は登録/ログイン モーダルでゲート（`docs/requirements-v2.md` §7・§7.1）。見出しは「おすすめ」。
- ✅ 確認：`header.php` にゲートモーダル＋常設検索、`common.js` の `showAuthGate()`／`bindAuthGate()`／`bindHeaderSearch()`、未ログイン時の `data-gated-link` 誘導。ゲートにも再設定動線（[ADR-023](decisions.md)）。

## Phase 7 — 公開 / 非公開（可視性の土台）✅
作品に公開/非公開の2状態を持たせる（[ADR-014](decisions.md)）。`gallery.visibility`（既定 公開）を冪等 migration で追加 ＋ **一覧・検索・ユーザーページの全 SELECT に公開フィルタ**、詳細は「公開＝誰でも／非公開＝本人のみ」。作成時に可視性を選べる・後から切替可。**非公開を所有者以外に返さない**ことが核。
- **seed をテスト fixture に格上げ**（[ADR-020](decisions.md)）：各ユーザー（demo/Aoi/Ren/Mio）に**公開＋非公開を混在**させ、非公開遮断・所有・検索の自分除外を実データで検証できるようにする。
- ✅ 確認（Claude Code review）：`gallery.php` 一覧は `WHERE g.visibility='public'`、詳細は `(g.visibility='public' OR g.user_id=?)`→他人の非公開は 404。PATCH/DELETE は `WHERE id=? AND user_id=?`。`search.php`／`users.php` も公開のみ。

## Phase 8 — マイページ（管理画面）＋ 作品作成 / 編集 ✅
本人専用のマイページ（=管理画面）を公開プロフィール（ユーザーページ）と別に新設（[ADR-013](decisions.md)）。本人の**全作品（非公開含む）**一覧＋新規作成・編集・削除・公開切替・GitHub 設定。作成/編集はモーダルから**専用フォーム画面**へ格上げ。ユーザーページ（`profile.php`）は**公開作品のみ**に整理。
- **公開の作品詳細ページから v1 のインライン削除ボタンを撤去**し、編集/削除/公開切替はマイページに集約（詳細は閲覧専用＋所有者には「編集」導線のみ／`requirements-v2.md` §3）。
- ✅ 確認：`mypage.php`／`mypage.js`（`?mine=1` で非公開含む一覧・公開切替・編集→`work-edit.php`・削除・GitHub 設定）、`work-edit.php`／`work-edit.js`（専用フォーム）、`gallery-detail.js` は所有者のみ「編集」導線・旧インライン削除なし。

## Phase 9 — 画像アップロード ✅
作品画像をサーバ保存に対応（[ADR-015](decisions.md)）。`public/uploads/` 保存・`src` にパス。MIME/拡張子/サイズ検証・ファイル名サーバ生成（safety invariant 追加）。作成/編集フォームに統合（外部 URL も併存可）。
- ✅ 確認：`gallery.php` の `storeUpload()` が **拡張子allowlist(jpg/png/webp/gif)＋finfo の MIME 一致＋5MB＋`random_bytes` 由来のファイル名**で保存、`public/uploads/.htaccess` で PHP 実行を無効化。`work-edit.js` は画像ファイル or 外部 URL を送信。

## Phase 10 — タグ ✅
作品にタグを付与（[ADR-017](decisions.md)）。`tags` ＋ `gallery_tags`（多対多）。作成/編集にタグ入力、詳細/カードにタグ表示。
- ✅ 確認：`gallery.php` の `normalizeTags()`（重複排除・最大10）＋`syncTags()`（タグ upsert→`gallery_tags` 張り直し）、読み出しは `GROUP_CONCAT`。`gallery-list.js`／`gallery-detail.js`／`profile.js` がカード・詳細に `#tag` 描画、`search.php` もタグ検索対応。

## Phase 11 — 検索 ✅
検索画面 `search.php`（`docs/requirements-v2.md` §6）。ワード（タイトル/説明）＋タグで**公開作品**を検索（**自分の作品は除く**）、ユーザー（名前/GitHub）検索。
- ✅ 確認：`api/search.php` は作品（`title`/`description`/タグ LIKE）とユーザー（`name`/`github_username` LIKE）の2系統、`WHERE g.visibility='public' AND (?=0 OR g.user_id<>?)` で**公開のみ＋自分除外**。ヘッダー検索（`common.js`）は `search.php?q=` へ。
- 🔧 入口の一本化（[ADR-025](decisions.md)・✅ 実装・コミット済）：`search.php` の独自フォームとヘッダー検索が二重だったので、**ヘッダーの種別プルダウン（キーワード/タグ/ユーザー）に集約**し search 画面はフォーム撤去＝結果専用に。API は不変。handoff：`docs/search-consolidation-handoff.md`（下記「検索の入口一本化」節も参照）。

## Phase 12 — 登録フロー（double opt-in / メール確認先行）✅（独立トラック）
登録を**メール確認先行**に変更（[ADR-018](decisions.md)／`requirements-v2.md` §7.2）。① email 入力 → ② 確認URL送信 → ③ 表示名/パスワード設定 → ④ 本登録・自動ログイン。`pending_registrations` を冪等 migration で追加、`verify.php` 新設、`auth.php` の register を **request / complete の2アクションに分割**。**ローカルのメール送信は MailHog を docker-compose に追加**（Web UI `:8025`、SMTP は `.env`）。token は hash 保存・単回・期限／enumeration 対策／email 一意は④で最終チェック。
- ✅ 確認（Claude Code review）：`auth.php` の `register-request`/`register-complete`、token は `random_bytes(32)`→`sha256` 保存・期限24h・単回。存在の有無で応答を変えない neutral（送信失敗も `error_log` に留め neutral）。`register-complete` で email 一意を最終チェック（request〜complete 間の TOCTOU を塞ぐ）。

## パスワードリセット（独立トラック・[ADR-021](decisions.md)）✅
認証の基本機能（再設定）を揃える。登録 double opt-in と同じメールトークン方式で `forgot.php`→メール（MailHog）→`reset.php?token=`→新パスワード→ログイン。`password_resets` テーブル追加、`auth.php` に reset-request / reset-complete、login の死にリンク（`href="#"`）を `forgot.php` へ。token は 256bit・sha256 hash・単回・**期限1h**、enumeration 対策。handoff：`docs/password-reset-handoff.md`。
- ✅ 確認（Claude Code review）：`reset-complete` は `UPDATE users ... WHERE id = $reset['user_id']`（更新対象はトークン照合で得た user_id＝**クライアント値を信用しない**）、トークンは単回（削除）・期限1h、`reset.php` は token を `htmlspecialchars(...,ENT_QUOTES)` で出力。ゲートモーダルにも再設定動線（[ADR-023](decisions.md)）。

## 静的アセット cache-busting（独立トラック・[ADR-022](decisions.md)）✅
v2 で JS を全面刷新 → 再訪問ブラウザが古い JS を掴んで壊れる事象が発生。JS/CSS の URL に**バージョンクエリ（`?v=filemtime`）**を付ける。`asset()`（`public/includes/asset.php`）ヘルパーで `head.php`（共通 CSS＋`$pageStyles`）・`footer.php`（`common.js`）・各ページの `<script>` を置換。挙動不変。Codex 実装・Claude Code review 済（12 ページ＋common.js の結線網羅・生参照ゼロ・全ページ 200・`?v=` 出力を独立確認）。`Cache-Control` は Docker Apache に `mod_headers` が無く見送り、version クエリ単独で成立（[ADR-022](decisions.md) 実装メモ）。handoff：`docs/cache-busting-handoff.md`。

> 🎉 **v2 機能セット（可視性・マイページ/作成編集・画像アップロード・タグ・検索・double opt-in 登録・パスワード再設定・ゲート型回遊・cache-busting）は実装到達。** 以降は下記の発展。

## GitHub 取り込みの永続化 ✅（[ADR-024](decisions.md)）
看板の「全員が発信者 ＋ GitHub 取り込み」を表示レイヤーから昇格。リポジトリを **`gallery` の作品として保存**し ⭐・検索・おすすめ・プロフィールに乗せる。`gallery` に `source`／`source_url`＋UNIQUE、サーバ側で GitHub を再取得して保存値を確定（client 非信頼）、`(user_id, source_url)` で de-dupe（再取り込みはリフレッシュ・visibility 保持）、fork 除外、取り込みはマイページ、サムネは OG カード画像。編集は visibility/タグのみ（title/説明/画像は import が唯一の writer）。Codex 実装・Claude Code review 済。handoff：`docs/github-import-handoff.md`。

## 検索の入口一本化 ✅（[ADR-025](decisions.md)）
ヘッダー検索に集約：対象プルダウン（作品/ユーザー）＋入力、作品は普通の語＝キーワード（タイトル/説明）・`#` 始まり＝タグ検索。`search.php` のページ内フォームを撤去し結果専用に。空検索＝全件、先頭 `#` 正規化。Codex 実装＋Claude Code 直接修正・review 済。handoff：`docs/search-consolidation-handoff.md`。

---

## 優先トラック（堅牢性・基本機能の穴埋め）✅
機能の核が出揃った後、「プラットフォームとしての堅牢性・基本機能の穴埋め」を ①→②→③ の順で実施。3本とも完了。

### ① セキュリティ強化（CSRF / セッション Cookie）✅（[ADR-026](decisions.md)）
マルチユーザーで投稿/削除/スター/取り込み/認証があるのに CSRF 防御が無く、セッション Cookie の属性も未設定だった。セッション開始を `src/session.php` に共通化して **SameSite=Lax＋HttpOnly＋Secure** を付与（cross-site CSRF をほぼ封殺）し、状態変更系に **CSRF トークン**（中央化した `requireCsrf` 検証＋`common.js` の fetch 共通付与）。Codex 実装・Claude Code review 済。handoff：`docs/security-hardening-handoff.md`。

### ② アカウント設定（表示名・パスワード変更）✅（[ADR-028](decisions.md)）
ログイン中に表示名・パスワードを変更できなかった（変更可は github_username のみ）。マイページに「アカウント設定」を追加し、表示名は `users.php`（送られたキーだけの部分更新＋session 同期）、パスワードは `auth.php?action=change-password`（現行パスワード確認＋`session_regenerate_id`）。CSRF は①のラッパで自動。Codex 実装・Claude Code review 済。handoff：`docs/account-settings-handoff.md`。

### ③ 発見系の役割分担（検索＝もっと見る／おすすめ＝新着・人気の2軸ランキング）✅（[ADR-029](decisions.md)）
一覧・検索が全件返しだった。**検索（works）は「もっと見る」段階読み込み**（`PER_PAGE+1` で hasMore 判定＝`COUNT` 不要・`LIMIT/OFFSET` は int 化）、**おすすめ（トップ）は網羅をやめ「新着 トップ5」「人気 トップ5」の2軸ランキング**（pixiv 風の横1列レーン・各 `LIMIT 5`・順位 #1〜・自分除外維持・未ログインは teaser＋クリックでログイン催促）。人気順を可視化するため seed に star を傾斜配分（ユーザー増＋冪等）。当初は両方ページングする案だったが、**トップ＝キュレーション／検索＝網羅**と役割を分けた。Codex 実装・Claude Code review 済。handoff：`docs/pagination-handoff.md`。

## マイページ分割（作品管理 ／ 設定）✅（[ADR-030](decisions.md)・実装・コミット済）
マイページ（管理画面）が3関心（作品管理／アカウント／GitHub連携）を1ページに詰め込み窮屈だったので、**2ページに分離**する（[ADR-030](decisions.md)）。`mypage.php` を作品管理ハブ（作品一覧・新規作成・GitHub取り込み）に純化し、表示名・パスワード・GitHub username 設定を新規 `settings.php` に集約。取り込みは「作品を増やす日常操作」としてマイページ側に残し、username 未設定時は設定への導線を出す。入口は `navItems` に足さずマイページ内リンク（設定をサブページ扱い＝work-edit の前例）。**API・スキーマ不変の純フロント IA リファクタ**で、内部 backlog の「マイページの横幅が窮屈」も同時に解消。handoff：`docs/mypage-split-handoff.md`。

## 個人系ページの再編・拡張（作品／人となりの分節）✅（[ADR-031](decisions.md)〜[ADR-034](decisions.md)）
「マイページ」が総称として広すぎた（ユーザー指摘）。**「作品＝マイページ／人となり＝プロフィール」**の分節で個人系を整理し、ハンバーガーに集約する。段階ごとに Codex handoff／review／コミット1サイクル。
- **第1段 ✅（[ADR-031](decisions.md)＋[ADR-032](decisions.md)・実装・コミット済）**：個人系（作品管理／お気に入り／プロフィール／設定）を**ハンバーガーに並列集約**し「マイページ」総称を廃止（ラベルは「作品管理」へ）。**お気に入りページ**を新設（`gallery.php ?starred=1` ＝自分がスターした公開/自作作品。片側だけだったスター機能の穴埋め）。handoff：`docs/personal-area-handoff.md`。
- **第2段 ✅（[ADR-033](decisions.md)・実装・コミット済）**：プロフィール拡充①＝`users.bio`（自己紹介）追加・**表示名を設定→プロフィールへ移設**（設定はパスワード＋GitHub username に純化）・profile に owner 編集。
- **第3段 ✅（[ADR-034](decisions.md)・実装・コミット済）**：**ユーザーネーム `@username`**（一意ハンドル）＝`users.username`（UNIQUE）追加・登録時に自動採番＋後で編集・`profile.php?u=<username>` ルックアップ（`?id=` も維持・pretty URL は後回し）。handoff：`docs/username-handle-handoff.md`。
> 第3段（@username）は schema 追加・登録フロー波及（auth.php の register-complete に採番を差し込む）を含む最大段。共有検証は `src/username.php` に集約。

## 作品詳細の「戻る」を直前の画面に返す（[ADR-035](decisions.md)）✅（実装・コミット済）
作品詳細の「戻る」が `gallery-list.php`（おすすめ）固定で、検索やプロフィールから開いても必ずおすすめへ飛んでいた。アプリ内遷移（same-origin referrer）なら `history.back()` で直前画面（検索結果ならクエリ状態ごと）に戻し、直接アクセスはおすすめへフォールバック。`gallery-detail.js` のみの小修正・open-redirect を作らない。handoff：`docs/detail-back-handoff.md`。

## 今後の発展（展望・後回し）⬜
- タグ予測（`#` 入力中に既存タグを候補表示。`type=tags` エンドポイント＋`<datalist>`）。
- ブランド表現（虹色テーマ）の作り込み・レスポンシブ細部（デザインは終盤にまとめる＝[進め方の原則]）。
- ~~username / slug（公開 URL）の導入~~ → **[ADR-034](decisions.md) に昇格**（個人系拡張 第3段）。pretty URL（`/@handle` の Apache rewrite）のみ polish として後回し。
- 検索の更なる微調整、自動テスト（安全不変条件を固定する薄い統合テスト＝機能ではないが価値あり）。

---

## 進め方の原則
- 各フェーズは「常に動く状態」を保つ（`0 → 1 → 2 → 3 → 4 → 5` の順を基本）。
- フェーズ着手前にこの roadmap を更新し、重要な分岐を決めたら `docs/decisions.md` に追記する。
- 実装は原則 Codex に handoff（`docs/ai-roles-and-workflow.md` §1・§7）。
- **機能・画面を先に作り切り、デザイン面（色・UI の視覚的な作り込み）の修正は終盤にまとめる。** 機能の完成を優先し、見た目の微調整で手を止めない（必要な視覚調整は気づいたら控えに残し、最後に一括で当てる）。
