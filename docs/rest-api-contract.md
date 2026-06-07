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
| `GET /capabilities` | `capabilities:read` |
| `POST /proposals`, `POST /proposals/from-plan` | `proposals:create` |
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
  `direct_read_sensitive`, or `not_direct_read`.
- `sensitivity` is `public`, `internal`, or `sensitive`.
- `redaction_required=true` means the adapter must run the read result through
  its read redaction policy before returning it to OpenClaw or logging it.
- `read_audit_mode=adapter_read_envelope` means read execution is not a Core
  proposal event; the adapter must return/log a read envelope with correlation
  context.
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
and `archived`.

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
| `policy_decision` | string | Defaults to `manual_required`. `local_guarded` may return `auto_approved` only for trusted test cleanup trash-post batches. Reserved values are `manual_required`, `auto_approved`, and `blocked`. |
| `policy_profile` | string | Defaults to `manual`. `dry_run_guarded` may return `guarded`; `local_guarded` auto approval returns `trusted_local`. Reserved profiles are `manual`, `guarded`, `trusted_local`, and `break_glass`. |
| `policy_version` | string | Current value is `core-approval-policy-v1`. |
| `policy_reasons` | array | Stable, sanitized reason keys. |

The policy evaluator stores `caller.core_policy`, promotes the same fields into
proposal responses, and records `proposal.policy_evaluated`. `manual` remains
the default and does not auto-approve. `local_guarded` may auto-approve only
trusted `build-nonproduction-content-cleanup-plan` `plan_to_proposal_batch` proposals
whose actions all target `npcink-abilities-toolkit/trash-post`. It does not add a rules DSL,
workflow runtime, long-running scheduler, final execution path, or policy
configuration UI.

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
- `proposal.auto_approved` when `local_guarded` changes status to approved

App audit attribution:

- stored in event `metadata.auth`;
- copied into proposal `caller.auth`.
- includes `scope_decision=allowed` for successful app-authenticated creates.

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
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-toolbox/build-article-write-plan`
- `npcink-toolbox/build-article-batch-write-plan`
- `npcink-toolbox/build-article-media-batch-write-plan`
- `npcink-toolbox/build-image-candidate-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`

Permission: `manage_options` or app scope `proposals:create`.

Request fields:

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `plan_ability_id` | string | yes | Must be one of the supported read-only planning ability ids and currently discoverable as `governance_mode=direct_read`. |
| `plan` | object | yes | Ability success envelope or its `data` object. Must include `requires_approval=true`, `dry_run=true`, `commit_execution=false`, and `write_actions`. |
| `plan_input` | object | no | Input originally used to build the plan. Used for safety gates such as `include_delete_candidates=true`; media delete plans may also require source-side flags such as `include_unattached_nonproduction_media=true` or `include_trash_parent_media=true` before the plan emits a delete action. |
| `caller` | object | no | Caller metadata copied into generated proposals. |

For `npcink-toolbox/build-article-write-plan`, the plan must declare
`artifact_type=article_write_plan`, `version>=1`, and include
`article_goal_brief`, `research_evidence_pack`, `article_outline`,
`article_draft_candidate`, `discoverability_pack`, and `article_risk_report`.
The P0 action set must contain exactly one `npcink-abilities-toolkit/create-draft` action with
`status=draft` or no explicit status. `publish`, high-risk reports, blocked
claims, `commit=true`, or `dry_run=false` are rejected before proposal creation.

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

For `npcink-abilities-toolkit/build-media-optimization-plan`, the plan must declare
`artifact_type=media_optimization_plan`, `proposal_mode=batch`,
`batch_approval=true`, and target exactly one `attachment_id` across all
actions. It must include `npcink-abilities-toolkit/update-media-details` and either
`npcink-abilities-toolkit/adopt-cloud-media-derivative` or
`npcink-abilities-toolkit/replace-media-file`. Post-content media reference repair belongs to
that derivative action's dry-run and commit contract; Core rejects media
optimization plans that split the repair into a separate
`npcink-abilities-toolkit/patch-post-content`, `npcink-abilities-toolkit/update-post`, or
`npcink-abilities-toolkit/update-post-blocks` write action.

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
the write, or mutate WordPress content.

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

## Planned Routes

These are not implemented yet:

- final commit route

Commit execution must not be added until idempotency and failure contracts are
documented and tested.
