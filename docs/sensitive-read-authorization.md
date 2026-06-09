# Sensitive Read Authorization

Status: implemented Core governance contract.

Core-managed sensitive read authorization is the read-side counterpart to
proposal approval. It is for read abilities that can expose private operational
data such as logs, diagnostics, database-derived records, private content
fields, account metadata, or other sensitive site information.

This contract is not a write proposal and does not execute reads. It creates a
reviewable Core record, lets an administrator or trusted host policy approve or
reject it, and returns a bounded `read_authorization_context` that an Adapter
must check before executing the read through the normal WordPress Abilities API
surface.

## Intended Use

Use this flow when a capability row marks a read ability with one or more of:

- `read_authorization_required=true`
- `requires_read_authorization=true`
- `read_policy=core_read_authorization_required`
- `authorization_mode=core_read_request`
- `read_authorization.required=true`
- `governance_mode=core_read_authorization_required`

The Adapter must fail closed when these fields appear. It should create or
reuse a Core read request, wait for approval, call read preflight with the same
`ability_id` and input, then execute the read only if Core returns
`read_authorization_granted=true`.

## Record Fields

Core stores one `{prefix}npcink_governance_core_read_requests` row with:

- `request_id`
- `ability_id`
- `input_hash`
- `requested_input_summary`
- `sensitivity`
- `data_classes`
- `redaction_level`
- `purpose`
- `caller`
- `status`: `pending`, `approved`, `rejected`, `expired`, or `consumed`
- `expires_at`
- `bounds`: `max_rows`, `tail_lines`, `allowed_fields`, `denied_fields`, and
  `one_time`
- `correlation_id`
- `created_at`
- `updated_at`
- `consumed_at`

Core stores sanitized summaries and metadata only. It must not store or return
raw read results, provider prompts, authorization headers, cookies, app tokens,
application passwords, private keys, or provider secrets.

## REST Routes

All routes use `npcink-governance-core/v1` and require either a WordPress
administrator with `manage_options` or the listed app scope.

| Route | Scope | Purpose |
| --- | --- | --- |
| `POST /read-requests` | `read_requests:create` | Create a pending sensitive read request bound to `ability_id` and `input_hash`. |
| `GET /read-requests` | `read_requests:read` | List recent read request records. |
| `GET /read-requests/{request_id}` | `read_requests:read` | Fetch request detail with `audit_timeline`. |
| `POST /read-requests/{request_id}/approve` | `read_requests:approve` | Approve a pending request and optionally tighten expiry, redaction, or bounds. |
| `POST /read-requests/{request_id}/reject` | `read_requests:reject` | Reject a pending request. |
| `POST /read-requests/{request_id}/read-preflight` | `read_requests:preflight` | Return bounded read authorization context for the approved input. |

Request creation accepts either structured `input` or an explicit 64-character
SHA-256 `input_hash`. It must also include a review `purpose` and at least one
`data_classes` entry so the administrator can review why the sensitive data is
needed and what kind of data may be exposed. `redaction_level` can be `standard`
or `strict` only; sensitive read authorization cannot disable redaction. If
`input` is supplied, Core computes the approved hash from sanitized structured
input. Read preflight must submit the same `ability_id` and either the same
`input` or the approved `input_hash`; mismatches are rejected.

## Grant Context

Successful read preflight returns:

```json
{
  "read_authorization_context": {
    "request_id": "uuid",
    "ability_id": "npcink-abilities-toolkit/read-error-log",
    "approved_input_hash": "sha256",
    "correlation_id": "uuid",
    "policy_version": "core-read-authorization-v1",
    "sensitivity": "sensitive",
    "data_classes": ["logs", "diagnostics"],
    "redaction_level": "strict",
    "expires_at": "2026-06-09 12:00:00",
    "bounds": {
      "max_rows": 50,
      "tail_lines": 100,
      "allowed_fields": ["timestamp", "message", "severity"],
      "denied_fields": ["authorization", "cookie"],
      "one_time": false
    },
    "read_authorization_granted": true,
    "core_authorization_truth": "npcink_governance_core",
    "commit_execution": false,
    "write_execution": false
  }
}
```

The context is not a write approval, not a prompt-derived permission, and not an
execution token for any other ability or input.

## Audit Semantics

Core records:

- `read_request.created`
- `read_request.approved`
- `read_request.rejected`
- `read_request.expired`
- `read_request.viewed`
- `read_request.listed`
- `read_request.preflighted`
- `read_request.preflight_failed`
- `read_request.consumed` for one-time grants

The request detail route returns an `audit_timeline` ordered oldest to newest.
Audit metadata includes `request_id`, `ability_id`, `input_hash`,
`correlation_id`, `policy_version`, sensitivity, data classes, redaction level,
and `commit_execution=false` / `write_execution=false`.

## Adapter Handoff Contract

Adapter must:

- fail closed when capability rows require Core read authorization;
- call `read_authorization_request_route` to create the request when needed;
- call `read_authorization_status_route` to poll or display review state;
- call `read_authorization_preflight_route` immediately before execution;
- verify `ability_id`, `approved_input_hash`, `expires_at`, bounds,
  `read_authorization_granted=true`, and
  `core_authorization_truth=npcink_governance_core`;
- apply `redaction_level`, `allowed_fields`, `denied_fields`, `max_rows`, and
  `tail_lines` before returning read data to an agent or logs;
- treat `redaction_level=none` as invalid for sensitive read authorization;
- reject expired, rejected, consumed, mismatched, or unaudited grants.

Adapter must not store an Adapter-owned approval truth for this flow. It may
cache display state, but Core remains the authorization truth.

## Non-Goals

This contract does not add:

- Core read execution or proxy execution;
- write approval, final commit execution, or write preflight;
- Adapter-owned approval truth;
- OpenClaw prompt authorization;
- direct database, filesystem, log, or custom script authorization outside
  Core;
- workflow runtime, task queue, MCP runtime, Cloud runtime, provider routing,
  prompt/preset storage, or model credentials.
