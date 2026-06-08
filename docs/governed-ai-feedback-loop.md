# Governed AI Feedback Loop

Status: active planning guide.

This document records the first-principles product model behind Npcink AI's
WordPress operation strategy. It is a planning guide for product modules,
ability providers, adapters, and future implementation agents. It does not
move product workflow, final execution, provider routing, or runtime ownership
into Governance Core.

## First-Principles Ladder

The product should not stop at "AI writes articles." That is the legacy CMS
plugin frame applied to a new production tool.

The useful abstraction ladder is:

1. WordPress is not only a blog writer. It is an extensible site data,
   identity, content, commerce, permission, and plugin capability platform.
2. WordPress operations are state transitions: a current site state changes
   into a target state.
3. Users do not buy state transitions for their own sake. They want business
   outcomes such as better inquiry conversion, content discovery, search
   visibility, trust, compliance, speed, or lower operating risk.
4. The operational unit is the delta between the current site state and the
   desired business outcome.
5. In practice, users often cannot describe the delta. They express vague pain:
   "this page has no effect," "content has no traffic," "the site feels wrong,"
   or "WordPress is hard to operate."
6. The product must turn vague pain and site signals into a framed problem,
   diagnose likely causes, propose a bounded delta, govern execution, measure
   the result, and retain what was learned.

The resulting model is:

```text
Vague Pain
-> Signal Intake
-> Problem Framing
-> Diagnosis
-> Delta
-> Proposal or Local Consent
-> Execution
-> Measurement
-> Learning
```

This is the governed AI feedback loop for WordPress.

## Operating Model

The product should treat WordPress as an observable, diagnosable, changeable,
and learnable business interface.

AI's job is not to be a writer, publisher, or unchecked automation worker. AI's
job is to:

- model a vague business intent into inspectable WordPress objects and signals;
- identify the likely delta between current state and desired outcome;
- turn that delta into a bounded plan;
- preserve evidence, uncertainty, and risk;
- hand off write-like actions through the correct authorization path;
- help compare the post-change result with the expected outcome;
- retain structured lessons for the next loop.

Governance Core's job is not to own the workbench. Core remains the trust
kernel for risky operations: proposal records, approval/rejection, commit
preflight, and audit. Product modules own workbench UX and workflow artifacts.
Ability providers own WordPress Abilities API definitions, previews, callbacks,
and final execution outside Core.

## Current AI Capability Boundary

Current AI systems are good enough for:

- summarizing site signals and page context;
- producing diagnostic hypotheses;
- ranking possible causes with evidence strength;
- generating structured deltas and JSON-shaped plans;
- recommending excerpts, metadata, tags, categories, SEO/GEO improvements, and
  editorial changes;
- explaining uncertainty and needs-human-input fields;
- supporting review, approval, and audit.

They are not reliable enough to own:

- unattended business-causality decisions;
- automatic publishing;
- unattended batch site changes;
- legal or compliance truth without human review;
- external account configuration changes;
- destructive or hard-to-reverse WordPress mutations.

The first product loops must therefore be controlled, inspectable, and small.
AI may recommend and plan. WordPress state changes must follow the
[Operation Classification Contract](operation-classification-contract.md).

## Loop Artifacts

Each closed-loop scenario should preserve three durable artifact types.

### Issue Record

An issue record captures the user's original pain and the system's framed
problem.

Recommended fields:

- `issue_id`;
- `source_module`;
- `user_expression`;
- `target_object_type`;
- `target_object_id`;
- `observed_signals`;
- `context_refs`;
- `diagnostic_hypotheses`;
- `selected_hypothesis`;
- `delta_summary`;
- `authorization_path`;
- `proposal_id` or local consent evidence;
- `execution_status`;
- `measurement_status`;
- `learning_status`.

### Outcome Contract

An outcome contract states what will count as improvement before the operation
is executed.

For early content metadata loops, outcome contracts should use directly
verifiable measures, not broad business claims. Examples:

- the post has a reviewed excerpt within the accepted length range;
- taxonomy assignments reuse existing terms when possible;
- new term creation is separately reviewed or disabled in P0;
- the suggested category matches the chosen evidence;
- the final preview matches the approved write input;
- the user accepted, edited, or rejected each recommendation.

### Learning Store

The learning store should start as structured memory, not model fine-tuning.

Recommended entries:

- accepted taxonomy patterns;
- rejected tags, categories, or wording patterns;
- site-specific terminology;
- preferred excerpt style;
- common content gaps by site or industry;
- false diagnoses;
- operations that required stronger review than initially expected.

This allows the product to improve without creating an unsafe black-box
self-training system.

## First Closed Loop: Content Metadata Delta

The first practical loop should be narrower than full article generation:

> Given one existing post, its content, existing taxonomy, and vector-related
> content context, recommend or apply a better excerpt, tags, and categories.

This loop fits the new model because it improves content structure and
discoverability without turning the product into an article generator.

### Inputs

- target post title, body, excerpt, status, categories, and tags;
- top related posts from vector search, including ids, titles, snippets,
  similarity scores, and existing terms;
- site taxonomy inventory;
- optional operator intent such as "improve summary," "improve discovery,"
  "improve B2B knowledge structure," or "improve related-content matching";
- existing site learning records when available.

### Outputs

- recommended excerpt or summary;
- existing category recommendation with reason and confidence;
- existing tag recommendations with reason and confidence;
- optional new term candidates, clearly separated from existing-term reuse;
- evidence references from the target post and related posts;
- warnings for duplicate, overly broad, or taxonomy-polluting terms;
- a structured delta suitable for review and, when allowed, execution.

### Authorization

Use the operation classifier before any write.

Recommended P0 behavior:

- AI recommendation only: `suggestion_only`;
- one present admin applying a fully previewed excerpt or existing-term update
  to one post: eligible for `local_admin_consent` with audit/activity evidence;
- new term creation: recommendation-only in P0, or use stronger review;
- batch metadata updates, external adapter calls, scheduled runs, or any action
  with incomplete preview: `core_proposal_required`.

### Non-Goals

The P0 content metadata loop must not:

- generate or rewrite full articles;
- publish content;
- create many taxonomy terms automatically;
- run as an unattended batch job;
- become a Cloud writing or Cloud taxonomy service;
- bypass Core when classification requires proposal review.

## Product Naming

Use language that keeps the team in the new frame:

- governed AI feedback loop;
- content metadata delta;
- diagnosis workbench;
- issue record;
- outcome contract;
- learning store;
- reviewed delta proposal;
- local admin consent when classification permits.

Avoid language that pulls the product back into the old frame:

- AI writer;
- article generator;
- automatic publisher;
- Cloud writing;
- bulk article writer;
- auto taxonomy engine.

## Implementation Rule

Every future product slice should state:

1. the vague pain or signal it starts from;
2. the issue record it creates;
3. the diagnosis and delta it produces;
4. the authorization path it uses;
5. what execution writes, if anything;
6. how the result is measured;
7. what gets written to the learning store.

If a slice cannot answer those seven questions, it is still a feature idea, not
a closed loop.
