# Reference Plugin Evaluation Development Summary - 2026-07-08

Status: accepted historical summary and Core-local boundary guide.

Authority note: the active cross-project checklist should live in the platform
coordination lane, starting from
`/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`. This Core
document records the Governance Core perspective only. It must not become a
Toolbox product checklist, Cloud runtime plan, adapter catalog, or ability
definition registry.

## Why This Stage Happened

The stage started from one practical question:

```text
Do the current projects already overlap with capabilities found in mature
plugins, and can we learn from those plugins instead of rebuilding everything
from scratch?
```

The answer was yes, but with a constraint: useful reference-plugin ideas must
be decomposed into capability, contract, ownership, and verification pieces
before implementation. Copying a mature plugin's whole product shape would
collapse the current Npcink boundaries and make the stack heavier.

## What Was Learned

Reference plugins are useful as pattern sources, not as ownership models.

The reusable lessons are:

- queue and audit screens can improve Core proposal readability;
- ability and schema examples can improve provider contract clarity;
- webhook and integration plugins can improve Adapter feedback;
- fixed workflow plugins can improve Toolbox preview and confirmation UX;
- SaaS connector diagnostics can improve Cloud Addon and Cloud evidence;
- none of those patterns justify moving execution, queues, provider keys,
  billing, workflow runtime, or product UX into Core.

## Current Project Split

The working split after the reference-plugin and contract-reuse pass is:

| Role | Owning project |
| --- | --- |
| `ability_contracts` | `npcink-abilities-toolkit` or another WordPress Abilities API provider |
| `proposal_handoff` | `npcink-governance-core` |
| `execution_profiles` | `npcink-ai-client-adapter` or another approved channel adapter |
| `product_surface` | `npcink-workflow-toolbox` or another product plugin |
| `signed_transport` | `npcink-cloud-addon` |
| `runtime_detail` | `npcink-ai-cloud` |

Core's lane is intentionally narrow. It can consume real ability ids, record
proposals, approve or reject proposals, issue commit preflight, preserve
classification evidence, and audit lifecycle events. It does not implement the
reference plugin's product workflow, runtime engine, job queue, model routing,
provider credentials, billing, or final WordPress write execution.

## What The Previous Stage Delivered

The previous stage did not add a broad feature. It produced operating
discipline:

- a reference-plugin action plan with one small improvement lane per project;
- a Core contract-reuse observation showing that no new Core runtime code was
  needed for the current pass;
- cross-repo release and observation discipline in the platform lane;
- GitHub-first repository management for the current project family;
- a clean baseline for choosing one narrow follow-up instead of adding more
  scope.

For Core, the important output is this rule:

```text
product or adapter suggestion
-> real ability id
-> operation classification
-> Core proposal or Core local-consent audit when required
-> Core approval/preflight if required
-> Adapter or host execution outside Core
-> Core record-execution or audit evidence
```

## Next Stage Shape

The next stage should implement a lightweight Reference Plugin Evaluation
Checklist in the platform coordination lane. The checklist should help future
work decide:

- what capability is worth borrowing;
- which repository owns that capability;
- whether the result is documentation only, a static contract, a
  suggestion-only product surface, or a Core-governed handoff;
- which boundary blocks the idea;
- which gate proves the idea is safe enough to keep.

Core should participate by answering only governance questions:

- Does the external pattern imply a write, destructive action, sensitive read,
  automated action, or delegated action?
- Does it need `suggestion_only`, `local_admin_consent`,
  `strong_local_confirmation`, or `core_proposal_required` classification?
- Is the target a real currently discoverable ability id?
- Does Core need to store proposal, approval, preflight, read authorization,
  local-consent audit, or execution-result evidence?
- Would the idea require Core to own anything forbidden?

## Core Stop Rules

Stop and write a boundary note or ADR before implementing in Core if a
reference-plugin idea requires Core to own any of these:

- second ability registry;
- second workflow registry;
- product workflow UX;
- workflow runtime, jobs, tasks, runs, queues, schedulers, workers, or leases;
- Agent Gateway catalogs or MCP runtime;
- provider credentials, prompt presets, model routing, cloud billing, or SaaS
  account state;
- final WordPress write execution;
- Cloud runtime/detail or signed transport;
- silent WordPress writes outside the existing Core-governed or audited paths.

## Complexity Decision

This stage was worth doing because it reduced uncertainty without adding
runtime weight. The useful additions were records, gates, and decision rules.
They make the project faster to develop because the next agent can quickly
decide whether an external plugin idea belongs in Core, Toolkit, Adapter,
Toolbox, Cloud Addon, or Cloud.

It would become too complex only if the checklist turned into a new platform
inside Core. Keep it as an intake and decision artifact until a real,
repo-owned implementation slice passes the owning repository's gate.

## Verification Guidance

Use the narrowest gate that matches the change:

| Change type | Gate |
| --- | --- |
| Core documentation or static boundary record | `composer test:all` |
| Core REST, tables, proposal lifecycle, preflight, audit, or ability intake | `composer test:all` and `composer smoke:wp` |
| Multi-repo milestone or release closeout | central matrix from `npcink-workflow-toolbox` |
| Product workflow or runtime evidence outside Core | owning repository gate first, then central matrix when closing a milestone |

Do not add a new Core dependency or implementation path solely to evaluate a
reference plugin. If a dependency becomes necessary, record the decision in a
new ADR before implementation.
