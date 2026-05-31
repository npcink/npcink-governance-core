# Magick AI Core Positioning

## English

Magick AI Core is the WordPress AI operation governance layer for ability
intake, proposal records, approval boundaries, commit preflight, scoped app
keys, and audit logs.

It discovers agent-callable abilities from WordPress and provider plugins,
records proposed operations, supports approve/reject decisions, performs commit
preflight, and stores audit evidence for governance review.

Magick AI Core is part of the Magick AI plugin family:

- `magick-ai-abilities` - ability definitions and ability callbacks.
- `magick-ai-core` - governance, approval, preflight, and audit.
- `magick-ai-adapter` - OpenClaw channel adaptation that calls Core and the
  Abilities API.
- `magick-ai-cloud-addon` - cloud service connection.

Core does not generate content, route models, run MCP or workflow runtimes,
store provider credentials, proxy ability execution, own reusable ability
definitions, or perform final WordPress mutations.

## Chinese

Magick AI Core 是 WordPress AI 操作的治理层，负责 ability intake、proposal
记录、审批边界、commit preflight、scoped app key 和 audit log。

它从 WordPress 和 provider 插件发现可被 agent 调用的能力，记录待治理的操作
提案，支持 approve/reject 决策，执行 commit preflight，并保存可审查的治理
证据。

Magick AI Core 是 Magick AI 系列插件的一部分：

- `magick-ai-abilities` - 能力定义和 ability callback。
- `magick-ai-core` - 治理、审批、preflight、audit。
- `magick-ai-adapter` - OpenClaw 通道适配，调用 Core 和 Abilities API。
- `magick-ai-cloud-addon` - 链接云端服务。

Core 不生成内容、不路由模型、不运行 MCP 或 workflow runtime、不保存 provider
credentials、不代理 ability execution、不拥有可复用能力定义，也不执行最终
WordPress 写入。
