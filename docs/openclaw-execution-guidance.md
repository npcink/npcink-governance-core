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
- call read abilities through WordPress Abilities API;
- call Core proposal/preflight for write and destructive abilities;
- keep adapter transport, tool presentation, and OpenClaw-specific behavior out
  of Core.
