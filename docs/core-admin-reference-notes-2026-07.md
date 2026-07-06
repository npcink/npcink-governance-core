# Core Admin Reference Notes - 2026-07

Status: benchmark notes for future Core admin UX decisions.

Date: 2026-07-06.

This note is the first execution pass after
[Reference Plugin Deep Dive - 2026-07-06](reference-plugin-deep-dive-2026-07-06.md).
It focuses only on Core admin queue and audit patterns. It does not propose
new REST routes, data shape changes, lifecycle changes, runtime behavior, or
plugin dependencies.

## Question

Which mature WordPress admin patterns should Core learn from before changing
its proposal review queue or audit surfaces?

## Short Answer

Core should learn admin information architecture from PublishPress Revisions,
WP Activity Log, and Activity Log:

- PublishPress Revisions proves that a review queue should make state, target,
  submitter, and moderation actions obvious before the reviewer opens a detail
  screen.
- WP Activity Log proves that dense audit tables can remain readable when they
  use severity, actor, object, event type, message, detail inspection, filters,
  and retention policy.
- Activity Log proves that a lighter log view can still answer the basic
  operator question: who did what, where, and when.

Core should not copy their product boundaries. Core proposals are governance
records, not post revisions. Core audit is AI-operation lifecycle evidence, not
a generic site activity log.

## Current Core Baseline

Current Core documentation already defines a focused admin surface:

- `Review Queue`, `Activity Log`, `History`, and `Settings` tabs;
- compact status summary;
- pending proposal list with request labels, display ids, source, status,
  age/expiry, details, and review entry;
- row technical details behind a dedicated `Details` column;
- bounded bulk rejection only for selected pending proposals;
- proposal detail split into `Overview`, `Action plan`, `Audit evidence`, and
  `Technical info`;
- full activity table that suppresses low-value read/list events by default;
- no workflow runtime, queue, scheduler, final execution, or product UX
  ownership.

These notes therefore look for admin clarity improvements only. They do not
ask Core to expand authority.

## Reference Sources

| Reference | Useful source facts | Screenshot target |
| --- | --- | --- |
| [PublishPress Revisions](https://wordpress.org/plugins/revisionary/) | Users can submit changes for approval; changes are stored in a Revision Queue; admins can approve, reject, schedule, preview, and compare changes. | [Revision Queue screenshot](https://ps.w.org/revisionary/assets/screenshot-5.jpg?rev=3394615) |
| [WP Activity Log](https://wordpress.org/plugins/wp-security-audit-log/) | Tracks what changed, when, and by whom; exposes event ids, user, object, event type, message, inspector detail, filters, retention, and export/report concepts. | [Activity Log Viewer](https://ps.w.org/wp-security-audit-log/assets/screenshot-1.png?rev=2980108), [event inspector](https://ps.w.org/wp-security-audit-log/assets/screenshot-4.png?rev=2980108), [filters](https://ps.w.org/wp-security-audit-log/assets/screenshot-6.png?rev=2980108) |
| [Activity Log](https://wordpress.org/plugins/aryo-activity-log/) | Lightweight log table focused on date, author, IP, type, label, action, and description; offers filtering and CSV export. | [Activity Log table](https://ps.w.org/aryo-activity-log/trunk/screenshot-1.png?rev=3563647) |

Screenshot links are reference targets. They are not vendored into this
repository.

## Reference Pattern Matrix

| Pattern | Reference | What the reference does well | Core learning | Core boundary |
| --- | --- | --- | --- | --- |
| Queue name and purpose | PublishPress Revisions | Names the queue around the operator job: submitted revisions waiting for moderation. | Keep `Review Queue` as an active-governance surface, not a generic proposal database. | Do not rename Core proposals into revisions or editorial changes. |
| Row scan | PublishPress Revisions | Shows revision title, status, post type, revised by, and row actions. | Pending proposal rows should lead with request label, status, source, age, and one review action. | Do not add content-editor actions such as edit post, compare content, or schedule publication. |
| Moderation actions | PublishPress Revisions | Offers moderation tools from the queue while deeper preview/compare exists elsewhere. | Keep approve/reject entry discoverable, but keep evidence and technical payload in detail views. | Do not add final execution or scheduling actions to Core rows. |
| Before/after evidence | PublishPress Revisions | Uses WordPress revision comparison for content changes. | Core should show preview/diff/evidence close to the decision when a provider supplies it. | Core must not generate or execute the content diff itself. |
| Audit table density | WP Activity Log | Uses severity, id, date, user, IP, object, event type, message, and detail action. | Core `Activity Log` can use compact columns and hide rare technical fields behind detail inspection. | Core should not log every WordPress site event. |
| Detail inspection | WP Activity Log | Uses `More details` / inspector patterns for technical context. | Core row details and audit detail should expose proposal id, ability id, app, caller, scope, and correlation id only when needed. | Do not make raw JSON the first scan. |
| Search and filters | WP Activity Log | Provides filter UI for event id, object, type, severity, user, dates, and IP. | Core filters should prioritize proposal id/display id, event name, ability, app/key, caller type, and correlation id. | Do not add broad site-object filters unrelated to Core governance. |
| Retention and export language | WP Activity Log and Activity Log | Make retention/export part of audit operability. | Core should keep history retention language explicit and governance-specific. | Export/reporting should not become a generic compliance product without a separate decision. |
| Lightweight activity view | Activity Log | Uses a simple table to answer who, what, where, and when. | Core can keep `Activity Log` readable by using labels and context cells instead of raw event names everywhere. | Do not replace Core lifecycle event model with generic action labels. |

## Candidate Improvements

These are candidate notes only. They should not be implemented until the
current dirty worktree is resolved and a scoped UI issue is opened.

### P1 - Preserve

Keep these existing Core choices:

- `Review Queue` as the default active-governance tab.
- Separate `Activity Log` and `History` tabs.
- Display id as the human lookup handle.
- Details column for technical metadata.
- Audit timeline before raw payload in proposal detail.
- Settings as the place for low-frequency token and retention controls.

These choices already match mature WordPress admin patterns while preserving
Core's boundary.

### P1 - Clarify

Potential future admin UX improvements:

- Make the pending queue's first-scan columns match the reviewer's question:
  request, source, status, age/expiry, details, action.
- Use human-facing activity labels in the activity table, with raw event names
  hidden behind details or filters.
- Keep actor/object/action/time/correlation id consistent between proposal
  detail and activity table.
- Add a compact `More details` style inspector for activity rows if the current
  detail surface feels too raw.
- Ensure empty Review Queue points to lookup, recent activity, and history
  rather than reading as a dead end.

### P2 - Investigate Later

Only after screenshots and a current Core admin smoke pass:

- Whether Core needs a saved activity-filter preset.
- Whether audit export is necessary for governance evidence handoff.
- Whether proposal detail should expose a compact before/after preview summary
  for plan-generated proposals.

These are not next-step implementation items.

## Explicit Rejections

Do not implement these from the reference plugins:

- editorial revision ownership;
- content scheduling;
- frontend moderation toolbar;
- generic site-wide activity logging;
- external log mirroring;
- broad compliance reporting;
- generic CSV export for all site events;
- new queue/job/scheduler/runtime state;
- proposal row final execution buttons.

If a future request requires any of these, write a separate boundary note or
ADR first.

## Suggested Next Artifact

The next artifact should be a Core UI issue or design note, not code:

```text
Title: Compare Core Review Queue and Activity Log against reference plugin screenshots

Scope:
- Review current Core admin screenshots.
- Compare against PublishPress Revisions Revision Queue.
- Compare against WP Activity Log viewer, inspector, and filters.
- Compare against Activity Log lightweight table.
- Propose no more than three low-risk presentation-only improvements.

Non-goals:
- No REST changes.
- No data shape changes.
- No lifecycle changes.
- No final execution.
- No workflow runtime.
- No dependency on reference plugins.
```

## Decision Gate

Before any Core admin implementation derived from this note, answer yes to all
of these:

1. Does it reduce proposal or audit review confusion?
2. Does it keep Core as the governance truth only?
3. Does it avoid changing REST, table schema, proposal lifecycle, and
   execution ownership?
4. Does it avoid new plugin dependencies?
5. Can it be verified with `composer test:all`, and only with
   `composer smoke:wp` if WordPress admin behavior actually changes?

If any answer is no, do not implement it as a Core admin cleanup.
