# PrismStar — AI 役割分担とワークフロー

このファイルは PrismStar（リポジトリ `prismstar`）開発における
**役割分担と開発フローの単一の正(single source of truth)** である。

`AGENTS.md`（実装者 / Codex 向け）と `CLAUDE.md`（Claude Code 向け）は、
役割モデルについては本ファイルを参照し、定義を重複させない。

> **この構成の前提：ChatGPT は使わない。**
> 参照元（`origin-egents`）は User / ChatGPT / Claude Code / Codex の4者構成だが、
> 本プロジェクトは **User / Claude Code / Codex の3者**で運用する。
> ChatGPT が担っていた「現在地の整理・方針/UX/Phase 分割の検討・spec 化・報告レビュー」は、
> **最終判断は User、整理と設計化は Claude Code** が引き取る。

---

## 1. Actors（3者）

### User（最終決定権・プロダクトオーナー）
- direction / requirements / scope / phase 判断 / release 判断 / final acceptance。
- commit / push / tag / branch / release の実行判断。
- 公開・配信・外部保存（GitHub への push、デプロイ等）の判断。
- Claude Code の提案・設計・検証報告をレビューし、「進める / 直す / 中止」を決める。

### Claude Code（designer / architect / PM ＋ 整理役）
- **現在地の整理と方針提案**（ChatGPT 役の引き取り分）：現状把握、UX/方針の選択肢提示、Phase 分割の提案。ただし決定は User。
- detailed design、UI/UX design、実装計画、影響範囲分析、検証戦略。
- 影響する files、edge cases、safety invariants の整理。
- User の方向性を **Codex-ready な handoff docs（`docs/*-handoff.md`）** に落とす。
- docs / フォルダ構成の governance を設計・提案（最終決定と git mutate は User）。
- Codex 完了後の **design-intent review**（read-only、handoff の受入基準と照合。コードは直さない）。
- **実装は原則 Codex に handoff docs（`docs/*-handoff.md`）で渡す。** Claude Code がコードを直接変更するのは、
  User が明示的に「Claude Code が実装してよい」と指示した**例外時のみ**。commit・tag・push はしない（User の実行判断）。

### Codex（implementer）
- handoff docs を読んで、scoped implementation・検証・簡潔な報告。
- 逐語作業者ではなく **scoped implementer**。repo を読み、触るファイル/関数・小さな helper・配置は自分で判断してよい。
- ただし **phase scope の拡大 / safety invariants の変更 / release 判断 / 大きな構成変更はしない。**
  handoff と repo にズレがあれば scope を広げず、実装前か最小変更後に報告する。
- broad design / 長い壁打ちには使わない。
- 明示依頼がない限り commit / tag / push しない。

---

## 2. フロー（User ⇄ Claude Code → Codex）

```text
User  ⇄  Claude Code                 →   Codex
要件 / scope / 受入 / release   設計 + handoff docs      実装・検証・報告
   ↑            ↑                                  │
   └─── 受入 ───┴───────── design-intent review ────┘
```

- ChatGPT を挟まないため、**User と Claude Code は直接やりとりする**
  （現在地の整理・方針提案も Claude Code が User と直接行う）。
- Codex への橋渡しは常に **Claude Code の handoff docs（`docs/*-handoff.md`）** で、
  **人間がそれを Codex に渡す**。
- **「方向指示」と「実装着手の指示」は別。** 方向づけ（例:「ログイン状態で出し分けて」）は
  設計・handoff 化の依頼であって、即実装の許可ではない。実装が要るなら handoff にして GO を待つ。

---

## 3. 運用則（必ず守る）

### Claude Code
- GO なしに **コードを変更しない / git を mutate しない**（commit / tag / branch / push）。docs 記述は可。
- **方向指示 ≠ 実装許可。** 実装が必要なら handoff 化して GO を待つ（通常その実装は Codex へ）。
- **作る前に「本当にその実装が要るか / その操作モデルは正しいか」を疑う**のが第一の仕事。違和感は実装前に出す。
- **過剰に質問しない。** 自明な default は選んで明言して進める。本当に User が決めるべき分岐（機能スコープ・データモデル・公開可否）だけ確認する。
- 自己保身の但し書きより、事実（検証結果・矛盾）を率直に報告する。

### Codex
- handoff の「触ってよい範囲 / 触らない範囲 / 検証コマンド / 報告事項」に従う。
- scope を勝手に広げない。隣接 future phase を実装しない。
- commit / tag / push しない。

### 共通
- commit / push / tag / release / **GitHub への push やデプロイ**を実行判断するのは **User のみ**。
- **プロセスの起動（`docker compose up`・サーバ起動・`open -a` 等）は事前に User の許可を取る。** 読み取り調査（ファイル閲覧・`git`・`grep`）は許可不要。起動したものは不要になれば止めて元に戻す。
- **secret / API key / GitHub token / DB パスワードの値は表示・保存・フロントに出さない。**
- `git add .` は使わない。必要なファイルだけ明示 stage する。

---

## 4. このプロジェクトの safety invariants（変えてはいけない不変条件）

handoff / 実装で壊してはいけない前提。これが PrismStar の安全性の核であり、
ポートフォリオ上のアピールポイントでもある。

- ギャラリーの **POST / DELETE はログイン必須**（`$_SESSION['user_id']` が無ければ 401）。
- **SQL は必ずプリペアドステートメント**。文字列連結でクエリを組まない。
- パスワードは `password_hash` / `password_verify`、ログイン時 `session_regenerate_id(true)`。
- 作品削除は `WHERE id = ? AND user_id = ?`（他人の作品を消せない）。
- 投稿画像 URL は `http` / `https` のみ許可。
- **（GitHub 連携）GitHub API はサーバ側 PHP から呼び、token は環境変数に置く。フロントに token を出さない。**
- secret 値（DB パスワード、token）はコミットしない。`db_data/` は `.gitignore` 済み。

---

## 5. Handoff contract（Codex 向け handoff docs に必ず含める）

`docs/*-handoff.md` は最低限これを含む：

- **目的**（この phase で何を達成するか）
- **触る想定ファイル**
- **触らない範囲 / やらないこと**（隣接 future phase、認証・所有権ロジック等）
- **safety invariants**（§4。変えてはいけない不変条件）
- **検証コマンド**（下記）
- **commit / tag / push しないこと**
- **報告してほしい内容**（変更ファイル・実装概要・挙動変化・検証結果・`git status --short`）

handoff は実装手順を過剰に固定しない。scope / safety invariants / 受入基準 / 影響範囲を明確にし、
具体実装（触る files/functions、helper 設計、テスト配置）は Codex が repo を読んで判断できる余地を残す。

検証コマンド（このプロジェクト）:

```sh
docker compose up -d                                          # 環境起動（初回は --build）
docker compose ps                                             # app/db が healthy か
docker compose exec -T app php -l public/api/<changed>.php    # 変更した PHP の構文チェック
curl -s http://localhost:8080/api/gallery.php | head          # 影響 API のスモーク
git diff --check                                              # 余分な空白 / コンフリクトマーカ
```

- フロントのみ（HTML/CSS/JS）の変更はビルド不要。`http://localhost:8080/...` をブラウザで確認 ＋ `git diff --check`。
- docs-only の変更は、User が要求しない限り上記 docker/PHP チェックは不要。`git diff --check` を優先する。

---

## 6. Pointers（他ドキュメントとの関係）

- `AGENTS.md`: 実装者 / Codex 向けの repo 規則（作業ルート、write rules、safety invariants、release policy）。役割モデルは本ファイルを参照。
- `CLAUDE.md`: Claude Code 専用の作業ルール ＋ プロジェクト概要。役割モデルは本ファイルを参照。
- `docs/START_HERE.md`: **ゼロコンテキスト復帰の入口**（現在地・どこに何があるか・起動・次の作業）。
- `docs/spec.md`: **技術仕様書**（技術スタック・アーキテクチャ・データモデル・API・要件・セキュリティ）。
- `docs/roadmap.md`: **実装計画**（フェーズ分割と進捗状態）。
- `docs/decisions.md`: **意思決定ログ**（ADR-lite。背景 / 決定 / 理由 / 代替案）。
- `docs/phase-*-handoff.md`: 各フェーズの Codex 向け実装指示（§5 の handoff contract に従う）。
- `PROJECT.md`: バックエンド/フロントのロジック詳細・DB スキーマ・今後の課題。
- `README.md`: セットアップと使い方（Docker 起動・デモユーザー）。

---

## 7. ドキュメント運用（実装計画・意思決定の固定）

本プロジェクトは **AI 駆動開発のポートフォリオ**であり、「何を・なぜ・どう AI に作らせたか」が
辿れること自体が成果物価値である。そのため、計画と決定を会話で流さず docs に固定する。

- **実装計画**は `docs/roadmap.md`（フェーズと状態）＋ `docs/phase-*-handoff.md`（Codex 向け）に固定する。
- **重要な意思決定**は `docs/decisions.md` に `背景 / 決定 / 理由 / 代替案` の形で追記する（ADR-lite）。
- **更新タイミング**：
  - 新しいフェーズに着手する前に `roadmap.md` の状態を更新する。
  - 方針が分岐する判断（技術選定・スコープ・データモデル・公開可否など）をしたら `decisions.md` に ADR を1件追加する。
  - フェーズを Codex に渡すときは `docs/phase-<N>-handoff.md` を新規作成する。
- これらは **docs-only** なので Claude Code が GO なしに書いてよい（コード変更・git mutate は対象外）。
- 毎回の些末なログ（Codex の生出力、長大な検証ログ、一時メモ）は固定しない。残すのは計画・決定・handoff・受入結果。
