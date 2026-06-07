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
- `Expired / Archived`.

The default `Review Queue` tab must stay focused on the current governance
queue:

- paginated pending request list with user-facing request labels, time, and a
  clear decision entry;
- `Proposal ID` visible in each default row as the governance lookup handle;
- ability id and source trace preserved behind per-row technical details for
  Adapter/OpenClaw handoff lookup;
- bounded bulk rejection for selected pending proposals;
- stale proposals available from the expired/archive tab;
- `Development Approval Policy` disclosure for the lightweight manual,
  dry-run guarded, and local guarded policy modes;
- one-line recent activity summary with a link to the activity log;
- `Advanced Access` disclosure for low-frequency client access key management.

## Detail Views

Proposal detail should be a focused review surface:

- proposal identity and status;
- approve/reject decision controls for pending proposals;
- review context from ability intake and preview metadata;
- raw proposal payload behind a disclosure;
- proposal audit timeline behind a disclosure.
- lifecycle controls for expired or archived proposals.

Full `Activity Log` and `Expired / Archived` belong in dedicated tabs, not
inline on the default workbench. Long lists in `Review Queue`, `Activity Log`,
`Expired / Archived`, and advanced app-key management must be paginated.

Core app-key creation is a low-frequency fallback action. It should stay behind
the default workbench's `Advanced Access` disclosure and then behind an
explicit creation disclosure on the advanced access page. It must not appear as
a first-level Core tab.

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
