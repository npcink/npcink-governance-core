# Authorized Core branch cleanup — 2026-10-17

Status: the explicitly authorized **nine local branch refs and one remote
branch ref are removed**. No other owner resource or tag was removed or changed.
This closes only the Core-owned subset of the [prior audit](branch-worktree-stash-audit-2026-10-17.md).

## Change envelope and authority

The human authorized the exact nine local Core candidates and the one separate
remote candidate, after fresh SHA/ownership checks. This overrides the prior
audit-only instruction for this explicit subset, without granting broader
cleanup. Scope is Core Git refs and release-coordination documentation: this
receipt/JSON, dated audit follow-up, stage plan and bounded breadcrumb/archive.
No public contract, executable source, sibling checkout, runtime, candidate
manifest or tag change. Gates are exact ref/source/PR/worktree checks and
Core documentation verification. Recovery uses preserved remote source refs
or the unchanged existing snapshot tag; no recovery ref is created here.

## Fresh preflight

At `2026-10-17T05:34:25.128607+00:00`, Core had 18 local branches and 16 actual origin
branches. Worktree was clean at `92c4a02a137cd0990446b83ce30d8e1c9449ed75` on
`codex/weekly-docs-merge-receipt-2026-10-17`. A no-tags fetch confirmed current origin/master
`95ef21089f281c16f69f8898d7eb89c3e7671795`. The app snapshot showed only this coordination
chat active in the Core working directory; no owner message was sent.

Each exact local SHA matched the authorized list and its named origin backup.
None was occupied by a registered worktree or an open PR. All nine live PR
records are merged to master at that exact reviewed head; each squash tree
matches its topic tree and is reachable from current origin/master. These
positive proofs replace an unreliable squash-ancestry-only deletion test.

The original `03be509f` matrix-tested commit remains included by remote
`codex/docs-publication-receipt-2026-10-17@b07972c1`; RC2 Core `7904f5b`
remains included by remote `codex/rc2-validation-2026-10-16@f5181a3e`.
Those exact remote refs were retained, not paired with local deletion.

## Nine local branches removed

All nine ordinary `git branch -d -- <branch>` operations succeeded. Each
branch's upstream was its exact same-SHA origin topic, so a forced local delete
was unnecessary. Branch deletion also removed that branch's local configuration;
no source file or worktree was switched, reset or removed.

| Deleted local branch | Exact former SHA | Merged PR |
| --- | --- | --- |
| `codex/docs-publication-receipt-2026-10-17` | `b07972c12f0fb822c674570f2cc5b22b2c8d8302` | #178 |
| `codex/docs-rc-acceptance-2026-10-16` | `4006c644a3d0a62393e1d53f418fac1fd3e5a50c` | #169 |
| `codex/docs-rc-acceptance-owner-handoff-2026-10-16` | `ed28f984153200a8362fcd0928bbb32b83636159` | #172 |
| `codex/docs-rc-audit-refresh-2026-10-16` | `0d7153a98fe176b29063b0cd6141601ef26c78df` | #176 |
| `codex/docs-rc-final-closeout-2026-10-16` | `19953429829f237551b752b8438e12b0f290a385` | #170 |
| `codex/docs-rc-matrix-drift-2026-10-16` | `ec01042c7862a47a2534eb5f6cf9d35cf5d26e4a` | #171 |
| `codex/docs-rc-release-notes-2026-10-16` | `fe70ede92fa42a6f0880c1c9543ca34fe9ff9472` | #173 |
| `codex/docs-rc-tag-published-2026-10-16` | `94af361fa20bacd441506fd9d3be13e5d5aa8de1` | #174 |
| `codex/rc2-validation-2026-10-16` | `f5181a3ebe347624c468dbb36623421868c88857` | #177 |

## One separately authorized remote branch removed

Only origin's `refs/heads/codex/docs-rc-acceptance-owner-handoff-2026-10-16` was deleted, at expected
`ed28f984153200a8362fcd0928bbb32b83636159`. The explicit deletion refspec used
an exact-SHA lease plus disabled tag following; it could not silently delete a
newly advanced branch tip. No broad remote prune or other remote deletion ran.

PR #172 is merged and tree-equivalent. The unchanged existing snapshot tag
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0` still has object
`ffb5e5d6d7b68fa952288524a181ff688a7698ee`, peeled commit
`ed28f984153200a8362fcd0928bbb32b83636159`. It retains this exact source after
both branch refs are gone. The tag was neither created, pushed, moved nor deleted.

## Post-cleanup preservation checks

At `2026-10-17T05:35:54.316238+00:00`, the full before/after maps prove:

- Core local branches **18 → 9**, with exactly the nine authorized removals.
- Actual origin branches **16 → 15**, with exactly the one authorized removal;
  every other remote branch and SHA is identical, including all eight remaining
  source backups for the local-only deletions.
- Complete local/remote tag maps are identical. RC2 manifest SHA-256 remains
  `79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
- Current HEAD/branch, registered worktrees and stash refs are unchanged; tracked
  and non-ignored untracked state was clean before editing this documentation.

The [machine-readable receipt](core-scoped-branch-cleanup-2026-10-17.json)
contains the exact preflight, nine live PR records, per-operation exit/output,
post-cleanup maps and recovery sources. The later documentation commit advances
only the current receipt branch and is outside that captured pre-documentation
HEAD comparison.

## Preserved remaining work and recovery

Other-owner 17 local candidates, Cloud's auxiliary worktree, Toolbox's saved
stash, unresolved Core/Toolkit/Eval source, Addon workflow diagnostics, Adapter's
exact pin and all open dependency PRs remain outside this cleanup. No sibling
repository was mutated; no worktree/stash was removed. Prior family-wide totals
are historical; this operation reports freshly verified **Core-only** counts.

For the eight local-only deletions, original source remains on each named origin
branch. The ninth local ref and the deleted remote ref can be reconstructed
from the snapshot's verified `ed28f984` commit if a later authorized recovery is
needed. Full original SHAs are recorded; no tag movement or new tag is needed.

No new PR, branch publication, matrix, exact acceptance, new candidate,
production operation or M4 promotion occurred. The new docs-only receipt joins
the two already local records for the next weekly documentation PR. Broader
housekeeping and owner decisions remain open; no all-history closure is claimed.

## Documentation verification

`composer test:all` passed after the receipt update: 55 PHPUnit tests / 207
assertions, lint, static contracts, fail-closed injection, reproducible package
and revision-bound smoke-evidence behavior. Composer metadata, receipt JSON,
local links, breadcrumb's ten-entry bound, removed branch configuration,
unchanged manifest hash and `git diff --check` also passed. These checks do not
imply a new live WordPress run, full matrix or candidate acceptance.
