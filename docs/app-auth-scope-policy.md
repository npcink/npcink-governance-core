# App Auth Scope Policy

Status: minimal implementation active.

Npcink Governance Core supports the original `current_user_can( 'manage_options' )`
admin path and a minimal scoped app-key path for external governance clients.
Agent, MCP, product-plugin, and hosted adapter entry must use scoped app
identity when they are not operating as a WordPress administrator.

This is not an OAuth portal, MCP session system, provider credential store, or
workflow runtime.

## Goals

- Identify which app, adapter, or host created each governance request.
- Limit each app to explicit scopes.
- Rate-limit app traffic without moving policy into provider plugins.
- Attribute proposals, approvals, preflight calls, and audit reads.
- Fail closed when identity, scope, rate, or audit checks cannot be completed.

## Non-Goals

Core app auth must not become:

- provider credential storage;
- model routing;
- cloud customer API key management;
- MCP session management;
- OAuth portal implementation;
- workflow or task runtime;
- a replacement for WordPress user capability checks.

## App Identity Model

Core app identity includes:

| Field | Purpose |
| --- | --- |
| `app_id` | Stable public app identifier used in audit and caller metadata. |
| `app_label` | Human-readable admin label. |
| `key_id` | Public key identifier for rotation and audit. |
| `secret_hash` | Hash of the app secret. Never store raw app secrets. |
| `status` | Currently `active`; `revoked` and `expired` are reserved states. |
| `scopes` | Explicit allowed actions. |
| `rate_limit` | Requests allowed per route-family window. |
| `rate_window_seconds` | Fixed-window duration. |
| `created_by` | WordPress user id that created the app identity. |
| `created_at` | UTC creation time. |
| `last_used_at` | Last successful authentication time. |

Raw secrets, bearer tokens, HMAC secrets, request signatures, cookies, and
authorization headers must not be stored in proposal or audit metadata.

## Initial Scope Set

Use additive scopes. Do not infer permissions from app names, channel names, or
ability ids.

| Scope | Allows |
| --- | --- |
| `capabilities:read` | Read normalized Core capability rows. |
| `proposals:create` | Create proposals for real `ability_id` values. |
| `proposals:read` | List or fetch proposal records. |
| `proposals:approve` | Approve proposals when a trusted host policy is allowed to do so; also authorizes `local_guarded` cleanup auto approval when paired with `proposals:create`. |
| `proposals:reject` | Reject proposals. |
| `commit:preflight` | Request Core-generated approval context for approved proposals. |
| `audit:read` | Read audit events. |

Future scopes may add ability-family constraints, but the first version should
avoid wildcard write semantics.

## Scope Rules

- Read-only capability discovery requires `capabilities:read`.
- Proposal creation requires `proposals:create`.
- Proposal approval is high risk and requires `proposals:approve`; most MCP
  adapters should not receive this by default.
- Productized Magick AI Adapter may receive `proposals:approve` only as a
  separately issued trusted Adapter key. That key represents a host-controlled
  UI or policy that has already presented the proposal preview and risk context
  to the user.
- In `local_guarded` policy mode, an app-created cleanup batch can be
  auto-approved only when the app key carries both `proposals:create` and
  `proposals:approve`; Core still requires trusted test-content evidence,
  quotas, audit, and commit preflight.
- Commit preflight requires `commit:preflight` and an approved proposal.
- Audit listing requires `audit:read`.
- Write/destructive WordPress execution is outside Core until final commit
  execution is separately designed.

Scopes do not replace WordPress permissions. When a request maps to a WordPress
user, Core should still require the relevant WordPress capability or a trusted
host policy that is explicitly documented.

## Rate Policy

The first app-key implementation supports a simple fixed-window limit:

- per `app_id`;
- per route family;
- bounded to a minimum one-minute window;
- default `60` requests per `3600` seconds.

Rate events should be auditable without storing secrets or raw request bodies.
Rate limit denials emit `app.rate_limited`.

Proposal creation has an additional pending queue guardrail. App-authenticated
callers may have at most 20 pending proposals at a time, and administrator
callers may have at most 1000 pending proposals per user. Repeated creation of
the same `ability_id` with the same sanitized `input` by the same caller
returns the existing pending proposal with `deduplicated=true` instead of
creating another row. Quota denials return
`magick_ai_core_pending_proposal_quota_exceeded` with HTTP `429`.

## Audit Attribution

Every app-authenticated governance event includes `metadata.auth` with:

- `app_id`;
- `key_id` when available;
- `caller_type`, such as `mcp_adapter`, `agent_host`, `product_plugin`, or
  `internal`;
- required `scope`;
- `scope_decision`, currently `allowed`, `denied`, or `rate_limited`;
- `route_family`.

The existing `actor_id` column continues to hold WordPress user identity. Pure
app-key requests normally record `actor_id=0` and app attribution in metadata.
Proposal rows also copy the sanitized app auth context into `caller.auth`.

Audit reads can filter by `app_id`, `key_id`, `caller_type`, and
`correlation_id` so operators can trace a specific external app or
commit-preflight response without exposing raw app secrets.

## Authentication Shape

The current implementation uses a bearer app token:

```text
Authorization: Bearer mai_core.<key_id>.<secret>
```

The same token may be sent as `X-Magick-AI-Core-App-Token` for clients that
cannot set the `Authorization` header.

WordPress administrators can issue tokens from either admin-only
`POST /wp-json/npcink-governance-core/v1/apps` or the `Npcink -> Core`
`Advanced Access` entry. Both paths use the same app identity store, default
scope policy, and one-time raw-token display rule. The admin panel keeps app
keys behind a low-frequency disclosure because it is a Core credential
management fallback, not the primary OpenClaw product setup flow.
Productized OpenClaw setup should use Magick AI Adapter, which calls Core for
governance and WordPress Abilities API for direct reads.

The admin panel also exposes a minimal key-disable action. Disabling a key marks
its status as `revoked`; future requests with that token return `401`, while
historical proposal and audit attribution remains intact.

LocalWP TLS switches, OpenClaw handoff text, and agent rules belong in
Magick AI Adapter or another client-side adapter layer. Core does not export
`NPCINK_GOVERNANCE_CORE_INSECURE_SSL`, adapter base URLs, or OpenClaw instructions from
the app-key screen.

Minimum requirements:

- raw secret is shown once at creation and stored only as a hash;
- inactive, expired, revoked, or invalid keys return `401`;
- missing scope returns `403`;
- malformed requests return `400`;
- rate-limit failures return `429`;
- all failure responses use stable error codes;
- successful calls update `last_used_at`.

## Initial Consumer Defaults

Recommended defaults:

| Consumer | Default scopes |
| --- | --- |
| MCP adapter | `capabilities:read`, `proposals:create`, `proposals:read`, `commit:preflight` |
| Product plugin | `capabilities:read`, `proposals:create`, `proposals:read` |
| Trusted Magick AI Adapter approve-and-execute path | `capabilities:read`, `proposals:create`, `proposals:read`, `proposals:approve`, `commit:preflight` |
| Human admin UI | WordPress `manage_options`; no app key required. |
| Hosted runtime callback | No default Core access until callback identity is separately contracted. |

Do not grant `proposals:approve` or `audit:read` by default to generic MCP
adapters. A trusted Adapter approval key should be separate from generic agent
keys where practical, should not include `audit:read` by default, and should be
revoked if the Adapter UI or host policy is no longer trusted.

## Implementation Gates

Current implementation gates:

1. Database schema for app identities and rate counters is documented.
2. REST authentication and error semantics are documented.
3. Security storage and redaction behavior is documented.
4. Admin UI can issue one-time scoped app tokens from a dedicated Core App
   Keys view, point productized OpenClaw setup to Magick AI Adapter, and
   disable leaked or obsolete keys without exporting Adapter onboarding content.
5. Static contract tests cover scopes, UI entry, revocation, and forbidden
   secret storage.
6. WordPress smoke covers app-authenticated proposal creation, commit preflight,
   denied generic approval, trusted Adapter approval, denied audit access, rate
   limiting, scope-decision attribution, and app audit filters.
