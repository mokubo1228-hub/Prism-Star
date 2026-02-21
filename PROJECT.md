# PROJECT — ロジック詳細

## ディレクトリ構成

```
myport-site/
├── docker-compose.yml       … app(PHP+Apache) + db(MySQL) の2コンテナ
├── docker/
│   ├── Dockerfile           … PHP 8.2-apache、pdo_mysql 有効化
│   └── init.sql             … テーブル定義（users, gallery, contacts）
├── src/
│   ├── db.php               … PDO接続ヘルパー
│   └── seed.php             … 初期データ投入スクリプト
├── public/                  … Apache ドキュメントルート
│   ├── api/
│   │   ├── gallery.php      … ギャラリー CRUD
│   │   ├── auth.php         … ログイン/ログアウト
│   │   └── contact.php      … お問い合わせ送信
│   ├── Script/
│   │   ├── common.js        … 共通ナビ生成 + ハンバーガーメニュー
│   │   ├── gallery-list.js  … 一覧取得・投稿
│   │   ├── gallery-detail.js… 詳細取得
│   │   ├── login.js         … ログインフォーム制御
│   │   └── form.js          … お問い合わせフォーム制御
│   ├── Style/               … ページごとのCSS
│   ├── Image/               … SNSアイコン画像
│   └── *.html               … 各ページ
└── mikansei-Page/           … 未完成ページ（開発中）
```

---

## バックエンド（PHP API）

### src/db.php — DB接続

- `getDb()` 関数で PDO インスタンスを返す（シングルトン）
- 環境変数 `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` から接続情報を取得
- `ERRMODE_EXCEPTION` でエラー時は例外をスロー
- `EMULATE_PREPARES = false` でネイティブプリペアドステートメントを使用

### src/seed.php — 初期データ投入

- `users` テーブルにデモユーザーを1件 INSERT
- パスワードは `password_hash()` で bcrypt ハッシュ化
- `gallery` テーブルに15件の作品データを INSERT
- 二重実行防止: `SELECT COUNT(*) FROM users` が0の時だけ実行

### api/gallery.php — ギャラリーCRUD

```
GET  /api/gallery.php          → 全作品を JSON 配列で返す
GET  /api/gallery.php?id=1     → 1件取得（404 なら error を返す）
POST /api/gallery.php          → 新規投稿（要ログイン）
DELETE /api/gallery.php?id=1   → 削除（要ログイン、自分の作品のみ）
```

**POST のバリデーション:**
1. セッションに `user_id` がなければ 401
2. `title` と `src` が空なら 400
3. `src` が http:// または https:// でなければ 400（`parse_url` でチェック）

**DELETE の制御:**
- `WHERE id = ? AND user_id = ?` で他人の作品を削除できないようにしている

### api/auth.php — 認証

```
POST /api/auth.php?action=login   → ログイン
POST /api/auth.php?action=logout  → ログアウト
GET  /api/auth.php?action=status  → ログイン状態確認
```

**ログイン処理の流れ:**
1. リクエストボディから `email`, `password` を取得
2. `users` テーブルを email で検索
3. `password_verify()` でハッシュと照合
4. 成功したら `session_regenerate_id(true)` でセッション固定攻撃を防止
5. `$_SESSION` に `user_id`, `user_email`, `user_name` を保存

**ログアウト処理:**
- `$_SESSION = []` + `session_destroy()` でセッションを完全破棄

### api/contact.php — お問い合わせ

```
POST /api/contact.php → お問い合わせをDBに保存
```

**バリデーション:**
1. `first_name`, `last_name`, `email` が必須
2. `filter_var(FILTER_VALIDATE_EMAIL)` でメール形式チェック
3. バリデーション通過後、`contacts` テーブルに INSERT

---

## フロントエンド（JavaScript）

### common.js — 共通処理

**ナビゲーション生成:**
- `navItems` 配列にリンク先とラベルを定義
- ページ内の全 `#nav-item-template` を `querySelectorAll` で取得
- `<template>` を `cloneNode(true)` でコピーし、ヘッダーとフッター両方に展開

**ハンバーガーメニュー:**
- `.hamburger` クリックで `.header-nav` と `.nav-overlay` に `active` クラスをトグル
- オーバーレイは JS で `document.createElement('div')` して動的に追加
- オーバーレイクリックでもメニューを閉じる

### gallery-list.js — ギャラリー一覧

**一覧描画:**
1. `fetch("/api/gallery.php")` で全作品を取得
2. `renderItem()` で `<template>` からDOMを生成し `gallery` に追加
3. 失敗時は「読み込みに失敗しました」を表示

**モーダル投稿:**
1. 「＋」ボタンクリックで `postModal.style.display = "flex"`
2. 閉じるボタン or オーバーレイクリックで非表示
3. submit 時:
   - `isSafeUrl()` で URL プロトコルチェック（http/https のみ許可）
   - `fetch POST` でサーバーに送信
   - 成功したら `renderItem()` で即座にDOMに追加

### gallery-detail.js — 作品詳細

1. `URLSearchParams` から `id` を取得
2. `fetch("/api/gallery.php?id=...")` で1件取得
3. `<template>` からDOM生成してタイトル・画像・説明を埋める
4. 取得失敗時は「見つかりません」とギャラリーへの戻りリンクを表示

### login.js — ログイン

1. フォーム submit で `preventDefault()`
2. email と password の空チェック
3. `fetch POST` → `/api/auth.php?action=login`
4. 成功で `gallery-list.html` にリダイレクト
5. 失敗でサーバーからのエラーメッセージを `alert` 表示

### form.js — お問い合わせ

1. フォーム submit で `preventDefault()`
2. 必須項目（姓・名・メール）の空チェック
3. `fetch POST` → `/api/contact.php`
4. 成功で「受け付けました」alert + フォームリセット

---

## データベース

### users テーブル

| カラム | 型 | 説明 |
|---|---|---|
| id | INT AUTO_INCREMENT | 主キー |
| email | VARCHAR(255) UNIQUE | ログインID |
| password_hash | VARCHAR(255) | bcrypt ハッシュ |
| name | VARCHAR(100) | 表示名 |
| created_at | DATETIME | 作成日時 |

### gallery テーブル

| カラム | 型 | 説明 |
|---|---|---|
| id | INT AUTO_INCREMENT | 主キー |
| user_id | INT (FK → users.id) | 投稿者 |
| title | VARCHAR(200) | 作品タイトル |
| src | VARCHAR(500) | 画像URL |
| description | TEXT | 説明文 |
| created_at | DATETIME | 投稿日時 |

### contacts テーブル

| カラム | 型 | 説明 |
|---|---|---|
| id | INT AUTO_INCREMENT | 主キー |
| first_name | VARCHAR(100) | 姓 |
| last_name | VARCHAR(100) | 名 |
| email | VARCHAR(255) | メールアドレス |
| message | TEXT | 問い合わせ内容 |
| created_at | DATETIME | 送信日時 |

---

## CSS設計

- `sanitize.css` — リセットCSS
- `body.css` — `grid-template-rows: auto 1fr auto` で header/main/footer の3段レイアウト
- `header.css` — ハンバーガー、サイドナビ（`translateX(-100%)` → `translateX(0)` でスライドイン）、投稿ボタン
- `gallery-list.css` — CSS変数 `--cols` をメディアクエリで切り替え（1列→5列）
- `gallery-detail.css` — 中央配置のカード型レイアウト、レスポンシブで幅を調整
- `modal.css` — `backdrop-filter: blur(6px)` で背景をぼかすオーバーレイ
- `footer.css` — 2カラムgrid → 1000px以下で1カラムに切り替え
- `login-main.css` / `form-main.css` — カード型のフォームUI
- `policy.css` — 記事型レイアウト、左ボーダー付き見出し

---

## 今後の課題

### 優先度：高
- [ ] **ログイン状態のUI反映** — `GET /api/auth.php?action=status` を common.js で呼び、ログイン中は「＋」ボタン・ログアウトボタンを表示、未ログイン時は非表示にする
- [ ] **ログアウトボタンの追加** — API（`auth.php?action=logout`）は実装済みだがUIがない
- [ ] **削除ボタンの追加** — 詳細ページに自分の作品であれば削除できるボタンを設置（API は実装済み）
- [ ] **login.html / form.html にヘッダー/フッターを追加** — 現状 common.js を読み込んでおらずナビゲーションが出ない

### 優先度：中
- [ ] **ヘッダー/フッターの共通化** — 全ページにHTML がコピペされている。JS動的挿入 or テンプレートエンジンで一元管理にする
- [ ] **mikansei-Page/ の整理** — 未完成ページ3つが残っている。使わないなら削除

### 優先度：低
- [ ] **画像アップロード対応** — 現在は外部URLのみ。ファイルアップロード + サーバー保存に対応する
- [ ] **ユーザー登録機能** — 現在はデモユーザーのみ。新規登録フローを実装する
- [ ] **ページネーション** — 作品数が増えた場合の一覧表示対策
