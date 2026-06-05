# Agent MCP Entry Contract

Status: planning contract; runtime implementation not started.

Npcink Governance Core is MCP-aware, but it is not an MCP runtime. WordPress, a
dedicated MCP adapter, or an agent host may expose WordPress abilities to
agents. Core only provides the governance entrypoints that decide whether an
operation needs approval, who approved it, whether it can proceed to commit,
and how the lifecycle is audited.

## Source Contracts

This contract is aligned with the current local truth boundaries in:

- `/Users/muze/gitee/npcink-root/npcink-abilities-toolkit/docs/contracts/channel-delivery-matrix-v1.md`
- `/Users/muze/gitee/npcink-root/npcink-abilities-toolkit/docs/contracts/hosted-model-runtime-v1.md`
- `/Users/muze/gitee/npcink-root/npcink-abilities-toolkit/docs/contracts/cloud-responsibility-boundary-v1.md`
- `/Users/muze/gitee/npcink-root/npcink-abilities-toolkit/docs/contracts/cloud-skill-execution-v1.md`

The useful rule from those contracts is: channel adapters may present abilities
through MCP, OpenAPI, Agent Gateway, or another client surface, but they must
not create channel-private schema, scope, approval, workflow, or write truth.

## Product Sentence

Agent and MCP adapters expose abilities. Core governs risky operations.
WordPress stores final state.

## Actors

| Actor | Responsibility |
| --- | --- |
| WordPress Abilities API | Canonical ability discovery and execution surface. |
| `npcink-abilities-toolkit` or provider plugins | Stable `ability_id`, schemas, permission callbacks, risk metadata, read helpers, and host-governed write/destructive abilities. |
| MCP adapter or agent host | Channel transport, tool presentation, agent request validation, and actual WordPress Abilities API calls. |
| `npcink-governance-core` | Ability intake, proposal records, approval/rejection, commit preflight, app policy, and audit. |
| Product plugin | Domain workflow, UX, previews, and user-facing orchestration. |

## Allowed Core Shape

Core may expose governance REST routes consumed by adapters:

- list normalized capabilities;
- return capability execution guidance such as `governance_mode`,
  `execution_surface`, `core_proxy_execute=false`, and
  `commit_execution=false`;
- create a proposal for a target `ability_id`;
- approve or reject proposals;
- return commit preflight authorization context;
- list audit records;
- later authenticate callers by app identity and scopes.

Core may store proposal input, preview payloads, caller metadata, approval
metadata, and audit metadata after sanitization.

## Forbidden Core Shape

Core must not implement:

- an MCP server;
- MCP tool catalogs;
- Agent Gateway task catalogs;
- OpenAPI tool projection;
- natural language routing;
- workflow runtime, queues, retries, leases, or batch consoles;
- channel-specific schema, scope, approval, or write semantics;
- short-name ability aliases such as `site/read`;
- final WordPress write execution before idempotency and failure contracts are
  documented, implemented, and tested.

## Adapter Call Flow

For read-only abilities:

1. Adapter discovers abilities through WordPress Abilities API or through Core
   capability intake when it needs Core risk metadata and execution guidance.
2. Adapter calls the read ability through WordPress Abilities API.
3. Core is not required unless the host chooses to list or audit governance
   context.

For write or destructive abilities:

1. Adapter discovers the real `ability_id`.
2. Adapter or product plugin prepares a preview, diff, dry-run payload, or
   human-readable handoff.
3. Adapter calls `POST /wp-json/npcink-governance-core/v1/proposals`.
4. Adapter may poll proposal status through Core `GET /proposals/{proposal_id}`
   or through a dedicated adapter read proxy that forwards to Core with
   `proposals:read`.
5. A human or separately documented trusted host policy approves or rejects the
   proposal.
6. Adapter calls
   `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`.
7. Core returns approval context with `commit_execution=false`.
8. Adapter calls the target WordPress ability only if the ability contract
   accepts Core approval context and idempotency protection.
9. Adapter or provider records commit result through future Core audit/commit
   contracts when those exist.

## Commit Preflight Handoff

Commit preflight is an authorization handoff, not execution.

The current context shape is:

```php
array(
	'approval_commit_authorized' => true,
	'confirmation_state'        => 'approved_commit',
	'proposal_id'               => '<core proposal id>',
	'ability_id'                 => '<target ability id>',
	'correlation_id'            => '<preflight correlation id>',
	'approved_input_hash'        => '<sha256>',
	'approved_preview_hash'      => '<sha256>',
	'approval_updated_at'        => '<utc timestamp>',
	'policy_version'             => 'core-preflight-v1',
)
```

An adapter must treat this context as scoped to:

- the proposal id;
- the real `ability_id`;
- the approved input or approved input hash;
- the caller/app identity;
- the correlation id;
- the approval timestamp and policy version.

## Channel Projection Rules

MCP, OpenAPI, Agent Gateway, and other channel surfaces may format tools
differently, but they must point back to the same canonical `ability_id` and
schema source.

Adapters must not:

- widen channel exposure when an ability or host policy blocks it;
- rebuild schemas as a second source of truth;
- remap workflow or ability ids for channel convenience;
- override Core approval requirements;
- treat channel-local confirmation as Core approval;
- expose Core approve/reject proxy routes by default for generic OpenClaw or
  MCP clients;
- execute write/destructive abilities when Core preflight fails closed.

## Minimum First Integration

The first useful integration is a thin external governance adapter:

1. Keep WordPress/MCP Adapter outside Core.
2. Use existing Core REST routes for governance.
3. Add app identity, scope, rate-limit, and audit attribution contracts before
   accepting non-`manage_options` callers.
4. Do not add a final commit execution route until idempotency and failure
   handling are specified.

The reference CLI example lives at
`examples/openclaw-governance-adapter/`. It demonstrates capabilities
discovery, proposal creation, and commit preflight over HTTP. It intentionally
does not expose MCP tools, approve proposals, execute abilities, or route
natural language tasks.

See [OpenClaw Execution Guidance](openclaw-execution-guidance.md) for the
machine-readable fields that let adapters choose between direct read execution
through WordPress Abilities API and proposal-required governance through Core.
