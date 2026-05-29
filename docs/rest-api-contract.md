# REST API Contract

Status: active for MVP.

All MVP routes use the namespace `magick-ai-core/v1`. Routes accept either a
WordPress administrator with `manage_options` or a scoped Magick AI Core app key
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

| Route family | Required future scope |
| --- | --- |
| `GET /capabilities` | `capabilities:read` |
| `POST /proposals` | `proposals:create` |
| `GET /proposals`, `GET /proposals/{proposal_id}` | `proposals:read` |
| `POST /proposals/{proposal_id}/approve` | `proposals:approve` |
| `POST /proposals/{proposal_id}/reject` | `proposals:reject` |
| `POST /proposals/{proposal_id}/commit-preflight` | `commit:preflight` |
| `GET /audit` | `audit:read` |
| `GET /apps`, `POST /apps` | admin-only `manage_options` |

Generic MCP adapters should not receive `proposals:approve` or `audit:read` by
default. Missing or revoked app identity must return `401`; missing scope must
return `403`; rate-limited requests must return `429`.

App auth error codes:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `magick_ai_core_app_auth_missing` | `401` | No WordPress admin session and no app token. |
| `magick_ai_core_app_auth_malformed` | `400` | Bearer app token does not match the Core token shape. |
| `magick_ai_core_app_auth_invalid` | `401` | App token is unknown, inactive, or has an invalid secret. |
| `magick_ai_core_app_scope_forbidden` | `403` | App key does not include the route's required scope. |
| `magick_ai_core_app_rate_limited` | `429` | App key exceeded its fixed-window route-family limit. |

App tokens use:

```text
Authorization: Bearer mai_core.<key_id>.<secret>
```

The raw secret is returned only by `POST /apps`.

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
| `scopes` | array | no | Defaults to `capabilities:read`, `proposals:create`, `proposals:read`, and `commit:preflight`. |
| `rate_limit` | integer | no | Defaults to `60`. |
| `rate_window_seconds` | integer | no | Defaults to `3600`. |

Response `201`: app row plus `secret`, `token`, and `shown_once=true`.

Audit event:

- `app.created`

## `GET /capabilities`

Purpose: list normalized abilities available to Core.

Permission: `manage_options`.

Response `200`:

```json
{
  "available": true,
  "source": "magick_ai_abilities",
  "count": 1,
  "message": "Capabilities discovered through magick-ai-abilities public API.",
  "items": [
    {
      "ability_id": "magick-ai/site-info",
      "label": "Site Info",
      "description": "Returns site information.",
      "risk_level": "read",
      "requires_approval": false,
      "input_schema": { "type": "object" },
      "output_schema": { "type": "object" },
      "source": "magick_ai_abilities",
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

## `GET /proposals`

Purpose: list recent proposal records.

Permission: `manage_options`.

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
      "ability_id": "magick-ai/create-draft",
      "status": "pending",
      "title": "Smoke proposal",
      "summary": "Reviewable operation summary.",
      "input": {},
      "preview": {},
      "caller": {},
      "created_by": 1,
      "created_at": "2026-05-29 00:00:00",
      "updated_at": "2026-05-29 00:00:00"
    }
  ]
}
```

Audit event:

- `proposal.listed`

## `GET /proposals/{proposal_id}`

Purpose: fetch one proposal record by id.

Permission: `manage_options`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Response `200`: proposal row.

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `magick_ai_core_proposal_not_found` | `404` | Proposal id does not exist. |

Audit event:

- `proposal.viewed`

## `POST /proposals`

Purpose: create a proposal. This route records reviewable intent only. It does
not execute the target ability.

Permission: `manage_options`.

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

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `magick_ai_core_invalid_ability_id` | `400` | Missing or invalid namespaced `ability_id`. |
| `magick_ai_core_ability_not_available` | `404` | Target ability id is not currently discoverable. |

Audit event:

- `proposal.created`

App audit attribution:

- stored in event `metadata.auth`;
- copied into proposal `caller.auth`.

## `POST /proposals/{proposal_id}/approve`

Purpose: mark a pending proposal as approved. This route does not execute the
target ability.

Permission: `manage_options`.

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
| `magick_ai_core_proposal_not_found` | `404` | Proposal id does not exist. |
| `magick_ai_core_proposal_already_decided` | `409` | Proposal is not pending. |
| `magick_ai_core_proposal_transition_failed` | `500` | Status update failed. |

Audit event:

- `proposal.approved`

## `POST /proposals/{proposal_id}/reject`

Purpose: mark a pending proposal as rejected.

Permission: `manage_options`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `note` | string | no | Human-readable rejection note. |

Response `200`: updated proposal row with `status=rejected`.

Errors: same as approve route.

Audit event:

- `proposal.rejected`

## `GET /audit`

Purpose: list recent audit events.

Permission: `manage_options`.

Query parameters:

| Name | Type | Default | Notes |
| --- | --- | --- | --- |
| `limit` | integer | `50` | Clamped by repository to `1..200`. |
| `proposal_id` | string | empty | Optional proposal id filter. |
| `event_name` | string | empty | Optional dotted event name filter. |

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

Permission: `manage_options`.

Path parameters:

| Name | Type | Required |
| --- | --- | --- |
| `proposal_id` | string | yes |

Response `200`:

```json
{
  "proposal": {},
  "capability": {},
  "approval_context": {
    "approval_commit_authorized": true,
    "confirmation_state": "approved_commit",
    "proposal_id": "uuid"
  },
  "commit_execution": false,
  "idempotency_required": true
}
```

Errors:

| Code | HTTP | Meaning |
| --- | --- | --- |
| `magick_ai_core_legacy_confirmation_rejected` | `400` | Request attempted to use `confirm_token` or `write_confirmed`. |
| `magick_ai_core_proposal_not_found` | `404` | Proposal id does not exist. |
| `magick_ai_core_proposal_not_approved` | `409` | Proposal is not approved. |
| `magick_ai_core_ability_unavailable` | `409` | Target ability is no longer discoverable. |
| `magick_ai_core_preflight_forbidden` | `403` | Current user lacks permission. |
| `magick_ai_core_preflight_audit_failed` | `500` | Preflight could not be audited. |

Audit event:

- `commit.preflighted`

App audit attribution:

- `metadata.auth.scope=commit:preflight`

## Planned Routes

These are not implemented yet:

- final commit route

Commit execution must not be added until idempotency and failure contracts are
documented and tested.
