# Ability Recipe Orchestration Contract

Status: active planning contract.

This contract defines how Magick AI should compose multiple WordPress
Abilities into useful operator flows without creating feature-specific
exceptions, second workflow runtimes, or Cloud content-generation products.

## Position

An ability recipe is a deterministic orchestration plan over standard
Abilities. It is not a new ability registry, workflow runtime, queue, approval
system, prompt store, or Cloud writing surface.

Article drafting is the first example recipe, not a privileged product
exception.

## Complexity Budget

Recipes must stay thin. Their value is the scientific ordering of existing
Abilities, operator checkpoints, and Core-governed write handoffs, not a new
product runtime.

The accepted product language is local article assistant workbench or local
Ability recipe. Avoid language such as article generation product, Cloud
writer, bulk article publisher, or autonomous writing workflow when describing
the current architecture.

For `article_draft_v1`, the current budget is:

- one local article at a time;
- no batch article queue, scheduler, retry worker, or durable recipe runtime;
- no Cloud-generated draft body, SEO copy, or article plan;
- no automatic approval from recipe readiness;
- no final write except the Core-approved `magick-ai/create-draft` Ability
  callback executed outside Core.

`article_batch_draft_v1` is a separate bounded local profile for the same
Article Assistant Workbench. It may group 2 to 5 locally reviewed draft-only
`magick-ai/create-draft` actions into one Core `plan_to_proposal_batch`
proposal through `magick-ai-toolbox/build-article-batch-write-plan`. It must
not add a queue, scheduler, retry worker, Cloud writing generation, automatic
approval, or direct WordPress write.

If a future feature needs more than this budget, it must be documented as a
new local recipe profile and still enter Core through the same governed
`write_actions` bridge. It must not extend this article recipe into a hidden
content-generation platform.

It must not become a hidden content-generation platform.

## Recipe Shape

A recipe should describe:

- `recipe_id`: stable local identifier, such as `article_draft_v1`.
- `version`: integer contract version.
- `steps`: ordered Ability calls or operator review checkpoints.
- `artifacts`: named outputs produced by earlier steps.
- `write_actions`: optional final write actions for Core proposal intake.
- `handoff`: the Core route and planning ability id used for governed writes.
- `guardrails`: explicit non-goals and fail-closed conditions.

Recipe steps must reference real local Ability ids, for example:

- `magick-ai-toolbox/get-content-discoverability-context`
- `magick-ai-toolbox/web-research`
- `magick-ai-toolbox/search-image-source`
- `magick-ai-toolbox/vector-search`
- `magick-ai-toolbox/build-article-write-plan`
- `magick-ai-toolbox/build-article-batch-write-plan`
- `magick-ai-toolbox/build-article-media-batch-write-plan`
- `magick-ai/create-draft`

## Article Draft Recipe

`article_draft_v1` is a local recipe profile:

1. gather site/content context;
2. run optional research, image-source, and vector-context reads;
3. compose or review article artifacts locally or through local/provider
   Abilities selected by the host;
4. build an `article_write_plan`;
5. submit the plan through Core `POST /proposals/from-plan`;
6. approve and preflight in Core;
7. execute only the approved `magick-ai/create-draft` write through WordPress
   Abilities API outside Core.

The recipe may use `article_write_plan` as a profile-specific artifact, but
Core still governs only the resulting `write_actions`.

## Article Batch Draft Recipe

`article_batch_draft_v1` is a bounded local recipe profile:

1. gather or reuse local site/content context;
2. prepare 2 to 5 reviewed article artifact sets locally;
3. ensure each article risk report is ready, non-high, and has no blocked
   claims;
4. build an `article_batch_write_plan` with `proposal_mode=batch` and
   `batch_approval=true`;
5. submit the plan through Core `POST /proposals/from-plan`;
6. approve and preflight the one batch proposal in Core;
7. execute each approved `magick-ai/create-draft` action through WordPress
   Abilities API outside Core.

The batch proposal is an approval grouping. It is not a batch writing job,
queue, scheduler, or Cloud generation surface.

`article_media_batch_draft_v1` is a bounded local recipe profile for reviewed
drafts plus reviewed image-source candidates. It builds an
`article_media_batch_write_plan` through
`magick-ai-toolbox/build-article-media-batch-write-plan` with draft creation,
media upload, media metadata, and featured-image write actions, then submits
that plan through Core proposal intake. The media proposal is an approval
grouping only; Toolbox does not import media, Core does not execute writes, and
Cloud must not generate the article or image-source plan.

## Ownership

| Project | Owns | Does not own |
| --- | --- | --- |
| `magick-ai-abilities` | Standard Ability definitions, schemas, callbacks, previews, and permissions. | Product workflow state, Cloud writing, approval truth, or recipe runtime ownership. |
| `magick-ai-toolbox` | Operator-facing recipe UX, fixed recipe buttons, and artifact rendering. | Core proposal truth, approval truth, final writes, or Cloud writing. |
| `magick-ai-core` | Proposal records, approval/rejection, commit preflight, plan intake, and audit. | Recipe execution, article generation, queues, or Ability callbacks. |
| `magick-ai-adapter` | OpenClaw channel guidance and Core/Abilities relay. | Recipe truth, Cloud writing, approval truth, or arbitrary write proxying. |
| `magick-ai-cloud-addon` | Cloud connection, health, entitlement, and bounded non-content runtime transport. | Writing generation, recipe truth, proposal truth, or WordPress writes. |
| `magick-ai-cloud` | Hosted runtime infrastructure for allowed non-writing service tasks. | Article drafting, bulk writing, prompt/preset ownership, publishing, or WordPress write ownership. |

## Cloud Boundary

Cloud must not provide article writing generation, batch article drafting, SEO
copy generation, or bulk publishing as a hosted product capability.

Allowed Cloud involvement for writing-related screens is limited to:

- connection health;
- entitlement and usage detail;
- runtime diagnostics for allowed non-writing service tasks;
- service status;
- bounded metadata that does not include article body generation.

Cloud must not store article body generation jobs, generate draft candidates,
return `article_write_plan` candidates, or expose a bulk article run endpoint.
Cloud must also not generate `article_batch_write_plan` or
`article_media_batch_write_plan` candidates.

## Core Boundary

Core must not become article-aware beyond validating supported plan output. It
may preserve article artifacts for proposal review, but its durable governance
contract is still:

- real `ability_id`;
- structured `input`;
- preview/context;
- `write_actions`;
- approval;
- commit preflight;
- audit.

Any future recipe should enter Core through the same plan-to-proposal bridge
instead of adding feature-specific Core routes.

## Guardrails

- Do not introduce `confirm_token` or `write_confirmed`.
- Do not add Cloud writing generation.
- Do not add local workflow/task queues for recipes.
- Do not add Cloud article import flows.
- Do not map recipe readiness to Core approval.
- Do not let Adapter execute unprofiled writes.
- Do not treat an article recipe as a special Core feature.
