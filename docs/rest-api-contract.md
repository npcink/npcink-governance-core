# REST API Contract

Status: active for MVP.

All MVP routes use the namespace `magick-ai-core/v1` and require the current
user to have `manage_options`. Future app-key or scoped access must be added as
a new contract update before implementation.

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

## Future App-Authenticated Access

Not implemented.

Future app-authenticated access must be additive to the current REST surface.
The initial scope map is:

| Route family | Required future scope |
| --- | --- |
| `GET /capabilities` | `capabilities:read` |
| `POST /proposals` | `proposals:create` |
| `GET /proposals`, `GET /proposals/{proposal_id}` | `proposals:read` |
| `POST /proposals/{proposal_id}/approve` | `proposals:approve` |
| `POST /proposals/{proposal_id}/reject` | `proposals:reject` |
| `POST /proposals/{proposal_id}/commit-preflight` | `commit:preflight` |
| `GET /audit` | `audit:read` |

Generic MCP adapters should not receive `proposals:approve` or `audit:read` by
default. Missing or revoked app identity must return `401`; missing scope must
return `403`; rate-limited requests must return `429`.

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

## Planned Routes

These are not implemented yet:

- final commit route

Commit execution must not be added until idempotency and failure contracts are
documented and tested.
