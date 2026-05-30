# Plan To Proposal Governance

Status: active.

Core can now consume read-only planning ability output and turn the plan's
`write_actions` into ordinary Core proposals. This is a governance bridge, not
an execution bridge.

## Supported Plan Abilities

- `magick-ai/build-content-inventory-fix-plan`
- `magick-ai/build-test-content-cleanup-plan`
- `magick-ai/build-media-inventory-fix-plan`

These abilities belong to `magick-ai-abilities`. They are executed through the
WordPress Abilities API by the host or adapter. Core only receives their output.

## Boundary

Core owns:

- accepting plan output;
- validating the plan ability is allowed and direct-read;
- validating each `target_ability_id` is discoverable and proposal-governed;
- converting accepted `write_actions` into pending proposals;
- preserving preview, risk, warning, blocked, and needs-input context;
- approval, rejection, commit preflight, and audit.

Core does not own:

- running the planning abilities;
- generating content, SEO, media, or cleanup recommendations;
- executing final WordPress mutations;
- workflow runtime, MCP runtime, queueing, or batch execution.

## REST Flow

1. Adapter or host runs a supported read-only plan ability through
   `/wp-json/wp-abilities/v1/abilities/{ability_id}/run`.
2. Adapter posts the plan output to
   `POST /wp-json/magick-ai-core/v1/proposals/from-plan`.
3. Core creates one pending proposal per accepted `write_action`.
4. Admin or trusted policy approves or rejects proposals through the existing
   proposal routes.
5. Adapter calls
   `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`.
6. Only after approval and successful preflight may the host call the real write
   ability outside Core.

Core still returns `commit_execution=false`; there is no Core write execution
route.

## Proposal Preview Contract

Generated proposal previews preserve:

- `source.type=plan_to_proposal`;
- `source.plan_ability_id`;
- `source.batch_id`;
- `source.issue_types`;
- `action_id`;
- `action_index`;
- `target_ability_id`;
- `before`;
- `after_suggestion`;
- `reason`;
- `risk.level`;
- `risk.plan_level`;
- `risk.target_risk_level`;
- `required_scopes`;
- `requires_approval=true`;
- `dry_run=true`;
- `commit=false`;
- `commit_execution=false`;
- `proposal_ready`;
- `needs_input`;
- `warnings.manual_review`;
- `warnings.skipped_destructive_candidates`;
- `blocked_items.manual_review`;
- `blocked_items.skipped_destructive_candidates`;
- `preflight_blockers`.

The target ability input is stored in proposal `input` with `dry_run=true` and
`commit=false` forced by Core.

## Safety Rules

Plan intake fails closed when:

- the planning ability id is not supported;
- the planning ability is not discoverable;
- the planning ability is not `governance_mode=direct_read`;
- the plan does not include `requires_approval=true`;
- the plan does not include `dry_run=true`;
- the plan includes `commit_execution=true`;
- the plan lacks a `write_actions` array;
- a target ability is not discoverable;
- a target ability is not proposal-governed;
- a target ability unexpectedly enables Core proxy or commit execution;
- an action input sets `dry_run=false` or `commit=true`.

`manual_review` and `skipped_destructive_candidates` are never dropped. They
are copied into generated proposal warnings and blocked item context.

Permanent media deletion is stricter: `magick-ai/delete-media-permanently`
actions are blocked unless the submitted `plan_input` explicitly contains
`include_delete_candidates=true`. Allowed delete proposals remain high risk.

## Commit Preflight

Commit preflight now evaluates proposal item readiness:

- `proposal_ready=true`, no `needs_input`, and no `preflight_blockers` can pass
  after approval.
- `proposal_ready=false` or non-empty `needs_input`/`preflight_blockers` returns
  `magick_ai_core_proposal_items_blocked` with HTTP `409`.

This means a human may review an incomplete plan action, but the host cannot
treat it as committable until the required input is resolved in a later
proposal.

## Audit

Plan intake records:

- `proposal.plan_ingested`

Each generated proposal also records the normal proposal lifecycle events:

- `proposal.created`
- `proposal.approved`
- `proposal.rejected`
- `commit.preflighted`

Use `proposal_id`, `ability_id`, and `correlation_id` filters in Core
Governance Audit to trace the plan-to-proposal lifecycle.

