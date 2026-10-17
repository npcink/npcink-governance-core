# Adapter update channel and Addon recovery refresh — 2026-10-17

Status: read-only recheck complete. Adapter 0.4.3 is published, but it is not
offered by the two update probes. Addon site code is still unsynchronized;
historical gap recovery remains unverified. [Structured evidence](adapter-update-addon-recovery-refresh-2026-10-17.json)
binds each conclusion to its timestamp and source.

## Change envelope

Core documentation only: this report/JSON, plan and bounded breadcrumb/archive.
No public contract, source code, sibling checkout, site, manifest or tag change.
Gates: official readbacks, site symlink/file hashes, owner handoff and dated
coverage evidence, JSON/links/diff/breadcrumb, Composer metadata and default
Core tests. No full matrix or runtime smoke is required for this documentary
checkpoint. Rollback: revert this documentation commit. Preserve original
branches, worktrees/stashes and all other unfinished owner work.

## Fresh Adapter update-channel result

At `2026-10-17T07:11:27.556881Z` (15:11 Beijing), read-only official API
probes returned:

| Evidence | Observed result | Closeout classification |
| --- | --- | --- |
| Plugin-information API | Version 0.4.3, official version ZIP available. | Standalone publication remains complete. |
| Update API, installed 0.4.1 | Offers 0.4.2. | Does not establish 0.4.3 update availability. |
| Update API, installed 0.4.2 | No plugin update entry. | Does not establish 0.4.3 update availability. |
| Downloaded official 0.4.3 ZIP | 176,503 bytes; SHA-256 `0aa2d93bbd6f6b5e8730f33c8754c15f3d5bd13e2e2113d41813da1b49b4dc7b`. | Still matches the earlier published package record. |
| GitHub artifact versus official ZIP | All 26 unpacked paths/file hashes equal; GitHub ZIP hash `4356f90721d56b7484d42ad4a73b1ed0500295f21580ce75a656b5f9cba68fbc`. | Package equivalence remains verified; no installation performed. |

This is a fresh recheck, not proof that every client or regional cache has the
same response. It is still earlier than the owner's suggested next check after
20:32 Beijing on October 17. That waiting window is not a promised release
instant. No security rejection, caching cause or universal outage is diagnosed.
Do not re-upload SVN or move the published tag to force distribution.

No background monitor was created. Owner/operator follow-up remains: recheck
after the recorded window, and inspect a committer notification if the service
still does not offer the version. The [previous release audit](adapter-release-addon-drift-review-2026-10-17.md)
retains exact release/tag/SVN identities and the separate frozen compatibility
candidate; this update-API read changes none of them.

## Addon owner handoff and actual site-code path

Current Addon shared root is clean `master@b80b966151f26f114271a5534082c336ab2fea50`.
Actual remote master is `3e650d1bc74d9428d6671f27dd668938c6b6078b`, two commits
ahead: runtime reconciliation fix #284 and documentation #285.

Filesystem evidence at `07:11:55Z` confirms the local WordPress plugin symlink
resolves to that shared root. The site's bridge file hash equals the old
HEAD blob and differs from the fixed #284/#285 bridge. Therefore the filesystem
code has **not been synchronized to the fix**. This does not inspect FPM cache
or claim a new HTTP/runtime acceptance result.

The owner's saved handoff at `2026-10-17T02:46:49.539523Z` explicitly states:
no site fast-forward, request, cron, flush, cursor rewind, Cloud cleanup,
rebuild or deployment. It retains the exact two-target recovery acceptance as
unfinished work. Current Git and filesystem evidence agree with that receipt.
The merged retrospective deliberately preserved the old loaded directory to
avoid an implicit site update; two commits behind is not unowned debris.

No newer recovery completion was found in the inspected owner handoff and
source records. The owner's chat was not present in the queried recent/archived
list pages; this is not a claim of exhaustive chat-history coverage. No new
cross-chat message was sent and no missing approval is inferred from a list.

## Historical coverage is not a fresh recovery measurement

The available stable public-source/Cloud comparison was sampled on
**2026-10-16 at 13:12:04–13:12:05Z**, with Cloud revision `2fd7282f`:

- public denominator 1,999, covered 1,997;
- missing targets `post:282639` and `page:282641`;
- persistent reconciliation cursor absent at that sample.

The report/hash is retained in the structured evidence. These counts and cursor
are historical observations, not today's state. This audit did not request the
live WordPress/Cloud services, query the current private denominator or access
content/credentials. It cannot certify either current failure or recovery.
The earlier Adapter isolated-candidate matrix and exact acceptance included
Addon `3e650d1b`; they do not establish a shared-site update or recovered Cloud
records.

## Updated closeout checklist

| Item | Current classification | Next completion evidence |
| --- | --- | --- |
| Adapter public release/package identity | Completed; unchanged. | Retain fixed source/package/tag records. |
| Adapter 0.4.3 background update availability | Still awaiting external confirmation; not offered to current probes. | Dated update response offering 0.4.3 to an old-version client; investigate any official notification if needed. |
| Addon site-code synchronization | Not completed in the observed site filesystem. | Separately scoped owner site update, exact deployed revision and bridge hash, plus applicable checks. |
| Two historical Cloud gaps | Unverified; do not close from code merge or historical counts. | After code update, stable before/after current public manifest, target documents/chunks, current cursor/queue/run evidence and all remaining gaps. |
| Cursor already past a missing target | Unknown current state. | Read actual cursor first; any exact resend needs a separate decision. Do not rewind it under this audit. |
| Wider owner work | Preserved. | F-01 historical 64-record disposition, F-02 OCR coverage, Cloud budget/transport/dependencies, human quality/adoption and owner resources remain separate. |

This audit does not authorize site synchronization, cron/flush/rebuild, exact
resend, Cloud cleanup, production deployment, paid/model calls or tag actions.
If the user later chooses Addon recovery, first agree the site-update scope and
observation/recovery method; do not substitute a new matrix for live coverage.

Fixed RC2 manifest remains byte-identical, SHA-256
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
The original source branches and tags are retained. This local audit joins the
post-#181 receipt for the next weekly docs batch; no new PR is opened today.

## Documentation verification

Composer metadata and `composer test:all` passed (55 PHPUnit tests / 207
assertions plus component gates). Changed JSON/local links, ten-entry
breadcrumb, diff and manifest checks passed. Final before/after Core/Adapter/
Addon local/remote branch/tag refs, worktrees and stashes are identical;
sibling HEAD/status remain unchanged. Core advances only this local docs
record. No owner message, publication or runtime mutation occurred.
