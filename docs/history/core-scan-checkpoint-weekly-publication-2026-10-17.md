# Scan checkpoint weekly documentation publication — 2026-10-17

Status: [PR #184](https://github.com/npcink/npcink-governance-core/pull/184)
merged at `2026-10-17T09:03:01Z` as `a0d26847d77e273f9c8047aff28aeda02c9d77a0`.
Exact reviewed head `75466f6e90ae1637748e1d11bce725a9ad5e13e6` and squash trees match.

## Scope and publication result

Two accumulated commits `7fa4c33` / `75466f6`, seven docs/evidence files, are
published: #183 receipt and the appended 16:52 channel/scan checkpoint. Original
observation dates remain. No live API/site/runtime data was reread by this PR.
The [checkpoint](adapter-channel-natural-reconciliation-2026-10-17.md) keeps
accepted index coverage separate from unfinished cursor catch-up, direct
natural-trigger attribution and the meaning of an overdue event.

Core documentation only: this receipt/JSON, plan and bounded breadcrumb/archive.
No public contract or source change. Gates: Core metadata/default tests/wporg,
docs integrity, required/protected checks, tree/ref/tag proof. No full matrix
or WordPress runtime smoke is needed for docs publication. Rollback is a docs
revert retaining indexed data, source backups and original owner resources.

## Verification and review limits

- Local Composer metadata, `composer test:all` (55 PHPUnit tests / 207
  assertions and component gates) and WordPress.org guard passed on exact topic
  content. JSON/local links, ten-entry breadcrumb, scope/diff and manifest passed.
- Five-axis review covered dated correctness/provenance, readability, governance
  boundaries, privacy and runtime/performance impact; no blocker identified.
- Static contracts and PR body contract passed, including the body refresh.
  Strict branch protection remained enabled. Publisher requested auto-merge;
  normal protected exact-head squash completed while advisory status was
  UNSTABLE. No admin override or required-check bypass was used.
- Merged-SHA Static contracts passed on `a0d26847`, run `39019650917`.
- Local OCR hit 180 seconds without a complete report. CI advisory run
  `39019540038` failed both selected items with zero comments/tokens. PR body
  and receipt retain the coverage gap; no completed/clean external review is
  claimed. No OCR retry or workflow/protection change occurred.

The first publisher invocation failed on GitHub API EOF/TLS before pushing or
creating a PR. A fresh remote-head/all-state PR query confirmed no partial
resources; a second standard publisher invocation succeeded. No duplicate PR,
force push or alternative publication path was used.

## Preservation and handoff

All original local branch/tag refs and actual origin non-master heads retain
their exact tips. Complete remote tag maps remain identical. Only the intended
remote publication topic was added and approved master advanced. The original
topic is retained; the new local receipt branch starts at the merged source.

Fixed RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
No original branch deletion, worktree/stash cleanup, sibling edit, owner message,
cron/flush/refresh, runtime acceptance, candidate selection, production/M4 or
tag creation/push/movement occurred. Other owner work remains independent.

The two-gap recovery stays accepted in its recorded window; Adapter offering,
periodic scan/call provenance and other items retain the checkpoint's dated
status. This publication does not close those remaining tasks.

The former two-commit backlog is closed. **This later receipt is one new local
record for the next weekly docs batch**, not a recursive PR today.
[Structured evidence](core-scan-checkpoint-weekly-publication-2026-10-17.json).

## Local receipt verification

The new receipt passed Composer metadata, `composer test:all` (55 tests / 207
assertions plus component gates), WordPress.org guard, JSON/local links,
ten-entry breadcrumb, diff and fixed-manifest checks. Core worktree paths and
stash refs remain unchanged; only the new receipt topic advances locally.
