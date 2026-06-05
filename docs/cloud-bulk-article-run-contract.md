# Cloud Bulk Article Run Contract

Status: prohibited and deprecated planning contract.

This document records a product decision: Magick AI will not develop Cloud
article writing generation, Cloud bulk article drafting, or Cloud publishing
capabilities. It is retained to prevent future drift back into a Cloud writing
surface.

## Decision

Cloud must not provide:

- `bulk_article_run_v1` as an active hosted writing product;
- article title, outline, paragraph, draft, SEO copy, or body generation;
- batch article draft production;
- Cloud-produced `article_write_plan` candidates;
- Cloud-produced `article_batch_write_plan` candidates;
- Cloud-produced `article_media_batch_write_plan` candidates;
- Cloud article artifact import into Toolbox;
- Cloud-side article scheduling or publishing;
- Cloud WordPress credentials or direct WordPress writes.

## Replacement

Article drafting is a local Ability recipe, not a Cloud feature. The active
contract is [Ability Recipe Orchestration Contract](ability-recipe-orchestration-contract.md).

The safe local path is:

```text
local ability recipe
  -> local/operator-reviewed artifacts
  -> npcink-toolbox/build-article-write-plan
  -> Core POST /proposals/from-plan
  -> Core approval and commit preflight
  -> Adapter executes npcink-abilities-toolkit/create-draft through WordPress Abilities API
```

## Allowed Cloud Role

Cloud may still support the Magick AI stack through non-writing service
functions:

- connection health;
- entitlement and usage detail;
- runtime diagnostics;
- service status;
- allowed non-content runtime tasks.

Cloud must not generate, store, or return article body content, draft
candidates, SEO writing, `article_write_plan`, `article_batch_write_plan`,
`article_media_batch_write_plan`, or bulk writing artifacts.

## Boundary Statement

The phrase "bulk article publishing in Cloud" is rejected for the current
product. The correct architecture is local Ability recipe orchestration with
Core-governed write actions.

## Rejected Product Language

Do not describe current or planned Cloud capability as:

- Cloud article generator;
- Cloud writing assistant;
- Cloud bulk article writer;
- hosted article drafting;
- article publishing automation;
- Cloud article import.

When writing docs or UI copy, use local Article Assistant Workbench, local
Ability recipe, or Core-governed draft write proposal instead.
