# Portal Architecture

Version 0.2

社内ポータルシステムの設計資料。

本資料は、Portal Compass の思想である「改善が自然に続く会社をつくる」を実現するためのアーキテクチャ方針である。
Version 0.2 では、ページや CMS 機能を中心に考えるのではなく、業務を支える Business Modules を中心に設計する。

---

## 1. Design Foundation

### 1.1 Core Idea

このポータルは、社内情報を掲載するための Web サイトではない。

会社の知識、業務、会議、実績、資産、改善活動をつなぎ、社員が日々の判断と行動を良くしていくための業務改善プラットフォームである。

Version 0.2 の設計原則は以下とする。

- Page First ではなく Module First
- 情報保存ではなく業務支援
- 完成品ではなく改善サイクル
- 個人知ではなく組織知
- 部門最適ではなく会社全体の One Source of Truth
- 自社改善から他社展開へ育てられる汎用構造

### 1.2 Portal Compass Principles

Portal Compass の中核思想を、アーキテクチャ上は次のように扱う。

| Principle | Architecture Meaning |
| --- | --- |
| One Source of Truth | 情報・業務データの主管理場所を明確にする |
| Findability | モジュール横断検索と関連情報リンクを標準化する |
| Simplicity | 入力項目と業務フローを最小化する |
| Continuous Improvement | 改善ログ、振り返り、変更履歴を全モジュールに接続する |
| Mobile First | 現場から登録・確認できる画面構造にする |
| Security | 役割・所属・情報種別ごとに閲覧と操作を制御する |

### 1.3 Non Goals

Version 0.2 では、以下を目的にしない。

- 単なる CMS を作ること
- すべての業務システムを置き換えること
- チャット、勤怠、会計などの専門システムを内製すること
- 初期段階から多機能な完成品を作ること
- ページ数を増やすこと自体を成果とすること

---

## 2. Business Modules

### 2.1 Module Map

Version 0.2 では、ポータルを以下の Business Modules で構成する。

```text
Portal Platform
  |
  +-- Knowledge
  |     組織知、マニュアル、FAQ、資料、検索
  |
  +-- Workflow
  |     申請、報告、承認、依頼、進捗
  |
  +-- Meetings
  |     会議、議事録、決定事項、ToDo
  |
  +-- Performance
  |     目標、実績、KPI、共有レポート
  |
  +-- Assets
  |     設備、車両、アカウント、契約、ライセンス
  |
  +-- Improvement
  |     改善提案、改善ログ、振り返り、横展開
  |
  +-- Administration
        ユーザー、部署、権限、カテゴリ、設定、監査
```

### 2.2 Module Design Policy

各 Business Module は、必ず以下の観点で設計する。

- Purpose: 何の業務を支えるか
- Users: 誰が使うか
- Data: 何を蓄積するか
- Screens: どの操作画面が必要か
- Workflow: 業務の流れ
- Permissions: 誰が見て、誰が更新するか
- Improvement Link: 改善ログとどう接続するか
- Future Extension: 将来どう拡張できるか

---

## 3. System Overview

### 3.1 Architecture Style

初期構成は PHP / HTML / CSS / JavaScript / MySQL とする。

ただし、ディレクトリや責務はモジュール単位で整理し、将来的に一部モジュールを API 化、外部連携化、SaaS 化できる構造にする。

```text
Browser
  |
  v
PHP Application
  |
  +-- Business Modules
  |     +-- Knowledge
  |     +-- Workflow
  |     +-- Meetings
  |     +-- Performance
  |     +-- Assets
  |     +-- Improvement
  |     +-- Administration
  |
  +-- Shared Platform Services
        +-- Auth
        +-- Permission
        +-- Search
        +-- File Storage
        +-- Notification
        +-- Audit Log
        +-- Integration
```

### 3.2 Shared Platform Services

| Service | Purpose |
| --- | --- |
| Auth | ログイン、セッション、本人確認 |
| Permission | ロール、権限、公開範囲の判定 |
| Search | モジュール横断検索 |
| File Storage | 添付ファイル、PDF、画像、動画の管理 |
| Notification | 通知、確認依頼、承認依頼 |
| Audit Log | 重要操作の記録 |
| Activity Log | 業務上の更新履歴 |
| Integration | 外部システム、AI、API 連携 |

---

## 4. Directory Structure

Business Modules を中心にした構成とする。

```text
hit_portal/
  app/
    Modules/
      Knowledge/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Workflow/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Meetings/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Performance/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Assets/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Improvement/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
      Administration/
        Controllers/
        Models/
        Repositories/
        Services/
        Views/
    Platform/
      Auth/
      Permission/
      Search/
      Storage/
      Notification/
      Audit/
      Integration/
      Http/
      Validation/
      Database/
    Shared/
      Models/
      View/
      Helpers/
  config/
    app.php
    modules.php
    database.php
    permissions.php
    navigation.php
  database/
    migrations/
    seeds/
    schema.sql
  docs/
    portal-architecture.md
  public/
    index.php
    assets/
      css/
      js/
      images/
  resources/
    layouts/
    partials/
    components/
  routes/
    web.php
    api.php
  storage/
    logs/
    cache/
    private/
```

### 4.1 Directory Policy

- `app/Modules` に業務機能を置く
- `app/Platform` に認証、権限、検索、監査など全モジュール共通の基盤を置く
- `app/Shared` に複数モジュールで使う汎用部品を置く
- `resources` には全体共通のレイアウトや UI 部品を置く
- モジュール固有の画面は各 Module の `Views` に置く
- 企業固有の差分は `config` とデータで吸収する

---

## 5. Module Designs

## 5.1 Knowledge Module

### Purpose

社内の知識を整理し、誰でも必要な情報へたどり着ける状態を作る。

### Users

- 全社員
- 営業
- 工場
- 事務
- パートスタッフ
- FC 加盟店などの外部関係者

### Data

- ナレッジ記事
- マニュアル
- FAQ
- メーカー資料
- 商品情報
- PDF
- カタログ
- 動画
- 添付ファイル
- タグ
- カテゴリ
- 更新履歴

### Screens

- ナレッジ検索
- ナレッジ一覧
- ナレッジ詳細
- ナレッジ登録・編集
- FAQ 管理
- ファイル添付管理
- カテゴリ管理

### Workflow

```text
現場の知識・資料が発生
  -> 担当者がナレッジとして登録
  -> カテゴリ・タグ・公開範囲を設定
  -> 閲覧者が検索・参照
  -> 古い情報や不足情報を改善ログへ登録
  -> 担当者が修正
  -> 更新履歴として蓄積
```

### Permissions

- 全体公開
- 部署限定
- ロール限定
- 外部ユーザー閲覧可否
- 作成者編集
- 管理者編集

### Improvement Link

ナレッジ詳細から「改善提案」「情報が古い」「不足している」を登録できるようにする。

### Future Extension

- AI 検索
- PDF 本文検索
- 動画文字起こし検索
- 関連ナレッジ自動提案
- ナレッジの有効期限・定期レビュー

---

## 5.2 Workflow Module

### Purpose

申請、報告、依頼、承認などの日常業務を標準化し、処理状況を見える化する。

### Users

- 申請者
- 承認者
- 管理者
- 経理・総務・事務担当
- 設備・車両管理担当

### Data

- 申請種別
- 申請フォーム定義
- 申請データ
- 承認ステップ
- 承認履歴
- コメント
- 添付ファイル
- ステータス

### Screens

- 申請一覧
- 申請詳細
- 新規申請
- 承認待ち一覧
- 承認・差戻し画面
- 申請フォーム管理
- ワークフロー設定

### Workflow

```text
申請者が申請を作成
  -> 入力内容を保存
  -> 承認者へ通知
  -> 承認者が承認・差戻し・却下
  -> 必要に応じて担当部署が処理
  -> 完了
  -> 実運用上の問題を改善ログへ登録
```

### Initial Workflow Types

- 購入前申請
- カード決済報告
- 設備不良報告
- 車両予約
- 各種依頼

### Permissions

- 自分の申請を閲覧
- 部署内申請を閲覧
- 承認対象のみ操作
- 管理者は全体管理

### Improvement Link

差戻しが多い申請、処理が遅い申請、入力ミスが多い申請を改善対象として記録できるようにする。

### Future Extension

- フォームビルダー
- 条件分岐承認
- 通知連携
- 外部業務システム連携
- API による申請作成

---

## 5.3 Meetings Module

### Purpose

会議の目的、議題、決定事項、ToDo、議事録を蓄積し、会議を改善活動につなげる。

### Users

- 経営者
- 管理者
- 会議参加者
- 議事録作成者
- ToDo 担当者

### Data

- 会議
- 議題
- 議事録
- 決定事項
- ToDo
- 参加者
- 添付資料
- 関連ナレッジ
- 関連改善ログ

### Screens

- 会議一覧
- 会議詳細
- 議事録作成
- ToDo 一覧
- 決定事項一覧
- 会議資料管理

### Workflow

```text
会議を登録
  -> 議題と参加者を設定
  -> 会議資料を添付
  -> 議事録を作成
  -> 決定事項と ToDo を登録
  -> ToDo の進捗を確認
  -> 改善テーマを Improvement へ接続
```

### Permissions

- 参加者閲覧
- 部署内閲覧
- 経営会議などの限定閲覧
- 議事録作成者編集
- 管理者編集

### Improvement Link

会議で出た課題や決定事項を Improvement Module へ直接登録できるようにする。

### Future Extension

- カレンダー連携
- AI 議事録要約
- 音声文字起こし
- 会議アクションの自動通知

---

## 5.4 Performance Module

### Purpose

目標、実績、KPI、レポートを共有し、数字から改善のきっかけを見つける。

### Users

- 経営者
- 管理者
- 営業
- 店舗・拠点責任者
- 事務担当

### Data

- 年間目標
- 月間目標
- KPI
- 実績データ
- レポート
- 部署別・拠点別指標
- コメント
- 関連改善ログ

### Screens

- 実績ダッシュボード
- KPI 一覧
- 月次レポート
- 目標管理
- 実績入力
- 実績コメント

### Workflow

```text
目標を設定
  -> 実績を登録または連携
  -> 差異を確認
  -> コメント・要因を記録
  -> 改善テーマを登録
  -> 次回実績で効果を確認
```

### Permissions

- 経営者は全体閲覧
- 管理者は担当範囲閲覧
- 一般ユーザーは公開された指標のみ閲覧
- 実績入力者を限定

### Improvement Link

目標未達、急な変動、良い成果を Improvement Module へ接続し、改善・横展開の材料にする。

### Future Extension

- BI ツール連携
- 会計・販売管理システム連携
- KPI 自動集計
- AI による異常値検知

---

## 5.5 Assets Module

### Purpose

会社の設備、車両、アカウント、契約、ライセンスなどの管理情報を一元化する。

### Users

- 管理者
- 総務
- 情報システム担当
- 設備担当
- 車両利用者
- 契約管理担当

### Data

- 設備
- 車両
- アカウント
- サーバー情報
- ドメイン情報
- ライセンス
- 契約
- 更新期限
- 保守履歴
- 関連ファイル

### Screens

- 資産一覧
- 資産詳細
- 資産登録・編集
- 更新期限一覧
- 保守履歴
- 契約・ライセンス管理

### Workflow

```text
資産を登録
  -> 管理者・利用部署・期限を設定
  -> 更新や点検の履歴を記録
  -> 期限前に通知
  -> トラブルや改善点を記録
  -> 必要に応じて Workflow や Improvement へ接続
```

### Permissions

- 機密情報は管理者限定
- 一般資産は利用部署に公開
- パスワードや認証情報は別管理または厳格に制限
- 契約情報は閲覧者を限定

### Improvement Link

故障頻度、更新漏れ、契約重複、ライセンス不足などを改善ログとして蓄積する。

### Future Extension

- QR コード資産管理
- 外部パスワード管理ツール連携
- 契約更新通知
- 設備点検ワークフロー

---

## 5.6 Improvement Module

### Purpose

改善提案、改善実績、課題、横展開を蓄積し、改善を文化として定着させる。

### Users

- 全社員
- 現場担当
- 管理者
- 経営者
- 改善活動担当

### Data

- 改善提案
- 課題
- 原因
- 実施内容
- 結果
- 効果
- ステータス
- 担当者
- 関連モジュール
- 関連ナレッジ
- 関連会議
- 関連実績

### Screens

- 改善ログ一覧
- 改善詳細
- 改善提案登録
- 改善ステータス管理
- 改善カテゴリ
- 改善の横展開一覧

### Workflow

```text
課題やアイデアを登録
  -> 担当者・優先度を設定
  -> 実施内容を記録
  -> 結果と効果を記録
  -> 必要な知識を Knowledge へ反映
  -> 会議や実績と関連付ける
  -> 横展開できる改善として蓄積
```

### Status Model

- idea: アイデア
- accepted: 採用
- planned: 計画中
- doing: 実施中
- done: 完了
- shared: 横展開済み
- archived: 保管

### Permissions

- 全社員が提案できる
- 担当者が進捗更新できる
- 管理者がステータスを管理できる
- 機密性の高い改善は公開範囲を制限できる

### Cross Module Role

Improvement Module は単独機能ではなく、全モジュールを改善文化につなぐ中心機能とする。

```text
Knowledge -> 情報の不足・古さを改善へ
Workflow -> 申請の滞りを改善へ
Meetings -> 会議の決定事項を改善へ
Performance -> 数字の差異を改善へ
Assets -> 管理漏れや故障を改善へ
Administration -> 運用上の問題を改善へ
```

### Future Extension

- 改善効果の数値化
- 改善ランキング
- 改善テンプレート
- AI による類似改善提案
- 他社展開時のベストプラクティス化

---

## 5.7 Administration Module

### Purpose

ポータル全体の運用、権限、設定、監査を管理する。

### Users

- システム管理者
- ポータル管理者
- 部門管理者

### Data

- ユーザー
- 部署
- ロール
- 権限
- カテゴリ
- モジュール設定
- ナビゲーション設定
- 監査ログ
- システム設定

### Screens

- 管理ダッシュボード
- ユーザー管理
- 部署管理
- ロール管理
- 権限管理
- カテゴリ管理
- モジュール設定
- 監査ログ

### Workflow

```text
管理者がユーザー・部署を登録
  -> ロールと権限を付与
  -> モジュール利用可否を設定
  -> カテゴリやメニューを整備
  -> 監査ログで運用状態を確認
  -> 運用改善点を Improvement へ登録
```

### Permissions

- system_admin: システム全体管理
- portal_admin: ポータル運用管理
- module_admin: 担当モジュール管理
- editor: コンテンツ編集
- viewer: 一般閲覧
- external_partner: 外部関係者

### Improvement Link

権限設定の複雑さ、カテゴリの乱れ、使われないメニューなどを運用改善として記録する。

### Future Extension

- マルチテナント管理
- 組織別設定
- 監査レポート
- 管理操作の承認制

---

## 6. Database Design

### 6.1 Database Policy

データベースはモジュール単位で整理しつつ、共通基盤テーブルを持つ。

共通方針。

- 主要テーブルは `created_at`, `updated_at`, `deleted_at` を持つ
- 重要データは `created_by`, `updated_by` を持つ
- コンテンツは公開範囲を持つ
- モジュール横断の関連付けを可能にする
- 将来的な `organization_id` 追加を妨げない
- 監査ログを残す

### 6.2 Platform Tables

| Table | Purpose |
| --- | --- |
| users | ユーザー |
| departments | 部署・拠点 |
| roles | ロール |
| permissions | 権限 |
| user_roles | ユーザーとロール |
| role_permissions | ロールと権限 |
| categories | モジュール横断カテゴリ |
| tags | タグ |
| taggables | タグ紐付け |
| attachments | 添付ファイル |
| content_permissions | コンテンツ単位の公開範囲 |
| activity_logs | 業務上の更新履歴 |
| audit_logs | 監査ログ |
| module_links | モジュール間の関連付け |

### 6.3 Module Tables

#### Knowledge

| Table | Purpose |
| --- | --- |
| knowledge_articles | ナレッジ記事 |
| knowledge_revisions | 記事の版管理 |
| faqs | FAQ |
| document_metadata | PDF・動画などのメタ情報 |

#### Workflow

| Table | Purpose |
| --- | --- |
| workflow_types | 申請種別 |
| workflow_forms | フォーム定義 |
| workflow_requests | 申請本体 |
| workflow_steps | 承認ステップ定義 |
| workflow_approvals | 承認履歴 |
| workflow_comments | 申請コメント |

#### Meetings

| Table | Purpose |
| --- | --- |
| meetings | 会議 |
| meeting_agendas | 議題 |
| meeting_minutes | 議事録 |
| meeting_decisions | 決定事項 |
| meeting_tasks | ToDo |
| meeting_participants | 参加者 |

#### Performance

| Table | Purpose |
| --- | --- |
| performance_metrics | 指標定義 |
| performance_targets | 目標 |
| performance_results | 実績 |
| performance_reports | レポート |
| performance_comments | コメント |

#### Assets

| Table | Purpose |
| --- | --- |
| assets | 資産 |
| asset_types | 資産種別 |
| asset_maintenances | 保守履歴 |
| asset_contracts | 契約 |
| asset_licenses | ライセンス |
| asset_reservations | 車両・設備予約 |

#### Improvement

| Table | Purpose |
| --- | --- |
| improvement_logs | 改善ログ |
| improvement_actions | 改善アクション |
| improvement_effects | 改善効果 |
| improvement_reviews | 振り返り |

### 6.4 Module Links

モジュール間の接続を標準化するため、`module_links` を用意する。

| Column | Type | Description |
| --- | --- | --- |
| id | BIGINT PK | ID |
| source_module | VARCHAR(50) | 起点モジュール |
| source_type | VARCHAR(100) | 起点データ種別 |
| source_id | BIGINT | 起点 ID |
| target_module | VARCHAR(50) | 接続先モジュール |
| target_type | VARCHAR(100) | 接続先データ種別 |
| target_id | BIGINT | 接続先 ID |
| link_type | VARCHAR(50) | related / caused_by / improves / references |
| created_by | BIGINT | 作成者 |
| created_at | DATETIME | 作成日時 |

これにより、例えば以下を表現できる。

- 会議の決定事項から改善ログを作成
- 実績レポートから改善テーマを作成
- 改善結果をナレッジ記事へ反映
- 設備トラブルを申請・改善ログへ接続

---

## 7. Authentication And Authorization

### 7.1 Authentication

Version 0.2 の初期認証はメールアドレスとパスワードを基本とする。

将来追加を想定する。

- SSO
- Google Workspace / Microsoft Entra ID 連携
- 二要素認証
- パスワードレスログイン

### 7.2 Authorization Model

権限は以下の 3 層で判定する。

```text
System Role
  -> Module Permission
    -> Record Visibility
```

| Layer | Meaning |
| --- | --- |
| System Role | system_admin, portal_admin など全体権限 |
| Module Permission | knowledge.create, workflow.approve など機能権限 |
| Record Visibility | 全社、部署、ロール、個人、外部公開などの公開範囲 |

### 7.3 Permission Examples

| Permission | Description |
| --- | --- |
| knowledge.view | ナレッジ閲覧 |
| knowledge.create | ナレッジ作成 |
| workflow.request | 申請作成 |
| workflow.approve | 申請承認 |
| meetings.manage | 会議管理 |
| performance.view | 実績閲覧 |
| assets.manage | 資産管理 |
| improvement.create | 改善提案 |
| improvement.manage | 改善管理 |
| administration.manage | 管理機能 |

---

## 8. Navigation And Screens

### 8.1 Navigation Policy

ナビゲーションはページ一覧ではなく、業務モジュールを入口にする。

```text
Dashboard
Knowledge
Workflow
Meetings
Performance
Assets
Improvement
Administration
```

### 8.2 Dashboard

Dashboard は単なる新着一覧ではなく、今日の業務入口とする。

表示候補。

- 自分への通知
- 承認待ち
- 未完了 ToDo
- 最近更新されたナレッジ
- 期限が近い資産・契約
- 注目すべき実績
- 進行中の改善
- よく使う業務

### 8.3 Screen Policy

各モジュールには共通して以下の画面型を用意する。

- Overview: 業務状況の概要
- List: 一覧
- Detail: 詳細
- Create/Edit: 登録・編集
- Activity: 履歴
- Settings: モジュール設定

画面は目的ではなく、業務フローを進めるための手段とする。

---

## 9. Component Design

### 9.1 Platform Components

| Component | Purpose |
| --- | --- |
| AppLayout | ログイン後共通レイアウト |
| ModuleLayout | モジュール共通レイアウト |
| AdminLayout | 管理画面レイアウト |
| Header | 検索、通知、ユーザー情報 |
| ModuleNav | モジュール内ナビゲーション |
| Breadcrumb | 現在位置 |
| GlobalSearch | モジュール横断検索 |

### 9.2 Business Components

| Component | Purpose |
| --- | --- |
| StatusBadge | 業務ステータス表示 |
| OwnerField | 担当者表示 |
| VisibilityBadge | 公開範囲表示 |
| ActivityTimeline | 更新履歴 |
| AttachmentList | 添付ファイル |
| RelatedLinks | 関連モジュールリンク |
| ApprovalTrail | 承認履歴 |
| ImprovementPrompt | 改善提案導線 |

### 9.3 UI Components

| Component | Purpose |
| --- | --- |
| Button | 主要操作 |
| IconButton | 補助操作 |
| FormField | 入力項目 |
| SelectField | 選択項目 |
| SearchBox | 検索 |
| Table | 管理・一覧 |
| Card | 業務アイテム |
| Alert | 通知・エラー |
| Pagination | ページ送り |

---

## 10. Improvement Architecture

### 10.1 Improvement As A Platform Layer

改善は単独モジュールではなく、全モジュールに横断する考え方として扱う。

各モジュールには、以下の改善導線を標準で組み込む。

- この情報を改善する
- この業務を改善する
- この会議から改善を作る
- この実績差異を改善にする
- この資産トラブルを改善にする

### 10.2 Improvement Cycle

```text
Notice
  -> Record
  -> Discuss
  -> Act
  -> Review
  -> Share
  -> Standardize
```

| Step | Meaning |
| --- | --- |
| Notice | 課題や良い工夫に気づく |
| Record | 改善ログに残す |
| Discuss | 会議やコメントで検討する |
| Act | 実行する |
| Review | 結果を振り返る |
| Share | ナレッジとして共有する |
| Standardize | 業務標準へ反映する |

### 10.3 Improvement Metrics

将来的に改善文化を測る指標。

- 改善提案件数
- 完了件数
- 横展開件数
- ナレッジ反映件数
- 申請処理時間の短縮
- 会議 ToDo 完了率
- 資産トラブル再発率
- KPI 改善効果

---

## 11. Search And AI Readiness

### 11.1 Search Policy

検索は Knowledge だけの機能ではなく、全モジュール横断の基盤機能とする。

検索対象。

- ナレッジ
- 申請
- 会議議事録
- 決定事項
- ToDo
- 実績レポート
- 資産
- 改善ログ
- 添付ファイル名

### 11.2 AI Ready Data

AI 活用に備え、各モジュールで以下を持てる設計にする。

- title
- summary
- body
- status
- owner
- category
- tags
- visibility
- created_by
- updated_by
- reviewed_at
- related records

### 11.3 Future AI Features

- 社内 AI 検索
- PDF 検索
- 動画検索
- 議事録要約
- 類似改善提案
- 申請内容の入力補助
- KPI 異常値の説明候補
- ナレッジ更新候補の提示

---

## 12. Integration Design

### 12.1 Integration Policy

外部システム連携は各モジュールへ直接書かず、Platform の `Integration` に集約する。

### 12.2 Future Integrations

| Integration | Related Module |
| --- | --- |
| Calendar | Meetings |
| Mail | Notification / Workflow |
| Attendance | Performance / Administration |
| Kitchen system | Knowledge / Assets / Workflow |
| File storage | Knowledge / Assets |
| AI API | Search / Knowledge / Meetings / Improvement |
| BI Tool | Performance |

---

## 13. Security Design

### 13.1 Security Policy

業務改善プラットフォームは、会社の知識と業務データを扱う。
そのため、利便性と情報保護を両立する。

### 13.2 Required Controls

- 認証必須ページの保護
- CSRF 対策
- XSS 対策
- SQL Injection 対策
- ファイルアップロード制限
- 添付ファイルのアクセス制御
- レコード単位の公開範囲
- 管理操作の監査ログ
- 退職者アカウントの停止

---

## 14. Future Extensibility

### 14.1 Multi Tenant

他社展開時には `organizations` を追加し、主要テーブルに `organization_id` を追加する。

初期段階では単一組織で開始するが、Repository 層と Permission 層にデータ取得条件を集約し、移行しやすくする。

### 14.2 Module Enablement

他社展開時には、企業ごとに利用モジュールを有効化・無効化できるようにする。

例。

- Knowledge のみ利用
- Knowledge + Workflow を利用
- 全モジュール利用
- Assets は非表示

### 14.3 Plugin Ready

将来的には Business Modules をプラグイン化できる設計を目指す。

初期実装では完全なプラグイン機構は作らず、ディレクトリ構造と設定ファイルでモジュール境界を明確にする。

---

## 15. Version 0.2 Implementation Order

実装フェーズでは、以下の順序を推奨する。

1. Platform 基盤
2. 認証・権限
3. Dashboard
4. Improvement Module
5. Knowledge Module
6. Administration Module
7. Workflow Module
8. Meetings Module
9. Assets Module
10. Performance Module
11. Search
12. Audit Log
13. Integration 基盤

理由。

- 改善文化を中心にするため、Improvement を早期に作る
- Knowledge は改善結果を標準化する受け皿になる
- Administration は運用改善を支える
- Workflow / Meetings / Assets / Performance は実運用で優先度を見ながら拡張する

---

## 16. Open Questions

実装前に確認したい論点。

- 最初に運用したい Business Module はどれか
- 改善ログを全社員が投稿できるか
- 外部ユーザーに公開するモジュールはあるか
- 初期 Workflow は何から始めるか
- 会議体の種類と公開範囲
- 実績データは手入力か外部連携か
- 資産管理で扱う機密情報の範囲
- 他社展開時に必須となる汎用機能

---

## 17. Summary

Portal Architecture v0.2 では、ポータルをページや CMS の集合ではなく、業務改善を支える Business Modules の集合として再定義した。

中心にあるのは Improvement Module であり、Knowledge、Workflow、Meetings、Performance、Assets、Administration のすべてが改善活動と接続される。

この構造により、日々の業務から課題を見つけ、改善として記録し、会議で検討し、実行し、ナレッジとして標準化し、実績で効果を確認する流れを作る。

Version 0.2 の目的は、機能を増やすことではない。
改善が文化として続くための、長期運用可能な業務改善プラットフォームの骨格を作ることである。
