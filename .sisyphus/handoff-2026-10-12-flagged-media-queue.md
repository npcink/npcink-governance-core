# Handoff — 2026-10-12: flagged media removal slice staged, PR publish waits on a clean ocr round

Written for the next AI session. Read together with
`.sisyphus/session-breadcrumb.md` (the 2026-10-12 entry) and
`AGENTS.md` session startup protocol.

## Stage recalibration (owner decision, 2026-10-12)

The owner confirmed the project is still in local development with **no real
users**. The real-usage validation ledger (docs/real-usage-validation.md)
therefore cannot advance and is **not** the actionable main line right now;
it stays the recorded trigger mechanism for pending product decisions. The
owner approved executing the engineering queue in this order:

1. flagged media removal Core slice (this branch — implemented, publish pending);
2. issue #123: opportunistic Admin_Page.php view splits;
3. briefs Decision 2 (option B): provider-log correlation assertion in the
   next cross-repo acceptance authoring pass;
4. issue #105: split the monolithic static test runners by module.

## Current state of the flagged media slice

- **Branch**: `feat/flagged-media-removal-plan-intake`, code commit `7a78822`
  ("feat: govern flagged media removal plans end to end"), plus this handoff
  commit on top. Nothing pushed; no PR yet.
- **Gates green at 7a78822**:
  - `composer test:all` — PHP lint, static contracts, fail-closed fault
    injection, PHPUnit **53 tests / 181 assertions**, reproducible package
    hash, revision-bound smoke evidence;
  - `composer smoke:wp` — full LocalWP stack smoke green (needs explicit
    `WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/s63K4c8XP/mysql/mysqld.sock"`);
  - `composer check:wporg` — green;
  - `composer validate --no-check-publish` — valid.
- **Blocked on**: the advisory `ocr review` gate. Rounds 1-3 produced
  findings, all fixed and amended into 7a78822. Round 4 (final pass) failed
  on repeated HTTP 429 rate limits — **a completed clean round is still
  required before `composer pr:publish`** per the zero-findings-before-publish
  flow.

### What was implemented

`flagged_media_removal_plan.v1` per docs/flagged-media-deletion-policy.md,
Core side only, through the existing `POST /proposals/from-plan` route:

1. **includes/Governance/Plan_Contract_Validator.php**
   - Allowlist gains `npcink-abilities-toolkit/build-flagged-media-removal-plan`;
     constants `FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY`,
     `DELETION_POLICY_BRANCH_GOVERNED_WITH_BACKUP`,
     `DELETION_POLICY_BRANCH_ILLEGAL_NO_BACKUP`;
     `FLAGGED_MEDIA_MAX_REFERENCE_ACTIONS = 10`, reason bounded to 500 bytes.
   - `validate_flagged_media_removal_plan_contract`: requires
     `artifact_type=flagged_media_removal_plan`, batch mode, a
     `flagged_media_removal` review context with one `attachment_id`, a valid
     `deletion_policy_branch`, non-empty bounded `reason`, `idempotency_key`;
     branch-exclusive evidence (`backup_evidence` with location/retention
     window/restore token contract XOR confirmed `no_backup_attestation`,
     conflicts rejected both ways); exactly one `delete-attachment` action
     whose attachment/branch/reason/idempotency match the context; reference
     patches limited to `patch-post-content` (static `post_id`) and
     `patch-setting-value` (`target_type` option|theme_mod + `target_name`),
     every operation `op=replace` on one absolute URL with `replace=""`
     (empty/fuzzy patches rejected, not widened).
   - Static helpers shared by policy/preflight/gate:
     `is_flagged_media_removal_proposal` (plan caller attribution OR
     `ability_id=delete-attachment` OR a valid recorded branch),
     `flagged_media_removal_context` (preview context, falling back to flat
     direct-proposal input), `flagged_media_removal_branch`,
     `is_valid_deletion_policy_branch`.
2. **includes/Governance/Plan_Proposal_Service.php**:
   `flagged_media_removal_preview` (bounded review context, no URL lists),
   `flagged_media_removal_proposal_summary` (human-readable summary),
   `flagged_media_removal_audit_description` (description-only intake audit:
   attachment id, branch, reason, evidence presence — no content).
3. **includes/Governance/Approval_Policy_Evaluator.php**:
   `flagged_media_removal_manual_evaluation` consulted by `smart_guarded`
   (early-returns manual before any lane) and by `dev_allow_all` **before**
   the enable-constant check, so flagged media removal stays `manual_required`
   in every mode with explicit reasons
   (`flagged_media_removal_manual_required`,
   `flagged_media_removal_destructive_never_auto_approved`,
   `dev_allow_all_flagged_media_removal_manual_required`).
4. **includes/Governance/Proposal_Service.php**:
   `flagged_media_attestation_gate` blocks approving
   `illegal_content_no_backup` records without a confirmed recorded
   attestation (`npcink_governance_core_flagged_media_attestation_required`);
   execution-result audit metadata gains the `flagged_media_removal`
   description block (`no_backup_artifacts_created=true` on the illegal
   branch, `backup_evidence_present` on the governed branch).
5. **includes/Governance/Commit_Preflight_Service.php**:
   `flagged_media_branch_evidence_preflight` revalidates branch-consistent
   evidence for **both** plan batches and direct `delete-attachment`
   proposals (branch valid + consistent with the delete action, backup
   evidence present for governed branch, confirmed attestation plus zero
   declared backup artifacts for the illegal branch); fails closed with
   `npcink_governance_core_flagged_media_branch_evidence_missing`;
   `commit.preflighted` events gain the `flagged_media_removal`
   {attachment_id, deletion_policy_branch} block; preflight response exposes
   `flagged_media_branch_evidence`.
6. **Docs**: governance-contract.md (allowlist + handoff paragraph),
   rest-api-contract.md (route section, direct-proposal parity),
   ability-intake-contract.md (allowlist + boundary paragraph),
   plan-to-proposal-governance.md (full "Flagged Media Removal Handoff"
   section), flagged-media-deletion-policy.md (status updated, checklist
   items 1-4 checked, implementation record),
   README.md (bridge paragraph + never-auto-approved note).
7. **Tests**: PlanContractValidatorTest +11 (both branches, rejection table,
   illegal-branch evidence rules, exact-removal patch rules, reference cap,
   detection helpers incl. direct-proposal shapes); ApprovalPolicyEvaluatorTest
   +4 (manual in smart_guarded/dev_allow_all, preview-context-only match,
   direct delete-attachment in dev_allow_all); tests/run.php static contract
   entries for every new symbol/error code/doc anchor.

### ocr review history on this branch

- Round 1 finding (fixed): direct `POST /proposals` with
  `ability_id=delete-attachment` and flat input escaped flagged-media
  detection — could be auto-approved under `dev_allow_all`, skipped the
  attestation gate and preflight evidence. Fix: ability-id match plus
  flat-input context fallback; gate and preflight handle both shapes.
- Round 2 finding (fixed): the preview-context-only unit test was satisfied
  by the new ability-id match instead of exercising the context branch —
  test now uses a `trash-post` ability id.
- Round 3 finding (fixed): attestation checks hardened to strict types
  (`confirmed === true`, `is_string` statement) at all three enforcement
  sites.
- Round 4: HTTP 429 rate limited on every request — review did not run.
  **Rerun required; publish only after a completed round** (fix any new
  findings or record the disposition, then repeat until clean).

## Resume steps for the next session

1. `git status --short --branch` — expect clean on
   `feat/flagged-media-removal-plan-intake`; `git fetch` and re-verify
   `origin/master` is still 16a7ec8-era baseline (parallel sessions may have
   moved it; rebase if needed).
2. Re-run the review gate (wait out the rate limit):
   `ocr review --from origin/master --to HEAD`
   — treat findings as before: fix real defects, amend or add commits.
3. Re-verify gates quickly after any new edits:
   `composer test:all && composer check:wporg`
   (`composer smoke:wp` only if runtime code changed again).
4. Write the PR body from `.github/pull_request_template.md` — a ready
   draft is embedded below; **update the `ocr review` line to reflect the
   final completed round** — save it to a temp path, then publish:
   `composer pr:publish -- --title "feat: govern flagged media removal plans end to end" --body-file <path>`
5. After merge: delete the branch locally (`git branch -D` — squash merge),
   sync master, then proceed to queue item 2 (#123 Admin_Page splits) in a
   **fresh session** per the one-module-per-session rule.

## Queue items after this PR (owner-approved order)

- **#123**: split Admin_Page.php views opportunistically on the next
  admin-surface change (file is 5857 lines; keep it governance-only).
- **Briefs Decision 2 (option B)**: in the next cross-repo acceptance
  authoring pass, assert the signed Adapter fixture execution produces an
  AI Request Log row carrying the same `correlation_id`; skip-with-warning
  when the `ai` plugin is absent, fail when present but the row is missing.
  This lives in `composer acceptance:cross-repo-release` tooling, not a new
  runtime responsibility.
- **#105**: split the monolithic static test runners by module.
- **Cross-repo flagged-media follow-ups** (sibling repos, dedicated
  worktrees only): Toolkit registers `delete-attachment` (candidate accepted,
  unimplemented) and the `build-flagged-media-removal-plan` read ability;
  Adapter adds the explicit execution profile for `delete-attachment`;
  boundary matrix + Toolkit governance catalog record the plan artifact
  (policy checklist item 5, still open). Live intake of this plan fails
  closed with `npcink_governance_core_plan_ability_unavailable` until the
  Toolkit abilities ship.

## Ready PR body draft (update the ocr line before publishing)

```markdown
## Scope

- [x] This change is limited to the stated module.
- [x] Public REST, data shape, lifecycle, or product boundary docs were updated if changed.
- [x] No unrelated generated files or local environment files are included.

Implements the Core-side governance slice of the accepted
[Flagged Media Deletion Policy](docs/flagged-media-deletion-policy.md):
`flagged_media_removal_plan.v1` enters through the existing
`POST /proposals/from-plan` route under the new allowlisted planning ability
`npcink-abilities-toolkit/build-flagged-media-removal-plan`. One plan becomes
one batch proposal for exactly one attachment: one `npcink-abilities-toolkit/delete-attachment`
action carrying the operator-chosen `deletion_policy_branch`, plus at most 10
exact-match reference removal patches (`patch-post-content` with a static
`post_id`, `patch-setting-value` with a reviewed target).

Branch evidence is validated as shape, never classified:

- `governed_with_backup` requires the Toolkit dry-run `backup_evidence`
  (location, retention window, restore token contract) and rejects a
  conflicting no-backup attestation;
- `illegal_content_no_backup` requires a confirmed `no_backup_attestation`
  and rejects any declared backup artifacts;
- patch operations must be `op=replace` on one reviewed absolute URL with
  `replace=""`; empty or fuzzy reference patches are rejected, not widened.

Governance rules added:

- flagged media removal stays `manual_required` in **every** approval policy
  mode — `smart_guarded` lanes and even `dev_allow_all` carry the explicit
  exclusion guard;
- approving an `illegal_content_no_backup` plan requires the recorded
  attestation (defense-in-depth gate at the approve transition);
- commit preflight revalidates branch-consistent evidence for plan batches
  **and** direct `delete-attachment` proposals, and fails closed with
  `npcink_governance_core_flagged_media_branch_evidence_missing`;
- audit events (`proposal.plan_ingested`, `commit.preflighted`, execution
  results) record the deletion description (attachment id, branch, reason,
  evidence presence) without retaining deleted content or reference URL
  lists.

## Core Boundary

- [x] Core remains the governance layer for ability intake, proposals, approval/preflight, and audit.
- [x] This does not add final WordPress write execution to Core.
- [x] This does not add provider credential storage, model routing, workflow runtime, MCP runtime, Agent Gateway catalogs, task queues, batch execution consoles, or product workflow UX.
- [x] Reusable WordPress ability definitions remain outside Core, in `npcink-abilities-toolkit` or another provider plugin.

The deletion callback, backup, lineage, and restore machinery stay in
`npcink-abilities-toolkit`; content classification stays in the Cloud
runtime; Toolbox review sets stay suggestion-only. The Toolkit
`delete-attachment` ability is not registered yet, so live intake of this
plan fails closed with `npcink_governance_core_plan_ability_unavailable`
until that sibling slice ships. Cross-repo boundary-matrix and governance
catalog registrations (policy checklist item 5) remain open follow-ups.

## Verification

- [x] `composer validate --no-check-publish`
- [x] `composer test:all` (PHP lint, static contracts, fail-closed fault
      injection, PHPUnit 53 tests / 181 assertions, reproducible package
      hash, revision-bound smoke evidence — all green)
- [x] `composer check:wporg`
- [x] `composer smoke:wp` (REST intake touched; full stack smoke green)
- [x] `ocr review --from origin/master --to HEAD` — UPDATE THIS LINE after
      the completed round (three fix rounds landed: direct-proposal bypass,
      test masking, attestation type-safety; the fourth round was HTTP 429
      rate-limited and did not run).

## Risk

- Residual risk: low. The new plan type is additive; existing plan types,
  approval lanes, and preflight paths are unchanged except for the merged
  flagged-media reasons in policy results. A client cannot use the
  flagged-media markers to loosen governance — matching only adds stricter
  manual-approval and evidence rules.
- Rollback plan: revert the single squash commit; no schema migration, no
  new REST routes, no option/format changes.

## Release Impact

- [x] No release impact.
- [ ] Requires a GitHub release package build.
- [ ] Requires WordPress.org SVN sync after local release gate passes.
```
