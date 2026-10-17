# Addon owner synchronization and recovery handoff — 2026-10-17

Status: the one authorized request was delivered. Site code synchronization
and loading checks completed; both historical Cloud gaps remain open. The
owner chat then received a human archive instruction and stopped. No second
message or reactivation was made. [Structured evidence](addon-owner-sync-recovery-handoff-2026-10-17.json)
records exact source identities, hashes and the interruption boundary.

## Change envelope and coordination authority

Core documentation only: this report/JSON, plan and bounded breadcrumb/archive.
No Core public contract, source, manifest or tag changes. The human explicitly
authorized one request to the Addon owner for site-code synchronization and
two-target recovery acceptance. The archived owner chat was reopened to deliver
that request exactly once; the owner independently read the original human
message before acting. No unrequested follow-up message was sent.

Gates: source/backup/hash checks, independent public/Cloud set comparison,
JSON/local links/breadcrumb/diff, Composer metadata and Core default tests.
No new matrix is needed for this documentary checkpoint. Documentation rollback
is a revert; it does not roll back the separately authorized site update or
delete its preservation branch/bundle. Other owner work stays preserved.

## Completed: source synchronization and loading verification

The owner recorded stable clean source, mtime/content and refs/stashes before
synchronization. The shared Addon master safely fast-forwarded from
`b80b966151f26f114271a5534082c336ab2fea50` to
`3e650d1bc74d9428d6671f27dd668938c6b6078b` at `07:20:43Z`.
The root remains clean. Original source was preserved by:

- local `codex/site-sync-before-20261017T071849Z` at the old SHA;
- `source-before-sync.bundle`, independently rehashed to
  `575dafe0f5742d25761fc5c417a4c2c33c2e7ee32b7a3c6a6dd6ef5fe05cf36a`.

The bridge file SHA-256 is
`4ed4452cdb8bfffa9c9d76fac7acb00c6817cef986add92000a86c037aa65c46`,
equal to the reviewed #284/#285 source. The coordinator independently checked
the current file and backup bytes against the owner's receipt.

At `07:22:52Z`, a full WP-CLI bootstrap loaded the active Addon from the site
symlink with that bridge hash and the expected two-parameter buffer method.
Cron was disabled for the request, outbound HTTP blocked, reconciliation/flush
invocations were zero and blocked HTTP attempts were zero.

At `07:22:51Z`, direct FastCGI diagnostics confirmed the same source under
actual `fpm-fcgi`, with bounded invalidation of the changed bridge file only.
The diagnostic used SHORTINIT plus the bridge declaration, not a frontend or
cron task. This establishes loading/OPcache alignment; it is not evidence of
Cloud indexing recovery or a whole-stack production deployment.

## Fresh recovery evidence: 1,997 of 1,999, still two missing

The post-sync source/Cloud/source window is `07:23:42–07:23:43Z` on October 17.
The coordinator independently recomputed both public manifests and their exact
intersection/difference with the recorded Cloud documents:

| Check | Fresh result |
| --- | --- |
| Stable public post/page denominator | 1,999 before and after; password-protected content excluded. |
| Covered public source keys | 1,997. |
| Complete missing set | `post:282639`, `page:282641`. |
| Target Cloud documents / chunks | Both result sets empty. |
| Persistent cursor | Absent at that sample. |

The two targets remain published and not password protected. These are fresh
October 17 observations, not a reuse of the October 16 1997/1999 report. The
set comparison confirms **recovery was not complete at observation time**;
no claim is made about a later natural run. No synthetic success or isolated
matrix result replaces this finding.

Evidence is retained under Addon
`build/site-recovery-acceptance-20261017T071849Z/`; the JSON records hashes of
the public metadata manifests, Cloud read-only snapshot, source/OPcache and
coverage receipts. Article bodies, secrets and the 64 historical source-key
list are not copied into Core.

## Interruption and updated closeout list

After these steps, the owner chat received a new human instruction to archive
it and retain the three independent follow-ups and two-gap recovery acceptance.
Its active turn ended interrupted and the chat was archived. This is an
explicit stop boundary; the coordinator did not revive it or send another
message under the already-used one-message authorization.

| Item | Current disposition |
| --- | --- |
| Authorized formal owner request | Complete: one message delivered and original human authority read by owner. |
| Site code synchronization | Complete at `3e650d1b`; old source preserved. |
| WordPress/FPM loading alignment | Verified by controlled load and exact file hash. |
| Two-target actual recovery | Still open; fresh documents/chunks empty and full gap set unchanged. |
| Any corrective resend / next natural-run acceptance | Not completed in this interrupted owner turn. Do not infer that code synchronization alone fixes existing records. |
| Owner continuation | Needs a resumed or replacement owner assignment under user direction. No extra cross-chat message was sent. |
| Other owner items | F-01 64-record disposition, F-02 OCR evidence, F-03 dependency maintenance and other owners' work remain preserved. |

Next recovery work should first refresh cursor/buffer/current public/Cloud
state, then choose the existing supported bounded recovery/observation path.
Do not rewind cursor or expand to historical cleanup, full rebuild, quota
changes or production deployment. Use a fresh stable denominator and exact
documents/chunks evidence to close both targets and all remaining gaps.

Fixed RC2 bytes remain unchanged, SHA-256
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
The owner changed only the authorized Addon source checkout and preserved old
source with a new backup branch. Original branches, other owner resources and
all existing tags are retained; no tag was created, pushed or moved.
This local Core record joins the two existing receipts for weekly batching;
no new PR, whole-stack candidate or matrix/acceptance rerun occurred here.

## Documentation verification and retained evidence

Core Composer metadata and `composer test:all` passed (55 PHPUnit tests / 207
assertions plus component gates). JSON/local links, ten-entry breadcrumb and
diff checks passed. Core original local refs/tags and remote tags are unchanged.
Addon original non-master refs and all remote refs/tags are unchanged; only
its authorized master checkout advanced and the old-source backup branch was
added. Addon is clean with unchanged stash state. No final owner cleanup or
full-gate receipt was delivered after interruption; do not claim full recovery
or completed owner closeout from the partial evidence.
