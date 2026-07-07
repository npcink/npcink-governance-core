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
6. `preflight`: Core issues one bounded execution handoff after approval.
7. `execution_result`: Adapter or another host records the post-preflight
   execution outcome back to Core without making Core the executor.
8. `audit`: Core records every lifecycle event.

The MVP implements discovery, proposal records, approval/rejection status,
post-preflight execution outcome status, and audit records. Commit preflight
verifies approval readiness without executing writes. Commit execution remains
outside Core.

Sensitive read abilities use a separate read request lifecycle. A read request
is not a proposal and does not authorize writes. It records reviewable intent
for a bounded read, binds the request to `ability_id` and `input_hash`, and can
return a Core-generated `read_authorization_context` only after approval.
Adapter or another host still executes the read through WordPress Abilities API
and must enforce Core bounds and redaction.

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
- `display_id`: deterministic human-facing alias derived from `proposal_id`
  for operator display and admin lookup. It is not a replacement primary id.
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
- `executed`
- `execution_failed`

Pending proposals have a bounded review lifetime. Core may automatically move
stale `pending` proposals to `expired` before listing, viewing, or deciding
them. Expired proposals are not eligible for approval until they are reopened.
Expired proposals may be archived as low-frequency audit records, and expired
or archived proposals may be reopened to `pending` when an administrator needs
to review them again.

Approved proposals may transition to `executed` or `execution_failed` only
through a post-preflight execution-result record that binds to the Core-issued
`commit.preflighted` `correlation_id` and `approved_input_hash`. This records
Adapter-owned execution outcome and audit evidence; Core still does not execute
the target ability or store full ability result payloads.

## Plan-To-Proposal Intake

Core may consume these read-only planning ability outputs:

- `npcink-abilities-toolkit/build-content-inventory-fix-plan`
- `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`
- `npcink-abilities-toolkit/build-media-inventory-fix-plan`
- `npcink-abilities-toolkit/build-media-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-settings-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-optimization-plan`
- `npcink-abilities-toolkit/build-media-adoption-enhancement-plan`
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-abilities-toolkit/build-article-block-plan`
- `npcink-abilities-toolkit/build-pattern-page-plan`
- `npcink-abilities-toolkit/build-block-theme-site-plan`
- `npcink-toolbox/build-article-write-plan`
- `npcink-toolbox/build-article-batch-write-plan`
- `npcink-toolbox/build-article-media-batch-write-plan`
- `npcink-abilities-toolkit/build-image-candidate-adoption-plan`
- `npcink-abilities-toolkit/build-article-audio-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`
- `npcink-toolbox/build-nightly-inspection-review-plan`
- `npcink-abilities-toolkit/build-content-metadata-apply-plan`

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
write path. The filter also fails closed unless the metadata includes an
`operation-classification-v1` decision envelope whose classification is
`local_admin_consent` or `strong_local_confirmation`; a
`core_proposal_required` classification must use proposal intake instead.

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
`npcink-abilities-toolkit/build-image-candidate-adoption-plan` only when it is an
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
`npcink-abilities-toolkit/build-content-metadata-apply-plan` only when it is a
`content_metadata_apply_plan` for one post, with explicit batch approval and
dry-run actions limited to excerpt updates and existing category or post-tag
assignment. One apply plan may contain at most one excerpt action, one category
assignment action, and one post-tag assignment action. Core stores proposal
truth and `preview.content_metadata_apply` only; if classification evidence is
present, it must show `core_proposal_required` before Core accepts the plan.
Core does not generate summaries, create terms, approve proposals, store
feedback/learning truth, or execute WordPress writes.

Every accepted direct or plan-generated proposal receives
`preview.operation_classification` with `classification=core_proposal_required`
and `intake_path=core_proposal`. This is Core's stored evidence that the request
entered the independent proposal-review path. If submitted preview evidence
claims `suggestion_only`, `local_admin_consent`, or
`strong_local_confirmation`, Core rejects the proposal request instead of
silently converting that lower-friction path into a proposal.

The media optimization handoff is the governed shape for the user intent
"optimize this attachment." Core accepts
`npcink-abilities-toolkit/build-media-optimization-plan` only as an explicit batch proposal
where every attachment has paired `npcink-abilities-toolkit/update-media-details` and
derivative adoption actions such as
`npcink-abilities-toolkit/adopt-cloud-media-derivative` or `npcink-abilities-toolkit/replace-media-file`.
Post-content media reference repair is part of the derivative adoption ability
contract and must not be split into a separate `npcink-abilities-toolkit/patch-post-content`,
`npcink-abilities-toolkit/update-post`, or `npcink-abilities-toolkit/update-post-blocks` action inside the
media optimization batch. Plans may expose the adoption dry-run
`content_reference_repairs` evidence in the derivative preview for review.
Cloud may provide derivative artifacts and diagnostics, but approval, adoption,
and WordPress writes stay local and outside Core execution.

The media adoption enhancement handoff is the governed shape for the user intent
"adopt this reviewed remote image into a page." Core accepts
`npcink-abilities-toolkit/build-media-adoption-enhancement-plan` only as a
`media_adoption_enhancement_plan` batch with one
`npcink-abilities-toolkit/upload-media-from-url` action, one
`npcink-abilities-toolkit/optimize-media-asset` action, and at most one
`npcink-abilities-toolkit/patch-post-content` action that replaces a reviewed
old URL with `$outputs.optimize-media-asset.derivative_url`. Core stores
proposal truth and `preview.media_adoption_enhancement` only; it does not
search for images, generate images, import media, optimize files, repair page
content, approve proposals, or execute WordPress writes.

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

The block theme site handoff is the governed shape for the user intent "modify
this active block theme template." Core accepts
`npcink-abilities-toolkit/build-block-theme-site-plan` only as a
`block_theme_site_plan` with `intent=add_breadcrumbs` or
`intent=customize_template_layout`, `proposal_mode=batch`, and template write
actions limited to
`npcink-abilities-toolkit/update-template-blocks` or
`npcink-abilities-toolkit/upsert-template-blocks`. Layout customization plans
must include a passing bounded `template_layout_contract` with accepted compiler,
policy, and profile versions (`block_theme_profile_compiler@0.3`,
`block_theme_safe_core_blocks@0.2`, and versioned profiles such as
`page_standard@0.2`, `homepage_landing@0.3`). Core stores
`preview.block_theme_site` and the reviewed block tree, but does not edit theme
files, navigation entities, global styles, approve proposals, or execute
WordPress writes.
Accepted block theme template plans are limited to bounded template content
changes: accepted template slugs, safe core blocks only, declared parser
roundtrip validation, bounded block count/depth/attribute size, and no
scriptable or embedded raw HTML. The `homepage_landing` profile may include
safe dynamic reader blocks such as `core/latest-posts` and `core/categories`
for latest post and category entry sections. `article_standard` plans may use
safe dynamic template blocks such as `core/post-terms`,
`core/post-navigation-link`, and `core/comments`. Navigation, global styles, theme files,
`theme.json`, custom HTML/freeform, shortcode, embed, and unknown block changes
are rejected before proposal creation.

Plans may request one review item for a group of generated actions with either
`batch_approval=true` or `proposal_mode=batch`. Core then creates one
`plan_to_proposal_batch` proposal containing `input.write_actions[]`. The batch
proposal remains a governance record only; Adapter or another host executor is
still responsible for final per-action allowlist and schema checks after Core
approval and commit preflight. Batch previews include
`batch_review_summary` so review surfaces can show action counts, target
ability ids, blocked counts, retry guidance, and the operator next action
without creating a Core queue, retry worker, or unattended execution runtime.

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
The supported approval policy mode set is closed to `manual`,
`smart_guarded`, and `dev_allow_all`. Unrecognized stored values, including
removed legacy mode names, fall back to `manual` and must not act as aliases.
The default `manual` mode records `manual_required` for every proposal with
`policy_profile=manual` and `policy_version=core-approval-policy-v1`.
Mode `smart_guarded` may return `auto_approved` only for trusted
`build-nonproduction-content-cleanup-plan` batch proposals whose actions all target
`npcink-abilities-toolkit/trash-post`, have persisted test-content evidence, pass caller
authorization, and pass auto-approval quotas; or for a single direct
`npcink-abilities-toolkit/create-draft` proposal that creates only a draft post,
does not target existing content, stays dry-run/non-commit, and has no
schedule/publish intent; or for a guarded `npcink-abilities-toolkit/adopt-article-audio`
proposal from the article-audio plan path; or for one reviewed
`npcink-abilities-toolkit/adopt-cloud-media-derivative` proposal with a single
attachment, derivative artifact evidence, dry-run/non-commit input, and
`media_optimization_plan` preview evidence; or for one reviewed
`npcink-abilities-toolkit/update-media-details` ALT-only proposal from a
Toolbox `media_alt_caption_review_set.v1` row. Mode `dev_allow_all` may auto-approve every proposal
only in local development when
`NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL` is true, the caller can approve
proposals, quotas pass, and audit succeeds. The evaluator does not expose a
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

## Sensitive Read Request Boundary

Read abilities that expose sensitive data must fail closed unless Core has
approved a sensitive read request. Capability rows may mark this with
`read_authorization_required=true`, `requires_read_authorization=true`,
`read_policy=core_read_authorization_required`,
`authorization_mode=core_read_request`, or
`read_authorization.required=true`.

A Core read request stores `request_id`, `ability_id`, `input_hash`,
`requested_input_summary`, `sensitivity`, `data_classes`, `redaction_level`,
`purpose`, caller metadata, status, `expires_at`, bounds, `correlation_id`,
timestamps, and audit timeline. Allowed statuses are `pending`, `approved`,
`rejected`, `expired`, and `consumed` for one-time grants.

Read preflight returns bounded `read_authorization_context` with
`read_authorization_granted=true`,
`core_authorization_truth=npcink_governance_core`,
`commit_execution=false`, and `write_execution=false`. It must reject wrong
`ability_id`, changed input hash, rejected requests, expired requests, consumed
one-time grants, unauditable grants, or attempts to widen the capability's
declared read scope. Prompt text, Adapter state, direct database reads, file
reads, log reads, cookies, authorization headers, tokens, and custom scripts
are never authorization truth.

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
- `proposal.executed`
- `proposal.execution_failed`
- `proposal.viewed`
- `proposal.listed`
- `read_request.created`
- `read_request.approved`
- `read_request.rejected`
- `read_request.expired`
- `read_request.consumed`
- `read_request.viewed`
- `read_request.listed`
- `read_request.preflighted`
- `read_request.preflight_failed`
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
`allowed`, `denied`, `expired`, or `rate_limited`.

## Security Defaults

- REST routes require `manage_options` in the MVP.
- Inputs are sanitized before persistence.
- Outputs are escaped by callers or REST serialization.
- SQL writes must use `$wpdb->insert()` or prepared queries.
- Secrets, provider keys, raw cookies, and passwords must not be stored in
  proposals or audit metadata.
