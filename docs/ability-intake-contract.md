# Ability Intake Contract

Status: MVP contract.

Magick AI Core consumes abilities. It does not define product abilities.

## Discovery Order

1. Prefer `magick_ai_abilities_get_registered()` when the
   `magick-ai-abilities` package is active.
2. Fall back to WordPress Abilities API discovery with `wp_get_abilities()` when
   available.
3. Return an empty list with a diagnostic status when no ability source is
   available.

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

## Runtime Boundary

Ability intake is read-only. It must not:

- execute abilities;
- register fallback abilities;
- project Agent Gateway tools;
- infer workflow ownership;
- approve or commit writes.

## Plan-To-Proposal Bridge Inputs

Core may accept output from these read-only planning abilities:

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

They must remain discoverable as `governance_mode=direct_read` with
`execution_surface=wp_abilities_rest`. Core does not execute them. A host or
adapter runs the plan through WordPress Abilities API and submits the resulting
plan payload to Core.

`magick-ai-toolbox/build-article-write-plan` is the P0 AI-assisted writing
handoff owned by Toolbox. Core accepts it only as a reviewed
`article_write_plan` that can create one `magick-ai/create-draft` proposal; it
does not move article generation, workflow state, or operator UX into Core.

`magick-ai-toolbox/build-article-batch-write-plan` is the bounded local batch
draft handoff owned by Toolbox. Core accepts it only as a reviewed
`article_batch_write_plan` with explicit batch approval and 2 to 5 draft-only
`magick-ai/create-draft` actions. It does not move article generation, batch
writing jobs, workflow state, or Cloud writing into Core.

`magick-ai-toolbox/build-article-media-batch-write-plan` is the media-enabled
local article batch handoff owned by Toolbox. Core accepts it only as a
reviewed `article_media_batch_write_plan` with explicit batch approval,
preserved image-source candidate evidence, and allowlisted draft/media write
actions. It does not move image search, media import, featured-image setting,
article generation, workflow state, or Cloud writing into Core.

`magick-ai-toolbox/build-image-candidate-adoption-plan` is the single reviewed
image candidate adoption handoff owned by Toolbox. Core accepts it only as an
`image_candidate_adoption_plan` carrying a normalized `image_candidate.v1`
candidate and dry-run actions for `magick-ai/upload-media-from-url`,
`magick-ai/update-media-details`, and optional
`magick-ai/set-post-featured-image`. It does not move stock search, AI image
generation, media import, featured-image setting, workflow state, or Cloud
writing into Core.

`magick-ai/build-media-optimization-plan` is the bounded local media
optimization handoff owned by `magick-ai-abilities` or a local product plugin.
Core accepts it only as an explicit batch plan for one attachment, combining
metadata updates with derivative adoption while leaving Cloud processing and
final WordPress writes outside Core.

`magick-ai/build-media-rename-plan` is the bounded local media rename handoff
owned by `magick-ai-abilities` or a local product plugin. Core accepts it only
as one reviewed `media_rename_plan` for one attachment and one
`magick-ai/rename-media-file` action. Filename policy stays outside Core; Core
only governs the reviewed target filename before Adapter/host execution.

Each plan `write_action.target_ability_id` must resolve through normal ability
intake as a proposal-governed write or destructive ability. Core must not
accept short labels, stale tool names, or adapter-local aliases as proposal
targets.

## Core Governance Handoff

Core treats
`/Users/muze/gitee/magick-ai-abilities/docs/core-governance-handoff-guide.md`
as the documentation-only handoff guide for first-party abilities that are
ready for governance proposals.

Proposal, approval, preflight, and audit records must use real WordPress
Abilities API ids, such as `magick-ai/site-info` or `magick-ai/create-draft`.
Planning labels such as `site/read`, `content/draft-preview`, and
`comment/moderation-preview` are documentation labels only. Core must not add a
runtime short-name mapping layer for them.

The handoff guide may identify deferred operation surfaces, such as CDN purge
preview or site-level backup restore preflight. Deferred surfaces are not Core
features; they require a provider or product plugin ability contract before Core
can govern them.

## Shared Replay Truth

Consumer-side workflow checks should prefer
`magick_ai_abilities_get_workflow_definitions()` when the installed
`magick-ai-abilities` package exposes it. Older local development profiles may
fall back to the shared replay fixture at
`magick-ai-abilities/tests/fixtures/agent-workflow-replay.json`.

Core uses that fixture to verify its current responsibility:

- preferred workflow bundle abilities are discoverable through `/capabilities`;
- preferred bundle rows remain read-risk and do not require approval;
- write/destructive abilities listed as disallowed defaults remain available
  only as proposal/approval handoff targets;
- Core does not copy the fixture into a workflow runtime or route natural
  language tasks by itself.

Set `MAGICK_AI_ABILITIES_PATH=/path/to/magick-ai-abilities` when the sibling
repository is not located next to `magick-ai-core`.

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

The first solidified consumer scenario is `magick-ai/create-draft`; see
[Create Draft Governance Scenario](create-draft-governance-scenario.md). Core
must continue to discover that ability and its schema through intake instead of
copying definitions from `magick-ai-abilities`.

The second solidified consumer scenario is `magick-ai/set-post-seo-meta`; see
[Set Post SEO Meta Governance Scenario](set-post-seo-meta-governance-scenario.md).
Core must treat it as an existing-resource field update proposal and still
discover the schema through intake.

The third solidified consumer scenario is `magick-ai/approve-comment`; see
[Approve Comment Governance Scenario](approve-comment-governance-scenario.md).
Core must treat it as a non-post comment moderation proposal and still discover
the schema through intake.

The taxonomy terms preview scenario consumes
`magick-ai/propose-post-taxonomy-terms` as a direct-read helper and
`magick-ai/set-post-terms` as the governed write target; see
[Taxonomy Terms Preview Governance Scenario](taxonomy-terms-preview-governance-scenario.md).
Core must not execute the helper or assign terms. It only governs the generated
dry-run `set-post-terms` proposal, approval, preflight, and audit lifecycle.

The plan-to-proposal scenario follows the same consumer rule at larger plan
granularity. See [Plan To Proposal Governance](plan-to-proposal-governance.md).
