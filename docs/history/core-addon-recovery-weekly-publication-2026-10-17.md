# Addon recovery weekly documentation publication — 2026-10-17

Status: [PR #182](https://github.com/npcink/npcink-governance-core/pull/182)
merged by protected squash auto-merge at `2026-10-17T08:17:52Z` as
`c63b7b7a1fad5673cabdbd809678c5a360c2e387`. Final reviewed topic
`d7950a307400b03b357a2bd9b859d787de81f2fe` and squash have identical trees.

## Published scope and change envelope

The four accumulated commits `5a03ada`, `c7a1abf`, `d5b526f`, `d7950a3`
are now published, covering 11 documentation/evidence files: #181 receipt,
dated Adapter update-channel check, the interrupted owner sync handoff and the
successful bounded two-gap recovery closeout. Older pending statuses retain
their dates; the latest accepted recovery record supersedes them.

Core documentation only: this receipt/JSON, next-stage plan and bounded
breadcrumb/archive. No public contract or runtime code changes. Relevant gates:
Core metadata/default tests/wporg, documentation integrity, PR protection,
merge-tree and preservation proof. No new WordPress smoke or cross-repository
matrix is required for a docs-only publication. Rollback is a docs revert;
do not delete recovered indexed data, source backups or owner resources.

The actual two-object recovery remains accepted at its recorded stable window:
1,999/1,999 with documents/chunks/embeddings and no gaps. This publication did
not rerun that recovery or refresh live Cloud/WordPress observations.
See [bounded recovery acceptance](addon-bounded-recovery-closeout-2026-10-17.md).

## Verification and external-review coverage

- Local Composer metadata, `composer test:all` (55 PHPUnit tests / 207
  assertions plus component gates) and `composer check:wporg` passed on final
  topic content. Changed JSON/local links, ten-entry breadcrumb, docs-only scope,
  diff whitespace and fixed-manifest checks passed.
- Manual five-axis review covered dated provenance/correctness, readability,
  governance boundaries, privacy and runtime/performance impact; no blocking
  issue was identified.
- Both required checks, Static contracts and PR body contract, passed. Strict
  protection remained enabled. Normal squash auto-merge completed without an
  admin override or required-check bypass.
- Merged-SHA Static contracts passed on `c63b7b7a`, run `39016832880`.
- Local advisory OCR hit the 180-second bound without a complete report.
  CI advisory run `39016769014` failed all four selected items, with zero
  comments and zero tokens. The auto-merge completed after required checks;
  the CI coverage gap was then added to the PR body and retained here. No
  completed external review or successful zero-finding review is claimed.
  No review retry, workflow change or branch-protection change was made.

## Preservation and remaining work

All original Core local branch/tag refs retain their exact tips. Existing
actual origin non-master heads are unchanged. The intended publication topic
was added remotely; approved master advanced to the squash. Complete Core
remote tag maps remain identical, and all original local tags are unchanged.
A new local receipt branch starts at the merge; the published topic is retained.

Fixed RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
No tag creation/push/movement, original branch deletion, worktree/stash cleanup,
sibling checkout change, owner message, refresh/indexing request, candidate
selection, runtime acceptance or production/M4 deployment occurred in this
publication session.

Natural periodic reconciliation evidence, historical 64-key disposition,
real OCR history, dependency/quality/adoption and other owner work remain
independent. Adapter update availability remains as dated in its check record;
this PR did not refresh it. The accepted two gaps are not reopened by these
other follow-ups, and their completion does not close unrelated work.

The former four-commit backlog is closed. **This later receipt is one new local
record for the next weekly docs batch**, not a recursive PR today.
[Structured publication and preservation evidence](core-addon-recovery-weekly-publication-2026-10-17.json).

## Local receipt verification

The new post-merge receipt also passed Composer metadata, `composer test:all`
(55 tests / 207 assertions plus component gates), WordPress.org guard,
JSON/local links, ten-entry breadcrumb, diff and unchanged manifest checks.
It advances only the new receipt topic; original source refs remain retained.
