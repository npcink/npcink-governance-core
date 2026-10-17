# UX/history weekly documentation publication — 2026-10-17

Status: [PR #180](https://github.com/npcink/npcink-governance-core/pull/180)
merged automatically at `2026-10-17T06:20:31Z` as
`c6031aff5f15b0ab568479e1099314c1e901b6a8`. Final topic head
`27c6c570540f906c8d4653836ed16dd17986c905` and squash have identical trees.
The four commits `89adeb8f`, `55c8768`, `ddcbc78`, `27c6c57`
are now published through this squash; original source branches remain intact.

## Change envelope and completed scope

Core documentation reconciliation only, with the matching static ADR reference
assertion. Published the two accumulated post-#179 receipt/coverage records,
[the UX retrospective](user-perspective-ux-audit-2026-10-11.md), its workflow
link, and the ADR-013 historical-reference correction. Updated the plan and
rotated rolling breadcrumbs into the existing archive. No runtime/public
contract, accepted ADR decision, sibling implementation or release identity
changed. Rollback is a revert of this documentation/assertion batch.

The historical UX implementation was already merged in #151–#154; its merge
SHAs and current-master ancestry were rechecked. The durable record preserves
the corrected finding that revoke already had confirmation, stable-checkout
review practices, catalog slug/plural checks, integration-source attribution,
and evidence limits. It excludes obsolete pre-tag/ADR-conflict/ledger status.
ADR-013 now refers to the preflight-only limitation in Context and links the
actual ADR-012 file. The old assertion required the nonexistent section's
literal name; it was corrected to require the real link and reject that claim.
No old runtime limitation was replayed.

## Verification and review coverage

- Local Composer metadata, `composer test:all` (55 tests / 207 assertions and
  component gates), `composer check:wporg`, changed JSON/local links, ten-entry
  breadcrumb, scope and whitespace checks passed on final topic content.
- Both required protected checks, Static contracts and PR body contract,
  passed. Strict base protection remained enabled; the publisher requested
  normal matched-head squash auto-merge. No bypass or admin merge was used.
- Merged-SHA Static contracts passed on `c6031aff`, run `39009552269`.
- Manual review covered correctness/provenance, readability, governance
  boundaries, privacy and runtime/performance impact; no blocking issue found.
- The first local OCR attempt was interrupted when the ADR assertion needed
  correction; final-head OCR hit the 180-second bound. CI advisory run
  `39009468162` failed all three selected items with zero comments and zero
  tokens. These are coverage gaps, **not** successful zero-finding reviews.
  The PR body records both. No new browser/WP acceptance or full matrix is
  claimed for this documentation/assertion change.

## Preservation and next disposition

Before/after local and actual remote tag maps are identical. Fixed RC2 manifest
SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Every pre-existing historical local branch retains its exact tip; only this
session's publication topic advanced to the reviewed head. All pre-existing
remote source backups are unchanged; remote master advanced by #180 and the
publication topic was added. The new local receipt branch starts at the merge.
No branches, worktrees or stashes were removed; no sibling resource was changed.

The two historical documentation follow-ups are complete and merged. Other
owner source/dependency/human-acceptance work remains open under its existing
ownership; no release readiness, new candidate, M4 promotion or production
closure follows from this docs publication. No tag was created, pushed or moved.

This post-merge receipt is a **new local record for the next weekly docs PR**,
not an unpublished implementation or reason to open a recursive PR today.
See [structured publication evidence](core-ux-weekly-docs-publication-2026-10-17.json).
