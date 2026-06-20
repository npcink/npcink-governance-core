# zh_TW - Stable Readme Translation Draft

## Short Description

為 AI 輔助的 WordPress 操作提供治理、核准、提交前檢查與稽核記錄。

## Description

Npcink Governance Core 是面向 WordPress 操作的 Npcink AI 治理層。它讓網站管理員與可信任的 host 外掛，在 AI 輔助操作真正提交到 WordPress 網站前，擁有可審查的核准層。

Core 會探索可用的 WordPress Abilities API 操作，記錄 AI 提出的操作請求，支援核准與拒絕，回傳 commit preflight 上下文，並保存稽核證據。它適用於 AI 工具、adapter 或產品外掛可能要求 WordPress 變更，但網站仍需要清楚治理紀錄的情境。

### Core 能做什麼

- 探索可進入治理流程的 WordPress abilities。
- 儲存 AI 輔助 WordPress 操作的 proposal。
- 支援 approve 與 reject 決策，並保存稽核證據。
- 在可信任 host 或 adapter 執行最終寫入前提供 commit-preflight 上下文。
- 為可信任治理客戶端管理 scoped app keys。
- 記錄 proposal 建立、policy evaluation、approval、rejection、commit preflight 與 execution-result handoff 稽核事件。
- 將治理與內容生成、模型路由、雲端服務計費與最終寫入執行分開。

### 誰適合使用這個外掛

Npcink Governance Core 適合需要為 AI 輔助 WordPress 操作建立本地核准與稽核邊界的 WordPress 管理員、host 外掛、adapter 和開發者。當 AI 工具可以準備或要求變更，但網站擁有者或可信任治理客戶端仍需要審查、核准並追蹤這些變更時，它特別有用。

這個外掛不是一鍵 AI 寫作工具、SEO 助手、圖片產生器、聊天機器人或工作流程執行環境。產品工作流程與 ability callbacks 應位於獨立的 provider、adapter 或產品外掛中。

### 需求與整合

Core 最適合與 WordPress 7.0 或更新版本以及 WordPress Abilities API provider 搭配使用。第一方參考 provider 是 Npcink Abilities Toolkit，但基礎治理生命週期也可以治理第三方 WordPress Abilities API provider，只要它們提供穩定的 ability ids、schemas、permission callbacks、risk metadata 與 dry-run previews。

Core 在 `/wp-json/npcink-governance-core/v1/` 下提供治理 REST endpoints。可信任 adapter 與 host 外掛可以使用這些 endpoints 建立 proposals、核准或拒絕 proposals、要求 commit preflight，並記錄外部執行結果。

### 隱私與資料

Npcink Governance Core 不會呼叫外部 AI 服務，不會載入遠端資源，也不會將網站資料傳送給第三方。它會在 WordPress 資料庫表中本地儲存治理紀錄，包括 proposal 資料、audit events、app-key metadata 與 rate-limit state。App-key secrets 會以雜湊方式儲存，一次性 bearer tokens 只會在建立時顯示。

### 邊界

Core 不產生內容、不路由模型、不執行 MCP 或 workflow runtimes、不儲存 provider credentials、不代理 ability execution，也不執行最終 WordPress 寫入。最終寫入屬於治理步驟完成後的可信任 host、adapter 或外部 runtime。

## Installation

1. 將外掛上傳至 `wp-content/plugins/npcink-governance-core`。
2. 在 WordPress 中啟用 Npcink Governance Core。
3. 開啟 Npcink AI > Core，檢視治理狀態、proposals、audit entries 與進階 Core app-key 控制。

## FAQ

### 這個外掛適合誰？

它適合需要為 AI 輔助 WordPress 操作提供可審查治理的網站管理員、host 外掛、adapter 與開發者。當 AI 工具可以準備變更，但網站仍需要核准、commit preflight 與稽核證據後才能套用變更時，它特別有用。

### 這是 AI 內容產生器嗎？

不是。Core 不撰寫文章、不產生媒體、不建立 SEO 文案、不回覆留言、不選擇 AI 模型，也不儲存 provider credentials。它治理由獨立工具、adapter 或 WordPress Abilities API provider 建立的 proposed operations。

### Core 會執行 AI 寫入嗎？

不會。Core 會記錄治理 proposals 並回傳 commit-preflight 上下文。最終寫入屬於 Core 外部的可信任 host、adapter 或 runtime。

### 什麼是 proposal？

Proposal 是一個已儲存的 AI 輔助 WordPress 操作請求。它包含目標 ability、輸入摘要、preview 或 dry-run evidence、狀態、caller metadata 與審查所需的 audit trail。

### 什麼是 commit preflight？

Commit preflight 是核准之後、可信任外部元件執行最終寫入之前的治理檢查。它回傳有限上下文、correlation data 與 input binding，讓下游元件驗證自己正在處理已核准的請求。

### 我需要另一個外掛來提供 abilities 嗎？

完整 ability intake 需要。Core 治理由 WordPress Abilities API providers 暴露的 abilities。Npcink Abilities Toolkit 是參考 provider，第三方 providers 也可以透過穩定 ability ids、schemas、permissions、risk metadata 與 dry-run previews 整合。

### 這個外掛會把資料傳送給外部 AI 服務嗎？

不會。Core 不呼叫外部 AI 服務，也不會將網站資料傳送給第三方。它會在 WordPress 資料庫表中本地儲存治理紀錄。

### Core 會儲存哪些資料？

Core 會儲存 proposal records、approval 與 rejection decisions、commit-preflight evidence、execution-result handoff records、audit events、app-key metadata 與 rate-limit state。App-key secrets 會以雜湊儲存，一次性 bearer tokens 只在建立時顯示。

### Scoped app keys 用來做什麼？

Scoped app keys 允許可信任治理客戶端呼叫特定 Core REST endpoints，而不需要給它們廣泛的管理員權限。它們面向受控 hosts、adapters 與內部治理客戶端。

### OpenClaw 應該直接連接 Core 嗎？

產品化 OpenClaw 設定應透過可信任 adapter 連接。直接 Core app keys 只適合內部治理客戶端與 fallback testing。

### 第三方 ability providers 可以使用 Core 嗎？

可以。基礎 proposal lifecycle 是 provider-neutral 的。第三方 providers 可以暴露帶有 schemas、permission callbacks、risk metadata 與 dry-run previews 的 WordPress Abilities API definitions，然後把寫入或破壞性操作提交給 Core 審查。

## Changelog

### 0.1.0

初始治理外掛，包含 ability intake、proposals、approval/rejection、commit preflight、scoped app keys、rate limiting 與 audit records。
