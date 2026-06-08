# ADR-005: Keep Core Independent And Standardize Channel Adapters

## Status
Accepted

## Date
2026-06-08

## Context
ADR-004 allows Npcink to consolidate product packaging when a single suite
entry improves installation and operator experience. That packaging option
should not be mistaken for the immediate implementation plan.

Core is currently used mainly by the OpenClaw Adapter, but OpenClaw is not the
only plausible agent/channel shape. Future integrations may need MCP adapters,
browser-agent adapters, cloud-agent adapters, local automation adapters, agency
adapters, or other client-specific channels. If Core is merged into today's
Adapter too early, the next channel will either duplicate governance behavior
or inherit OpenClaw-specific assumptions.

Toolbox is different from an external channel adapter. It runs inside the
WordPress admin and can use local admin consent for low-risk, single-object,
fully previewed actions. That does not remove the need for Core proposal review
when a Toolbox or agent flow becomes batch, external, automated, destructive,
high-impact, or insufficiently previewed.

## Decision
Do not merge Core and Adapter as the next implementation step.

Keep `npcink-governance-core` independent as the governance kernel. Treat
OpenClaw Adapter as the first channel adapter, not the only or canonical shape
for all future channels.

The next work is:

1. Define a shared operation classification contract.
2. Use that contract in product and adapter planning to decide between:
   suggestion-only, local admin consent, strong local confirmation, and Core
   proposal review.
3. Validate the contract with one low-risk Toolbox local-admin-consent scenario
   and one high-risk Core-proposal scenario.
4. Standardize the common channel adapter contract before creating additional
   adapters.

Suite packaging, shared menu entries, health checks, and installer guidance may
still improve the product experience, but runtime ownership stays separated
until the adapter family and operation classification rules prove stable.

## Operation Classification Requirement
Every AI-assisted write path should be classified before implementation:

- `suggestion_only`: no WordPress write; no Core proposal required.
- `local_admin_consent`: a present WordPress administrator sees one bounded
  final result and intentionally applies a low-risk single-object change;
  no Core proposal required, but audit/activity logging is required.
- `strong_local_confirmation`: a present WordPress administrator sees a
  high-impact single-object result; use a stronger confirmation or Core
  proposal depending on reversibility and preview completeness.
- `core_proposal_required`: external, automated, batch, destructive,
  high-impact, or insufficiently previewed writes; Core proposal, approval,
  commit preflight, and audit are required.

Classification must consider:

- request source: WordPress admin UI, external adapter, scheduled task, CLI, or
  cloud callback;
- actor presence: present user click versus background execution;
- preview completeness: exact final result, partial preview, or no preview;
- blast radius: one field, one object, multiple objects, site-wide state, or
  external account state;
- reversibility: easy undo, backup-backed restore, hard restore, or
  irreversible;
- operation type: create draft, update metadata, publish, delete, replace file,
  settings change, permission change, or batch plan.

## Required Scenario Proofs
The first low-risk proof should be a Toolbox-style local admin consent scenario:
for example, selecting one displayed image candidate as the featured image for
one post. The product surface must show the candidate and target post before
the click, require a present WordPress administrator with the needed
capability, execute only one bounded write, and record audit/activity evidence.
It should not create a Core proposal.

The first high-risk proof should be a contrasting Core-proposal scenario: for
example, batch image selection, batch SEO updates, or batch article edits. The
flow must create a Core proposal, preserve enough preview detail for review,
require approval and commit preflight, and keep final execution outside Core.

These proofs are acceptance examples for the shared classification contract.
They should not become private special cases that bypass the classification
rules.

## Channel Adapter Contract
All channel adapters must follow the same governance rules:

- discover or present real WordPress `ability_id` values;
- call read-only abilities through the proper ability surface;
- create Core proposals for proposal-required writes;
- never store proposal or approval truth as adapter-private state;
- never expose generic approve/reject proxy routes to untrusted clients by
  default;
- execute writes only after Core approval and commit preflight, and only when
  the execution profile is explicit;
- preserve Core audit attribution, app identity, caller identity, proposal id,
  and correlation id.

OpenClaw Adapter is one implementation of this contract. Future MCP, browser,
cloud, or local automation adapters should implement the same contract rather
than forking Core behavior.

## Alternatives Considered

### Merge Core into OpenClaw Adapter now
Pros:

- Reduces plugin count for the current known consumer.
- Makes one product entry easier to explain.

Cons:

- Couples governance to the current OpenClaw channel shape.
- Makes future adapter styles inherit OpenClaw-specific assumptions.
- Encourages duplicated governance or adapter-private approval behavior when
  new channel types arrive.

Rejected for the current stage.

### Keep Core independent forever with no shared adapter standard
Pros:

- Maximum plugin separation.

Cons:

- Each adapter may invent slightly different proposal, approval, preflight, and
  audit handling.
- Future channels become harder to compare and certify.

Rejected. Core should stay independent, but adapters need a common contract.

### Implement low-risk Toolbox bypasses first
Pros:

- Quickly improves operator experience.

Cons:

- Risks creating ad hoc bypass rules before the classification model is stable.
- Makes later audits harder if each product flow defines consent differently.

Rejected as the first step. Define the classification contract first, then
prove it with low-risk and high-risk scenarios.

## Consequences
The current near-term plan is contract-first, not merge-first.

ADR-004 remains valid as a packaging option. ADR-005 narrows the current
implementation sequence: keep Core independent, standardize channel adapters,
and add operation classification before moving specific flows to local admin
consent or Core proposal paths.
