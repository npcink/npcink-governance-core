# Approval Commit Contract

Status: approval status and commit preflight implemented; commit execution
planned after MVP.

The approval-commit path is the core reason this plugin exists. The MVP can
approve or reject proposals, but it does not execute commits yet; this document
freezes the direction before commit runtime is added.

## Commit Preconditions

A final write or destructive operation may execute only when all are true:

- the proposal exists;
- the proposal is approved;
- the target ability is still available;
- the current user or caller has permission;
- the request includes Core-generated approval context;
- idempotency checks pass;
- audit logging is available.

## MVP Approval Status And Preflight

The current implementation supports:

- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/reject`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`

Approval and rejection routes update proposal status and write audit events.
Commit preflight verifies that an approved proposal can produce Core-generated
approval context. None of these routes execute the target ability.

Preflight must:

- fail unless the proposal exists and is approved;
- fail when the target ability is no longer discoverable;
- fail when the proposal preview marks the item as not ready, lists
  `needs_input`, or carries `preflight_blockers`;
- fail when the request includes legacy confirmation parameters;
- return Core-generated approval context;
- return `proposal_item_preflight` describing executable, blocked, warning, and
  needs-input state;
- return a `correlation_id` in the response and
  `approval_context.correlation_id`;
- return `commit_execution=false`;
- record `commit.preflighted` on success.

## Approved Context

Future commit calls should use new approval-commit semantics:

```php
array(
	'approval_commit_authorized' => true,
	'confirmation_state'        => 'approved_commit',
	'proposal_id'               => '<core proposal id>',
	'correlation_id'            => '<preflight correlation id>',
)
```

The `correlation_id` must also be stored in the `commit.preflighted` audit
event. It connects the returned approval context to the audit trail; it is not
a final execution token.

Do not accept legacy `confirm_token`, `write_confirmed`, or compatibility
confirmation parameters in the rebuilt Core.

## Fail-Closed Rules

Commit must fail when:

- the proposal is missing or not approved;
- the ability is not known;
- required permission is missing;
- approval context is absent, stale, or mismatched;
- the provider tries to bypass dry-run/proposal semantics;
- a plan-generated proposal still has unresolved `needs_input` or
  `preflight_blockers`;
- audit persistence fails.

## Plan-Generated Proposals

Plan-to-proposal intake can create reviewable proposals that are not yet
committable. For example, a missing title action may target
`magick-ai/update-post` but require a human-provided `title`. Core stores that
proposal as pending review, but commit preflight returns
`magick_ai_core_proposal_items_blocked` until the host creates a later complete
proposal with the missing input resolved.
