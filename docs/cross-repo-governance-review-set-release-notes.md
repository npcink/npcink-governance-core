# Cross-Repo Governance Review-Set Release Notes

Status: draft PR and release summary for the 2026-06-15 governance review-set
hardening slice.

## Summary

This slice turns the current batch and metadata work into explicit reviewed
governance contracts across Core, Toolkit, and Toolbox.

The main boundary is unchanged: Core owns proposal, approval, commit preflight,
audit, and review evidence. Toolkit owns ability schemas, dry-run previews, and
planning details. Toolbox owns operator-facing review-set UX and proposal
handoffs. Adapter remains the channel executor only after Core approval,
commit preflight, and its explicit allowlist pass.

No project in this slice adds a workflow runtime, queue, scheduler, lease
store, unattended approval loop, or Core-owned final WordPress write executor.

## Included Repositories And Commits

### npcink-governance-core

- `8829e85 core: harden governance decision evidence`
- `a38bbc1 core: expose batch proposal review summary`
- `a0a4f86 core: document local automation runtime boundary`

Core now preserves stronger operation classification evidence, exposes stable
`core-batch-review-summary-v1` review data for grouped proposals and
commit-preflight recovery, and records ADR-006 plus the local automation
runtime contract so unattended batch execution cannot drift into Core or the
OpenClaw Adapter.

### npcink-abilities-toolkit

- `3a371a2 toolkit: align block theme handoff targets`

Toolkit plan output now aligns with Core's bounded block-theme template
contract. Archive template write prompts fail closed before Core handoff, while
read-side inspection can still examine archive templates.

### magick-ai-toolbox

- `c748423 toolbox: preserve metadata apply classification evidence`
- `7c85a45 toolbox: document progressive recommendations closeout`
- `1b61bd9 toolbox: add media derivative batch review smoke`

Toolbox now carries Core proposal-required classification evidence in accepted
content metadata apply plans, documents the progressive recommendation closeout,
and exposes media derivative batch review-set feedback in the admin UI. The
batch media path builds a visible plan, shows eligible and blocked items,
generates selected previews, and submits selected Core review proposals without
executing media writes.

### magick-ai-adapter

No new commit is required for this slice.

Adapter already exposes the required `batch_review_feedback` and selected
media derivative proposal paths. Verification confirmed the existing Adapter
contract can create selected Core review proposals without executing them.

## Boundary Statement

This release is a governance and review-set hardening slice.

It permits:

- Core proposal intake and review evidence;
- Core approval and commit-preflight readiness checks;
- Toolkit eligibility summaries, blocked-item details, and dry-run previews;
- Toolbox operator-visible review plans, selected previews, and Core proposal
  handoffs;
- Adapter execution only after Core approval, commit preflight, and an explicit
  execution profile allowlist.

It does not permit:

- Core final write execution;
- hidden Toolbox direct media mutation;
- Toolkit approval or runtime state;
- Adapter scheduler or durable job ownership;
- queues, leases, workers, retry processors, dead-letter processors, MCP
  runtime, Agent Gateway catalogs, or unattended approval loops.

## Verification

Core:

- `composer test:all`
- `composer validate --no-check-publish`
- `composer smoke:wp`

Toolkit:

- `composer test:all`
- `composer analyse:phpstan`
- `git diff --check`

Toolbox:

- `composer test:all`
- `composer validate --no-check-publish`
- `composer smoke:metadata-delta`
- `composer smoke:media-derivative-batch-plan`
- `composer smoke:media-derivative-batch-core`
- `git diff --check`

Adapter:

- `composer test:all`

## Operator-Facing Change Notes

- Batch media optimization is now presented as a bounded review set, not as
  one-click whole-site replacement.
- Operators can see eligible items, blocked items, retry guidance, next action,
  selected preview status, submitted proposal counts, and failed proposal
  status.
- Content metadata apply handoffs now carry Core proposal-required
  classification evidence through to Core proposal preview.
- Progressive editor recommendations remain local, suggestion-only, and
  explicitly closed out as a separate accepted slice.

## Suggested PR Description

Title:

```text
Harden Core-governed review sets across Core, Toolkit, and Toolbox
```

Body:

```markdown
## Summary
- harden Core decision evidence and batch review summaries
- align Toolkit block-theme handoff targets with Core fail-closed intake
- preserve Toolbox metadata apply classification evidence
- add Toolbox media derivative batch review-set UI and smoke coverage
- document the local automation runtime boundary without implementing runtime

## Boundary
- no Core execution route or workflow runtime
- no Toolbox direct media mutation
- no Adapter scheduler or durable job state
- no unattended approval or queue/lease/retry worker

## Verification
- Core: composer test:all, composer smoke:wp, composer validate --no-check-publish
- Toolkit: composer test:all, composer analyse:phpstan
- Toolbox: composer test:all, composer smoke:metadata-delta,
  composer smoke:media-derivative-batch-plan,
  composer smoke:media-derivative-batch-core
- Adapter: composer test:all
```

## Follow-Up

The next separate slice can handle Phase 1 local automation runtime owner and
dry-run replay schema work. That should stay independent from this review-set
release and must remain contract-only until a dedicated runtime repository is
selected and tested.
