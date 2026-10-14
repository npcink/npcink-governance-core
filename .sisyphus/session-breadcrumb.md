# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-09 - ADR-013 verification-integrity arc closed end to end

- **Core side merged**: ADR-013 accepted (#135/#136); provisional-record
  result-bound minting implemented and hardened (#138/#139). The
  provisional record is lifecycle-safe by construction: audit event plus
  read mints only, allowed while the proposal is still approved, and the
  definitive record path owns transitions (Proposal_Service.php:538-607).
- **Adapter side merged**: adapter #95 (squash 9b30954) after 13
  OpenCodeReview rounds, every finding triaged on the PR body; the
  drained-key grant seeding is pinned by a behavior probe. Grant-mode
  smoke: both post-block-readback assertions green, 1235 ok - the
  furthest this run has ever reached. Issues #125 and adapter #93 closed
  on acceptance evidence; adapter closeout record in its
  docs/archive/2026-10-09-adr-013-verification-supplement-closeout.md.
- **Standing verdicts for future rounds**: the supplement deliberately
  sends the ORIGINAL (unresolved) action input - output references are
  the reference-addressing evidence for the result-bound mint, and
  resolved ids would be denied as statically addressed; the provisional
  record never transitions lifecycle state.
- **Main line unchanged**: the real-usage validation ledger still has
  zero rows.


# 2026-10-11 - Positioning audit clean; README synced to implementation truth

- **Module**: README docs sync only (no runtime class, no contract change).
- **Audit**: systematic positioning-vs-implementation check: all 20 REST
  routes, 5 tables, multisite provisioning, uninstall no-op, ADR-012/013
  verification-read minting, and the local-admin-consent hook verified
  against code; boundary scan (final writes, workflow/queue, MCP, gateway
  catalogs, credentials, product UX, prompts, billing) clean 10/10 with
  zero wp_remote_* and zero content-write API calls in runtime code.
  Only staleness found: the README smart_guarded paragraph named 2 of 5
  candidate classes and the plan-bridge prose listed 13 of 21 allowlisted
  abilities (approval-policy-evaluator-standard.md and
  plan-to-proposal-governance.md were already current).
- **Fix**: README now names all five smart_guarded candidate classes and
  all 21 allowlisted plan abilities with one-line bounds, repairs the
  dangling "plan output" sentence in the bridge paragraph, and defers the
  exhaustive list to plan-to-proposal-governance.md.
- **Observations recorded (no action taken)**: Admin_Page.php at 5857 lines
  strains the "minimal admin surfaces" wording though its content is
  governance-only; Plan_Contract_Validator's per-plan domain knowledge is
  the ADR-009-accepted stretch point of the governance-layer positioning.
- **Verification**: `composer test:all` green (PHP lint, static contracts,
  fail-closed fault injection, PHPUnit 41 tests, reproducible package
  hash, revision-bound smoke evidence).
- **Main line unchanged**: the real-usage validation ledger still has
  zero rows.


# 2026-10-12 - Flagged media removal Core slice staged; PR publish waits on a clean ocr round

- **Stage recalibration (owner)**: project is local-development only, no
  real users yet; the real-usage ledger cannot advance and stays the
  recorded trigger mechanism. Owner approved the engineering queue order:
  flagged-media slice -> #123 Admin_Page split -> briefs Decision 2
  correlation assertion -> #105 test runner split.
- **Completed**: `flagged_media_removal_plan.v1` Core governance slice on
  branch `feat/flagged-media-removal-plan-intake` (code commit 7a78822):
  allowlisted `build-flagged-media-removal-plan` intake, dual-branch
  evidence contract (backup evidence XOR no-backup attestation),
  manual-required in every policy mode including `dev_allow_all`,
  illegal-branch attestation approval gate, preflight branch-evidence
  revalidation covering plan batches AND direct `delete-attachment`
  proposals, description-only audit blocks. Docs + README + static
  contracts updated; PHPUnit 53 tests / 181 assertions; `test:all`,
  `smoke:wp`, `check:wporg` all green.
- **ocr review**: three fix rounds landed (direct REST proposal bypass,
  preview-context test masking, attestation type-safety); the fourth
  round failed on repeated HTTP 429 rate limits and did not run.
- **Stopped before publish**: `composer pr:publish` needs one completed
  clean ocr round first. Resume steps, ready PR body, and the full queue
  live in
  `.sisyphus/handoff-2026-10-12-flagged-media-queue.md`.
- **Cross-repo leftovers (sibling repos, worktrees only)**: Toolkit
  `delete-attachment` + `build-flagged-media-removal-plan` abilities,
  Adapter execution profile, boundary matrix + governance catalog
  registration (policy checklist item 5 open).


# 2026-10-13 - Engineering queue implemented; M4 provider positive evidence passed

- **Recalibration**: the 2026-10-12 pending Core/OCR/Toolkit/Adapter claims are
  superseded by live merged PRs. OCR is advisory; it does not require a clean
  verdict when its service cannot deliver a review. This remains local
  development, with no real users or ledger rows implied by fixtures.
- **Flagged media**: Core #157/#158 (preflight fix 98ffa00), Toolkit #251
  (7d6313f: durable manifests, deletion drift guards, deleted attachment
  restoration), Adapter #109/#113 (f510270), and Toolbox #266 (7ec1aaf)
  merged. The approved restoration extension is implemented. Adapter #112
  (55654d3) passed real signed HTTPS deletion in both branches, restore to a
  new attachment, rejection/idempotency guards, cleanup, and local aggregate
  acceptance. Earlier dedicated four-source matrix passed 4/4; this was not
  a full seven-repository release gate.
- **Core #123**: #159 merged e3098a1; Admin_Page facade 6040 -> 1689 lines,
  181 method bodies/signatures byte-identical, five WP-rendered screens equal.
- **Core #105**: #160 merged 6ac6400 (source f856ad6); test entrypoints now
  21/32/39 lines with ordered responsibility modules. All original statement
  tokens preserved, early/late failure aggregation returns exit 1, normal and
  relocated WordPress smoke pass. Composer test:all: 55 tests/207 assertions;
  package reproducibility, metadata, and wp.org source guard pass.
- **Provider gate**: Adapter #115 merged 35afff4 (source 1386563 plus current-master merge)
  adds strict signed draft/log correlation, absent-ai warning skip, and
  active-ai missing-log failure; 10 behavior checks/source gates pass.
  Default WP smoke passed its fail-closed segment and skipped grant-dependent
  remainder. Live tests correctly refused unregistered Ollama, a Cloud scene
  with no SDK log, and a later provider failure. M4 Ollama 0.32.5 /
  `qwen2.5:1.5b` then passed the strict signed fixture after the Adapter write
  dispatch fix (#116 merged 7b8431c, source 31a7ec0): actual success log
  03cbd3a3-8d40-4322-bb7d-5e49356b615b matches all three IDs and nested Core
  fields. The regression probe fails before the fix and passes success/error/
  exception cleanup afterward; private runtime authority stays out of logs.
  WordPress.org Ollama Provider 1.3.0 classes were loaded only for acceptance;
  temporary SDK/controller and marker loaders, draft 282138, host cache, and
  SSH tunnel removed. Original registry restored to npcink-cloud only.
- **OCR**: Core focused session f2761387 failed all three selected files with
  repeated LLM network errors; no clean verdict claimed. Required CI passed.
- **Completed follow-up**: Core #161 merged 391c291 (source e57d913) replaces
  M4's stale 0.2.0 pin with the candidate plugin Version header; missing or
  ambiguous headers fail before setup. On exact merged SHA, WP7.0/PHP8.0 and
  WP7.0/PHP8.5 ZIP-installed profiles pass 1614 assertions each, with revision/
  archive/package/Toolkit bindings validated. Docker projects cleaned; source
  gates 55/207 passed. LocalWP worktree gate correctly refused the shared mount;
  the real M4 package profiles supplied the WordPress behavior gate instead.
- **Adapter owner acceptance**: actual user admin UI approval of proposal
  a51c3c2b-0a43-4661-96fc-63cd664f33a2 (actor 1), signed attachment 282139
  deletion, backup/readback/duplicate refusal and cleanup passed. On merged
  7b8431c, full local aggregate passed with no WordPress grant skips, limit400,
  package install/restore, real Ollama logs, both HTTPS deletion branches and
  backup restore. Release gate/Plugin Check 0 errors and six CLI tests passed;
  owner confirmed MU/cache/tunnel/fixtures cleaned and live mount restored.
- **Next**: external owners fix Cloud Addon complexity and AI Cloud backend CI;
  re-run clean central matrix before release closeout. No tag/SVN release
  authorized; real-user ledger remains parked.
- **Ownership/hygiene**: no sibling shared roots were switched/reset. Toolkit
  owner removed its completed worktree; this made our private WP smoke mount
  stale, and the isolated mount was corrected to clean Toolkit master before
  rerunning the unchanged original and split smoke. Shared live symlinks
  stayed unchanged. The owner's central run at 09:05:54 UTC passed six repos;
  AI Cloud's parallel dirty workspace was needs_validation. The reported Cloud
  Addon Scope message was expected negative-test output, and its gate passed.
  Our 09:09:38 UTC status-only snapshot found two AI Cloud dirty files.
  Newer actual 10:20:34 UTC matrix passed five repos and failed Cloud Addon
  (settings-page 2282 lines > 2281 ratchet, 13 dirty files) and clean AI Cloud
  (exact-SHA backend CI). Owners notified. No full-family green claim is made.
- **OCR follow-up**: Adapter local session e32d3de5 timed out after source
  inspection; CI run 38375605314 failed original/retry with Provider errors;
  standard publisher recorded exception before merge. Core focused shell
  session a9ba8b90 also timed out before tool calls; recorded in #161 body.
  Required CI passed; owner independently reviewed the Adapter fix.
- **Worktree cleanup**: our Adapter write-context topic was saved as verified
  complete bundle before removal; Core M4 evidence copied to primary ignored
  dist before removing its dedicated worktree. Official Ollama SDK and reusable
  /tmp prepare/cleanup scripts retained for the Adapter owner's acceptance.
- **Docs**: current stage/Decision 2/flagged-media pending prose recalibrated.
  Adapter #115 CI review failed its original and repeated runs; the standard
  publisher recorded an explicit review exception before protected merge.
  Historical closeout and this bounded breadcrumb accumulate on the weekly
  docs branch, per the batched docs-only publication rule.

- **Dependency cleanup**: Adapter owner saved/verified its #113 topic Git
  bundle and removed adapter-flagged-media after this session released all
  dependencies. Provider topic history was also saved as a complete verified Git bundle
  before its worktree was removed; topic refs are preserved.


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
