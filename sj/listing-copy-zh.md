# WordPress.org 上架文案草稿 - 中文

## 插件名称

Magick AI Core

## 简短描述

面向 AI 辅助 WordPress 操作的治理、审批、commit preflight 和 audit log 层。

## 标签建议

ai, governance, approval, audit, abilities

## 插件介绍

Magick AI Core 是 WordPress AI 操作的治理层，负责 ability intake、proposal、
审批边界、commit preflight、scoped app key 和 audit log。

它从 WordPress 和 provider 插件发现可被 agent 调用的能力，然后围绕操作提案
增加 host-side policy 和治理流程。Core 会记录 proposal，支持 approve/reject
决策，执行 commit preflight，并保存 audit evidence，让站点管理员和可信 host
可以审查哪些操作被请求、审批、拒绝或准备提交。

Core 属于 Magick AI 系列插件，但它只聚焦治理。能力定义属于 Magick AI
Abilities 或其他 provider 插件。产品化 OpenClaw 连接属于 Magick AI Adapter。
云端服务连接属于 Magick AI Cloud Addon。

Magick AI Core 不生成内容、不路由模型、不运行 MCP 或 workflow runtime、不保存
provider credentials、不代理 ability execution，也不执行最终 WordPress 写入。

## 核心功能

- 发现可用于治理 intake 的 WordPress abilities。
- 存储 AI 辅助 WordPress 操作的 proposal。
- 支持 approve/reject 决策并记录 audit evidence。
- 在可信 host 或 adapter 执行最终写入前提供 commit preflight。
- 为可信治理客户端提供 scoped app-key access。
- 保留近期治理 audit 记录，方便审查和排障。
- 将治理与能力定义、模型路由、transport、云端执行、最终写入执行分层。

## 适合谁使用

- 需要可审查 AI 操作治理的 WordPress 管理员。
- 需要 proposal approval 和 commit preflight 的 host 插件或 adapter。
- 希望把 ability execution 和 governance decision 分开的开发者。
- 需要本地 WordPress control-plane 边界的 Magick AI 部署。

## 环境要求

- WordPress 7.0 或更高版本；完整 ability intake 需要 WordPress Abilities API 可用。
- PHP 8.0 或更高版本。

## 系列插件边界

在 Magick AI 系列插件中：

- Magick AI Abilities 负责能力定义和 ability callback。
- Magick AI Core 负责治理、审批、preflight、audit。
- Magick AI Adapter 负责 OpenClaw 通道适配。
- Magick AI Cloud Addon 负责链接云端服务。

这个分层让 Core 专注于治理，同时让执行、transport、云端服务和能力内容留在
各自独立层。
