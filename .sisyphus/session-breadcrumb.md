# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-09 - Session closeout audit: issues, branches, worktrees, norms

- **Issues**: #125 was auto-closed by #128; adapter #93 closed with the
  full record (#94 merged; the in-transaction remainder lives in proposed
  Core ADR-013). Remaining open Core issues (#3/#4/#6/#105/#123) are
  deliberate trackers.
- **Cleanup**: all session-created branches deleted local+remote across
  Core (9), adapter (#94 head), ai-cloud (salvage); the ocr scratch
  worktree in /private/tmp removed. Parallel-session branches and worktrees
  were left untouched (their in-flight Core branch
  `codex/execution-verification-reads` has no PR yet).
- **Norms recorded**: AGENTS.md gains the parallel-session worktree rule
  and the ADR next-free-number rule (two same-day duplicate-number
  incidents); the wp.org release gate documents the openrsync silent
  --dry-run caveat with the diff-based review replacement.
- **Session is closable**; the main line remains the validation ledger
  (zero rows).
# 2026-10-09 - Post-release cleanup: branches, sibling roots, norms

- **Closeout audit**: all historically-raised items are closed — 0.3.0
  published (tag 3357f91, SVN r3735752), release hygiene #95, decision
  briefs + ADR-010, sj/ 8-locale drafts #140, flagged-media policy review
  #141, translation owner actions #142 (wp.org submission + PTE deferred,
  documented in the release gate).
- **Sibling roots**: ai-cloud shared root restored to clean master; the
  m4-ops worktree was detached at its own commit to free the master branch
  name (content-identical). The superseded codex/runtime-diagnostics-review
  branch is preserved with unpushed dc58be21 (not contained in master) —
  keep/discard belongs to the owner. workflow-toolbox's unpushed commits
  were pushed by their owning session; eval-lab sits clean on a pushed
  feature branch.
- **Cleanup**: all five session branches deleted local+remote (squash
  merges need `git branch -D`); parallel codex/* branches untouched.
  Central matrix: 7/7 repositories clean on master, 0 dirty/ahead/behind.
- **Norms**: AGENTS.md gains the stale-breadcrumb re-verification rule,
  the sibling-session liveness check (two status snapshots + mtimes), the
  worktree-vs-master branch-name rule, and the corrected central-matrix
  path (npcink-workflow-toolbox, not the non-existent npcink-toolbox).
- **Boundary**: Documentation and repository hygiene only; no runtime
  behavior changed this session.
# 2026-10-09 - 0.3.0 stack closeout completed (this session's part)

- **Recalibration**: the 0.3.0 release chain (v0.3.0 tag at 3357f91,
  cross-repo acceptance, wp.org SVN publication, 8-locale readme) was
  completed by parallel sessions; this session contributed the 0.3.0 version
  bump + full local release gate (#94), the next-decisions briefs, and the
  matrix follow-up (#144).
- **Toolkit 0.5.10**: the docs-only bump PR #219 merged (note: its auto-merge
  fired before the parallel session's docs-only-drift adjudication was seen;
  accepting the merged 0.5.10 as the normal next release per that ruling).
  Annotated tag `0.5.10` pushed at dd4b42f; matrix reports points_at_head.
- **Adapter v0.4.1 intentionally untagged**: master already carries six
  post-0.4.1 commits including the execution-attached verification-reads
  feature (pairs with Core ADR-012); tagging 0.4.1 now would fold unreleased
  feature work into a patch tag. The next Adapter release (0.4.2/0.5.0)
  should tag its own candidate head.
- **Remaining owner items**: the three decision briefs
  (docs/next-decisions-briefs-2026-10.md) and the deferred wp.org translation
  owner actions (#142) still await the owner's read-through.
# 2026-10-09 - Real-usage first-week kickoff runway

- **Recalibration**: the validation program itself, the ADR-007 park note,
  and the decision-briefs-to-ledger trigger wiring were all landed by
  parallel sessions (real-usage-validation.md, ADR-007 Start Trigger, #141/#142).
  What remained missing was the executable path to the first ledger row.
- **Completed** (PR #146, merged): `docs/real-usage-first-week-kickoff.md` —
  real-site selection step, one-time environment prep (0.3.0 stack versions,
  ai plugin 1.4.0+, per-site adapter token), week-one operating loop,
  per-column ledger data sources, and the post-week wiring that fires on its
  own.
- **Smoke-site environment check**: full Npcink stack active and current on
  magick-ai.local; `ai` plugin at 1.3.0 with 1.4.0 available (upgrade noted
  in the kickoff checklist for the real site).
- **Next recommended step (owner)**: pick the real site, run the kickoff
  checklist, and land the first weekly ledger row. Adapter ADR-013 supplement
  PR (#95) remains in active parallel development; run cross-repo acceptance
  after it merges.
# 2026-10-09 - Session closeout audit

- **Issues**: every issue raised in this session's threads is done or has a
  recorded disposition — UX audit P0-P2 shipped (#91) and adopted by Adapter
  (#89); visual smoke passed with the two historical debts closed; 0.3.0
  stack released and closed out (Toolkit 0.5.10 tagged; Adapter v0.4.1
  intentionally untagged pending its next release); remaining open items are
  owner-facing (first real-usage ledger row, wp.org zh_CN PTE) or in active
  parallel development (Adapter #95), none dangling silently.
- **Code**: all PRs merged (#91 #92 #94 #144-#147 Core; #89 Adapter; #219
  Toolkit); local masters synced and clean.
- **Branches/worktrees**: deleted merged session branches local+remote
  (Core 4+2 superseded codex residuals, Adapter adapt/core-ux-contract,
  Toolkit release/0.5.10); no worktrees created by this session. Kept
  deliberately: Core codex/execution-verification-reads (1 unapplied) and
  codex/verification-reads-hardening (2 unapplied) — in-flight parallel work,
  keep/discard belongs to the owner; Adapter shared checkout sits on the
  active codex/adr-013-verification-supplement branch (PR #95); Toolkit
  codex/phpstan-ratchet-local-note (parallel residual, 0 unapplied).
- **Norms/docs**: durable lessons already recorded — browser visual-smoke
  pattern + stale `.maintenance` 503 note in development-workflow.md,
  parallel-session rules in AGENTS.md, ledger-trigger mechanism in
  real-usage-validation.md, first-week runway in
  real-usage-first-week-kickoff.md.
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
  as exceptions.
