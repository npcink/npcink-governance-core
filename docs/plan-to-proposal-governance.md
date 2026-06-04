# Plan To Proposal Governance

Status: active.

Core can now consume read-only planning ability output and turn the plan's
`write_actions` into ordinary Core proposals. This is a governance bridge, not
an execution bridge.

## Supported Plan Abilities

- `magick-ai/build-content-inventory-fix-plan`
- `magick-ai/build-test-content-cleanup-plan`
- `magick-ai/build-media-inventory-fix-plan`
- `magick-ai/build-media-reference-repair-plan`
- `magick-ai/build-media-settings-reference-repair-plan`
- `magick-ai/build-media-optimization-plan`
- `magick-ai/build-media-rename-plan`
- `magick-ai-toolbox/build-article-write-plan`
- `magick-ai-toolbox/build-article-batch-write-plan`
- `magick-ai-toolbox/build-article-media-batch-write-plan`
- `magick-ai-toolbox/build-image-candidate-adoption-plan`

The `magick-ai/*` planning abilities belong to `magick-ai-abilities`; the
Toolbox article and image candidate handoffs belong to `magick-ai-toolbox`. They are executed
through the WordPress Abilities API by the host or adapter. Core only receives
their output. The Toolbox plan is included here because Core can govern its
write plan without owning Toolbox workflow UX, content generation, image
search, or image generation.

## Boundary

Core owns:

- accepting plan output;
- validating the plan ability is allowed and direct-read;
- validating each `target_ability_id` is discoverable and proposal-governed;
- converting accepted `write_actions` into pending proposals;
- preserving preview, risk, warning, blocked, and needs-input context;
- approval, rejection, commit preflight, and audit.

Core does not own:

- running the planning abilities;
- generating content, SEO, media, or cleanup recommendations;
- executing final WordPress mutations;
- workflow runtime, MCP runtime, queueing, or batch execution.

## REST Flow

1. Adapter or host runs a supported read-only plan ability through
   `/wp-json/wp-abilities/v1/abilities/{ability_id}/run`.
2. Adapter posts the plan output to
   `POST /wp-json/magick-ai-core/v1/proposals/from-plan`.
3. Core creates one pending proposal per accepted independent `write_action` by
   default. If the plan declares `batch_approval=true` or
   `proposal_mode=batch`, or if actions use `depends_on` or
   `$outputs.<prior_action_id>.<field>`, Core creates one ordered batch
   proposal so the Adapter can review and execute the approved group through
   its batch resolver. Core preserves `depends_on` for review and audit; the
   batch proposal's first `ability_id` is only a Core availability/preflight
   anchor, not a per-action execution safety endorsement.
4. Admin or trusted policy approves or rejects proposals through the existing
   proposal routes.
5. Adapter calls
   `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`.
6. Only after approval and successful preflight may the host call the real write
   ability outside Core.

Core still returns `commit_execution=false`; there is no Core write execution
route.

## Article Writing Handoff

`magick-ai-toolbox/build-article-write-plan` is the P0 AI-assisted writing
handoff. It must return `artifact_type=article_write_plan`, `version>=1`,
`requires_approval=true`, `dry_run=true`, `commit_execution=false`, and the
standard article artifacts documented in
[Article Writing Workflow Contract](article-writing-workflow-contract.md).

Core accepts that plan only when `article_risk_report.ready_for_proposal=true`,
`article_risk_report.risk_level` is not `high`,
`article_risk_report.blocked_claims` is empty, and the plan contains exactly
one draft-only `magick-ai/create-draft` write action. The generated proposal
preserves `preview.article_workflow` for review. Core does not generate the
article, run Toolbox tools, call Cloud, approve the proposal, or execute the
draft write.

Article writing is a local Ability recipe, not a Cloud writing feature. Cloud
must not produce article drafts, `article_write_plan` candidates, or bulk
article artifacts for Core intake. If a local host runs the
`article_draft_v1` recipe, Core still receives only the same
`magick-ai-toolbox/build-article-write-plan` output and applies the same
single-draft acceptance rules. See
[Ability Recipe Orchestration Contract](ability-recipe-orchestration-contract.md)
and [Cloud Bulk Article Run Contract](cloud-bulk-article-run-contract.md).

`magick-ai-toolbox/build-article-batch-write-plan` is the bounded local batch
draft handoff for the same Article Assistant Workbench. It is not a Cloud
writing feature. Core accepts it only when it declares
`artifact_type=article_batch_write_plan`, `proposal_mode=batch`,
`batch_approval=true`, includes 2 to 5 draft-only
`magick-ai/create-draft` actions, and carries one reviewed article artifact set
per action. Publish requests, high-risk article artifacts, blocked claims,
`commit=true`, `dry_run=false`, or missing per-article review artifacts are
rejected before proposal creation. Core stores one `plan_to_proposal_batch`
proposal so the user can approve the related draft writes once, while Adapter
still performs per-action allowlist, schema, idempotency, and execution checks
outside Core.

`magick-ai-toolbox/build-article-media-batch-write-plan` is the media-enabled
local batch handoff for reviewed drafts with reviewed image-source candidates.
It is not a Cloud writing feature and not an image generation/import runtime.
Core accepts it only when it declares
`artifact_type=article_media_batch_write_plan`, `proposal_mode=batch`,
`batch_approval=true`, includes 1 to 5 reviewed article artifact sets,
preserves selected image-source candidate evidence, and uses only allowlisted
draft/media actions such as `magick-ai/create-draft`,
`magick-ai/upload-media-from-url`, `magick-ai/update-media-details`, and
`magick-ai/set-post-featured-image`.

## Image Candidate Adoption Handoff

`magick-ai-toolbox/build-image-candidate-adoption-plan` is the bounded local
handoff for adopting one reviewed image candidate from stock, AI-generated,
owned, external, or manual-upload sources. It is not a Cloud image registry,
not an image generation runtime, and not a media import executor.

Core accepts it only when it declares
`artifact_type=image_candidate_adoption_plan`, carries
`candidate_contract_version=image_candidate.v1` or a selected candidate with
`contract_version=image_candidate.v1`, and contains dry-run write actions for:

- exactly one `magick-ai/upload-media-from-url` action;
- exactly one `magick-ai/update-media-details` action;
- at most one `magick-ai/set-post-featured-image` action.

Each action must keep `dry_run=true` and `commit=false`. Core stores one
`plan_to_proposal_batch` proposal so the user can approve the reviewed import,
metadata, and optional featured-image update together. Adapter or the local
host still performs per-action allowlist, schema, idempotency, and execution
checks after Core approval and commit preflight. Core does not download the
image, upload media, set featured images, or persist provider candidate truth.

## Media Optimization Handoff

`magick-ai/build-media-optimization-plan` is the bounded local plan for the user
intent "optimize this media item." It must declare
`artifact_type=media_optimization_plan`, `proposal_mode=batch`,
`batch_approval=true`, and target exactly one attachment across all write
actions.

The plan must include:

- one `magick-ai/update-media-details` action for title, alt, caption,
  description, or source metadata;
- one derivative adoption action, currently
  `magick-ai/adopt-cloud-media-derivative` or `magick-ai/replace-media-file`;
- dry-run preview evidence for the metadata change and derivative change.

Cloud may create or return a derivative artifact, checksum, mime type, size
preview, or processing diagnostics through the local Cloud Addon path, but
final proposal, approval, adoption, and WordPress writes stay local. Core does
not optimize images, execute media writes, or approve the proposal
automatically.

## Media Rename Handoff

`magick-ai/build-media-rename-plan` is the bounded local plan for renaming one
attachment main file after the operator has reviewed the filename. It is not a
filename policy engine and does not compute hashes inside Core.

Core accepts it only when it declares `artifact_type=media_rename_plan`, targets
exactly one `attachment_id`, and contains exactly one dry-run
`magick-ai/rename-media-file` action with a non-empty `target_file_name`.
Optional expected current path, MIME type, MD5, SHA256, conflict mode, and
backup suffix guards may be preserved in action input for Adapter/host
execution after Core approval and commit preflight.

## Proposal Preview Contract

Generated proposal previews preserve:

- `source.type=plan_to_proposal`;
- `source.plan_ability_id`;
- `source.batch_id`;
- `source.issue_types`;
- `action_id`;
- `action_index`;
- `target_ability_id`;
- `before`;
- `after_suggestion`;
- `reason`;
- `risk.level`;
- `risk.plan_level`;
- `risk.target_risk_level`;
- `required_scopes`;
- `requires_approval=true`;
- `dry_run=true`;
- `commit=false`;
- `commit_execution=false`;
- `proposal_ready`;
- `needs_input`;
- `warnings.manual_review`;
- `warnings.skipped_destructive_candidates`;
- `blocked_items.manual_review`;
- `blocked_items.skipped_destructive_candidates`;
- `preflight_blockers`.

The target ability input is stored in proposal `input` with `dry_run=true` and
`commit=false` forced by Core.

## Safety Rules

Plan intake fails closed when:

- the planning ability id is not supported;
- the planning ability is not discoverable;
- the planning ability is not `governance_mode=direct_read`;
- the plan does not include `requires_approval=true`;
- the plan does not include `dry_run=true`;
- the plan includes `commit_execution=true`;
- the plan lacks a `write_actions` array;
- a target ability is not discoverable;
- a target ability is not proposal-governed;
- a target ability unexpectedly enables Core proxy or commit execution;
- an action input sets `dry_run=false` or `commit=true`.

`manual_review` and `skipped_destructive_candidates` are never dropped. They
are copied into generated proposal warnings and blocked item context.

Permanent media deletion is stricter: `magick-ai/delete-media-permanently`
actions are blocked unless the submitted `plan_input` explicitly contains
`include_delete_candidates=true`. The media planning ability still decides
whether a delete action can be emitted at all; current destructive-media plans
also require a narrow source-side flag such as
`include_unattached_test_media=true` or `include_trash_parent_media=true`.
Allowed delete proposals remain high risk.

## Batch Approval

Planning abilities may request a single approval for a bounded group of related
actions by returning `batch_approval=true` or `proposal_mode=batch` at the plan
data level. This is intended for one-plan, many-action cleanup cases where
separate proposals would create review fatigue without improving governance.
It is also the required shape for article batch draft and media optimization
plans, where the proposal represents one user intent and `write_actions[]`
records the per-ability execution units.

Batch approval does not let Core execute writes. The proposal stores the
ordered `input.write_actions[]`, records `source.type=plan_to_proposal_batch`,
and still requires normal approval and commit preflight. The Adapter execution
path must continue to enforce its per-action allowlist, schema validation,
dependency/output reference rules, and batch size limits before any WordPress
mutation happens.

## Commit Preflight

Commit preflight now evaluates proposal item readiness:

- `proposal_ready=true`, no `needs_input`, and no `preflight_blockers` can pass
  after approval.
- `proposal_ready=false` or non-empty `needs_input`/`preflight_blockers` returns
  `magick_ai_core_proposal_items_blocked` with HTTP `409`.

This means a human may review an incomplete plan action, but the host cannot
treat it as committable until the required input is resolved in a later
proposal.

## Audit

Plan intake records:

- `proposal.plan_ingested`

Each generated proposal also records the normal proposal lifecycle events:

- `proposal.created`
- `proposal.approved`
- `proposal.rejected`
- `commit.preflighted`

Use `proposal_id`, `ability_id`, and `correlation_id` filters in Core
Governance Audit to trace the plan-to-proposal lifecycle.
