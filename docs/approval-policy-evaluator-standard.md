# Approval Policy Evaluator Standard

Status: active planning standard for the lightweight evaluator.

Magick AI Core owns proposal, approval, commit-preflight, and audit truth. A
policy evaluator may help Core classify whether a proposal still needs manual
review, can be conservatively auto-approved in the future, or must be blocked.
It must stay small enough to preserve Core as a governance layer, not a strategy
engine or workflow runtime.

## Current State

The evaluator supports three site policy modes stored in
`magick_ai_core_approval_policy_mode`:

- `manual`: default. Core records `manual_required` for every proposal with
  `policy_profile=manual`.
- `dry_run_guarded`: development observation mode. Core may classify trusted
  cleanup trash-post batches with `policy_profile=guarded`, but the proposal
  remains `pending`.
- `local_guarded`: development auto-approval mode. Core may approve only
  trusted test-content cleanup trash-post batches after explicit authorization,
  persisted evidence, quota checks, and audit.

Every successful proposal creation writes `proposal.policy_evaluated`. If that
audit event cannot be recorded, Core fails closed by deleting the created
proposal row.

Current behavior is intentionally unchanged:

- all proposals remain `pending` by default in `manual`;
- no proposal is auto-approved unless `local_guarded` is explicitly enabled and
  every narrow cleanup condition passes;
- Adapter remains thin and executes only approved proposals that pass Core
  commit preflight;
- policy fields are non-secret governance metadata, not caller-controlled
  instructions;
- the evaluator does not add a rules DSL, workflow runtime, scheduler, or UI
  configuration center.

## Boundary

Core may own:

- a hardcoded evaluator class;
- stable policy decision fields;
- audit metadata for every policy decision;
- conservative fail-closed checks before and during auto approval;
- proposal spam guardrails that protect Core's governance queue.

Core must not own:

- rules DSLs;
- workflow runtime or queue execution;
- long-running policy jobs or schedulers;
- product-specific approval consoles;
- model routing, provider credentials, prompt or preset policy;
- final WordPress ability execution.

If an implementation needs any of those surfaces, stop and write a boundary
note instead of extending the evaluator.

## Decision Shape

Reserved decision values:

- `manual_required`: human or host approval is still required.
- `auto_approved`: Core may transition the proposal to approved without a
  separate manual approval, only after explicit enablement and narrow checks.
- `blocked`: Core must not create or advance the proposal under the selected
  policy.

Reserved profiles:

- `manual`: default profile; every proposal requires manual approval.
- `guarded`: future conservative auto-approval candidate profile.
- `trusted_local`: future local-only profile for explicitly trusted adapters
  and local test content operations.
- `break_glass`: reserved name for emergency manual override policy; it must
  not bypass audit, preflight, or destructive-operation blocks.

Proposal responses expose:

- `policy_decision`
- `policy_profile`
- `policy_version`
- `policy_reasons[]`

Proposal rows do not need new columns for the current stage. Core stores
non-secret policy metadata under `caller.core_policy`, and
`Proposal_Repository` promotes those fields into response rows. This keeps the
database shape stable while preserving the decision record.

Audit metadata for `proposal.policy_evaluated` must include:

- `policy_decision`
- `policy_profile`
- `policy_version`
- `policy_mode`
- `policy_reasons`
- `auto_approval_applied`
- `commit_execution=false`
- proposal id and ability id when available

When `local_guarded` changes proposal status, Core must also write
`proposal.auto_approved`. If that audit event cannot be written, Core must not
leave the proposal approved.

Policy-only diagnostics that are useful for operators but not part of the
proposal API should stay in audit metadata. Ephemeral evaluator inputs that are
only useful during calculation should stay out of both proposal rows and REST
responses unless they are needed to justify a decision later.

## Spam Guardrails

Policy evaluation must not make proposal spam worse. These guardrails are part
of the policy standard even when the first implementation only records
`manual_required`:

- proposal creation reuses an equivalent pending proposal for the same caller,
  `ability_id`, and sanitized input hash;
- app-authenticated callers have a bounded pending proposal quota;
- administrator callers have a high but finite pending proposal quota;
- stale pending proposals are expired before create guardrail checks;
- `local_guarded` uses separate hourly and daily auto-approval quotas per
  app/caller/profile;
- auto approval records quota subject and limits in audit metadata.

## Auto-Approval Readiness

Do not widen real auto approval beyond cleanup until all of these remain true:

- auto approval is explicitly enabled by Core-owned configuration or a trusted
  host policy contract;
- the caller/app key is authenticated and authorized with
  `proposals:create` plus `proposals:approve`, or a narrower trusted adapter
  scope documented before use;
- every decision writes audit before status transition is considered complete;
- the evaluator can prove that the proposal matches a narrow allowlisted
  scenario without trusting free-form caller claims;
- per-window auto-approval quotas exist and fail closed;
- duplicate input hashes cannot create repeated approved rows;
- commit preflight continues to bind approved input hash, preview hash,
  correlation id, and preflight policy version;
- Adapter still executes only after approved status and successful preflight.

The current safe production default is still `manual`. Use `dry_run_guarded`
first when validating a new local environment, then `local_guarded` only to
reduce repetitive development approvals for trusted cleanup batches.

## First Narrow Candidate: Test Cleanup Trash Batch

This is the only implemented auto-approval scenario.

Required properties:

- source must be `build-test-content-cleanup-plan` through
  `plan_to_proposal_batch`;
- every action target must be `magick-ai/trash-post`;
- every post/title must match trusted test-content patterns, or the plan
  preview must carry a trusted test-content marker produced by the planning
  ability;
- batch size must be capped;
- caller/app key must be authenticated and explicitly authorized for approval;
- duplicate input hash must reuse or block instead of creating repeated
  approved proposals;
- auto approval must write `proposal.policy_evaluated` and
  `proposal.auto_approved` if it changes proposal status.

Evidence required for real auto approval:

- persisted preview evidence proving each action targets test content;
- smoke or static coverage for batch size cap, non-test content block, mixed
  target block, missing approval scope block, and quota block;
- documentation that Adapter still performs per-action allowlist and schema
  checks before final WordPress mutation.

## Later Candidate: Create Draft

`magick-ai/create-draft` can be considered only after the cleanup batch path is
proven.

Required properties:

- creates draft content only;
- cannot publish or schedule;
- cannot modify existing public content;
- single item or very small batch;
- caller/app key is explicitly trusted and quota-bound;
- audit records decision reasons and any status transition.

This is lower priority than cleanup because draft creation is product-facing
and easier to confuse with article-generation workflow ownership.

## Explicit Non-Candidates

Do not auto-approve these in the first policy stages:

- `magick-ai/delete-media-permanently`
- `magick-ai/delete-post-permanently`
- `magick-ai/delete-term`
- `magick-ai/set-post-terms`
- `magick-ai/approve-comment`
- `magick-ai/reply-comment`
- `magick-ai/update-post` when it touches published content

These may still create reviewable proposals, but they must remain manual unless
a later accepted standard replaces this one.

For media-derived plans, Core's own destructive gate is not enough to prove
source safety. Media planning abilities may require upstream flags such as
`include_unattached_test_media=true` or `include_trash_parent_media=true` before
they emit delete actions at all. The evaluator must treat those flags as
source-policy evidence only, not as permission to bypass manual approval.

## Implementation Plan

### Phase 0: Observation-Only Baseline

Status: implemented.

- Add evaluator skeleton and reserved constants.
- Record `manual_required` for every proposal.
- Promote decision fields into proposal responses.
- Write `proposal.policy_evaluated` for every created proposal.
- Fail closed if decision audit cannot be written.

### Phase 1: Dry-Run Guarded Candidates

Status: implemented.

- Add evaluator checks for cleanup trash-post batch candidates.
- Return `manual_required` while adding stable reasons such as
  `guarded_cleanup_candidate` or `guarded_cleanup_rejected_*`.
- Do not change proposal status.
- Add audit metadata for candidate evidence and rejection reasons.
- Add static, fail-closed, and WordPress smoke coverage.

### Phase 2: Explicit Auto Approval For Cleanup Only

Status: implemented for development `local_guarded`.

- Add explicit enablement and app/scope authorization.
- Add hourly and daily auto-approval quotas.
- Transition only qualifying cleanup batch proposals to `approved`.
- Write `proposal.auto_approved` after `proposal.policy_evaluated`.
- Keep commit preflight unchanged and mandatory.

### Phase 3: Consider Create Draft

Only after cleanup auto approval has passed local smoke and operator review.

- Keep single-draft or very small batch limits.
- Block publish, schedule, and update of existing public content.
- Require explicit trusted caller/app policy and quotas.

## Test Expectations

Every policy change must update the narrowest useful gates:

- `tests/run.php` static contracts for reserved decisions, profiles, docs, and
  boundary text;
- fail-closed tests when policy audit, quota, or status transitions are added;
- WordPress smoke when behavior depends on REST routes, tables, app scopes,
  abilities, or proposal lifecycle;
- docs whenever response fields, audit metadata, status transitions, or
  product boundaries change.

Run at least:

```bash
composer test:all
```

Run WordPress smoke when the policy touches activation, tables, REST routing,
plan-to-proposal intake, app-authenticated proposal creation, or abilities:

```bash
composer smoke:wp
```
