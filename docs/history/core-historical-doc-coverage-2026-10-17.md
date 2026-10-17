# Core historical post-merge documentation coverage — 2026-10-17

Status: coverage audit complete. Five original patches are already published,
one old limitation has an accepted/implemented semantic successor, and one
historical UX synthesis remains unpublished. All original refs are retained;
this audit creates no PR and performs no cleanup.

## Change envelope and evidence boundary

Target: Core documentation-history reconciliation only. Expected changes are
this report/JSON, next-stage plan and bounded breadcrumb/archive rotation. No
source code, accepted ADR text, public contract, sibling work, branch/tag refs,
manifest, runtime or release identity is changed. Gates: commit/patch/PR/tree
proof, content/history comparison, JSON/links/diff and Core default checks.
Rollback: revert this docs-only audit commit; original source refs stay intact.
No full matrix is required for this documentary coverage checkpoint.

The audit baseline is origin/master
`289ce777eff158e8f2d5088c30d90866efc87e2c` (#179); the working receipt starts
at `89adeb8f9ce33423d9c0647f79b0ffcec1838df7`. All ten local branches were
reviewed. The relevant set is six post-PR documentation commits across five
historical topics plus the independent local UX-summary commit. The current
post-#179 receipt is normal weekly documentation, not a newly discovered old gap.

## Five exact patches were published through later PRs

| Original local commit | Published equivalent | Later merged PR | Stable patch-id |
| --- | --- | --- | --- |
| `418a081d` | `8d9626b7` | [#167](https://github.com/npcink/npcink-governance-core/pull/167) | `01657c672a5f414ec6a554192a817441962b6a91` |
| `4a23d358` | `50f72a2a` | [#168](https://github.com/npcink/npcink-governance-core/pull/168) | `ab7cd46c3fbf72ea4efb9d71f3264750b7cec4ea` |
| `31279cf6` | `1d8a2144` | [#168](https://github.com/npcink/npcink-governance-core/pull/168) | `6d07ddfb68dffa8d2b784ae4a1ba348b62ebe80f` |
| `8d6083e2` | `4006c644` | [#169](https://github.com/npcink/npcink-governance-core/pull/169) | `c33edd215e8281865a5afd9ac6610a2ee7386311` |
| `e93c3303` | `0d7153a9` | [#176](https://github.com/npcink/npcink-governance-core/pull/176) | `e94194d962d9a36c48c19789d91d6f4fd05753b7` |

For each row, the original and replayed commit have equal stable patch IDs;
the replay is an ancestor of that PR's reviewed head; the final PR head and
squash trees are identical; and the squash is in current origin/master history.
The original raw commit itself is not an ancestor of origin/master. That is
expected after replay and squash and does not indicate missing documentation.
Subsequent edits/archives can replace old wording without invalidating this
historical publication proof.

The branch-level disposition is therefore:

| Retained branch | Post-PR original commits | Coverage | Action now |
| --- | --- | --- | --- |
| `codex/docs-week-2026-10-13-followup` | `418a081d` after #166 | #167 via `8d9626b7` | Close republish need; keep ref |
| `codex/docs-classification-lane1-2026-10-16` | `4a23d358`, `31279cf6` after #167 | #168 via `50f72a2a`, `1d8a2144` | Close republish need; keep ref |
| `codex/docs-rc-identity-2026-10-16` | `8d6083e2` after #168 | #169 via `4006c644` | Close republish need; keep ref |
| `codex/docs-rc-post-tag-drift-2026-10-16` | `e93c3303` after #175 | #176 via `0d7153a9` | Close republish need; keep ref |

These branches need no duplicate docs PR or replay. This audit does not
convert that finding into deletion authorization. The prior audit was correct
to retain them until this exact coverage comparison was done.

## Old preflight limitation: semantic successor, not a missing code feature

`codex/execution-verification-reads@e73f9684` added 11 lines under a
`Limitations` heading to the then-colliding
`ADR-011-execution-attached-verification-reads.md`, after implementation #128.
That literal paragraph was not found in master history. The canonical
[ADR-012](../decisions/ADR-012-execution-attached-verification-reads.md) was
renumbered through [#132](https://github.com/npcink/npcink-governance-core/pull/132)
and does not contain that heading.

Its material constraints are nevertheless covered by the successor chain:
[#135](https://github.com/npcink/npcink-governance-core/pull/135) introduced
the post-execution decision, [#136](https://github.com/npcink/npcink-governance-core/pull/136)
renumbered it to [ADR-013](../decisions/ADR-013-post-execution-verification-read-minting.md),
[#138](https://github.com/npcink/npcink-governance-core/pull/138) implemented
provisional-record minting, and [#139](https://github.com/npcink/npcink-governance-core/pull/139)
added result-bound membership/reference-only hardening. Each merge is in the
current default history. ADR-013 explicitly preserves:

- preflight cannot bind an object created later in the execution;
- Core verifies execution evidence; Adapter assertions alone grant nothing;
- in-transaction objects use the new post-execution decision/record path;
- statically addressed objects continue using preflight minting.

Do **not** cherry-pick the old paragraph into the current decision as a live
unresolved product limitation. Retain `e73f9684` as historical source. There is
a narrow documentation defect to fix separately: ADR-013 line 75 says it
supersedes ADR-012's `Limitations` section, but that section is absent.

A reviewable correction is to replace that nonexistent-section reference with
“Resolves the preflight-only limitation described in Context for in-transaction
objects; ADR-012 preflight minting remains for statically-addressed objects.”
This is a historical-reference/link correction, not a new decision, API,
lifecycle change or runtime fix. No ADR source was edited by this audit.

## Local UX summary: implementation merged, synthesis still needs salvage

`zcode/docs-2026-10-11-ux-audit-arc@3c45612c` changes only breadcrumb/archive.
Its heading “User-perspective UX audit implemented across four merged PRs”
exists in that local source but not in current main's `.sisyphus` content or
its heading-addition history. The full narrative and incident lessons have
therefore not been demonstrated as published; a same-name PR was not found.

The implementation is already published:

- [#151](https://github.com/npcink/npcink-governance-core/pull/151): submitted
  action input and registry-label fallback;
- [#152](https://github.com/npcink/npcink-governance-core/pull/152): audit event
  labels/filters and their completeness contract;
- [#153](https://github.com/npcink/npcink-governance-core/pull/153): expired
  unreviewed proposals and expiry guidance;
- [#154](https://github.com/npcink/npcink-governance-core/pull/154): admin polish.

All four merges are ancestors of the current default. This is not a missing
code fix. Current admin guidance also covers the shipped surfaces; what remains
is the historical synthesis, the corrected “revoke already had confirmation”
finding, and incident-specific engineering lessons.

Prepare a focused follow-up from the **current** baseline, preferably
`docs/history/user-perspective-ux-audit-2026-10-11.md`, covering the four PRs
and these dated source lessons:

1. OCR can be invalidated by a checkout switch; use isolated worktrees and bind
   review evidence to the before/after SHA.
2. `wp i18n make-pot` can derive `Report-Msgid-Bugs-To` from the directory name;
   verify the canonical plugin slug after generation. The present POT has the
   correct Core slug; this audit did not regenerate catalogs.
3. Follow the catalog's declared plural forms instead of assuming a locale
   shortcut. Current Core zh_CN declares `nplurals=2; plural=0` and contains
   both plural entries; this is a repository-specific fact, not a universal
   Chinese-locale prescription.
4. Check sibling checkout movement before blaming Core for integration-smoke
   drift; existing workflow rules already cover the general ownership principle.

Keep source date/owner provenance, verify claims and PR links, and distinguish
historical reported OCR results from a new review. Do not cherry-pick the whole
old breadcrumb: it would also move old entries and revive outdated pre-tag,
ADR-conflict, package-hash and zero-ledger status. The original local branch
stays untouched until salvage/publication and owner disposition are complete.

## Remaining refs and concrete follow-up list

| Item | Current disposition |
| --- | --- |
| `codex/verification-reads-hardening@f4b17dba` | Reviewed ancestor of #131 head; no later doc commit on this tip. Keep source; no republish identified. |
| `codex/weekly-docs-merge-receipt-2026-10-17@8df58884` | Exact published #179 head; no later doc commit. Retain source anchor. |
| Current `89adeb8f` receipt | Intentional local post-#179 record for weekly batching; separate from old gaps. |
| Local `master` | Protected base branch; not a legacy-topic cleanup target. |

The next documentation slice has **two** items: (1) salvage the UX historical
synthesis without stale state, and (2) correct ADR-013's nonexistent-section
reference. Neither requires replaying the five already published patches or
republishing the UX implementation. This turn only records the list; no old
patch, ADR correction or new PR was applied.

## Preservation, verification and next batch

Every original local branch/tag and actual origin branch/tag ref was unchanged
through investigation. The later docs-only audit commit advances only the
current receipt branch. Fixed RC2 manifest SHA-256 remains
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
No branch/stash/worktree cleanup, owner message, sibling mutation, tag operation,
new candidate, full matrix, exact acceptance, production or M4 action occurred.
Other owner unfinished work remains outside this audit.

[Full commit/PR/patch and content evidence](core-historical-doc-coverage-2026-10-17.json)
records all ten refs, the five proofs, successor PRs and follow-up scope.
This new local audit joins `89adeb8f` for the next weekly docs batch. No claim
of complete historical closure or new release acceptance is made.

Documentation verification passed: `composer test:all` (55 PHPUnit tests /
207 assertions and all component gates), Composer metadata, JSON/count/follow-up
scope checks, local links, ten-entry breadcrumb, unchanged manifest and diff
checks. A direct all-state PR query for the UX source branch returned no PR.
Final local/remote branch and tag inventories remained unchanged before this
docs commit; no sibling or owner resource was touched.
