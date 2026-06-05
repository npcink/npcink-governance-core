# ADR-002: No Workflow Runtime In Core

## Status

Accepted.

## Date

2026-05-29

## Context

The old Npcink Governance Core exposed product workflows such as article generation,
article optimization, media ALT handling, comment moderation, maintenance, and
batch operations through workflow definitions and skill manifests.

The rebuilt Core has a different responsibility: it governs AI-assisted
WordPress operations requested by agents, tools, and product plugins.

`npcink-abilities-toolkit` owns reusable WordPress atomic abilities. Product plugins
such as Content Assistant own domain UX and host workflows.

## Decision

`npcink-governance-core` must not implement a workflow runtime.

Core may:

- discover abilities;
- record proposals;
- approve or reject proposals;
- prepare future approval-commit context;
- audit lifecycle events.

Core must not:

- register `workflow/*` runtime definitions;
- own skill manifests;
- execute queues, retries, leases, or batch jobs;
- project Agent Gateway or MCP tools;
- own article/media/comment/SEO/toolbox product flows.

## Alternatives Considered

### Keep a minimal workflow runner

Pros:

- product plugins could delegate more logic to Core;
- some old contracts could be reused.

Cons:

- unclear boundary between governance and execution;
- risk of rebuilding Agent Gateway/Open Platform inside Core;
- makes product plugins depend on Core internals.

Rejected because even a minimal runner would push Core away from governance.

### Let Core own documentation-only recipes

Pros:

- helps product implementers understand recommended sequences.

Cons:

- recipes may be mistaken for runtime ownership.

Rejected for Core. Documentation-only recipes belong in
`npcink-abilities-toolkit` when they describe ability composition, or product plugin
docs when they describe product UX.

## Consequences

- Product plugins must submit proposals or future commit requests to Core
  instead of asking Core to run their workflows.
- Ability providers remain independent and usable without Core.
- Core REST and data schema stay focused on proposal, approval, and audit.
- Any future request to add workflow runtime must first supersede this ADR.

