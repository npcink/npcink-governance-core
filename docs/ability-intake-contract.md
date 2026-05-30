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
