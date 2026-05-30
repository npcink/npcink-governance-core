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
- admin UI for external clients to copy the Core URL and create scoped one-time
  app tokens.

Not implemented:

- final commit execution;
- app-key rotation, revocation UI, and expiry automation;

Documented but not implemented:

- Agent/MCP governance entry contract;

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
- the admin UI includes a copyable OpenClaw handoff guide and key disable
  action;
- the OpenClaw handoff can include an explicit local TLS test setting for
  `.local`/`localhost` PoC work without changing Core server policy;
- raw secrets are returned once as bearer tokens;
- default external adapter scopes exclude approval and audit read;
- app-authenticated proposal and preflight events include app attribution.

See [App Auth Scope Policy](app-auth-scope-policy.md).

### 6. Agent/MCP Governance Entry

Status: create-draft governance scenario active.

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
- `composer smoke:wp` verifies discovery, schema controls, proposal creation,
  admin approval, and commit preflight for that real ability id;
- the OpenClaw example adapter includes `create-draft-proposal`, which discovers
  capabilities before creating the proposal and still does not approve or
  execute writes.

See [Create Draft Governance Scenario](create-draft-governance-scenario.md).

## Stop Conditions

Stop and write a boundary note if a task tries to add:

- article/media/comment generation UX;
- model/provider settings;
- workflow runtime or queues;
- Agent Gateway or MCP surfaces;
- direct provider credential storage;
- final commit execution before preflight and idempotency contracts exist.
