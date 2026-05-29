# REST API Contract

Status: active for MVP.

All MVP routes use the namespace `magick-ai-core/v1` and require the current
user to have `manage_options`. Future app-key or scoped access must be added as
a new contract update before implementation.

## Common Rules

- Request and response bodies are JSON.
- Route permissions fail closed.
- All write-like routes record audit events.
- Routes must not accept legacy `confirm_token` or `write_confirmed`
  parameters.
- Routes must not execute final WordPress writes until the approval-commit
  preflight and commit contracts are implemented.

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
      "ability_id": "magick-ai/site-info",
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

## `POST /proposals`

Purpose: create a proposal. This route records reviewable intent only. It does
not execute the target ability.

Permission: `manage_options`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `ability_id` | string | yes | Must be a namespaced ability id containing `/`. |
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

## Planned Routes

These are not implemented yet:

- `GET /proposals/{proposal_id}`
- `POST /proposals/{proposal_id}/commit-preflight`
- final commit route

Commit execution must not be added until preflight, idempotency, and failure
contracts are documented and tested.

