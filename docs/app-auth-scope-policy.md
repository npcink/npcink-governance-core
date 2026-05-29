# App Auth Scope Policy

Status: planning contract; not implemented.

Magick AI Core currently protects all REST routes with
`current_user_can( 'manage_options' )`. Agent, MCP, product-plugin, and hosted
adapter entry requires a scoped app identity model before Core accepts
non-admin-user callers.

This document defines the target policy. It does not add app keys, database
tables, or route behavior yet.

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

Future Core app identity should include:

| Field | Purpose |
| --- | --- |
| `app_id` | Stable public app identifier used in audit and caller metadata. |
| `app_label` | Human-readable admin label. |
| `key_id` | Public key identifier for rotation and audit. |
| `secret_hash` | Hash of the app secret. Never store raw app secrets. |
| `status` | `active`, `revoked`, or `expired`. |
| `scopes` | Explicit allowed actions. |
| `rate_policy` | Requests allowed per time window. |
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
| `proposals:approve` | Approve proposals when a trusted host policy is allowed to do so. |
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
- Commit preflight requires `commit:preflight` and an approved proposal.
- Audit listing requires `audit:read`.
- Write/destructive WordPress execution is outside Core until final commit
  execution is separately designed.

Scopes do not replace WordPress permissions. When a request maps to a WordPress
user, Core should still require the relevant WordPress capability or a trusted
host policy that is explicitly documented.

## Rate Policy

The first app-key implementation should support a simple fixed-window limit:

- per `app_id`;
- per route family;
- with a bounded default;
- fail closed when the rate backend is unavailable.

Rate events should be auditable without storing secrets or raw request bodies.

## Audit Attribution

Every app-authenticated governance event should include:

- `app_id`;
- `key_id` when available;
- `actor_id` when a WordPress user is mapped;
- `caller_type`, such as `mcp_adapter`, `agent_host`, `product_plugin`, or
  `internal`;
- sanitized caller metadata;
- scope decision result.

The existing `actor_id` column can continue to hold WordPress user identity.
If app attribution becomes durable first-class data, update
`docs/database-schema.md` and `tests/run.php` before implementation.

## Authentication Shape

The first implementation may choose either signed requests or bearer-style app
keys, but it must document the exact shape before code changes.

Minimum requirements:

- raw secret is shown once at creation and stored only as a hash;
- inactive, expired, or revoked keys return `401`;
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
| Human admin UI | WordPress `manage_options`; no app key required. |
| Hosted runtime callback | No default Core access until callback identity is separately contracted. |

Do not grant `proposals:approve` or `audit:read` by default to generic MCP
adapters.

## Implementation Gates

Before app auth code is added:

1. Freeze the database schema for app identities and rate counters.
2. Update `docs/rest-api-contract.md` with authentication and error semantics.
3. Update `docs/security-model.md` with storage, redaction, and capability
   behavior.
4. Add static contract tests for scopes and forbidden secret storage.
5. Add WordPress smoke coverage for one app-authenticated proposal request.

