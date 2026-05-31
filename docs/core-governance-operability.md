# Core Governance Operability

Status: minimal implementation active.

This document defines the first operational layer around Core governance. It
answers a practical question: after an adapter can discover abilities, create a
proposal, and request commit preflight, can an operator trace what happened and
make an approval decision with enough context?

## Positioning

Core remains the WordPress AI operation governance layer. This operability work
does not make Core an ability executor, workflow runtime, MCP runtime, product
console, or general observability platform.

Core owns the governance evidence needed to answer:

- which ability was proposed;
- who or which app called Core;
- which scope decision allowed, denied, or rate-limited the request;
- which proposal lifecycle events happened;
- which commit-preflight correlation id ties an approval context to audit.

The minimal surface is proposal audit timelines, audit filters, scope-decision
attribution, and commit-preflight correlation.

Execution remains outside Core:

- `commit_execution=false`;
- `core_proxy_execute=false`;
- direct-read abilities execute through WordPress Abilities API;
- write/destructive abilities continue through proposal, approval/rejection,
  and commit preflight before any external host can execute them.

## Implemented Surface

### Proposal Detail

`GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}` returns the proposal
row plus `audit_timeline`, ordered oldest to newest for the selected proposal.

The WordPress admin proposal detail also shows:

- proposal status and summary;
- review context from live ability intake and preview metadata, including
  before/after suggestions when present;
- raw caller, input, and preview JSON behind an explicit disclosure;
- audit timeline with event, actor, app, scope decision, and correlation id;
- approve/reject form for pending proposals.

### Governance Audit Admin View

`Magick AI -> Core` keeps recent activity on the default review workbench and
links to a dedicated `Governance Audit` view for full inspection. It is an
operator view over Core audit records, not an AI request log viewer.

The default view shows a short recent activity table. The full audit view keeps
the advanced audit filter disclosure for:

- proposal id;
- event name;
- ability id;
- app id;
- caller type;
- correlation id;
- limit.

The result table shows time, event, proposal link, actor, ability, app/caller,
scope decision, and correlation id. AI Request Logs remain owned by the
WordPress `ai` plugin; operators should correlate the two systems with
`proposal_id` or `correlation_id` rather than merging their storage.

### Core App Keys

The default review workbench links to a dedicated `Core App Keys` view for
app-key creation and key disable actions. This preserves the Core credential
fallback without turning the default governance page into an OpenClaw onboarding
or adapter configuration screen. Productized OpenClaw connection copy, TLS
switches, and handoff instructions remain Adapter-owned.

For real AI provider requests, Adapter should inject Core `proposal_id` and
commit-preflight `correlation_id` into the `ai` plugin request log context.
Core audit remains the governance record; AI Request Logs remain the provider
request record. See [AI Provider Log Correlation](ai-provider-log-correlation.md).

### Audit Filters

`GET /wp-json/magick-ai-core/v1/audit` supports these filters:

- `proposal_id`;
- `event_name`;
- `ability_id`;
- `app_id`;
- `key_id`;
- `caller_type`;
- `correlation_id`;
- `limit`.

The metadata filters are intentionally narrow string filters over sanitized
audit metadata. They make governance review and smoke diagnostics useful
without adding a separate logging index or analytics subsystem.

### Scope Decision Attribution

App-authenticated audit metadata includes:

- `auth.app_id`;
- `auth.key_id`;
- `auth.caller_type`;
- `auth.scope`;
- `auth.scope_decision`;
- `auth.route_family`.

Current `scope_decision` values:

- `allowed`;
- `denied`;
- `rate_limited`.

Raw app secrets, bearer tokens, authorization headers, cookies, passwords, and
provider credentials must not be stored in audit metadata or proposal caller
metadata.

### Commit-Preflight Correlation

Successful commit preflight returns a `correlation_id` at the top level and in
`approval_context.correlation_id`. The same value is stored in the
`commit.preflighted` audit event metadata.

This lets an adapter or operator connect:

```text
proposal -> approval -> commit-preflight response -> commit.preflighted audit
```

Core still does not execute the target WordPress mutation.

## Non-Goals

Do not add these as part of Core governance operability:

- final commit execution;
- `/execute` or `/proxy-execute`;
- MCP server or workflow runtime;
- Agent Gateway task catalog;
- OAuth or cloud account system;
- product UX for content, SEO, comments, media, or workflows;
- provider credential storage;
- long-term metrics warehouse or log shipping.

## Next Decisions

The next useful Core decisions are:

1. Whether proposal detail needs richer decision annotations before expanding
   approval policy.
2. Whether app-key rotation and expiry are needed before external product use.
3. Whether Adapter's real AI provider log correlation smoke should become a
   productized OpenClaw acceptance gate.
4. Whether final commit execution deserves a separate ADR.

Until a final commit execution ADR is accepted, Core remains a governance layer:
ability intake, proposal records, approval/rejection, commit preflight, and
audit.
