# Portal Architecture (Version 0.1)

目的: 社内ポータルの長期的な改善を前提とした、保守性・可読性・拡張性を重視したアーキテクチャ設計（初期実装は最小限）

注意: 本設計は Portal Compass の方針を最優先し、将来的に他社展開や AI/API 連携がしやすい汎用プラットフォームを目指します。

---

## 1. 高レベルシステム構成

- シンプルな LAMP 系を想定（PHP + MySQL + HTML/CSS/JS）。
- フロント: サーバーサイドレンダリング中心（PHP テンプレート）＋必要に応じて小さなクライアント JS（純粋な Vanilla/モジュール化された ES6）
- バックエンド: PHP（軽量な自前ミニフレームワーク / もしくは小さな MVC 層）
- DB: MySQL（将来的に他 DB や検索エンジンへの差し替えを想定）
- 公開 API 層: 将来のために REST API v1 を用意（内部向けは最初は限定公開）
- 拡張ポイント: モジュール/プラグインインターフェイス、イベント（フック）層、Webhook

運用モデル:
- 環境: `development` / `staging` / `production`
- デプロイ: 簡易な CI（ビルド/検証→ステージ→本番）

セキュリティの基本:
- 環境変数で機密情報を管理
- HTTPS を前提
- 入力検証と出力エスケープを徹底
- ログは監査用と運用用に分離

---

## 2. ディレクトリ構成（推奨）

- /public
  - index.php            -- フロントコントローラ、公開ドキュメントルート
  - assets/              -- CSS, JS, images
- /src
  - Controller/          -- HTTP ハンドラ（ページ別コントローラ）
  - Model/               -- DB に対応するモデル（シンプルな Active Record 風 or QueryBuilder）
  - Service/             -- ビジネスロジック、ユースケース
  - Repository/          -- DB アクセス（SQL を分離）
  - Middleware/          -- 認証・権限・CSRF 等
  - Event/               -- イベントとリスナ
  - Api/                 -- REST API エンドポイント実装（将来）
- /templates             -- HTML テンプレート（プレゼン層）
- /config                -- 設定ファイル（例: config.php, routes.php）
- /migrations            -- DB マイグレーション SQL / スクリプト
- /docs                  -- 設計書（このファイル）
- /tests                 -- 単体テスト、統合テスト
- /var                   -- ランタイム生成物（uploads, cache, logs）※書き込み権限あり
- composer.json (任意)  -- 将来の依存管理に備える

命名方針:
- PSR 準拠を意識（クラス名は PascalCase、ファイルはクラス名に一致）
- ルートは短く明快に（例: `Controller/UserController.php`）

---

## 3. データベース設計（主要テーブル）

設計方針:
- 正規化を基本としつつ、アクセス頻度の高い表示は冗長カラムで高速化可能
- 変更はマイグレーションで管理

主要テーブル一覧（概要）:

1) `users`
 - `id` BIGINT PK AI
 - `email` VARCHAR(255) UNIQUE
 - `password_hash` VARCHAR(255)
 - `display_name` VARCHAR(100)
 - `department_id` BIGINT NULL
 - `is_active` TINYINT(1)
 - `created_at` DATETIME
 - `updated_at` DATETIME

2) `roles`
 - `id` INT PK AI
 - `name` VARCHAR(100) UNIQUE -- 例: admin, editor, viewer
 - `display_name` VARCHAR(100)

3) `permissions`
 - `id` INT PK AI
 - `name` VARCHAR(150) UNIQUE -- 例: pages.view, pages.edit
 - `description` TEXT

4) `role_permissions` (role と permission の多対多)
 - `role_id` INT
 - `permission_id` INT

5) `user_roles`
 - `user_id` BIGINT
 - `role_id` INT

6) `departments`
 - `id` INT PK AI
 - `name` VARCHAR(100)

7) `pages` (ポータルの各ページ／コンテンツメタ)
 - `id` BIGINT PK AI
 - `slug` VARCHAR(255) UNIQUE
 - `title` VARCHAR(255)
 - `content` TEXT (HTML) -- 基本はテンプレート＋部分コンポーネント
 - `author_id` BIGINT
 - `visibility` ENUM(public, internal, private)
 - `created_at`, `updated_at`

8) `components` (再利用 UI コンポーネントのメタ、オプショナル)
 - `id`, `name`, `type`, `config_json`

9) `attachments`
 - `id`, `owner_type`, `owner_id`, `filename`, `path`, `mime`, `size`, `created_at`

10) `audit_logs`
 - `id`, `user_id`, `action`, `target_type`, `target_id`, `meta_json`, `created_at`

インデックス方針:
- 検索条件に使うカラム（email, slug, created_at など）にインデックスを張る

拡張用テーブル（将来）:
- `api_keys`, `webhooks`, `integrations`, `kb_entries`（AI 用のナレッジベース）

---

## 4. 認証・権限設計

認証（Authentication）:
- 初期: セッションベース認証（PHP セッション）＋セキュアなクッキー設定
- パスワードハッシュ: `password_hash()`（PHP の BCRYPT/ARGON2 を使用）
- CSRF 防止: ミドルウェアでトークン検証
- 多要素認証（2FA）は将来のオプションとしてサービス層で対応可能
- API 用はトークン（長期/短期）方式を別途用意

認可（Authorization）:
- RBAC（ロールベース）を基本とする
- 権限は細かな `permission` 名で管理（`module.action` 型推奨）
- 管理画面でロールと権限を編集可能にする
- 一部のページは所属部署・属性ベースの制御も行える（例: department限定公開）

実装のポイント:
- ミドルウェアで認証チェック → サービス層で権限チェック
- 権限のキャッシュ（Redis 等）を将来検討（初期は DB キャッシュ／アプリ内キャッシュ）

---

## 5. ページ構成（初期セット）

- サインイン / サインアウト / プロフィール
- ダッシュボード（役割別ウィジェット）
- 社員ディレクトリ（検索・部署ツリー）
- お知らせ（News）
- ドキュメント（Docs）: Markdown / HTML 表示
- 申請・ワークフロー（簡易フォーム）
- ファイル管理 / 添付
- 管理コンソール（ユーザー・ロール管理、設定）

各ページは小さなコンポーネントに分割し、テンプレートで組み合わせる

---

## 6. コンポーネント設計（UI / 再利用要素）

プレゼン層の最小コンポーネント:
- `Header`（グローバルナビ、ユーザーメニュー）
- `Sidebar`（セクションナビ、権限に応じて項目表示）
- `Breadcrumbs`
- `Card`（コンテンツブロック）
- `Table`（ページネーション、ソート対応）
- `Form`（バリデーション・エラーメッセージの統一）
- `Modal`（標準モーダル）
- `FileUploader`（chunked は後）
- `Search`（共通検索コンポーネント）

実装方針:
- テンプレート内で部分テンプレート（include）を使い再利用
- JavaScript は ES Modules で機能ごとに分割
- CSS はユーティリティ＋コンポーネントスタイル（BEM 系か軽量な設計）

---

## 7. 将来の拡張性（AI / API 連携を見据えた設計）

設計原則:
- データと表示の分離（プレゼンはテンプレート、ビジネスロジックは Service）
- イベントドリブン：主要アクションにイベントを発行（例: document.published → listeners）
- モジュール化：新機能は `modules/` として追加できる設計

API とインテグレーション:
- 内部 REST API（認証付）を用意し、UI も API 経由で動かせるようにする
- 将来の外部連携（Slack, Teams, SSO, AI）向けに `integrations` 層を用意

AI 対応の準備:
- ナレッジベース（`kb_entries` テーブル）を用意し、構造化データで蓄積
- 操作ログ・コンテンツを匿名化ポリシーに基づいて収集し、学習用にエクスポート可能にする
- OpenAI 等へ投げる API クライアントを `Service/AIClient` として分離

データ保護:
- AI に送る前に PII を除去・マスキングするフィルタを実装

---

## 8. 運用・開発ワークフロー（簡易）

- 開発: ローカルで動く最小構成（PHP ビルトイン or ローカル Apache）
- マイグレーション管理: `/migrations` に SQL または PHP スクリプトを保存
- デプロイ: アーティファクトは `public` 以下を含めて配備、DB マイグレーションを実行
- 監視: ログのローテーションと簡易アラート（メール）

---

## 9. 命名規約・設計ルール（簡潔）

- クラス: PascalCase, 単一責任
- ファイル: クラス名と一致
- 変数/関数: camelCase
- テンプレート: スネークケース or kebab-case（プロジェクト内で統一）
- DB カラム: snake_case

---

## 10. Version 0.1 のスコープ（最小実装）

- ユーザー認証（ログイン/ログアウト/プロフィール）
- RBAC のコア（ユーザ・ロール・権限）
- ダッシュボード（静的ウィジェット）
- お知らせ（作成・編集・一覧・表示）
- 社員ディレクトリ（一覧・検索）

上記を満たす最小限のページと API を実装し、モジュール化とイベント基盤の基礎を整える。

---

## 11. 次のアクション（実装フェーズへの移行）

1. `docs` 設計レビュー（今回のファイル）
2. リポジトリにベースのディレクトリを作成（`public`, `src`, `templates`, `config`, `migrations`）
3. 最初のブートストラップ実装: `public/index.php`, ルーティング, シンプルなユーザー認証
4. DB マイグレーション（`users`, `roles`, `permissions`, `user_roles`, `pages`）を準備
5. 小さな e2e ショーケース（ダッシュボード + ログイン）を作る

---

付録: 参考設計メモ
- REST API は `/api/v1/*` に集約
- 管理画面は `/admin/*` に分離
- 将来的に OAuth/SSO（SAML, OIDC）を取り込む想定

---

設計質問や優先度変更があれば、次のコミットで調整します。
