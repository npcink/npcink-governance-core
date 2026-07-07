# ADR-004: Suite Consolidation And Local Admin Consent

## Status
Accepted

## Date
2026-06-08

## Context
Core, Adapter, Toolbox, and the Abilities Toolkit currently describe separate
runtime responsibilities. That separation protected the rebuild from turning
Core back into a workflow runtime, content product, or generic execution
gateway.

The product pressure is now different: operators should not have to reason
about too many plugins and approval loops when they are already working inside
the WordPress admin. Adapter is mainly the external channel surface, Toolbox is
already a WordPress admin product surface, and Core review exists as the safety
backstop for risky AI-assisted operations.

The important distinction is therefore not "separate plugin means safe" versus
"merged plugin means unsafe." The important distinction is whether a write is
authorized by a present WordPress administrator with enough context, or whether
it is external, batch, automated, destructive, or insufficiently previewed and
therefore needs independent governance review.

## Decision
Npcink may consolidate Core, Adapter, and Toolbox into one product plugin or
suite entry when that improves installation, onboarding, and operator
experience.

Consolidation must not collapse the internal authority boundary:

- the Governance module owns proposal records, approval/rejection,
  commit-preflight authorization, governance app scopes, and audit truth;
- the Adapter module owns external channel routes, OpenClaw-facing connection
  behavior, direct-read ability calls, and post-governance execution profiles;
- Toolbox or other local product modules own WordPress admin product UX,
  operator-facing workflows, product defaults, and single-operation assistance;
- the Abilities layer owns reusable WordPress ability definitions, schemas,
  permission callbacks, previews, and execution callbacks.

The same plugin package may contain those modules, but one module must not treat
co-location as permission to bypass another module's authority.

## Local Admin Consent Model
WordPress admin actions may use local admin consent instead of Core proposal
approval only when all of these are true:

- the user is authenticated in WordPress admin with the required capability;
- the user is present and intentionally clicks the action;
- the final write result, or a sufficiently exact preview, is shown before the
  click;
- the operation affects one bounded object or one tightly scoped visible field;
- the operation does not publish, delete, bulk mutate, silently replace
  high-value state, or run later without the user present;
- the operation is reversible or low-cost to correct;
- the product module records an audit or activity event with actor, source,
  target object, and enough AI suggestion context for later review.

Examples that may use local admin consent:

- selecting one displayed image candidate as a featured image;
- inserting one reviewed AI text suggestion into one draft or editable field;
- applying one displayed SEO title or description to one post;
- updating one media item alt text, title, caption, or description after the
  user sees the proposed values;
- creating a draft from fully displayed content without publishing it.

This is not Core approval. It is a local WordPress admin authorization path for
low-risk, visible, operator-present actions.

Generic AI plugin output inside the WordPress editor is even more direct: when
the AI plugin shows a visible title, excerpt, summary, category, tag, ALT, meta
description, or editing suggestion and the author chooses to insert, save, or
publish through the normal editor flow, that visible editor action is the human
review step. Npcink should not add a Core proposal hop to that native author
workflow. Core governance starts when a separate system asks WordPress to write
on the author's behalf, or when the action is external, automated, batch,
insufficiently previewed, destructive, or otherwise high impact.

## Operations That Still Require Governance Review
Core proposal review remains required for operations that are external,
deferred, broad, destructive, or difficult for the operator to fully inspect at
the moment of action.

Examples:

- external agent or OpenClaw write requests;
- automatic or scheduled AI writes;
- batch article, media, comment, term, SEO, or settings changes;
- publishing, unpublishing, deleting, trashing, or permanent deletion;
- replacing files or overwriting substantial existing content;
- changing slugs, site settings, provider configuration, permissions, or other
  high-impact state;
- any flow where the UI cannot show each affected object and final write before
  the user acts;
- any action that depends on an AI-generated plan with multiple write actions.

Those operations must continue through proposal creation, independent
WordPress-side review, approval or rejection, commit preflight, and audit.
External, automated, batch, destructive, high-impact, or insufficiently
previewed AI writes must not use local admin consent as a shortcut around Core
proposal review.

## Risk Classification
Future product work should classify AI-assisted operations with this ladder:

1. Suggestion only: no WordPress write; no proposal required.
2. Single visible local write: local admin consent may be enough.
3. Single high-impact write: strong local confirmation or Core proposal,
   depending on reversibility and preview completeness.
4. Batch, external, automated, destructive, or insufficiently previewed write:
   Core proposal required.

The classification is based on human presence, preview completeness, blast
radius, reversibility, and source of the request, not only on whether AI was
involved.

## Alternatives Considered

### Keep all plugins separate indefinitely
Pros:

- Preserves current architecture boundaries in the package layout.
- Keeps accidental coupling harder.

Cons:

- Increases installation and onboarding friction.
- Makes the product feel like infrastructure instead of one WordPress AI
  assistant.
- Encourages duplicate menus, health checks, and connection setup.

Rejected as a product packaging rule. Separation remains useful internally, but
it does not need to be exposed as multiple product surfaces forever.

### Require Core approval for every AI-assisted write
Pros:

- Very conservative.
- Creates a complete proposal trail.

Cons:

- Makes simple admin-present actions unnecessarily slow.
- Treats "AI generated" as the only risk signal.
- Degrades ordinary WordPress admin workflows such as choosing one displayed
  image or accepting one field suggestion.

Rejected. Low-risk, single-object, fully previewed admin actions can use local
admin consent with audit.

### Let all WordPress-admin Toolbox actions bypass governance
Pros:

- Fastest operator experience.
- Simple implementation rule.

Cons:

- Allows batch or destructive AI actions to bypass independent review.
- Makes external, deferred, and automated flows too easy to disguise as local
  product actions.
- Weakens the audit and approval story for high-impact operations.

Rejected. WordPress admin context is a strong signal, not a blanket exemption.

## Consequences
Product packaging can move toward one Npcink AI plugin or one suite entry
without undoing the governance model.

Implementation work should add a shared operation-classification helper before
moving write flows across modules. That helper should decide whether an action
is suggestion-only, local-admin-consent eligible, strong-confirmation eligible,
or Core-proposal required.

Docs and tests should stop equating safety with separate plugin packages. They
should instead preserve the stronger rule: approval truth and high-risk review
remain in the Governance module, while local product modules can directly serve
low-risk, visible, operator-present actions.

ADR-001, ADR-002, and ADR-003 remain active. This ADR changes the product
packaging direction and local admin consent policy; it does not allow the
Governance module to become a workflow runtime or generic ability executor.
