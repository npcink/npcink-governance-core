# Project History Summary

Status: local handoff summary.

Last updated: 2026-06-18.

This document summarizes the working history that led
`npcink-governance-core` to its current stage. It is not a replacement for the
ADRs, contracts, or test strategy. Use it as the short orientation document
before deciding what to build next.

## Current Position

`npcink-governance-core` is the local WordPress AI operation governance control
plane. It classifies, records, approves, preflights, and audits AI-initiated
site operations.

Core does not plan operations, execute writes, route models, run workflows, own
MCP transport, own Agent Gateway catalogs, store provider credentials, or own
product UX. Those boundaries are now repeated across README, ADRs, contract
docs, static contracts, and smoke tests because most historical risk came from
Core drifting toward executor or product-surface ownership.

## Main Decisions So Far

### Governance kernel, not executor

ADR-001 rebuilt Core as a governance layer. ADR-002 and ADR-003 kept workflow
runtime and final commit execution outside Core. The current route set can
create proposals, approve or reject them, run commit preflight, record
Adapter-owned execution outcomes, manage app-key access, authorize sensitive
reads, and expose audit evidence. It still does not execute final WordPress
writes.

### Core remains independent

ADR-005 keeps Core independent while shared channel-adapter contracts stabilize.
OpenClaw Adapter is the first channel adapter, not the default owner of every
future channel. Future MCP, browser-agent, cloud-agent, local automation, and
agency adapters should consume Core contracts instead of inheriting
OpenClaw-specific assumptions.

### Local admin consent is narrow

ADR-004 allows present-admin, low-risk, single-object local consent with audit,
but this path is deliberately narrow. External, automated, batch, destructive,
high-impact, insufficiently previewed, or broad-scope writes still require Core
proposal review.

The current operation classifier implements:

- `suggestion_only`
- `local_admin_consent`
- `strong_local_confirmation`
- `core_proposal_required`

The classifier is intentionally side-effect free. Callers must persist the
decision envelope in proposal preview, local consent audit, or product activity
evidence before they report execution or rejection as final.

### Runtime ownership moved out of Core

ADR-006 and ADR-007 stopped unattended batch automation from being implemented
inside Core or the OpenClaw Adapter. The future owner is
`npcink-local-automation-runtime`. Core no longer keeps future runtime schema
or replay fixtures locally; those artifacts belong in the future runtime owner
repo or isolated runtime module.

The future runtime may be bundled into Toolbox for distribution only if it
keeps its own namespace, table prefix, capabilities, kill switch, tests, and
boundary docs.

## Implemented Baseline

Core has implemented:

- plugin activation and custom governance tables;
- ability intake from `npcink-abilities-toolkit`;
- proposal create/list/detail, approval, rejection, commit preflight, and
  execution-result recording;
- audit list and filters;
- app-key authentication, scopes, rate limiting, revocation, expiry field,
  last-used evidence, and scope-decision audit attribution;
- sensitive read request lifecycle with approval, bounded read preflight,
  one-time consumption, and no raw sensitive payload persistence;
- admin review queue and proposal detail surfaces focused on governance
  evidence, not content production;
- `/contract` metadata discovery for Adapter/runtime compatibility checks;
- site and signed-client-fingerprint binding in Core-issued preflight
  contexts;
- static contracts, fail-closed fault injection, and real WordPress smoke
  gates.

## Plan-To-Proposal History

Core's plan-to-proposal bridge started as a way to convert reviewed dry-run
plans into proposal records without letting Core become a planner. Its current
rule is deterministic mapping and validation only.

Supported handoff families include content inventory cleanup, nonproduction
cleanup, media inventory and reference repair, article draft handoff, article
batch draft handoff, media optimization, media adoption enhancement, media
rename, pattern page plans, article block plans, block theme template layout
plans, content metadata apply plans, Site Knowledge review plans, and Nightly
Inspection / Morning Brief review plans.

All of these routes preserve the same boundary:

- Core stores reviewable proposal truth and audit evidence.
- Core keeps inputs dry-run and non-commit.
- Core rejects unsupported artifact types, target abilities, broad action
  counts, unsafe write actions, missing human input, and dangerous plan shapes.
- Adapter or product modules execute only after Core approval and commit
  preflight.

## Product Proofs And Their Status

### Media optimization

Media optimization is now a regression-owned cross-repo path, not a new Core
feature line. Core preserves plan intake, batch review summary, approval,
preflight, and audit. Adapter owns readiness and execution handoff. Abilities
own local write verification and restore behavior. Cloud Addon must stay
runtime/detail only.

### Content Metadata Delta

Content Metadata Delta is the next practical product proof, but not a Core
product feature. Core already has a bounded `content_metadata_apply_plan`
handoff contract: one target post, at most one excerpt action, at most one
category assignment, at most one tag assignment, existing term ids only,
`create_missing=false`, dry-run only, and no title/content/SEO writes.

Still missing outside Core:

- Toolbox generates the actual `content_metadata_delta`;
- product UI lets a present admin review accepted choices;
- Adapter or host applies approved changes;
- post-apply checks and learning evidence are recorded.

### Block theme layouts

Block theme layout intake was narrowed repeatedly because it is the easiest
path to accidentally become a site builder. Core now requires versioned
profile compiler evidence, safe block policy evidence, template allowlists,
block allowlists, bounded block count/depth/attribute size, roundtrip evidence,
and malicious fixture rejection. Core still does not edit navigation, global
styles, `theme.json`, theme files, or final template content.

## Admin Surface History

The admin UI evolved from a dense review surface into a review workbench:

- review queue pagination and bulk rejection controls were tightened;
- settings and app-key management moved away from the first review scan;
- proposal detail was split into Overview, Action plan, Audit evidence, and
  Technical info;
- proposal identity, source, policy, audit, and raw payload evidence were
  grouped;
- pending approve/reject controls now live inside the top summary panel as a
  decision slot;
- zero evidence states render as `No risk signals` instead of undeclared risk
  plus empty counters.

These changes are presentation-only. They do not change proposal lifecycle,
approval/rejection semantics, commit preflight, audit truth, Adapter behavior,
or final execution.

## Current Verification Model

The default gate is:

```bash
composer test:all
```

This runs PHP lint, static contracts, and fail-closed fault injection.

Use WordPress smoke when changes affect activation, tables, REST dispatch,
WordPress capabilities, app auth behavior, proposal/preflight behavior,
`npcink-abilities-toolkit` integration, or admin behavior that needs runtime
confidence:

```bash
composer smoke:wp
```

The current hardening matrix should drive future Core work:

- proposal state transition matrix;
- commit preflight race and duplicate handoff;
- ability schema, permission, risk, or fingerprint drift;
- app-key scope isolation;
- redaction before proposal or audit persistence;
- sensitive read one-time consumption;
- block theme malicious fixtures;
- from-plan static contracts;
- audit completeness.

## What Is Intentionally Not Done

These omissions are intentional unless a new ADR changes the boundary:

- Core final commit execution;
- Core workflow runtime, queues, schedulers, workers, leases, retries, or
  dead-letter processors;
- generic MCP runtime or Agent Gateway catalog ownership;
- model routing, provider-key storage, prompt/preset truth, or billing truth;
- content generation, SEO product UX, article workbench UX, or media product
  workflow UX;
- app-key rotation automation before a real long-lived external client requires
  it;
- unattended approval or broad auto-approval policy.

## Recommended Next Moves

1. Keep Core focused on hardening, not new product surface.
2. Start with the proposal state transition matrix or commit-preflight race /
   duplicate handoff tests.
3. Move Content Metadata Delta product proof work to Toolbox, Adapter, and
   Abilities. Core should only accept reviewed apply plans and evidence.
4. Start `npcink-local-automation-runtime` only as Phase 1 schema/replay
   validation. Do not implement workers, schedulers, job tables, leases,
   retries, dead-letter processing, unattended approval, or final writes in the
   first runtime pass.
5. Treat media optimization and block theme layout as regression-owned paths:
   fix contract failures, but do not expand Core into execution or product
   generation.

## Practical Start Point For A New Agent

Before editing, read:

- `README.md`
- `.sisyphus/session-breadcrumb.md`
- `docs/next-stage-plan.md`
- `docs/operation-classification-contract.md`
- `docs/testing-strategy.md`
- relevant ADRs under `docs/decisions/`

Then run:

```bash
git status --short --branch
```

For the next Core-only slice, prefer a small fail-closed hardening PR with
`composer test:all` as the minimum gate. Add `composer smoke:wp` when the slice
touches WordPress runtime behavior.
