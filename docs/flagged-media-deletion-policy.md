# Flagged Media Deletion Policy

Status: Core-side governance slice implemented 2026-10-11 (from-plan intake,
manual-only approval lanes, attestation approval gate, branch-evidence
preflight, deletion-description audit). The Toolkit
`npcink-abilities-toolkit/delete-attachment` ability and the Adapter execution
profile are still pending; until they land, intake of this plan fails closed
with `npcink_governance_core_plan_ability_unavailable` and no approved plan
can execute. Checklist item 5 stays open until the sibling repositories
record the artifact.

## Context

`npcink-workflow-toolbox` surfaces a suggestion-only flagged media review set
(`docs/flagged-media-review.md` in that repository, merged 2026-09-30). When
an operator confirms a flagged attachment, removal needs a governed Core path.
The matching ability candidate is recorded in `npcink-abilities-toolkit`
`docs/ability-candidates/delete-attachment-ability-candidate.md` with a
dual-branch deletion policy. This document proposes the Core side of that
contract: proposal intake, approval policy, preflight evidence, and audit
rules for flagged media removal.

## Plan Artifact

`flagged_media_removal_plan.v1` submitted through the existing from-plan
intake as one review proposal. It may group, for one reviewed attachment:

- `npcink-abilities-toolkit/delete-attachment` with the operator-chosen
  `deletion_policy_branch`;
- non-empty exact-match `patch-post-content` actions that remove the deleted
  media URLs from post content (existing action shape);
- non-empty exact-match `patch-setting-value` actions that remove the deleted
  media URLs from plugin/theme settings (existing action shape).

One plan targets one attachment with its bounded reference set. Bulk flagged
media removal is not part of this contract and needs its own future decision.

## Approval Policy

- Flagged media removal is destructive and is never eligible for
  conservative auto-approval in any policy mode, including `smart_guarded`.
  Every `flagged_media_removal_plan.v1` proposal requires manual approval.
- `illegal_content_no_backup` plans additionally require the operator's
  recorded no-backup attestation from the ability dry-run preview before the
  proposal becomes approvable.
- The branch is an operator review choice recorded in the plan; Core validates
  its presence and shape but never classifies content.

## Commit Preflight

- `governed_with_backup` plans must carry the Toolkit dry-run backup evidence
  fields (backup location, retention window, restore token contract) and
  preflight must verify that evidence before execution.
- `illegal_content_no_backup` plans must carry the no-backup attestation, and
  preflight must verify that the execution path creates no backup artifacts.
- Reference patch actions reuse the existing exact-match preflight rules;
  empty or fuzzy reference patches are rejected, not widened.

## Audit Rules

- Audit records describe the deletion (attachment id, branch, reason,
  operator, correlation id) without retaining the deleted content itself.
- `illegal_content_no_backup` audits must prove no backup, local copy, or
  Cloud artifact retention was created.
- Restore evidence applies only to `governed_with_backup` plans within the
  retention window; the restore path itself is the existing Toolkit restore
  ability, not a new Core path.

## Boundary

- Core owns proposal, approval, preflight, and audit truth only.
- Content-safety classification stays in the Cloud runtime; Toolbox only
  prepares suggestion-only review sets.
- The deletion callback, backup, lineage, and restore machinery stay in
  `npcink-abilities-toolkit`.
- Reporting or referral for illegal content remains a manual operator
  responsibility outside the platform.
- Adapter needs an explicit execution profile for
  `npcink-abilities-toolkit/delete-attachment` before any client may execute
  an approved plan; Toolbox productizes the button only after that profile is
  proven, per the platform batch-order rule.

## Acceptance Checklist

Before implementation:

- [x] from-plan intake accepts `flagged_media_removal_plan.v1` with the
      grouped action shape (implemented 2026-10-11: allowlisted
      `npcink-abilities-toolkit/build-flagged-media-removal-plan` with the
      contract validator and one batch proposal per plan);
- [x] the approval policy excludes flagged media removal from every
      auto-approval lane (implemented 2026-10-11:
      `flagged_media_removal_manual_evaluation` guards `smart_guarded` and
      `dev_allow_all`; approving `illegal_content_no_backup` plans requires
      the recorded no-backup attestation);
- [x] preflight enforces the branch-specific evidence rules (implemented
      2026-10-11: `flagged_media_branch_evidence_preflight` fails closed with
      `npcink_governance_core_flagged_media_branch_evidence_missing`);
- [x] audit events record deletion descriptions without content retention
      (implemented 2026-10-11: `flagged_media_removal` description blocks on
      `proposal.plan_ingested`, `commit.preflighted`, and execution-result
      events carry attachment id, branch, reason, and evidence presence
      only);
- [ ] the cross-repo boundary matrix and the Toolkit governance catalog
      record the new plan artifact (sibling-repository docs, still open).

## Implementation Record — 2026-10-11

Core-side slice shipped on the plan bridge allowlist
(`npcink-abilities-toolkit/build-flagged-media-removal-plan`), the contract
validator (`validate_flagged_media_removal_plan_contract`), the proposal
preview context (`preview.flagged_media_removal`), the manual-only approval
exclusion, the illegal-branch attestation approval gate, the commit-preflight
branch-evidence revalidation, and the deletion-description audit blocks.
Direct proposals whose `ability_id` is
`npcink-abilities-toolkit/delete-attachment` are held to the same contract
(manual in every mode, branch evidence carried as flat input and revalidated
at preflight), so the destructive deletion cannot escape the plan rules
through the generic proposal route. The remaining cross-repo work is
unchanged: Toolkit must register
`npcink-abilities-toolkit/delete-attachment` (candidate accepted, not yet
implemented), the build-flagged-media-removal-plan read ability, and the
Adapter execution profile; the boundary matrix and governance catalog
registrations follow those slices.

## Review Record — 2026-10-09

Reviewed against the Core implementation at the published 0.3.0 line:

- Checklist items 1-4 are not implemented: `includes/` has no
  flagged-media handling, `flagged_media_removal_plan.v1` is not in the
  from-plan contract allowlist, and the branch-evidence preflight rules
  and deletion-description audit rules do not exist yet.
- Checklist item 5 is partially recorded: the cross-repo boundary matrix
  (`npcink-workflow-toolbox` `docs/boundary.md`) documents the
  suggestion-only `flagged_media_review_set.v1` and names the dual-branch
  deletion policy as the specification input for the future Toolkit/Core
  path, but neither that matrix nor the Toolkit governance catalog
  records `flagged_media_removal_plan.v1` itself.
- Because the artifact cannot be created today, no approval-policy lane
  can admit it; the explicit auto-approval exclusion still needs its own
  guard when intake lands.

Verdict: the contract is sound and matches the existing from-plan,
approval-policy, preflight, and audit shapes. Nothing in it violates the
Core boundary: the deletion callback, backup, lineage, and restore
machinery stay in `npcink-abilities-toolkit`, classification stays in the
Cloud runtime, and Toolbox only prepares suggestion-only review sets.

Accepted as the target contract. Implementation is a future dedicated
slice and still requires the Adapter execution profile for
`npcink-abilities-toolkit/delete-attachment` before any client may
execute an approved plan.
