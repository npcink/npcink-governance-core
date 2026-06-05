# Database Schema

Status: active for MVP.

Npcink Governance Core currently owns four custom tables. They are created during plugin
activation with `dbDelta()`.

## Table: `{prefix}npcink_governance_core_proposals`

Purpose: stores reviewable operation proposals. Proposal rows are lifecycle
records; they are not workflow runtime state.

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | no | Internal auto-increment primary key. |
| `proposal_id` | `varchar(64)` | no | Public stable id, generated with `wp_generate_uuid4()` when available. |
| `ability_id` | `varchar(190)` | no | Target WordPress ability id. |
| `status` | `varchar(40)` | no | `pending`, `approved`, `rejected`, `expired`, or `archived`. |
| `title` | `text` | yes | Human-readable title. |
| `summary` | `longtext` | yes | Human-readable summary. |
| `input_json` | `longtext` | yes | Sanitized structured input. |
| `preview_json` | `longtext` | yes | Sanitized dry-run preview or handoff payload. |
| `caller_json` | `longtext` | yes | Sanitized caller metadata, including non-secret Core guardrails and policy decision metadata. |
| `created_by` | `bigint(20) unsigned` | no | WordPress user id. |
| `created_at` | `datetime` | no | UTC time from `current_time( 'mysql', true )`. |
| `updated_at` | `datetime` | no | UTC time from `current_time( 'mysql', true )`. |

Indexes:

- primary key: `id`
- unique key: `proposal_id`
- key: `ability_id`
- key: `status`
- key: `created_at`

Allowed statuses:

- `pending`
- `approved`
- `rejected`
- `expired`
- `archived`

Status transition rules:

- proposals start as `pending`;
- only `pending` proposals may transition to `approved`;
- only `pending` proposals may transition to `rejected`;
- stale `pending` proposals transition to `expired` after the Core pending TTL;
- only `expired` proposals may transition to `archived`;
- `expired` or `archived` proposals may be reopened to `pending` for review;
- MVP status transitions do not execute the target ability.

## Table: `{prefix}npcink_governance_core_audit_log`

Purpose: append-only governance events.

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | no | Internal auto-increment primary key. |
| `event_id` | `varchar(64)` | no | Public stable id, generated with `wp_generate_uuid4()` when available. |
| `event_name` | `varchar(120)` | no | Dotted event name such as `proposal.created`. |
| `proposal_id` | `varchar(64)` | no | Related proposal id or empty string. |
| `actor_id` | `bigint(20) unsigned` | no | WordPress user id. |
| `metadata_json` | `longtext` | yes | Sanitized event metadata. |
| `created_at` | `datetime` | no | UTC time from `current_time( 'mysql', true )`. |

Indexes:

- primary key: `id`
- unique key: `event_id`
- key: `event_name`
- key: `proposal_id`
- key: `created_at`

MVP event names:

- `app.created`
- `app.listed`
- `app.revoked`
- `app.rate_limited`
- `app.scope_denied`
- `capabilities.listed`
- `proposal.created`
- `proposal.policy_evaluated`
- `proposal.auto_approved`
- `proposal.plan_ingested`
- `proposal.approved`
- `proposal.rejected`
- `proposal.expired`
- `proposal.archived`
- `proposal.reopened`
- `proposal.viewed`
- `proposal.listed`
- `audit.listed`
- `commit.preflighted`
- `core.approval_policy_updated`

Governance operability metadata:

- proposal lifecycle events include `ability_id` when available;
- policy evaluation events include `policy_decision`, `policy_profile`,
  `policy_version`, `policy_mode`, `policy_reasons`,
  `auto_approval_applied`, optional auto-approval quota limits, and
  `commit_execution=false`;
- plan intake events include `plan_ability_id`, `batch_id`,
  `action_count`, `proposal_count`, `blocked_count`,
  `needs_input_count`, and `commit_execution=false`;
- app-authenticated events include `metadata.auth.app_id`,
  `metadata.auth.key_id`, `metadata.auth.caller_type`,
  `metadata.auth.scope`, `metadata.auth.scope_decision`, and
  `metadata.auth.route_family`;
- commit preflight events include `metadata.correlation_id`;
- audit reads support metadata filters for `ability_id`, `app_id`, `key_id`,
  `caller_type`, and `correlation_id` without adding extra audit columns.

## Table: `{prefix}npcink_governance_core_app_keys`

Purpose: stores scoped app identities for external governance clients.

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | no | Internal auto-increment primary key. |
| `app_id` | `varchar(64)` | no | Stable public app id. |
| `app_label` | `varchar(190)` | no | Human-readable label. |
| `key_id` | `varchar(64)` | no | Public key id used in bearer tokens. |
| `secret_hash` | `varchar(255)` | no | Password hash of the raw secret. Raw secrets are returned once and never stored. |
| `status` | `varchar(40)` | no | `active` or `revoked`; `expired` is reserved. |
| `scopes_json` | `longtext` | yes | JSON array of allowed scopes. |
| `rate_limit` | `int unsigned` | no | Requests allowed per route-family window. |
| `rate_window_seconds` | `int unsigned` | no | Fixed-window duration. |
| `caller_type` | `varchar(80)` | no | Sanitized caller type such as `mcp_adapter`. |
| `created_by` | `bigint(20) unsigned` | no | WordPress user id that created the app. |
| `created_at` | `datetime` | no | UTC creation time. |
| `updated_at` | `datetime` | no | UTC update time. |
| `last_used_at` | `datetime` | yes | Last successful app-authenticated request. |

Indexes:

- primary key: `id`
- unique key: `app_id`
- unique key: `key_id`
- key: `status`
- key: `created_at`

## Table: `{prefix}npcink_governance_core_app_rate_limits`

Purpose: fixed-window rate counters for app-authenticated requests.

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | no | Internal auto-increment primary key. |
| `app_id` | `varchar(64)` | no | App id. |
| `key_id` | `varchar(64)` | no | Key id used for the request. |
| `route_family` | `varchar(80)` | no | Scope route family, such as `proposals_create`. |
| `window_start` | `datetime` | no | UTC fixed-window start. |
| `window_end` | `datetime` | no | UTC fixed-window end. |
| `request_count` | `int unsigned` | no | Requests used in the window. |
| `created_at` | `datetime` | no | UTC creation time. |
| `updated_at` | `datetime` | no | UTC update time. |

Indexes:

- primary key: `id`
- unique key: `app_route_window` on `app_id`, `route_family`, `window_start`
- key: `key_id`
- key: `window_end`

## Migration Rules

- Schema changes must update this document, `docs/architecture.md`, and
  `tests/run.php`.
- New columns must have deterministic defaults or nullable semantics.
- Do not remove existing columns without an ADR.
- Do not store secrets, provider keys, cookies, passwords, raw request headers,
  or unsanitized user input.
- App key raw secrets must never be stored; only `secret_hash` is persisted.
- Do not add workflow runtime, queue, retry, lease, or batch execution state to
  these tables.

## Local Smoke Data

`composer smoke:wp` creates local proposal and audit records. That is expected.
Cleanup can be done manually in local development if needed, but no automated
cleanup is part of the MVP.
