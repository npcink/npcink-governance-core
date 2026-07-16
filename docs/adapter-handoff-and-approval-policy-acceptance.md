# Adapter Handoff And Approval Policy Acceptance

Status: active handoff and manual acceptance checklist.

This document covers the next implementation step after the Core approval
policy work. The next execution work belongs in Npcink AI Client Adapter or another
channel adapter, not in Core.

Core remains responsible for proposal records, approval/rejection, policy
evaluation, commit preflight, scoped app-key policy, and audit. Adapter remains
responsible for final WordPress Abilities API execution after Core approval and
preflight.

## Adapter Next Step

The next product implementation should happen in
`/Users/muze/gitee/npcink-ai-client-adapter` or the relevant Npcink AI Client Adapter
workspace.

Adapter should implement or verify this approve-and-execute sequence:

1. Discover Core capability guidance and use real WordPress `ability_id`
   values.
2. For read abilities, call the WordPress Abilities API read surface directly,
   applying Adapter-side redaction when Core marks the read sensitive.
3. For write or destructive abilities, create a Core proposal with
   `proposals:create`.
4. Poll or display Core proposal status with `proposals:read`.
5. Approve only from a trusted Adapter surface that presents Core preview,
   risk, blocked or needs-input state, source, and audit context to the user.
6. Call Core commit preflight with `commit:preflight`.
7. Execute the target WordPress ability through WordPress Abilities API only
   when Core returns an executable preflight item, matching approval context,
   approved input hash, and `execution_handoff.executor=adapter_after_core_preflight`.
8. Record the result back to Core with `commit:record_execution`, passing the
   proposal id, correlation id, approved input hash, execution status, and
   public-safe counters.

Adapter must fail closed when:

- Core proposal status is not `approved`;
- commit preflight fails or has already been consumed;
- `approval_context.site_url`, `home_url`, or `blog_id` do not match the
  execution site;
- the target ability id does not match Core handoff;
- the approved input hash does not match the payload Adapter is about to run;
- the execution profile is not explicitly allowlisted for that Adapter surface;
- the request comes from a generic agent key without trusted approval or
  execution-recording scopes.

Adapter must not:

- treat adapter-private state as proposal or approval truth;
- expose approve/reject to generic MCP or OpenClaw clients by default;
- execute before Core approval and commit preflight;
- ask Core to execute the target ability;
- add Core `/execute`, `/proxy-execute`, scheduler, queue, workflow runtime, or
  provider credential storage.

Evidence for Adapter acceptance should include:

- Adapter `composer test:all`;
- Adapter WordPress smoke or equivalent local acceptance;
- one approved proposal that preflights, executes through WordPress Abilities
  API, and records `execution_status=success` back to Core;
- one failed or blocked proposal that does not execute;
- Core audit showing `proposal.approved`, `commit.preflighted`, and
  `proposal.executed` or `proposal.execution_failed` with the same
  `correlation_id`;
- no final WordPress mutation route added to Core.

Core's own WordPress smoke provides a boundary-safe dry-run proof of this
sequence for `npcink-abilities-toolkit/create-draft`: Core creates and approves
the proposal, returns an Adapter handoff, the host calls WordPress Abilities API
outside Core with the approved dry-run input, and a separate
`commit:record_execution` key records the outcome. Product Adapter acceptance
must still prove its own non-dry-run execution policy, idempotency, and
user-facing approval surface outside Core.

## Manual Approval Policy Acceptance

Run this checklist from the local WordPress admin surface:

```text
/wp-admin/admin.php?page=npcink-governance-core
```

Use `System settings -> Development Approval Policy` to change policy mode.
Use `Review Queue`, proposal detail, display-id lookup, and audit timeline to
verify the outcomes. These checks are local development acceptance only; do not
store local passwords or secrets in the repository.

### Require Approval For All

1. Save `Require approval for all`.
2. Create a representative write proposal, such as
   `npcink-abilities-toolkit/create-draft`.
3. Confirm the proposal is `pending`.
4. Confirm policy fields show `policy_decision=manual_required`,
   `policy_profile=manual`, and `policy_reasons` includes
   `default_manual_required`.
5. Approve from Core admin and run commit preflight.
6. Confirm preflight returns approval context and `commit_execution=false`.
7. Confirm Core did not create or mutate WordPress content.

### Smart Approval

1. Save `Smart approval`.
2. Use a trusted Adapter or app key with `proposals:create` and
   `proposals:approve`.
3. Submit a narrow supported candidate:
   - trusted nonproduction cleanup plan whose actions target only
     `npcink-abilities-toolkit/trash-post`; or
   - one direct draft-only `npcink-abilities-toolkit/create-draft` proposal
     with `dry_run=true`, `commit=false`, no publish or schedule intent, and no
     existing-content target; or
   - one guarded article-audio adoption proposal; or
   - one reviewed `npcink-abilities-toolkit/adopt-cloud-media-derivative`
     proposal with a single attachment, the exact local 11-field artifact
     descriptor (`art_[0-9a-f]{32}` and no URL/transport/ACK fields),
     dry-run/non-commit input, and `media_optimization_plan` preview evidence.
4. Confirm the proposal becomes `approved` without manual approval.
5. Confirm policy fields show `policy_decision=auto_approved`,
   `policy_profile=trusted_local`, and one of:
   `smart_guarded_cleanup_auto_approved` or
   `smart_guarded_create_draft_auto_approved` or
   `smart_guarded_article_audio_auto_approved` or
   `smart_guarded_media_derivative_auto_approved`.
6. Confirm `proposal.policy_evaluated` and `proposal.auto_approved` are in the
   audit timeline.
7. Run commit preflight and confirm it returns a handoff without executing the
   target ability.
8. Submit a non-candidate, such as comment approval, taxonomy terms, publish,
   schedule, destructive delete, or existing published-content update, and
   confirm it remains manual.

### Allow All Development Mode

1. Confirm `NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` is not enabled.
2. Save `Allow all (development only)`.
3. Submit a representative non-smart proposal and confirm it stays `pending`
   with `policy_decision=manual_required` and
   `dev_allow_all_rejected_disabled`.
4. Enable `NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` only in the local
   development environment.
5. Submit another representative proposal from a caller with approval
   authority.
6. Confirm it becomes `approved` with `policy_decision=auto_approved`,
   `dev_allow_all_auto_approved`, and `commit_preflight_still_required`.
7. Run commit preflight and confirm Core still returns `commit_execution=false`
   and does not execute the target ability.
8. Remove or disable the local development constant after the acceptance run.

### Stale Stored Policy Value

1. In a local-only environment, set
   `npcink_governance_core_approval_policy_mode` to a removed value such as
   `local_guarded` or `dry_run_guarded`.
2. Open `System settings -> Development Approval Policy`.
3. Confirm Core shows an inline warning that the stored value is no longer
   supported.
4. Confirm the effective current mode is `Require approval for all`.
5. Save one of the three supported modes and confirm the warning disappears.

## Acceptance Boundary

Manual acceptance is complete only when:

- all three supported policy modes behave as documented;
- stale stored values fall back to `manual` and remain visible to operators;
- display-id lookup opens proposal detail;
- audit timeline shows policy evaluation and approval/preflight events;
- commit preflight returns Adapter handoff data with `commit_execution=false`;
- final WordPress writes happen only in Adapter or another host executor after
  Core preflight;
- no Core code path executes a target ability.
