# Owner/release weekly documentation publication — 2026-10-17

Status: [PR #181](https://github.com/npcink/npcink-governance-core/pull/181)
merged through protected squash auto-merge at `2026-10-17T07:08:56Z` as
`9f5b2bf25451b283c154bd5419564345fadfdb66`. Reviewed topic
`aec4acb7d0dd9ff77fcabd87e89bdcb0c454f2c2` and squash have identical trees.

## Scope and publication outcome

The three accumulated commits `f764306b`, `5be60b35`, `aec4acb7` (nine
Core documentation/evidence files) are now published: #180 merge receipt,
other-owner classification and Adapter publication/Addon drift review.
They retain their observation dates; this publication did not refresh owner
status, update APIs or rerun historical matrix/acceptance evidence.

Core documentation coordination only: this receipt/JSON, next-stage plan,
breadcrumb/archive. No public contract or source change. Gates are Core
metadata/default tests/wporg, docs integrity, required checks and merge-tree/
ref/tag preservation. No matrix or WordPress runtime smoke is necessary for
this docs-only change. Rollback is a documentation revert; preserve source refs.

Standalone Adapter publication, its separately frozen RC3 compatibility
acceptance, fixed Core RC2 and current shared checkouts remain distinct.
The batch does not update the fixed manifest, deploy Addon code or complete
Cloud missing-record recovery. See [the release/drift review](adapter-release-addon-drift-review-2026-10-17.md)
and [classified remaining items](other-owner-closeout-refresh-2026-10-17.md).

## Verification and honest review coverage

- Local `composer validate --no-check-publish`, `composer test:all` (55
  PHPUnit tests / 207 assertions and component gates) and
  `composer check:wporg` passed on final topic content.
- Changed JSON/local links, ten-entry breadcrumb, docs-only scope, diff and
  fixed-manifest checks passed. Five-axis manual review covered provenance,
  correctness, readability, governance boundaries, privacy and runtime impact;
  no blocking issue was identified.
- Both required checks, Static contracts and PR body contract, passed.
  Strict protection remained enabled; normal squash auto-merge completed
  without an admin override or bypass.
- Merged-SHA Static contracts passed on `9f5b2bf2`, run `39012548194`.
- Local advisory OCR hit the 180-second limit without a complete report.
  CI advisory run `39012473676` failed all three selected items, zero comments
  and zero tokens. PR body records the gap. This is not a successful clean
  external review; no retry or workflow change was made.

## Preservation and remaining work

All original Core local branches retain their exact tips. Actual remote
non-master heads remain unchanged; the only new remote head is the intended
publication topic. Core master advanced by the approved squash. All complete
Core local/remote tag maps and fixed RC2 bytes are unchanged; manifest SHA-256:
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.

No branch deletion, worktree/stash cleanup, sibling checkout mutation, owner
message, model/business call, candidate selection, runtime acceptance,
production/M4 deployment or tag operation occurred. The original publication
topic is retained locally/remotely; a fresh receipt topic starts at the merge.

Other owners' unfinished budget/real-use/quality/dependency/source work remains
open. Adapter update-channel and Addon site-update/live-recovery follow-ups
remain as dated in the published records; no new status is inferred here.
The former three-commit backlog is closed. **This post-merge receipt is one
new local record for the next weekly docs batch**, not a recursive PR today.
[Structured publication evidence](core-owner-release-weekly-publication-2026-10-17.json).
