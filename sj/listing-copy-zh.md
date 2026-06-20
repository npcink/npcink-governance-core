# 中文发布文案草稿

## 插件名称

Npcink Governance Core

## 简短描述

面向 AI 辅助 WordPress 操作的治理、审批、提交前检查和审计日志。

## 标签建议

ai, governance, approval, audit, abilities

## 插件介绍

Npcink Governance Core 是面向 WordPress 操作的 Npcink AI 治理层。它让站点管理员
和可信 host 插件在 AI 辅助操作真正提交到 WordPress 站点前，拥有一个可审查的
审批层。

Core 会发现可用的 WordPress Abilities API 操作，记录 AI 提出的操作请求，
支持批准和拒绝，返回 commit preflight 上下文，并保存审计证据。它适合 AI 工具、
adapter 或产品插件可能请求 WordPress 变更，但站点仍需要清晰治理记录的场景。

Core 属于 Npcink 系列插件，但它只聚焦治理。能力定义属于 Npcink Abilities Toolkit 或其他 provider 插件。产品化 OpenClaw 连接属于 trusted adapter。
云端服务连接属于 cloud connector。

Npcink Governance Core 不生成内容、不路由模型、不运行 MCP 或 workflow runtime、
不保存 provider credentials、不代理 ability execution，也不执行最终 WordPress 写入。

## 核心功能

- 发现可用于治理 intake 的 WordPress abilities。
- 存储 AI 辅助 WordPress 操作的 proposal。
- 支持 approve/reject 决策并记录 audit evidence。
- 在可信 host 或 adapter 执行最终写入前提供 commit-preflight 上下文。
- 为可信治理客户端提供 scoped app-key access。
- 记录 proposal 创建、policy evaluation、approval、rejection、commit preflight
  和 execution-result handoff 审计事件。
- 将治理与能力定义、模型路由、transport、云端执行、最终写入执行分层。

## 适合谁使用

- 需要可审查 AI 操作治理的 WordPress 管理员。
- 需要 proposal approval 和 commit preflight 的 host 插件或 adapter。
- 希望把 ability execution 和 governance decision 分开的开发者。
- 需要本地 WordPress control-plane 边界的 Npcink 部署。

## 环境要求

- WordPress 7.0 或更高版本；完整 ability intake 需要 WordPress Abilities API 可用。
- PHP 8.0 或更高版本。

## 系列插件边界

在 Npcink 系列插件中：

- Npcink Abilities Toolkit 负责能力定义和 ability callback。
- `npcink-governance-core` 负责治理、审批、preflight、audit。
- trusted adapter 负责 OpenClaw 通道适配。
- cloud connector 负责链接云端服务。

这个分层让 Core 专注于治理，同时让执行、transport、云端服务和能力内容留在
各自独立层。

## WordPress.org 翻译说明

WordPress.org 插件公开页的描述、安装说明、FAQ 和更新日志，需要在
translate.wordpress.org 的 `Stable Readme` 中提交和审核。当前简体中文源稿见
`wporg-readme-translations/stable-readme-zh_CN.md`。
