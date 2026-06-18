# Core Governance Operability

Status: minimal implementation active.

This document defines the first operational layer around Core governance. It
answers a practical question: after an adapter can discover abilities, create a
proposal, and request commit preflight, can an operator trace what happened and
make an approval decision with enough context?

## Positioning

Core remains the Npcink AI governance layer for WordPress operations. This operability work
does not make Core an ability executor, workflow runtime, MCP runtime, product
console, or general observability platform.

Core owns the governance evidence needed to answer:

- which ability was proposed;
- who or which app called Core;
- which scope decision allowed, denied, or rate-limited the request;
- which proposal lifecycle events happened;
- which commit-preflight correlation id ties an approval context to audit.
- which sensitive read request lifecycle events happened;
- which read-preflight correlation id ties a bounded read authorization context
  to audit.

The minimal surface is proposal audit timelines, audit filters, scope-decision
attribution, commit-preflight correlation, and sensitive read request audit
timelines.

Execution remains outside Core:

- `commit_execution=false`;
- `core_proxy_execute=false`;
- direct-read abilities execute through WordPress Abilities API;
- sensitive read abilities execute through WordPress Abilities API only after
  Core returns bounded `read_authorization_context`;
- write/destructive abilities continue through proposal, approval/rejection,
  and commit preflight before any external host can execute them.

### Sensitive Read Request Detail

`GET /wp-json/npcink-governance-core/v1/read-requests/{request_id}` returns the
read request row plus `audit_timeline`, ordered oldest to newest for the
selected request. The timeline uses Core audit records and includes create,
approve, reject, preflight, expiry, and one-time consumption events.

The request row stores review and handoff metadata only: `ability_id`,
`input_hash`, purpose, sensitivity, data classes, redaction level, expiry,
bounds, caller metadata, and correlation id. It does not store raw read results,
logs, files, database rows, prompts, provider secrets, cookies, or
authorization headers.

## Implemented Surface

### Proposal Detail

`GET /wp-json/npcink-governance-core/v1/proposals/{proposal_id}` returns the proposal
row plus `audit_timeline`, ordered oldest to newest for the selected proposal.

The WordPress admin proposal detail also shows:

- compact review id, status, risk, and warning/blocker counts in the top
  operator summary, with a single no-risk conclusion when preview evidence has
  no warnings, blocked items, required input, or preflight blockers;
- inline pending-decision slot in the top summary with always-visible approval
  and a secondary rejection disclosure for rejection notes, without a separate
  empty action bar;
- proposal detail tabs for overview, action plan, audit evidence, and technical
  information so dense action/audit/troubleshooting data is not shown in the
  first scan;
- collapsed technical identity/source/policy inspectors in the technical tab for
  display id, full proposal id, target ability, timestamps, source trace,
  caller/app attribution, and policy reasons;
- explicit non-pending outcome copy when approval/rejection controls are absent;
- batch action details in the action-plan tab for plan-to-proposal rows,
  including ordered action id, target ability, readiness, and dependency
  information while final execution remains outside Core;
- grouped review basis from live ability intake and preview metadata, with
  zero-value preview signals reduced to one no-issues line and before/after
  suggestions kept behind structured preview detail in the action-plan tab when
  present;
- article workflow summary when `preview.article_workflow` exists, including
  title/topic, risk level, readiness, blocked-claim count, final write ability,
  final write path, direct-write state, and required artifact availability;
- audit lifecycle summary in the audit-evidence tab with event labels and
  timestamps visible by default, plus a collapsed full audit timeline with
  actor and compact technical metadata;
- raw caller, input, and preview JSON behind a troubleshooting disclosure in the
  technical tab;
- approve/reject controls for pending proposals before the detail tabs.
- archive/reopen controls for expired or archived proposals.

The default review queue keeps each pending proposal traceable without leading
with machine details. Every row shows a user-facing request label, created time,
the `Proposal ID` lookup handle, and a clear decision entry. Target ability and
source trace metadata such as plan-to-proposal source, batch id, action id,
caller type, or app id stay available behind per-row technical details. This
lets operators match Core approval items back to Adapter/OpenClaw tasks, AI
Request Logs, and audit filters without making the first scan feel like a
protocol log.

The review queue also supports bounded bulk rejection for selected pending
proposals. Bulk rejection reuses the normal reject transition for each row so
every proposal still records `proposal.rejected`; it is intended for obsolete
pending proposals that have been superseded by a safer batch review.

### Expired And Archived Proposals

Pending proposals expire automatically after the Core pending review TTL. Core
marks stale rows as `expired`, records `proposal.expired`, and removes them
from the default review queue. The `Expired / Archived` admin tab keeps stale
records visible without letting them crowd active review work.

Administrators may archive expired proposals as low-frequency audit records.
Archived and expired proposals may be reopened to `pending` review when a stale
request still needs a decision. Reopening records `proposal.reopened`; archiving
records `proposal.archived`. None of these lifecycle transitions execute an
ability or final WordPress mutation.

### Activity Log Admin View

`Npcink AI -> Core` keeps recent activity on the default review workbench and
links to a dedicated `Activity Log` view for full inspection. It is an operator
view over Core audit records, not an AI request log viewer.

The default view shows one latest recent activity row and links to the full
activity log instead of rendering another audit table on the review workbench.
The full activity view keeps low-value read/list events hidden by default,
shows user-facing activity labels in the main table, and opens with a compact
filter toolbar for:

- broad search across proposal, event, ability, client, caller, and correlation
  identifiers;
- event type;
- time range;
- per-page display count;
- optional read/list noise events.

Exact proposal id, ability id, app id, caller type, and correlation id remain
inside an `Advanced filters` disclosure. Active filters render as chips so the
operator can see and clear the current narrowing without reading a long form.

Read-only admin navigation uses short GET URLs without nonce parameters.
Nonces stay on POST forms that change approval, lifecycle, policy, or app-key
state.

The result table shows time, user-facing event label, short proposal display id,
compact context, and a row-level details disclosure. It uses WordPress-style
top and bottom table navigation with item count plus first/previous/next/last
page controls so long audit histories can be scanned like other admin lists.
Empty app, scope, and correlation fields are omitted instead of rendered as
placeholder-only columns. Full proposal id, raw event name, actor, ability,
app, caller, scope, and correlation values remain available in the per-row
details table for troubleshooting. AI Request Logs remain owned by the
WordPress `ai` plugin; operators should correlate the two systems with
`proposal_id` or `correlation_id` rather than merging their storage.

### Core App Keys

The default review workbench keeps Core app-key management behind a collapsed
`Advanced Access` disclosure for client access keys. The advanced access page
handles app-key creation and paginated key disable actions. This preserves the
Core credential fallback without turning Core's first-level tabs into OpenClaw
onboarding or adapter configuration. Productized OpenClaw connection copy, TLS
switches, and handoff instructions remain Adapter-owned.

For real AI provider requests, Adapter should inject Core `proposal_id` and
commit-preflight `correlation_id` into the `ai` plugin request log context.
Core audit remains the governance record; AI Request Logs remain the provider
request record. See [AI Provider Log Correlation](ai-provider-log-correlation.md).

### Audit Filters

`GET /wp-json/npcink-governance-core/v1/audit` supports these filters:

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
- `expired`;
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

### Local Observability Hook

Core emits a local `npcink_governance_core_observability_event` action for proposal create,
plan intake, approve, reject, and commit-preflight REST operations. The payload
is metadata-only and includes stable operation kind, status, error code,
latency, and safe identifiers such as proposal id, ability id, or correlation
id when available.

Canonical Core operation event kinds are:

- `core.proposal.create`
- `core.proposal.plan_ingest`
- `core.proposal.approve`
- `core.proposal.reject`
- `core.commit.preflight`

Successful operations emit `status=ok`. Expected governance preflight blocks,
such as a pending proposal, blocked proposal items, or duplicate handoff
request, emit `status=warning` with the stable Core `npcink_governance_core_*`
`error_code`. Failed operation paths emit `status=error`. Event payloads are
bounded to operational metadata and must not include proposal input, preview,
caller payloads, approval notes, generated content, or policy payloads.

This hook is optional operational detail for local listeners such as a Cloud
Addon. It is not an audit replacement, not a Cloud transport client, not a
remote log shipping system, and not a second proposal, approval, preflight, or
WordPress write truth.

## Non-Goals

Do not add these as part of Core governance operability:

- final commit execution;
- `/execute` or `/proxy-execute`;
- MCP server or workflow runtime;
- Agent Gateway task catalog;
- OAuth or cloud account system;
- product UX for content, SEO, comments, media, or workflows;
- provider credential storage;
- mandatory remote telemetry or Cloud writeback;
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
