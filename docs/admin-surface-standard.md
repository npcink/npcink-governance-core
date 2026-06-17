# Core Admin Surface Standard

Status: active for `Npcink AI -> Core`.

## Purpose

The Core admin page is the local governance workbench for WordPress ability
proposals. It helps an administrator review pending proposals, make approval
decisions, inspect commit-preflight readiness, and trace Core audit evidence.

## Default View

The page is split into focused admin tabs:

- `Review Queue`;
- `Activity Log`;
- `Expired / Archived`;
- `Settings`.

The default `Review Queue` tab must stay focused on the current governance
queue:

- compact status summary for current needs-review, approved, execution-failed,
  and audit-event state;
- lookup and recent activity grouped as secondary utilities below the status
  summary, not as first-level review work;
- paginated pending request list with 10 proposals per page, user-facing request
  labels, compact source summary, compact status, compact age/expiry, and a
  clear review entry;
- review queue pagination and bulk selection should use a WordPress-style table navigation row:
  bulk action controls on the left, item count and square
  first/previous/next/last page buttons on the right;
- default pending rows use a dedicated `Source` column for compact caller/app
  attribution and a stable display id such as `P-1234ABCD-EF90`; full proposal
  id, ability id, app id, and source trace stay behind technical details;
- default pending rows should keep request identity, source attribution, status,
  created time, details, and action columns left-aligned except the final action
  column; display ids should remain single-line and should not repeat a
  `Display ID:` label in the row; source attribution should default to the actor only,
  with app id and source context kept in the details table or title text instead
  of the main row;
- default pending rows must not place the technical detail disclosure inside
  the request column. Use a dedicated `Details` column that toggles an inline full-width key-value details table below the row;
- default pending rows do not render an undeclared-risk badge. Risk appears in
  the list only when the proposal declares risk metadata;
- default pending rows should show remaining time compactly and avoid spelling
  the fixed 24-hour TTL as dominant repeated text on every row;
- read-only lookup that accepts either display id or full `Proposal ID` and
  opens the existing Core proposal detail route without adding Adapter
  execution actions;
- display id visible in each default row as the governance lookup handle;
- full proposal id, ability id, raw source, caller/app attribution, created
  and updated time, and policy fields preserved behind the per-row inline
  technical details table for Adapter/OpenClaw handoff lookup;
- per-row technical details should render as a two-column grouped inspector on
  desktop, with identity/source fields separated from time/policy fields;
  Source should show the raw source value only, while caller type and app id
  remain separate fields to avoid duplicate trace text;
- bounded bulk rejection for selected pending proposals. Because it is a
  low-frequency destructive action, the default JavaScript-enabled view should
  hide it until one or more rows are selected, then show a compact contextual action bar
  with selected count, clear selection, optional rejection note, and
  reject selected action. Keep a collapsed disclosure fallback for no-JavaScript
  admin sessions;
- stale proposals available from the expired/archive tab;
- useful empty state that points to proposal lookup, activity log, and expired
  records instead of only saying that the queue is empty;
- `Settings` tab for low-frequency development policy and trusted client
  access, keeping these controls out of the default review queue;
- `Development Approval Policy` disclosure in the Settings tab for the
  lightweight require approval for all, smart approval, and local-development
  allow-all modes. It should default open because it is the primary setting;
- stale or invalid stored approval policy option values must show an inline
  warning that Core is treating the value as require approval for all until a
  supported mode is saved;
- `Advanced Access` disclosure in the Settings tab for low-frequency client
  access key management. It should default collapsed.

## Detail Views

Proposal detail should be a focused review surface:

- top proposal summary panel with four default blocks: request, status, action count,
  and evidence with warning/blocker counts. Source, full ids, and policy internals stay out of the
  first scan while visual status badges preserve proposal lifecycle state;
- collapsed technical identity inspector with display id, full proposal id,
  target ability, created/updated time, source trace, caller/app attribution,
  and policy fields without repeating the same summary fields as a second
  linear table;
- explicit non-pending outcome notice for approved, executed, rejected,
  expired, archived, or execution-failed proposals so the page explains why
  approve/reject controls are absent;
- batch action table for plan-to-proposal or other multi-action proposal rows,
  showing ordered action id, target ability, readiness, and dependencies while
  keeping final execution outside Core;
- review basis from ability intake and preview metadata rendered as a grouped
  `Ability and policy` inspector. `Preview signals` should appear only when a
  reason, warning, blocked item, required input, or preflight blocker exists;
  otherwise show one concise no-issues line instead of a table of zero values;
- approve/reject decision controls for pending proposals after the review
  context, so the reviewer sees the basis before choosing;
- proposal audit evidence before raw payload, with a compact lifecycle summary
  visible by default and the full audit timeline collapsed for technical
  attribution;
- raw proposal payload behind a final troubleshooting disclosure with bounded code blocks;
- lifecycle controls for expired or archived proposals.

Full `Activity Log` and `Expired / Archived` belong in dedicated tabs, not
inline on the default workbench. Long lists in `Review Queue`, `Activity Log`,
`Expired / Archived`, and advanced app-key management must be paginated.

Core app-key creation is a low-frequency fallback action. It should stay behind
the Settings tab's `Advanced Access` disclosure and then behind an explicit
creation disclosure on the advanced access page. It must not appear as a
first-level Core tab.

The review queue must not remove proposal identity from the page, but the
default row should lead with the user-facing request label. Keep `Proposal ID`
visible as the row's governance lookup handle. Keep ability id and source
metadata behind per-row technical details so operators can still match
Adapter/OpenClaw task status, provider request logs, audit filters, and
proposal detail links. OpenClaw onboarding, client export, and single
approve-and-execute product flow still belong in Magick AI Adapter.

The full activity table should suppress low-value read/list events by default,
use user-facing activity labels instead of raw event names in the main column,
and must not render placeholder-only columns such as `- / -`. Optional actor,
ability, app, scope, and correlation metadata belongs in a compact context cell
or technical filter disclosure and should appear only when it exists.

Admin tab, pagination, detail, archive, and filter links are read-only GET
navigation and must not append a nonce to the URL. Nonces belong on POST forms
that change state, such as approval, rejection, lifecycle actions, policy
updates, app-key creation, and app-key revocation.

## Time Display

Core stores governance proposal, audit, app-key, and rate-limit timestamps as
UTC machine values. Keep those stored values and REST response fields stable.

Any timestamp shown in the Core wp-admin page must be formatted through the
WordPress site timezone as `Y-m-d H:i:s`. Do not print raw UTC strings or
database datetime values directly in the human-facing admin UI unless the label
explicitly describes a machine/debug value.

## Do Not Add

Core admin must not add:

- OpenClaw onboarding or client handoff copy;
- ability definitions or callback test controls;
- cloud connection settings or entitlement controls;
- a general policy rules UI or workflow-style approval configuration center;
- workflow runtime, queue, batch, MCP, or Agent Gateway control panels;
- provider credentials, prompt/preset settings, or final write execution.

## Verification

Static contracts should keep the page aligned with this standard and preserve
the boundary that Core owns governance records, approval/rejection,
commit-preflight, audit, and fallback app-key management only.
