# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-15 - Default matrix and full engineering acceptance restored

- **Recalibration**: AI Cloud PR #1115 merged as `0bb4d1fc`; its shared topic
  `fca129f8` is clean, pushed, tree-identical to master, and exact-SHA CI passed.
  The previous dirty shared-root owner blocker is resolved.
- **Matrix**: no-override `quality:matrix:run -- --fail-on-dirty` passed 7/7
  at `2026-10-15T07:45:04Z`. Full `acceptance:cross-repo-release` then passed
  on clean masters Core `9716cf5e`, Adapter `ab32e503`, Toolkit `921d1dc7`,
  with real Ollama persisted-log correlation, duplicate refusal, package
  installation, PHPStan, and Toolkit Docker smoke. Core PHPStan also passed.
- **Recovery**: supplied the live LocalWP socket, started Docker, used M4 LAN
  SSH after alternate-path timeout, and corrected the temporary log-provider
  pattern to hostname-only. The strict assertion remained intact. Temporary
  loaders, drafts, discovery cache, tunnel, and smoke containers were cleaned;
  provider registry is back to `npcink-cloud` only.
- **Audit**: 28 registered worktrees / 98 local branches inspected. Five
  worktrees are locked; AI Cloud resource-baseline still has three untracked
  owner files. Open PRs and unpublished local work remain preserved. No global
  cleanup or all-branches-merged claim is made.
- **Posture**: ordinary RC matrix passes; historical Core/Toolkit tag mismatch
  remains. No tags, SVN, or production deployment. Owner reconfirmed local-only
  development: real-usage ledger stays empty and kickoff is parked.
- **Docs**: current planning, acceptance, tag-readiness, and usage instructions
  recalibrated; full audit and lessons recorded in
  `docs/history/cross-repo-engineering-checkpoint-2026-10-15.md`. Batch these
  docs with the next weekly docs publication instead of a per-session PR.

# 2026-10-16 - AI Cloud owner reconciled; strict matrix now waits on Addon owner

- **Historical close point**: the referenced review session reached a 7/7
  default matrix and full cross-repository acceptance on 2026-10-15 for the
  exact tested revisions, including real Ollama persisted-log correlation,
  duplicate refusal, package checks, PHPStan, and Docker smoke. No tag, SVN
  publication, deployment, or real-user ledger row was created.
- **AI Cloud owner closeout**: the six-file Admin async-state follow-up was
  committed as `e5aa6143`, merged by PR #1117, and is now clean on shared
  `master` at `fe920796`. Required exact-SHA checks passed; the separate
  `code-review` advisory failed and is not merge authority.
- **Matrix rerun**: final no-override `quality:matrix:run -- --fail-on-dirty`
  at `2026-10-16T04:01:39Z` passed all 7/7 gate rows with exit 0. Cloud Addon
  is clean `master@d8bcac9`; AI Cloud is clean `master@fe920796`.
- **Open gates**: worktree ownership audit -> full release acceptance ->
  explicit RC/tag decision -> real-user kickoff when an operating site exists.
- **Classification**: Toolbox lanes 2 and 3 pass; native editor lane 1 still
  needs exact visible-AI-output/present-author evidence and must not be
  relabeled complete from a fixture or partial draft save.
- **Docs**: detailed summary and live checkout table are recorded in
  `docs/history/session-review-and-next-stage-2026-10-16.md`;
  `docs/next-stage-plan.md` now carries the same live recalibration.

# 2026-10-16 - Worktree audit and full release acceptance passed

- **Audit**: read-only ownership audit generated at `2026-10-16T04:05:03+00:00`; 30 registered worktrees and 100 local branches inspected. All seven default roots were clean. Six AI Cloud worktrees are locked; `m4-resource-baseline` retains three untracked owner files. Toolbox classification-registry auxiliary worktree retains one owner modification. No cleanup or reset was performed.
- **Acceptance**: `composer acceptance:cross-repo-release` passed with exit 0; completion observed at `2026-10-16T04:14:31Z`. Core, Adapter, and Toolkit source/package/WordPress/release gates passed, including M4 Ollama `qwen2.5:1.5b` provider-log correlation and duplicate refusal. Temporary loader and tunnel were removed; LocalWP provider registry is back to `npcink-cloud`.
- **Next**: choose a new RC identity without moving historical tags, close classification Lane 1 with exact present-admin evidence, and wait for an operating site before the first real-usage ledger row. No tag, SVN publication, or production deployment is authorized.

# 2026-10-16 - Classification Lane 1 passed with native editor evidence

- **Lane 1**: on `https://magick-ai.local`, administrator `1` opened disposable draft `282453`, triggered the generic WordPress AI plugin's visible `生成文章摘要`, reviewed and edited the result, then clicked native `保存草稿`. AI ability and normal WordPress save both returned HTTP 200.
- **Core boundary**: proposal count stayed `317 -> 317`; audit rows stayed `3366 -> 3366`; no Core proposal or audit event was created. The draft and one-time login helper were deleted.
- **Stage**: native editor classification is complete. Keep the exact evidence with the RC record; remaining gates are RC identity and real-site usage. No tag, SVN publication, or production deployment was authorized.

# 2026-10-16 - RC snapshot identity decided; historical tags preserved

- **Exact matrix**: status-only central matrix at `2026-10-16T04:58:45+00:00` found all seven default roots clean. Candidate manifest is Core `8d9626b`, AI Cloud `fe920796`, Cloud Addon `d8bcac9`, Adapter `eb73e503`, Toolkit `921d1dc7`, Toolbox `216e102`, Eval Lab `5362655`.
- **Decision**: reserve `stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0` as the RC identity. Core `v0.3.0` remains at historical `3357f916`; Toolkit `0.7.0` remains at historical `46c14054`; Adapter `v0.4.3` is available. `--require-tag-ready` fails by design and no tag was moved or created.
- **Next**: rerun full acceptance against this exact manifest before any snapshot tag push, with explicit authorization. Real-site usage remains separate and the ledger is still empty.

# 2026-10-16 - Exact RC manifest acceptance passed

- **Manifest**: exact acceptance bound Core `8d9626b`, Adapter `eb73e503`, and Toolkit `921d1dc7`, with the previously recorded AI Cloud, Cloud Addon, Toolbox, and Eval Lab revisions.
- **Acceptance**: `composer acceptance:cross-repo-release` passed exit 0; completion observed at `2026-10-16T05:44:19Z`. Core 55/207, WordPress smoke, package/release, Adapter signed M4 Ollama correlation and duplicate refusal, Toolkit PHPStan, Docker and WordPress smoke all passed.
- **Cleanup/posture**: temporary clone, symlink, loader, tunnel, and dependency link were removed; shared Core symlink was restored. Snapshot tag remains uncreated and unpushed; historical tags were not moved.

# 2026-10-16 - PR #169 merged; final RC publication remains gated

- **Merge**: PR #169 merged through the protected publisher at
  `2026-10-16T05:51:39Z` as `34d75e2d`; the exact RC acceptance record is now
  on Core `master`.
- **RC posture**: selected identity remains
  `stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`; no snapshot tag
  was created or pushed, and historical Core/Toolkit tags were not moved.
- **Remaining**: refresh the seven-root matrix immediately before publication,
  rerun acceptance if the manifest drifts, prepare checksums/release notes,
  then obtain explicit authorization for tag, SVN, or deployment actions.
  Local-only development means the real-usage ledger stays empty.

# 2026-10-16 - Final matrix refresh found manifest drift

- **Matrix**: `composer quality:matrix:run -- --fail-on-dirty` completed at
  `2026-10-16T06:10:45+00:00`; all seven gate rows passed, but exit 1 records
  two owner-held Toolbox changes (`docs/README.md` and one untracked handoff
  document).
- **Drift**: Core is `1995342` versus manifest `8d9626b`; Cloud Addon is
  `633c62f` versus `d8bcac9`. AI Cloud, Adapter, Toolkit, Toolbox, and Eval Lab
  remain unchanged. The two drift commits are docs-only, but still require a
  deliberate manifest decision and fresh exact acceptance.
- **Posture**: release notes and reproducible checksums are deferred; no
  snapshot tag was created or pushed.

# 2026-10-16 - Toolbox stable audit; Cloud Addon owner still moving

- **Toolbox**: two snapshots over approximately two minutes showed stable
  owner-held changes in `docs/README.md` and the untracked internal-link handoff
  document. Nothing was deleted, reset, staged, or committed.
- **Acceptance**: Core `ec01042`, Adapter `eb73e503`, and Toolkit `921d1dc7`
  passed exact three-repository acceptance, including M4 provider-log
  correlation, duplicate refusal, Docker smoke, and cleanup.
- **Cloud Addon**: owner work advanced from `ac82a3b` to `5f223af` during its
  stability check. Manifest freeze, release notes, and final checksums remain
  deferred; no snapshot tag was created or pushed.

# 2026-10-16 - Final 7/7 owner closeout and acceptance passed

- **Owner state**: Toolbox is clean at `ee814e04`; Cloud Addon is clean at
  `4978338f`. The default `--fail-on-dirty` matrix passed 7/7 with exit 0 at
  `2026-10-16T10:26:15+00:00`.
- **Acceptance**: Core `ed28f98`, Adapter `eb73e503`, and Toolkit `921d1dc7`
  passed exact acceptance, including M4 provider-log correlation, duplicate
  refusal, Docker smoke, package/release gates, and cleanup.
- **Release evidence**: Core, Adapter, and two-build Toolkit checksums are in
  `docs/release-candidate-notes-2026-10-16.md`; no snapshot tag was created or
  pushed.
