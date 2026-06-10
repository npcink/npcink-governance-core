# Plan To Proposal Governance

Status: active.

Core can now consume read-only planning ability output and turn the plan's
`write_actions` into ordinary Core proposals. This is a governance bridge, not
an execution bridge.

## Supported Plan Abilities

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
- `npcink-toolbox/build-image-candidate-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`
- `npcink-toolbox/build-content-metadata-apply-plan`

The `npcink-abilities-toolkit/*` planning abilities belong to `npcink-abilities-toolkit`; the
Toolbox article and image candidate handoffs belong to `npcink-toolbox`. They are executed
through the WordPress Abilities API by the host or adapter. Core only receives
their output. The Toolbox plan is included here because Core can govern its
write plan without owning Toolbox workflow UX, content generation, image
search, or image generation.

`npcink-toolbox/build-site-knowledge-review-plan` is a narrow bridge from the
Cloud Site Knowledge agent handoff into local Core review. The plan must carry
evidence refs and create only a blocked draft-review proposal with human
`title` and `content` input still required. It is not an autonomous article
writer, Cloud write path, or approval/preflight bypass.

`npcink-toolbox/build-content-metadata-apply-plan` is the reviewed metadata
choice handoff from the Toolbox editor. It may package accepted excerpt,
existing category, and existing tag choices into dry-run `write_actions`, but it
must not create terms, mutate SEO fields, rewrite content, or claim Toolbox
direct write authority.

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
   `POST /wp-json/npcink-governance-core/v1/proposals/from-plan`.
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
   `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`.
6. Only after approval and successful preflight may the host call the real write
   ability outside Core.

Core still returns `commit_execution=false`; there is no Core write execution
route.

## Article Writing Handoff

`npcink-toolbox/build-article-write-plan` is the P0 AI-assisted writing
handoff. It must return `artifact_type=article_write_plan`, `version>=1`,
`requires_approval=true`, `dry_run=true`, `commit_execution=false`, and the
standard article artifacts documented in
[Article Writing Workflow Contract](article-writing-workflow-contract.md).

Core accepts that plan only when `article_risk_report.ready_for_proposal=true`,
`article_risk_report.risk_level` is not `high`,
`article_risk_report.blocked_claims` is empty, and the plan contains exactly
one draft-only `npcink-abilities-toolkit/create-draft` write action. The generated proposal
preserves `preview.article_workflow` for review. Core does not generate the
article, run Toolbox tools, call Cloud, approve the proposal, or execute the
draft write.

`npcink-abilities-toolkit/build-article-optimization-apply-plan` is the bounded
local handoff for the user intent "optimize this existing article" after a
reviewed article optimization report already exists. It must return
`artifact_type=article_optimization_apply_plan`, `requires_approval=true`,
`dry_run=true`, `commit_execution=false`, `direct_wordpress_write=false`, and a
small set of reviewed `write_actions` for the same target post. Core currently
accepts only allowlisted post update targets such as
`npcink-abilities-toolkit/update-post`, `npcink-abilities-toolkit/set-post-seo-meta`,
`npcink-abilities-toolkit/patch-post-content`, and
`npcink-abilities-toolkit/update-post-blocks`; each action must keep
`dry_run=true`, `commit=false`, and the same `post_id` as the plan.
For update-post-blocks actions, Core preserves the case-sensitive Gutenberg
block tree under `input.blocks` through proposal persistence, including
`blockName`, `innerBlocks`, `innerHTML`, `innerContent`, and attrs camelCase.

The generated proposal preserves `preview.article_optimization` with the source
recipe ref, safe apply summary, advisory sections, and
`direct_wordpress_write=false`. Core does not produce optimization
recommendations, rewrite article content, approve the proposal, or execute the
post update. Adapter or the local host still performs final per-action
allowlist, schema, idempotency, and execution checks after Core approval and
commit preflight.

`npcink-abilities-toolkit/build-pattern-page-plan` is the bounded local handoff
for creating a draft page from an allowlisted Gutenberg page pattern. It must
return `artifact_type=pattern_page_plan`, `pattern_id=openai-style-landing`,
`style_preset=minimal-dark-light`, `proposal_mode=batch`,
`requires_approval=true`, `dry_run=true`, `commit_execution=false`, and
`direct_wordpress_write=false`. Core accepts exactly two ordered actions:
`npcink-abilities-toolkit/create-draft` for a draft `page`, followed by
`npcink-abilities-toolkit/update-post-blocks` that uses
`$outputs.create-pattern-page.post_id` and a non-empty Gutenberg `blocks` tree.

The plan must include `allowed_classes`, and Core rejects block `className`
values outside that allowlist. The generated batch proposal preserves
`preview.pattern_page` with the pattern id, style preset, block count, action
count, and allowed class list. Core does not render the pattern, create the
draft page, approve the proposal, execute the block update, or provide a
generic final write path.

`npcink-abilities-toolkit/build-article-block-plan` is the bounded local
handoff for creating a draft post from whitelisted Gutenberg-native editorial
article structures. It must return `artifact_type=article_block_plan`,
`article_template` in `editorial-longform`, `how-to-guide`, or
`comparison-review`, `responsive_profile=article_standard`,
`proposal_mode=batch`, `requires_approval=true`, `dry_run=true`,
`commit_execution=false`, and `direct_wordpress_write=false`. Core accepts
exactly two ordered actions: `npcink-abilities-toolkit/create-draft` for a
draft `post`, followed by `npcink-abilities-toolkit/update-post-blocks` that
uses `$outputs.create-article-draft.post_id` and a non-empty Gutenberg `blocks`
tree.

The plan must report native editorial and responsive quality, including
`editorial_quality.uses_native_blocks=true` and
`custom_css_required=false`. Core rejects custom block `className` values for
this article plan because article visual structure should come from core
blocks and native attrs, not arbitrary CSS. The generated batch proposal
preserves `preview.article_block` with the article template, responsive
profile, media strategy, block count, and quality summaries. Core does not
generate the article, render the blocks, create the draft post, approve the
proposal, execute the block update, or provide a generic final write path.

`npcink-abilities-toolkit/build-block-theme-site-plan` is the bounded local
handoff for modifying reviewed templates in the active block theme. It must
return `artifact_type=block_theme_site_plan`, `intent=add_breadcrumbs`,
`proposal_mode=batch`, `requires_approval=true`, `dry_run=true`,
`commit_execution=false`, and `direct_wordpress_write=false`. Core accepts only
template block write actions targeting
`npcink-abilities-toolkit/update-template-blocks` or
`npcink-abilities-toolkit/upsert-template-blocks`, with `mode=replace` and a
non-empty Gutenberg `blocks` tree.

File-backed templates are represented as reviewed
`npcink-abilities-toolkit/upsert-template-blocks` actions that create a
`wp_template` Site Editor override after approval and external execution. The
generated batch proposal preserves `preview.block_theme_site` with active theme
evidence, affected template slugs, action count, and
`file_template_write_mode=create_wp_template_override`. Core does not edit
theme files, navigation entities, global styles, approve the proposal, execute
the write, or provide a generic final write path.

`npcink-toolbox/build-content-metadata-apply-plan` is the bounded local handoff
for the user intent "apply these reviewed article metadata choices" after
Toolbox has produced a `content_metadata_delta` and the operator has accepted
specific choices. It must return `artifact_type=content_metadata_apply_plan`,
`proposal_mode=batch`, `batch_approval=true`, `requires_approval=true`,
`dry_run=true`, `commit_execution=false`, `direct_wordpress_write=false`, and a
small set of reviewed actions for one target post. Core accepts only
`npcink-abilities-toolkit/update-post` actions that update `excerpt`, and
`npcink-abilities-toolkit/set-post-terms` actions that target `category` or
`post_tag` with existing `term_ids`, `create_missing=false`, `dry_run=true`, and
`commit=false`. The batch may contain at most one excerpt action, at most one
category assignment action, and at most one post-tag assignment action. Core
rejects duplicate metadata action slots, title/content updates, SEO writes,
missing-term creation, named `terms`, unsupported taxonomies, and remove-mode
term changes.

The generated batch proposal preserves `preview.content_metadata_apply` with the
target post id, accepted choices, evidence refs, new-term candidate count, and
`direct_wordpress_write=false`. Core does not generate metadata suggestions,
approve the proposal, execute the write, create taxonomy terms, store feedback,
or maintain a learning loop.

Article writing is a local Ability recipe, not a Cloud writing feature. Cloud
must not produce article drafts, `article_write_plan` candidates, or bulk
article artifacts for Core intake. If a local host runs the
`article_draft_v1` recipe, Core still receives only the same
`npcink-toolbox/build-article-write-plan` output and applies the same
single-draft acceptance rules. See
[Ability Recipe Orchestration Contract](ability-recipe-orchestration-contract.md)
and [Cloud Bulk Article Run Contract](cloud-bulk-article-run-contract.md).

`npcink-toolbox/build-article-batch-write-plan` is the bounded local batch
draft handoff for the same Article Assistant Workbench. It is not a Cloud
writing feature. Core accepts it only when it declares
`artifact_type=article_batch_write_plan`, `proposal_mode=batch`,
`batch_approval=true`, includes 2 to 5 draft-only
`npcink-abilities-toolkit/create-draft` actions, and carries one reviewed article artifact set
per action. Publish requests, high-risk article artifacts, blocked claims,
`commit=true`, `dry_run=false`, or missing per-article review artifacts are
rejected before proposal creation. Core stores one `plan_to_proposal_batch`
proposal so the user can approve the related draft writes once, while Adapter
still performs per-action allowlist, schema, idempotency, and execution checks
outside Core.

`npcink-toolbox/build-article-media-batch-write-plan` is the media-enabled
local batch handoff for reviewed drafts with reviewed image-source candidates.
It is not a Cloud writing feature and not an image generation/import runtime.
Core accepts it only when it declares
`artifact_type=article_media_batch_write_plan`, `proposal_mode=batch`,
`batch_approval=true`, includes 1 to 5 reviewed article artifact sets,
preserves selected image-source candidate evidence, and uses only allowlisted
draft/media actions such as `npcink-abilities-toolkit/create-draft`,
`npcink-abilities-toolkit/upload-media-from-url`, `npcink-abilities-toolkit/update-media-details`, and
`npcink-abilities-toolkit/set-post-featured-image`.

## Image Candidate Adoption Handoff

`npcink-toolbox/build-image-candidate-adoption-plan` is the bounded local
handoff for adopting one reviewed image candidate from stock, AI-generated,
owned, external, or manual-upload sources. It is not a Cloud image registry,
not an image generation runtime, and not a media import executor.

Core accepts it only when it declares
`artifact_type=image_candidate_adoption_plan`, carries
`candidate_contract_version=image_candidate.v1` or a selected candidate with
`contract_version=image_candidate.v1`, and contains dry-run write actions for:

- exactly one `npcink-abilities-toolkit/upload-media-from-url` action;
- exactly one `npcink-abilities-toolkit/update-media-details` action;
- at most one `npcink-abilities-toolkit/set-post-featured-image` action.

Each action must keep `dry_run=true` and `commit=false`. Core stores one
`plan_to_proposal_batch` proposal so the user can approve the reviewed import,
metadata, and optional featured-image update together. Adapter or the local
host still performs per-action allowlist, schema, idempotency, and execution
checks after Core approval and commit preflight. Core does not download the
image, upload media, set featured images, or persist provider candidate truth.

## Media Optimization Handoff

`npcink-abilities-toolkit/build-media-optimization-plan` is the bounded local plan for the user
intent "optimize these reviewed media items." It must declare
`artifact_type=media_optimization_plan`, `proposal_mode=batch`,
`batch_approval=true`, and include paired metadata and derivative actions for
each attachment in the plan.

The plan must include:

- one `npcink-abilities-toolkit/update-media-details` action for title, alt, caption,
  description, or source metadata;
- one derivative adoption action, currently
  `npcink-abilities-toolkit/adopt-cloud-media-derivative` or `npcink-abilities-toolkit/replace-media-file`;
- dry-run preview evidence for the metadata change and derivative change.

If derivative adoption will also update old inline media URLs in post content,
that repair evidence must stay inside the derivative action preview as
`content_reference_repairs`. A media optimization plan must not add a separate
`npcink-abilities-toolkit/patch-post-content`, `npcink-abilities-toolkit/update-post`, or
`npcink-abilities-toolkit/update-post-blocks` write action for the same user intent.
For review, Core turns this plan into a human-readable proposal summary that
highlights the attachment id, MIME/file replacement, reviewed derivative
filename or dimensions when present, metadata update intent, expected inline
reference repairs, one Core approval for the ordered actions, and local backup
rollback availability. The summary is review copy only; final writes and
verification still belong to Adapter and the local write abilities.

Cloud may create or return a derivative artifact, checksum, mime type, size
preview, or processing diagnostics through the local Cloud Addon path, but
final proposal, approval, adoption, and WordPress writes stay local. Core does
not optimize images, execute media writes, or approve the proposal
automatically.

## Media Adoption Enhancement Handoff

`npcink-abilities-toolkit/build-media-adoption-enhancement-plan` is the bounded
local plan for the user intent "adopt this reviewed remote image into a page."
It is the governed bridge for images selected through cloud search, cloud image
generation, or another reviewed source. It is not a search runtime, generation
runtime, media library executor, or direct page writer.

Core accepts it only when it declares
`artifact_type=media_adoption_enhancement_plan`, `proposal_mode=batch`,
`batch_approval=true`, `requires_approval=true`, `dry_run=true`,
`commit_execution=false`, and `direct_wordpress_write=false`. The write actions
must contain:

- exactly one `npcink-abilities-toolkit/upload-media-from-url` action with a
  reviewed absolute URL;
- exactly one `npcink-abilities-toolkit/optimize-media-asset` action that uses
  the upload output reference or a reviewed attachment id;
- at most one `npcink-abilities-toolkit/patch-post-content` action that replaces
  one reviewed old URL with `$outputs.optimize-media-asset.derivative_url`.

The generated batch proposal preserves `preview.media_adoption_enhancement`
with source URL, old URL, post attachment context, reference-repair evidence,
and `direct_wordpress_write=false`. Adapter or the local host still performs
per-action allowlist, schema, idempotency, output-reference resolution, and
execution checks after Core approval and commit preflight. Core does not fetch
the image, optimize files, repair post content, approve the proposal, or execute
WordPress writes.

## Media Rename Handoff

`npcink-abilities-toolkit/build-media-rename-plan` is the bounded local plan for renaming one
attachment main file after the operator has reviewed the filename. It is not a
filename policy engine and does not compute hashes inside Core.

Core accepts it only when it declares `artifact_type=media_rename_plan`, targets
exactly one `attachment_id`, and contains exactly one dry-run
`npcink-abilities-toolkit/rename-media-file` action with a non-empty `target_file_name`.
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

Permanent media deletion is stricter: `npcink-abilities-toolkit/delete-media-permanently`
actions are blocked unless the submitted `plan_input` explicitly contains
`include_delete_candidates=true`. The media planning ability still decides
whether a delete action can be emitted at all; current destructive-media plans
also require a narrow source-side flag such as
`include_unattached_nonproduction_media=true` or `include_trash_parent_media=true`.
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
  `npcink_governance_core_proposal_items_blocked` with HTTP `409`.

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
