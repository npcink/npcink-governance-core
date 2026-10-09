# zh_CN - Stable Readme Translation Draft

## Short Description

面向 AI 辅助 WordPress 操作的治理、审批、提交前检查和审计日志。

## Description

Npcink Governance Core 是面向 WordPress 操作的 Npcink AI 治理层。它让站点管理员和可信 host 插件在 AI 辅助操作真正提交到 WordPress 站点前，拥有一个可审查的审批层。

Core 会发现可用的 WordPress Abilities API 操作，记录 AI 提出的操作请求，支持批准和拒绝，返回 commit preflight 上下文，并保存审计证据。它适合 AI 工具、adapter 或产品插件可能请求 WordPress 变更，但站点仍需要清晰治理记录的场景。

### Core 能做什么

- 发现可进入治理流程的 WordPress abilities。
- 存储 AI 辅助 WordPress 操作的 proposal。
- 支持 approve 和 reject 决策，并保存审计证据。
- 在可信 host 或 adapter 执行最终写入前提供 commit-preflight 上下文。
- 为可信治理客户端管理 scoped app keys。
- 记录 proposal 创建、policy evaluation、approval、rejection、commit preflight 和 execution-result handoff 审计事件。
- 将治理与内容生成、模型路由、云服务计费和最终写入执行分开。

### 谁适合使用这个插件

Npcink Governance Core 适合需要为 AI 辅助 WordPress 操作建立本地审批和审计边界的 WordPress 管理员、host 插件、adapter 和开发者。当 AI 工具可以准备或请求变更，但站点所有者或可信治理客户端仍需要审查、批准和追踪这些变更时，它尤其有用。

这个插件不是一键 AI 写作工具、SEO 助手、图片生成器、聊天机器人或工作流运行时。产品工作流和 ability callbacks 应放在独立的 provider、adapter 或产品插件中。

### 要求和集成

Core 最适合与 WordPress 7.0 或更高版本以及 WordPress Abilities API provider 一起使用。第一方参考 provider 是 Npcink Abilities Toolkit，但基础治理生命周期也可以治理第三方 WordPress Abilities API provider，只要它们提供稳定的 ability ids、schemas、permission callbacks、risk metadata 和 dry-run previews。参考通道 adapter 是 Npcink AI Client Adapter，它会在 Core commit preflight 之后，通过 WordPress Abilities API 执行已批准的 abilities。

Core 在 `/wp-json/npcink-governance-core/v1/` 下暴露治理 REST endpoints。可信 adapter 和 host 插件可以使用这些 endpoints 创建 proposals、批准或拒绝 proposals、请求 commit preflight，并记录外部执行结果。

### 隐私和数据

Npcink Governance Core 不调用外部 AI 服务，不加载远程资源，也不会把站点数据发送给第三方。它会在 WordPress 数据库表中本地存储治理记录，包括 proposal 数据、audit events、app-key metadata 和 rate-limit state。App-key secrets 会哈希后存储，一次性 bearer tokens 只会在创建时显示。

### 边界

Core 不生成内容、不路由模型、不运行 MCP 或 workflow runtimes、不保存 provider credentials、不代理 ability execution，也不执行最终 WordPress 写入。最终写入属于治理步骤完成后的可信 host、adapter 或外部 runtime。

## Installation

1. 将插件上传到 `wp-content/plugins/npcink-governance-core`。
2. 在 WordPress 中启用 Npcink Governance Core。
3. 打开 Npcink AI > Core，查看治理状态、proposals、audit entries 和高级 Core app-key 控制。

## FAQ

### 这个插件适合谁？

它适合需要为 AI 辅助 WordPress 操作提供可审查治理的站点管理员、host 插件、adapter 和开发者。当 AI 工具可以准备变更，但站点仍需要审批、commit preflight 和审计证据后才能应用这些变更时，它尤其有用。

### 这是 AI 内容生成器吗？

不是。Core 不写文章、不生成媒体、不创建 SEO 文案、不回复评论、不选择 AI 模型，也不保存 provider credentials。它治理由独立工具、adapter 或 WordPress Abilities API provider 创建的 proposed operations。

### Core 会执行 AI 写入吗？

不会。Core 会记录治理 proposals 并返回 commit-preflight 上下文。最终写入属于 Core 外部的可信 host、adapter 或 runtime。

### 什么是 proposal？

Proposal 是一个已存储的 AI 辅助 WordPress 操作请求。它包含目标 ability、输入摘要、preview 或 dry-run evidence、状态、caller metadata 和审查所需的 audit trail。

### 什么是 commit preflight？

Commit preflight 是批准之后、可信外部组件执行最终写入之前的治理检查。它返回有限上下文、correlation data 和 input binding，让下游组件验证自己正在处理已批准的请求。

### 我需要另一个插件来提供 abilities 吗？

完整 ability intake 需要。Core 治理由 WordPress Abilities API providers 暴露的 abilities。Npcink Abilities Toolkit 是参考 provider，第三方 providers 也可以通过稳定 ability ids、schemas、permissions、risk metadata 和 dry-run previews 集成。

### 这个插件会把数据发送给外部 AI 服务吗？

不会。Core 不调用外部 AI 服务，也不会把站点数据发送给第三方。它会在 WordPress 数据库表中本地存储治理记录。

### Core 会存储哪些数据？

Core 会存储 proposal records、approval 和 rejection decisions、commit-preflight evidence、execution-result handoff records、audit events、app-key metadata 和 rate-limit state。App-key secrets 会哈希存储，一次性 bearer tokens 只在创建时显示。

### Scoped app keys 用来做什么？

Scoped app keys 允许可信治理客户端调用特定 Core REST endpoints，而不需要给它们广泛的管理员权限。它们面向受控 hosts、adapters 和内部治理客户端。

### OpenClaw 应该直接连接 Core 吗？

产品化 OpenClaw 设置应通过可信 adapter 连接。直接 Core app keys 只适合内部治理客户端和 fallback testing。

### 第三方 ability providers 可以使用 Core 吗？

可以。基础 proposal lifecycle 是 provider-neutral 的。第三方 providers 可以暴露带有 schemas、permission callbacks、risk metadata 和 dry-run previews 的 WordPress Abilities API definitions，然后把写入或破坏性操作提交给 Core 审查。

### 为什么 Core 要求 WordPress 7.0？

Core 的 ability intake 读取 WordPress Abilities API 接口，Core 的治理契约面向 WordPress 7.0 及更高版本构建和测试。在更旧的 WordPress 版本上，ability intake 未经过测试，Core 会 fail closed（拒绝处理），而不是治理无法对照真实 ability ids 验证的 proposals。

### Core 支持多站点（multisite）吗？

支持。网络启用（network activation）会为每个现有站点创建 Core 治理表，新建站点会在初始化时自动创建。治理记录按站点分别存储。

### 停用或删除插件后，治理记录会怎样？

停用会保留全部治理记录。删除插件同样保留记录：proposals、audit events、app-key metadata 和 read authorization records 都会保留，因为审批与审计历史可能受站点留存要求约束。本版本不提供自动卸载删除。

## Upgrade Notice

### 0.3.0

操作员体验与 REST 消费者契约版本。无需数据迁移；治理表自 0.2.0 起未变更。审查队列筛选在决策间保留，列表端点返回总数，REST 时间戳为 ISO8601 UTC，并内置完整的 zh_CN 翻译目录。

## Changelog

### 0.3.0

操作员体验与 REST 消费者契约版本。管理端审查队列在决策间保留筛选和分页上下文，每次决策后可打开下一条 pending proposal，支持按 ability 和等待时长筛选，一次性 client token 在正常管理界面内呈现并带复制按钮。Audit read-requests 增加 offset 和 search 分页，所有列表端点返回总数，REST 时间戳为 ISO8601 UTC，重复 commit preflight 会回显原始 handoff 标识以便恢复，过期的 app keys 返回专用的 rotate-key 错误，限流响应带 Retry-After 指引，运行时 contract 端点接受可选的 contract:read app scope。内置 zh_CN 目录已完整（810 条字符串），并会在本地分发的插件 zip 上加载。本版本仍未加入最终写入执行、workflow runtime 或 provider credential 归属。

### 0.2.0

0.1.1 之后完成的治理 intake、approval、preflight、audit 与发布打包强化候选版本。本版本不加入最终写入执行或 workflow runtime 归属。

### 0.1.1

发布工具补丁，用于常规 RC tag 路径。版本矩阵现在可以识别指向当前提交的 annotated release tags，无需重新为历史版本打 tag。

### 0.1.0

初始治理插件，包含 ability intake、proposals、approval/rejection、commit preflight、scoped app keys、rate limiting 和 audit records。
