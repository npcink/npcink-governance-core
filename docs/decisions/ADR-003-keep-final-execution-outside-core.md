# ADR-003: Keep Final Execution Outside Core For The Current Stage

## Status
Accepted

## Date
2026-06-01

## Context
Magick AI Core now provides the governance loop for AI-assisted WordPress
operations:

- ability intake;
- proposal records;
- approval and rejection;
- commit preflight;
- scoped app-key policy;
- audit records.

The next pressure point is final WordPress mutation execution. Core already
returns approval context and an execution handoff from commit preflight, but it
does not execute the target WordPress ability. Adapter and product plugins can
compose a user-facing approve-and-execute flow by calling Core for governance
and WordPress Abilities API for execution.

Moving final execution into Core now would make Core both the governance
authority and a generic ability runtime. That would require Core to own
authorization binding, idempotency, retries, partial failure, rollback,
sensitive result redaction, execution result audit, and destructive action
semantics for every governed ability.

## Decision
For the current stage, Core remains governance-only.

Core must continue to return:

- `core_proxy_execute=false`;
- `commit_execution=false`;
- `execution_handoff.executor=adapter_after_core_preflight`.

Adapter or product plugins own final WordPress ability execution after Core
approval and commit preflight. They must treat Core approval context as scoped
to the proposal, real `ability_id`, approved input hash, caller/app identity,
correlation id, and policy version.

Final execution inside Core may be reconsidered only through a future ADR that
explicitly defines:

- allowed ability classes;
- authorization and approval-context binding;
- idempotency keys and replay behavior;
- timeout, retry, rollback, and partial failure semantics;
- destructive action rules;
- execution result audit schema;
- sensitive read-result redaction;
- compatibility with WordPress Abilities API and Adapter execution.

Until such an ADR is accepted, no Core `/execute`, `/proxy-execute`, final
commit route, workflow queue, or generic ability runtime may be added.

## Alternatives Considered

### Add final commit execution to Core now

Pros:

- Gives external clients one simpler endpoint.
- Lets Core record execution result audit directly.

Cons:

- Turns Core into a second ability runtime.
- Forces Core to own retry, rollback, timeout, and destructive semantics before
  those contracts are stable.
- Duplicates responsibility already held by WordPress Abilities API and Adapter.

Rejected because it expands Core beyond the governance kernel before the
execution contract is mature.

### Add a read-only proxy first

Pros:

- Makes OpenClaw and similar clients easier to connect.
- Avoids final mutation risk.

Cons:

- Still makes Core a proxy runtime and opens pressure to add write execution.
- Requires read-result redaction and transport/authentication semantics that
  belong in Adapter.

Rejected for the current stage. Adapter should call read abilities directly
through WordPress Abilities API.

### Keep execution in Adapter and product plugins

Pros:

- Preserves Core as the governance source of truth.
- Keeps ability callbacks, permission callbacks, and execution semantics in the
  canonical ability layer.
- Lets productized Adapter own the user-facing approve-and-execute workflow.

Cons:

- Adapter must call both Core and WordPress Abilities API.
- Core audit does not yet include final execution result records.

Accepted. This is the smallest stable boundary for the current stage.

## Consequences

- Core hardens approval context and preflight handoff instead of adding
  execution routes.
- Adapter/product plugins must verify approval context before executing writes.
- Execution result audit remains future contract work, not an implicit Core
  runtime feature.
- Any future Core execution work must supersede this ADR or add a more specific
  accepted ADR.
