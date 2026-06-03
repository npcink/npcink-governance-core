# Article Writing Workflow Contract

Status: active planning contract.

This contract defines the first governed handoff for AI-assisted article
writing across the Magick AI local plugins. It turns a single reviewed writing
artifact into a Core proposal without moving product workflow, ability
callbacks, OpenClaw channel logic, hosted runtime, or final WordPress execution
into Core.

## Product Position

The first product shape is an AI-assisted writing workflow, not automatic
publishing.

The workflow is a local Ability recipe. It helps an operator collect research,
prepare or review a draft, inspect risk, and request a governed WordPress draft
write. Operators review. Core governs the write. Cloud does not provide article
writing generation.

## Project Ownership

| Project | Owns | Does not own |
| --- | --- | --- |
| `magick-ai-toolbox` | Operator-facing workflow UI, fixed writing flow artifacts, research/image/vector tool UX, content discoverability context, and `magick-ai-toolbox/build-article-write-plan`. | Final WordPress writes, Core proposal records, approval truth, audit truth, OpenClaw channel truth, or hosted runtime ownership. |
| `magick-ai-abilities` | Standard WordPress abilities, schemas, callbacks, permissions, dry-run previews, and reusable deterministic helpers such as context, risk, compose, and write callbacks. | Product workflow state, model routing, cloud execution, approval truth, audit truth, or final governance. |
| `magick-ai-core` | Plan intake, proposal records, approval/rejection, commit preflight, fail-closed policy checks, and audit. | Article generation, Toolbox workflow state, ability execution, final writes, workflow runtime, queues, model routing, or provider credentials. |
| `magick-ai-adapter` | OpenClaw channel routes, capability guidance, direct-read Ability API calls, proposal relay, commit-preflight relay, and allowlisted execution after Core approval and preflight. | Article generation, SEO/GEO/AEO judgment, workflow state, approval truth, or generic write proxying. |
| `magick-ai-cloud-addon` | Cloud connection, health, stats, and entitlement detail for non-writing service surfaces. | Article generation, local control plane, proposal truth, approval truth, workflow truth, ability registry, prompt/router/preset ownership, or WordPress writes. |

## P0 Flow

The first slice supports one article and one draft write proposal:

1. Toolbox collects the operator's topic, intent, audience, and context.
2. Toolbox reads `magick-ai-toolbox/get-content-discoverability-context`.
3. Toolbox runs bounded research, image-source, and vector-search actions when
   configured.
4. Toolbox, local/provider Abilities, or the operator produce the standard
   artifacts:
   - `article_goal_brief`
   - `research_evidence_pack`
   - `article_outline`
   - `article_draft_candidate`
   - `discoverability_pack`
   - `article_risk_report`
   - `article_write_plan`
5. Toolbox submits the plan to Core through
   `POST /wp-json/magick-ai-core/v1/proposals/from-plan` with
   `plan_ability_id=magick-ai-toolbox/build-article-write-plan`.
6. Core validates the plan and creates a pending `magick-ai/create-draft`
   proposal only when the plan is ready.
7. Adapter may approve and execute the proposal only after Core approval and
   successful commit preflight.
8. The WordPress Abilities API executes the approved `magick-ai/create-draft`
   callback outside Core.

## Article Write Plan Shape

The Toolbox planning ability must return a direct-read plan payload:

```json
{
  "artifact_type": "article_write_plan",
  "version": 1,
  "batch_id": "article_write_...",
  "requires_approval": true,
  "dry_run": true,
  "commit_execution": false,
  "proposal_mode": "single",
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
  "write_actions": [
    {
      "action_id": "create_article_draft",
      "target_ability_id": "magick-ai/create-draft",
      "input": {
        "title": "Draft title",
        "content": "Draft body",
        "status": "draft",
        "dry_run": true,
        "commit": false
      },
      "risk": "medium",
      "requires_approval": true,
      "commit_execution": false,
      "proposal_ready": true
    }
  ]
}
```

The P0 plan is intentionally single-action. Later slices may add separate
governed proposals for SEO meta, terms, media metadata, and featured images
after the single draft loop is stable.

## Ability Recipe Position

Article drafting is the `article_draft_v1` profile of
[Ability Recipe Orchestration Contract](ability-recipe-orchestration-contract.md).
The recipe is a scientific composition of standard Abilities, not an article
exception in Core and not a Cloud writing feature.

The Cloud boundary is defined in the prohibited/deprecated
[Cloud Bulk Article Run Contract](cloud-bulk-article-run-contract.md). The
short version is:

- Cloud must not generate article drafts, SEO copy, or bulk writing artifacts.
- Cloud Addon must not import Cloud article artifacts.
- Toolbox may run local recipe UX and render local artifacts.
- Core still accepts only locally submitted, reviewable plan data.
- Adapter still executes only after Core approval and commit preflight.
- Final WordPress writes stay local and Abilities API based.

## Core Acceptance Rules

Core accepts `magick-ai-toolbox/build-article-write-plan` only when the
planning ability is discoverable as `governance_mode=direct_read` and
`execution_surface=wp_abilities_rest`.

Core fails closed when:

- the plan is not `artifact_type=article_write_plan`;
- `version` is missing or less than `1`;
- any required article artifact is missing or not an object;
- `article_risk_report.ready_for_proposal` is not `true`;
- `article_risk_report.risk_level` is `high`;
- `article_risk_report.blocked_claims` is not empty;
- the plan does not contain exactly one `write_action`;
- the action target is not `magick-ai/create-draft`;
- the draft input requests `status` or `post_status` other than `draft`;
- the action or input claims commit execution already happened;
- the action input requests `commit=true` or `dry_run=false`;
- the target ability is not currently discoverable as a proposal-governed
  write ability.

Core preserves the writing artifacts in proposal preview context for review,
preflight, and audit correlation. Core still returns `commit_execution=false`.

## Expansion Order

1. P0: single `magick-ai/create-draft` proposal.
2. P1: separate governed proposals for `magick-ai/set-post-seo-meta`,
   `magick-ai/set-post-terms`, `magick-ai/update-media-details`, and
   `magick-ai/set-post-featured-image` when the target abilities and Adapter
   execution profiles are ready.
3. P2: additional local recipe profiles for topic clusters or editorial
   calendars may be documented, but they must remain local Ability recipes and
   must not become Cloud writing generation.
4. P3: any batch behavior must be local, bounded, reviewable, and Core-governed.

## Boundary Guardrails

- Toolbox may store workflow draft state, but not approval truth.
- Abilities may execute standard callbacks, but not host governance.
- Core may validate and govern the plan, but not generate or execute content.
- Adapter may execute only after Core approval and preflight, and only through
  explicit execution profiles.
- Cloud Addon may provide hosted runtime enhancement, but not a second control
  plane, second workflow truth, or WordPress write owner.
