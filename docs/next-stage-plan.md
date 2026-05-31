# Next Stage Plan

Status: active planning guide.

This plan sequences the next work after the MVP scaffold and real WordPress
smoke baseline.

## Current Baseline

Implemented:

- plugin activation;
- proposal and audit tables;
- ability intake from `magick-ai-abilities`;
- REST capability list;
- proposal create/list;
- proposal approve/reject;
- audit list;
- static contracts;
- real WordPress smoke;
- minimal app-key authentication, scopes, rate limiting, and app audit
  attribution.
- admin UI for Core app-key creation, scoped one-time token display, and key
  disable actions.
- Core 0.4 consumer readiness for the `magick-ai-abilities` 0.4.0
  representative scenarios.
- capability execution guidance for OpenClaw and adapter clients, without
  adding Core proxy execution.
- governance operability baseline with proposal audit timelines,
  ability/app/key/caller/correlation audit filters, app scope-decision
  attribution, and commit-preflight correlation ids.
- documented AI provider log correlation contract that keeps provider request
  logs in the WordPress `ai` plugin and assigns productized context injection
  to Magick AI Adapter.
- plan-to-proposal governance bridge for content inventory fix, test content
  cleanup, and media inventory fix plans.
- trusted Adapter approval support through scoped `proposals:approve`, with
  Core still returning `commit_execution=false` and execution handoff guidance.

Not implemented:

- final commit execution;
- app-key rotation, revocation UI, and expiry automation;

Documented but not implemented:

- Agent/MCP governance entry contract;

## Current Next Decision

Core can now govern both single dry-run write proposals and supported
read-only plans that produce multiple `write_actions`. The next decision should
not be another Core runtime feature by default. Either:

- keep Core as the governance layer and let Adapter/product plugins execute
  approved writes after commit preflight; or
- write a separate ADR for final commit execution, including idempotency,
  retry, partial failure, and destructive action rules.

## Strategic Product Boundary

The next stage should keep Core focused on the governance kernel. WordPress 7.0
research and the current product split both point to the same plan:

- `magick-ai-core` governs AI-assisted WordPress operations through proposals,
  approval boundaries, commit preflight, and audit.
- `magick-ai-abilities` and provider plugins define reusable abilities and
  previews.
- product plugins own commercial workflows, including Content Assistant and a
  possible China-market toolbox product.

See [Strategy And Product Split](strategy-and-product-split.md). Do not move
toolbox modules, content generation, provider configuration, or workflow runtime
into Core while executing this plan.

## Recommended Order

### 1. Proposal Detail Endpoint

Status: implemented.

Goal: fetch one proposal by id.

Routes:

- `GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}`

Acceptance:

- returns 200 for existing proposal;
- returns 404 for missing proposal;
- covered by static contract and WordPress smoke.

### 2. Audit Filters

Status: implemented.

Goal: make audit useful for proposal review.

Filters:

- `proposal_id`
- `event_name`
- `limit`

Acceptance:

- audit list can return only events for one proposal;
- audit list can return one event type;
- no raw secrets in metadata.

### 3. Commit Preflight Contract

Status: implemented.

Goal: prepare for final commit without executing writes yet.

Route:

- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`

Acceptance:

- fails unless proposal is approved;
- confirms ability still exists;
- returns approval-commit context preview;
- does not execute the ability;
- never accepts `confirm_token` or `write_confirmed`.

### 4. Minimal Admin Approval UI

Status: implemented.

Goal: let humans review pending proposals inside WordPress.

Screen:

- Tools -> Magick AI Core

Acceptance:

- list pending proposals;
- show proposal detail;
- approve/reject with nonce and capability check;
- no content-generation UI.

### 5. App Auth, Scope, And Rate Policy

Status: minimal implementation active.

Goal: move beyond `manage_options`-only MVP.

Acceptance:

- app identity model documented first;
- scope rules are derived from ability metadata and Core policy;
- rate limits are stored in Core policy, not provider plugins;
- all decisions emit audit events.

Current implementation:

- app keys are created by admin-only `POST /apps`;
- app keys can also be created from `Tools -> Magick AI Core`;
- the admin UI keeps Core App Keys behind an advanced disclosure with
  one-time token display on the creation result page and a key disable action;
- the admin UI points productized OpenClaw setup to Magick AI Adapter and does
  not export OpenClaw handoff text, Adapter URLs, agent rules, or LocalWP TLS
  switches;
- raw secrets are returned once as bearer tokens;
- default external adapter scopes exclude approval and audit read;
- app-authenticated proposal and preflight events include app attribution.

See [App Auth Scope Policy](app-auth-scope-policy.md).

Trusted Adapter approve-and-execute uses this same app auth layer. Core may
grant `proposals:approve` to a separately issued trusted Adapter key, while
generic MCP keys continue to exclude approval scope by default. Core records
the app/key/caller attribution for the approval event; Adapter owns the single
user-facing approve-and-execute button and final WordPress Abilities API call.

### 6. Agent/MCP Governance Entry

Status: consumer readiness complete.

Goal: let WordPress, MCP adapters, Agent Gateway bridges, and product plugins
consume Core governance without moving MCP runtime or channel projection into
Core.

Acceptance:

- adapters expose abilities from WordPress Abilities API or provider plugins;
- Core stores proposals against real `ability_id` values only;
- Core decides approval and commit preflight;
- adapters execute target abilities only after Core approval context is valid;
- no MCP server, Agent Gateway catalog, natural-language router, or workflow
  runtime is added to Core.

See [Agent MCP Entry Contract](agent-mcp-entry-contract.md).

Current implementation:

- `magick-ai/create-draft` is the first solidified host-governed write
  scenario;
- `magick-ai/set-post-seo-meta` is the second solidified host-governed write
  scenario, covering field-level updates to an existing post;
- `magick-ai/approve-comment` is the third solidified host-governed write
  scenario, covering comment moderation and a non-post resource;
- `magick-ai/propose-post-taxonomy-terms` -> `magick-ai/set-post-terms` is the
  taxonomy terms preview scenario, covering read-helper-to-write-proposal
  handoff for existing taxonomy terms;
- `composer smoke:wp` verifies discovery, schema controls, proposal creation,
  admin approval, and commit preflight for the representative real ability ids;
- the OpenClaw example adapter includes `create-draft-proposal` and
  `create-seo-meta-proposal`, and `create-comment-approval-proposal`, which
  discover capabilities before creating proposals and still do not approve or
  execute writes.
- the OpenClaw example adapter also includes `create-taxonomy-terms-proposal`,
  which consumes taxonomy helper output or already resolved existing terms
  before creating the governed `magick-ai/set-post-terms` proposal.
- capability rows now tell adapters whether to use direct read execution through
  WordPress Abilities API or proposal-required governance through Core.

See [Core 0.4 Consumer Readiness](core-0.4-consumer-readiness.md).
See [OpenClaw Execution Guidance](openclaw-execution-guidance.md).
See [Create Draft Governance Scenario](create-draft-governance-scenario.md).
See [Set Post SEO Meta Governance Scenario](set-post-seo-meta-governance-scenario.md).
See [Approve Comment Governance Scenario](approve-comment-governance-scenario.md).
See [Taxonomy Terms Preview Governance Scenario](taxonomy-terms-preview-governance-scenario.md).

### 7. Core Governance Operability

Status: minimal implementation active.

Goal: make the existing governance loop reviewable and diagnosable before
adding product features or execution runtime.

Acceptance:

- proposal detail includes an `audit_timeline`;
- admin proposal detail shows live capability summary and audit timeline;
- audit filters cover `ability_id`, `app_id`, `key_id`, `caller_type`, and
  `correlation_id`;
- app-authenticated audit metadata includes `scope_decision`;
- commit preflight returns and audits a `correlation_id`;
- no final WordPress mutation, proxy execution, MCP runtime, workflow runtime,
  or product UX is added.

See [Core Governance Operability](core-governance-operability.md).

### 8. Final Commit Execution ADR Decision

Status: decision required.

Goal: decide whether Core should design final commit execution at all.

Acceptance before implementation:

- write a separate ADR for final commit execution;
- define authorization, idempotency, failure semantics, retry behavior, audit
  attribution, and adapter responsibility;
- preserve `commit_execution=false` until the ADR is accepted and implemented;
- avoid adding final WordPress mutation routes as an incidental extension of
  commit preflight.

### 9. AI Provider Log Correlation Acceptance

Status: Core contract documented; Adapter implementation required.

Goal: turn the manual local Ollama proof into a repeatable productized Adapter
acceptance path without moving provider execution into Core.

Acceptance:

- Adapter creates or receives a Core-governed proposal context;
- Core approval and commit preflight produce a `correlation_id`;
- Adapter performs a real AI provider request through the WordPress `ai` plugin
  or provider connector;
- Adapter injects `proposal_id`, `correlation_id`, `ability_id`,
  `adapter_request_id`, `adapter_route`, `ai_provider`, `ai_model`, and
  `governance_source=magick-ai-core` into the AI request log context;
- AI Request Logs can be queried by `proposal_id` and `correlation_id`;
- Core does not add provider execution, provider credentials, prompt/response
  storage, or token accounting.

See [AI Provider Log Correlation](ai-provider-log-correlation.md).

### 10. OpenClaw Adapter / Agent Gateway Planning

Status: outside Core, productized acceptance in Magick AI Adapter.

Goal: design a dedicated adapter or gateway layer that presents WordPress
abilities to OpenClaw while preserving Core as the governance layer.

Acceptance before implementation:

- adapter reads Core capability execution guidance;
- adapter may proxy Core proposal list/detail reads for OpenClaw status polling
  with `proposals:read`;
- read abilities execute through WordPress Abilities API;
- write and destructive abilities go through Core proposal and commit
  preflight first;
- adapter does not expose approve/reject by default; any approval proxy needs a
  separate trusted host policy and explicit approval scopes;
- OpenClaw tool presentation, MCP transport, workflow routing, queues, and
  long-running task handling stay outside Core.
- real AI provider request log correlation is implemented and tested in
  Adapter, not Core.

Current handoff:

- productized OpenClaw clients should connect to Magick AI Adapter, not Core;
- Adapter owns the OpenClaw connection UI, Application Password handoff, route
  discovery, direct-read shortcuts, proposal status bridge, and acceptance
  checklist;
- Core remains the backing governance service for capability guidance,
  proposal records, approval/rejection, commit preflight, app-key policy, and
  audit;
- Core documentation only cross-references Adapter acceptance so future agents
  do not recreate OpenClaw onboarding inside Core.

See `/Users/muze/gitee/magick-ai-adapter/docs/openclaw-consumer-acceptance.md`
for the productized OpenClaw acceptance checklist.

## Stop Conditions

Stop and write a boundary note if a task tries to add:

- article/media/comment generation UX;
- model/provider settings;
- workflow runtime or queues;
- Agent Gateway or MCP surfaces;
- direct provider credential storage;
- final commit execution before preflight and idempotency contracts exist.
