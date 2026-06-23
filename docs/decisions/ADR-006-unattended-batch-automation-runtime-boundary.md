# ADR-006: Unattended Batch Automation Runtime Boundary

## Status
Accepted

## Date
2026-06-15

## Context
Batch execution needs productized governance before it needs a background
worker. The reference lesson from existing WordPress automation tools is useful:
rules, eligibility, scope, blocked reasons, queue visibility, retry windows,
locks, timeouts, priorities, dependency states, and operator recovery guidance
must be explicit before work is allowed to run without a present operator.

Npcink already has the safer current-stage pieces:

- `npcink-abilities-toolkit` defines ability schemas, metadata, callbacks,
  dry-run previews, and plan-building abilities.
- `npcink-governance-core` owns ability intake, proposal records,
  approval/rejection, commit preflight, audit, and Adapter execution-result
  records.
- `npcink-ai-client-adapter` is the channel executor that may execute approved writes
  only after Core approval, successful commit preflight, and an explicit
  execution profile allowlist.
- `npcink-toolbox` is the operator UX and fixed-button product surface. It
  may produce planning artifacts and handoff suggestions, but final WordPress
  writes still flow through abilities and Core governance.

Core now exposes `preview.batch_review_summary` and commit-preflight
`proposal_item_preflight.batch_review_summary` for grouped proposal review. The
Adapter can project this as `batch_review_feedback` for operators. These are
review and recovery contracts, not queue or runtime contracts.

There is pressure to add unattended batch automation next. If that is placed in
Core, Core becomes a workflow runtime and contradicts ADR-002 and ADR-003. If it
is placed in the OpenClaw Adapter, the channel layer becomes a scheduler,
worker, and retry system. If it is hidden behind Toolbox buttons, the operator
surface becomes an implicit runtime without a clear operational contract.

## Decision
Do not implement unattended batch automation inside Core or the OpenClaw
Adapter for the current stage.

Unattended batch automation must wait for a dedicated local automation runtime
contract. That runtime may later be a separate plugin, module, or deliberately
contracted product component, but it must not be smuggled into Core governance
or an Adapter channel.

Core remains the governance truth:

- ability intake;
- proposal records;
- approval, rejection, and policy evidence;
- commit preflight;
- audit;
- execution-result recording after an external executor reports the outcome.

Core must not own jobs, leases, retries, workers, scheduler state, unattended
approval loops, or final WordPress writes.

Adapter remains a request/response channel executor:

- it may run direct-read abilities allowed by Core capability guidance;
- it may submit write proposals to Core;
- it may execute approved write actions only after Core approval,
  commit-preflight, and Adapter execution-profile allowlist validation.

Adapter must not own scheduler state, cron loops, durable job tables, lease
management, retry backoff, dead-letter handling, unattended approval, or broad
automation rules.

Toolkit remains the ability and preview owner. It may define plan abilities,
eligibility summaries, blocked-item structures, dependency output references,
and dry-run previews, but it must not become the approval truth or workflow
runtime.

Toolbox remains the operator product surface. It may show eligibility,
blocked items, retry guidance, `operator_next_action`, and per-action review
results, but it must not own final writes or hidden unattended runtime behavior
without a separate product and runtime contract.

## Required Runtime Contract Before Implementation
Before Npcink adds unattended batch automation, the dedicated runtime contract
must define all of the following:

1. Runtime owner and plugin/module boundary.
2. Job storage schema separate from Core proposal tables.
3. Job id, request id, actor, app, source, and correlation attribution.
4. Explicit eligibility, scope, blocked reason, and skipped-item contract.
5. Core proposal, approval, and commit-preflight handoff per job or bounded
   batch.
6. Lease, lock, timeout, retry backoff, and dead-letter semantics.
7. Idempotency keys and duplicate suppression.
8. Dependency graph and output-reference resolution.
9. Per-action ability allowlist and execution-profile validation.
10. Kill switch, pause, resume, and cancel behavior.
11. Rate limits, concurrency caps, and runtime health checks.
12. Core governance audit plus runtime operational audit.
13. Partial failure, rollback expectation, and recovery guidance.
14. Operator-facing job visibility, retry guidance, and blocked-item detail.
15. A default prohibition on auto-publish, destructive actions, broad
    permission changes, and irreversible mutations.

WordPress Cron, transients, Action Scheduler, or custom tables may be
implementation details after this contract exists. They are not a substitute
for the contract.

## Phased Plan

### Phase 0: Reviewed Batch Governance
This is the current stage.

- Toolkit emits explicit batch plan eligibility, blocked items, dry-run
  previews, and dependency references.
- Core stores grouped proposals and `batch_review_summary`.
- Adapter projects `batch_review_feedback` and executes nothing until approval,
  commit preflight, and allowlist validation pass.
- Toolbox presents operator-facing batch review and recovery details.

No unattended runtime exists in this phase.

### Phase 1: Runtime Contract Only
Define the runtime contract, job schema, event taxonomy, authorization model,
failure semantics, and dry-run replay fixtures in the future
`npcink-local-automation-runtime` owner repo or isolated runtime module. Core
must not keep those runtime implementation contracts locally. Add static
contracts that keep Core and Adapter out of runtime ownership.

No background execution should ship in this phase.

### Phase 2: Operator-Started Supervised Worker
Allow a present administrator to start a bounded batch run from an explicit
runtime surface. The run must support pause, cancel, retry review, and one
visible job state machine. Writes still require Core approval and commit
preflight.

Auto-publish, destructive actions, broad settings changes, and site-wide writes
remain excluded.

### Phase 3: Scheduled Low-Risk Unattended Jobs
Allow scheduled unattended runs only for narrow, reversible, low-risk
operations with explicit schedules, small allowlists, rate limits, kill switch,
runtime audit, and Core-governed approval or bounded policy grants.

### Phase 4: Broader Automation After Recovery Proofs
Consider broader automation only after incident drills, timeout/retry tests,
dead-letter recovery, duplicate suppression tests, and audit reconciliation
prove reliable.

## Alternatives Considered

### Put the queue in Core
Rejected. Core would become a workflow runtime and would own jobs, leases,
workers, retries, and failure recovery. That contradicts ADR-002 and ADR-003.

### Put the queue in OpenClaw Adapter
Rejected. Adapter would stop being a channel layer and become the canonical
runtime for other future channels, contradicting ADR-005.

### Hide automation behind Toolbox fixed buttons
Rejected for the current stage. Toolbox is the operator UX and artifact
surface. Hidden unattended behavior would make product buttons double as a
runtime without clear operational guarantees.

### Use WordPress Cron and transients directly
Rejected as a decision substitute. Cron and transients can be useful
implementation details, but they do not define eligibility, authorization,
idempotency, lease recovery, audit, or operator recovery semantics.

## Consequences
Npcink can safely use the current batch review path now, but unattended
execution remains intentionally unavailable until a runtime contract exists.

The near-term work is contract-first:

- keep improving `batch_review_summary` and Adapter `batch_review_feedback`;
- keep Toolbox focused on visible operator review;
- keep Toolkit focused on ability definitions, plan schemas, dry-run previews,
  and eligibility details;
- add runtime ADR/API work only when a dedicated owner is selected.

This slows down fully automated bulk operations, but it avoids turning Core,
Adapter, or Toolbox into accidental schedulers with unclear failure semantics.
