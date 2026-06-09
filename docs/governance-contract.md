# Governance Contract

Status: MVP contract.

This document defines the first Npcink Governance Core governance boundary.

## Operation Lifecycle

1. `discover`: Core lists available abilities from WordPress Abilities API and
   known provider APIs.
2. `plan`: for supported read-only planning abilities, Core can accept the
   plan output and convert `write_actions` into proposals without executing
   the plan or target writes.
3. `request`: a caller submits an intended operation.
4. `proposal`: Core records a reviewable proposal and normalizes metadata.
5. `review`: a human or trusted host policy approves or rejects the proposal.
6. `commit`: a future Core service executes only after approval.
7. `audit`: Core records every lifecycle event.

The MVP implements discovery, proposal records, approval/rejection status, and
audit records. Commit preflight verifies approval readiness without executing
writes. Commit execution is intentionally contract-first follow-up work.

## Proposal Shape

Required fields:

- `ability_id`: the target WordPress ability id. It must be a real,
  currently discoverable ability id from ability intake, not a planning label
  such as `content/draft-preview`.
- `input`: caller-supplied structured input.

Optional fields:

- `title`: human-readable proposal title.
- `summary`: human-readable proposal summary.
- `preview`: dry-run result, diff, or handoff payload from the provider.
- `caller`: caller identity metadata.

Core-generated fields:

- `proposal_id`
- `status`
- `policy_decision`
- `policy_profile`
- `policy_version`
- `policy_reasons`
- `created_at`
- `updated_at`

Allowed MVP statuses:

- `pending`
- `approved`
- `rejected`
- `expired`
- `archived`

Pending proposals have a bounded review lifetime. Core may automatically move
stale `pending` proposals to `expired` before listing, viewing, or deciding
them. Expired proposals are not eligible for approval until they are reopened.
Expired proposals may be archived as low-frequency audit records, and expired
or archived proposals may be reopened to `pending` when an administrator needs
to review them again.

## Plan-To-Proposal Intake

Core may consume these read-only planning ability outputs:

- `npcink-abilities-toolkit/build-content-inventory-fix-plan`
- `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`
- `npcink-abilities-toolkit/build-media-inventory-fix-plan`
- `npcink-abilities-toolkit/build-media-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-settings-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-optimization-plan`
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-abilities-toolkit/build-article-block-plan`
- `npcink-abilities-toolkit/build-pattern-page-plan`
- `npcink-toolbox/build-article-write-plan`
- `npcink-toolbox/build-article-batch-write-plan`
- `npcink-toolbox/build-article-media-batch-write-plan`
- `npcink-toolbox/build-image-candidate-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`
- `npcink-toolbox/build-content-metadata-apply-plan`

Plan intake does not execute the plan ability and does not execute target write
abilities. It accepts a successful plan payload, validates that the planning
ability is direct-read, validates each `write_action.target_ability_id` against
current ability intake, then creates one pending proposal per accepted action
unless the plan explicitly requests batch approval.

Local Admin Consent is not plan intake. Core may record
`local_admin_consent.requested`, `local_admin_consent.completed`, and
`local_admin_consent.failed` audit events through the
`npcink_governance_core_record_local_admin_consent` filter for a local product
module's already-authorized single-object action. That filter is audit-only:
it must not create proposals, approve proposals, preflight commits, or execute
abilities. If a local product module cannot record required Core audit, it
should fail closed instead of treating Local Admin Consent as an unaudited
write path.

The P0 article writing handoff is stricter: Toolbox owns the workflow artifact,
and Core accepts `npcink-toolbox/build-article-write-plan` only when it is an
`article_write_plan` containing the required article artifacts, a passing risk
report, no blocked claims, and exactly one draft-only
`npcink-abilities-toolkit/create-draft` action. Core preserves those artifacts in proposal
preview context for review without generating content or executing the write.

The bounded article batch handoff is separate from P0. Core accepts
`npcink-toolbox/build-article-batch-write-plan` only when it is an
`article_batch_write_plan` with `proposal_mode=batch`,
`batch_approval=true`, 2 to 5 draft-only `npcink-abilities-toolkit/create-draft` actions, and
one reviewed article artifact set per action. Core stores one batch proposal
for one user approval, but it still does not generate articles, approve the
proposal, execute writes, or run a batch writing job.

The media-enabled article batch handoff is separate from the draft-only batch.
Core accepts `npcink-toolbox/build-article-media-batch-write-plan` only when
it is an `article_media_batch_write_plan` with explicit batch approval, 1 to 5
reviewed article artifact sets, preserved image-source candidate evidence, and
allowlisted draft/media actions such as `npcink-abilities-toolkit/create-draft`,
`npcink-abilities-toolkit/upload-media-from-url`, `npcink-abilities-toolkit/update-media-details`, and
`npcink-abilities-toolkit/set-post-featured-image`. Core stores the grouped proposal only; it
does not search images, import media, set featured images, or execute writes.
This handoff is the high-risk contrast for Local Admin Consent: because it
combines multiple article/media actions and includes media import plus
featured-image writes, it must stay `core_proposal_required` and be stored as
one reviewable `plan_to_proposal_batch`.

The image candidate adoption handoff is separate from article generation and
media derivative optimization. Core accepts
`npcink-toolbox/build-image-candidate-adoption-plan` only when it is an
`image_candidate_adoption_plan` with a normalized `image_candidate.v1`
candidate, one `npcink-abilities-toolkit/upload-media-from-url` action, one
`npcink-abilities-toolkit/update-media-details` action, and at most one
`npcink-abilities-toolkit/set-post-featured-image` action. Core stores the grouped proposal
only; it does not search stock providers, generate images, import media, set
featured images, or execute writes.

The Site Knowledge agent handoff is a review-only bridge. Core accepts
`npcink-toolbox/build-site-knowledge-review-plan` only when it is a
`site_knowledge_review_plan` with preserved `evidence_refs` and one
non-ready `npcink-abilities-toolkit/create-draft` action requiring human
`title` and `content` input. Core stores a blocked review proposal only; it
does not generate drafts, approve proposals, pass commit preflight, or execute
WordPress writes from Cloud Site Knowledge output.

The content metadata apply handoff is a reviewed-choice bridge. Core accepts
`npcink-toolbox/build-content-metadata-apply-plan` only when it is a
`content_metadata_apply_plan` for one post, with explicit batch approval and
dry-run actions limited to excerpt updates and existing category or post-tag
assignment. One apply plan may contain at most one excerpt action, one category
assignment action, and one post-tag assignment action. Core stores proposal
truth and `preview.content_metadata_apply` only; it does not generate
summaries, create terms, approve proposals, store feedback/learning truth, or
execute WordPress writes.

The media optimization handoff is the governed shape for the user intent
"optimize this attachment." Core accepts
`npcink-abilities-toolkit/build-media-optimization-plan` only as an explicit batch proposal
for exactly one attachment, combining `npcink-abilities-toolkit/update-media-details` with a
derivative adoption action such as
`npcink-abilities-toolkit/adopt-cloud-media-derivative` or `npcink-abilities-toolkit/replace-media-file`.
Post-content media reference repair is part of the derivative adoption ability
contract and must not be split into a separate `npcink-abilities-toolkit/patch-post-content`,
`npcink-abilities-toolkit/update-post`, or `npcink-abilities-toolkit/update-post-blocks` action inside the
media optimization batch. Plans may expose the adoption dry-run
`content_reference_repairs` evidence in the derivative preview for review.
Cloud may provide derivative artifacts and diagnostics, but approval, adoption,
and WordPress writes stay local and outside Core execution.

The media rename handoff is the governed shape for the user intent "rename this
attachment file." Core accepts `npcink-abilities-toolkit/build-media-rename-plan` only as a
single `media_rename_plan` for exactly one attachment and one
`npcink-abilities-toolkit/rename-media-file` action with a reviewed `target_file_name`.
Filename generation rules stay in OpenClaw/local product policy; Core stores
proposal truth and approval context only.

The article optimization apply handoff is the governed shape for the user
intent "optimize this existing article" after the local Toolkit has produced a
reviewed optimization plan. Core accepts
`npcink-abilities-toolkit/build-article-optimization-apply-plan` only as an
`article_optimization_apply_plan` for exactly one post, with a bounded set of
dry-run, non-commit post update actions that all target that same post. Core
stores proposal truth and `preview.article_optimization` only; it does not
generate recommendations, rewrite content, approve proposals, or execute
WordPress writes.

The pattern page handoff is the governed shape for the user intent "create this
reviewed page pattern as a draft." Core accepts
`npcink-abilities-toolkit/build-pattern-page-plan` only as a
`pattern_page_plan` for the allowlisted `openai-style-landing` pattern and
`minimal-dark-light` style preset. It creates one ordered batch proposal for a
draft page create action and a Gutenberg block replacement action using the
new page output reference, and it stores `preview.pattern_page`. Core rejects
block classes outside the plan allowlist and does not render patterns or
execute WordPress writes.

The article block handoff is the governed shape for the user intent "create
this reviewed Gutenberg article as a draft." Core accepts
`npcink-abilities-toolkit/build-article-block-plan` only as an
`article_block_plan` for allowlisted editorial templates and
`responsive_profile=article_standard`. It creates one ordered batch proposal
for a draft post create action and a Gutenberg block replacement action using
the new post output reference, and it stores `preview.article_block`. Core
rejects custom block classes and does not generate article content, render
blocks, or execute WordPress writes.

Plans may request one review item for a group of generated actions with either
`batch_approval=true` or `proposal_mode=batch`. Core then creates one
`plan_to_proposal_batch` proposal containing `input.write_actions[]`. The batch
proposal remains a governance record only; Adapter or another host executor is
still responsible for final per-action allowlist and schema checks after Core
approval and commit preflight.

Generated proposal previews must preserve:

- `target_ability_id`;
- target `input`;
- `before`;
- `after_suggestion`;
- `reason`;
- `risk`;
- `required_scopes`;
- `requires_approval=true`;
- `dry_run=true`;
- `commit=false`;
- `commit_execution=false`;
- `proposal_ready`;
- `manual_review`;
- `skipped_destructive_candidates`.

Actions with `requires_input` are reviewable but not committable. Their preview
must carry `proposal_ready=false`, `needs_input`, and `preflight_blockers`, and
commit preflight must return `npcink_governance_core_proposal_items_blocked`.

Permanent media deletion is blocked by default. A plan action targeting
`npcink-abilities-toolkit/delete-media-permanently` may become a proposal only when
`include_delete_candidates=true` is explicitly supplied with the plan input,
and it must remain high risk. The planning ability must still satisfy its own
destructive-media policy first, such as requiring
`include_unattached_nonproduction_media=true` for unattached test media or
`include_trash_parent_media=true` for media attached to trash-parent content.

## Approval Boundary

Write and destructive commits must fail closed unless the commit request carries
host approval context created by Core.

Proposal creation validates that the target ability is currently discoverable.
Commit preflight repeats discovery against the stored real `ability_id` and
fails closed if that ability disappeared after approval. Proposal creation also
stores a governance-relevant ability contract fingerprint covering risk,
approval requirement, execution guidance, WordPress capability, required scopes,
and input schema. Commit preflight fails closed if the live fingerprint no
longer matches the approved proposal.

Core evaluates a lightweight approval policy decision during proposal creation.
The default `manual` mode records `manual_required` for every proposal with
`policy_profile=manual` and `policy_version=core-approval-policy-v1`.
Development mode `dry_run_guarded` may classify trusted cleanup candidates with
`policy_profile=guarded` while leaving them pending. Development mode
`local_guarded` may return `auto_approved` only for trusted
`build-nonproduction-content-cleanup-plan` batch proposals whose actions all target
`npcink-abilities-toolkit/trash-post`, have persisted test-content evidence, pass caller
authorization, and pass auto-approval quotas. The evaluator does not expose a
rules DSL and does not add workflow runtime, long-running policy jobs, final
execution, or a configuration center.

Implementation rules and the staged auto-approval roadmap are documented in
[Approval Policy Evaluator Standard](approval-policy-evaluator-standard.md).

Reserved policy decision values are:

- `manual_required`
- `auto_approved`
- `blocked`

Reserved policy profiles are:

- `manual`
- `guarded`
- `trusted_local`
- `break_glass`

The new Core uses approval-commit terminology. It must not reintroduce
`confirm_token`, `write_confirmed`, or other legacy confirmation parameters.

## Audit Events

MVP event names:

- `proposal.created`
- `proposal.policy_evaluated`
- `proposal.auto_approved`
- `proposal.deduplicated`
- `proposal.quota_blocked`
- `proposal.plan_ingested`
- `proposal.approved`
- `proposal.rejected`
- `proposal.expired`
- `proposal.archived`
- `proposal.reopened`
- `proposal.viewed`
- `proposal.listed`
- `capabilities.listed`
- `audit.listed`
- `commit.preflighted`
- `commit.preflight_failed`
- `app.created`
- `app.revoked`
- `app.rate_limited`
- `app.scope_denied`
- `core.approval_policy_updated`

Future event names:

- `commit.requested`
- `commit.succeeded`
- `commit.failed`

## Governance Operability

Core keeps enough operational evidence for proposal review and adapter
debugging without becoming an execution runtime or analytics system.

Proposal detail responses include an `audit_timeline` for the selected
proposal. The admin proposal detail uses the same evidence to show the proposal
payload, live capability summary, lifecycle events, app attribution, scope
decision, and commit-preflight correlation id.

Audit list filters include:

- `proposal_id`
- `event_name`
- `ability_id`
- `app_id`
- `key_id`
- `caller_type`
- `correlation_id`

Successful commit preflight returns a `correlation_id` in the response,
includes it in `approval_context.correlation_id`, and records the same value in
the `commit.preflighted` audit event metadata. Proposal-bound preflight failures
record `commit.preflight_failed` with the stable error code, target ability id,
proposal status, and `commit_execution=false`.

App-authenticated audit metadata includes `scope_decision`, currently
`allowed`, `denied`, or `rate_limited`.

## Security Defaults

- REST routes require `manage_options` in the MVP.
- Inputs are sanitized before persistence.
- Outputs are escaped by callers or REST serialization.
- SQL writes must use `$wpdb->insert()` or prepared queries.
- Secrets, provider keys, raw cookies, and passwords must not be stored in
  proposals or audit metadata.
