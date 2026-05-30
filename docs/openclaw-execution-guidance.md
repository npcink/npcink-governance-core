# OpenClaw Execution Guidance

Status: active Core contract.

This document records how Magick AI Core should guide OpenClaw and similar
agent clients without becoming an ability execution proxy.

The guidance aligns with
`/Users/muze/gitee/pdf/提炼资料/Agent_Gateway_Openclaw_开发建议.md`: OpenClaw
should operate WordPress through registered abilities, strict permission
callbacks, human approval for risky operations, and auditability. Core provides
the governance bridge for those operations, while WordPress Abilities API and
adapter layers remain the execution bridge.

## Positioning

Core is the OpenClaw governance bridge, not the OpenClaw execution gateway.

Layer ownership:

| Layer | Owner | Responsibility |
| --- | --- | --- |
| Ability layer | `magick-ai-abilities` and provider plugins | Register canonical abilities, schemas, callbacks, permission callbacks, risk metadata, and dry-run previews. |
| Governance layer | `magick-ai-core` | Discover abilities, classify risk, create proposals, record approval/rejection, run commit preflight, provide audit, and return execution guidance. |
| Channel layer | OpenClaw Adapter, MCP Adapter, or Agent Gateway plugin | Present tools to OpenClaw, call read abilities, and execute approved write abilities only after Core preflight. |

## Why Governance And Execution Stay Separate

The split is a sequencing and safety decision, not a permanent ban on future
execution work.

Governance answers these questions:

- is the operation known and currently available;
- what is its risk level;
- does it need human approval;
- who approved or rejected it;
- is commit preflight authorized;
- how can the decision be audited later.

Execution answers different questions:

- how to invoke the target ability callback;
- how to authenticate to the execution surface;
- how to validate and sanitize the ability result;
- how to handle timeout, retry, partial failure, rollback, and idempotency;
- how to redact sensitive read results;
- how to record final execution results without creating a second source of
  truth.

Combining both roles in Core now would make Core both the governance authority
and the generic ability runtime. That would force Core to own execution
semantics for every discovered ability, duplicate WordPress Abilities API as an
execution surface, and enter final commit execution before the authorization,
idempotency, failure, audit, and rollback contracts are accepted.

Keeping execution in the adapter and WordPress Abilities API lets Core remain
the trust center: it decides whether risky operations may proceed, while the
canonical ability layer performs the actual WordPress reads and writes.

## Tradeoff

Keeping Core as governance-only has costs:

- OpenClaw needs an adapter that can call both Core and WordPress Abilities API;
- read ability execution depends on the site exposing the WordPress Abilities
  API run surface;
- execution result auditing is not yet end-to-end inside Core;
- a future product may still want one simpler external URL.

The benefit is a smaller and safer contract:

- Core does not become a second ability runtime;
- read and write permissions stay with ability permission callbacks;
- write and destructive operations keep the proposal and approval boundary;
- OpenClaw can still receive machine-readable routing guidance from Core;
- final execution can be designed deliberately in a separate ADR instead of
  leaking into `/capabilities` as an accidental runtime promise.

## When To Reconsider Core Execution

Core execution can be reconsidered only through a separate ADR. That ADR should
define, at minimum:

- which ability classes Core may execute;
- whether the first step is read-only proxy execution or final write execution;
- required scopes for read, write, destructive, and diagnostics execution;
- approval context binding to ability id, input hash, caller, and proposal id;
- idempotency key requirements;
- timeout, retry, rollback, and partial failure behavior;
- execution result audit schema;
- sensitive read-result redaction;
- compatibility with WordPress Abilities API and adapter execution.

Until that ADR is accepted, Core must keep returning
`core_proxy_execute=false` and `commit_execution=false`.

## Capability Guidance Fields

`GET /wp-json/magick-ai-core/v1/capabilities` includes machine-readable
guidance on each capability row:

| Field | Values | Meaning |
| --- | --- | --- |
| `governance_mode` | `direct_read`, `proposal_required` | Whether the adapter may call a read ability directly or must start a Core proposal. |
| `execution_surface` | `wp_abilities_rest`, `adapter_after_core_preflight` | Where the adapter should route execution after reading Core guidance. |
| `core_proxy_execute` | `false` | Core does not proxy ability execution. |
| `commit_execution` | `false` | Core does not execute final WordPress mutation from capability discovery or commit preflight. |

Core does not return concrete WordPress Abilities API execution URLs. The
adapter owns URL construction, authentication, nonce/application-password
handling, transport retries, and client-specific presentation.

## Read Ability Flow

For rows with:

```json
{
  "governance_mode": "direct_read",
  "execution_surface": "wp_abilities_rest",
  "core_proxy_execute": false
}
```

The adapter may call the canonical WordPress Abilities API execution surface,
subject to the ability permission callback and site authentication policy. Core
is not required unless the host wants governance discovery or audit context.

Examples include site context, diagnostics, and workflow recipe read helpers.

## Write Or Destructive Flow

For rows with:

```json
{
  "governance_mode": "proposal_required",
  "execution_surface": "adapter_after_core_preflight",
  "core_proxy_execute": false,
  "commit_execution": false
}
```

The adapter must:

1. create a Core proposal with the real `ability_id`;
2. wait for WordPress human approval or a separately contracted trusted host
   policy;
3. call Core commit preflight;
4. execute the target WordPress ability only if its contract accepts Core
   approval context and idempotency protection.

Core still does not execute the final WordPress mutation.

## Proposal Status Bridge

Productized OpenClaw clients should connect to Magick AI Adapter rather than
directly to Core. The adapter may expose a thin read-only bridge for Core
proposal status so an agent can poll or display the lifecycle after it creates
a proposal:

- `GET /proposals`;
- `GET /proposals/{proposal_id}`.

Those adapter routes should forward to the matching Core governance routes and
use a Core app key with `proposals:read`. This is a usability bridge, not an
approval bridge. It lets OpenClaw see whether a proposal is `pending`,
`approved`, or `rejected`, and it may surface Core proposal detail fields such
as preview data and `audit_timeline` when available.

The adapter should not expose `POST /proposals/{proposal_id}/approve` or
`POST /proposals/{proposal_id}/reject` by default. Approval and rejection are
governance decisions owned by Core and the WordPress admin surface unless a
separate trusted host policy is explicitly documented. If a future adapter adds
an approval proxy, it must be disabled by default, require
`proposals:approve` or `proposals:reject`, use a separate trusted key where
possible, and preserve app/key/caller audit attribution.

## Non-Goals

Do not add these to Core as part of execution guidance:

- `/execute`, `/proxy-execute`, or generic ability run routes;
- MCP server runtime;
- Agent Gateway task catalog;
- workflow runtime, queue, retry, or long-task scheduler;
- full WordPress Abilities API execution URL generation;
- final commit execution without a separate accepted ADR.

## Next Adapter Work

The next OpenClaw-side work should happen in a dedicated adapter or gateway
module:

- read `governance_mode` before choosing a call path;
- proxy Core proposal list/detail reads for productized clients that need
  proposal status polling;
- call read abilities through WordPress Abilities API;
- call Core proposal/preflight for write and destructive abilities;
- keep approve/reject out of the adapter default surface unless a separate
  trusted host approval policy is accepted;
- keep adapter transport, tool presentation, and OpenClaw-specific behavior out
  of Core.
