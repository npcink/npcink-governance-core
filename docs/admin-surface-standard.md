# Core Admin Surface Standard

Status: active for `Magick AI -> Core`.

## Purpose

The Core admin page is the local governance workbench for WordPress ability
proposals. It helps an administrator review pending proposals, make approval
decisions, inspect commit-preflight readiness, and trace Core audit evidence.

## Default View

The page is split into focused admin tabs:

- `Review Queue`;
- `Governance Audit`;
- `Expired / Archived`.

The default `Review Queue` tab must stay focused on the current governance
queue:

- compact status strip;
- paginated pending proposal review list;
- stale proposal counts that link operators to the expired/archive tab;
- short recent activity disclosure, collapsed by default;
- `Advanced Access` disclosure for low-frequency Core app-key management.

## Detail Views

Proposal detail should be a focused review surface:

- proposal identity and status;
- review context from ability intake and preview metadata;
- approve/reject decision controls for pending proposals;
- raw proposal payload behind a disclosure;
- proposal audit timeline.
- lifecycle controls for expired or archived proposals.

Full `Governance Audit` and `Expired / Archived` belong in dedicated tabs, not
inline on the default workbench. Long lists in `Review Queue`,
`Governance Audit`, `Expired / Archived`, and advanced app-key management must
be paginated.

Core app-key creation is a low-frequency fallback action. It should stay behind
the default workbench's `Advanced Access` disclosure and then behind an
explicit creation disclosure on the advanced access page. It must not appear as
a first-level Core tab.

The full audit table should suppress low-value read/list events by default and
must not render placeholder-only columns such as `- / -`. Optional app, scope,
and correlation metadata belongs in a compact detail cell and should appear
only when it exists.

## Do Not Add

Core admin must not add:

- OpenClaw onboarding or client handoff copy;
- ability definitions or callback test controls;
- cloud connection settings or entitlement controls;
- workflow runtime, queue, batch, MCP, or Agent Gateway control panels;
- provider credentials, prompt/preset settings, or final write execution.

## Verification

Static contracts should keep the page aligned with this standard and preserve
the boundary that Core owns governance records, approval/rejection,
commit-preflight, audit, and fallback app-key management only.
