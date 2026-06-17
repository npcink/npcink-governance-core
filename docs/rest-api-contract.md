# REST API Contract

Status: active for MVP.

All MVP routes use the namespace `npcink-governance-core/v1`. Routes accept either a
WordPress administrator with `manage_options` or a scoped Npcink Governance Core app key
when the route has an app scope listed below.

Agent and MCP adapter entry is governed by
[Agent MCP Entry Contract](agent-mcp-entry-contract.md). Scoped app
authentication is governed by [App Auth Scope Policy](app-auth-scope-policy.md).
Neither contract turns Core into an MCP runtime or final WordPress write
executor.

## Common Rules

- Request and response bodies are JSON.
- Route permissions fail closed.
- All write-like routes record audit events.
- Routes must not accept legacy `confirm_token` or `write_confirmed`
  parameters.
- Routes must not execute final WordPress writes until the final commit contract
  is documented and tested.
- Routes must store real `ability_id` values only; planning labels and channel
  tool names are not runtime identifiers.

## App-Authenticated Access

App-authenticated access is additive to the current REST surface. The current
scope map is:

| Route family | Required scope |
| --- | --- |
| `GET /contract` | admin-only `manage_options` |
| `GET /capabilities` | `capabilities:read` |
| `POST /proposals`, `POST /proposals/from-plan` | `proposals:create` |
| `GET /proposals`, `GET /proposals/{proposal_id}` | `proposals:read` |
| `POST /proposals/{proposal_id}/approve` | `proposals:approve` |
| `POST /proposals/{proposal_id}/reject` | `proposals:reject` |
| `POST /proposals/{proposal_id}/commit-preflight` | `commit:preflight` |
| `POST /proposals/{proposal_id}/record-execution` | `commit:record_execution` |
| `POST /read-requests` | `read_requests:create` |
| `GET /read-requests`, `GET /read-requests/{request_id}` | `read_requests:read` |
| `POST /read-requests/{request_id}/approve` | `read_requests:approve` |
| `POST /read-requests/{request_id}/reject` | `read_requests:reject` |
| `POST /read-requests/{request_id}/read-preflight` | `read_requests:preflight` |
| `GET /audit` | `audit:read` |
| `GET /apps`, `POST /apps` | admin-only `manage_options` |

Generic MCP adapters should not receive `proposals:approve` or `audit:read` by
default. Missing or revoked app identity must return `401`; missing scope must
return `403`; rate-limited requests must return `429`.

App auth error codes:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_app_auth_missing` | `401` | No WordPress admin session and no app token. |
| `npcink_governance_core_app_auth_malformed` | `400` | Bearer app token does not match the Core token shape. |
| `npcink_governance_core_app_auth_invalid` | `401` | App token is unknown, inactive, or has an invalid secret. |
| `npcink_governance_core_app_scope_forbidden` | `403` | App key does not include the route's required scope. |
| `npcink_governance_core_app_rate_limited` | `429` | App key exceeded its fixed-window route-family limit. |
| `npcink_governance_core_pending_proposal_quota_exceeded` | `429` | Caller already has too many pending proposals. |

App tokens use:

```text
Authorization: Bearer npcink_governance_core.<key_id>.<secret>
```

Clients that cannot set `Authorization` may send the same token as
`X-Npcink-Governance-Core-App-Token`.

The raw secret is returned only by `POST /apps`.

## `GET /contract`

Purpose: expose a stable admin-only runtime discovery surface for local host
and adapter compatibility checks.

Permission: `manage_options`.

Response `200`:

```json
{
  "schema_version": "npcink_governance_core_contract.v1",
  "core_contract_version": "1",
  "plugin_version": "0.1.0",
  "rest_namespace": "npcink-governance-core/v1",
  "governance_contract_version": "1",
  "rest_api_contract_version": "1",
  "proposal_lifecycle_version": "1",
  "approval_commit_contract_version": "1",
  "sensitive_read_authorization_version": "1",
  "app_auth_contract_version": "1",
  "runtime_contract_endpoint_version": "1",
  "compatibility": {
    "contract_family": "npcink_governance_core",
    "minimum_adapter_contract_version": "1",
    "metadata_only": true,
    "admin_authenticated": true,
    "proposal_truth_available": true,
    "approval_truth_available": true,
    "commit_preflight_available": true,
    "execution_result_record_available": true,
    "sensitive_read_preflight_available": true
  },
  "runtime_controls": {
    "core_proxy_execute": false,
    "commit_execution": false,
    "read_proxy_execute": false,
    "workflow_orchestration": false,
    "background_jobs": false,
    "batch_execution": false,
    "mcp_transport": false,
    "agent_catalog": false,
    "provider_secret_storage": false
  },
  "context_bindings": {
    "site_binding": {
      "fields": ["site_url", "home_url", "blog_id"],
      "emitted_in": [
        "approval_context",
        "execution_handoff",
        "read_authorization_context"
      ],
      "site_url": "https://example.test",
      "home_url": "https://example.test",
      "blog_id": 1,
      "fail_closed": true
    },
    "client_key_fingerprint": {
      "field": "client_key_fingerprint",
      "emitted": false,
      "status": "pending_signed_client_identity_contract",
      "owner": "npcink-governance-core"
    }
  },
  "handoff_routes": {
    "capabilities": "/wp-json/npcink-governance-core/v1/capabilities",
    "proposal_create": "/wp-json/npcink-governance-core/v1/proposals",
    "plan_to_proposal": "/wp-json/npcink-governance-core/v1/proposals/from-plan",
    "commit_preflight_template": "/wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight",
    "record_execution_template": "/wp-json/npcink-governance-core/v1/proposals/{proposal_id}/record-execution",
    "read_request_create": "/wp-json/npcink-governance-core/v1/read-requests",
    "read_preflight_template": "/wp-json/npcink-governance-core/v1/read-requests/{request_id}/read-preflight"
  },
  "boundary": {
    "proposal_truth_owner": "npcink-governance-core",
    "approval_truth_owner": "npcink-governance-core",
    "audit_truth_owner": "npcink-governance-core",
    "ability_definitions_owner": "wordpress_abilities_provider",
    "final_write_authority": "adapter_or_host_after_core_preflight",
    "workflow_execution_owner": "external_dedicated_runtime",
    "cloud_control_plane_owner": "not_npcink-governance-core"
  },
  "forbidden_payloads": {
    "proposal_rows": false,
    "audit_rows": false,
    "app_secret_material": false,
    "provider_secret_material": false,
    "ability_definitions": false,
    "runtime_state": false,
    "final_execution_results": false
  }
}
```

This endpoint is metadata-only. It must not return proposal rows, audit rows,
app keys, app secret material, provider credentials, ability definitions,
workflow runtime state, queues, MCP sessions, Agent Gateway catalogs, or final
write execution results.

Core-issued commit preflight and sensitive-read authorization contexts include
the current `site_url`, `home_url`, and `blog_id` so adapters can fail closed
when a handoff is replayed on the wrong WordPress site. Client-key fingerprint
binding remains pending until Core emits a signed `client_key_fingerprint`
field in those contexts; the contract advertises that state as
`pending_signed_client_identity_contract`.

## `GET /apps`

Purpose: list Core app identities without raw secrets or secret hashes.

Permission: `manage_options`.

Response `200`: app identity rows without secret material.

Audit event:

- `app.listed`

## `POST /apps`

Purpose: create one app key and return the raw secret once.

Permission: `manage_options`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `app_label` | string | no | Human-readable app label. |
| `caller_type` | string | no | `mcp_adapter`, `agent_host`, `product_plugin`, or another sanitized key. |
| `scopes` | array | no | Omit to use default adapter scopes: `capabilities:read`, `proposals:create`, `proposals:read`, `commit:preflight`, `read_requests:create`, `read_requests:read`, and `read_requests:preflight`. If provided, at least one valid scope is required. |
| `rate_limit` | integer | no | Defaults to `60`. |
| `rate_window_seconds` | integer | no | Defaults to `3600`. |
| `expires_at` | string | no | Optional future UTC expiry. Past or invalid values are ignored. |

Response `201`: app row plus `secret`, `token`, `token_prefix`, and
`shown_once=true`. App rows expose `expires_at`, `last_used_at`,
`last_used_ip_hash`, `revoked_at`, `revoked_reason`, and
`hash_algorithm_version`, but never raw secrets or secret hashes.

Audit event:

- `app.created`

## `GET /capabilities`

Purpose: list normalized abilities available to Core.

Permission: `manage_options` or app scope `capabilities:read`.

Response `200`:

```json
{
  "available": true,
  "source": "npcink_abilities_toolkit",
  "count": 1,
  "message": "Capabilities discovered through npcink-abilities-toolkit public API.",
  "items": [
    {
      "ability_id": "npcink-abilities-toolkit/site-info",
      "label": "Site Info",
      "description": "Returns site information.",
      "risk_level": "read",
      "requires_approval": false,
      "governance_mode": "direct_read",
      "execution_surface": "wp_abilities_rest",
      "core_proxy_execute": false,
      "commit_execution": false,
      "read_policy": "direct_read_public",
      "sensitivity": "public",
      "redaction_required": false,
      "read_authorization_required": false,
      "requires_read_authorization": false,
      "authorization_mode": "none",
      "read_authorization": { "required": false },
      "read_authorization_request_route": "",
      "read_authorization_preflight_route": "",
      "read_authorization_status_route": "",
      "read_audit_mode": "adapter_read_envelope",
      "input_schema": { "type": "object" },
      "output_schema": { "type": "object" },
      "source": "npcink_abilities_toolkit",
      "raw": {}
    }
  ]
}
```

Audit event:

- `capabilities.listed`

App audit attribution:

- `metadata.auth.app_id`
- `metadata.auth.key_id`
- `metadata.auth.scope=capabilities:read`
- `metadata.auth.scope_decision=allowed`

Capability execution guidance:

- `governance_mode=direct_read` means an adapter may call the canonical
  WordPress Abilities API execution surface for a read-only ability.
- `governance_mode=proposal_required` means an adapter must create a Core
  proposal before any write or destructive execution.
- `execution_surface=wp_abilities_rest` means execution belongs to WordPress
  Abilities API, not Core.
- `execution_surface=adapter_after_core_preflight` means execution belongs to
  the adapter or host only after Core approval and commit preflight.
- `read_policy` is `direct_read_public`, `direct_read_internal`,
  `direct_read_sensitive`, `core_read_authorization_required`, or
  `not_direct_read`.
- `sensitivity` is `public`, `internal`, or `sensitive`.
- `redaction_required=true` means the adapter must run the read result through
  its read redaction policy before returning it to OpenClaw or logging it.
- `read_audit_mode=adapter_read_envelope` means read execution is not a Core
  proposal event; the adapter must return/log a read envelope with correlation
  context.
- `read_authorization_required=true`,
  `requires_read_authorization=true`,
  `read_authorization.required=true`,
  `read_policy=core_read_authorization_required`, or
  `authorization_mode=core_read_request` means the adapter must create or fetch
  a Core sensitive read request and call read preflight before executing the
  read.
- `read_authorization_request_route`,
  `read_authorization_preflight_route`, and
  `read_authorization_status_route` are route guidance for Adapter handoff.
- `core_proxy_execute=false` is fixed in the current contract. Core does not
  provide `/execute` or `/proxy-execute`.
- `commit_execution=false` remains fixed until a separate final commit
  execution ADR is accepted.

## `GET /proposals`

Purpose: list recent proposal records.

Permission: `manage_options` or app scope `proposals:read`.

Before listing, Core expires stale `pending` proposals whose review TTL has
elapsed. Expired rows remain available as proposal records but no longer count
as active review work.

Query parameters:

| Name | Type | Default | Notes |
| --- | --- | --- | --- |
| `limit` | integer | `50` | Clamped by repository to `1..200`. |

Response `200`:

```json
{
  "items": [
    {
      "proposal_id": "uuid",
      "ability_id": "npcink-abilities-toolkit/create-draft",
      "status": "pending",
      "title": "Smoke proposal",
      "summary": "Reviewable operation summary.",
      "input": {},
      "preview": {},
      "caller": {},
      "policy_decision": "manual_required",
      "policy_profile": "manual",
      "policy_version": "core-approval-policy-v1",
      "policy_reasons": ["default_manual_required"],
      "created_by": 1,
      "created_at": "2026-05-29 00:00:00",
      "updated_at": "2026-05-29 00:00:00"
    }
  ]
}
```

Known proposal status values are `pending`, `approved`, `rejected`, `expired`,
`archived`, `executed`, and `execution_failed`.

Audit event:

- `proposal.listed`

## `GET /proposals/{proposal_id}`

Purpose: fetch one proposal record by id.

Permission: `manage_options` or app scope `proposals:read`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Response `200`: proposal row plus `audit_timeline`, ordered oldest to newest
for that proposal.

Fetching a proposal may also trigger stale pending expiration before the row is
returned.

Example shape:

```json
{
  "proposal_id": "uuid",
  "ability_id": "npcink-abilities-toolkit/create-draft",
  "status": "approved",
  "audit_timeline": [
    {
      "event_id": "uuid",
      "event_name": "proposal.created",
      "proposal_id": "uuid",
      "actor_id": 1,
      "metadata": {
        "ability_id": "npcink-abilities-toolkit/create-draft"
      },
      "created_at": "2026-05-29 00:00:00"
    }
  ]
}
```

The grant is read-only: `commit_execution=false` and `write_execution=false`
are required at the top level and inside `read_authorization_context`.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_proposal_not_found` | `404` | Proposal id does not exist. |

Audit event:

- `proposal.viewed`

## `POST /proposals`

Purpose: create a proposal. This route records reviewable intent only. It does
not execute the target ability.

Permission: `manage_options` or app scope `proposals:create`.

If the same caller already has a pending proposal with the same `ability_id`
and sanitized `input`, Core returns that proposal with HTTP `200` and
`deduplicated=true` instead of storing another row. If the caller's pending
proposal quota is full, Core returns
`npcink_governance_core_pending_proposal_quota_exceeded` with HTTP `429`.
For `npcink-abilities-toolkit/create-draft`, `input.content` may remain
WordPress safe post HTML only when the input explicitly sets
`content_format=html`; unsafe HTML is stripped before persistence. The same
rule applies to nested create-draft actions in plan-to-proposal batch input.
For `npcink-abilities-toolkit/update-post-blocks`, Core preserves the
case-sensitive Gutenberg block tree keys under `input.blocks`, including
`blockName`, `innerBlocks`, `innerHTML`, `innerContent`, and attrs camelCase
such as `contentSize`, `fontSize`, `letterSpacing`, and `textTransform`.
Block values are still sanitized recursively, and `innerHTML` / `innerContent`
strings are filtered as WordPress safe post HTML. The same rule applies to
nested update-post-blocks actions in plan-to-proposal batch input.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `ability_id` | string | yes | Must be a namespaced, currently discoverable ability id. Planning labels such as `content/draft-preview` are rejected because they are not runtime ability ids. |
| `title` | string | no | Human-readable title. |
| `summary` | string | no | Human-readable summary. |
| `input` | object | no | Target ability input or caller intent. |
| `preview` | object | no | Dry-run preview, diff, or provider handoff. |
| `caller` | object | no | Caller metadata. |

Response `201`: proposal row.

Proposal rows include policy fields:

| Name | Type | Notes |
| --- | --- | --- |
| `policy_decision` | string | Defaults to `manual_required`. `smart_guarded` may return `auto_approved` only for trusted test cleanup trash-post batches and single draft-only create-draft proposals. `dev_allow_all` may return `auto_approved` only in explicit local development mode. Reserved values are `manual_required`, `auto_approved`, and `blocked`. |
| `policy_profile` | string | Defaults to `manual`. `smart_guarded` and `dev_allow_all` candidate evaluation may return `guarded`; auto approval returns `trusted_local`. Reserved profiles are `manual`, `guarded`, `trusted_local`, and `break_glass`. |
| `policy_version` | string | Current value is `core-approval-policy-v1`. |
| `policy_reasons` | array | Stable, sanitized reason keys. |

The policy evaluator stores `caller.core_policy`, promotes the same fields into
proposal responses, and records `proposal.policy_evaluated`. `manual` remains
the default and does not auto-approve. `smart_guarded` may auto-approve only
trusted `build-nonproduction-content-cleanup-plan` `plan_to_proposal_batch` proposals
whose actions all target `npcink-abilities-toolkit/trash-post`, or a single
direct `npcink-abilities-toolkit/create-draft` proposal that creates only a
draft post with dry-run/non-commit input and no schedule/publish intent.
`dev_allow_all` is local-development only, requires
`NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL`, and still leaves commit
preflight mandatory. It does not add a rules DSL, workflow runtime,
long-running scheduler, final execution path, or policy configuration UI.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_invalid_ability_id` | `400` | Missing or invalid namespaced `ability_id`. |
| `npcink_governance_core_ability_not_available` | `404` | Target ability id is not currently discoverable. |
| `npcink_governance_core_proposal_insert_failed` | `500` | Proposal row could not be stored. |
| `npcink_governance_core_proposal_audit_failed` | `500` | Proposal creation could not be audited; Core deletes the created proposal before failing. |
| `npcink_governance_core_policy_decision_audit_failed` | `500` | Policy decision could not be audited; Core deletes the created proposal before failing. |
| `npcink_governance_core_auto_approval_audit_failed` | `500` | Auto approval could not be audited; Core does not leave the proposal approved. |
| `npcink_governance_core_auto_approval_quota_failed` | `500` | Auto approval quota could not be consumed; Core deletes the created proposal before failing. |

Audit event:

- `proposal.created`
- `proposal.policy_evaluated`
- `proposal.auto_approved` when a policy strategy changes status to approved

App audit attribution:

- stored in event `metadata.auth`;
- copied into proposal `caller.auth`.
- includes `scope_decision=allowed` for successful app-authenticated creates.

## Sensitive Read Requests

Sensitive read requests are Core-owned review records for read abilities that
require extra authorization. They are not write proposals and do not execute
reads.

### `POST /read-requests`

Purpose: create a pending sensitive read request bound to one real read
`ability_id` and approved input hash.

Permission: `manage_options` or app scope `read_requests:create`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `ability_id` | string | yes | Must be a discoverable read ability that requires Core read authorization. |
| `input` | object | no | Structured read input. Core computes `input_hash` when supplied. |
| `input_hash` | string | no | 64-character SHA-256 hash when the caller cannot send input. Ignored when non-empty `input` is supplied. |
| `requested_input_summary` | string | no | Human-readable summary; secrets are redacted. |
| `sensitivity` | string | no | `internal` or `sensitive`; defaults to capability sensitivity. |
| `data_classes` | array | yes | Data classes such as `logs`, `diagnostics`, or `private_content`; at least one is required for review. |
| `redaction_level` | string | no | `standard` or `strict`; defaults to `strict`. Sensitive read authorization cannot disable redaction. |
| `purpose` | string | yes | Review purpose. |
| `caller` | object | no | Caller metadata; app auth is copied into `caller.auth`. |
| `expires_at` | string | no | UTC expiry, clamped to Core max TTL. |
| `max_rows`, `tail_lines`, `allowed_fields`, `denied_fields`, `one_time` | mixed | no | Requested bounds, clamped to provider and Core caps. |
| `bounds` | object | no | Alternate container for the same bounds. |

Response `201`: read request row with `request_id`, `input_hash`, status,
expiry, bounds, correlation id, and sanitized metadata.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_invalid_read_request_ability_id` | `400` | Missing or invalid namespaced ability id. |
| `npcink_governance_core_read_ability_not_available` | `404` | Target read ability is not currently discoverable. |
| `npcink_governance_core_read_authorization_not_required` | `409` | Ability does not require Core read authorization. |
| `npcink_governance_core_read_request_input_hash_required` | `400` | Neither input nor input_hash was supplied. |
| `npcink_governance_core_read_request_insert_failed` | `500` | Read request row could not be stored. |
| `npcink_governance_core_read_request_audit_failed` | `500` | Creation could not be audited; Core deletes the row before failing. |

Audit event:

- `read_request.created`

### `GET /read-requests`

Purpose: list recent sensitive read request records.

Permission: `manage_options` or app scope `read_requests:read`.

Query parameters:

| Name | Type | Default |
| --- | --- | --- |
| `limit` | integer | `50` |
| `status` | string | empty |

Audit event:

- `read_request.listed`

### `GET /read-requests/{request_id}`

Purpose: fetch one read request with `audit_timeline`.

Permission: `manage_options` or app scope `read_requests:read`.

Audit event:

- `read_request.viewed`

### `POST /read-requests/{request_id}/approve`

Purpose: approve a pending read request and optionally tighten expiry,
redaction level, or bounds. Approval cannot widen provider-declared bounds.

Permission: `manage_options` or app scope `read_requests:approve`.

Audit event:

- `read_request.approved`

### `POST /read-requests/{request_id}/reject`

Purpose: reject a pending read request.

Permission: `manage_options` or app scope `read_requests:reject`.

Audit event:

- `read_request.rejected`

### `POST /read-requests/{request_id}/read-preflight`

Purpose: return bounded `read_authorization_context` for Adapter execution
checks. This route does not execute the read.

Permission: `manage_options` or app scope `read_requests:preflight`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `ability_id` | string | yes | Must match the approved request. |
| `input` | object | no | Structured input used to recompute hash. |
| `input_hash` | string | no | Approved hash when the caller cannot send input. |

Response `200`:

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
  },
  "commit_execution": false,
  "write_execution": false
}
```

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_read_request_not_found` | `404` | Request id does not exist. |
| `npcink_governance_core_read_request_not_approved` | `409` | Pending, rejected, expired, or consumed requests cannot grant. |
| `npcink_governance_core_read_request_expired` | `409` | Approved grant expired before use. |
| `npcink_governance_core_read_request_ability_mismatch` | `409` | Requested ability differs from approved ability. |
| `npcink_governance_core_read_request_input_mismatch` | `409` | Requested input hash differs from approved input hash. |
| `npcink_governance_core_read_preflight_audit_failed` | `500` | Grant could not be audited. |

Audit events:

- `read_request.preflighted`
- `read_request.preflight_failed`
- `read_request.expired` when expiry is detected
- `read_request.consumed` for one-time grants

## `POST /proposals/from-plan`

Purpose: convert a read-only planning ability output into one or more Core
proposal records. This route does not run the planning ability and does not
execute any target write ability. It only accepts the current plan bridge
abilities:

- `npcink-abilities-toolkit/build-content-inventory-fix-plan`
- `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`
- `npcink-abilities-toolkit/build-media-inventory-fix-plan`
- `npcink-abilities-toolkit/build-media-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-settings-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-optimization-plan`
- `npcink-abilities-toolkit/build-media-adoption-enhancement-plan`
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-abilities-toolkit/build-article-block-plan`
- `npcink-abilities-toolkit/build-pattern-page-plan`
- `npcink-abilities-toolkit/build-block-theme-site-plan`
- `npcink-toolbox/build-article-write-plan`
- `npcink-toolbox/build-article-batch-write-plan`
- `npcink-toolbox/build-article-media-batch-write-plan`
- `npcink-toolbox/build-image-candidate-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`
- `npcink-toolbox/build-content-metadata-apply-plan`

Permission: `manage_options` or app scope `proposals:create`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `plan_ability_id` | string | yes | Must be one of the supported read-only planning ability ids and currently discoverable as `governance_mode=direct_read`. |
| `plan` | object | yes | Ability success envelope or its `data` object. Must include `requires_approval=true`, `dry_run=true`, `commit_execution=false`, and `write_actions`. |
| `plan_input` | object | no | Input originally used to build the plan. Used for safety gates such as `include_delete_candidates=true`; media delete plans may also require source-side flags such as `include_unattached_nonproduction_media=true` or `include_trash_parent_media=true` before the plan emits a delete action. |
| `caller` | object | no | Caller metadata copied into generated proposals. |

Core rejects oversized plan intake before proposal creation. The plan payload
must stay within Core's plan intake byte limit and may not contain more than 25
`write_actions`. Specific batch review shapes can be narrower; media
optimization plans and block theme site plans are capped at 10 actions each.

For `npcink-toolbox/build-article-write-plan`, the plan must declare
`artifact_type=article_write_plan`, `version>=1`, and include
`article_goal_brief`, `research_evidence_pack`, `article_outline`,
`article_draft_candidate`, `discoverability_pack`, and `article_risk_report`.
The P0 action set must contain exactly one `npcink-abilities-toolkit/create-draft` action with
`status=draft` or no explicit status. `publish`, high-risk reports, blocked
claims, `commit=true`, or `dry_run=false` are rejected before proposal creation.
When that action sets `content_format=html`, Core stores the action
`input.content` as WordPress safe post HTML instead of flattening it to plain
text.

For `npcink-toolbox/build-article-batch-write-plan`, the plan must declare
`artifact_type=article_batch_write_plan`, `proposal_mode=batch`,
`batch_approval=true`, and include 2 to 5 draft-only
`npcink-abilities-toolkit/create-draft` actions plus one reviewed article artifact set per
action under `articles[]`. Publish, high-risk reports, blocked claims,
`commit=true`, or `dry_run=false` are rejected before proposal creation.

For `npcink-toolbox/build-article-media-batch-write-plan`, the plan must
declare `artifact_type=article_media_batch_write_plan`,
`proposal_mode=batch`, `batch_approval=true`, and include 1 to 5 reviewed
article artifact sets with `featured_image_candidate` evidence. The action set
must include draft creation, `npcink-abilities-toolkit/upload-media-from-url`, and
`npcink-abilities-toolkit/set-post-featured-image` actions for each article, with optional
`npcink-abilities-toolkit/update-media-details` or `npcink-abilities-toolkit/patch-post-content` actions.

For `npcink-toolbox/build-image-candidate-adoption-plan`, the plan must
declare `artifact_type=image_candidate_adoption_plan` and carry a normalized
`image_candidate.v1` candidate through `candidate_contract_version` or
`selected_image_candidate.contract_version`. The action set must include
exactly one `npcink-abilities-toolkit/upload-media-from-url` action, exactly one
`npcink-abilities-toolkit/update-media-details` action, and at most one optional
`npcink-abilities-toolkit/set-post-featured-image` action. Every action must be dry-run and
must not request commit execution.

For `npcink-toolbox/build-site-knowledge-review-plan`, the plan must declare
`artifact_type=site_knowledge_review_plan`, preserve non-empty
`evidence_refs`, and contain exactly one blocked
`npcink-abilities-toolkit/create-draft` review action. The action must remain
`proposal_ready=false`, require human `title` and `content` input, stay
`status=draft`, and keep `dry_run=true`, `commit=false`, and
`direct_wordpress_write=false`. This creates a Core review proposal only; it
does not generate article content, approve the proposal, pass commit preflight,
or execute WordPress writes.

For `npcink-toolbox/build-content-metadata-apply-plan`, the plan must declare
`artifact_type=content_metadata_apply_plan`, `proposal_mode=batch`,
`batch_approval=true`, target exactly one post, and keep
`direct_wordpress_write=false`. Core accepts only dry-run, non-commit
`npcink-abilities-toolkit/update-post` actions that update `excerpt`, and
`npcink-abilities-toolkit/set-post-terms` actions for `category` or `post_tag`
using existing `term_ids` with `create_missing=false`. The batch may contain at
most one excerpt action, one category action, and one post-tag action.
Title/content updates, duplicate metadata action slots, SEO writes, named
missing terms, unsupported taxonomies, remove-mode term changes, `commit=true`,
or `dry_run=false` are rejected before proposal creation. If the plan supplies
an operation classification envelope, the classification must be
`core_proposal_required`; Core rejects `local_admin_consent` plan submissions
because that path belongs to a present-admin product surface with local audit
instead of Core plan intake.

For `npcink-abilities-toolkit/build-media-optimization-plan`, the plan must declare
`artifact_type=media_optimization_plan`, `proposal_mode=batch`,
`batch_approval=true`, and include paired metadata and derivative actions for
each `attachment_id` in the plan. It must include `npcink-abilities-toolkit/update-media-details` and either
`npcink-abilities-toolkit/adopt-cloud-media-derivative` or
`npcink-abilities-toolkit/replace-media-file`. Post-content media reference repair belongs to
that derivative action's dry-run and commit contract; Core rejects media
optimization plans that split the repair into a separate
`npcink-abilities-toolkit/patch-post-content`, `npcink-abilities-toolkit/update-post`, or
`npcink-abilities-toolkit/update-post-blocks` write action.

For `npcink-abilities-toolkit/build-media-adoption-enhancement-plan`, the plan
must declare `artifact_type=media_adoption_enhancement_plan`,
`proposal_mode=batch`, `batch_approval=true`, `requires_approval=true`,
`dry_run=true`, `commit_execution=false`, and `direct_wordpress_write=false`.
It must contain exactly one dry-run, non-commit
`npcink-abilities-toolkit/upload-media-from-url` action, exactly one
`npcink-abilities-toolkit/optimize-media-asset` action, and at most one
`npcink-abilities-toolkit/patch-post-content` action. The patch action may only
replace a reviewed absolute old URL with
`$outputs.optimize-media-asset.derivative_url`. The route creates a pending
Core batch proposal with `preview.media_adoption_enhancement`; it does not
search for images, generate images, download media, optimize files, approve the
proposal, execute the write, or mutate WordPress content.

For `npcink-abilities-toolkit/build-media-rename-plan`, the plan must declare
`artifact_type=media_rename_plan`, target exactly one `attachment_id`, and
contain exactly one dry-run `npcink-abilities-toolkit/rename-media-file` action with a
reviewed `target_file_name`. The action may preserve expected current path,
MIME type, MD5, SHA256, conflict mode, and backup suffix guards for the host
executor.

For `npcink-abilities-toolkit/build-article-optimization-apply-plan`, the plan
must declare `artifact_type=article_optimization_apply_plan`, target exactly one
post through `post.post_id`, and contain a bounded set of reviewed post update
actions for that same post. Core accepts only dry-run, non-commit actions
targeting `npcink-abilities-toolkit/update-post`,
`npcink-abilities-toolkit/set-post-seo-meta`,
`npcink-abilities-toolkit/patch-post-content`, or
`npcink-abilities-toolkit/update-post-blocks`. The route creates pending Core
proposals only; it does not optimize the article, approve the proposal, execute
the write, or mutate WordPress content. Generated update-post-blocks proposals
preserve the case-sensitive Gutenberg block tree under action `input.blocks` so
Adapter-side execution can pass valid block objects to the WordPress Abilities
API after approval and commit preflight.

For `npcink-abilities-toolkit/build-pattern-page-plan`, the plan must declare
`artifact_type=pattern_page_plan`, `pattern_id=openai-style-landing`,
`style_preset=minimal-dark-light`, and `proposal_mode=batch`. It must contain
exactly two dry-run, non-commit actions: `npcink-abilities-toolkit/create-draft`
for a draft page, followed by `npcink-abilities-toolkit/update-post-blocks`
using `$outputs.create-pattern-page.post_id`. Core rejects missing block trees
and block `className` values outside the plan `allowed_classes` list. The route
creates a pending Core batch proposal with `preview.pattern_page`; it does not
render the pattern, approve the proposal, execute the write, or mutate
WordPress content.

For `npcink-abilities-toolkit/build-article-block-plan`, the plan must declare
`artifact_type=article_block_plan`, an allowlisted `article_template`,
`responsive_profile=article_standard`, and `proposal_mode=batch`. It must
contain exactly two dry-run, non-commit actions:
`npcink-abilities-toolkit/create-draft` for a draft post, followed by
`npcink-abilities-toolkit/update-post-blocks` using
`$outputs.create-article-draft.post_id`. Core rejects missing block trees,
custom block `className` values, and quality summaries that require custom CSS.
The route creates a pending Core batch proposal with `preview.article_block`;
it does not generate the article, render the blocks, approve the proposal,
execute the write, or mutate WordPress content.

For `npcink-abilities-toolkit/build-block-theme-site-plan`, the plan must
declare `artifact_type=block_theme_site_plan`, `intent=add_breadcrumbs` or
`intent=customize_template_layout`, `proposal_mode=batch`, an active theme
stylesheet, and `direct_wordpress_write=false`. It must contain one or more
dry-run, non-commit template block actions targeting only
`npcink-abilities-toolkit/update-template-blocks` or
`npcink-abilities-toolkit/upsert-template-blocks`, with `mode=replace` and a
reviewed Gutenberg block tree. Upserts must include the active theme and
template slug so file-backed theme templates become reviewed `wp_template` Site
Editor overrides. The route creates a pending Core batch proposal with
`preview.block_theme_site`; it does not edit theme files, navigation entities,
global styles, approve the proposal, execute the write, or mutate WordPress
content.
For `intent=customize_template_layout`, Core additionally requires a passing
`template_layout_contract` whose profile rows use accepted profiles such as
`article_standard`, `page_standard`, or `homepage_landing`. The contract must
also declare accepted compiler, policy, and profile versions, including
`block_theme_profile_compiler@0.2`, `block_theme_safe_core_blocks@0.2`, and
profile ids such as `article_standard@0.4` and `homepage_landing@0.2`.
Core accepts only bounded template slugs (`front-page`, `home`, `index`,
`page`, and `single`), requires parser roundtrip validation evidence, and
allows homepage layout reader modules such as `core/latest-posts` and
`core/categories` while still rejecting navigation blocks, custom HTML/freeform
blocks, shortcode blocks, embed blocks, unknown blocks, scriptable or embedded
raw HTML, oversized block attributes, excessive block count, and excessive
block depth.

Each accepted independent `write_action` becomes a separate pending proposal by
default. If the plan declares `batch_approval=true` or
`proposal_mode=batch`, or if the plan uses `depends_on` or
`$outputs.<prior_action_id>.<field>` references, Core keeps the actions
together as one ordered batch proposal. That batch proposal stores
`input.write_actions[]` and uses the first target ability as its proposal
`ability_id` only for Core availability and preflight checks; this first
ability id is not a safety endorsement for every batch action. Core preserves
each action's `depends_on` metadata for review and audit, while final
per-action allowlist and schema checks remain in the Adapter execution path.
Final execution still happens outside Core.

Generated proposals preserve:

- target ability input with `dry_run=true` and `commit=false`;
- `preview.before`;
- `preview.after_suggestion`;
- `reason`;
- `risk`;
- `required_scopes`;
- `requires_approval=true`;
- `proposal_ready`;
- `manual_review`;
- `skipped_destructive_candidates`.

Response `201`:

```json
{
  "plan_ability_id": "npcink-abilities-toolkit/build-content-inventory-fix-plan",
  "batch_id": "content_inventory_fix_...",
  "issue_types": ["seo_title"],
  "requires_approval": true,
  "dry_run": true,
  "commit_execution": false,
  "action_count": 1,
  "proposal_count": 1,
  "proposal_ready_count": 1,
  "proposals": [
    {
      "proposal_id": "uuid",
      "ability_id": "npcink-abilities-toolkit/set-post-seo-meta",
      "status": "pending",
      "policy_decision": "manual_required",
      "policy_profile": "manual",
      "policy_version": "core-approval-policy-v1",
      "policy_reasons": ["default_manual_required"],
      "input": {
        "post_id": 123,
        "seo_title": "Suggested title",
        "dry_run": true,
        "commit": false
      },
      "preview": {
        "target_ability_id": "npcink-abilities-toolkit/set-post-seo-meta",
        "before": {},
        "after_suggestion": {},
        "risk": { "level": "medium" },
        "requires_approval": true,
        "proposal_ready": true,
        "dry_run": true,
        "commit": false,
        "commit_execution": false
      }
    }
  ],
  "warnings": {},
  "blocked_items": [],
  "needs_input": []
}
```

Destructive media deletion is excluded unless `include_delete_candidates=true`
is present in the plan input supplied to this route. The media planning ability
must also have accepted its own narrow destructive flag, such as
`include_unattached_nonproduction_media=true` or `include_trash_parent_media=true`, before
Core has a delete action to review. Actions with `requires_input` still become
reviewable proposals, but their preview carries `proposal_ready=false`,
`needs_input`, and `preflight_blockers`; commit preflight must return `409`
until the missing input is resolved by the host.

When the plan creates one `plan_to_proposal_batch` proposal, the proposal
preview includes `batch_review_summary`. The summary standardizes
`action_count`, `blocked_count`, `needs_input_count`, `target_ability_ids`,
`operator_next_action`, `retryable`, `final_execution_owner`, and
`commit_execution=false` for operator review and Adapter recovery guidance. It
does not create a Core queue, scheduler, retry lease, or execution runtime.
Commit preflight returns only the bounded summary fields; unknown queue-like or
secret-shaped preview fields are not part of the preflight contract.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_plan_ability_not_allowed` | `400` | Unsupported planning ability id. |
| `npcink_governance_core_plan_ability_unavailable` | `404` | Planning ability is not discoverable. |
| `npcink_governance_core_plan_ability_not_read_only` | `409` | Planning ability is not a direct-read ability. |
| `npcink_governance_core_plan_requires_approval_missing` | `422` | Plan does not require approval. |
| `npcink_governance_core_plan_commit_execution_rejected` | `422` | Plan requested commit execution. |
| `npcink_governance_core_plan_dry_run_required` | `422` | Plan is not dry-run. |
| `npcink_governance_core_plan_write_actions_missing` | `422` | Plan has no `write_actions` array. |
| `npcink_governance_core_plan_payload_too_large` | `413` | Plan payload exceeds Core's intake byte limit. |
| `npcink_governance_core_plan_too_many_actions` | `422` | Plan contains too many `write_actions` for one Core intake request. |
| `npcink_governance_core_media_optimization_actions_rejected` | `422` | Media optimization plan exceeds its bounded metadata/derivative action cap. |
| `npcink_governance_core_block_theme_site_actions_rejected` | `422` | Block theme site plan exceeds its bounded template action cap. |

Audit event:

- `proposal.plan_ingested`

## `POST /proposals/{proposal_id}/approve`

Purpose: mark a pending proposal as approved. This route does not execute the
target ability.

Permission: `manage_options` or app scope `proposals:approve`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `note` | string | no | Human-readable approval note. |

Response `200`: updated proposal row with `status=approved`.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_proposal_not_found` | `404` | Proposal id does not exist. |
| `npcink_governance_core_proposal_expired` | `409` | Proposal expired before a decision was made. |
| `npcink_governance_core_proposal_already_decided` | `409` | Proposal is not pending. |
| `npcink_governance_core_proposal_transition_failed` | `500` | Status update failed. |
| `npcink_governance_core_proposal_decision_audit_failed` | `500` | Approval could not be audited; Core rolls the proposal back to its previous status before failing. |

Audit event:

- `proposal.approved`
- `proposal.expired` when a stale pending proposal expires before approval

App audit attribution:

- `metadata.auth.scope=proposals:approve`
- `metadata.auth.scope_decision=allowed`
- `metadata.auth.caller_type` should identify trusted Adapter approval when
  approval is proxied through productized Adapter UI.

## `POST /proposals/{proposal_id}/reject`

Purpose: mark a pending proposal as rejected.

Permission: `manage_options` or app scope `proposals:reject`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `note` | string | no | Human-readable rejection note. |

Response `200`: updated proposal row with `status=rejected`.

Errors: same as approve route.

Audit event:

- `proposal.rejected`
- `proposal.expired` when a stale pending proposal expires before rejection

## `GET /audit`

Purpose: list recent audit events.

Permission: `manage_options` or app scope `audit:read`.

Query parameters:

| Name | Type | Default | Notes |
| --- | --- | --- | --- |
| `limit` | integer | `50` | Clamped by repository to `1..200`. |
| `proposal_id` | string | empty | Optional proposal id filter. |
| `event_name` | string | empty | Optional dotted event name filter. |
| `ability_id` | string | empty | Optional metadata filter for target ability id. |
| `app_id` | string | empty | Optional metadata filter for app-authenticated events. |
| `key_id` | string | empty | Optional metadata filter for the app key id. |
| `caller_type` | string | empty | Optional metadata filter such as `mcp_adapter`. |
| `correlation_id` | string | empty | Optional metadata filter for commit-preflight correlation. |

The common metadata filters above are backed by indexed audit columns copied
from sanitized event metadata at write time. The response still returns the
sanitized `metadata` object.

Response `200`:

```json
{
  "items": [
    {
      "event_id": "uuid",
      "event_name": "proposal.created",
      "proposal_id": "uuid",
      "actor_id": 1,
      "metadata": {},
      "created_at": "2026-05-29 00:00:00"
    }
  ]
}
```

Audit event:

- `audit.listed`

## `POST /proposals/{proposal_id}/commit-preflight`

Purpose: verify that a proposal is ready for a future commit without executing
the target ability.

Permission: `manage_options` or app scope `commit:preflight`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Response `200`:

```json
{
  "proposal": {},
  "capability": {},
  "contract_preflight": {
    "contract_matches": true,
    "approved_contract_hash": "sha256...",
    "current_contract_hash": "sha256..."
  },
  "permission_preflight": {
    "allowed": true,
    "capability": "delete_posts",
    "source": "current_user_can"
  },
  "proposal_item_preflight": {
    "executable": true,
    "proposal_ready": true,
    "needs_input": [],
    "blocked_items": [],
    "warnings": [],
    "batch_review_summary": {
      "summary_version": "core-batch-review-summary-v1",
      "action_count": 2,
      "blocked_count": 0,
      "operator_next_action": "review_and_approve_or_reject",
      "final_execution_owner": "adapter_after_core_preflight",
      "core_execution": false,
      "commit_execution": false
    },
    "commit_execution": false
  },
  "approval_context": {
    "approval_commit_authorized": true,
    "confirmation_state": "approved_commit",
    "proposal_id": "uuid",
    "ability_id": "npcink-abilities-toolkit/trash-post",
    "correlation_id": "uuid",
    "approved_input_hash": "sha256...",
    "approved_preview_hash": "sha256...",
    "approval_updated_at": "2026-05-29 00:00:00",
    "policy_version": "core-preflight-v1"
  },
  "execution_handoff": {
    "executor": "adapter_after_core_preflight",
    "execution_surface": "wp_abilities_rest",
    "ability_id": "npcink-abilities-toolkit/trash-post",
    "proposal_id": "uuid",
    "correlation_id": "uuid",
    "approved_input_hash": "sha256...",
    "policy_version": "core-preflight-v1",
    "core_proxy_execute": false,
    "commit_execution": false
  },
  "correlation_id": "uuid",
  "commit_execution": false,
  "idempotency_required": true
}
```

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_legacy_confirmation_rejected` | `400` | Request attempted to use `confirm_token` or `write_confirmed`. |
| `npcink_governance_core_proposal_not_found` | `404` | Proposal id does not exist. |
| `npcink_governance_core_proposal_not_approved` | `409` | Proposal is not approved. |
| `npcink_governance_core_proposal_items_blocked` | `409` | Proposal preview has `proposal_ready=false`, `needs_input`, or `preflight_blockers`. |
| `npcink_governance_core_ability_unavailable` | `409` | Target ability is no longer discoverable. |
| `npcink_governance_core_ability_contract_changed` | `409` | Target ability risk, approval, schema, scope, execution guidance, or WordPress capability changed after proposal creation. |
| `npcink_governance_core_commit_preflight_already_issued` | `409` | Core already issued one execution handoff for this approved proposal input. |
| `npcink_governance_core_preflight_forbidden` | `403` | Current user lacks permission. |
| `npcink_governance_core_ability_permission_denied` | `403` | Current WordPress user lacks the target ability's declared WordPress capability. |
| `npcink_governance_core_preflight_audit_failed` | `500` | Preflight could not be audited. |

Audit event:

- `commit.preflighted`
- `commit.preflight_failed` for proposal-bound preflight failures

Local observability event:

- `core.commit.preflight`

Successful preflight emits `status=ok` with `proposal_id`, `ability_id`,
`correlation_id`, `latency_ms`, and an empty `error_code`. Expected governance
blocks such as `npcink_governance_core_proposal_not_approved`,
`npcink_governance_core_proposal_items_blocked`, and
`npcink_governance_core_commit_preflight_already_issued` emit `status=warning` with the
stable `error_code`. Other preflight failures emit `status=error`.
Observability events are metadata-only and must not include proposal input,
preview, caller payloads, approval notes, generated content, or policy payloads.

App audit attribution:

- `metadata.auth.scope=commit:preflight`
- `metadata.auth.scope_decision=allowed`

Preflight audit correlation:

- response `correlation_id`;
- `approval_context.correlation_id`;
- `approval_context.ability_id`;
- `approval_context.approved_input_hash`;
- `approval_context.policy_version`;
- `commit.preflighted` event `metadata.correlation_id`;
- `commit.preflighted` event `metadata.ability_contract_hash`.

Execution handoff:

- `execution_handoff.executor=adapter_after_core_preflight`;
- `execution_handoff.execution_surface=wp_abilities_rest`;
- `execution_handoff.core_proxy_execute=false`;
- `execution_handoff.commit_execution=false`.

The handoff object is routing guidance for Adapter. It is not an execution
token and does not make Core execute the target ability. Core issues at most one
successful handoff per approved proposal input; replay attempts fail with
`npcink_governance_core_commit_preflight_already_issued`.

## `POST /proposals/{proposal_id}/record-execution`

Purpose: record the Adapter-owned execution outcome after Core approval and
commit preflight. This route updates proposal lifecycle status and audit only;
it does not execute the target ability and does not store full ability result
payloads.

Permission: `manage_options` or app scope `commit:record_execution`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `execution_status` | string | yes | `succeeded` or `failed`. |
| `correlation_id` | string | yes | Must match a `commit.preflighted` event for the proposal. |
| `approved_input_hash` | string | yes | Must match the approved input hash from preflight. |
| `adapter_request_id` | string | no | Adapter request correlation id. |
| `execution_mode` | string | no | `single_post`, `batch_write_actions`, or another adapter-safe key. |
| `executed_count` | integer | no | Number of successful Adapter actions. |
| `failed_count` | integer | no | Number of failed Adapter actions. |
| `error_code` | string | no | Public-safe failure code when `execution_status=failed`. |

Response `200`: proposal row with `status=executed` or
`status=execution_failed`.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `npcink_governance_core_proposal_not_found` | `404` | Proposal id does not exist. |
| `npcink_governance_core_execution_record_not_allowed` | `409` | Proposal is not approved or already in an incompatible state. |
| `npcink_governance_core_invalid_execution_status` | `400` | Execution status is not supported. |
| `npcink_governance_core_execution_record_binding_required` | `400` | Required preflight binding fields are missing. |
| `npcink_governance_core_execution_record_preflight_missing` | `409` | No matching Core preflight handoff exists for the supplied binding. |
| `npcink_governance_core_execution_record_audit_failed` | `500` | Execution outcome could not be audited; status is rolled back. |

Audit events:

- `proposal.executed`
- `proposal.execution_failed`

Local observability event:

- `core.proposal.record_execution`

Execution result recording keeps `core_proxy_execute=false` and
`commit_execution=false`. Core remains the proposal, approval, preflight, and
audit truth; Adapter remains the final WordPress ability executor.

## Planned Routes

These are not implemented yet:

- final commit route

Commit execution must not be added until idempotency and failure contracts are
documented and tested.
