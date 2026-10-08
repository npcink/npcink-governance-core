# ADR-010: Keep Final Commit Execution Outside Core Until A Named Trigger Fires

## Status
Accepted

## Date
2026-10-08

## Context
ADR-003 keeps final WordPress execution outside Core for "the current stage"
and requires a future ADR before any Core execution surface is added. That
phrasing left an open-ended question: every planning session had to relitigate
when "the current stage" ends.

The preconditions ADR-003 named are now largely covered without Core owning
execution:

- per-action fingerprints, idempotency keys, and `record-execution` binding
  are covered by tests;
- the Adapter executes approved abilities through the WordPress Abilities API
  after Core commit preflight, with allowlisted execution profiles and audit;
- Core records Adapter-owned execution outcomes as proposal lifecycle status.

The current execution consumer count is one (the Adapter). Moving execution
into Core now would concentrate blast radius, duplicate the Abilities API
surface, and buy nothing while a single channel executes.

This ADR converts the open question into named triggers, per the 2026-10
decision briefs (Decision 3).

## Decision
ADR-003 stands unchanged until one of the following triggers fires:

1. A second execution surface ships (a channel adapter other than
   Npcink AI Client Adapter executes approved abilities in production).
2. The unattended local automation runtime named by ADR-007
   (`npcink-local-automation-runtime`) becomes a real, released consumer.
3. A consumer demonstrates a hard requirement that Core itself execute under
   audit, which Adapter-style execution cannot meet.

When a trigger fires, a new ADR must be opened that defines the execution
contract items ADR-003 listed (allowed ability classes, authorization and
approval-context binding, idempotency and replay, timeout/retry/rollback and
partial failure semantics, destructive action rules, execution result audit
schema, sensitive read-result redaction, and WordPress Abilities API
compatibility). Until such an ADR is accepted, no Core `/execute`,
`/proxy-execute`, final commit route, workflow queue, or generic ability
runtime may be added.

## Trigger Review Checkpoint

Triggers only matter if someone checks them. At every Core release closeout
(see `docs/release-closeout-standard.md`) and every cross-repo release
acceptance run, the owner records one checkpoint line in the closeout
evidence:

- the current count of production execution consumers that execute approved
  abilities after Core commit preflight (as of this ADR: 1, Npcink AI Client
  Adapter);
- whether `npcink-local-automation-runtime` has shipped a real release;
- whether any consumer has stated a hard audit requirement that Adapter-style
  execution cannot meet.

If any answer changes the trigger state (consumer count above 1, the runtime
released, or a hard requirement stated), the closeout stops there: the
successor ADR is opened before release work continues. The checkpoint is a
review step recorded by the owner, not a runtime feature: Core adds no code
for it.

## Alternatives Considered

### Reaffirm ADR-003 unchanged

Pros:

- No new document.

Cons:

- Leaves "current stage" undefined, so the ownership question keeps reopening.

Rejected because the open question is more expensive than the answer.

### Move execution into Core now

Rejected for the same reasons ADR-003 recorded: Core would become a second
ability runtime and concentrate governance and blast radius in one component
while a single channel executes.

## Consequences
- Core keeps returning `commit_execution=false` and
  `execution_handoff.executor=adapter_after_core_preflight`.
- Planning sessions stop relitigating execution ownership; they check the
  trigger list.
- Adapter and product plugins keep composing approve-and-execute flows through
  Core governance plus WordPress Abilities API execution.
- Trigger 1 or 3 firing requires opening the successor ADR before any
  execution work starts.
