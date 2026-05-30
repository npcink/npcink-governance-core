# Core Governance Handoff Validation

Status: active MVP validation target.

This document turns the `magick-ai-abilities` handoff guide into concrete Core
verification rules.

## Runtime ID Rule

Core proposal records must store real ability ids discovered from ability
intake. Documentation and planning labels such as `content/draft-preview`,
`seo/metadata-preview`, or `comment/moderation-preview` must not be accepted as
runtime proposal targets.

`POST /proposals` validates the target ability before persistence. Commit
preflight validates it again after approval so approved proposals fail closed if
their target ability disappears.

## Smoke-Covered Surfaces

The WordPress smoke test validates these ready handoff surfaces:

| Surface | Read/intake role | Proposal target |
| --- | --- | --- |
| Site context | `magick-ai/site-info` and diagnostics abilities are discoverable read context. | No proposal is required for read-only intake. |
| Draft creation | Provider supplies dry-run preview or caller intent. | `magick-ai/create-draft` |
| SEO metadata | Planning/read helpers can prepare metadata context. | `magick-ai/set-post-seo-meta` |
| Comment moderation | Handoff/suggestion helpers provide read-side context. | `magick-ai/approve-comment` |

Each proposal target must be write-like, require approval, support approval and
rejection, and pass commit preflight without executing the ability.

## Primary Scenario

The first practical consumer-side loop is `magick-ai/create-draft`. Core must
discover the real ability id and schema from `magick-ai-abilities`, create a
proposal with dry-run input, let an administrator approve or reject it, and
return approval context from commit preflight with `commit_execution=false`.

The dedicated scenario is documented in
[Create Draft Governance Scenario](create-draft-governance-scenario.md). The
OpenClaw example adapter exposes `create-draft-proposal` for this path, but it
still does not approve proposals or execute the final write.

## Non-Goals

This validation does not add workflow runtime ownership, natural language
routing, model/provider selection, prompt or preset ownership, final WordPress
write execution, or runtime alias mapping.
