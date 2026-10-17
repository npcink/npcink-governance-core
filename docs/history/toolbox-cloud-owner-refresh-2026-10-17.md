# Toolbox / Cloud owner refresh — 2026-10-17

Status: Toolbox closed; Cloud publication and exact-SHA CI pending. No new
full matrix was run during this refresh. This does not establish current 7/7.

## Focus and boundary

The change envelope is Core release-coordination documentation only. Expected
files are this record, its audit JSON, the next-stage plan and bounded breadcrumb
with archive rotation. No public contracts, sibling source, candidate manifest,
release tags, deployment or M4 promotion change. Verification is JSON parsing,
diff checks and Core default gates. Rollback is reverting this docs commit.
The full cross-repo gate remains conditional on owner readiness.

## Toolbox is closed

[PR #273](https://github.com/npcink/npcink-workflow-toolbox/pull/273) merged at
`2026-10-17T03:17:07Z` as `d253bdb110be353f959b27d5b6d93682db5b2df8`.
Its exact topic head `86c6ac7ae699211e593bc61420aa82ba85851164` passed PHP
contracts, PR body checks and advisory static analysis. The default shared root
is clean, synchronized master at the merge SHA.

The owner records real article 18817 readiness agreeing with Apply: one ready
candidate and four linked-range reference-only candidates; format-only refresh,
Apply and exact-block Undo passed. Native save passed on a disposable draft,
the published article stayed unchanged and temporary drafts/helpers were removed.
Ranking policy stayed fixed. The owner cleaned this task's topic branches.
These are owner integration receipts, not a new independent browser rerun here.
External AI review was not delivered (disabled workflow / review quota); the PR
records its publisher exception, local review and protected checks.

## Cloud is clean but not published

The owner completed the zero-paid critical regression implementation and local
commit `f7aab8c493b41683e402ea0a307d8b1e856d4a35`, on
`codex/zero-paid-critical-regression-20261017`. Local tests reported 22 API,
15 browser and six contract cases plus strict type checks, with no paid provider
or production action. The final shared-root snapshot is clean, ahead one.

Remote master remains `585aa6609b45eff3e41e4a3e4718ae68941824ef` from merged
[PR #1124](https://github.com/npcink/npcink-ai-cloud/pull/1124); its five required
checks passed. The new topic ref is absent from `git ls-remote`. GitHub's
check-runs API for **f7aab8c4** returns HTTP 422, “No commit found for SHA”.
Thus the older green checks do not cover the current source. The owner ended
with an explicit local-only implementation receipt; passive waiting cannot
start its remote checks. The separate ops PR #1125 remains outside this scope.

No extra owner message was sent. The prior one-message authorization was already
used. Authorization for one new formal Cloud publication/PR/CI/clean-root request
has been asked and is pending; no publication or sibling cleanup is implied.

## Matrix and immutable release evidence

All seven default roots were clean at `2026-10-17T03:21:49Z`; cleanliness alone
is insufficient while current Cloud SHA has no CI. Respecting “wait until ready”,
no full matrix was rerun. The last full green at `02:45:41Z` covers its recorded
versions only; the last diagnostic at `03:08:09Z` was 6/7, exit 1. Neither result
proves the current default heads pass.

RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
The published snapshot remote object remains
`ffb5e5d6d7b68fa952288524a181ff688a7698ee`, peeled to
`ed28f984153200a8362fcd0928bbb32b83636159`. No tag was created, pushed or moved.

## Next gate and retained work

1. Cloud owner formally publishes the new implementation through its protected
   PR flow and closes the default root. Verify all five required checks on the
   actual final shared-root SHA, including any subsequent merge drift.
2. Recheck seven default roots and owner activity; then run Toolbox's full
   `composer quality:matrix:run -- --fail-on-dirty` without path or CI overrides.
   Preserve the raw report and pre/post revisions; do not hide new owner work by
   selecting an older green Cloud revision.
3. Compare the fixed manifest without rewriting it; any new candidate and exact
   release acceptance require a separate decision. Preserve Addon drift,
   auxiliary worktrees, retained branches/stashes and broader housekeeping.

Adapter exact source preservation and Core #177 publication remain completed.
Core's later receipts accumulate locally for the next weekly docs PR. This
refresh closes neither all-history cleanup nor candidate/release acceptance.

The reusable rule is to distinguish clean Git state, remote source availability,
exact-SHA CI, default matrix and fixed-candidate acceptance. Each proves a
separate condition; a local test result or an earlier green SHA cannot substitute
for another condition. See [audit JSON](toolbox-cloud-owner-refresh-2026-10-17.json).

## Documentation verification

`composer test:all` passed (55 PHPUnit cases / 207 assertions, lint, static
contracts, fault injection, reproducible package and revision-bound smoke-evidence
behavior). `composer validate --no-check-publish`, JSON parsing, local link
checks, breadcrumb limit (10 entries) and `git diff --check` passed. No live
WordPress rerun or cross-repo full matrix is claimed by these Core doc gates.
