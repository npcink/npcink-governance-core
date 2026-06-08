# Npcink Governance Core

Npcink Governance Core is the Npcink AI governance layer for WordPress operations.

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
- workflow runtime, workflow/task queues, batch execution consoles, MCP
  runtime, or Agent Gateway task catalogs;
- model routing, provider keys, prompt/preset management, or cloud billing;
- reusable WordPress ability definitions, which belong in
  `npcink-abilities-toolkit` or other provider plugins.

## Requirements

- WordPress 7.0+ with WordPress Abilities API available for full ability intake.
- PHP 8.0+.

## MVP REST Surface

MVP routes require `manage_options` or a scoped app key where documented.

- `GET /wp-json/npcink-governance-core/v1/capabilities`
- `GET /wp-json/npcink-governance-core/v1/apps`
- `POST /wp-json/npcink-governance-core/v1/apps`
- `GET /wp-json/npcink-governance-core/v1/proposals`
- `GET /wp-json/npcink-governance-core/v1/proposals/{proposal_id}`
- `POST /wp-json/npcink-governance-core/v1/proposals`
- `POST /wp-json/npcink-governance-core/v1/proposals/from-plan`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/reject`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`
- `GET /wp-json/npcink-governance-core/v1/audit`

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
- [Third-Party Ability Provider Guide](docs/third-party-ability-provider-guide.md)
- [Approval Commit Contract](docs/approval-commit-contract.md)
- [Agent MCP Entry Contract](docs/agent-mcp-entry-contract.md)
- [App Auth Scope Policy](docs/app-auth-scope-policy.md)
- [Approval Policy Evaluator Standard](docs/approval-policy-evaluator-standard.md)
- [Core Governance Operability](docs/core-governance-operability.md)
- [AI Provider Log Correlation](docs/ai-provider-log-correlation.md)
- [Core Governance Handoff Validation](docs/core-governance-handoff-validation.md)
- [Current Stage Governance Reliability](docs/current-stage-governance-reliability.md)
- [Core 0.4 Consumer Readiness](docs/core-0.4-consumer-readiness.md)
- [Governed AI Feedback Loop](docs/governed-ai-feedback-loop.md)
- [Platform Baseline](docs/platform-baseline.md)
- [Admin Menu Standard](docs/admin-menu-standard.md)
- [Admin Surface Standard](docs/admin-surface-standard.md)
- [OpenClaw Execution Guidance](docs/openclaw-execution-guidance.md)
- [Operation Classification Contract](docs/operation-classification-contract.md)
- [Plan To Proposal Governance](docs/plan-to-proposal-governance.md)
- [Ability Recipe Orchestration Contract](docs/ability-recipe-orchestration-contract.md)
- [Article Writing Workflow Contract](docs/article-writing-workflow-contract.md)
- [Cloud Bulk Article Run Contract](docs/cloud-bulk-article-run-contract.md)
- [Create Draft Governance Scenario](docs/create-draft-governance-scenario.md)
- [Set Post SEO Meta Governance Scenario](docs/set-post-seo-meta-governance-scenario.md)
- [Approve Comment Governance Scenario](docs/approve-comment-governance-scenario.md)
- [Taxonomy Terms Preview Governance Scenario](docs/taxonomy-terms-preview-governance-scenario.md)
- [Development Workflow](docs/development-workflow.md)
- [Testing Strategy](docs/testing-strategy.md)
- [WordPress.org Release Gate](docs/wordpress-org-release-gate.md)
- [Next Stage Plan](docs/next-stage-plan.md)
- [Strategy And Product Split](docs/strategy-and-product-split.md)
- [Content Metadata Delta Implementation Prompt](docs/content-metadata-delta-implementation-prompt.md)
- [ADR-001: Rebuild Core As A Governance Layer](docs/decisions/ADR-001-rebuild-core-as-governance-layer.md)
- [ADR-002: No Workflow Runtime In Core](docs/decisions/ADR-002-no-workflow-runtime-in-core.md)
- [ADR-003: Keep Final Execution Outside Core For The Current Stage](docs/decisions/ADR-003-keep-final-execution-outside-core.md)
- [ADR-004: Suite Consolidation And Local Admin Consent](docs/decisions/ADR-004-suite-consolidation-and-local-admin-consent.md)
- [ADR-005: Keep Core Independent And Standardize Channel Adapters](docs/decisions/ADR-005-keep-core-independent-and-standardize-channel-adapters.md)

External agent clients can start from the
[OpenClaw governance adapter example](examples/openclaw-governance-adapter/README.md).
That example calls Core REST governance routes only; it is not an MCP runtime
or final write executor.

Third-party ability providers can integrate without adopting
`npcink-abilities-toolkit`: register real WordPress Abilities API ids with
schemas, permission callbacks, risk metadata, and dry-run previews, then submit
write or destructive operations to Core as proposals. See
[Third-Party Ability Provider Guide](docs/third-party-ability-provider-guide.md).

Core 0.4 consumer readiness is complete for the reference
`npcink-abilities-toolkit` 0.4.0 provider across
the `npcink-abilities-toolkit/create-draft`,
`npcink-abilities-toolkit/set-post-seo-meta`, and
`npcink-abilities-toolkit/approve-comment` representative scenarios. See
[Core 0.4 Consumer Readiness](docs/core-0.4-consumer-readiness.md). ADR-003
keeps final WordPress execution outside Core for the current stage; Core
hardens approval context and commit preflight while Adapter/product plugins
execute approved abilities through WordPress Abilities API.

ADR-004 allows the product to move toward one Npcink AI plugin or suite entry
when that improves installation and operator experience. That consolidation
does not collapse authority boundaries: the Governance module remains the
proposal, approval, preflight, and audit truth, while Adapter and Toolbox
modules can serve external channels and local WordPress admin product UX.
Low-risk, single-object, fully previewed actions taken by a present WordPress
administrator may use local admin consent with audit instead of Core proposal
approval. External, automated, batch, destructive, high-impact, or
insufficiently previewed AI writes must still go through Core proposal review.
Core exposes only an audit hook for this path:
`npcink_governance_core_record_local_admin_consent` records bounded
`local_admin_consent.*` events without creating proposals, approving proposals,
preflighting commits, or executing abilities.
ADR-005 makes the current implementation sequence explicit: do not merge Core
into today's OpenClaw Adapter yet. Keep Core independent, treat OpenClaw
Adapter as the first channel adapter, standardize the shared channel adapter
contract, and use the Operation Classification Contract before moving Toolbox
or adapter flows to local admin consent or Core proposal paths.

The taxonomy terms preview extension proves the same boundary for
`npcink-abilities-toolkit/propose-post-taxonomy-terms` -> `npcink-abilities-toolkit/set-post-terms`: adapters
run the read helper through WordPress Abilities API, then submit the generated
dry-run write proposal to Core for approval and commit preflight.

The plan-to-proposal bridge extends that pattern to allowlisted read-only planning
abilities such as `npcink-abilities-toolkit/build-content-inventory-fix-plan`,
`npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`, and
`npcink-abilities-toolkit/build-media-inventory-fix-plan`,
`npcink-abilities-toolkit/build-media-reference-repair-plan`, and
`npcink-abilities-toolkit/build-media-settings-reference-repair-plan`. It also accepts
`npcink-abilities-toolkit/build-media-optimization-plan` only as one attachment-level media
optimization batch, `npcink-abilities-toolkit/build-media-rename-plan` only as one reviewed
attachment rename proposal, and accepts the P0 Toolbox article handoff
`npcink-toolbox/build-article-write-plan` only as a single reviewed
`npcink-abilities-toolkit/create-draft` proposal. The bounded local article batch handoff
`npcink-toolbox/build-article-batch-write-plan` may create one reviewed
batch of 2 to 5 draft-only `npcink-abilities-toolkit/create-draft` actions. Core accepts the
media-enabled local article batch handoff
`npcink-toolbox/build-article-media-batch-write-plan` only as one
reviewed `article_media_batch_write_plan` with draft creation, media upload,
media metadata, and featured-image actions grouped in a Core batch proposal.
plan output, validates each target ability, stores either one pending proposal
per `write_action` or one `plan_to_proposal_batch` proposal when the plan
explicitly requests batch approval, preserves `preview.before`,
`preview.after_suggestion`, `dry_run=true`, `commit=false`, and article
workflow artifacts where applicable, and keeps final mutation execution outside
Core. See
[Plan To Proposal Governance](docs/plan-to-proposal-governance.md).

Core documentation may use Review Queue, pending proposal queue, bounded bulk
rejection, and `plan_to_proposal_batch` for governance review records. Those terms do not permit workflow/task queue ownership, batch execution, retries,
leases, schedulers, or operator runtime consoles inside Core.

Article writing is now treated as local Ability recipe orchestration, not a
Cloud writing product. The [Ability Recipe Orchestration Contract](docs/ability-recipe-orchestration-contract.md)
keeps article drafting as a local `article_draft_v1` recipe over standard
Abilities and Core-governed `write_actions`. The bounded
`article_batch_draft_v1` profile may group 2 to 5 locally reviewed draft-only
actions into one Core batch proposal, but it remains local and does not add
Cloud writing, a queue, automatic approval, or Core execution. The
[Cloud Bulk Article Run Contract](docs/cloud-bulk-article-run-contract.md) is a
prohibited/deprecated planning contract: Cloud must not generate article drafts,
SEO copy, bulk article runs, `article_write_plan` candidates, or
`article_batch_write_plan` candidates.
The accepted product surface is a local Article Assistant Workbench: one
article or bounded reviewed draft batch, reviewed artifacts, and
Core-governed draft proposals, not an article generation product or Cloud
writing feature.

The current governance operability baseline adds proposal audit timelines,
audit filters for ability/app/key/caller/correlation, app scope-decision
attribution, and commit-preflight correlation ids. See
[Core Governance Operability](docs/core-governance-operability.md). This makes
the Core loop easier to review and debug without adding ability execution or a
workflow runtime.

Toolbox stores local media derivative defaults such as output format, max
width, quality, watermark placement, and whether Cloud execution should be used
when available. Core does not own those product settings. It governs the
reviewed media optimization plan that returns from Toolbox/Adapter, while Cloud
Addon may sign and dispatch the request and Cloud may process the derivative
artifact. Final proposal, approval, adoption, and WordPress writes stay local.
When a local media optimization plan combines SEO metadata with derivative
adoption, Core governs it as one `plan_to_proposal_batch` for a single
attachment so the user approves the optimization intent once while Adapter
still validates and executes each write action outside Core. Inline post-content
media reference repair is not a separate batch action in that flow; it belongs
to the local derivative adoption ability's dry-run/commit contract and may be
shown as `content_reference_repairs` evidence in the derivative preview.

The approval policy evaluator defaults to `manual`, records
`proposal.policy_evaluated` for every created proposal, and supports two
development-only guarded modes. `dry_run_guarded` records cleanup candidates
without approval. `local_guarded` can auto-approve only trusted
`build-nonproduction-content-cleanup-plan` trash-post batches when explicit
authorization, test-content evidence, quotas, and audit all pass. See
[Approval Policy Evaluator Standard](docs/approval-policy-evaluator-standard.md).

Real AI provider request logs remain owned by the WordPress `ai` plugin.
Magick AI Adapter should carry Core `proposal_id` and commit-preflight
`correlation_id` into provider request log context so operators can correlate
Core governance audit with AI Request Logs. See
[AI Provider Log Correlation](docs/ai-provider-log-correlation.md). Core does
not store prompts, responses, token metrics, provider credentials, or provider
request logs.

For Core governance credentials, WordPress administrators can open the
collapsed `Advanced Access` entry from `Npcink AI -> Core` to create a scoped
one-time app token and disable leaked or obsolete keys. This screen is a
governance fallback, not an OpenClaw onboarding surface. Productized OpenClaw
setup, local TLS client configuration, agent rules, and handoff instructions
belong in Magick AI Adapter. Core only issues governance app keys and records
approvals, preflight, rate limits, and audit attribution. The token is shown
once and should be stored only in a trusted Adapter or internal governance
client secret store.

Productized OpenClaw acceptance should be run from Magick AI Adapter's
`docs/openclaw-consumer-acceptance.md`. Core participates as the governance
authority behind Adapter; OpenClaw should not use Core as its primary product
connection.

Run the local static test suite:

```bash
composer test
```

Run the static contract gate only:

```bash
composer test:contracts
```

Run fail-closed fault injection:

```bash
composer test:fail-closed
```

Run PHP syntax linting:

```bash
composer lint:php
```

Run both:

```bash
composer test:all
```
