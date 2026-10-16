# Session Review And Next Stage — 2026-10-16

Status: historical summary with a live-state recalibration.

This note summarizes the referenced Codex task **Review pending work and next
steps** and records where that work stands after a fresh read-only check on
2026-10-16. It is a handoff record, not a release approval.

## Current position

The referenced session reached an engineering acceptance checkpoint on
2026-10-15:

- the default cross-repository matrix passed 7/7;
- `composer acceptance:cross-repo-release` passed Core, Adapter, and Toolkit,
  including the signed Ollama provider fixture, persisted log correlation,
  duplicate refusal, package installation, PHPStan, and Docker smoke;
- Core remained clean and its documentation branch ended at `4841135`;
- temporary provider loaders, SDK files, marker fixtures, drafts, tunnels, and
  Docker smoke resources were removed;
- no tag, SVN publication, production deployment, or real-user ledger row was
  created.

That green result is historical evidence for the exact revisions tested on
2026-10-15. A fresh status-only matrix on 2026-10-16 shows that the AI Cloud
owner is working again in its shared checkout. Six files are dirty, so the
current default matrix is `needs_validation` until that owner finishes or
hands off the work. The previous 7/7 result must not be reused as today's
release evidence.

## What the session completed

### Core governance and contract work

The session carried the Core slice from a flagged-media governance intake to a
reviewable, fail-closed handoff. The completed behavior includes:

- allowlisted `flagged_media_removal_plan.v1` intake;
- backup evidence or an explicit no-backup attestation, with the two branches
  mutually exclusive;
- manual approval in every policy mode, including `dev_allow_all`;
- branch-evidence validation during approval and commit preflight;
- protection against direct `delete-attachment` proposal bypasses; and
- description-only audit evidence without storing deletion contents or URL
  lists.

The cross-repository implementation and regression work then landed in the
owning repositories. Core also completed the Admin_Page extraction (#159),
test-runner split (#160), M4 candidate-version binding (#161), and the
documentation closeout records (#162–#165). The current Core branch is clean,
and `composer test:all` has repeatedly passed with 55 tests and 207
assertions.

### Cross-repository acceptance

The session recovered the one-time M4 Ollama path using the verified provider
SDK, `qwen2.5:1.5b`, and a loopback tunnel. The final acceptance proved:

- Core proposal, approval, preflight, and audit boundaries;
- Adapter signed request and execution handoff;
- Toolkit ability execution and restore behavior;
- persisted provider-log correlation for proposal, preflight, and Adapter
  request identifiers;
- duplicate execution refusal and cleanup; and
- the Toolkit Docker smoke and PHPStan gates.

The provider registry was restored to `npcink-cloud` after the run. The strict
log assertion stays in place: a successful AI response without a matching
persisted provider log is a failure.

### Classification regression

The three-lane classification regression was repaired and re-run in an
isolated Toolbox worktree:

- Lane 2, the existing-attachment featured-image path, passed as
  `local_admin_consent`: it created the two expected consent audit events,
  created no proposal, and restored the fixture.
- Lane 3, the high-risk article/media batch path, passed as
  `core_proposal_required`: it created and purged the expected proposal,
  performed no WordPress write, and emitted no local-consent audit event.
- Lane 1, native editor/generic AI-plugin acceptance, remains open as a
  release evidence item. The referenced session included a user-assisted
  draft save and confirmed unchanged Core counts (315 proposals and 3,234
  audit rows before and after), but the closeout record did not establish the
  full required evidence of visible generic AI output followed by a normal
  present-author editor save. It must not be relabeled as complete without
  that exact evidence.

The corrected Toolbox fixture was committed as `da02356` and later merged as
`444128a`. Its repair models the present-admin nonce, cookie, and same-origin
headers and checks abilities through the WordPress Abilities API.

### Ownership and process lessons

The session correctly separated four claims that had previously been blended:

1. a local deterministic gate passed;
2. a protected pull request merged;
3. the configured default shared checkouts were clean at a specific time; and
4. real-user evidence exists.

A clean detached worktree can prove an exact revision, but it cannot prove
that a shared owner checkout is clean. OCR remains advisory; provider or
network failures were recorded as exceptions and were not reported as clean
AI review results. The session also preserved locked worktrees, owner files,
open PRs, and unpublished branches rather than deleting or resetting them.

## Live state checked on 2026-10-16

The status-only matrix was generated at `2026-10-16T02:55:44Z` with gates not
run. The configured default checkouts were:

| Repository | Revision / branch | Live state |
| --- | --- | --- |
| Core | `4841135`, `codex/docs-week-2026-10-13-followup` | clean |
| Cloud Addon | `b31a37a`, `master` | clean |
| AI Cloud | `fca129f8`, `codex/provider-refresh-advisory` | dirty: 5 modified and 1 untracked file |
| Toolbox | `216e102`, `codex/internal-link-recommendation-followup` | clean |
| Adapter | `eb73e50`, `codex/m4-pgy-stack-rc-docs-2026-10-15` | clean |
| Toolkit | `921d1dc`, `master` | clean |

The six current AI Cloud files are:

- `frontend/src/app/admin/ai-resources/page.tsx`;
- `frontend/src/components/admin/SupplierConnectionTables.tsx`;
- `frontend/tests/e2e/admin-provider-directory-v2.spec.ts`;
- `frontend/tests/unit/admin-ai-resources-contract.mjs`;
- `frontend/tests/unit/admin-provider-config-pc-v2-contract.mjs`; and
- `frontend/src/features/admin/ai-resources/async-operation-state.ts`.

The diagnostic RC matrix still reads Core `0.3.0`, Adapter `0.4.3`, and
Toolkit `0.7.0`. It correctly reports Core `v0.3.0` and Toolkit `0.7.0` as
existing at historical commits; neither tag may be moved. Adapter `0.4.3` is
available for a future, explicitly authorized release decision.

## Owner reconciliation and matrix rerun — 2026-10-16

The AI Cloud owner work was reconciled without touching another task's files.
The async Provider Admin state model was committed as `e5aa6143` on
`codex/provider-async-state-followup`, pushed, and published as PR #1117. Its
required exact-SHA checks passed, including frontend after one controlled retry;
the separate `code-review` job failed as an advisory result and is not merge
authority. AI Cloud is now clean.

The subsequent no-override command
`composer quality:matrix:run -- --fail-on-dirty` generated
`2026-10-16T03:45:30Z` and passed all seven repository gate rows, including the
AI Cloud exact-SHA check. The command still returned exit 1 because Cloud Addon
has two unrelated owner files (`docs/README.md` and
`docs/next-session-handoff-2026-10-16.md`). Those files remain untouched. The
current state is therefore **7/7 gates passed, strict clean-matrix closeout
blocked by Cloud Addon owner work**.

## Recommended next sequence

1. **Reconcile the Cloud Addon owner checkout.** The owner should commit valid
   work to its topic branch, discard it through the owner's own process, or
   document an explicit handoff. Do not reset, delete, or commit those files
   from Core.
2. **Run the strict default matrix after Addon reconciliation.** Use
   `composer quality:matrix:run -- --fail-on-dirty` from
   `npcink-workflow-toolbox`. The gate rows already pass 7/7; this rerun is
   needed to prove that all configured shared roots are clean as well.
3. **Audit worktree ownership before cleanup claims.** Recheck locked
   worktrees, untracked owner files, open PRs, and unpublished branches. Keep
   valuable or locked work intact and record ownership; do not equate a clean
   default path with global cleanup.
4. **Close classification Lane 1 with exact evidence.** Use a present
   administrator, visible generic AI output, normal editor save or publish,
   unchanged Core proposal/audit counts, and fixture cleanup. Keep this
   evidence separate from Core proposal and local-consent lanes.
5. **Decide the RC identity before any publication.** Refresh exact revisions,
   run the full acceptance gate, then choose either a new plugin version or a
   clearly named stack snapshot RC. Never move the historical Core or Toolkit
   tags. Tagging, SVN, and deployment require explicit authorization.
6. **Keep real usage parked until the product state changes.** The owner has
   confirmed local-only development. Leave the ledger empty until an operating
   site produces the first real-user record; fixtures, M4 provider evidence,
   and an administrator acceptance run do not count as real usage.
7. **Publish the accumulated docs through the weekly docs flow.** Before
   publication, run the Core metadata validation, `composer test:all`, and
   `git diff --check`; use the repository's protected `composer pr:publish`
   flow rather than a direct push.

The principal next gate is therefore Cloud Addon owner reconciliation followed
by a fresh strict default matrix. Release and real-usage work should remain
downstream of that gate.
