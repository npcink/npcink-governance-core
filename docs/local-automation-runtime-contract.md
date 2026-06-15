# Local Automation Runtime Contract

Status: planning contract.

This document defines the contract a future local automation runtime must
implement before Npcink allows unattended batch execution. It is not an
implementation plan for `npcink-governance-core`, and it does not add Core REST
routes, Core tables, Core workers, or Core final write execution.

## Purpose

The runtime exists to turn already-reviewed automation intent into observable,
recoverable work. It must make every unattended run easy to stop, audit, retry,
or diagnose without moving governance truth out of Core.

The runtime may own:

- job records and action records;
- scheduling and operator-started run control;
- leases, locks, timeouts, retry windows, and dead-letter state;
- runtime operational events and health signals;
- runtime admin UI for job visibility, pause, cancel, retry, and recovery.

The runtime must not own:

- WordPress ability definitions;
- Core proposal, approval, preflight, or audit truth;
- Adapter execution-profile policy;
- provider credentials, prompt/preset truth, or billing truth;
- broad auto-approval policy;
- direct WordPress writes that bypass WordPress Abilities API and Core
  governance.

## Owner Boundary

The runtime must be a dedicated local plugin, module, or deliberately
contracted product component. It must not be implemented inside Core or inside
the OpenClaw Adapter.

| Component | Owns | Must not own |
| --- | --- | --- |
| Core | Ability intake, proposals, approval/rejection, commit preflight, audit, execution-result records. | Jobs, leases, retries, scheduler state, dead-letter state, workers, final writes. |
| Toolkit | Ability schemas, metadata, callbacks, dry-run previews, planning abilities, eligibility detail. | Approval truth, runtime job state, queues, final writes. |
| Adapter | Channel execution after Core approval, commit preflight, and execution-profile allowlist. | Scheduler state, durable jobs, retry loops, lease management, approval truth. |
| Toolbox | Operator-facing artifacts, fixed buttons, review UI, handoff suggestions. | Hidden unattended runtime, final writes, Core governance truth. |
| Local automation runtime | Jobs, schedules, action state, leases, retries, dead-letter recovery, runtime UI. | Core governance truth, ability registry, Adapter profile policy, provider billing truth. |

## Job Model

A runtime job represents one bounded automation request.

Required fields:

- `job_id`: stable runtime-generated id.
- `contract_version`: `npcink_local_automation_runtime.v1`.
- `runtime_id`: local runtime instance id.
- `source`: `operator_started`, `scheduled`, `cli`, or `local_product`.
- `actor`: user, app, or system identity visible to operators.
- `correlation_id`: id shared across runtime events, Core proposals, Adapter
  execution, and provider logs where available.
- `idempotency_key`: caller-provided or runtime-generated duplicate
  suppression key.
- `title` and `summary`: human-readable review text.
- `status`: one value from the runtime state machine.
- `eligibility_summary`: evaluated object/action counts and scope.
- `blocked_items`: list of blocked candidates with reason codes.
- `actions`: ordered runtime action records.
- `core_handoff`: Core proposal and preflight references.
- `limits`: max actions, concurrency, retry count, timeout, and schedule bounds.
- `created_at`, `updated_at`, and `expires_at`.

The runtime job record must not store raw provider secrets, authorization
headers, cookies, application passwords, private keys, prompt text, or
unsanitized ability result payloads.

## Action Model

Each runtime action represents one intended ability execution unit.

Required fields:

- `action_id`: stable id within the job.
- `target_ability_id`: real WordPress ability id.
- `execution_profile`: Adapter/runtime allowlist profile.
- `input_hash`: hash of the approved action input.
- `preview_ref`: reference to dry-run preview evidence.
- `depends_on`: zero or more prior action output references.
- `status`: `blocked`, `waiting`, `ready`, `leased`, `running`, `succeeded`,
  `failed`, `retry_wait`, `skipped`, or `dead_lettered`.
- `attempt_count` and `max_attempts`.
- `retryable`: boolean.
- `blocked_reason`: machine-readable reason when not ready.
- `last_error_code` and redacted `last_error_message`.
- `output_refs`: bounded references only, not full result payloads.

Actions that are destructive, irreversible, publish content, modify broad
settings, or change permissions must default to blocked unless a later ADR and
runtime policy explicitly allow them.

## State Machine

Allowed job statuses:

- `planned`: job has been created but not admitted for execution.
- `blocked`: eligibility, scope, input, or policy prevents proposal creation.
- `awaiting_core_proposal`: runtime has not yet created the Core proposal.
- `awaiting_core_approval`: Core proposal exists and requires approval.
- `awaiting_core_preflight`: proposal is approved but preflight has not passed.
- `ready`: preflight passed and actions are ready to lease.
- `paused`: operator or kill switch paused the job.
- `running`: one or more actions are leased or executing.
- `retry_wait`: failed retryable actions are waiting for backoff.
- `succeeded`: all required actions succeeded or were intentionally skipped.
- `partially_failed`: at least one required action failed and recovery remains
  possible.
- `failed`: job cannot continue without a new proposal or operator action.
- `cancelled`: operator cancelled before completion.
- `dead_lettered`: retry limits or stale recovery limits were exhausted.

Transitions must be compare-and-set style: the runtime may only move a job or
action from an expected previous status to the next status. Stale workers must
not be able to overwrite a newer operator decision.

## Eligibility And Scope

The runtime must evaluate eligibility before it creates or reuses a Core
proposal.

The eligibility summary must include:

- `scope`: target post ids, attachment ids, taxonomy ids, or site object ids.
- `candidate_count`, `eligible_count`, `blocked_count`, and
  `needs_input_count`.
- `allowed_ability_ids`.
- `disallowed_ability_ids`.
- `risk_level`.
- `operator_next_action`.
- `retryable`.

Each blocked item must include:

- `item_id` and `item_type`.
- `target_ability_id` when known.
- `reason_code`.
- `reason_message`.
- `operator_next_action`.
- `retryable`.

Blocked candidates must never disappear silently. They must be visible in the
runtime UI and, when submitted to Core as a batch proposal, reflected in Core's
`batch_review_summary` or proposal preview.

## Core Handoff

The runtime must use Core through public governance routes only. It must not
write directly to Core tables.

Required handoff flow:

1. Build or receive a dry-run plan with `commit=false`.
2. Submit proposal-required write actions through Core plan intake or proposal
   creation.
3. Wait for Core approval or a future explicit bounded policy grant.
4. Call Core commit preflight after approval.
5. Store the returned `proposal_id`, `correlation_id`, `approved_input_hash`,
   and any bounded `batch_review_summary`.
6. Execute actions only through an allowed executor after preflight.
7. Record execution success or failure back to Core through Core's
   execution-result route.

The runtime must treat these as hard failures:

- Core proposal creation fails.
- Core approval is missing, rejected, expired, or archived.
- Commit preflight returns `409` or any non-success response.
- `approved_input_hash` does not match the action input hash.
- The Adapter or executor profile is not explicitly allowlisted.
- A dependency output reference cannot be resolved safely.

## Lease And Lock Semantics

Every executing action must be protected by a lease.

Required lease fields:

- `lease_id`
- `lease_owner`
- `lease_token`
- `leased_at`
- `lease_expires_at`
- `heartbeat_at`
- `expected_status`

A worker may execute an action only when it holds the current lease token. Lease
renewal must use the same token and must fail if the action status or token has
changed. Expired leases may be recovered only after the timeout window passes
and after the runtime records a stale-lease event.

## Retry And Dead Letter

Retries must be bounded and visible.

Required retry rules:

- no infinite retries;
- no retry for non-idempotent actions unless the ability and runtime policy
  explicitly mark the action idempotent;
- exponential or fixed backoff recorded per action;
- operator-visible `next_retry_at`;
- final transition to `dead_lettered` when retry limits are exhausted;
- dead-letter records must preserve `job_id`, `action_id`, `proposal_id`,
  `correlation_id`, `attempt_count`, redacted error evidence, and recovery
  guidance.

Retrying an action after Core preflight expires requires a new Core preflight
or a new proposal, depending on the Core response and proposal status.

## Idempotency And Duplicate Suppression

The runtime must reject or coalesce duplicate jobs with the same
`idempotency_key` while an equivalent job is active.

Action execution must carry an action-level idempotency key to the executor
when the target ability supports it. If an ability does not support
idempotency, the action must be treated as higher risk and must not be retried
unattended by default.

## Dependency Resolution

Dependency references must use explicit output paths such as
`$outputs.prior_action.field`. The runtime must validate that:

- the referenced action succeeded;
- the output field exists;
- the field value matches the receiving ability schema;
- the value is not a secret-shaped field;
- the dependency does not create a cycle.

Unresolved dependencies move the dependent action to `blocked` or
`retry_wait`, not `running`.

## Authorization

Runtime administration requires a present administrator capability such as
`manage_options` until a narrower capability model is intentionally designed.

Runtime app keys should be scoped narrowly. A default unattended runtime app key
may request proposal creation, proposal reads, commit preflight, and
execution-result recording scopes. It must not receive approval scope by
default.

Any future unattended approval or policy grant must be a separate Core-governed
contract with explicit bounds, quotas, risk profiles, expiry, audit evidence,
and kill-switch behavior.

## Operator Controls

The runtime UI must expose:

- current status and next action;
- eligibility summary and blocked items;
- Core proposal id and approval/preflight status;
- per-action status, attempts, and redacted error evidence;
- pause, resume, cancel, and retry controls;
- kill switch for all scheduled unattended runs;
- dead-letter view with recovery guidance;
- filters by status, ability id, actor, app, source, and correlation id.

The UI must not hide blocked, skipped, failed, or dead-lettered items behind a
single success count.

## Audit And Events

Core audit remains the governance audit. Runtime audit is operational audit.
Both are required.

Minimum runtime events:

- `runtime.job.created`
- `runtime.job.eligibility_evaluated`
- `runtime.job.core_proposal_created`
- `runtime.job.awaiting_approval`
- `runtime.job.preflight_passed`
- `runtime.job.paused`
- `runtime.job.resumed`
- `runtime.job.cancelled`
- `runtime.job.completed`
- `runtime.job.failed`
- `runtime.job.dead_lettered`
- `runtime.action.lease_acquired`
- `runtime.action.started`
- `runtime.action.succeeded`
- `runtime.action.failed`
- `runtime.action.retry_scheduled`
- `runtime.action.dead_lettered`

Event metadata must be redacted and must carry `job_id`, `action_id` when
applicable, `proposal_id` when applicable, `correlation_id`, actor/app/source
attribution, and runtime contract version.

## Acceptance Gates

Before implementation starts:

- ADR-006 remains accepted and linked from project docs.
- This contract has static tests for its boundary markers.
- Core and Adapter tests reject queue/runtime ownership drift.
- A dedicated runtime owner has been selected.

Before a supervised worker ships:

- job/action schema is documented and covered by tests;
- state transitions use expected-status checks;
- lease timeout and stale recovery tests pass;
- duplicate suppression tests pass;
- Core proposal/preflight handoff tests pass;
- kill switch, pause, cancel, and retry controls are covered by tests;
- no auto-publish, destructive, or irreversible action can run by default.

Before scheduled unattended jobs ship:

- runtime has rate limits and concurrency caps;
- dead-letter recovery has test fixtures;
- Core audit and runtime audit can be reconciled by `correlation_id`;
- incident drill docs exist;
- a rollback or recovery expectation exists for every allowed profile.

## Current Stage

Current Npcink batch support remains Phase 0 reviewed governance:

- `batch_review_summary` in Core;
- `batch_review_feedback` in Adapter;
- visible eligibility, blocked items, retry guidance, and operator next action
  in product surfaces;
- no unattended runtime, no scheduler, no worker, no runtime job table, and no
  final WordPress write execution in Core.
