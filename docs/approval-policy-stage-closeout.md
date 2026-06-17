# Approval Policy Stage Closeout

Status: active closeout and handoff note.

Date: 2026-06-09.

This note summarizes the approval-policy work completed in the current stage
and the boundary future AI sessions should preserve. Read it before extending
the approval policy evaluator, OpenClaw development approval flows, or Adapter
handoff behavior.

## Current Position

Npcink Governance Core is still the proposal, approval, commit-preflight, and
audit truth source. The approval policy evaluator is intentionally lightweight:
it classifies proposals, records decision metadata, and can auto-approve only
the narrow development candidates already documented in the Approval Policy
Evaluator Standard.

The default remains `manual`. All proposals require manual approval unless the
site explicitly enables a bounded strategy mode and every fail-closed
condition passes.

Current supported modes:

- `manual`: production-safe default. Every proposal records
  `manual_required`.
- `smart_guarded`: conservative approval reducer. It may auto-approve only
  trusted test cleanup trash batches and single direct draft-only
  `npcink-abilities-toolkit/create-draft` proposals.
- `dev_allow_all`: explicit local-development allow-all mode. It requires
  `NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` and still requires commit
  preflight before Adapter-owned execution.

Old approval policy values are not accepted; stale stored values fall back to
`manual`.

Adapter remains thin. It reads Core proposal state, calls Core commit preflight,
and executes only already approved proposals whose preflight passes. Adapter
does not make automatic approval decisions.

## What Was Completed

The stage delivered these Core-side pieces:

- policy decision fields in proposal responses:
  `policy_decision`, `policy_profile`, `policy_version`, and
  `policy_reasons`;
- `proposal.policy_evaluated` audit for every successful proposal creation,
  with fail-closed cleanup if the audit write fails;
- duplicate pending proposal reuse based on sanitized input hash;
- pending proposal quotas and stale pending expiration;
- separate hourly and daily auto-approval quotas;
- `smart_guarded` auto approval for trusted nonproduction cleanup
  `trash-post` batches;
- `smart_guarded` auto approval for a single direct draft-only
  `npcink-abilities-toolkit/create-draft` proposal;
- `proposal.auto_approved` audit whenever Core changes a proposal to approved;
- admin/settings copy for the guarded modes;
- static contracts, fail-closed coverage, WordPress smoke coverage, REST/API
  docs, security docs, app scope docs, and translation updates;
- OpenClaw execution guidance that keeps productized OpenClaw usage in Magick
  AI Adapter, with Core as the governance authority behind it.

## Verified Evidence

The closeout test pass verified the Core path with:

- `composer test:all`;
- `composer validate --no-check-publish`;
- `composer smoke:wp`;
- a manual REST positive probe proving a trusted guarded direct
  create-draft proposal became `approved`, returned
  `policy_decision=auto_approved`, wrote `proposal.policy_evaluated` and
  `proposal.auto_approved`, passed commit preflight, returned
  `commit_execution=false`, and did not create the post in Core;
- a manual REST negative probe proving a non-draft create-draft proposal stayed
  `pending` with `policy_decision=manual_required`;
- Magick AI Adapter `composer test:all`,
  `composer validate --no-check-publish`, and `composer smoke:wp` against the
  same governed flow.

The Adapter worktree had pre-existing local changes during verification. Core
does not require taking ownership of those Adapter changes for this closeout.

## Stop Point

Do not keep expanding Core approval policy in this stage. The useful local
development pain point is addressed: repeated OpenClaw draft and trusted test
cleanup approvals can be reduced with `smart_guarded`, while the governance
boundary stays intact.

Core should not add auto approval for:

- publish or schedule operations;
- batch article plans;
- article block or pattern page batch plans;
- `delete-media-permanently`;
- `delete-post-permanently`;
- `delete-term`;
- `set-post-terms`;
- `approve-comment`;
- `reply-comment`;
- updates to existing published content;
- media settings, featured-image adoption, derivative adoption, or other
  multi-object write plans.

Core should also not add:

- rules DSLs;
- workflow runtime or task queues;
- approval workflow engines;
- long-running schedulers;
- generic execution routes;
- MCP runtime;
- OpenClaw onboarding UX;
- complex policy configuration centers.

If one of those appears necessary, write a boundary note or ADR before coding.

## OpenClaw Development Use

For local development, use Magick AI Adapter as the productized OpenClaw entry
instead of connecting OpenClaw directly to Core.

The trusted development app key used by Adapter or an internal local probe must
have only the scopes it actually needs. A typical guarded create-draft test key
needs:

- `proposals:create`;
- `proposals:approve`;
- `commit:preflight`;
- `proposals:read` when polling status;
- `audit:read` only for diagnostics.

The caller/app identity should be stable so Core quotas and audit attribution
remain useful. If a proposal is auto-approved, Adapter must still call commit
preflight and execute the target WordPress ability outside Core.

## Recommended Next Step

Treat the current Core implementation as complete for this approval-policy
stage. The next useful work is operational:

1. push and publish the current Core branch;
2. use `smart_guarded` during local OpenClaw development;
3. observe audit and proposal behavior in real daily use;
4. fix only concrete bugs or missing evidence surfaced by that use;
5. move product polish, OpenClaw setup, and approve-and-execute experience to
   Magick AI Adapter.

Return to Core only for bounded contract fixes, documentation corrections, or a
separate accepted ADR that deliberately widens the policy surface.
