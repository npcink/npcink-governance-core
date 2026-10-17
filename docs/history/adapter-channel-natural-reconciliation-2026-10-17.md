# Adapter channel and natural reconciliation evidence — 2026-10-17

Status: Adapter 0.4.3 is published but still not offered to the update probes.
The two historical gaps remain recovered. Reconciliation callback state and
cursor now show progress; full catch-up and direct natural-cron invocation
provenance are not established. [Structured evidence](adapter-channel-natural-reconciliation-2026-10-17.json)
records observations separately from inference.

## Change envelope and read-only method

Core documentation only: this report/JSON, plan and bounded breadcrumb/archive.
No public contract, source, sibling checkout, site data, manifest or tag changes.
Used the WordPress operations read-only workflow and documentation skill:
SHORTINIT without plugin/cron bootstrap; local repeatable-read read-only SQL
with rollback; Cloud repeatable-read read-only SQL/rollback with bounded query
and connection timeouts. Reviewed collector copies write only outside sibling
roots. No runtime/embedding/refresh request was made.

Gates: official API/package readbacks, stable public/Cloud set comparison,
source/status and evidence hashes, JSON/links/breadcrumb/diff, metadata and
Core default tests. No full matrix/runtime smoke is required for a documentary
checkpoint. Rollback is a docs-only revert; preserve original branches and
other owners' resources. No cross-chat message or monitoring task was created.

## Adapter: publication complete, update channel still pending

Official readback at `2026-10-17T08:23:57.592557Z` (16:23 Beijing):

| Read | Result |
| --- | --- |
| Plugin-information API | 0.4.3 and its official version ZIP are available. |
| Update probe, installed 0.4.1 | Offers 0.4.2. |
| Update probe, installed 0.4.2 | No update entry. |
| Downloaded official ZIP | SHA-256 `0aa2d93bbd6f6b5e8730f33c8754c15f3d5bd13e2e2113d41813da1b49b4dc7b`; all 26 unpacked files still equal GitHub artifact. |

These two probes do not show 0.4.3 update-channel release, nor prove universal
client/cache state. The check is earlier than the owner's suggested 20:32
Beijing follow-up window, which is not a promised availability time. No cause
such as caching/security rejection is diagnosed. The public release/tag/package
identity remains as recorded in [the release review](adapter-release-addon-drift-review-2026-10-17.md).
Keep the external update follow-up open without re-uploading or retagging.

## Two historical gaps: accepted result remains intact

Fresh WordPress/Cloud/WordPress window `08:24:32–08:24:33Z` has identical public
metadata sets before and after. Independently recomputed coverage is again
**1,999/1,999**, no missing public key, none without chunks or nonempty embedding
dimensions; both recovered targets remain present. Buffer is empty, attempts
zero, and no Site Knowledge run is active.

This confirms the accepted two-object recovery remains intact at this window.
Its successful singleton runs at 08:08 remain distinct from later sync runs.
See [the bounded recovery closeout](addon-bounded-recovery-closeout-2026-10-17.md).
No extra refresh or recovery was performed in this recheck.

## Reconciliation: new callback progress, not a completed full scan

| State | Prior accepted recovery, 08:08Z | Fresh observation, 08:24Z |
| --- | --- | --- |
| last_reconciled_at | 07:05:58Z | **08:10:45Z** |
| Persistent cursor | Empty | **2018-07-12 08:14:25 / post ID 588** |
| Next hourly reconciliation event | 07:47:21Z, already due | **08:47:21Z**, future at the observed window |
| total_sent | 8,408 | **8,458** |
| last_delivered_at | 06:30:59Z | **08:21:05Z**, success |
| Buffer / dropped | Empty / 0 | Empty / 0 |

The current callback's accepted-candidate path writes the cursor only after
retaining candidates; it also writes `last_reconciled_at`. Those fields have
actually advanced, so the earlier “no new reconciliation execution evidence”
status is superseded. The recurring event has also advanced by one hour.
This is execution-state evidence, rather than treating a scheduled time as
proof that a job ran.

The recorded cursor is at/beyond 50 keys in today's public post/page set;
**1,949 public keys still lie after it**. Therefore this is page-level progress,
not proof that catch-up scanned the whole public set. Current full index
coverage and unfinished catch-up can coexist because previous indexing and
the bounded recovery already populated the documents.

Two newer Cloud Site Knowledge runs succeeded without errors at 08:14:57 and
08:21:05. Their `operation_source` and `post_ids` projections are null. Together
with a 50-count delivery increase they support delivery progress, but do not
by themselves identify the initiating cron invocation. The exact natural
trigger/source is not directly proven by the available invocation logs; no new
trace logging or manual trigger was introduced to manufacture that proof.
The coordinator only read state and did not trigger the observed scan.

## Updated closeout checklist

| Item | Disposition / next evidence |
| --- | --- |
| Adapter publication | Complete; identity unchanged. |
| Adapter update-channel offering | Still open for a later dated official API check. No background automation created. |
| Two historical missing public objects | Closed at accepted window and reconfirmed covered now. Do not reopen because scan catch-up is unfinished. |
| Reconciliation callback execution/cursor progress | New state evidence observed; prior no-progress entry is historical. |
| Natural-cron invocation attribution | Not directly established; preserve this limit instead of assigning all sync runs to cron. |
| Full catch-up / continued periodic operation | Not yet established; observe future natural cursor/status advancement and eventual end-of-set behavior without forcing a run. |
| Other owner work | Historical 64-key disposition, OCR coverage, dependencies, semantic quality/adoption and other resources remain separate. |

Cloud runtime self-reported `331cd4cd` in this read; the task did not deploy,
synchronize or promote it, and does not claim current full-stack acceptance.
Addon default root stays clean at `3e650d1b`; source backups remain retained.
Fixed RC2 SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Original refs/tags and other owner work are preserved. This audit joins the
post-#182 local receipt for the next weekly docs batch; no new PR, candidate
or tag operation occurred.

## Documentation closeout verification

Core Composer metadata and `composer test:all` passed (55 PHPUnit tests / 207
assertions plus component gates). JSON/local links, ten-entry breadcrumb,
diff and fixed-manifest checks passed. Before/after Core/Adapter/Addon local
and remote branch/tag refs, worktrees and stashes are identical; sibling
HEAD/status remain clean and unchanged. Core advances only this local docs
record. No trigger, owner message, external publication or tag action occurred.
