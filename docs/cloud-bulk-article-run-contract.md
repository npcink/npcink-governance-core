# Cloud Bulk Article Run Contract

Status: active planning contract.

This contract defines how large article runs may use hosted Cloud runtime
without moving local WordPress governance, approval, preflight, audit, or final
write ownership out of Core and the local Abilities API path.

## Product Position

Bulk article work belongs in Cloud for long-running production tasks:
research expansion, outline and draft candidate generation, queue-backed
worker execution, retries, progress, cost, quota, and diagnostics.

Bulk article publishing does not belong in Cloud. The safe product shape is
bulk article production and preparation in Cloud, followed by selective local
review and governed WordPress draft creation through Core.

## Ownership Split

| Project | Owns | Does not own |
| --- | --- | --- |
| `magick-ai-cloud` | Hosted bulk run API, worker execution, durable run state, progress, retry detail, cost/usage/quota records, and generated article artifacts. | Local approval truth, Core proposal truth, WordPress credentials, direct WordPress writes, or a second workflow/control plane. |
| `magick-ai-cloud-addon` | Local Cloud connection settings, signed request transport, health, run/result reads, and bounded read-only summaries or selected-artifact import affordances. | Bulk execution truth, Cloud worker recovery, local approval truth, proposal truth, or WordPress writes. |
| `magick-ai-toolbox` | Operator-facing article workflow UX and local import/review of selected Cloud artifacts as `article_write_plan` data. | Cloud run ownership, queue ownership, proposal truth, approval truth, or final writes. |
| `magick-ai-core` | Local proposal records, approval/rejection, commit preflight, fail-closed plan intake, and audit for selected write plans. | Cloud run APIs, Cloud callbacks, queues, retries, worker state, article generation, or final WordPress execution. |
| `magick-ai-adapter` | OpenClaw channel guidance, Core relay, and allowed Abilities API execution after Core approval and preflight. | Cloud run truth, bulk queue operation, approval truth, or generic bulk publish proxying. |
| `magick-ai-abilities` | Standard write abilities, schemas, callbacks, permissions, and dry-run previews. | Cloud run state, product workflow state, or governance truth. |

## Bulk Run Shape

Cloud may expose a versioned bulk article run artifact such as
`bulk_article_run_v1`.

The artifact should include:

- `run_id`: stable Cloud run identifier.
- `contract_version`: `bulk_article_run_v1`.
- `site_id`: Cloud-side site identity used for signed runtime access, not a
  WordPress write credential.
- `status`: bounded run lifecycle value.
- `idempotency_key`: caller-provided or Cloud-generated duplicate guard.
- `requested_article_count`: requested count.
- `completed_article_count`: completed artifact count.
- `failed_article_count`: failed item count.
- `limits`: batch size, concurrency, quota, retention, and retry limits.
- `cost`: usage and cost summary when available.
- `items`: per-article generated artifacts and failure details.

Allowed status values for the planning contract:

- `queued`
- `running`
- `ready_for_local_review`
- `partially_ready_for_local_review`
- `cancelled`
- `failed`
- `expired`

Cloud status is runtime evidence only. It is not Core proposal status and must
not be treated as approval, preflight, or WordPress write authorization.

## Item Shape

Each completed item should produce reviewable article artifacts, not a direct
publish instruction:

```json
{
  "item_id": "article_item_...",
  "status": "ready_for_local_review",
  "article_goal_brief": {},
  "research_evidence_pack": {
    "sources": []
  },
  "article_outline": {},
  "article_draft_candidate": {
    "content_markdown": "",
    "used_sources": [],
    "unverified_claims": [],
    "needs_human_input": []
  },
  "discoverability_pack": {},
  "article_risk_report": {
    "risk_level": "low",
    "blocked_claims": [],
    "needs_review": [],
    "ready_for_proposal": true
  },
  "article_write_plan": {
    "artifact_type": "article_write_plan",
    "version": 1,
    "proposal_mode": "single",
    "requires_approval": true,
    "dry_run": true,
    "commit_execution": false
  }
}
```

The imported `article_write_plan` must still satisfy the
[Article Writing Workflow Contract](article-writing-workflow-contract.md).
Core keeps accepting only the current single draft proposal shape unless a
future Core contract explicitly expands it.

## Local Import Flow

1. Cloud runs the bulk production job and stores run evidence.
2. Cloud Addon reads run summaries and item results through signed Cloud
   transport.
3. Toolbox or another local operator surface lets the operator select one or a
   small bounded set of ready items for local review.
4. The selected item is converted into the normal
   `magick-ai-toolbox/build-article-write-plan` handoff shape.
5. Core receives that plan through
   `POST /wp-json/magick-ai-core/v1/proposals/from-plan`.
6. Core creates local pending proposals only when the plan satisfies local
   acceptance rules.
7. Adapter or another local host may execute the approved draft write only
   after Core approval and commit preflight, through WordPress Abilities API.

## Guardrails

- Cloud must not store WordPress admin credentials or application passwords for
  direct post creation.
- Cloud must not call WordPress write endpoints to publish, draft, update, or
  delete posts.
- Cloud must not mark an item as approved, preflighted, committed, published,
  or locally executed.
- Cloud callbacks and run status are delivery/runtime evidence only.
- Cloud Addon summaries are read-only detail unless the local operator imports
  selected artifacts into the existing Core-governed path.
- Imported plans must default to draft creation and must not request
  `status=publish`, `post_status=publish`, `commit=true`, or
  `dry_run=false`.
- Bulk runs must be bounded by site quota, requested article count, retention,
  retry, and idempotency controls.
- Any future scheduled publishing feature must be local and Core-gated; it
  must not be inferred from this bulk run contract.

## Expansion Order

1. P0: Cloud produces a bulk run summary and per-item article artifacts; local
   import selects one item at a time into the existing single draft proposal
   path.
2. P1: local import may select a small bounded set of items, but each selected
   draft still becomes reviewable Core proposal data.
3. P2: Cloud may add better progress, cost, retry, and diagnostics detail.
4. P3: scheduled publishing can be reconsidered only with a new local Core
   governance contract and must remain local-write controlled.

## Boundary Statement

The phrase "bulk article publishing in Cloud" must be implemented as bulk
article production and preparation in Cloud. Final WordPress writes stay local,
proposal-governed, preflighted, audited, and executed through the WordPress
Abilities API path.
