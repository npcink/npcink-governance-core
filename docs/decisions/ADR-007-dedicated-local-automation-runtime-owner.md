# ADR-007: Dedicated Local Automation Runtime Owner And Toolbox Bundling

## Status
Accepted

## Date
2026-06-15

## Context
ADR-006 keeps unattended batch automation out of Core and the OpenClaw Adapter
until a dedicated runtime contract exists. The
[Local Automation Runtime Contract](../local-automation-runtime-contract.md)
now defines the required boundary and runtime semantics, but it intentionally
does not choose an implementation owner.

Without an explicit owner, future work can drift in three bad directions:

- Core quietly grows job tables, scheduler state, leases, retries, or workers.
- The OpenClaw Adapter becomes the default runtime for every future channel.
- Toolbox fixed buttons become hidden unattended automation entrypoints.

The next decision is where Phase 1 should land and how it may be packaged for
operators.

## Decision
Use `npcink-local-automation-runtime` as the dedicated owner for unattended
batch automation runtime implementation.

The owner must be independently developed and independently testable. Product
release packaging may bundle it inside `magick-ai-toolbox` as an isolated
module when that gives operators a simpler install path.

The canonical development identity is:

- repo: `/Users/muze/gitee/npcink-local-automation-runtime`
- plugin slug: `npcink-local-automation-runtime`
- PHP namespace prefix: `Npcink\LocalAutomationRuntime`
- option/table prefix: `npcink_local_automation_runtime`
- contract version: `npcink_local_automation_runtime.v1`

If the runtime is bundled in Toolbox for release, it must retain that identity
inside the package:

- module path: `modules/local-automation-runtime/`
- namespace: `Npcink\LocalAutomationRuntime`
- table prefix: `npcink_local_automation_runtime`
- capability family: `npcink_runtime_*` or `cap.runtime.*`
- contract version: `npcink_local_automation_runtime.v1`
- independent kill switch and runtime health status
- independent tests and boundary docs

Toolbox may be the distribution shell and operator UX host. Toolbox fixed-flow
buttons must not become the runtime state machine, scheduler, lease manager,
retry processor, dead-letter processor, or unattended approval path.

This Core pass does not create the development repository, does not alter
Toolbox packaging, and does not implement runtime code. It records the owner
and packaging decision and provides Phase 1 schema and dry-run replay artifacts
that the future runtime owner must consume.

## Phase 1 Scope
Phase 1 is contract and replay only.

Allowed Phase 1 deliverables:

- local automation runtime schema documentation;
- dry-run replay fixture examples;
- validator tests in the future runtime repo;
- boundary tests proving Core, Adapter, Toolbox, and Toolkit do not own runtime
  state;
- docs for Core proposal/preflight handoff and runtime operational audit.

Forbidden Phase 1 deliverables:

- background workers;
- scheduler registration;
- durable runtime job tables;
- lease stores;
- retry processors;
- dead-letter processors;
- unattended approval;
- automatic publish, destructive execution, or broad settings mutation.

## Development And Packaging Boundary
The future `npcink-local-automation-runtime` development repo may depend on
public Core REST routes, WordPress Abilities API, and Adapter execution
contracts. It must not depend on Core internals or write directly to Core
tables.

If release packaging bundles the runtime in Toolbox, the Toolbox package must
still expose it as an isolated runtime module. The module must not reuse
Toolbox fixed-button state as runtime job state, must not write final
WordPress changes outside WordPress Abilities API, and must not bypass Core
approval and commit preflight for proposal-required writes.

The future runtime repo or module must provide its own `AGENTS.md` boundary
before implementation begins. That boundary must repeat these hard blocks:

- no Core proposal/approval/preflight/audit truth ownership;
- no WordPress writes outside WordPress Abilities API;
- no approval scope by default for unattended app keys;
- no auto-publish or destructive action by default;
- no provider key, prompt/preset, or billing truth ownership.

## Handoff Artifacts
Core keeps planning artifacts only:

- [Local Automation Runtime Contract](../local-automation-runtime-contract.md)
- [Local Automation Runtime Phase 1 Schema](../local-automation-runtime-phase-1-schema.md)
- `tests/fixtures/local-automation-runtime-dry-run-replay.json`

These artifacts are examples and contracts for a future repo. They are not Core
runtime inputs and are not loaded by Core plugin runtime.

## Alternatives Considered

### Implement runtime inside Core
Rejected. This contradicts ADR-002, ADR-003, and ADR-006 by making Core own
jobs, leases, retries, workers, and runtime recovery.

### Implement runtime inside OpenClaw Adapter
Rejected. Adapter would become the default scheduler and worker for every
future channel, which contradicts ADR-005.

### Implement runtime inside Toolbox
Rejected for the current stage. Toolbox owns operator UX and fixed-button
product surfaces, not hidden unattended runtime state.

### Keep owner undecided
Rejected. An undecided owner makes boundary drift likely and prevents useful
Phase 1 schema and replay testing.

### Publish as a completely separate plugin only
Rejected as a product requirement. Separate development is useful for
ownership and tests, but forcing a second operator-installed plugin is not
necessary if Toolbox can bundle the runtime as an isolated module while
preserving runtime boundaries.

## Consequences
Unattended batch automation now has a named future owner without adding runtime
behavior to Core.

Core remains the governance source for proposal, approval, preflight, audit,
and execution-result records. The future runtime must integrate through public
contracts and remain independently testable whether it is shipped as its own
plugin or bundled inside Toolbox.
