# Core 0.4 Consumer Readiness

Status: complete for the `magick-ai-abilities` 0.4.0 handoff.

This document summarizes the consumer-side governance readiness now proven in
Core. It is the roll-up entry point for future humans and AI agents that need
to understand what the 0.4 representative scenarios validated.

## Dependency Version

- `magick-ai-abilities`: 0.4.0

## Verified Representative Scenarios

| Scenario | Ability | Commit | Coverage |
| --- | --- | --- | --- |
| create-draft | `magick-ai/create-draft` | `3d94af7` | New post draft proposal governed by Core before any host write. |
| set-post-seo-meta | `magick-ai/set-post-seo-meta` | `2c28a27` | Field-level update proposal for an existing post resource. |
| approve-comment | `magick-ai/approve-comment` | `0f44ee0` | Comment moderation proposal for a non-post resource. |

## Unified Governance Chain

All three scenarios use the same governance chain:

`capabilities discovery -> proposal -> approve/reject -> commit-preflight -> audit`

Core discovers real ability ids and schemas, stores a proposal, records the
human approval or rejection decision, returns approval context from commit
preflight, and writes audit events for the governance decisions.

## Unified Boundary

- `commit_execution=false` remains the contract for commit preflight.
- Core does not execute final WordPress mutation.
- Core does not own article, SEO, comment, media, workflow, MCP runtime,
  provider, prompt, or product UX execution.
- Adapters and host plugins may use the approval context, but final execution is
  outside Core until a separate design decision is accepted.

## Verified Commands

The 0.4 readiness loop is backed by these verification gates:

- `composer test:all`
- `composer smoke:wp`
- `php examples/openclaw-governance-adapter/openclaw-governance-adapter.php --help`

## Ability Contract Conclusion

No current finding requires `magick-ai-abilities` to add or change schema,
metadata, or ability contract fields for the three representative 0.4
scenarios.

If a future consumer finds a missing field, the fix should be proposed in
`magick-ai-abilities`; Core should not patch around missing ability contracts
with aliases, local schema fallbacks, or product-specific defaults.

## Next Stage Candidate

Final commit execution should only be discussed after a separate ADR proposes
the contract. That ADR would need to cover authorization, idempotency, failure
semantics, audit attribution, adapter responsibility, and rollback boundaries.

Until such an ADR is accepted, Core remains the governance layer: discovery,
proposal, approve/reject, commit preflight, and audit.
