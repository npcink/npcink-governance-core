# Core weekly documentation publication — 2026-10-17

Status: three accumulated documentation commits published and merged. Remaining
owner/product/housekeeping work is preserved; all-history closeout is not claimed.

## Publication receipt

[PR #178](https://github.com/npcink/npcink-governance-core/pull/178) published
`codex/docs-publication-receipt-2026-10-17` at
`b07972c12f0fb822c674570f2cc5b22b2c8d8302` from baseline
`9d153849b2fc130e7d3ab7e2a090995e184954a1`. It contains exactly three commits:

| Original commit | Scope |
| --- | --- |
| `397fee55` | Adapter exact-source preservation and Core #177 merge receipt |
| `03be509f` | Toolbox #273 closeout and Cloud local-only CI blocker |
| `b07972c1` | Cloud #1126 / Toolbox #274 closeout and default 7/7 evidence |

All 12 changed files are Markdown/JSON coordination records. The publisher used
the checked-in PR body template, fetched the current baseline and requested
protected squash auto-merge. Tag-following was disabled; no tag action occurred.
Both protected checks, Static contracts and PR body contract, passed on the
exact topic head. The squash merged at `2026-10-17T04:14:39Z` as
`95ef21089f281c16f69f8898d7eb89c3e7671795`. That merged SHA's own Static
contracts check also passed.

The original topic and squash have identical tree
`5b23168d54735e91ffac991e33ae17df3ee3c9dd`. Local and remote topic refs remain
at `b07972c1`, preserving all original commits. Their absence from squash
ancestry does not mean the documentation is unmerged. The working checkout
moved to a separate receipt branch based on merged origin/master, without
resetting or deleting the original branch.

## Verification and review coverage

Before publication, Composer metadata, `composer test:all` (55 PHPUnit tests /
207 assertions), WordPress.org guard, changed JSON parsing, document links,
breadcrumb limit and diff checks passed. The local five-axis review checked
chronology, exact-SHA/check binding, immutable report hashes, owner receipts
versus independent evidence, privacy and Core boundary; no blocking defects
were found. Most added lines are archived machine-readable evidence.

The required local advisory OCR command was attempted once, selected five JSON
files and reached its external 180-second limit (exit 124), with no complete
review delivered. [CI OCR](https://github.com/npcink/npcink-governance-core/actions/runs/39001760116)
failed all five selected files, zero comments and zero tokens. This is an
undelivered review, not a clean review. The PR body records the limitation;
no retry, workflow change or required-check bypass occurred. OCR/provider
investigation stays separate from this docs publication.

## Evidence and remaining boundaries

The original default [7/7 report](cloud-publication-default-matrix-2026-10-17.json)
remains unchanged, SHA-256
`145cd7929faeeb9f2d63f843e921974ee6a56d719f9a9f56411a7fd42987a777`.
It binds Core `03be509f`, Cloud `fbdfa372`, Toolbox `a2ad8981` and the other
four recorded revisions. It is not evidence that this squash or later owner
HEADs received another full matrix. No cross-repo gate, candidate acceptance,
WordPress live smoke, deployment or M4 promotion was rerun for this docs-only
publication. Topic/squash tree identity and exact merge CI cover publication.

Fixed RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Both complete local and remote tag inventories are byte-identical before/after
publication. No tag was created, pushed, moved or removed; no new candidate was
selected. Original branches, sibling worktrees, both saved stashes, Addon drift,
Toolbox quality/human-label work and Cloud real mailbox/writing human acceptance
remain preserved. No new owner message or sibling mutation occurred.

## Change envelope and next documentation batch

This session targets Core release coordination documentation only: this receipt,
its JSON evidence, next-stage plan and bounded breadcrumb/archive rotation.
No executable code, public REST/data/lifecycle contract, sibling source, fixed
manifest or release tag changes. Gates are metadata, Core default checks and
JSON/link/diff verification. Rollback is reverting this receipt; the former
three commits remain published through #178.

This post-merge receipt is one **new local documentation commit** on
`codex/weekly-docs-merge-receipt-2026-10-17`, reserved for the next weekly docs
batch. It is intentionally outside #178; do not open a second PR merely to
publish its own merge receipt. The former three-commit backlog is closed.

See [machine-readable publication evidence](core-weekly-docs-publication-2026-10-17.json).

Receipt verification: `composer test:all` passed again after the documentation
update (55 PHPUnit tests / 207 assertions and all component gates). Composer
metadata, receipt JSON, local links, ten-entry breadcrumb, archived matrix and
fixed-manifest hashes, and `git diff --check` passed.
