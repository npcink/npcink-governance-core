# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-14 - Cross-repository acceptance restored

- **Addon**: PR #271 merged (`edbacb8`); status/recovery hardening, localized
  diagnostics, cleanup and scheduler guards passed the full local gate and
  required CI. The settings-page complexity ceiling is now 2283 lines, matching
  the implementation.
- **AI Cloud**: exact clean master HEAD `d97dfd4f` passed the required GitHub checks
  (`backend`, `frontend`, Secret scan, and both CodeQL analyses). The prior
  exact-SHA backend failure is superseded by this verified head.
- **Toolbox**: PR #267 (`codex/local-ocr-config`) merged after its dedicated
  clean worktree passed `composer test:all`. The shared root was left intact
  while its unrelated parallel branch completed.
- **Central matrix**: the no-override command
  `composer quality:matrix:run -- --fail-on-dirty` passed 7/7 repositories at
  `2026-10-14T02:32:23Z`; all default worktrees were clean and Toolbox/AI
  Cloud resolved to their merged master commits. Core retains its clean weekly
  docs branch, with exact gate evidence recorded.
- **Release posture**: no tag, SVN publication, production deployment, or
  real-user ledger row is authorized. OCR remains advisory; Addon and Toolbox
  local provider reviews hit bounded provider/context deadlines and are recorded
  as exceptions. The RC version matrix now passes for Core `0.3.0`, Adapter
  `0.4.3`, and Toolkit `0.7.0`; final tag readiness still fails safely on the
  historical Core/Toolkit tags, so no tag is moved. The subsequent
  `acceptance:cross-repo-release` run reached the real Adapter fixture but
  failed with `prompt_invalid_argument`: LocalWP is currently `npcink-cloud`
  only, while the default fixture expects Ollama. The M4 temporary loader,
  tunnel and `qwen2.5:1.5b` setup must be recreated for a future positive run;
  no provider configuration was changed.

# 2026-10-14 - Cross-repository release acceptance passed

- **Provider fixture**: recreated the one-time M4 Ollama 0.32.5 path with the
  verified WordPress.org AI Provider for Ollama 1.3.0 SHA
  `8c483f337b07c5dcb537444c09485208e3fa745430807a8f2905513fd5109e2c`, a
  loopback SSH tunnel on `127.0.0.1:11439`, and `qwen2.5:1.5b`. The signed
  Adapter fixture passed persisted Provider Log correlation for proposal,
  preflight correlation, and Adapter request identifiers, then cleaned its
  draft and marker loader.
- **Cross-repo gate**: `composer acceptance:cross-repo-release` passed Core
  validation/tests/smoke/release/package, Adapter tests/smoke/release/package
  and real Provider fixture, and Toolkit tests/PHPStan/release/Docker smoke.
  Docker Desktop 29.7.2 was started after the first retry reported an
  unavailable daemon; the final run completed all rows.
- **Central matrix**: `composer quality:matrix:run -- --fail-on-dirty` passed
  all 7/7 repositories at `2026-10-14T03:46:45Z`; every default worktree was
  clean. Cloud Addon is one commit behind `origin/master` but its deterministic
  gate passed.
- **Cleanup**: temporary SDK, MU loader, marker fixture, SSH tunnel/socket,
  and provider registration were removed. LocalWP's registered providers are
  back to `npcink-cloud` only. Core, Adapter, and Toolkit worktrees are clean.
- **Release posture**: RC `--require-tag-ready` still fails safely on the
  historical Core/Toolkit tags; no tag, SVN publication, production deploy,
  or real-user ledger row is authorized. OCR remains advisory with recorded
  delivery exceptions.
- **Next**: refresh the central matrix after this acceptance evidence, then
  keep the real-usage kickoff ready until an operating site produces the first
  ledger row. Release-candidate tagging requires a new version/tag decision and
  explicit operator authorization.

# 2026-10-14 - Remote Addon head verified without touching shared root

- **Liveness**: Cloud Addon shared `master` was clean and unchanged across two
  snapshots three minutes apart; it remains one commit behind `origin/master`.
- **Isolated verification**: remote commit `122ac79` was checked out in a
  dedicated detached worktree. Its `composer run test:all` gate passed.
- **Central matrix**: `composer quality:matrix:run -- --fail-on-dirty`
  with the detached Addon worktree supplied through `--repo-path` passed 7/7
  repositories at `2026-10-14T04:48:31Z`. A final default-path run after the
  Core docs commit also passed 7/7 at `2026-10-14T04:56:57Z`; the shared Addon
  gate remains green while its branch is one commit behind.
- **Boundary**: no sibling shared checkout was switched, pulled, or edited;
  no tag, SVN publication, deployment, or real-user ledger row was created.

# 2026-10-14 - Classification regression fixtures repaired in isolation

- **Cloud Addon**: after two stable liveness snapshots, shared `master` was
  fast-forwarded to `origin/master` `122ac79`; the root is clean and aligned.
- **Toolbox**: classification lane 2 initially hit a real 403 because the
  fixture omitted the present-admin nonce, cookie, and same-origin headers;
  lane 3 also checked a Toolbox ability in the Toolkit-only registry. Isolated
  commit `d4bc150` fixes both assumptions and the OCR follow-up isolation issue;
  PR #268 merged as `444128a`, and lanes 2/3 plus Toolbox `composer test:all`
  pass.
- **Matrix**: the override run using Toolbox `da02356` and a clean AI Cloud
  `origin/master` worktree passed 7/7 at `2026-10-14T06:25:15Z`.
- **Open owner state**: default matrix is currently `needs_validation` because
  the AI Cloud shared root has uncommitted owner files
  `deploy/deploy-to-ssh-host.sh` and `deploy/remote-refresh-providers.sh`;
  they were left untouched. The RC matrix reports Core `0.3.0`, Adapter
  `0.4.3`, and Toolkit `0.7.0`; `--require-tag-ready` safely rejects the
  historical Core tag. Classification lane 1 still awaits a real
  administrator editor action, and the real-usage ledger remains empty.

# 2026-10-14 - Session closeout audit: local fixes complete, global closeout still open

- **Core**: branch `codex/docs-week-2026-10-13` is clean at `075cd46`; `composer test:all` passes 55 tests / 207 assertions, `composer validate --no-check-publish` passes, and `git diff --check` passes. The branch remains local-only and is not merged to Core `master`.
- **Toolbox classification repair**: isolated topic commit `d4bc150` passed `composer test:all`, local OCR session `bd48b658-64fa-485d-b3ee-3aebdf910117` returned 0 findings, and PR #268 merged as `444128a` after the protected publisher recorded the missing OpenCodeReview delivery as an explicit advisory exception.
- **Cross-repo state**: the override matrix using clean AI Cloud `origin/master` and Toolbox `d4bc150` passed 7/7 at `2026-10-14T06:25:15Z`; the default matrix remains `needs_validation` because AI Cloud shared root has five owner-modified deploy/CI files.
- **Cleanup boundary**: Cloud Addon shared `master`, Toolkit `master`, Adapter `master`, and AI Cloud related roots were inspected. Historical and locked worktrees remain intentionally preserved; no owner files, tags, deployments, or real-user data were altered.
- **Core publication**: PR #162 merged as `e81aef9` after Core CI and PR body checks passed; its GitHub OpenCodeReview job failed twice at provider HTTP 400 with zero review comments, while local OCR session `7b6c2e43-b820-4e3a-b989-dad90a02b618` returned 0 findings.
- **Open closeout items**: AI Cloud owner reconciliation, historical worktree ownership decisions, classification lane 1 real-admin action, first real-usage ledger row, and a new RC tag/version decision.
- **Process lesson**: audit claims must separate local verification, protected PR merge, default-matrix cleanliness, and real-user evidence. A clean isolated worktree cannot be reported as a clean shared checkout.

# 2026-10-14 - Next-stage owner handoff checkpoint

- **Toolbox**: shared root is now clean on `codex/internal-link-recommendation-followup`; its local gate passed in the default matrix.
- **AI Cloud**: shared root remains dirty with eight owner files on `codex/provider-refresh-advisory`; one auxiliary `m4-resource-baseline` worktree also has three untracked files. No cleanup or reset was performed.
- **Matrix**: default `composer quality:matrix:run -- --fail-on-dirty` reached 6 passed plus AI Cloud `needs_validation` at `2026-10-14T09:52:18Z`. A clean detached AI Cloud `origin/master` override passed 7/7 at `2026-10-14T10:17:37Z`.
- **Locked sequence**: AI Cloud owner reconciliation -> default 7/7 matrix -> complete worktree audit -> full release acceptance -> new RC version decision -> real-usage first ledger row.

# 2026-10-15 - Owner-state audit continued

- **Core planning record**: PR #164 merged as `d317f73`; Core topic branch remains clean and the latest docs update is staged for the next docs-only closeout PR.
- **Toolbox**: shared `codex/internal-link-recommendation-followup` is clean and its gate passed in the default matrix.
- **AI Cloud**: shared `codex/provider-refresh-advisory` remains dirty with eight owner changes. Auxiliary `m4-resource-baseline` retains three untracked files; locked or owner-held worktrees were preserved.
- **Matrix**: default status snapshot at `2026-10-15T01:30:53Z` remains `needs_validation`; clean detached AI Cloud `origin/master` override passed 7/7 at `2026-10-14T10:17:37Z`.
- **Next gate**: AI Cloud owner reconciliation is still the only blocker before a no-override 7/7 matrix; then run worktree audit, full release acceptance, and only afterward decide RC/tag and real-usage kickoff.

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
