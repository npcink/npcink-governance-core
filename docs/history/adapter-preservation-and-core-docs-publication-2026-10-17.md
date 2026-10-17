# Adapter Exact Source Preservation And Core Docs Publication — 2026-10-17

Status: Adapter preservation complete; seven accumulated Core documentation
commits published and merged. Other owner work remains preserved.

## Adapter exact source is now available from origin

The exact documentation commit
[`eb73e503a8c102acd6f87b69f406ee4b94aac85f`](https://github.com/npcink/npcink-ai-client-adapter/commit/eb73e503a8c102acd6f87b69f406ee4b94aac85f)
was pushed unchanged to:

```text
https://github.com/npcink/npcink-ai-client-adapter.git
refs/heads/codex/m4-pgy-stack-rc-docs-2026-10-15
```

The destination branch was absent before this action. Push used an explicit
commit-to-branch refspec, `push.followTags=false`, no force and no upstream
configuration change. Adapter's checkout, HEAD and product files were preserved;
its master was not changed or merged. The commit changes only
`docs/acceptance/local-ai-client-smoke.md` and
`docs/archive/2026-10-15-m4-pgy-stack-rc-readiness.md`.

Verification at `2026-10-17T03:01:56Z` used three independent reads:

1. `git ls-remote` resolves that named origin branch to the exact SHA.
2. An empty temporary bare repository fetched the branch with `--depth=1
   --no-tags`; `FETCH_HEAD` is exactly `eb73e503`, and its tree equals local
   tree `cd78059dd4243cfc5239b4950ad348070e9d017f`.
3. GitHub's commit API now resolves the same SHA; the former HTTP 422 / missing
   source finding is closed.

The temporary bare repository was removed afterward. Preserve this named
source ref during later housekeeping: it makes the historical manifest pin
retrievable without moving a tag or rewriting a candidate. This action saves
the source; it does not turn the historical acceptance into new runtime evidence.

## Core's seven accumulated docs commits merged

[Core PR #177](https://github.com/npcink/npcink-governance-core/pull/177)
published the original branch `codex/rc2-validation-2026-10-16` at
`f5181a3ebe347624c468dbb36623421868c88857`, containing:

| Original commit | Documentation scope |
| --- | --- |
| `cac1689` | Separate RC2 manifest and acceptance |
| `a249fcb` | Default-root owner blockers |
| `0a63aab` | PR1119 handoff and pending integration |
| `4753aa4` | Owner closeout and default matrix |
| `79c247f` | Historical closure audit and saved-work rules |
| `13031b8` | Merged Toolbox Lane 3 repair and real WP cleanup |
| `f5181a3` | Toolkit/Cloud readiness and default 7/7 |

All 21 changed files are Markdown/JSON; no executable source or public contract
changed. Local validation passed: 55 tests / 207 assertions, PHPStan, Composer
metadata, WordPress.org guard, changed JSON, Markdown links, breadcrumb bound
and whitespace. The body started from the repository template and publication
used `composer pr:publish` with tag-following disabled.

The exact PR head passed both protected checks, **Static contracts** and
**PR body contract**. Protected squash auto-merge completed at
`2026-10-17T03:06:09Z` as
`9d153849b2fc130e7d3ab7e2a090995e184954a1`. The merged SHA's own Static
contracts job also passed. Its tree is exactly equal to the seven-commit
topic tree, `840bf2fc476657abaa482bfd9d69d2734adedf9e`.

Both local and remote original topic refs remain at `f5181a3`; the seven
original SHAs are preserved. Squash ancestry alone must not be used to label
these changes unmerged. The coordinating checkout moved to a fresh receipt
branch based on the merged origin/master without resetting or deleting the
original branch.

## Advisory review limitation

The required local advisory OCR command was attempted before publication. It
selected ten JSON evidence files and completed two groups without findings,
then reached its bounded 180-second timeout; no complete review was delivered.
The PR body records that limitation and the completed local document review.

The separate [CI OCR run](https://github.com/npcink/npcink-governance-core/actions/runs/38997412881)
failed. Its result artifact reports all ten selected items failed, zero
comments and zero tokens. This is an undelivered review, not a clean review
or a code finding. No required check was bypassed. OCR/provider/configuration
investigation remains separate from this source-preservation/docs publication.

## Post-merge default matrix and preserved work

A full no-override `--fail-on-dirty` matrix was run after the Core merge and
before editing this receipt. At **`2026-10-17T03:08:09Z`**, it reported
**6/7 passed, overall exit 1**. Core passed on clean merged `9d153849` and
Adapter passed on preserved `eb73e503`.

The wider result reflects independent owner activity: Toolbox had seven
modified source/test/doc files at its sample (eight at the later snapshot),
and Cloud had moved to `c8469e26`, whose five exact-SHA source checks were
missing. Owner snapshots changed during the run; this is not frozen candidate
evidence. Those files, stashes, branches and worktrees were left untouched.

The earlier [02:45:41Z 7/7](stack-owner-readiness-2026-10-17.md) remains valid
only for its named revisions and clean snapshot. No new candidate was selected,
no full exact-candidate acceptance was performed, and no production or M4
promotion occurred. Addon drift and other owner housekeeping remain separate.

See the [publication/preservation evidence](adapter-preservation-and-core-docs-publication-2026-10-17.json)
and [original post-merge matrix](core-docs-publication-matrix-2026-10-17.json).
The fixed RC2 manifest checksum remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Core's local tags are unchanged, and the remote published snapshot remains
object `ffb5e5d6`, peeled commit `ed28f984`. No tag was created, pushed,
moved or removed.

## Receipt publication policy

The seven former local-only docs commits are now merged. This subsequent
publication receipt, stage update and breadcrumb form one new local docs-only
commit on `codex/docs-publication-receipt-2026-10-17`, retained for the next
weekly documentation batch. It is outside the merged PR and post-merge matrix's
tested source revision. Other unresolved work is not published or discarded
by this receipt.

Receipt verification passed Core `composer test:all` (55 tests / 207 assertions),
Composer metadata, exact source/merge evidence checks, original matrix and
manifest hashes, local Markdown links, the ten-entry breadcrumb bound and
`git diff --check`.
