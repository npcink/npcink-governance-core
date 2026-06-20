# ja - Stable Readme Translation Draft

## Short Description

AI 支援の WordPress 操作に対するガバナンス、承認、コミット前確認、監査ログ。

## Description

Npcink Governance Core は、WordPress 操作のための Npcink AI ガバナンスレイヤーです。AI 支援の操作が WordPress サイトへコミットされる前に、サイト管理者と信頼されたホストプラグインが確認できる承認レイヤーを提供します。

Core は利用可能な WordPress Abilities API 操作を検出し、AI が提案した操作を記録し、承認と却下をサポートし、commit preflight のコンテキストを返し、監査証跡を保存します。AI ツール、adapter、または製品プラグインが WordPress の変更を要求する一方で、サイト側に明確なガバナンス記録が必要な環境に向いています。

### Core が行うこと

- ガバナンス対象にできる WordPress abilities を検出します。
- AI 支援の WordPress 操作 proposal を保存します。
- 監査証跡付きで approve と reject の判断をサポートします。
- 信頼された host または adapter が最終書き込みを行う前に commit-preflight コンテキストを提供します。
- 信頼されたガバナンスクライアント向けに scoped app keys を管理します。
- proposal 作成、policy evaluation、approval、rejection、commit preflight、execution-result handoff の監査イベントを記録します。
- ガバナンスをコンテンツ生成、モデルルーティング、クラウドサービス課金、最終書き込み実行から分離します。

### このプラグインの対象者

Npcink Governance Core は、AI 支援の WordPress 操作に対してローカルの承認および監査境界を必要とする WordPress 管理者、host プラグイン、adapter、開発者向けです。AI ツールが変更を準備または要求できるが、サイト所有者または信頼されたガバナンスクライアントがその変更を確認、承認、追跡する必要がある場合に役立ちます。

このプラグインは、ワンクリック AI ライター、SEO アシスタント、画像生成ツール、チャットボット、ワークフロー runtime ではありません。製品ワークフローと ability callbacks は、別の provider、adapter、または製品プラグインに属します。

### 要件と連携

Core は WordPress 7.0 以降、および WordPress Abilities API providers と組み合わせると最も効果的です。ファーストパーティの参照 provider は Npcink Abilities Toolkit ですが、安定した ability ids、schemas、permission callbacks、risk metadata、dry-run previews を公開するサードパーティの WordPress Abilities API providers も基本的なガバナンスライフサイクルで扱えます。

Core は `/wp-json/npcink-governance-core/v1/` 配下にガバナンス REST endpoints を公開します。信頼された adapters と host プラグインは、これらの endpoints を使って proposals の作成、承認または却下、commit preflight の要求、外部実行結果の記録を行えます。

### プライバシーとデータ

Npcink Governance Core は外部 AI サービスを呼び出さず、リモートアセットを読み込まず、サイトデータを第三者へ送信しません。proposal data、audit events、app-key metadata、rate-limit state などのガバナンス記録を WordPress データベーステーブルにローカル保存します。App-key secrets はハッシュ化して保存され、一度限りの bearer tokens は作成時にのみ表示されます。

### 境界

Core はコンテンツ生成、モデルルーティング、MCP または workflow runtimes の実行、provider credentials の保存、ability execution のプロキシ、最終的な WordPress 書き込みを行いません。最終書き込みは、ガバナンス手順の完了後に Core の外部にある信頼された host、adapter、または runtime が担当します。

## Installation

1. プラグインを `wp-content/plugins/npcink-governance-core` にアップロードします。
2. WordPress で Npcink Governance Core を有効化します。
3. Npcink AI > Core を開き、ガバナンス状態、proposals、audit entries、詳細な Core app-key コントロールを確認します。

## FAQ

### このプラグインは誰向けですか ?

AI 支援の WordPress 操作に対して確認可能なガバナンスを必要とするサイト管理者、host プラグイン、adapter、開発者向けです。AI ツールが変更を準備できても、適用前に承認、commit preflight、監査証跡が必要な場合に特に有用です。

### これは AI コンテンツ生成ツールですか ?

いいえ。Core は記事を書かず、メディアを生成せず、SEO コピーを作成せず、コメントに返信せず、AI モデルを選択せず、provider credentials を保存しません。別のツール、adapter、または WordPress Abilities API provider が作成した proposed operations をガバナンスします。

### Core は AI 書き込みを実行しますか ?

いいえ。Core はガバナンス proposals を記録し、commit-preflight コンテキストを返します。最終書き込みは Core の外部にある信頼された host、adapter、または runtime が担当します。

### proposal とは何ですか ?

proposal は、AI 支援の WordPress 操作に対する保存済みリクエストです。対象 ability、入力サマリー、preview または dry-run evidence、状態、caller metadata、確認に必要な audit trail を含みます。

### commit preflight とは何ですか ?

commit preflight は、承認後、信頼された外部コンポーネントが最終書き込みを行う前に実行されるガバナンスチェックです。下流コンポーネントが承認済みリクエストに基づいて動作していることを確認できるよう、限定されたコンテキスト、correlation data、input binding を返します。

### abilities 用に別のプラグインが必要ですか ?

完全な ability intake には必要です。Core は WordPress Abilities API providers が公開する abilities をガバナンスします。Npcink Abilities Toolkit は参照 provider であり、サードパーティ providers も安定した ability ids、schemas、permissions、risk metadata、dry-run previews を公開することで連携できます。

### このプラグインは外部 AI サービスへデータを送信しますか ?

いいえ。Core は外部 AI サービスを呼び出さず、サイトデータを第三者へ送信しません。ガバナンス記録は WordPress データベーステーブルにローカル保存されます。

### Core はどのようなデータを保存しますか ?

Core は proposal records、approval と rejection decisions、commit-preflight evidence、execution-result handoff records、audit events、app-key metadata、rate-limit state を保存します。App-key secrets はハッシュ化して保存され、一度限りの bearer tokens は作成時にのみ表示されます。

### scoped app keys は何に使いますか ?

scoped app keys は、信頼されたガバナンスクライアントが広範な管理者権限を持たずに特定の Core REST endpoints を呼び出すためのものです。制御された hosts、adapters、内部ガバナンスクライアント向けです。

### OpenClaw は Core に直接接続すべきですか ?

製品化された OpenClaw セットアップは、信頼された adapter 経由で接続すべきです。Core app keys を直接使うのは、内部ガバナンスクライアントと fallback testing のみに限定してください。

### サードパーティ ability providers は Core を使えますか ?

はい。基本 proposal lifecycle は provider-neutral です。サードパーティ providers は schemas、permission callbacks、risk metadata、dry-run previews を持つ WordPress Abilities API definitions を公開し、書き込みまたは破壊的操作を Core のレビューへ送信できます。

## Changelog

### 0.1.0

ability intake、proposals、approval/rejection、commit preflight、scoped app keys、rate limiting、audit records を備えた初期ガバナンスプラグイン。
