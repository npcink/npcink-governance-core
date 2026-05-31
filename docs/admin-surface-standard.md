# Core Admin Surface Standard

Status: active for `Magick AI -> Core`.

## Purpose

The Core admin page is the local governance workbench for WordPress ability
proposals. It helps an administrator review pending proposals, make approval
decisions, inspect commit-preflight readiness, and trace Core audit evidence.

## Default View

The default page must stay focused on the current governance queue:

- compact status strip;
- pending proposal review list;
- short recent activity list;
- explicit links to low-frequency administration views.

## Detail Views

Proposal detail should be a focused review surface:

- proposal identity and status;
- review context from ability intake and preview metadata;
- approve/reject decision controls for pending proposals;
- raw proposal payload behind a disclosure;
- proposal audit timeline.

Full `Governance Audit` and `Core App Keys` belong in dedicated low-frequency
views, not inline on the default workbench.

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
