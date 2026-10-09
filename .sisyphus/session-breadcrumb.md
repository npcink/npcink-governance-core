# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-08 - Next-stage kickoff: validation program opened, decisions closed, ADR-007 parked

- **Module**: Next-stage planning docs only (no runtime class changed).
- **Completed**: Three merged PRs executing the accepted next-stage advice:
  #117 closed briefs Decision 1 as option C (observability hook is the
  contracted notification seam; Decision 2 stays open on its recorded
  trigger); #119 opened docs/real-usage-validation.md (4+ week program,
  weekly ledger with low-usage why-lines, recorded-evidence exit criteria,
  0.4.0 scope gated on the first feedback batch) plus the smart_guarded
  widening standard (four clean ledger weeks, any misapproval reverts to
  manual same day, no end date); #121 parked the ADR-007 runtime behind a
  ledger-recorded start trigger with the state carried on the ADR-010
  closeout checkpoint line.
- **Owner next actions (the real main line)**: run the governed chain on one
  real operating site weekly and append ledger rows; nothing else fires
  until the ledger has entries.
- **Verification**: `composer test:all` exit 0 on every PR; advisory ocr
  review zero findings pre-publish each time; release package hash at
  baseline.

# 2026-10-08 - 0.3.0 closeout: pipeline cleared to the tag, stopped on an ADR-011 conflict

- **Module**: Release closeout only (central matrix, cross-repo acceptance
  preparation, decision records); no runtime class changed by this session's
  own PRs.
- **Completed**:
  - ai-cloud `codex/runtime-diagnostics-review` disposition: superseded by
    merged #1086 (from `codex/admin-diagnostics-closeout`; the old branch
    never had a PR). Central matrix `--run-gates --fail-on-dirty` green for
    all 7 roots using the ai-cloud path override to the clean master
    worktree (`npcink-ai-cloud-m4-ops`). Two orphan docs exist only on the
    stale branch (`troubleshooting-design-brief.json`, the 2026-10-08
    commercial brainstorm); owner decides salvage or drop.
  - rc version matrix refreshed to Core 0.3.0 / Adapter 0.4.1 / Toolkit
    0.5.9 (#126). Toolkit `0.5.9` tag exists at `a5ef13a` while HEAD is two
    docs commits ahead; bump-or-accept is a Toolkit-owner call and
    `--require-tag-ready` fails on that row until resolved.
  - ADR-011 option 2 (adapter-facing verification read-request kind,
    trigger-gated) accepted (#127); Core #125 and adapter #93 updated.
  - Repo-scan Plugin Check gate fixed for PHPUnit artifacts (#129):
    acceptance was failing `hidden_files` + `application_detected` because
    `.phpunit.result.cache`/`phpunit.xml.dist` postdate the exclude list.
- **Environment gotchas**: LocalWP smoke needs
  `WP_CLI_MYSQL_SOCKET=/Users/muze/Library/Application Support/Local/run/s63K4c8XP/mysql/mysqld.sock`
  passed explicitly (discovery missed it this run); a live parallel session
  edits npcink-workflow-toolbox, and the site fatals while its refactor
  branch is mid-break (the require-order fix landed 19:49; expect flapping).
- **STOPPED BEFORE TAG**: parallel PR #128 (merged 19:49) implemented
  option 1 — "execution-attached verification reads" minted at commit
  preflight — with its own
  `docs/decisions/ADR-011-execution-attached-verification-reads.md`.
  master now holds two ADR-011 files with opposite verdicts, and the
  implementation fired with zero ledger entries. Acceptance, the
  prepare:release evidence rebind, the terminal matrix rerun, and the
  v0.3.0 tag are paused for owner arbitration:
  - A. Keep #128: reconcile the records (renumber or supersede one ADR,
    update README + real-usage-validation + tests), record the trigger
    waiver explicitly, then resume acceptance -> prepare:release ->
    matrix -> tag.
  - B. Revert #128: the single option-2 ADR-011 stands; resume the same
    pipeline on the clean base.
- **Main line unchanged**: the real-usage validation ledger still has zero
  rows; the first weekly row on a real operating site outranks this
  release plumbing.

# 2026-10-08 - 0.3.0 tagged: arbitration executed, pipeline completed

- **Arbitration executed (owner: option A)**: the ADR conflict recorded one
  session ago is resolved. #132 renumbered the option-1 decision to
  `ADR-012-execution-attached-verification-reads.md` (supersedes the option-2
  ADR-011, kept for the record), README/validation-doc/tests updated, and the
  ledger-trigger waiver is recorded as one-item, non-precedential.
- **#131 unblocked and hardened**: the parallel session's PR was stuck on the
  ADR rename conflict plus unresolved review threads. This session merged
  master in (rename detection carried the Hardening section automatically),
  fixed the remaining review findings (granted-only cap/dedupe counting,
  normalize_payload_for_hash reuse for key-order-insensitive dedupe keys,
  invalid_input denial for unencodable inputs, MAX_VERIFICATION_READS
  constant, comment reword), recorded the test-coverage disposition (final
  Read_Request_Service blocks unit stubbing; grant-mode adapter smoke is the
  ADR-012 acceptance signal), and resolved all 10 threads; auto-merge landed
  it as 3357f91.
- **Release evidence at 3357f91**: cross-repo acceptance green on the fourth
  attempt after clearing three environment blockers in order (explicit
  `WP_CLI_MYSQL_SOCKET`, stale `.maintenance` file 503, Docker daemon down);
  signed fixture proved proposal -> approval -> execute -> duplicate-reject
  -> readback -> cleanup. `composer prepare:release -- --version 0.3.0`
  green; package `build/npcink-governance-core.zip` SHA-256
  `ed907c891dba944ecbd366358de2db2a9d2b641dc0b69a087907ca92cdee65d9`
  (matches the reproducible-package hash from the branch gates).
- **Tagged**: annotated `v0.3.0` pushed at 3357f91, remote peeled commit
  verified equal to the verified HEAD. WordPress.org SVN is NOT published
  (separately authorized). GitHub Release record waits for wp.org
  publication per issue #2.
- **Classified follow-ups**: `rc:version-matrix --require-tag-ready` fails
  only on the Toolkit row (tag `0.5.9` at `a5ef13a`, HEAD two docs commits
  ahead) — bump-or-accept is the Toolkit owner's call; ai-cloud stale branch
  holds two orphan docs; adapter #94 must adopt the handoff-only granted-ids
  shape; zh_CN wp.org/PTE unchanged.
- **Main line unchanged**: the real-usage validation ledger still has zero
  rows.

# 2026-10-09 - 0.3.0 published to WordPress.org; adapter #94 completed; follow-ups closed

- **WordPress.org publication**: owner authorized SVN sync. Dry-run reviewed
  via direct diff (openrsync's --dry-run prints nothing on this macOS —
  28 changed + 3 new files, zero deletions vs 0.1.1 trunk), applied, and
  committed as r3735752 (trunk + tags/0.3.0, 58M/7A reconciled against the
  reviewed delta; checkout at ~/wporg-svn). GitHub Release v0.3.0 created
  from the tag with the package asset (zip sha256 ed907c89…); issue #2
  closed with the full record. This is the first SVN release since 0.1.1 —
  0.2.0 was git-tag only.
- **Adapter #94 (verification reads) completed** with the parallel session:
  it had restructured onto instance-scoped grant queues; this session stacked
  the remaining deltas — positional per-ability binding of handoff grant ids
  to action-derived inputs (their map produced empty queues against hardened
  Core #131 because handoff entries carry no input), reference-addressed
  skip telemetry, expected-vs-granted coverage events, relay double-fetch
  reuse, slug-grant usability validation, and full grant-material redaction
  from the execute response/records. All 20 review threads replied and
  resolved; auto-merge armed. NOTE: the adapter main checkout is shared
  with the parallel session — use a worktree (npcink-adapter-94-final) and
  restore the checkout to master when done.
- **ai-cloud orphan docs salvaged**: PR #1089 carries the 2026-10-08
  commercial brainstorm and the diagnostics design brief from the superseded
  branch into master (byte-identical extraction); auto-merge armed. The
  stale branch itself is left for the owner's cleanup.
- **Toolkit 0.5.9 tag drift accepted** (docs-only commits after the tag) in
  the rc version matrix doc.
- **Main line unchanged**: the real-usage validation ledger still has zero
  rows; everything above is plumbing around it.

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

