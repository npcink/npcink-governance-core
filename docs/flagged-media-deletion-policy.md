# Flagged Media Deletion Policy

Status: proposed policy contract awaiting acceptance review.

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

- [ ] from-plan intake accepts `flagged_media_removal_plan.v1` with the
      grouped action shape;
- [ ] the approval policy excludes flagged media removal from every
      auto-approval lane;
- [ ] preflight enforces the branch-specific evidence rules;
- [ ] audit events record deletion descriptions without content retention;
- [ ] the cross-repo boundary matrix and the Toolkit governance catalog
      record the new plan artifact.
