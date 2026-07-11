# Approval Policy Evaluator Standard

Status: active planning standard for the lightweight evaluator.

Npcink Governance Core owns proposal, approval, commit-preflight, and audit truth. A
policy evaluator may help Core classify whether a proposal still needs manual
review, can be conservatively auto-approved in the future, or must be blocked.
It must stay small enough to preserve Core as a governance layer, not a strategy
engine or workflow runtime.

## Current State

The supported policy mode enum is closed. The evaluator supports exactly three
site policy modes stored in
`npcink_governance_core_approval_policy_mode`:

- `manual`: default. Core records `manual_required` for every proposal with
  `policy_profile=manual`.
- `smart_guarded`: conservative smart approval mode. Core may approve only
  trusted test-content cleanup trash-post batches and single draft-only
  create-draft proposals, guarded article-audio adoptions, or single reviewed
  media derivative adoption proposals after explicit authorization, persisted
  evidence, quota checks, and audit.
- `dev_allow_all`: local development allow-all mode. It may approve every
  proposal only when `NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` is explicitly
  defined as true, the caller can approve proposals, quota checks pass, and
  audit succeeds. It still does not execute writes and commit preflight remains
  mandatory.

Any unrecognized stored value falls back to `manual`. Old approval policy modes
are intentionally not accepted. Admin settings must make that fallback visible
to operators when the stored option is stale or invalid, but must not restore
alias behavior.

Every successful proposal creation writes `proposal.policy_evaluated`. If that
audit event cannot be recorded, Core fails closed by deleting the created
proposal row.

Current behavior is intentionally unchanged:

- all proposals remain `pending` by default in `manual`;
- no production proposal is auto-approved unless `smart_guarded` is explicitly
  enabled and every narrow implemented candidate condition passes;
- no development allow-all approval is applied unless
  `dev_allow_all` is selected and the local development constant is true;
- Adapter remains thin and executes only approved proposals that pass Core
  commit preflight;
- policy fields are non-secret governance metadata, not caller-controlled
  instructions;
- the evaluator does not add a rules DSL, workflow runtime, scheduler, or UI
  configuration center.

## Boundary

Core may own:

- a hardcoded evaluator class;
- small policy strategy classes selected by a bounded mode value;
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

When a strategy changes proposal status automatically, Core must also write
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
- `smart_guarded` and `dev_allow_all` use separate hourly and daily auto-approval quotas per
  app/caller/profile;
- auto approval records quota subject and limits in audit metadata.

## Auto-Approval Readiness

Do not widen real auto approval beyond implemented narrow candidates until all of these remain true:

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

The current safe production default is still `manual`. Use `smart_guarded`
only when the site should reduce repetitive approvals for trusted cleanup
batches, single draft-only create-draft proposals, guarded article-audio
adoptions, or single reviewed media derivative adoption proposals. Use
`dev_allow_all` only inside a local development environment with the explicit
constant enabled.

## First Narrow Candidate: Test Cleanup Trash Batch

This is one implemented auto-approval scenario.

Required properties:

- source must be `build-nonproduction-content-cleanup-plan` through
  `plan_to_proposal_batch`;
- every action target must be `npcink-abilities-toolkit/trash-post`;
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

## Second Narrow Candidate: Create Draft

Status: implemented for `smart_guarded`.

`npcink-abilities-toolkit/create-draft` may be auto-approved only for one direct
proposal at a time. Plan batch create-draft actions remain governed by their
own plan contracts and do not inherit this direct-proposal shortcut.

Required properties:

- creates draft content only;
- cannot publish or schedule;
- cannot modify existing public content;
- single direct proposal only;
- caller/app key is explicitly trusted and quota-bound;
- audit records decision reasons and any status transition.

Real auto approval additionally requires `post_type=post` or the equivalent
default, `status=draft` or the equivalent default, a reviewed title,
`dry_run=true`, `commit=false`, no schedule/publish fields, bounded content
size, `smart_guarded_create_draft_auto_approved` in `policy_reasons`, and
`proposal.auto_approved` audit if status changes. This remains a development
approval reducer, not an article-generation workflow, not batch article
approval, and not final WordPress execution.

## Third Narrow Candidate: Article Audio Adoption

Status: implemented for `smart_guarded`.

`npcink-abilities-toolkit/adopt-article-audio` may be auto-approved only when a
trusted `build-article-audio-adoption-plan` handoff produced one dry-run,
non-commit adoption proposal for one post. The proposal must carry the expected
plan source, a valid article-audio kind, required input evidence, and no
unexpected input keys. This reducer does not make Core an audio workflow
runtime, media importer, or execution owner.

## Fourth Narrow Candidate: Media Derivative Adoption

Status: implemented for `smart_guarded`.

`npcink-abilities-toolkit/adopt-cloud-media-derivative` may be auto-approved
only for one reviewed attachment derivative adoption proposal at a time. It is
intended for operator-reviewed media-library optimization flows where the
current user or trusted Adapter key already has approval authority.

Required properties:

- one `attachment_id`;
- one `derivative_artifact` with artifact evidence;
- proposal input remains `dry_run=true` and `commit=false`;
- preview identifies a `media_optimization_plan` or the
  `npcink-abilities-toolkit/build-media-optimization-plan` source ability;
- no nested `write_actions`, direct content-reference repair payload, delete,
  settings, featured-image, or URL-repair action is bundled into the shortcut;
- caller/app key is explicitly trusted and quota-bound;
- Adapter still performs Core commit preflight and final execution outside
  Core.

Real auto approval additionally requires
`guarded_media_derivative_candidate` and
`smart_guarded_media_derivative_auto_approved` in `policy_reasons` and
`proposal.auto_approved` audit if status changes. This is an approval reducer
for reviewed single-attachment derivative replacement proposals; it is not a
Core batch optimizer, media queue, Cloud runtime, or direct WordPress write.

## Fifth Narrow Candidate: Media ALT-Only Update

Status: implemented for `smart_guarded`.

`npcink-abilities-toolkit/update-media-details` may be auto-approved only for
one `media_alt_apply_plan.v1` missing-ALT proposal at a time. The proposal must
come from `npcink-abilities-toolkit/build-media-alt-apply-plan` after an
operator inspected the image and accepted the ALT text.

Required properties:

- one `attachment_id`;
- one concise `alt` value;
- `expected_current_alt` is explicitly empty;
- `operator_visual_review_confirmed=true` is present in input and evidence;
- proposal input remains `dry_run=true` and `commit=false`;
- no `title`, `caption`, `description`, source, attribution, or other media
  metadata fields are present;
- preview identifies `media_alt_apply_plan_item`, `media_alt_apply_plan.v1`,
  and its source `media_alt_caption_review_set.v1`;
- preview marks `operator_reviewed=true` and
  `operator_visual_review_confirmed=true`;
- current ALT status is exactly `missing`;
- caller/app key is explicitly trusted and quota-bound;
- Adapter still performs Core commit preflight and final execution outside
  Core, including a Toolkit live-value dry run immediately before commit.

Real auto approval additionally requires `guarded_media_alt_candidate` and
`smart_guarded_media_alt_auto_approved` in `policy_reasons` and
`proposal.auto_approved` audit if status changes.

## Development Allow-All Strategy

`dev_allow_all` exists only to reduce local development friction when a
developer deliberately wants every proposal to become `approved` before commit
preflight. It must remain visibly unsafe and bounded:

- the stored policy mode must be `dev_allow_all`;
- the constant `NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` must be true;
- the current user or app caller must be able to approve proposals;
- hourly and daily auto-approval quota checks must pass;
- `proposal.policy_evaluated` and `proposal.auto_approved` audit events must
  be written;
- `policy_reasons` must include `dev_allow_all_auto_approved` and
  `commit_preflight_still_required`;
- Core must still return `commit_execution=false` in policy audit metadata and
  must not execute final writes.

When the constant is absent or false, `dev_allow_all` must fail closed to
`manual_required` with `dev_allow_all_rejected_disabled`.

## Explicit Non-Candidates

Do not auto-approve these in the current policy stage:

- `npcink-abilities-toolkit/delete-media-permanently`
- `npcink-abilities-toolkit/delete-post-permanently`
- `npcink-abilities-toolkit/delete-term`
- `npcink-abilities-toolkit/set-post-terms`
- `npcink-abilities-toolkit/set-post-featured-image`
- URL repair or media settings proposals
- `npcink-abilities-toolkit/approve-comment`
- `npcink-abilities-toolkit/reply-comment`
- `npcink-abilities-toolkit/update-post` when it touches published content

These may still create reviewable proposals, but they must remain manual unless
a later accepted standard replaces this one.

For media-derived plans, Core's own destructive gate is not enough to prove
source safety. Media planning abilities may require upstream flags such as
`include_unattached_nonproduction_media=true` or `include_trash_parent_media=true` before
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

Status: implemented for `smart_guarded`.

- Add explicit enablement and app/scope authorization.
- Add hourly and daily auto-approval quotas.
- Transition only qualifying cleanup batch proposals to `approved`.
- Write `proposal.auto_approved` after `proposal.policy_evaluated`.
- Keep commit preflight unchanged and mandatory.

### Phase 3: Direct Draft Proposal Auto Approval

Status: implemented for `smart_guarded`.

- Keep single direct draft proposals only.
- Block publish, schedule, non-post post types, and existing-content targets.
- Require explicit trusted caller/app policy and quotas.
- Keep commit preflight unchanged and mandatory.

### Phase 4: Strategy Mode Refactor

Status: implemented.

- Add a bounded `Approval_Policy_Strategy` contract.
- Implement `manual`, `smart_guarded`, and `dev_allow_all` strategies.
- Remove prior approval policy mode aliases instead of keeping compatibility
  shims.
- Keep Core out of workflow runtime, scheduler, queue, MCP, Agent Gateway, and
  final execution ownership.

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
