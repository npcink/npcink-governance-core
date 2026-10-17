# Core weekly ownership-audit documentation publication — 2026-10-17

Status: the three accumulated records are published and merged. Other owner
work and broader housekeeping remain open.

## Published batch and protected merge

[PR #179](https://github.com/npcink/npcink-governance-core/pull/179) published
`codex/weekly-docs-merge-receipt-2026-10-17` at exact topic
`8df588847af0534f28755ff2a955df7748ceb0da`, from baseline
`95ef21089f281c16f69f8898d7eb89c3e7671795`. The batch contains exactly:

| Original commit | Documentation scope |
| --- | --- |
| `ac69fa72` | #178 merge receipt and preservation evidence |
| `92c4a02a` | Seven-repository branch/worktree/stash ownership audit and cleanup rules |
| `8df58884` | Authorized Core nine-local/one-remote cleanup and recovery sources |

All ten changed files are Markdown/JSON. Most lines are machine-readable
historical evidence, not source implementation. The PR used the checked-in
body template and `composer pr:publish`, with tag following disabled and
no-tags fetch. The publisher requested protected squash auto-merge.

Both required checks, Static contracts and PR body contract, passed on the
exact topic head. GitHub still reported OPEN/UNSTABLE after the advisory OCR
failure, so the merge was completed with normal
`gh pr merge --squash --match-head-commit`, without `--admin`. The live strict
protection required exactly those two deterministic checks; neither was bypassed.

The PR merged at `2026-10-17T05:51:31Z` as
`289ce777eff158e8f2d5088c30d90866efc87e2c`. Its own Static contracts also
passed. Topic and squash trees are identical:
`59df625fd5ffdf80a8532135e8814bcf537e485a`. The original local/remote topic
refs remain at `8df58884`, preserving all three original commits. A separate
receipt branch starts from the merged origin/master without resetting or
rewriting the original branch.

## Verification and advisory coverage

Composer metadata, `composer test:all` (55 PHPUnit tests / 207 assertions and
all component gates), WordPress.org guard, JSON parsing, document links,
breadcrumb bound, diff checks and evidence consistency checks passed. The
local five-axis review checked chronology, exact SHA/PR/tree binding, cleanup
authority, source retention, owner uncertainty and privacy. No blocking issue
remained. The historical inventory was not misrepresented as current readiness.

Local `ocr review --from origin/master --to HEAD` selected three JSON items
and reached its external 180-second limit after partial group work. It did not
deliver a complete review. [CI OCR](https://github.com/npcink/npcink-governance-core/actions/runs/39007652209)
failed all three selected items, zero comments and zero tokens. These are
undelivered reviews, not clean reviews. The PR records both limitations; no
model retry or workflow change occurred. Deterministic protection stayed intact.

## Cleanup and source preservation remain intact

This PR publishes the earlier audit and cleanup results; it does not repeat
or broaden cleanup. All prior non-master remote refs and SHAs stayed identical.
Only master advanced and the intended publication topic was added. Original
matrix source `codex/docs-publication-receipt-2026-10-17@b07972c1` and RC2
source `codex/rc2-validation-2026-10-16@f5181a3e` remain preserved.
The previously deleted remote `docs-rc-acceptance-owner-handoff` stays absent;
its exact `ed28f984` source remains under the existing snapshot tag.

Complete local/remote tag inventories are byte-identical before/after this
publication. Fixed RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
No tag was created, pushed, moved or deleted. No other owner message, source
change, branch/worktree/stash cleanup, candidate refresh, full matrix, exact
acceptance, production operation or M4 promotion occurred.

The prior cleanup ended at Core 9 local / 15 remote branches. Publication adds
one intended remote topic, and this new local receipt branch makes 10 local /
16 remote. That is expected publication work, not restoration of the deleted
branches or failure of the earlier scoped cleanup. Family-wide inventory totals
remain tied to their historical snapshot and were not freshly re-audited here.

## Change envelope and remaining work

Scope: Core release-coordination docs only; this receipt/JSON, stage plan and
bounded breadcrumb/archive rotation. No executable source or public contract
changes. Gates: Core default checks, metadata, JSON/links and diff checks.
Rollback is reverting this docs-only receipt; reverting documentation does not
undo the earlier Git-ref cleanup. Recovery sources remain in its dated receipt.

Other-owner candidate disposition, auxiliary worktree and stash retention,
unreconciled legacy local source, Addon drift/diagnostics, open dependency PRs,
quality labels and human production acceptance remain preserved. No blanket
all-history closure or fresh release acceptance is claimed.

The former three-commit backlog is closed. This later post-merge receipt is
one new **local** documentation commit on
`codex/audit-weekly-docs-receipt-2026-10-17`, reserved for the next weekly docs
batch. No second PR is opened merely to publish its own merge receipt.

See [machine-readable publication evidence](core-audit-weekly-docs-publication-2026-10-17.json).

Receipt verification passed after this documentation update: `composer
test:all` (55 PHPUnit tests / 207 assertions and all component gates), Composer
metadata, receipt JSON, local links, ten-entry breadcrumb, unchanged manifest
and `git diff --check`. No new live WordPress/matrix evidence is implied.
