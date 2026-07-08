# Core Contract Reuse Readiness - 2026-07-08

Status: active observation record

This record closes the Governance Core observation pass after the
Cloud/Add-on/Toolbox `contract_reuse` stack was merged. The purpose is to
decide whether Core needs new implementation work before the next project
optimization pass.

## Scope

Core's role in the current reuse stack is `proposal_handoff`:

- consume real WordPress Abilities API ids from Toolkit or provider plugins;
- store reviewable proposal records;
- preserve Core proposal classification evidence;
- approve or reject proposals;
- issue commit preflight handoff context with `commit_execution=false`;
- record Adapter or host execution outcomes after preflight;
- maintain append-only audit truth.

The adjacent roles stay outside Core:

| Role | Owner |
| --- | --- |
| `ability_contracts` | `npcink-abilities-toolkit` or another WordPress Abilities API provider |
| `execution_profiles` | `npcink-ai-client-adapter` or another approved channel adapter |
| `product_surface` | `npcink-workflow-toolbox` or another product plugin |
| `signed_transport` | `npcink-cloud-addon` |
| `runtime_detail` | `npcink-ai-cloud` |

## Current Evidence

The current Core already has the hooks needed to receive reused contracts:

- `/contract` exposes proposal, approval, audit, classification, implementation
  posture, and Adapter handoff metadata.
- `/capabilities` normalizes provider-owned `implementation_posture` without
  making Core the ability implementation owner.
- proposal intake stores `preview.operation_classification` with
  `classification=core_proposal_required` and `intake_path=core_proposal`.
- plan-to-proposal intake accepts only allowlisted read-only planning outputs
  and converts them into proposals without executing the plan or target writes.
- commit preflight returns `execution_handoff` for Adapter/host execution and
  keeps `commit_execution=false`.
- record-execution stores external execution outcomes without becoming a Core
  execute route, retry worker, scheduler, queue, or workflow runtime.
- audit tables remain the proposal, approval, preflight, local-consent, read
  authorization, and external execution-result evidence trail.

## Active Observation Result

No new Core runtime code is needed for this pass.

The current contract surface is sufficient for the next repository to reuse
Core as the governance handoff layer. The important follow-up is not to add
Core features, but to keep future product and adapter work inside the existing
handoff discipline:

```text
product or adapter suggestion
-> real ability id
-> Core proposal or Core local-consent audit classification
-> Core approval/preflight when required
-> Adapter or host execution outside Core
-> Core record-execution or audit evidence
```

## Stop Rule

Stop and write a boundary note or ADR before implementing if a follow-up
requires Core to own any of these:

- reusable ability definitions;
- product workflow UX;
- model routing, prompt/preset truth, provider keys, or cloud billing;
- workflow runtime, task queues, retry workers, leases, schedulers, or batch
  execution consoles;
- MCP runtime, Agent Gateway catalogs, or OpenClaw projection truth;
- final WordPress write execution;
- Cloud runtime/detail, signed transport, or Site Knowledge lifecycle.

## Next Development Recommendation

End this Core observation pass here.

The next useful development slice should move to the next repository in the
reuse chain, not add new Core functionality. A good next slice is
`npcink-abilities-toolkit`: verify that representative ability contracts expose
stable ids, schemas, dry-run evidence, implementation posture, and host-owned
write callbacks that Core can continue to govern through the existing
`proposal_handoff` path.

## Verification

Required Core gate for this record:

```bash
composer test:all
```

Run `composer smoke:wp` only if a future change touches runtime behavior,
activation, tables, REST routing, Abilities API integration, proposal
lifecycle, commit preflight, or audit persistence.
