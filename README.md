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

All MVP routes require `manage_options`.

- `GET /wp-json/magick-ai-core/v1/capabilities`
- `GET /wp-json/magick-ai-core/v1/proposals`
- `POST /wp-json/magick-ai-core/v1/proposals`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/reject`
- `GET /wp-json/magick-ai-core/v1/audit`

The first implementation records proposals, approval/rejection decisions, and
audit events. Final commit execution is intentionally not implemented until the
approval-commit contract is locked and covered by tests.

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
- [Development Workflow](docs/development-workflow.md)
- [Testing Strategy](docs/testing-strategy.md)
- [Next Stage Plan](docs/next-stage-plan.md)
- [ADR-001: Rebuild Core As A Governance Layer](docs/decisions/ADR-001-rebuild-core-as-governance-layer.md)
- [ADR-002: No Workflow Runtime In Core](docs/decisions/ADR-002-no-workflow-runtime-in-core.md)

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
