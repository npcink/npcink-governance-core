# Reference Plugin Benchmark

Status: advisory benchmark for future product and architecture planning.

Authority note: Cross-project reference learning and platform coordination now
start from `/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`.
This Core document is retained as advisory context, not as a platform
implementation plan or dependency decision.

Date: 2026-07-06.

This note records mature WordPress plugins and platform projects that overlap
with parts of the Npcink stack. It is a learning map, not an implementation
plan and not a dependency decision.

A deeper module-by-module research pass is recorded in
[Reference Plugin Deep Dive - 2026-07-06](reference-plugin-deep-dive-2026-07-06.md).

## Purpose

The Npcink stack should not rebuild mature WordPress patterns in isolation.
Before adding governance UI, ability packaging, connector surfaces, local
runtime behavior, or AI-assisted product flows, compare the proposed work
against existing plugins that have already solved similar operator problems.

The benchmark must still preserve the current ownership split:

| Project | Current responsibility |
| --- | --- |
| `npcink-governance-core` | Proposal records, approval, commit preflight, sensitive-read authorization, app keys, and audit truth. |
| `npcink-abilities-toolkit` | Reusable WordPress Abilities API definitions, schemas, callbacks, and dry-run previews. |
| `npcink-ai-client-adapter` | Thin channel layer for AI clients that call Core and WordPress Abilities API. |
| `npcink-workflow-toolbox` | Fixed, review-only local operator workflows and planning artifacts. |
| `npcink-cloud-addon` | Thin local connector to Npcink AI Cloud runtime and read-only projections. |
| `npcink-ai-cloud` | Hosted runtime, provider routing, run evidence, usage, entitlement, and commercial service surfaces. |

## Benchmark Shortlist

| Reference | Why it matters | Learn from it | Do not copy into Core |
| --- | --- | --- | --- |
| [PublishPress Revisions](https://wordpress.org/plugins/revisionary/) and [PublishPress Revisions docs](https://publishpress.com/revisions/) | Mature editorial approval for changes to published WordPress content. | Review queue language, approve/reject/schedule affordances, revision preview and comparison, permission-sensitive UX. | Do not make Core an editorial revision workflow or post publishing product. Core proposals may govern post operations, but Core must not own article workflow UX. |
| [WP Activity Log](https://wordpress.org/plugins/wp-security-audit-log/) and [Activity Log](https://wordpress.org/plugins/aryo-activity-log/) | Mature admin-facing audit trails for WordPress changes. | Log list density, event filtering, event detail pages, retention/export concepts, privacy-aware metadata display. | Do not replace Core's append-only governance audit with generic site activity logging. Core logs AI operation lifecycle evidence only. |
| [Action Scheduler](https://actionscheduler.org/) and its [WordPress.org plugin page](https://wordpress.org/plugins/action-scheduler/) | Mature traceable background job queue distributed in WordPress plugins. | Admin screen for scheduled actions, WP-CLI support, claim/runner mental model, retry and failure visibility. | Do not add jobs, leases, retry workers, scheduler truth, or runtime queues to Core, Adapter, Toolbox, or Cloud Addon. This is only a candidate reference for a separately owned local automation runtime. |
| [WordPress AI](https://github.com/WordPress/ai) and [Abilities API](https://github.com/WordPress/abilities-api) | Official direction for AI building blocks, AI Client SDK, connectors, abilities, and user-facing experiments. | Align Toolkit ability schema and discovery posture with the official Abilities API. Keep Cloud Addon connector behavior close to connector approval and request logging patterns. | Do not use the official AI plugin as an excuse to create a second Npcink ability registry, provider key store, model router, or prompt registry in Core. |
| [Uncanny Automator](https://wordpress.org/plugins/uncanny-automator/), [AutomatorWP](https://wordpress.org/plugins/automatorwp/), and [WP Webhooks](https://wordpress.org/plugins/wp-webhooks/) | Mature trigger/action, integration, webhook, and recipe mental models. | External integration language, manifest/connection concepts, operator feedback, tokens/context passed between steps, no-code discoverability. | Do not turn Adapter or Toolbox into a generic workflow builder, recipe executor, or automation marketplace. |
| [AI Engine](https://wordpress.org/plugins/ai-engine/) and [AI Puffer](https://aipower.org/) | Mature WordPress AI product surfaces for content, chat, image, forms, embeddings, credits, and provider integrations. | Editor/admin AI UX patterns, provider status, usage visibility, human-facing result review, source labels. | Do not adopt the broad "all-in-one AI dashboard" shape. Npcink should keep human-reviewed fixed workflows and governed handoffs instead of direct auto-publish or generic chat control of the site. |

## Project-Specific Guidance

### Governance Core

Primary references:

- PublishPress Revisions.
- WP Activity Log.
- Activity Log.

Use these references when improving:

- proposal list density and filters;
- proposal detail timeline;
- approve/reject affordances;
- audit search and event detail;
- retention and export language;
- human-readable actor, object, and action summaries.

Keep these boundaries:

- Core proposals are not post revisions.
- Core approval is not editorial publishing workflow.
- Core audit is not a full WordPress site activity log.
- Core must not schedule, execute, retry, or publish final WordPress writes.

Practical next study:

1. Capture screenshots of each reference queue/log screen.
2. Compare columns against Core proposal and audit list fields.
3. Identify one low-risk admin readability improvement that does not change
   REST, data shape, lifecycle, or execution ownership.

### Abilities Toolkit

Primary references:

- WordPress Abilities API.
- WordPress AI plugin ability and connector experiments.

Use these references when improving:

- ability metadata;
- schema shape;
- permission disclosure;
- dry-run preview conventions;
- discoverability for external agents and provider plugins.

Keep these boundaries:

- Toolkit may define abilities and callbacks.
- Toolkit must not decide final commit authorization.
- Toolkit must not store approval state, audit truth, provider credentials,
  routing policy, prompt material, or workflow runtime state.

Practical next study:

1. Compare Toolkit ability definitions with current WordPress Abilities API
   naming, schema, and metadata expectations.
2. Keep compatibility shims thin and documented.
3. Avoid private projection layers when WordPress Abilities API discovery is
   sufficient.

### AI Client Adapter

Primary references:

- WP Webhooks.
- Uncanny Automator.
- AutomatorWP.

Use these references when improving:

- external connection manifest;
- signed client identity;
- operation feedback;
- error messages that tell the operator what to revise;
- integration docs for AI clients.

Keep these boundaries:

- Adapter is a channel layer.
- Adapter may execute approved allowlisted abilities only after Core approval
  and commit preflight.
- Adapter must not persist approval truth, become a queue, or expose a generic
  approve/reject proxy.

Practical next study:

1. Compare Adapter connection/help surfaces against webhook/automation plugin
   onboarding.
2. Keep the happy path short: discover, read, propose, approve-and-execute
   where allowed.
3. Keep blocked proposal feedback visible and action-oriented.

### Workflow Toolbox

Primary references:

- PublishPress editorial/checklist-style admin surfaces.
- AI Engine and AI Puffer editor/admin AI surfaces.
- Uncanny Automator only as a mental model for fixed actions, not as a product
  target.

Use these references when improving:

- editor sidebar ergonomics;
- fixed-button workflows;
- preview before handoff;
- source labels and evidence display;
- present-admin local consent UX for the narrow accepted exception.

Keep these boundaries:

- Toolbox returns suggestions and planning artifacts.
- Toolbox should not directly update posts, upload media, publish content, or
  bypass governance except documented local-admin-consent exceptions.
- Toolbox should not become a generic workflow builder or broad AI control
  dashboard.

Practical next study:

1. Review editor/admin AI plugin screens for result review and provider/source
   clarity.
2. Keep Npcink fixed workflows narrower than all-in-one AI products.
3. Prefer one reviewed artifact and one clear next action over generic chat or
   freeform automation.

### Cloud Addon

Primary references:

- WordPress AI connector surfaces.
- WP Webhooks connection and delivery diagnostics.
- Activity log plugin filter/detail patterns.

Use these references when improving:

- Cloud connection setup;
- health and entitlement display;
- runtime run detail projection;
- delivery diagnostics;
- connector exposure to WordPress AI.

Keep these boundaries:

- Addon stores and signs Cloud connection settings.
- Addon is not approval truth, proposal truth, billing truth, scheduler truth,
  prompt ownership, model router ownership, or WordPress write ownership.
- Addon buffers transport evidence only where explicitly bounded.

Practical next study:

1. Compare Addon health/runtime screens with connector setup and webhook
   delivery debugging screens.
2. Keep troubleshooting read-only unless a contract explicitly allows a bounded
   retry request owned by Cloud.
3. Avoid local queue or scheduler language outside the accepted transport
   buffers.

### Npcink AI Cloud

Primary references:

- SaaS runtime gateway patterns.
- WordPress AI connector and request logging concepts.
- Action Scheduler only as a contrast for local WordPress queues.

Use these references when improving:

- provider connection testing;
- runtime diagnostics;
- usage and entitlement projections;
- run retention and artifact download;
- evidence output contracts for local governed adoption.

Keep these boundaries:

- Cloud can own hosted runtime execution, provider routing, run evidence,
  usage, entitlement, and commercial service surfaces.
- Cloud must not become a second WordPress control plane, second ability
  registry, second workflow registry, approval store, or final WordPress write
  executor.

Practical next study:

1. Keep Cloud admin/portal surfaces commercial and runtime-oriented.
2. Return evidence and candidates for local review, not WordPress mutations.
3. Keep local plugin ownership for schedule intent, batch policy, approval, and
   final write execution.

## Recommended Benchmark Workflow

Before adding a new capability that overlaps with mature WordPress plugins:

1. Name the owning Npcink module.
2. Name the closest reference plugin or official API.
3. Record the reference's user-facing pattern.
4. Record which parts are reusable as UX or contract ideas.
5. Record which parts would violate Npcink boundaries.
6. Decide whether the change is a doc-only learning item, a UI improvement, a
   contract change, or a new implementation task.
7. If the change touches public REST, lifecycle, data shape, or boundary,
   update the relevant Core docs and tests before implementation closeout.

## Priority Recommendations

1. Study PublishPress Revisions and WP Activity Log before expanding Core admin
   proposal and audit surfaces.
2. Study Action Scheduler before any future local automation runtime work, but
   keep that runtime outside Core, Adapter, Toolbox, and Cloud Addon.
3. Study WordPress AI and Abilities API before changing Toolkit ability
   metadata or Cloud Addon connector exposure.
4. Study WP Webhooks, Uncanny Automator, and AutomatorWP before changing
   external-client onboarding, but reject generic workflow-builder ownership.
5. Study AI Engine and AI Puffer for editor UX and source labeling, while
   keeping Npcink narrower, review-first, and governance-aware.

## Decision Rule

Learning from mature plugins is encouraged. Importing their product boundary is
not.

When a reference plugin's strongest feature requires queues, workflow runtime,
provider credential storage, broad model routing, prompt registries, direct
WordPress writes, or auto-publishing, the right Npcink response is a boundary
note first, not an implementation inside Core.
