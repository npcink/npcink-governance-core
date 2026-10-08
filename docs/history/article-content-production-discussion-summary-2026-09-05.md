# Article Content Production Discussion Summary - 2026-09-05

Status: accepted historical summary and Core-local boundary guide; product
development parked pending clearer positioning.

Authority note: this document summarizes two completed Codex discussion
threads and the implementation evidence they produced. It is not a new product
contract, plan schema, or permission to expand Governance Core. Active article
product details belong in the owning product or ability repository, with
cross-project coordination starting from
`/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`.

## Source Discussions

This summary consolidates these two local Codex tasks:

- external article translation and publication research, task
  `019f509f-6f34-7383-83bf-951f5cc420f3`;
- automatic article creation research, task
  `019f2ac3-ce63-7832-83c8-817f323de0eb`.

The first task progressed from product research through several cross-repo
implementation and real-editor trials. The second task focused on the product
value, ownership split, and implementation shape of automatic resource article
creation. Statements below distinguish verified outcomes from historical
proposals that were never accepted as current contracts.

## Executive Outcome

The two discussions converged on one product direction:

```text
Do not build a scrape, translate, and bulk-publish copier.

Build a governed content-production path that combines:
external evidence + Site Knowledge + reviewable drafting + human judgment
+ explicit WordPress commit paths.
```

The differentiation is not that an AI can translate or write an article.
Commodity tools already do that. The useful product value is the ability to
turn research into WordPress content while preserving source evidence, site
context, review, write authorization, failure evidence, and auditability.

The agreed default posture is draft-first and review-first. Automatic research
may proceed to a reviewable result, but publication is not an implied final
step. Empty results, blocked evidence, and editorial rejection are valid
outcomes.

## Current Product Disposition - 2026-09-05

The research and implementation evidence are retained, but article-product
development is intentionally paused. The technical path is sufficiently
demonstrated to avoid another infrastructure-first build. What remains unclear
is the product position: target user, highest-value recurring job, acceptable
level of automation, and whether the differentiated value is editor
assistance, governed external-client execution, or resource-content
production.

This is a parked direction, not an abandoned capability and not an approved
roadmap item. No repository should interpret this document as an instruction
to add article presets, resource schemas, batch controls, automation runtime,
or new Core contracts.

The preserved assets are:

- the source extraction, writing-pack, draft-preview, editor-adoption, and
  governed write foundations already present in their owning repositories;
- the implementation commits and real-trial evidence recorded below;
- the two archived Codex tasks listed in Source Discussions;
- this decision record, including rejected approaches and ownership rules.

Before product development resumes, answer these questions with a named user
and a real publishing workflow:

1. Which recurring editorial job is painful enough to justify a product:
   source adaptation, editor drafting, external-agent WordPress execution, or
   resource-article production?
2. Who makes the final editorial decision, and at what point must the product
   stop for review?
3. What measurable improvement is expected over the user's existing writing
   process: retained drafts, editing time, publishing throughput, or fewer
   factual and operational errors?
4. Which current capability is actually missing after a real end-to-end trial,
   rather than merely being inconvenient or not yet packaged?
5. Is the missing behavior owned by Toolbox, an ability provider, Adapter, or
   a future automation runtime? It must not be placed in Core by default.

The recommended restart is a no-new-contract discovery pilot using three to
five real articles and the existing stack. Resume implementation only when the
pilot identifies one repeated user problem, at least two outputs are retained
or published, the user reports meaningful time or quality improvement, and
one owning module can address the largest observed gap. Otherwise keep the
direction parked. Do not use a 20-article batch as the first validation.

## Discussion Stream A: External Source Adaptation

### Initial feasibility judgment

The reference feature was decomposed into URL fetch, article and lead-image
extraction, AI translation, remote-image adoption, and WordPress editor or
publication writes.

The existing stack already covered much of the write side:

- Toolbox had a Gutenberg writing surface and article plan support;
- Abilities Toolkit had draft creation, remote-media upload, featured-image,
  taxonomy, and SEO abilities;
- Core had proposal, approval, preflight, and audit contracts;
- Adapter had approved execution handoff;
- Cloud and Cloud Addon had hosted model and suggestion-only transport paths.

The main missing area was reliable external-source extraction and an editorial
product flow. That meant the work did not justify new Core runtime ownership.

### Product value was narrowed

The early idea was described as translation, but the discussion rejected
translation as the primary value. A small validation feature was considered
worthwhile; a broad translation and auto-publication product was not.

The useful problem statement became:

```text
external URL
-> bounded and attributable source evidence
-> Site Knowledge comparison
-> site-appropriate writing guidance or draft
-> human review
-> explicit WordPress commit
```

This reframing addressed mechanical editorial work without encouraging low
quality reposting. It also created explicit places for source coverage,
copyright, factual uncertainty, and site duplication checks.

### Reference plugin review

A small WordPress fetch-and-translate plugin was unpacked and reviewed. The
review separated useful interaction patterns from unsafe ownership patterns.

Useful patterns included:

- a clear fetch, process, and insert progression;
- previewing extracted content before model use;
- handling lazy-image attributes and `og:image` fallback;
- allowing users to edit AI output before it enters the editor;
- retaining WordPress native save and publish controls.

Rejected patterns included:

- arbitrary server-side URL fetches without SSRF controls;
- disabled TLS verification;
- provider keys and model controls stored in the product plugin;
- immediate media-library writes during preview;
- flattened HTML inserted as weak block structure;
- whole-article translation presented as original value;
- automatic publication.

The resulting engineering rule was:
reference plugins are pattern sources, not ownership models.

### Implemented source and drafting path

The discussion then produced a staged path across the owning repositories:

1. Cloud added exact, bounded source extraction with source identity and
   coverage evidence.
2. Cloud Addon transported the exact extraction intent without becoming a
   fetcher or write owner.
3. Toolbox presented source confirmation before site adaptation.
4. Site Knowledge supplied related local content for tone, coverage, and
   duplication context, not as an external fact source.
5. A writing pack recorded audience, angle, outline, fact ledger, sources,
   uncertainty, and copyright concerns.
6. Draft preview required explicit confirmation of the writing pack.
7. Lightweight review feedback could request one regeneration without
   becoming a review database or learning system.
8. A reviewed draft could be loaded into an empty Gutenberg editor as native
   blocks without automatic save or publication.

This path remained `suggestion_only` until the user explicitly chose a commit
path.

### Retrieval and embedding lessons

Real trials exposed several Site Knowledge issues that were treated as
provider-layer problems rather than patched repeatedly in Toolbox:

- chunk-level results caused one article to occupy several result slots;
- low-relevance results were worse than an honest empty result;
- indexes and queries built with different embedding models produced
  meaningless similarity scores;
- indexing hundreds of documents should not consume ordinary writing credits;
- full-site delivery required bounded pagination rather than one oversized
  request.

The decisions were:

- Cloud owns document-level grouping after retrieval and reranking;
- Toolbox requests document-level results only for the writing use case;
- incompatible or mixed embedding spaces fail closed;
- a stable Site Knowledge profile should bind model, dimensions, metric,
  provider identity, revision, and preprocessing into an embedding-space id;
- the discussed baseline was `BAAI/bge-m3`, 1024 dimensions, and `COSINE`;
- provider changes require a new probe and may require full reindexing;
- index maintenance keeps cost and provider-call evidence but is separate from
  ordinary AI writing-credit consumption.

The fixed vector profile was a design decision and handoff requirement in the
discussion. It must not be mistaken for proof that every deployment has
already completed migration or reindexing.

### Current contract reconciliation

Some historical trials used hosted Cloud model calls to produce writing packs
and draft previews. That evidence describes what was tested at the time, not
the current ownership contract.

The active External Article Recipe Boundary in `docs/next-stage-plan.md` now
requires article recipe details to live in the owning local product or ability
repository. Cloud does not generate drafts, SEO copy, article-plan candidates,
or bulk article artifacts. Current implementation work must follow that newer
boundary even when reproducing a historical trial.

### Real trials and corrected conclusions

The work deliberately separated technical completion from editorial value.

An early three-URL run produced structurally valid output but insufficient
article bodies because the source extraction coverage was weak. The result was
recorded as unusable instead of being called a successful product proof.

Later failures that appeared to be invalid JSON were traced to infrastructure:

- an upstream model read timed out at 60 seconds and returned a 504 page;
- a development hot reload stopped an in-flight Cloud process and returned a
  502 page;
- WordPress collapsed both HTML error pages into a misleading invalid-JSON
  message.

An isolated Cloud instance without hot reload then completed all three real
URLs. This proved technical feasibility but still did not prove human value.

The final editorial trial produced the strongest evidence:

- an outdated WordPress 7.0 delay article was reframed as a current upgrade
  checklist and stored as local draft `286721`;
- a June developer update became a focused plugin and theme testing checklist
  and was stored as local draft `286722`;
- a factually correct WordPress 6.9.2 release article was rejected because its
  July publication value had expired after WordPress 7.0 shipped.

At the close of the historical trial, both accepted outputs were unpublished
native Gutenberg drafts. A later local observation found that post `286721`
had been published, while post `286722` remained a draft. The rejected article
was a positive governance and editorial outcome: correctness alone does not
make content worth publishing.

The publication of one trial output is the strongest positive adoption signal
available, but one published post and one unfinished draft do not establish a
repeatable product need. The evidence proves technical feasibility and a
credible editor-value signal, not product positioning or product-market
validation.

## Discussion Stream B: Automatic Resource Article Creation

### Initial capability judgment

The second discussion compared the five-project stack with a GUAQI-style
request to create many resource-download articles from one AI instruction.

The initial assessment was that the stack contained roughly 70 to 80 percent
of the required foundation, but could not honestly claim a reliable one-shot,
20-article publication system.

Existing pieces included:

- draft, media, featured-image, taxonomy, and SEO abilities;
- proposal, approval, preflight, and audit;
- approved execution and output-reference binding;
- article planning, image candidates, FAQ and SEO suggestions, and Site
  Knowledge;
- hosted search and image suggestions.

Missing pieces included:

- a stable resource evidence shape covering repository, release, download,
  license, system requirement, and image provenance;
- a product-owned resource article package;
- a unified reviewed write recipe;
- customer-specific download-field mapping where Gutenberg content is
  insufficient;
- production-grade unattended scheduling, retry, lease, and resume behavior.

### MCP assumption was corrected

The initial analysis treated MCP as a possible future channel. The user then
clarified that Npcink AI Client Adapter already connects the customer's local
OpenClaw-like client and supplies the needed MCP-like interaction.

The decision changed accordingly:

```text
Do not add MCP for this goal.
Use the existing Adapter as the client channel.
```

The customer client owns natural-language interpretation, task decomposition,
iteration, pacing, and multi-batch coordination. Adapter remains a channel and
approved executor, not a workflow runtime.

### Product value judgment

The feature was considered worth doing only when framed as a reusable governed
content-production capability. A one-customer, one-category, 20-article custom
publisher had limited value. A reusable path from external AI research to
governed WordPress operations had higher platform value.

The key problem was the last mile:

- research results did not reliably become WordPress content;
- media, taxonomy, SEO, and draft operations required repeated manual work;
- external agents lacked consistent approval and audit boundaries;
- partial multi-step failures were difficult to explain and resume;
- the existing projects had atomic capabilities but lacked a customer-ready
  business flow.

The discussions judged governance, local control, and extensible ability
contracts to be the differentiator. Bulk AI writing by itself was not treated
as a durable product advantage.

### Target product shape

The target was clarified as:

```text
user business request
-> customer client decomposes the mission
-> Toolbox reads site context and finds evidence
-> Cloud/Toolbox research resources, releases, licenses, and images
-> a reviewable article package is generated
-> a local write recipe is built
-> Core governs required writes
-> Adapter executes approved abilities
-> WordPress receives drafts, media, taxonomy, and SEO
-> execution evidence is recorded
```

Research, selection, drafting, and plan preparation may be automated. The
system may automatically reach `pending` review. Publication remains a
separate high-impact decision, and draft creation is the default.

### Historical contract proposals

The discussion proposed names such as:

- `resource_content_mission.v1`;
- `site_content_profile.v1`;
- `resource_evidence_pack.v1`;
- `resource_article_pack.v1`;
- `resource_article_write_plan.v1`.

These names described a possible product contract decomposition. They were not
accepted Core contracts and must not be added to Core based on this historical
summary.

ADR-009 now freezes new domain-specific plan ids, schemas, previews, and
workflow semantics in Core. A current implementation should prefer an owning
product or provider contract plus the provider-neutral governance envelope.
Any exception requires a new ADR.

### Recommended delivery sequence

The agreed sequence was deliberately incremental:

1. Run one real resource-article golden sample with existing abilities.
2. Record actual missing evidence, bindings, and customer fields.
3. Stabilize the product-owned evidence and article package only after the
   sample proves the need.
4. Add a bounded local write recipe in the owning repository.
5. Expand to two to five reviewed drafts per batch.
6. Let the customer client split a 20-article request into sequential batches.
7. Add a dedicated automation runtime only if unattended retry, lease,
   scheduling, kill-switch, and resume requirements become real.

FAQ and download links should initially live in reviewed Gutenberg content.
If a customer site requires download-plugin metadata, the owning provider may
add a narrow, schema-specific ability. A generic arbitrary `post_meta` write
ability was explicitly rejected.

## One Product, Two Commit Paths

The discussions identified two legitimate commit paths. They are not
interchangeable.

| Situation | Classification and owner |
| --- | --- |
| A present author reviews a draft and loads it into the currently open, empty Gutenberg editor, then uses native WordPress save or publish controls. | `native_editor_commit`; the editor and present author own the final action. No Core proposal is required for the eligible editor-memory transition. |
| An external client or automated process creates a post, imports media, assigns terms, writes SEO metadata, or performs multiple site operations. | `core_proposal_required`; Core owns proposal, approval, preflight, and audit while Adapter or the host executes approved abilities. |

The first path must not be used to disguise background automation as a present
editor action. The second path must not make Core the executor or runtime.

## Stable Ownership Map

| Responsibility | Owner |
| --- | --- |
| Natural-language mission interpretation, iteration, and batch pacing | Customer local OpenClaw-like client |
| Article product UX, source review, site adaptation, article package, and local recipe | Workflow Toolbox or another owning product plugin |
| Reusable WordPress write abilities and provider schemas | Abilities Toolkit or another ability provider |
| Proposal, approval, preflight, and audit truth | Governance Core |
| Approved execution, output bindings, and result reporting | AI Client Adapter or another approved adapter/host |
| Signed WordPress-to-Cloud transport | Cloud Addon |
| Hosted extraction, model calls, retrieval, cost, and runtime diagnostics | AI Cloud |
| Final native save or publish decision in the editor path | Present WordPress author/editor |
| Unattended job, lease, retry, schedule, and resume state | Dedicated local automation runtime, if later justified |

## Development Lessons

### 1. Investigate before choosing an owner

The useful question is not only whether a feature can be built. Decompose it
into source data, product UX, ability contract, governance, execution, and
runtime concerns first. This prevented a reference plugin's convenient but
unsafe single-plugin architecture from being copied into Core.

### 2. Separate technical, quality, and value gates

Three different claims require three different forms of evidence:

- technical gate: the pipeline completes and preserves write boundaries;
- quality gate: the draft is factually supported and editorially usable;
- value gate: a real editor prefers keeping and editing the result over
  starting from zero.

Passing the technical gate must never be reported as passing the value gate.

### 3. Use stop-the-line behavior

When a fact ledger was empty, a source was incomplete, a vector space was
incompatible, or an article was stale, the correct result was to stop. Adding
retry, a lower threshold, or more generated prose would have hidden the real
problem.

### 4. Treat empty and rejected results as useful

Retrieval may return no related article. Evidence collection may block.
Editorial review may reject a correct but obsolete draft. These outcomes are
better than fabricated relevance, invented facts, or forced publication.

### 5. Diagnose across infrastructure layers

The invalid-JSON symptom was not a prompt or parser defect. It represented 502
and 504 HTML responses produced by timeout and hot-reload behavior. Effective
diagnosis checked the client error, proxy response, Cloud logs, provider timing,
and process lifecycle before changing product code.

### 6. Preserve one truth source per concern

- Cloud owns embedding-space and provider runtime truth.
- Ability providers own ability schemas and WordPress execution behavior.
- Core owns governance truth.
- Product repositories own article recipes and editorial UX.
- Customer clients or a dedicated runtime own long-running orchestration.

Duplicating any of these truths in Core would make later drift inevitable.

### 7. Add contracts only after a real sample

The recommended order was golden sample first, stable public contract second.
This avoids designing resource schemas, batch behavior, or custom-field support
from assumptions. It is especially important now that ADR-009 freezes new
domain plan contracts in Core.

### 8. Scale last

One reviewed article proves bindings and ownership. Two to five articles prove
bounded batch review. Twenty articles require operational evidence for pacing,
idempotency, failure recovery, and resume behavior. A large batch button is not
a substitute for that runtime evidence.

### 9. Keep experiments reversible

The historical work used isolated worktrees, temporary scripts, temporary
Cloud configuration, idempotent trial markers, targeted cleanup, and separate
commits. It also preserved unrelated dirty-worktree changes. This made real
WordPress tests possible without converting test artifacts into product state.

### 10. Record negative evidence

Rejected drafts, insufficient extraction, timeouts, model mismatches, and
misleading error classification changed the plan more than happy-path tests.
Future trials should retain those observations instead of keeping only passing
examples.

## Selected Historical Evidence

The discussions referenced these selected implementation commits. The list is
orientation evidence, not a release manifest:

- Cloud `9fbacbd2`: exact source extraction preview;
- Cloud Addon `11a3c00`: exact source extraction transport;
- Toolbox `fefc83e`: staged exact source adaptation;
- Toolbox `da6962a`: writing-pack confirmation and draft preview;
- Toolbox `004932c`: draft review feedback and regeneration;
- Toolbox `b5d4794`: reviewed empty-editor draft loading;
- Cloud `216c9d4b` and Toolbox `af9318f`: document-level Site Knowledge
  results and consumption;
- Cloud `fefd866b`: fail closed on incompatible Site Knowledge embeddings;
- Cloud `7f17f02b`: separate Site Knowledge index metering;
- Core `9b3d1f3`: aggregate ability discovery on cold start;
- Core `e2494b6`: record the two editor-value trial drafts.

Repository histories and current owning-repository contracts remain the source
of truth if any hash, branch, or historical statement conflicts with current
code.

## Current Decision Ledger

| Decision | Status |
| --- | --- |
| Default to reviewed drafts, not automatic publication. | Accepted direction. |
| Use exact, bounded source extraction and expose coverage evidence before generation. | Implemented and historically verified. |
| Use Site Knowledge for local context, not as an external fact source. | Accepted direction. |
| Keep editor-memory adoption separate from external automated writes. | Accepted Core classification boundary. |
| Use AI Client Adapter for the existing OpenClaw-like client; do not add MCP for this goal. | Accepted product/channel decision. |
| Keep queues, workers, leases, retries, schedules, and resume state outside Core and Adapter. | Accepted architecture boundary. |
| Continue article-product implementation now. | Parked until product positioning and a repeated user job are validated. |
| Start resource content with one golden sample, then bounded batches. | Historical recommendation; superseded for now by a three-to-five-article no-new-contract discovery pilot. |
| Add `resource_article_write_plan.v1` directly to Core. | Historical proposal; not accepted and now blocked by ADR-009 without a new ADR. |
| Consider the article product commercially validated. | Not yet proven; editor adoption remains the gate. |

## Recommended Next Gate

Do not add another Core feature or begin cross-repository article-product
implementation from these discussions.

If this direction is reconsidered later, the next useful product experiment
belongs in the owning product lane:

1. State one target user, one recurring job, and one success metric before
   changing code.
2. Run three to five real articles through the current stack, including a
   resource article only if that matches the selected job.
3. Record evidence completeness, edit time, retained content, failed bindings,
   duplicate behavior, and final draft status.
4. Decide Go/No-Go from observed adoption; do not equate pipeline completion
   with product value.
5. If Go, change only the owning module responsible for the largest repeated
   failure. Run the owning repository gate, then the central matrix when
   closing a cross-repository milestone.

## Core Stop Rules

This summary does not authorize Core to add:

- article generation or translation;
- product workflow UX or resource-domain planning;
- new domain-specific plan ids or preview schemas;
- MCP runtime or Agent Gateway catalogs;
- workflow runtime, task queues, workers, leases, retries, or schedulers;
- provider credentials, model routing, prompts, vector configuration, or
  billing;
- arbitrary WordPress metadata writes;
- final WordPress write execution;
- automatic or bulk publication.

If a future implementation appears to require one of these in Core, stop and
write a boundary note or a new ADR where ADR-009 requires an exception.
