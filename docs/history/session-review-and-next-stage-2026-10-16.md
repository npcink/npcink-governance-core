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
- Lane 1, native editor/generic AI-plugin acceptance, is now complete. A
  present administrator opened a disposable Gutenberg draft, triggered the
  WordPress AI plugin's visible excerpt suggestion, reviewed and edited the
  result, and clicked the native `保存草稿` control. The AI ability request
  returned 200, the native save issued `POST /wp-json/wp/v2/posts/282453`,
  Core counts stayed at 317 proposals and 3,366 audit rows before and after,
  and the draft was deleted after capture. No Core proposal or audit row was
  created.

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
`codex/provider-async-state-followup`, pushed, and merged by PR #1117. The
shared checkout now tracks clean `master` at `fe920796`. Its required exact-SHA
checks passed, including frontend after one controlled retry; the separate
`code-review` job failed as an advisory result and is not merge authority.

The final no-override command
`composer quality:matrix:run -- --fail-on-dirty` generated
`2026-10-16T04:01:39Z` and passed all seven repository gate rows with exit 0.
Cloud Addon is clean `master@d8bcac9`; AI Cloud is clean
`master@fe920796`. This closes the owner/worktree cleanliness gate for the
current engineering checkpoint.

## Classification Lane 1 completion — 2026-10-16

The final native-editor evidence used LocalWP `https://magick-ai.local` and
administrator user `1` with a disposable draft post `282453`. Before the
browser action, Core held 317 proposals and 3,366 audit rows. In the real
Gutenberg editor, the administrator clicked the generic WordPress AI plugin's
`生成文章摘要` control. The visible excerpt suggestion returned through
`POST /wp-json/wp-abilities/v1/abilities/ai/excerpt-generation/run` with HTTP
200 at approximately `2026-10-16T04:45:21Z`. The administrator opened the
excerpt editor, reviewed the suggestion, replaced it with a reviewed Chinese
excerpt, and clicked the native `保存草稿` control. The editor emitted the
normal `POST /wp-json/wp/v2/posts/282453` save with HTTP 200 at
`2026-10-16T04:47:04Z`.

After the save, Core still held exactly 317 proposals and 3,366 audit rows.
The disposable draft and one-time login helper were deleted. This proves the
Lane 1 boundary: visible AI output plus a present author's ordinary editor
save stays outside Core proposal and audit ownership. The screenshot and raw
provider text remain local-only; the committed record stores counts, route
shapes, status, and cleanup outcome only.

## RC identity decision — 2026-10-16

The central status-only matrix was refreshed at `2026-10-16T04:58:45+00:00`;
all seven default roots were clean. The exact manifest is Core
`8d9626b7a07ffca7126b400a404a6db05145d1e9`, AI Cloud
`fe9207965f4a5049f80736019114f360186ec96b`, Cloud Addon
`d8bcac9a192b04ad4aa2cc899cef32cdd6afe404`, Adapter
`eb73e503a8c102acd6f87b69f406ee4b94aac85f`, Toolkit
`921d1dc7cb5661fef10ca8f83b781690c98034cb`, Toolbox
`216e10230f8e120903c6bb476268ff9d263d556e`, and Eval Lab
`5362655d9f27f9db9a3bcc8a7717e75ac1d9659f`.

The chosen candidate identity is
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`. This is a named
stack snapshot decision, not a tag publication. Core `v0.3.0` remains bound to
historical `3357f916899ac4b013a753da6d99e712274f2221`; Toolkit `0.7.0` remains
bound to historical `46c1405422ae0ac1f8ea6820b391b48d09326a14`; Adapter
`v0.4.3` is still available. The `--require-tag-ready` check fails on the
existing Core tag as designed, and the proposed stack snapshot tag is absent.
No historical tag was moved or reused. Core later received docs-only
coordination commit `4a23d35`; that commit is not silently folded into the
candidate manifest.

## RC manifest acceptance — 2026-10-16

The exact-manifest rerun used a temporary ordinary clone at Core `8d9626b`,
with Adapter `eb73e503` and Toolkit `921d1dc7` supplied from their clean
checkouts. `composer acceptance:cross-repo-release` passed with exit 0;
completion was observed at `2026-10-16T05:44:19Z`. Core validation, 55 tests /
207 assertions, WordPress smoke, release verification, reproducible packaging,
Adapter source/smoke/package checks, the signed M4 Ollama fixture with
persisted provider-log correlation and duplicate refusal, Toolkit tests,
PHPStan, release verification, Docker official-stack smoke, and Toolkit
WordPress smoke all passed. Temporary Core clone, dependency link, plugin
symlink, Ollama loader, and SSH tunnel were removed; the shared Core symlink
was restored. No snapshot tag was created or pushed.

## PR #169 merge and final RC closeout — 2026-10-16

PR #169 (`docs: record exact RC acceptance`) merged through the protected
publisher at `2026-10-16T05:51:39Z` as
`34d75e2dc00804320a82086f175d69e7e5f14a76`. The merge records the exact
manifest acceptance and keeps the candidate identity separate from tag
publication. The selected identity remains
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`, and the
acceptance completed at `2026-10-16T05:44:19Z`.

The RC is engineering-ready for a publication decision. The following items
remain before any snapshot tag or release publication:

1. Refresh the seven-root status-only matrix immediately before publication
   and confirm that every exact manifest revision is unchanged and clean. If a
   revision or owner checkout changes, rerun the exact-manifest acceptance.
2. Keep Core `v0.3.0` and Toolkit `0.7.0` at their historical commits. If
   publication is authorized, use only the named stack snapshot identity (or
   make a separately approved version decision); never move a historical tag.
3. Prepare the release notes and reproducible artifact checksums against the
   exact manifest, then obtain explicit authorization before creating or
   pushing any snapshot tag. No snapshot tag has been created or pushed.
4. Treat WordPress.org SVN publication, production deployment, and the first
   real-usage ledger row as separate decisions. The owner has confirmed local
   development only, so the ledger remains empty.
5. Preserve owner-held locked worktrees and unpublished branches. Their
   existence is an ownership concern, not a reason to reset or delete them.

The completed acceptance, merged documentation PR, and clean-up evidence close
the current engineering stage; only the explicitly authorized publication and
operating-site follow-up remain.

## Final matrix refresh and manifest drift — 2026-10-16

The central command `composer quality:matrix:run -- --fail-on-dirty` completed
at `2026-10-16T06:10:45+00:00`. All seven repository gate rows reported
`passed`, but the command exited 1 because the Toolbox default root has two
owner-held changes: modified `docs/README.md` and untracked
`docs/session-handoff-2026-10-16-internal-link-related-content-history.md`.
Those files were left untouched.

The live revisions were compared with the selected RC manifest. Five roots
are unchanged: AI Cloud `fe920796`, Adapter `eb73e503`, Toolkit `921d1dc7`,
Toolbox `216e102`, and Eval Lab `5362655`. Two roots drifted through
documentation commits: Core is `1995342` instead of `8d9626b`, and Cloud Addon
is `633c62f` instead of `d8bcac9`. The drift is still a manifest change even
though the inspected commits are documentation-only.

Because the manifest and default cleanliness gate changed, release notes and
reproducible build checksums were not prepared from this refresh. The next
release step is owner reconciliation for the Toolbox working tree, followed by
a deliberate manifest decision and exact-manifest acceptance for the revised
revisions. No snapshot tag was created or pushed.

## Owner closeout audit and revised acceptance — 2026-10-16

The Toolbox shared root was checked twice over an approximately two-minute
interval. Its two owner-held changes remained stable: modified
`docs/README.md` and untracked
`docs/session-handoff-2026-10-16-internal-link-related-content-history.md`.
No file was deleted, reset, staged, or committed. A clean verification copy of
the recorded Toolbox revision remains the only safe matrix override until its
owner decides how to publish or preserve those files.

The Core/Adapter/Toolkit exact acceptance was then rerun with the live LocalWP
socket and the disposable M4 Ollama loader/tunnel. Core `ec01042`, Adapter
`eb73e503`, and Toolkit `921d1dc7` passed source, WordPress, package, release,
M4 provider-log correlation, duplicate refusal, PHPStan, Docker, and cleanup
checks; completion was observed at approximately `2026-10-16T14:39:02+08:00`.
The run produced provisional package evidence for Core
`71c752a7c100f58a4628646c071b72b1b862011fdaae776a8217d3ba6ad78fc4` and
Adapter `4356f90721d56b7484d42ad4a73b1ed0500295f21580ce75a656b5f9cba68fbc`.

Cloud Addon did not reach a stable owner checkpoint: its clean shared root
advanced from `ac82a3b` at `14:29:50+08:00` to `5f223af` at
`14:51:11+08:00` during the two-snapshot check. That movement is owner work,
not a release decision, so it remains untouched and is not silently added to
the RC manifest. Release notes and final reproducible checksums stay deferred
until Cloud Addon is stable, the seven-root owner gate is resolved, and the
manifest is deliberately refreshed. No snapshot tag was created or pushed.

## Worktree ownership audit and release acceptance — 2026-10-16

The read-only ownership audit was generated at
`2026-10-16T04:05:03+00:00` across the seven configured repositories. It
found 30 registered worktrees and 100 local branches. The seven default roots
were clean at the audit point. Six locked worktrees remain in AI Cloud; the
locked `m4-resource-baseline` worktree is still owner-held and contains three
untracked files (`docs/runtime-resource-baseline-v1.md`,
`scripts/runtime_resource_baseline.py`, and
`tests/dev/test_runtime_resource_baseline.py`).

One additional owner-held Toolbox worktree,
`/Users/muze/gitee/.worktrees/toolbox-classification-registry-20261015`, is
dirty on `codex/fix-classification-registry-owner` with a modification to
`tests/smoke-article-media-batch-core-proof.php`. No worktree, branch, owner
file, or open PR was deleted or reset. The audit therefore closes the
ownership inventory, not a claim that every auxiliary worktree is disposable.

The full command `composer acceptance:cross-repo-release` then passed with
exit 0; completion was observed at `2026-10-16T04:14:31Z`. The run covered
Core validation, tests, WordPress smoke, release verification and packaging;
Adapter source, release, package, WordPress smoke, signed fixture and
provider-log correlation; and Toolkit source, PHPStan, release, package and
Docker/WordPress smoke. The signed fixture used the verified M4 Ollama path
(`172.16.3.35`, model `qwen2.5:1.5b`) through a temporary loopback tunnel on
port `11439` and the cached official Ollama SDK. The first attempt exposed a
stale LocalWP socket; the rerun supplied the live socket explicitly and
passed. The temporary loader and tunnel were removed afterward, and the
normal LocalWP provider registry remains `npcink-cloud` only.

This is release acceptance evidence for the exact revisions checked during
the run: Core `a7ade2b`, AI Cloud `fe920796`, Cloud Addon `d8bcac9`, Adapter
`eb73e503`, Toolkit `921d1dc7`, with Toolbox and Eval Lab at the clean default
roots recorded by the matrix. It does not authorize a tag, SVN publication,
production deployment, or real-user ledger entry.

## Recommended next sequence

The ownership audit and full release acceptance are complete. The remaining
sequence is:

1. **Keep the completed classification evidence attached to the RC record.**
   Lane 1 now has visible generic AI output, present-administrator review,
   native save, unchanged Core counts, and fixture cleanup. Keep it separate
   from Core proposal and local-consent lanes.
2. **Decide the RC identity before any publication.** Use the accepted exact
   revisions as the evidence baseline, then choose either a new plugin version
   or a clearly named stack snapshot RC. Never move the historical Core or
   Toolkit tags. Tagging, SVN, and deployment require explicit authorization.
3. **Keep real usage parked until the product state changes.** The owner has
   confirmed local-only development. Leave the ledger empty until an operating
   site produces the first real-user record; fixtures, M4 provider evidence,
   and an administrator acceptance run do not count as real usage.
4. **Publish the accumulated docs through the weekly docs flow.** Before
   publication, run the Core metadata validation, `composer test:all`, and
   `git diff --check`; use the repository's protected `composer pr:publish`
   flow rather than a direct push.

The principal owner, cleanliness, worktree audit, and full release acceptance
gates are complete for this checkpoint. Release identity and real-usage work
remain downstream of that evidence.

## Final owner closeout, acceptance, and release notes — 2026-10-16

The owner handoff completed after the previous audit. The default
`composer quality:matrix:run -- --fail-on-dirty` passed all 7/7 rows with exit
0 at `2026-10-16T10:26:15+00:00`. Toolbox is clean at `ee814e04` and Cloud
Addon is clean at `4978338f`; the other five exact revisions are recorded in
[the RC release notes](../release-candidate-notes-2026-10-16.md).

The final exact-manifest acceptance then passed against Core `ed28f98`, Adapter
`eb73e503`, and Toolkit `921d1dc7`, with completion observed at approximately
`2026-10-16T18:32:56+08:00`. It included M4 provider-log correlation,
duplicate refusal, package/release gates, PHPStan, official Docker smoke, and
cleanup. Core, Adapter, and Toolkit reproducible package evidence is recorded
in the release notes; the Toolkit deterministic archive matched on two builds.

This closes the engineering evidence and checksum stage. The authorized
annotated stack snapshot tag was then created and pushed without force at the
verified Core commit `ed28f98`; historical Core and Toolkit tags were not
moved. SVN, deployment, and real-usage actions remain separately authorized.

## Snapshot tag publication — 2026-10-16

The final matrix and checksum recheck showed no candidate drift. The annotated
tag `stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0` was created
and pushed without force. Remote verification returned tag object
`ffb5e5d6d7b68fa952288524a181ff688a7698ee` peeling to
`ed28f984153200a8362fcd0928bbb32b83636159`. No historical plugin tag moved;
SVN publication, production deployment, and real-usage recording remain out
of scope for this tag action.

## Post-publication checkout drift — 2026-10-16

The final read-only check after tag publication found Cloud Addon `master` at
`6c9d3c4` after dependency update PR #279 and one new owner-held Toolbox
modification in the shared root. Both changes occurred after the tag preflight
and are outside the published manifest. The snapshot tag remains immutable;
future revisions require a new candidate decision and fresh acceptance.
