# Core Admin Surface Standard

Status: active for `Npcink AI -> Core`.

## Purpose

The Core admin page is the local governance workbench for WordPress ability
proposals. It helps an administrator review pending proposals, make approval
decisions, inspect commit-preflight readiness, and trace Core audit evidence.
The page title should use the operator-facing module name `Governance Core`
rather than the plugin slug.

## Default View

The page is split into focused admin tabs:

- `Review Queue`;
- `Activity Log`;
- `History`;
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
- stale proposals available from the history tab;
- useful empty state that points to proposal lookup, activity log, and expired
  history instead of only saying that the queue is empty;
- `Settings` tab for low-frequency development policy and trusted client
  access, keeping these controls out of the default review queue;
- `Development Approval Policy` as a directly visible Settings section for the
  lightweight require approval for all, smart approval, and local-development
  allow-all modes. Do not wrap this primary setting in a disclosure;
- stale or invalid stored approval policy option values must show an inline
  warning that Core is treating the value as require approval for all until a
  supported mode is saved;
- `History retention` as a bounded Settings option for the intended historical
  proposal retention window: 90 days, 180 days, 365 days, or no automatic
  deletion. This stores the retention policy only; scheduled cleanup must be a
  separate implementation and must not be implied by the admin control until it
  exists;
- `Advanced Access` disclosure in the Settings tab for low-frequency client
  access token management. It should default collapsed. Use operator-facing token
  language rather than leading with the internal app-key implementation name.

## Detail Views

Proposal detail should be a focused review surface:

- top proposal summary panel with three default blocks: review id, status, and
  evidence with warning/blocker counts. Source, full ids, action counts, long
  summaries, audit event counts, and policy internals stay out of the first
  scan while visual status badges preserve proposal lifecycle state. When
  evidence has no warnings, blocked items, required input, or preflight
  blockers, show one `No risk signals` conclusion instead of undeclared risk
  plus zero counts;
- pending proposals must show decision controls inside the top summary panel,
  as a right-side `Decision` action slot. Do not render a separate empty
  decision bar. `Approve` is the primary always-visible action; rejection is a
  secondary disclosure that reveals the rejection note and confirm action only
  when needed;
- proposal detail tabs after the summary:
  `Overview`, `Action plan`, `Audit evidence`, and `Technical info`. The
  default overview tab keeps the decision context short; action, audit, and
  troubleshooting data move out of the first scan;
- explicit non-pending outcome notice for approved, executed, rejected,
  expired, archived, or execution-failed proposals so the page explains why
  approve/reject controls are absent;
- batch action table in the `Action plan` tab for plan-to-proposal or other
  multi-action proposal rows, showing ordered action id, target ability,
  readiness, and dependencies while keeping final execution outside Core;
- review basis from ability intake and preview metadata rendered in the
  overview tab as a grouped `Ability and policy` inspector. `Preview signals`
  should appear only when a reason, warning, blocked item, required input, or
  preflight blocker exists; otherwise show one concise no-issues line. Keep
  this basis close to the decision bar so the reviewer sees the basis, but it
  must not push the primary approval action below the first screen;
- collapsed technical identity inspector in the `Technical info` tab with
  display id, full proposal id, target ability, created/updated time, source
  trace, caller/app attribution, and policy fields without repeating the same
  summary fields as a second linear table;
- proposal audit evidence in the `Audit evidence` tab, with a compact lifecycle
  summary visible by default and the full audit timeline collapsed for
  technical attribution. Keep audit evidence before raw payload in the
  information architecture even when the two surfaces live in separate tabs;
- raw proposal payload behind a troubleshooting disclosure in the `Technical
  info` tab with bounded code blocks;
- expired or archived proposals should be presented as historical records, not
  as active review work.
- `History` list rows should stay compact and read-only: 10 rows per page,
  user-facing proposal label plus display id in the proposal column, status,
  one combined updated/age column, and a dedicated row `Details` disclosure for
  full proposal id, ability id, source, time, and policy fields. Do not show
  row selection, bulk actions, archive actions, or reopen actions on this page.
  Existing `archived` rows may still appear for backward compatibility, but the
  admin surface should not ask operators to choose between expired and
  archived records.

Full `Activity Log` and `History` belong in dedicated tabs, not
inline on the default workbench. Long lists in `Review Queue`, `Activity Log`,
`History`, and advanced client access token management must be paginated with
the same WordPress-style top and bottom table navigation: current result range
on the left, item count plus square first/previous/next/last page buttons on
the right. Selection checkboxes and bulk action controls should appear only on
lists with real bulk lifecycle actions, such as bounded Review Queue rejection.

Client access token creation is a low-frequency fallback action backed by
Core's app-key contract. It should stay behind the Settings tab's
`Advanced Access` disclosure, but once an administrator opens the client access
token page the issuance panel should be directly visible instead of hidden
behind a second disclosure. Token issuance should lead with client label,
caller type, and a purpose preset; raw scope checkboxes and rate-limit fields
belong in an advanced disclosure for custom clients. It must not appear as a
first-level Core tab.
The default token table should lead with client label, localized status,
permission summary, last-used time, and a disable action. Full App ID, Key ID,
caller type, rate limit, expiry, and complete scope strings belong behind a row
`Details` disclosure so long machine identifiers do not dominate the page.

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
or technical filter disclosure and should appear only when it exists. The table
should use the same WordPress-style top and bottom table navigation as the
review queue: item count and square first/previous/next/last controls on the
right, with the current result range visible on the left.

Activity rows should lead with the short governance display id for linked
proposals, not the full UUID. Full proposal id, raw event name, actor id,
ability id, app id, caller type, scope decision, and correlation id stay behind
a per-row `Details` disclosure so the first scan stays compact while technical
handoff remains available.

The activity filter surface should default to one compact toolbar with a broad
search field, event-type dropdown, time-range dropdown, read-noise toggle, per
page control, and apply/reset actions. Exact proposal, ability, app, caller,
and correlation filters belong in an `Advanced filters` disclosure that opens
only when those technical filters are active. Active filters should be shown as
clearable chips above the table.

Admin tab, pagination, detail, archive, and filter links are read-only GET
navigation and must not append a nonce to the URL. Nonces belong on POST forms
that change state, such as approval, rejection, lifecycle actions, policy
updates, client access token creation, and client access token revocation.

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
commit-preflight, audit, and fallback client access token management only.
