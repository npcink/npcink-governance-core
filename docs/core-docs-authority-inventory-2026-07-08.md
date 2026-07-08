# Core Docs Authority Inventory - 2026-07-08

Status: active cleanup map.

This inventory narrows `npcink-governance-core/docs` back to Core's governance
authority. Cross-project platform coordination now starts from
`/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`.

Core documents are not being deleted in this pass. Historical records and
consumer notes remain useful, but they must not be treated as the platform
coordination source of truth.

## Classification

| Class | Meaning | Current action |
| --- | --- | --- |
| `core_truth` | Authoritative for Core governance behavior, REST, storage, policy, approval, preflight, audit, or Core security. | Keep in Core and keep indexed as governance truth. |
| `core_consumer_note` | Explains how other projects consume Core without moving their product/runtime ownership into Core. | Keep in Core, but phrase as consumer guidance. |
| `platform_coordination_pointer` | Broader platform, product placement, cross-repo process, shared menu, reference learning, or release coordination. | Keep only as a pointer or historical context; platform coordination authority lives in Toolbox. |
| `historical` | Closeout, release, translation, trial, or prior-stage evidence. | Keep for audit trail; do not use as the first current-contract entry. |

## Keep As Core Truth

These remain authoritative in `npcink-governance-core`:

- `docs/governance-contract.md`
- `docs/rest-api-contract.md`
- `docs/database-schema.md`
- `docs/security-model.md`
- `docs/ability-intake-contract.md`
- `docs/sensitive-read-authorization.md`
- `docs/app-auth-scope-policy.md`
- `docs/approval-commit-contract.md`
- `docs/approval-policy-evaluator-standard.md`
- `docs/operation-classification-contract.md`
- `docs/plan-to-proposal-governance.md`
- `docs/core-governance-operability.md`
- `docs/core-governance-handoff-validation.md`
- `docs/current-stage-governance-reliability.md`
- `docs/testing-strategy.md`
- `docs/decisions/ADR-001-rebuild-core-as-governance-layer.md`
- `docs/decisions/ADR-002-no-workflow-runtime-in-core.md`
- `docs/decisions/ADR-003-keep-final-execution-outside-core.md`
- `docs/decisions/ADR-005-keep-core-independent-and-standardize-channel-adapters.md`
- `docs/decisions/ADR-006-unattended-batch-automation-runtime-boundary.md`
- `docs/decisions/ADR-007-dedicated-local-automation-runtime-owner.md`

ADR-004 remains a Core packaging and local-admin-consent boundary record, but
suite-level product placement should be checked against the Toolbox platform
index before new work starts.

## Keep As Core Consumer Notes

These stay in Core because they describe how callers should consume Core:

- `docs/third-party-ability-provider-guide.md`
- `docs/agent-mcp-entry-contract.md`
- `docs/adapter-handoff-and-approval-policy-acceptance.md`
- `docs/openclaw-execution-guidance.md`
- `docs/external-owner-boundary-notes.md`
- `docs/core-0.4-consumer-readiness.md`
- `docs/core-contract-reuse-readiness-2026-07-08.md`
- governance scenarios such as `docs/create-draft-governance-scenario.md`,
  `docs/set-post-seo-meta-governance-scenario.md`,
  `docs/approve-comment-governance-scenario.md`, and
  `docs/taxonomy-terms-preview-governance-scenario.md`

These documents may mention Toolbox, Adapter, Toolkit, Cloud Addon, or Cloud,
but only to explain how those owners hand off to Core governance.

## Downgrade To Platform Coordination Pointers

These are not Core governance truth. They remain readable, but future sessions
should treat them as context and start from the Toolbox platform index for
cross-repo rules:

- `docs/platform-baseline.md`
- `docs/admin-menu-standard.md`
- `docs/reference-plugin-action-plan.md`
- `docs/reference-plugin-benchmark.md`
- `docs/reference-plugin-deep-dive-2026-07-06.md`
- `docs/strategy-and-product-split.md`
- `docs/cross-repo-release-acceptance.md`
- `docs/release-candidate-version-matrix.md`
- `docs/github-development-support.md`
- `docs/solo-ai-development-workflow.md`
- `docs/ai-development-handoff-summary.md`
- `docs/ai-development-workstream-summary.md`
- `docs/eval-lab-quality-gate.md`
- `docs/eval-lab-triad-review-closeout.md`

If these documents need expansion beyond Core governance, add the new rule to
Toolbox's platform index or the owning repository instead of expanding Core.

## Historical Records

Closeouts, stage summaries, translation status files, release notes, operator
trials, and prior-stage evidence are retained as history. They should not be
used as the first current-contract source unless a current Core truth document
links to them for evidence.

## README Rule

The root README should keep its startup path grouped by authority:

1. Governance truth first.
2. Core consumer guidance second.
3. Historical and platform coordination records last.

Do not return to one flat "read everything" list that makes Core appear to own
platform process, product UX, reference learning, release orchestration, or
cross-repo coordination.
