# Strategy And Product Split

Status: active planning guide.

This document records the planning conclusion from the WordPress 7.0 research
review and the current Npcink Governance Core product boundary.

## Planning Conclusion

Do not grow Npcink Governance Core into a large AI product plugin.

Npcink Governance Core should stay narrow and hard: it is the trust and governance
kernel for AI-assisted WordPress operations. Commercial value should be built in
product plugins and ability providers that consume Core governance rather than
inside Core itself.

## Source Inputs

The planning input included:

- the current Core positioning, architecture, governance, REST, ability intake,
  and approval-commit contracts;
- ADR-001 and ADR-002, which define Core as a governance layer and prohibit
  workflow runtime ownership;
- the Core governance handoff guide in
  `/Users/muze/gitee/npcink-abilities-toolkit/docs/core-governance-handoff-guide.md`,
  which maps ready first-party abilities to Core proposal use without creating
  runtime aliases;
- WordPress 7.0 research under `/Users/muze/gitee/pdf/原始资料/7.0/`, especially
  the reports on AI Client, Connectors API, Abilities API, MCP, agentic
  WordPress operations, and China-market WordPress site needs.

The research points in the same direction as the current Core boundary:
WordPress 7.0 makes AI model calls, provider configuration, ability discovery,
and agent access more platform-native. That lowers the value of simple API
proxy plugins and raises the value of safe operation governance, ability
quality, vertical workflows, compliance, and auditability.

## Product Layers

| Layer | Project | Responsibility |
| --- | --- | --- |
| Governance kernel | `npcink-governance-core` | Ability intake, risk policy, proposal records, human or host approval, approval-commit authorization, audit logs, minimal governance REST/admin UI. |
| Ability layer | `npcink-abilities-toolkit` and provider plugins | Reusable WordPress Abilities API definitions, schemas, permission callbacks, dry-run previews, and execution callbacks. |
| Product layer | Content Assistant, Toolbox, commerce, SEO, media, agency, or vertical plugins | Domain UX, workflows, configuration wizards, market-specific features, and user-facing product value. |
| Provider layer | Connector/provider plugins | AI provider registration, model support, credential connection, and provider-specific capability exposure through WordPress platform APIs. |

Core can discover, classify, gate, approve, and audit operations from the other
layers. Core must not absorb those layers.

Product packaging may still consolidate those layers into one Npcink AI plugin
or one suite entry when that reduces installation and onboarding friction. The
package boundary is not the trust boundary. If modules are co-located, the
Governance module still owns proposal, approval, preflight, and audit truth;
Adapter still owns external channel behavior; Toolbox and other product modules
still own WordPress admin product UX. See
[ADR-004: Suite Consolidation And Local Admin Consent](decisions/ADR-004-suite-consolidation-and-local-admin-consent.md).

## Core Position

Core sells trust, not content generation.

Use this product sentence when orienting future work:

> Npcink Governance Core lets agents, tools, and product plugins request WordPress
> changes safely by turning risky operations into reviewable proposals,
> approval-commit authorization, and auditable lifecycle records.

This means Core owns:

- normalized ability intake;
- risk and approval requirements;
- proposal creation, detail, approval, rejection, and future commit preflight;
- approval context generation and validation;
- audit events for requested, listed, approved, rejected, committed, and failed
  lifecycle actions;
- minimal admin surfaces needed to review governance state.

Core does not own:

- article, SEO, media, comment, WooCommerce, toolbox, or China-market product
  workflows;
- provider keys, model routing, prompt and preset management, or cloud billing;
- MCP runtime, Agent Gateway catalogs, workflow definitions, queues, retries,
  leases, or batch consoles;
- reusable ability definitions that belong in `npcink-abilities-toolkit` or provider
  plugins.

## Recommended Roadmap

### 0-2 Weeks: Finish The Governance Loop

Keep the implementation inside Core.

- Add proposal detail retrieval.
- Add audit filters by proposal, event name, and limit.
- Add commit preflight that verifies proposal approval, ability availability,
  permissions, approval context shape, and fail-closed behavior without
  executing writes.
- Add the smallest useful admin approval UI.

### 2-6 Weeks: Harden The Ability Boundary

Coordinate with `npcink-abilities-toolkit`, but do not move ability ownership into
Core.

- Define read, dry-run, write, and destructive risk levels.
- Ensure write/destructive abilities can produce previews suitable for Core
  proposals.
- Add shared fixtures that prove Core discovers preferred abilities without
  becoming a workflow router.
- Treat `permission_callback` and dry-run semantics as the provider-side
  counterpart to Core approval policy.

### 6-12 Weeks: Build The First Commercial Shell Outside Core

Use the China-market toolbox research as the first practical product shell, but
keep it outside Core.

Recommended first product direction:

- domestic environment diagnostics and fixes for blocked external resources;
- search submission and index health across Baidu, Bing IndexNow, and related
  engines;
- WeChat login, sharing, and later payment integration;
- ICP and public security filing display support;
- Chinese comment and UGC moderation as compliance assistance;
- SMTP health checks for common China hosting constraints;
- object storage and CDN purge workflows for Aliyun, Tencent Cloud, Qiniu, and
  similar providers.

High-risk toolbox actions should submit proposals to Core instead of executing
directly. Examples include CDN purge, restore from backup, destructive cleanup,
bulk content changes, and external account configuration changes.

## AI And MCP Strategy

Core should be MCP-aware but not an MCP runtime.

WordPress 7.0 and related ecosystem work make Abilities API and MCP important
entry points for agents. The recommended split is:

- ability providers register machine-readable WordPress abilities;
- WordPress or a dedicated MCP adapter exposes those abilities to compatible
  agent clients;
- product plugins provide domain UX and workflows;
- Core governs risky operation handoffs through proposal, approval, preflight,
  commit authorization, and audit.

The Core-side adapter contract is documented in
[Agent MCP Entry Contract](agent-mcp-entry-contract.md). Scoped non-admin
callers are documented in [App Auth Scope Policy](app-auth-scope-policy.md).
Both contracts preserve the same rule: channel adapters may consume Core
governance, but Core does not become a channel runtime or a second projection
truth.

If a future task asks Core to expose MCP tools directly, add Agent Gateway
catalogs, or route natural language tasks, stop and write a boundary note
instead of implementing it.

## Decision Rules

Use these tests before adding anything to Core:

1. Does this decide whether an AI operation may safely change WordPress?
   If yes, it may belong in Core.
2. Does this create content, choose a model, configure a provider, or own a
   domain workflow?
   If yes, it belongs outside Core.
3. Does this execute a final write before proposal approval, commit preflight,
   idempotency, and audit contracts are covered?
   If yes, do not build it yet.
4. Does this define reusable WordPress abilities?
   If yes, put it in `npcink-abilities-toolkit` or a provider plugin.
5. Does this improve a product workflow such as China toolbox operations,
   Content Assistant, SEO, commerce, or media?
   If yes, build it as a product plugin that consumes Core governance.
6. Does this add a short-name ability alias or planning-label router?
   If yes, keep it out of runtime code and document the real `ability_id`
   handoff instead.
7. Is this a low-risk, single-object, fully previewed action initiated by a
   present WordPress administrator inside the admin UI?
   If yes, it may use local admin consent with audit instead of a Core proposal.
8. Is this external, automated, batch, destructive, high-impact, or not fully
   previewed before the user acts?
   If yes, keep or move it behind Core proposal review.

## Stop Conditions

Stop and document a boundary issue if a task tries to add any of these to Core:

- Content Assistant UX;
- toolbox modules;
- provider credential storage;
- model router, prompt library, or preset manager;
- workflow runtime, queues, retries, leases, or batch UI;
- short-name ability alias mapping or natural-language task routing;
- Agent Gateway or MCP runtime;
- direct WordPress write execution before commit preflight and idempotency are
  specified and tested.
