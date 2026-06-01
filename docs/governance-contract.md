# Governance Contract

Status: MVP contract.

This document defines the first Magick AI Core governance boundary.

## Operation Lifecycle

1. `discover`: Core lists available abilities from WordPress Abilities API and
   known provider APIs.
2. `plan`: for supported read-only planning abilities, Core can accept the
   plan output and convert `write_actions` into proposals without executing
   the plan or target writes.
3. `request`: a caller submits an intended operation.
4. `proposal`: Core records a reviewable proposal and normalizes metadata.
5. `review`: a human or trusted host policy approves or rejects the proposal.
6. `commit`: a future Core service executes only after approval.
7. `audit`: Core records every lifecycle event.

The MVP implements discovery, proposal records, approval/rejection status, and
audit records. Commit preflight verifies approval readiness without executing
writes. Commit execution is intentionally contract-first follow-up work.

## Proposal Shape

Required fields:

- `ability_id`: the target WordPress ability id. It must be a real,
  currently discoverable ability id from ability intake, not a planning label
  such as `content/draft-preview`.
- `input`: caller-supplied structured input.

Optional fields:

- `title`: human-readable proposal title.
- `summary`: human-readable proposal summary.
- `preview`: dry-run result, diff, or handoff payload from the provider.
- `caller`: caller identity metadata.

Core-generated fields:

- `proposal_id`
- `status`
- `created_at`
- `updated_at`

Allowed MVP statuses:

- `pending`
- `approved`
- `rejected`
- `expired`
- `archived`

Pending proposals have a bounded review lifetime. Core may automatically move
stale `pending` proposals to `expired` before listing, viewing, or deciding
them. Expired proposals are not eligible for approval until they are reopened.
Expired proposals may be archived as low-frequency audit records, and expired
or archived proposals may be reopened to `pending` when an administrator needs
to review them again.

## Plan-To-Proposal Intake

Core may consume these read-only planning ability outputs:

- `magick-ai/build-content-inventory-fix-plan`
- `magick-ai/build-test-content-cleanup-plan`
- `magick-ai/build-media-inventory-fix-plan`

Plan intake does not execute the plan ability and does not execute target write
abilities. It accepts a successful plan payload, validates that the planning
ability is direct-read, validates each `write_action.target_ability_id` against
current ability intake, then creates one pending proposal per accepted action
unless the plan explicitly requests batch approval.

Plans may request one review item for a group of generated actions with either
`batch_approval=true` or `proposal_mode=batch`. Core then creates one
`plan_to_proposal_batch` proposal containing `input.write_actions[]`. The batch
proposal remains a governance record only; Adapter or another host executor is
still responsible for final per-action allowlist and schema checks after Core
approval and commit preflight.

Generated proposal previews must preserve:

- `target_ability_id`;
- target `input`;
- `before`;
- `after_suggestion`;
- `reason`;
- `risk`;
- `required_scopes`;
- `requires_approval=true`;
- `dry_run=true`;
- `commit=false`;
- `commit_execution=false`;
- `proposal_ready`;
- `manual_review`;
- `skipped_destructive_candidates`.

Actions with `requires_input` are reviewable but not committable. Their preview
must carry `proposal_ready=false`, `needs_input`, and `preflight_blockers`, and
commit preflight must return `magick_ai_core_proposal_items_blocked`.

Permanent media deletion is blocked by default. A plan action targeting
`magick-ai/delete-media-permanently` may become a proposal only when
`include_delete_candidates=true` is explicitly supplied with the plan input,
and it must remain high risk.

## Approval Boundary

Write and destructive commits must fail closed unless the commit request carries
host approval context created by Core.

Proposal creation validates that the target ability is currently discoverable.
Commit preflight repeats discovery against the stored real `ability_id` and
fails closed if that ability disappeared after approval.

The new Core uses approval-commit terminology. It must not reintroduce
`confirm_token`, `write_confirmed`, or other legacy confirmation parameters.

## Audit Events

MVP event names:

- `proposal.created`
- `proposal.plan_ingested`
- `proposal.approved`
- `proposal.rejected`
- `proposal.expired`
- `proposal.archived`
- `proposal.reopened`
- `proposal.viewed`
- `proposal.listed`
- `capabilities.listed`
- `audit.listed`
- `commit.preflighted`
- `app.created`
- `app.revoked`
- `app.rate_limited`
- `app.scope_denied`

Future event names:

- `commit.requested`
- `commit.succeeded`
- `commit.failed`

## Governance Operability

Core keeps enough operational evidence for proposal review and adapter
debugging without becoming an execution runtime or analytics system.

Proposal detail responses include an `audit_timeline` for the selected
proposal. The admin proposal detail uses the same evidence to show the proposal
payload, live capability summary, lifecycle events, app attribution, scope
decision, and commit-preflight correlation id.

Audit list filters include:

- `proposal_id`
- `event_name`
- `ability_id`
- `app_id`
- `key_id`
- `caller_type`
- `correlation_id`

Successful commit preflight returns a `correlation_id` in the response,
includes it in `approval_context.correlation_id`, and records the same value in
the `commit.preflighted` audit event metadata.

App-authenticated audit metadata includes `scope_decision`, currently
`allowed`, `denied`, or `rate_limited`.

## Security Defaults

- REST routes require `manage_options` in the MVP.
- Inputs are sanitized before persistence.
- Outputs are escaped by callers or REST serialization.
- SQL writes must use `$wpdb->insert()` or prepared queries.
- Secrets, provider keys, raw cookies, and passwords must not be stored in
  proposals or audit metadata.
