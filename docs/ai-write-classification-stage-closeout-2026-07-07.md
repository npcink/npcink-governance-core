# AI Write Classification Stage Closeout - 2026-07-07

Status: stage closeout record.

This record summarizes the product discussion and implementation work that
shifted the next-stage target away from first-party metadata generation and
toward AI write classification discipline.

## Why This Stage Existed

The project was facing a real tension:

- learning from mature AI and editorial plugins without turning this local
  project into a broad platform;
- proving a useful AI-governed workflow without expanding Core, Toolbox, or
  Adapter into content products;
- borrowing review and audit ideas without importing queues, workflow builders,
  Action Scheduler, or generic runtime ownership;
- validating a short-term product slice without committing to long-running
  automation infrastructure.

The principal contradiction was not whether AI-generated content metadata is
useful. Existing WordPress AI plugins can already show summaries, titles,
categories, tags, ALT text, SEO descriptions, and editor suggestions directly
inside the editor. The real question for Core is whether an AI-assisted action
is asking WordPress to write on the user's behalf, and therefore needs
independent governance.

## Decision

The current stage target is the AI Write Classification Matrix, not
first-party summary/category/tag generation.

Core should classify and govern AI-assisted WordPress write paths. It should
not build another content metadata product. Generic AI plugin output accepted
by a present author inside the WordPress editor is treated as native author
review and stays outside Core proposal review.

The stable classification values are:

- `suggestion_only`;
- `local_admin_consent`;
- `strong_local_confirmation`;
- `core_proposal_required`.

The operating rule is:

- visible editor or generic AI plugin acceptance: no Core proposal hop;
- Npcink-owned candidates with no write: `suggestion_only`;
- one visible low-risk present-admin object or field: possible
  `local_admin_consent` with audit evidence;
- one visible but high-impact present-admin action: `strong_local_confirmation`
  only when preview and restore evidence are strong, otherwise Core proposal;
- external, automated, delegated, destructive, publishing, settings-changing,
  permission-changing, incomplete-preview, multi-object, or batch writes:
  `core_proposal_required`.

## What Was Rejected

This stage explicitly rejected:

- first-party summary/category/tag generation in Core;
- a generic AI control console;
- Core final WordPress execution;
- Cloud writing to WordPress;
- local queues, workflow runtime, schedulers, retry workers, or run tables in
  Core;
- Action Scheduler or a workflow builder as a way to prove the slice;
- a second approval store or second ability/workflow registry;
- treating normal WordPress editor publish/save as a Core approval workflow.

## What Was Implemented

The stage landed in several small increments:

| Commit | Purpose |
| --- | --- |
| `9b81769` | Added the AI Write Classification Matrix. |
| `073bba9` | Superseded the metadata trial as the next-stage target. |
| `1991b8f` | Persisted classification evidence at real Core write entrypoints. |
| `3547bee` | Documented the three-lane release regression gate. |
| `069e09d` | Promoted classification to release and new-entrypoint admission. |
| `d29091a` | Added the reusable regression evidence template. |

The resulting documentation and gates are:

- [Operation Classification Contract](operation-classification-contract.md):
  source of truth for classification rules and the matrix.
- [Development Workflow](development-workflow.md): requires a classification
  answer before new AI-assisted write entrypoint implementation.
- [Testing Strategy](testing-strategy.md): defines the AI write classification
  release regression.
- [WordPress.org Release Gate](wordpress-org-release-gate.md): requires the
  regression when AI-assisted write entrypoints are in release scope.
- [Cross-Repo Release Acceptance](cross-repo-release-acceptance.md): includes
  the classification regression as a stack boundary check when relevant.
- [AI Write Classification Regression Evidence](ai-write-classification-regression-evidence.md):
  provides the copyable evidence record template.

## Real Validation Shape

The canonical local validation target is:

```text
/Users/muze/Local Sites/magick-ai/app/public
https://magick-ai.local/
```

The evidence template fixes three lanes:

1. Native editor or generic AI plugin acceptance keeps Core proposal and audit
   counts unchanged.
2. Toolbox existing-attachment featured-image Local Admin Consent records
   `local_admin_consent.requested` and `local_admin_consent.completed` without
   creating a Core proposal.
3. High-risk article/media batch work creates Core proposal evidence and emits
   no `local_admin_consent.*` audit events.

The template also blocks committed credentials, cookies, app tokens, raw
provider payloads, and private content.

## Effect

The effect is a tighter product boundary:

- authors can keep using normal WordPress AI plugin editor output without a
  redundant Core approval hop;
- Core still receives independent governance evidence for external, delegated,
  batch, destructive, high-impact, or incomplete-preview writes;
- Toolbox keeps only the narrow featured-image Local Admin Consent proof as an
  audited exception;
- release candidates and new write entrypoints now have a concrete admission
  and evidence path.

This was worth doing because it converts the discussion from "should AI content
go through Core?" into a concrete, repeatable question: who owns the final
WordPress action, and can the operator see the exact result before it is
written?

## Current Stop Condition

This stage is complete. Do not continue by adding Core features.

Continue only when one of these triggers appears:

- a new AI-assisted WordPress write entrypoint is proposed;
- one of the three regression evidence lanes fails;
- a release candidate needs the evidence template filled in;
- Toolbox, Adapter, Cloud Addon, or another product module introduces a new
  write path that must be classified.

When a trigger appears, first classify the path. Only fix code when the
classification or evidence shows a real gap. Do not expand Core, Toolbox, or
Cloud to make a failed evidence lane pass.
