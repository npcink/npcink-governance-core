# Database Schema

Status: active for MVP.

Magick AI Core currently owns two custom tables. They are created during plugin
activation with `dbDelta()`.

## Table: `{prefix}magick_ai_core_proposals`

Purpose: stores reviewable operation proposals. Proposal rows are lifecycle
records; they are not workflow runtime state.

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | no | Internal auto-increment primary key. |
| `proposal_id` | `varchar(64)` | no | Public stable id, generated with `wp_generate_uuid4()` when available. |
| `ability_id` | `varchar(190)` | no | Target WordPress ability id. |
| `status` | `varchar(40)` | no | `pending`, `approved`, or `rejected`. |
| `title` | `text` | yes | Human-readable title. |
| `summary` | `longtext` | yes | Human-readable summary. |
| `input_json` | `longtext` | yes | Sanitized structured input. |
| `preview_json` | `longtext` | yes | Sanitized dry-run preview or handoff payload. |
| `caller_json` | `longtext` | yes | Sanitized caller metadata. |
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

Status transition rules:

- proposals start as `pending`;
- only `pending` proposals may transition to `approved`;
- only `pending` proposals may transition to `rejected`;
- MVP status transitions do not execute the target ability.

## Table: `{prefix}magick_ai_core_audit_log`

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

- `capabilities.listed`
- `proposal.created`
- `proposal.approved`
- `proposal.rejected`
- `proposal.viewed`
- `proposal.listed`
- `audit.listed`
- `commit.preflighted`

## Migration Rules

- Schema changes must update this document, `docs/architecture.md`, and
  `tests/run.php`.
- New columns must have deterministic defaults or nullable semantics.
- Do not remove existing columns without an ADR.
- Do not store secrets, provider keys, cookies, passwords, raw request headers,
  or unsanitized user input.
- Do not add workflow runtime, queue, retry, lease, or batch execution state to
  these tables.

## Local Smoke Data

`composer smoke:wp` creates local proposal and audit records. That is expected.
Cleanup can be done manually in local development if needed, but no automated
cleanup is part of the MVP.
