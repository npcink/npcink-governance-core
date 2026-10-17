# Addon bounded two-object recovery closeout — 2026-10-17

Status: **the two historical Site Knowledge gaps are recovered and accepted**.
At `2026-10-17T08:08:49–08:08:51Z` (16:08 Beijing), the stable public source
manifest has **1,999/1,999** covered keys and no missing key. Both targets have
real documents, chunks and 1024-dimensional embeddings. This completes the
specific recovery objective; other owner and runtime-observation work remains.
[Structured evidence](addon-bounded-recovery-closeout-2026-10-17.json).

## Goal, authority and scope

The goal was to bring the two already-public WordPress objects into Cloud Site
Knowledge, preserving the governance/runtime boundaries and existing source.
The missing-cursor fix and site loading had already been delivered; this final
step restores data missed before the fix and verifies the actual result.

The human explicitly authorized reopening Addon owner chat
`01a1447a-4c96-7342-86f1-466eed68ac3b` and sending one continuation request.
Exactly one was delivered this round. The owner reread the original human
message, refreshed cursor/gaps and used the existing supported
`request_article_refresh()` path once per target. The later question about
project status did not cancel that authorization. No further message was sent.

Core's change envelope is documentation only: this report/JSON, plan and bounded
breadcrumb/archive. No Core source, public contract or release identity changes.
Gates: exact request/document/run and set comparison, source/evidence hashes,
ref/tag preservation, JSON/links/breadcrumb/diff, metadata and Core default tests.
No new matrix is needed for this documentary closeout. Rollback of this record
is a docs revert, not deletion of indexed data or source backups.

## Before, bounded action and accepted result

At `08:07:05–08:07:06Z`, a fresh stable manifest still had 1,999 public keys,
1,997 covered, and exactly `post:282639` / `page:282641` missing. Both were
published and not password protected; cursor/buffer were empty and no Site
Knowledge task was active.

Each supported request sent one singleton public document. Request-local cron
was disabled; only that object's article-refresh request to the configured
runtime was allowed. No reconciliation, flush, whole-site refresh, rebuild,
cleanup, cursor rewind or configuration/quota change was invoked.

| Target | Successful run | Actual indexed result |
| --- | --- | --- |
| `post:282639` | `run_f3607aebf0554034b1c88ca97da830d3` | 1 document / 1 chunk, 1024 dimensions, indexed `08:08:17.838715Z`. |
| `page:282641` | `run_1098a21fd0854e56adc4265aa4ee013d` | 1 document / 1 chunk, 1024 dimensions, indexed `08:08:20.502548Z`. |

Both runs are `succeeded`, with no error code. Each document's
`last_sync_run_id` matches its request's actual run; each chunk content hash
matches its document. Existing Ollama embedding performed the index work under
Cloud ownership. The coordinator did not issue additional refresh/model calls.

## Independent verification and evidence

The coordinator rehashed all **44** files in the owner's `SHA256SUMS`, then
independently recomputed the before/after public metadata manifests and exact
Cloud document intersection/difference:

- Public keys and metadata stayed identical across the entire recovery window;
  1,966 posts and 33 pages qualify, excluding one password-protected published row.
- All 1,999 public keys have documents, chunks and positive embedding dimensions;
  no missing key, no key without chunks/embedding, and no active task remains.
- Only the two target document keys were added; all pre-existing document
  metadata is unchanged. Historical failed runs remain historical failures.
- Both singleton request receipts, document/run links, target chunk dimensions
  and document/chunk content hashes match.
- Cursor/buffer were not changed; normal delivery buffer counters are not used
  as proof for the singleton path.

Final coverage evidence SHA-256:
`f8916429ece297a90626ca1fdcd8897e0d486b7e8aa7d9e5d901b23dae847d0f`.
Owner evidence is retained at Addon
`build/site-recovery-acceptance-20261017T071849Z/`, including acceptance.md/JSON,
raw metadata readbacks, bounded request receipts and source/cleanup records.
Core copies only the needed metadata/proofs, not article bodies, credentials
or the historical 64-key list.

Addon remains clean `master@3e650d1bc74d9428d6671f27dd668938c6b6078b`; the
previous WP/FPM bridge hash remains
`4ed4452cdb8bfffa9c9d76fac7acb00c6817cef986add92000a86c037aa65c46`.
The old-source branch and verified bundle remain. The owner retained existing
same-revision deterministic test evidence and did not claim a new full test
run. No new code commit or PR was needed for the operational recovery.

## Closeout boundary and remaining work

| Item | Disposition |
| --- | --- |
| Site synchronization and WP/FPM loading | Complete, from the earlier authorized step. |
| These two historical public-content gaps | Complete; true exact coverage acceptance passed at the recorded window. |
| Natural periodic reconciliation | Not established by this recovery; no scan/flush was triggered. Treat its observed overdue event and execution evidence separately. |
| Other historical Cloud owner work | F-01 64-key disposition/state semantics, F-02 real OCR coverage and F-03 dependency maintenance remain independent. |
| Semantic search quality / production adoption | Outside this structural recovery acceptance. |
| Current full-stack release readiness | Not newly tested or published. Fixed RC2 and snapshot remain immutable. |

The queried M4 preview self-reported revision `a8cd22aa` in this evidence. This
is an observation of the runtime used, not a Cloud deployment or current-master
promotion performed by this task. No new claim of production acceptance follows.
Owner diagnostics also observed another owner's onboarding branch/locked
worktree and preserved it; clean default status does not close their work.

No original branch/worktree/stash was deleted and no tag was created, pushed
or moved. Fixed RC2 SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
This Core receipt supersedes the two-gap pending status in
[the interrupted handoff](addon-owner-sync-recovery-handoff-2026-10-17.md), while
retaining that dated history. It joins the three existing local Core docs
commits for weekly batching; no new PR is opened in this session.

## Core documentation verification

Composer metadata and `composer test:all` passed (55 PHPUnit tests / 207
assertions plus component gates). JSON/local links, ten-entry breadcrumb,
diff and manifest checks passed. Original Core branch/tag refs and remote tag
map are unchanged before this docs commit; Addon final local refs and original
remote refs/tags retain their SHAs, clean source and stash state. Owner source
backups and other owners' resources remain preserved.
