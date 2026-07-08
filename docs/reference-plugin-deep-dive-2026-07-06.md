# Reference Plugin Deep Dive - 2026-07-06

Status: decision-prep research, not an implementation plan.

Authority note: Cross-project reference learning and platform coordination now
start from `/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`.
This Core document is retained as research context; product placement and
follow-up ownership should be confirmed from the Toolbox platform index.

This document answers a narrow product question:

> For the current Npcink project family, which capabilities already have
> similar mature WordPress plugin implementations, what should we learn from
> them, and where should we avoid copying their product boundaries?

The answer is yes: most parts of the Npcink stack overlap with established
WordPress patterns. The important distinction is that we should borrow proven
operator patterns, not import broad product ownership.

## Current Project Set

This deep dive uses the current local project names and boundaries:

| Project | Current role | Benchmark question |
| --- | --- | --- |
| `npcink-governance-core` | Governance records, approval, preflight, sensitive read authorization, app keys, and audit truth. | How do mature plugins show review queues, approval state, and audit events without becoming product workflow owners? |
| `npcink-abilities-toolkit` | Reusable WordPress Abilities API definitions, schemas, callbacks, and dry-run previews. | How do official ability/AI building blocks expose actions cleanly without becoming the host governance layer? |
| `npcink-ai-client-adapter` | Thin AI-client channel that calls Core and WordPress Abilities API. | How do webhook and automation plugins explain external connections, payloads, failures, and retries without confusing users? |
| `npcink-workflow-toolbox` | Fixed, review-only local operator workflows and planning artifacts. | How do editorial, SEO, checklist, and AI-assist plugins make suggestions actionable without turning every surface into a generic AI console? |
| `npcink-cloud-addon` | Thin local connector to Npcink AI Cloud runtime and read-only projections. | How do connector and webhook plugins show configuration, health, delivery, and diagnostics while keeping ownership clear? |
| `npcink-ai-cloud` | Hosted runtime, provider routing, run evidence, usage, entitlement, and commercial service surfaces. | How do hosted runtimes expose run/provider/usage evidence without becoming a second WordPress control plane? |

## Executive Recommendation

Do this first:

1. Use this deep dive as a decision gate before new feature work.
2. Pick one reference plugin per Npcink module and inspect its admin UX before
   implementing related UX.
3. Convert observations into small, local design improvements only when they
   reduce operator confusion.

Do not do this yet:

1. Do not add Action Scheduler, queues, workers, leases, or retry state to Core,
   Adapter, Toolbox, or Cloud Addon.
2. Do not build a generic workflow builder because Automator plugins have one.
3. Do not build an all-in-one AI dashboard because AI plugins have one.
4. Do not let Cloud or Cloud Addon own WordPress approval, proposal, schedule,
   or write truth.

The first practical outcome should be a set of screenshot-backed UX notes, not
a new runtime or product feature.

## Similar Capability Matrix

| Npcink capability | Similar mature pattern | Reference plugins/projects | What we can learn | Boundary we must keep |
| --- | --- | --- | --- | --- |
| Proposal review queue | Editorial revision approval and status queues. | PublishPress Revisions, PublishPress Statuses. | Clear queue columns, before/after review language, approve/reject/schedule action placement, role-sensitive actions. | Core proposals are governance records, not post revisions or editorial publishing workflow. |
| Audit timeline and event search | Security/activity logs. | WP Activity Log, Activity Log. | Dense event tables, event details, actor/object/action summaries, filters, retention/export language. | Core audit stays AI-operation lifecycle evidence, not a full site activity log. |
| Future local automation runtime | Scheduled/background action queues. | Action Scheduler. | Admin visibility, WP-CLI commands, status vocabulary, failed action inspection, runner health. | Only a separately owned local automation runtime may consider this. Core/Adapter/Toolbox/Addon must not add job ownership now. |
| Ability catalog and schemas | Official AI and Abilities API surfaces. | WordPress AI, WordPress Abilities API. | Ability naming, JSON-schema discipline, connector approval, request logging, standard discovery surfaces. | Toolkit defines abilities; Core governs; neither should become provider routing or prompt ownership. |
| External AI client channel | Webhook/automation integrations. | WP Webhooks, Uncanny Automator, AutomatorWP. | Connection setup, manifest language, payload preview, trigger/action mental model, operator-facing failure feedback. | Adapter is not a workflow builder, recipe executor, retry queue, or approval store. |
| Fixed operator workflows | Checklist, SEO/editor analysis, and AI-assist surfaces. | PublishPress Checklists, Yoast SEO, Rank Math, AI Engine, AI Puffer. | Show suggestions where the operator works, keep next action obvious, label sources, avoid hidden writes. | Toolbox returns suggestions/plans and narrow approved exceptions. It must not become broad auto-publish AI. |
| Cloud connection and diagnostics | Connector setup and delivery diagnostics. | WordPress AI connectors, WP Webhooks delivery logs, Activity Log detail pages. | Configuration status, health checks, signed delivery evidence, last-run summaries, user-readable errors. | Cloud Addon is a connector and projection layer, not billing truth, scheduler truth, approval truth, or write owner. |
| Hosted runtime and usage evidence | SaaS runtime gateway and metering surfaces. | WordPress AI connector concepts, managed AI platform run logs, Action Scheduler only as a contrast. | Provider test results, run states, usage/entitlement clarity, artifact retention language, diagnostics. | Cloud may own hosted runtime truth, but not local WordPress control-plane truth. |

## Module Deep Dives

### 1. Governance Core

Closest mature implementations:

- PublishPress Revisions for review queue and change approval.
- WP Activity Log and Activity Log for event audit and inspection.

Existing overlap:

- Core already has proposal list/detail, approve/reject, commit preflight, app
  attribution, audit filters, and audit timelines.
- The overlap is UX and operator comprehension, not data ownership.

Borrow:

- Queue table clarity: status, submitted by, target object, risk, age, and next
  action should be visible without opening every row.
- Detail page framing: separate request summary, preview/diff, approval state,
  audit trail, and final handoff state.
- Audit language: use actor/object/action/time/correlation id consistently.
- Retention language: make it clear what stays as governance evidence and what
  can be cleaned up.

Do not borrow:

- PublishPress's editorial workflow ownership.
- Generic site activity logging as a replacement for Core's lifecycle audit.
- Scheduled publishing, revision autosave, or post state ownership.

Decision gate before Core admin work:

1. Does this make proposal/audit review clearer?
2. Does it avoid changing REST, data shape, lifecycle, and execution ownership?
3. Does it preserve Core as governance-only?

If all three are yes, it is a good Core improvement candidate.

### 2. Abilities Toolkit

Closest mature implementations:

- WordPress Abilities API.
- WordPress AI plugin experiments around AI Client SDK, connectors, and request
  logging.

Existing overlap:

- Toolkit already packages abilities, schemas, permissions, dry-run previews,
  and host-governed callbacks.
- The official projects show the direction of travel for standard WordPress AI
  primitives.

Borrow:

- Ability metadata discipline: names, descriptions, categories, input/output
  schema, and permission disclosure should be boring and predictable.
- Callback posture: reads and dry-runs must be easy for hosts and agents to
  reason about.
- Standard discovery: prefer WordPress Abilities API surfaces where possible.

Do not borrow:

- Provider key storage.
- Model routing.
- Prompt registry.
- Approval state or audit truth.
- A parallel canonical ability registry that competes with WordPress Abilities
  API.

Decision gate before Toolkit work:

1. Is this a reusable WordPress ability or dry-run preview?
2. Is final authorization still owned by the host/Core?
3. Can a third-party provider follow the same shape?

If not, the work probably belongs outside Toolkit.

### 3. AI Client Adapter

Closest mature implementations:

- WP Webhooks for external event/action transport and delivery diagnostics.
- Uncanny Automator and AutomatorWP for integration onboarding and action
  mental models.

Existing overlap:

- Adapter already exposes a productized channel for AI clients to discover,
  read, propose, approve-and-execute where allowed, and receive operator
  feedback.

Borrow:

- Connection manifest language: tell the external client what it may do and
  which dependencies are ready.
- Payload preview and validation feedback: blocked requests should explain what
  to revise, not just return a generic failure.
- Delivery/failure ergonomics: use stable ids, correlation ids, and readable
  next-action messages.

Do not borrow:

- Recipe builders.
- Generic trigger/action marketplaces.
- Unattended retry queues.
- Adapter-owned approval state.
- A broad approve/reject proxy disconnected from Core.

Decision gate before Adapter work:

1. Does this make an external AI client safer or easier to connect?
2. Does it still call Core and WordPress Abilities API rather than replacing
   them?
3. Does it avoid workflow runtime ownership?

### 4. Workflow Toolbox

Closest mature implementations:

- PublishPress Checklists for pre-publish operator guidance.
- Yoast SEO and Rank Math for editor-side analysis and suggestions.
- AI Engine and AI Puffer for AI result review and provider/source UX.

Existing overlap:

- Toolbox already owns fixed review-only buttons, editor content support, site
  check, image candidates, article audio candidates, and governed handoff
  plans.

Borrow:

- Put recommendations close to the editing context.
- Make source/evidence visible before the operator acts.
- Prefer checklists and fixed buttons over generic freeform AI control.
- Keep one clear next action per recommendation.
- Make disabled/blocked states useful instead of opaque.

Do not borrow:

- Broad all-in-one AI dashboard scope.
- Chatbot as the primary site-control surface.
- Auto-publish content generation.
- Bulk writing or media import without Core proposal review.
- Generic workflow builder UX.

Decision gate before Toolbox work:

1. Is this a fixed operator workflow?
2. Does it return suggestions or a governed plan rather than silently writing?
3. Is the operator's next action obvious?
4. Does the flow avoid becoming a generic AI console?

### 5. Cloud Addon

Closest mature implementations:

- WordPress AI connector configuration surfaces.
- WP Webhooks connection and delivery diagnostics.
- Activity-log-style detail views for read-only troubleshooting.

Existing overlap:

- Cloud Addon stores Cloud connection settings, signs runtime requests, probes
  connectivity, exposes runtime projections, transports bounded observability,
  and presents read-only Cloud run detail.

Borrow:

- Configuration readiness checklist.
- "Last successful request" and "last failed request" diagnostics.
- Clear separation between local connector status and remote service status.
- Read-only delivery/run detail with correlation ids.
- User-facing error mapping that names what to fix next.

Do not borrow:

- Local billing truth.
- Local scheduler truth.
- Local retry queues except explicitly bounded transport buffers.
- Approval/proposal truth.
- Generic provider proxy behavior.

Decision gate before Cloud Addon work:

1. Is this local connector UX or transport evidence?
2. Is Cloud still the runtime owner and Core/local modules still the governance
   and write owners?
3. Does it avoid local queue/scheduler/control-plane ownership?

### 6. Npcink AI Cloud

Closest mature implementations:

- Hosted AI runtime gateways and provider operation dashboards.
- WordPress AI connector/request logging concepts.
- Action Scheduler only as a useful contrast for local WordPress job
  visibility, not as a Cloud design target.

Existing overlap:

- Cloud already owns hosted runtime/profile/catalog/routing/provider surface,
  run evidence, usage, entitlement, provider tests, and runtime diagnostics.

Borrow:

- Provider connection test UX.
- Run status and result detail.
- Usage ledger and entitlement clarity.
- Artifact retention and download semantics.
- Boundary copy that explains what Cloud can and cannot do.

Do not borrow:

- WordPress approval/proposal ownership.
- WordPress schedule ownership.
- Local workflow registry.
- Local ability registry.
- Final WordPress write execution.

Decision gate before Cloud work:

1. Is this runtime/provider/commercial evidence?
2. Does WordPress remain the local control plane?
3. Does the result return evidence/candidates rather than local mutations?

## What To Do Next

The next step is not feature implementation. The next step is a focused
benchmark pass:

| Step | Output | Owner repo |
| --- | --- | --- |
| Capture reference screenshots for Core queue/audit patterns. | [Core Admin Reference Notes - 2026-07](core-admin-reference-notes-2026-07.md). | `npcink-governance-core` |
| Compare Toolkit ability metadata against current official Abilities API shape. | Toolkit compatibility note. | `npcink-abilities-toolkit` |
| Compare Adapter onboarding against WP Webhooks/Automator connection language. | Adapter connection UX checklist. | `npcink-ai-client-adapter` |
| Compare Toolbox editor/sidebar flows against checklist/SEO/AI-assist plugins. | Toolbox fixed-button UX checklist. | `npcink-workflow-toolbox` |
| Compare Cloud Addon troubleshooting against connector/delivery diagnostics. | Cloud Addon diagnostics checklist. | `npcink-cloud-addon` |

Only after those notes exist should we choose a product implementation slice.
If a proposed slice cannot point to a benchmark note, it is likely too early.

## Worth-It Test

A benchmark-derived change is worth doing when it:

- reduces operator confusion;
- reuses an established WordPress mental model;
- keeps authority boundaries unchanged;
- avoids new dependencies unless there is a separate accepted ADR;
- can be verified with the owning repo's existing gate.

A benchmark-derived change is not worth doing when it:

- adds a new queue, worker, scheduler, lease, retry table, or workflow runtime
  to a module that does not own runtime;
- turns fixed workflows into a generic workflow builder;
- turns suggestions into silent writes;
- moves provider keys, model routing, prompt ownership, approval truth, or
  write truth into the wrong module;
- requires users to understand more surfaces before receiving value.

## Source List

Primary references used for this pass:

- [PublishPress Revisions](https://publishpress.com/revisions/)
- [PublishPress Revisions on WordPress.org](https://wordpress.org/plugins/revisionary/)
- [WP Activity Log](https://wordpress.org/plugins/wp-security-audit-log/)
- [Activity Log](https://wordpress.org/plugins/aryo-activity-log/)
- [Action Scheduler](https://actionscheduler.org/)
- [Action Scheduler on WordPress.org](https://wordpress.org/plugins/action-scheduler/)
- [WordPress AI](https://github.com/WordPress/ai)
- [WordPress Abilities API](https://github.com/WordPress/abilities-api)
- [WP Webhooks](https://wordpress.org/plugins/wp-webhooks/)
- [Uncanny Automator](https://wordpress.org/plugins/uncanny-automator/)
- [AutomatorWP](https://wordpress.org/plugins/automatorwp/)
- [PublishPress Checklists](https://wordpress.org/plugins/publishpress-checklists/)
- [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/)
- [AI Engine](https://wordpress.org/plugins/ai-engine/)
- [AI Puffer](https://aipower.org/)
