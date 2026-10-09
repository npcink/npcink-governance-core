# Session Breadcrumb

Rolling record of the newest sessions (newest last). Keep this file
bounded: when it holds more than 10 session entries, move the oldest
entries into `.sisyphus/archive/<session-month>.md` at closeout.
Older history: `.sisyphus/archive/`.

# 2026-10-08 - UX round 2: audit paging, quota visibility, evidence truncation

- **Module**: Admin review flow, REST consumer experience, zh_CN i18n
  (branch `fix/ux-round2`, 3 commits f2aca1d/9e7557e/253373f).
- **Trigger**: fresh user-experience audit after PR #91; user approved the
  prioritized fix list ("按建议依次落实").
- **Audit recalibrations** (do not re-propose without a new decision):
  - The admin H1 stays the fixed English product name by contract
    (admin-surface-standard; tests pin `esc_html( 'Npcink Governance Core' )`).
  - History is deliberately read-only with no status filters or
    archive/reopen UI (2026-06-19 decision, commit 0236838); the
    admin_post archive/reopen handlers are contracted stable surface
    (tests/run.php:2917-2918), not dead code to delete.
  - The single-event Recent Activity panel was a pinned contract; replaced
    with a five-event noise-free list and the contract updated.
- **Batch 1**: REST GET /audit excludes read-noise events by default
  (opt-in `include_read_events`) so `audit.listed` no longer shifts offset
  paging; meta.limit echoes the clamped value; proposal/read-request
  timelines (admin + REST) newest-first with `audit_timeline_total`
  truncation disclosure; admin menu pending badge (read-only,
  TTL-bounded via `count_pending_within_ttl`); bulk-reject JS confirm;
  read_request.* audit labels/filters; 5-event Recent Activity.
- **Batch 2**: X-RateLimit-Limit/Remaining/Reset headers served at
  rest_post_dispatch; 400/404 app-authenticated requests refund their
  rate slot (atomic, floored decrement in App_Rate_Limiter::refund);
  pending-quota 429 carries `earliest_pending_expires_at`; status filters
  enumerated (proposals + read-requests) so typos 400;
  read-request 409 carries `request_status`; read-preflight nested
  `expires_at` now ISO8601; queue summary cards deep-link filtered audit
  views; conditional "Sensitive reads" card when read requests pend
  (Admin_Page now takes Read_Request_Repository).
- **Batch 3 (minimal + docs)**: open decision brief
  `docs/next-decisions-briefs-2026-10-ux-round2.md` recommends
  Adapter-owned notification via the observability hook over
  Core-native webhooks/email; operability doc documents the notification
  seam + polling posture; zh_CN catalog: 53 new translations (incl.
  previously untranslated enum labels), 861 translated / 0 untranslated,
  obsolete entries preserved (Toolbox menu label contract).
- **Gotchas hit**: msgmerge preserves obsolete PO entries — do NOT run
  `msgattrib --no-obsolete` (drops the contracted Toolbox label entry);
  App_Authenticator constructor now registers a rest_post_dispatch filter
  and needs a `function_exists( 'add_filter' )` guard for the fail-closed
  harness; zh_CN POT regen command:
  `wp i18n make-pot . languages/npcink-governance-core.pot --domain=npcink-governance-core --exclude=vendor,build,dist,tests,scripts,sj,examples`.
- **Verification**: `composer test:all` green after every batch;
  `composer smoke:wp` green; advisory `ocr review` run before publish.
- **Not done (owner decisions pending)**: notification channel (brief D
  rejected / C recommended); `updated_since` polling filter (deferred);
  OpenAPI; browser visual smoke of the new badge/cards/timeline panel.

# 2026-10-08 - UX round 2 review closeout (fix commits 432d4c4, follow-up)

- Advisory review round 1 (15 findings) and round 2 (11 findings) fully
  triaged; every real defect fixed, no waivers. Key late catches:
  audit.listed total-snapshot drift, exact-window rate refunds
  (consume now returns window_start), 429 responses carrying
  X-RateLimit headers, admin detail timeline must exclude read-noise or
  polled proposals evict their own approval/preflight evidence from the
  bounded window, read-request lazy expiry inflating a naive pending
  count (count_pending_unexpired added), plural-form portability of the
  JS confirm (now plural-neutral "proposal(s)"), status enums reusing
  allowed_statuses() (instance call — static call fatalled smoke).
- zh_CN terminology realignments: 读预检已检查, Agent 主机.
- All gates green after each fix batch: test:all, smoke:wp, validate,
  check:wporg; final catalog 861 translated / 0 untranslated with
  obsolete entries preserved.
- PR body at /tmp/ux-round2-pr-body.md; publish with
  `composer pr:publish -- --title "ux: round-2 consumer and admin experience fixes" --body-file /tmp/ux-round2-pr-body.md`.

# 2026-10-08 - UX round 2 closeout: merge, master sync, visual smoke

- PR #97 merged (squash 6fdb964, all three CI checks green); local master
  synced; the LocalWP smoke site runs the merged code via its plugin
  symlink.
- Post-merge browser visual smoke passed on the live site (temp admin
  user 96 created and deleted; temp pending proposal created and deleted
  with its audit rows; no `.maintenance` residue):
  - menu badge renders (治理核心 179) with live pending count;
  - queue summary tiles are clickable cards with consistent targets
    (待审核→queue, 已批准→audit overview) in zh_CN;
  - Recent Activity lists five newest non-noise events, each linking its
    proposal, noise excluded;
  - proposal detail evidence tab: lifecycle summary chronological,
    timeline table newest-first with the "最新事件在前" wording;
  - bulk-reject confirm dialog shows the plural-neutral message with the
    selected count substituted and blocks on cancel.
  - Screenshots under /tmp/core-visual-smoke/ (local only).
- Observation (pre-existing, not changed): `composer smoke:wp` pending-
  quota fixtures accumulate on the local site (179 pending rows); this
  matches prior session behavior (40+ rows seen 2026-10-07) and was left
  as-is.
- Session open items for the owner: decide the two 2026-10 UX-round-2
  briefs (notification channel, updated_since polling); Adapter consumers
  adopt the new contract additions on next sync; zh_CN wp.org language
  pack submission still waits on the PTE path.

# 2026-10-08 - Solo+AI process hardening: breadcrumb rotation, docs history, behavioral tests, ADR-010 checkpoint, smoke self-cleanup, resume guide

- **Module**: Cross-cutting solo-developer process (no runtime class changed).
- **Completed**: Six merged PRs implementing the accepted assessment:
  #101 breadcrumb split (295KB -> 17KB rolling + monthly archives, 10-entry
  rotation rule in AGENTS.md); #103 docs authority inventory made physical
  (13 historical docs -> docs/history/, layout pinned both directions);
  #104/#106 PHPUnit 9.6 behavioral suite (31 tests / 95 assertions over
  Approval_Policy_Evaluator, Plan_Contract_Validator, App_Rate_Limiter;
  runners now report the full failure list, capped printing at 50); #109
  ADR-010 Trigger Review Checkpoint wired into release closeout step 1;
  #111 process de-load (visual smoke cadence, weekly docs batching, smoke
  deletes its own fixtures with 54 cleanup assertions; 220 stale pending
  rows wiped locally, 17 remaining belong to cross-repo smokes); #113
  docs/resume-after-break.md solo-gap insurance page. Issue #5 closed by
  coverage audit; #105 tracks the deferred runner-monolith split.
- **Gotchas hit**: composer require on local PHP 8.4 locked a PHP ^8.4
  transitive dep that CI's 8.0 floor rejected - config.platform.php=8.0.30
  pins resolution; phpunit.xml.dist leaked into the release package until
  .distignore caught it; wp eval-file includes files in method scope, so
  helpers must declare `global` explicitly; pinned phrases must survive on
  one unwrapped line; GitHub required_conversation_resolution blocks
  auto-merge until advisory-review threads are resolved - run local
  `ocr review` BEFORE pr:publish so CI review lands zero threads.
- **Verification**: `composer test:all` green on every PR; `composer
  smoke:wp` green twice on #111 (54 cleanup assertions executing); advisory
  ocr review pre-publish each time; release package hash unchanged
  throughout.
- **Not done (owner decisions pending, unchanged from earlier 2026-10-08
  sessions)**: notification channel brief (D rejected / C recommended);
  `updated_since` polling filter; zh_CN wp.org language pack still waits on
  the PTE path.

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
