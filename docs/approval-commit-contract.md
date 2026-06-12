# Approval Commit Contract

Status: approval status, commit preflight, and Adapter execution-result
recording implemented; commit execution stays outside Core.

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

- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/reject`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/record-execution`

Approval and rejection routes update proposal status and write audit events.
Commit preflight verifies that an approved proposal can produce Core-generated
approval context. Execution-result recording lets Adapter move an approved
proposal to `executed` or `execution_failed` after a matching preflight handoff.
None of these routes execute the target ability.

Preflight must:

- fail unless the proposal exists and is approved;
- fail when the target ability is no longer discoverable;
- fail when the target ability's governance-relevant contract has changed since
  proposal creation;
- fail when the current WordPress user no longer has the target ability's
  declared WordPress capability;
- fail when the proposal preview marks the item as not ready, lists
  `needs_input`, or carries `preflight_blockers`;
- fail when a successful execution handoff has already been issued for the same
  approved proposal input;
- fail when the request includes legacy confirmation parameters;
- return Core-generated approval context;
- return `contract_preflight` and `permission_preflight` on success;
- return `proposal_item_preflight` describing executable, blocked, warning, and
  needs-input state;
- return `execution_handoff` so Adapter can route final execution without
  treating Core as the executor;
- return a `correlation_id` in the response and
  `approval_context.correlation_id`;
- bind approval context to the real `ability_id`, approved input hash, approved
  preview hash, approval update timestamp, and policy version;
- return `commit_execution=false`;
- record `commit.preflighted` on success;
- record `commit.preflight_failed` for proposal-bound preflight failures.

## Approved Context

Future commit calls should use new approval-commit semantics:

```php
array(
	'approval_commit_authorized' => true,
	'confirmation_state'        => 'approved_commit',
	'proposal_id'               => '<core proposal id>',
	'ability_id'                 => '<target ability id>',
	'correlation_id'            => '<preflight correlation id>',
	'approved_input_hash'        => '<sha256>',
	'approved_preview_hash'      => '<sha256>',
	'approval_updated_at'        => '<utc timestamp>',
	'policy_version'             => 'core-preflight-v1',
)
```

The `correlation_id` must also be stored in the `commit.preflighted` audit
event. It connects the returned approval context to the audit trail; it is not
a final execution token.

## Execution Handoff

Commit preflight returns an execution handoff object for Adapter:

```php
array(
	'executor'            => 'adapter_after_core_preflight',
	'execution_surface'   => 'wp_abilities_rest',
	'ability_id'          => '<target ability id>',
	'proposal_id'         => '<core proposal id>',
	'correlation_id'      => '<preflight correlation id>',
	'approved_input_hash' => '<sha256>',
	'policy_version'      => 'core-preflight-v1',
	'core_proxy_execute'  => false,
	'commit_execution'    => false,
)
```

The handoff object is not a second approval and not an execution token. Core
issues one handoff per approved proposal input. Adapter must treat duplicate
preflight rejection as a signal to use the original audited handoff or create a
new proposal after fresh review.

After Adapter executes a write through WordPress Abilities API, it should call
Core `record-execution` with `execution_status`, `correlation_id`,
`approved_input_hash`, and public-safe execution counters. Core accepts the
record only when it matches an existing `commit.preflighted` handoff for the
approved proposal input and the caller has `commit:record_execution`. A
successful record changes proposal status to `executed`; a failed record
changes status to `execution_failed`. If audit persistence fails, Core rolls
the proposal back to `approved`.

Generic MCP keys should not receive `proposals:approve` or
`commit:record_execution`. Productized Magick AI Adapter may use a separately
issued trusted key with `proposals:approve` and `commit:record_execution` when
its own UI or host policy presents proposal risk, collects the user's approval,
executes the approved ability outside Core, and records only the execution
outcome back into Core.

Do not accept legacy `confirm_token`, `write_confirmed`, or compatibility
confirmation parameters in the rebuilt Core.

## Fail-Closed Rules

Commit must fail when:

- the proposal is missing or not approved;
- the ability is not known;
- the ability contract changed after proposal creation;
- required permission is missing;
- a preflight handoff was already issued for the approved input;
- approval context is absent, stale, or mismatched;
- the provider tries to bypass dry-run/proposal semantics;
- a plan-generated proposal still has unresolved `needs_input` or
  `preflight_blockers`;
- audit persistence fails.

## Plan-Generated Proposals

Plan-to-proposal intake can create reviewable proposals that are not yet
committable. For example, a missing title action may target
`npcink-abilities-toolkit/update-post` but require a human-provided `title`. Core stores that
proposal as pending review, but commit preflight returns
`npcink_governance_core_proposal_items_blocked` until the host creates a later complete
proposal with the missing input resolved.

Each plan action must also preserve the action-level safety contract. Core
rejects an action before proposal creation when it does not declare
`requires_approval=true` or when it claims `commit_execution=true`.
Permanent media deletion may only enter generated proposals when the host
request's `plan_input.include_delete_candidates` is true; a same-named flag
inside the plan output is not trusted for that destructive gate. This Core gate
is in addition to the media planning ability's own destructive delete policy,
such as `include_unattached_nonproduction_media` or `include_trash_parent_media`.
