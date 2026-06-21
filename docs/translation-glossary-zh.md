# Chinese Translation Glossary

Status: active.

This glossary keeps the Simplified Chinese translation consistent while the
plugin source strings, slug, REST namespace, database identifiers, ability ids,
and text domain remain English public contracts.

## Identity Rules

- Keep `Npcink Governance Core`, `Npcink AI`, and plugin slugs in English.
  Keep `npcink-governance-core` for slug and technical identifiers.
- Keep `npcink-governance-core`, REST paths, database table names, option names,
  transient prefixes, ability ids, app scopes, and audit event names
  untranslated.
- Translate user-facing descriptions, labels, validation messages, and admin UI
  copy.
- Do not translate `OpenClaw`, `Npcink Cloud Addon`, `Npcink AI Client
  Adapter`, `Npcink Abilities Toolkit`, or `npcink-abilities-toolkit` when
  they refer to product or package identities.
- Translate shared wp-admin navigation labels when they are short module
  entries rather than product names: `Core` -> `治理核心`, `Adapter` ->
  `渠道适配器`, `Abilities` -> `原子能力`, and `Cloud Addon` -> `云端扩展`.

## Standard Terms

| English | Simplified Chinese |
| --- | --- |
| Governance | 治理 |
| Core governance | Core 治理 |
| Governance layer | 治理层 |
| Proposal | 提案 |
| Proposal record | 提案记录 |
| Review Queue | 审核队列 |
| Needs review | 待审核 |
| Approval | 批准 |
| Rejection | 拒绝 |
| Approve | 批准 |
| Reject | 拒绝 |
| Commit preflight | 提交前检查 |
| Execution handoff | 执行交接 |
| Ability | 能力 |
| Ability id | 能力 ID |
| Abilities menu | 原子能力 |
| WordPress Abilities API | WordPress Abilities API |
| Audit | 审计 |
| Audit log | 审计日志 |
| Audit timeline | 审计时间线 |
| App key | 应用密钥 |
| Scoped app key | 限定范围的应用密钥 |
| Scope | 权限范围 |
| Rate limit | 速率限制 |
| Caller | 调用方 |
| Core menu | 治理核心 |
| Adapter menu | 渠道适配器 |
| Cloud Addon menu | 云端扩展 |
| Adapter product identity | Adapter |
| Cloud Addon product identity | Cloud Addon |
| Media Policy | 媒体策略 |
| Media optimization | 媒体优化 |
| Local control-plane truth | 本地控制面事实 |
| Dry-run | 试运行 |
| Write action | 写入操作 |
| Final writes | 最终写入 |
| Workflow runtime | 工作流运行时 |
| Provider credentials | 提供方凭据 |

## Style

- Prefer concise admin UI text over literal long-form translation.
- Use Arabic numerals and uppercase `ID` in Chinese strings.
- Keep placeholders such as `%s`, `%1$s`, and `%2$d` unchanged and in the same
  semantic order unless the string uses numbered placeholders.
- Keep security and failure messages explicit; do not soften fail-closed
  wording.
