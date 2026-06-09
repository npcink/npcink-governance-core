# Current Stage Closeout And Handoff

Status: active handoff note.

This note summarizes the current-stage product and governance discussion. It is
written for future maintainers and implementation agents who need to understand
why Governance Core stops here, and where the remaining product work should
continue.

## Context

The original product questions covered these candidate capabilities:

- batch SEO / GEO optimization suggestions;
- media alt text completion;
- pre-publish article checks;
- category and tag governance;
- safe draft writing and human approval.

The useful product direction is not to turn Core into a content assistant,
workflow runtime, or direct WordPress writer. The direction is a governed
feedback loop:

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

Core is the trust kernel for the loop. Product modules produce the diagnosis,
recommendations, and review UI. Ability providers define WordPress operations
with schemas, previews, and callbacks. Adapter or product modules execute only
after the correct authorization path.

## Historical Summary

### 1. Core boundary was confirmed

Core owns proposal intake, proposal records, approval and rejection state,
commit preflight, app-key policy, and audit truth. It does not own article,
media, SEO, taxonomy, or toolbox product workflows. It also does not own model
routing, provider credentials, prompt presets, workflow queues, MCP runtime, or
final WordPress write execution.

The standing ADRs reinforce this boundary:

- ADR-001 rebuilds Core as a governance layer.
- ADR-002 keeps workflow runtime outside Core.
- ADR-003 keeps final execution outside Core for the current stage.
- ADR-004 allows product-suite consolidation without collapsing authority
  boundaries.
- ADR-005 keeps Core independent while standardizing channel adapters.

### 2. The first practical product proof became Content Metadata Delta

The first closed-loop slice was narrowed to one existing post and its metadata:

- understand the post and related-content context;
- recommend a better excerpt, categories, and tags;
- prefer existing taxonomy terms;
- keep new term candidates review-only;
- preserve evidence for human review;
- send accepted write-like changes through the correct authorization path.

This avoided the unsafe and over-broad frame of "AI writes articles" while
still improving real WordPress content operations.

### 3. Toolbox owns the product workbench

Toolbox is the correct place for operator-facing recommendation UX, content
support panels, editor affordances, and reviewed metadata choices. The local
WordPress `ai` plugin already overlaps with generic editor AI suggestion
surfaces, so Toolbox should not become another direct-generation surface. The
better framing is "Core handoff candidates": previewed, proposal-ready changes
that a human can inspect before they become governed WordPress operations.

Toolbox-side Content Metadata Delta work improved ranking by using related Site
Knowledge context and existing taxonomy evidence. That remained suggestion-only
until the operator accepted specific metadata choices.

### 4. Core accepted only the governed handoff shape

Core now accepts the Toolbox
`npcink-toolbox/build-content-metadata-apply-plan` output only as a reviewed
`content_metadata_apply_plan`. It validates the plan before creating one
pending batch proposal:

- one target post;
- dry-run and non-commit write actions;
- `npcink-abilities-toolkit/update-post` only for excerpt updates;
- `npcink-abilities-toolkit/set-post-terms` only for existing `category` and
  `post_tag` term assignments;
- no new term creation;
- at most one excerpt action, one category action, and one post-tag action.

The duplicate-slot guardrail was added after the first handoff implementation
so a single metadata intent cannot smuggle multiple competing updates for the
same reviewed slot.

### 5. Operation classification now guides future handoffs

The Operation Classification Contract provides the shared decision rule:

- `suggestion_only`: no WordPress write;
- `local_admin_consent`: present WordPress admin, one bounded low-risk object,
  exact or sufficient preview, and audit/activity evidence;
- `strong_local_confirmation`: one high-impact visible result that needs a
  stronger local confirmation or Core proposal depending on risk;
- `core_proposal_required`: external, automated, batch, destructive,
  high-impact, or insufficiently previewed actions.

The completed proofs are:

- low-risk Toolbox featured-image assignment using Local Admin Consent audit;
- high-risk article/media batch handoff using Core proposal review;
- media optimization classified as a Core proposal path when it is a batch plan,
  even for one attachment.

## Current Feature Disposition

| Capability | Current decision | Where it belongs next |
| --- | --- | --- |
| Batch SEO / GEO optimization suggestions | Still useful, but not a Core product feature. Core should only govern a reviewed plan if the operation becomes write-like or batch. | Toolbox for recommendation and review UX; Abilities Toolkit for reusable SEO/meta write abilities; Adapter/product module for post-approval execution. |
| Media alt text completion | Still useful, but Core should not generate alt text or own media workflow UI. | Toolbox or media product UI for suggestions; Abilities Toolkit for media metadata abilities and previews; Core only for proposal-required handoff. |
| Pre-publish article checks | Useful as suggestion-only diagnostics and review evidence. It should not become automatic publishing or a Core checklist engine. | Toolbox/article workbench for checks; Abilities/read helpers for facts; Core only when accepted changes require proposal review. |
| Category and tag governance | Current P0 is implemented for accepted existing-term choices through `content_metadata_apply_plan`. New-term creation remains review-first and stronger-governance work. | Toolbox for candidate UX and evidence; Abilities Toolkit for term preview/set-term abilities; Core for reviewed apply plans and guardrails. |
| Safe draft writing and human approval | Core already supports proposal records, approval/rejection, commit preflight, and audit. Final execution remains outside Core. | Toolbox or Adapter submits reviewed draft plans; Abilities execute only after Core approval and preflight. |

## Why Core Stops Here

Core has enough current-stage support to govern the narrow handoff:

- it can admit supported read-only planning outputs through
  `/proposals/from-plan`;
- it can store one pending proposal or a bounded `plan_to_proposal_batch`;
- it preserves preview and caller context for review;
- it supports approval, rejection, commit preflight, and audit;
- it fails closed for unsupported actions, taxonomy creation, direct writes,
  duplicate metadata slots, and untrusted plan shapes.

Adding recommendation generation, taxonomy strategy, alt text drafting, article
checks, feedback storage, learning loops, or final write execution inside Core
would duplicate product or ability ownership and weaken the governance
boundary. Future Core work should therefore be limited to contract defects,
fail-closed guardrails, public REST/data-shape documentation, and regression
coverage for already accepted handoff shapes.

## Recommended Next Sequence

1. Continue product work in Toolbox:
   - refine Content Metadata Delta review UX;
   - expose clear accepted/rejected choices;
   - keep AI output evidence-rich and suggestion-only until the operator acts;
   - submit only reviewed apply plans to Core.
2. Continue reusable WordPress operation work in `npcink-abilities-toolkit`:
   - harden post excerpt and term assignment schemas;
   - add or improve dry-run previews where needed;
   - keep callbacks permission-checked and idempotent where possible.
3. Continue adapter/product execution work outside Core:
   - execute only after Core approval and commit preflight;
   - preserve proposal id, app identity, actor identity, and correlation ids;
   - report execution outcomes through the documented future contract when it
     exists.
4. Return to Core only when a real handoff exposes a governance gap:
   - unsupported but accepted plan shapes;
   - missing fail-closed validation;
   - public contract drift;
   - review/audit evidence that Core must preserve.

## Stop Criteria For This Core Stage

For the current stage, the answer to "is this done in Core?" is yes when:

- Core can classify or govern the action through the documented contracts;
- proposal-required writes enter Core as dry-run, non-commit plans;
- Core rejects direct writes and unsupported plan shapes;
- approval and commit preflight remain separate from final execution;
- tests and smoke coverage prove the accepted handoff path.

The product is not finished, but Core's current role is complete enough. The
next work should happen in Toolbox, Abilities Toolkit, or adapter execution
layers unless a specific Core contract fails.
