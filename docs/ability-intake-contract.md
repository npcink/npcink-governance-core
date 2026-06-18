# Ability Intake Contract

Status: MVP contract.

Npcink Governance Core consumes abilities. It does not define product abilities.

## Discovery Order

1. Prefer `npcink_abilities_toolkit_get_registered()` when the
   `npcink-abilities-toolkit` reference package is active.
2. Fall back to WordPress Abilities API discovery with `wp_get_abilities()` when
   available.
3. Return an empty list with a diagnostic status when no ability source is
   available.

The `npcink-abilities-toolkit` package is the reference provider and smoke-test
baseline, not the only valid source. Core's base intake can normalize any
currently discoverable WordPress Abilities API row from a provider plugin. See
[Third-Party Ability Provider Guide](third-party-ability-provider-guide.md).

## Normalized Capability Row

Core normalizes each ability to:

- `ability_id`
- `label`
- `description`
- `risk_level`
- `requires_approval`
- `input_schema`
- `output_schema`
- `source`
- `raw`

Read abilities may also expose sensitive read authorization guidance:

- `read_authorization_required`
- `requires_read_authorization`
- `read_policy=core_read_authorization_required`
- `governance_mode=core_read_authorization_required`
- `authorization_mode=core_read_request`
- `read_authorization.required=true`
- `read_authorization_request_route`
- `read_authorization_preflight_route`
- `read_authorization_status_route`

When any of those fields require Core authorization, an adapter must fail
closed until it receives a bounded Core `read_authorization_context` for the
same `ability_id` and input hash. Capability output may include declared read
bounds such as `max_rows`, `tail_lines`, `allowed_fields`, and
`denied_fields`; Core read grants may tighten but must not widen those bounds.

## Runtime Boundary

Ability intake is read-only. It must not:

- execute abilities;
- register fallback abilities;
- project Agent Gateway tools;
- infer workflow ownership;
- approve or commit writes.
- grant sensitive reads without a Core read request approval.

## Plan-To-Proposal Bridge Inputs

Core may accept output from these explicitly allowlisted read-only planning
abilities:

- `npcink-abilities-toolkit/build-content-inventory-fix-plan`
- `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`
- `npcink-abilities-toolkit/build-media-inventory-fix-plan`
- `npcink-abilities-toolkit/build-media-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-settings-reference-repair-plan`
- `npcink-abilities-toolkit/build-media-optimization-plan`
- `npcink-abilities-toolkit/build-media-adoption-enhancement-plan`
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/build-pattern-page-plan`
- `npcink-abilities-toolkit/build-article-block-plan`
- `npcink-abilities-toolkit/build-block-theme-site-plan`
- `npcink-toolbox/build-article-write-plan`
- `npcink-toolbox/build-article-batch-write-plan`
- `npcink-toolbox/build-article-media-batch-write-plan`
- `npcink-toolbox/build-image-candidate-adoption-plan`
- `npcink-toolbox/build-site-knowledge-review-plan`
- `npcink-toolbox/build-nightly-inspection-review-plan`
- `npcink-toolbox/build-content-metadata-apply-plan`

They must remain discoverable as `governance_mode=direct_read` with
`execution_surface=wp_abilities_rest`. Core does not execute them. A host or
adapter runs the plan through WordPress Abilities API and submits the resulting
plan payload to Core.

This bridge is intentionally narrower than ordinary proposal creation. It is
not a generic third-party workflow runtime. Providers outside this list should
submit direct single-action proposals until Core documents their planning
ability id, `plan_type`, bounds, allowed target abilities, and dry-run evidence
requirements.

`npcink-toolbox/build-article-write-plan` is the P0 AI-assisted writing
handoff owned by Toolbox. Core accepts it only as a reviewed
`article_write_plan` that can create one `npcink-abilities-toolkit/create-draft` proposal; it
does not move article generation, workflow state, or operator UX into Core.

`npcink-toolbox/build-article-batch-write-plan` is the bounded local batch
draft handoff owned by Toolbox. Core accepts it only as a reviewed
`article_batch_write_plan` with explicit batch approval and 2 to 5 draft-only
`npcink-abilities-toolkit/create-draft` actions. It does not move article generation, batch
writing jobs, workflow state, or Cloud writing into Core.

`npcink-toolbox/build-article-media-batch-write-plan` is the media-enabled
local article batch handoff owned by Toolbox. Core accepts it only as a
reviewed `article_media_batch_write_plan` with explicit batch approval,
preserved image-source candidate evidence, and allowlisted draft/media write
actions. It does not move image search, media import, featured-image setting,
article generation, workflow state, or Cloud writing into Core.

`npcink-toolbox/build-image-candidate-adoption-plan` is the single reviewed
image candidate adoption handoff owned by Toolbox. Core accepts it only as an
`image_candidate_adoption_plan` carrying a normalized `image_candidate.v1`
candidate and dry-run actions for `npcink-abilities-toolkit/upload-media-from-url`,
`npcink-abilities-toolkit/update-media-details`, and optional
`npcink-abilities-toolkit/set-post-featured-image`. It does not move stock search, AI image
generation, media import, featured-image setting, workflow state, or Cloud
writing into Core.

`npcink-toolbox/build-site-knowledge-review-plan` is the review-only Site
Knowledge agent handoff owned by Toolbox. Core accepts it only as a
`site_knowledge_review_plan` with preserved evidence refs and one blocked
`npcink-abilities-toolkit/create-draft` review action that requires human
`title` and `content` input. It does not move Cloud Site Knowledge into a
write owner, article generator, approval store, or preflight bypass.

`npcink-toolbox/build-nightly-inspection-review-plan` is the Morning Brief
review-item handoff owned by Toolbox after a user selects one or more review
items from Nightly Inspection output. Core accepts it only as a
`nightly_site_inspection_review_plan` that preserves Cloud evidence refs and
the selected Morning Brief review item, creates one blocked
`npcink-abilities-toolkit/create-draft` review proposal, and requires human
`title` and `content` input before commit preflight can pass. It does not move
Nightly Inspection, Morning Brief organization, Cloud scheduling, retry state,
article generation, approval, or WordPress writes into Core.

`npcink-toolbox/build-content-metadata-apply-plan` is the reviewed content
metadata handoff owned by Toolbox. Core accepts it only as a
`content_metadata_apply_plan` for one post, grouping accepted excerpt and
existing category/tag choices into a batch proposal with at most one action per
metadata slot. It does not move excerpt generation, taxonomy recommendation,
new-term governance, feedback storage, or WordPress write execution into Core
or Toolbox. If the handoff includes operation classification evidence, it must
classify the Core-submitted handoff as `core_proposal_required`; local admin
consent classifications belong to product-side present-admin flows and are not
accepted through Core plan intake.

`npcink-abilities-toolkit/build-media-optimization-plan` is the bounded local media
optimization handoff owned by `npcink-abilities-toolkit` or a local product plugin.
Core accepts it only as an explicit batch plan where every attachment has a
paired metadata update and derivative adoption action while leaving Cloud
processing and final WordPress writes outside Core.

`npcink-abilities-toolkit/build-media-adoption-enhancement-plan` is the bounded
local handoff for adopting a reviewed remote image into a page. Core accepts it
only as a `media_adoption_enhancement_plan` batch with one
`npcink-abilities-toolkit/upload-media-from-url` action, one
`npcink-abilities-toolkit/optimize-media-asset` action, and at most one
`npcink-abilities-toolkit/patch-post-content` reference repair action that uses
the optimized derivative URL output reference. Search, image generation, media
download, optimization, and final WordPress writes stay outside Core.

`npcink-abilities-toolkit/build-media-rename-plan` is the bounded local media rename handoff
owned by `npcink-abilities-toolkit` or a local product plugin. Core accepts it only
as one reviewed `media_rename_plan` for one attachment and one
`npcink-abilities-toolkit/rename-media-file` action. Filename policy stays outside Core; Core
only governs the reviewed target filename before Adapter/host execution.

`npcink-abilities-toolkit/build-pattern-page-plan` is the bounded local
Gutenberg pattern page handoff. Core accepts it only as a
`pattern_page_plan` with `proposal_mode=batch`, an allowlisted pattern and
style preset, one draft page create action, one block replacement action, and
block CSS classes limited to the plan `allowed_classes` list.

`npcink-abilities-toolkit/build-article-block-plan` is the bounded local
Gutenberg article block handoff. Core accepts it only as an
`article_block_plan` with `proposal_mode=batch`, an allowlisted editorial
template, `responsive_profile=article_standard`, one draft post create action,
one block replacement action, native editorial/responsive quality evidence, and
no custom block CSS classes.

`npcink-abilities-toolkit/build-block-theme-site-plan` is the bounded local
block theme handoff. Core accepts it only as a `block_theme_site_plan` with
`proposal_mode=batch`, `intent=add_breadcrumbs` or
`intent=customize_template_layout`, `direct_wordpress_write=false`, and template
write actions limited to
`npcink-abilities-toolkit/update-template-blocks` or
`npcink-abilities-toolkit/upsert-template-blocks`. File-backed theme templates
must become reviewed `wp_template` Site Editor overrides; Core does not edit
theme files, navigation entities, global styles, or execute WordPress writes.
For `customize_template_layout`, Core additionally requires a passing
`template_layout_contract` with a bounded profile such as `article_standard`,
`page_standard`, or `homepage_landing`. The layout contract must declare
`compiler_version=block_theme_profile_compiler@0.3`,
`forbidden_policy_version=block_theme_safe_core_blocks@0.2`, and accepted
versioned profiles such as `article_standard@0.4`, `page_standard@0.2`, and
`homepage_landing@0.3`;
each profile row must carry the matching profile version, operation, modules,
and forbidden-output policy.
Template upserts are limited to
`front-page`, `home`, `index`, `page`, or `single`; reviewed block trees must
use safe core blocks, declare parser roundtrip validation, stay within bounded
block count/depth/attribute-size limits, and avoid scriptable or embedded raw
HTML. The `homepage_landing` profile may use safe dynamic core reader blocks
such as `core/latest-posts` and `core/categories` for latest post and category
entry sections. The `article_standard` profile may use safe dynamic template
blocks such as `core/post-terms`, `core/post-navigation-link`, and
`core/comments` for categories/tags, previous-next navigation, and comments.
Navigation, global styles, theme files, `theme.json`, custom
HTML/freeform, shortcode, embed, and unknown block changes are rejected before
proposal creation.

Each plan `write_action.target_ability_id` must resolve through normal ability
intake as a proposal-governed write or destructive ability. Core must not
accept short labels, stale tool names, or adapter-local aliases as proposal
targets.

## Core Governance Handoff

Core treats
`/Users/muze/gitee/npcink-abilities-toolkit/docs/core-governance-handoff-guide.md`
as the documentation-only handoff guide for first-party abilities that are
ready for governance proposals.

Proposal, approval, preflight, and audit records must use real WordPress
Abilities API ids, such as `npcink-abilities-toolkit/site-info` or `npcink-abilities-toolkit/create-draft`.
Planning labels such as `site/read`, `content/draft-preview`, and
`comment/moderation-preview` are documentation labels only. Core must not add a
runtime short-name mapping layer for them.

The handoff guide may identify deferred operation surfaces, such as CDN purge
preview or site-level backup restore preflight. Deferred surfaces are not Core
features; they require a provider or product plugin ability contract before Core
can govern them.

## Shared Replay Truth

Consumer-side workflow checks should prefer
`npcink_abilities_toolkit_get_workflow_definitions()` when the installed
`npcink-abilities-toolkit` package exposes it. Older local development profiles may
fall back to the shared replay fixture at
`npcink-abilities-toolkit/tests/fixtures/agent-workflow-replay.json`.

Core uses that fixture to verify its current responsibility:

- preferred workflow bundle abilities are discoverable through `/capabilities`;
- preferred bundle rows remain read-risk and do not require approval;
- write/destructive abilities listed as disallowed defaults remain available
  only as proposal/approval handoff targets;
- Core does not copy the fixture into a workflow runtime or route natural
  language tasks by itself.

Set `NPCINK_ABILITIES_TOOLKIT_PATH=/path/to/npcink-abilities-toolkit` when the sibling
repository is not located next to `npcink-governance-core`.

## Missing Dependencies

When no ability source exists, Core should report:

- `source`: `none`
- `available`: `false`
- `message`: human-readable missing dependency text

This is not a fatal plugin activation condition. Core can still list governance
state and audit missing-provider diagnostics while ability providers are
installed later.

Proposal creation is stricter: it must use a real, currently discoverable
`ability_id`. Core must reject proposal creation when the target ability is not
available, because Agent/MCP entry must not let planning labels or stale channel
tool names become governance records.

The first solidified consumer scenario is `npcink-abilities-toolkit/create-draft`; see
[Create Draft Governance Scenario](create-draft-governance-scenario.md). Core
must continue to discover that ability and its schema through intake instead of
copying definitions from `npcink-abilities-toolkit`.

The second solidified consumer scenario is `npcink-abilities-toolkit/set-post-seo-meta`; see
[Set Post SEO Meta Governance Scenario](set-post-seo-meta-governance-scenario.md).
Core must treat it as an existing-resource field update proposal and still
discover the schema through intake.

The third solidified consumer scenario is `npcink-abilities-toolkit/approve-comment`; see
[Approve Comment Governance Scenario](approve-comment-governance-scenario.md).
Core must treat it as a non-post comment moderation proposal and still discover
the schema through intake.

The taxonomy terms preview scenario consumes
`npcink-abilities-toolkit/propose-post-taxonomy-terms` as a direct-read helper and
`npcink-abilities-toolkit/set-post-terms` as the governed write target; see
[Taxonomy Terms Preview Governance Scenario](taxonomy-terms-preview-governance-scenario.md).
Core must not execute the helper or assign terms. It only governs the generated
dry-run `set-post-terms` proposal, approval, preflight, and audit lifecycle.

The plan-to-proposal scenario follows the same consumer rule at larger plan
granularity. See [Plan To Proposal Governance](plan-to-proposal-governance.md).
