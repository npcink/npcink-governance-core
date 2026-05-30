# Magick AI Core

Magick AI Core is the WordPress AI operation governance layer.

It does not generate content, own product workflows, route models, or replace
the WordPress Abilities API. It discovers agent-callable abilities from
WordPress and provider plugins, then adds host-side policy, proposal review,
approval, commit boundaries, and audit records.

## Scope

This plugin owns:

- ability intake from WordPress Abilities API and provider plugins;
- proposal records for AI-assisted operations;
- approval and commit governance boundaries;
- audit logs for requested, approved, rejected, and committed operations;
- scoped app-key access for external governance clients;
- minimal admin and REST surfaces for governance.

This plugin does not own:

- article generation, SEO writing, media alt generation, or comment reply UX;
- workflow runtime, queues, batch consoles, MCP runtime, or Agent Gateway task
  catalogs;
- model routing, provider keys, prompt/preset management, or cloud billing;
- reusable WordPress ability definitions, which belong in
  `magick-ai-abilities` or other provider plugins.

## Requirements

- WordPress 6.9+ with WordPress Abilities API available for full ability intake.
- PHP 7.4+.

## MVP REST Surface

MVP routes require `manage_options` or a scoped app key where documented.

- `GET /wp-json/magick-ai-core/v1/capabilities`
- `GET /wp-json/magick-ai-core/v1/apps`
- `POST /wp-json/magick-ai-core/v1/apps`
- `GET /wp-json/magick-ai-core/v1/proposals`
- `GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}`
- `POST /wp-json/magick-ai-core/v1/proposals`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/reject`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`
- `GET /wp-json/magick-ai-core/v1/audit`

The first implementation records proposals, approval/rejection decisions, and
audit events, and provides commit preflight without executing writes. Final
commit execution is intentionally not implemented until idempotency and failure
contracts are locked and covered by tests.

## Development

Read the project handoff docs before starting a new implementation session:

- [Product Positioning](docs/product-positioning.md)
- [Architecture](docs/architecture.md)
- [Governance Contract](docs/governance-contract.md)
- [REST API Contract](docs/rest-api-contract.md)
- [Database Schema](docs/database-schema.md)
- [Security Model](docs/security-model.md)
- [Ability Intake Contract](docs/ability-intake-contract.md)
- [Approval Commit Contract](docs/approval-commit-contract.md)
- [Agent MCP Entry Contract](docs/agent-mcp-entry-contract.md)
- [App Auth Scope Policy](docs/app-auth-scope-policy.md)
- [Core Governance Handoff Validation](docs/core-governance-handoff-validation.md)
- [Core 0.4 Consumer Readiness](docs/core-0.4-consumer-readiness.md)
- [Create Draft Governance Scenario](docs/create-draft-governance-scenario.md)
- [Set Post SEO Meta Governance Scenario](docs/set-post-seo-meta-governance-scenario.md)
- [Approve Comment Governance Scenario](docs/approve-comment-governance-scenario.md)
- [Development Workflow](docs/development-workflow.md)
- [Testing Strategy](docs/testing-strategy.md)
- [Next Stage Plan](docs/next-stage-plan.md)
- [Strategy And Product Split](docs/strategy-and-product-split.md)
- [ADR-001: Rebuild Core As A Governance Layer](docs/decisions/ADR-001-rebuild-core-as-governance-layer.md)
- [ADR-002: No Workflow Runtime In Core](docs/decisions/ADR-002-no-workflow-runtime-in-core.md)

External agent clients can start from the
[OpenClaw governance adapter example](examples/openclaw-governance-adapter/README.md).
That example calls Core REST governance routes only; it is not an MCP runtime
or final write executor.

Core 0.4 consumer readiness is complete for `magick-ai-abilities` 0.4.0 across
the `magick-ai/create-draft`, `magick-ai/set-post-seo-meta`, and
`magick-ai/approve-comment` representative scenarios. See
[Core 0.4 Consumer Readiness](docs/core-0.4-consumer-readiness.md). The next
stage is a decision point: only design final commit execution through a
separate ADR; otherwise Core remains the governance layer.

For local adapter setup, WordPress administrators can open
`Tools -> Magick AI Core -> External App Access` to copy the Core base URL and
create a scoped one-time app token. The same screen provides an OpenClaw
handoff guide, an optional LocalWP TLS test export line for `.local` testing,
and a key disable action for leaked or obsolete tokens. The TLS option only
changes copied client configuration; it is not a Core server setting. The token
is shown once and should be stored only in the external client's secret store or
environment.

Run the local static test suite:

```bash
composer test
```

Run PHP syntax linting:

```bash
composer lint:php
```

Run both:

```bash
composer test:all
```
