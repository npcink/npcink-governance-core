# Security Model

Status: active for MVP.

Magick AI Core is a governance plugin. The security model must fail closed.

## MVP Authorization

All MVP REST routes require:

```php
current_user_can( 'manage_options' )
```

Scoped app keys are also supported for external governance clients. App keys do
not replace human admin approval.

## Future Authorization Layers

The app identity model is described in
[App Auth Scope Policy](app-auth-scope-policy.md).

Implemented layers include:

- app identity;
- bearer app token authentication;
- ability scopes;
- rate limits;
- per-app audit attribution.

Planned layers include:

- write-mode policy;
- idempotency keys;

These must be documented before implementation. They must not be inferred from
provider plugins or moved into `magick-ai-abilities`.

MCP adapters, Agent Gateway bridges, hosted runtimes, and product plugins are
callers of Core governance routes. They are not allowed to move MCP sessions,
tool catalogs, provider credentials, workflow runtime, or final WordPress write
authority into Core.

## Data Handling

Core may store:

- proposal metadata;
- sanitized ability input;
- sanitized preview or dry-run handoff payload;
- caller metadata that does not contain secrets;
- audit event metadata.

Core must not store:

- provider API keys;
- raw app secrets;
- passwords;
- raw cookies;
- authorization headers;
- WordPress salts;
- database credentials;
- unredacted secrets from provider payloads.

## Sanitization And SQL

- REST scalar parameters must use WordPress sanitizers.
- Structured payloads must be sanitized recursively before persistence.
- SQL writes must use `$wpdb->insert()` or `$wpdb->update()` with format arrays.
- SQL reads with parameters must use `$wpdb->prepare()`.

## Approval Boundary

Approving a proposal is not the same as committing a write.

The MVP supports:

- proposal creation;
- approval;
- rejection;
- commit preflight;
- audit records.

The MVP does not execute final writes.

Commit preflight returns Core-generated approval-commit context without running
the target ability. Final write or destructive execution must require that
context and must fail closed if the proposal is missing, not approved, stale,
unauthorized, or not auditable.

## Legacy Confirmation Ban

The rebuilt Core must not accept or emit:

- `confirm_token`
- `write_confirmed`
- compatibility confirmation tokens from the old Magick AI Core

Use approval-commit semantics only:

```php
array(
	'approval_commit_authorized' => true,
	'confirmation_state'        => 'approved_commit',
	'proposal_id'               => '<core proposal id>',
)
```

## Dependency Boundary

Core may call public provider APIs such as:

- `magick_ai_abilities_get_registered()`
- `wp_get_abilities()`

Core must not:

- require provider plugin internal files;
- copy ability definitions from `magick-ai-abilities`;
- store provider credentials;
- own product workflow runtime.

## App Key Authentication

App tokens use:

```text
Authorization: Bearer mai_core.<key_id>.<secret>
```

The raw secret is returned once by `POST /wp-json/magick-ai-core/v1/apps` and
stored only as `secret_hash`. `GET /apps`, proposals, and audit rows must not
return raw app secrets or secret hashes.

Administrators can also create the same scoped app token from
`Tools -> Magick AI Core -> Core App Keys`. The screen shows the Core REST URL,
minimal Core environment variables, and existing app keys without secret
material. It displays the raw token only on the creation result screen.
Productized OpenClaw setup, agent rules, handoff text, and local TLS client
configuration belong in Magick AI Adapter, not Core.

If a token is exposed, administrators should disable that app key from the same
screen and create a replacement. Disabled keys are stored as `revoked` and must
fail future app authentication with `401`.

App-authenticated requests must have the route's required scope and pass the
fixed-window rate limit. Missing auth returns `401`, missing scope returns
`403`, and rate limit failures return `429`.

Successful app-authenticated governance events include sanitized app attribution
in `metadata.auth`, including `scope_decision=allowed`. Scope denials record
`scope_decision=denied`; rate-limit denials record
`scope_decision=rate_limited`. App-created proposals also copy that attribution
into `caller.auth`.

Commit preflight returns and audits a `correlation_id` so an adapter can connect
the approval context it received to the `commit.preflighted` audit event. The
correlation id is not an execution token and does not authorize final WordPress
mutation by itself.

## Local Development Credentials

Local WordPress smoke credentials are local-only and must not be committed.
Repository docs may mention the local username when useful, but the password
must remain redacted in memory notes and outside repository files.

## Adapter Credential Boundary

Adapters may hold Core app tokens, WordPress Application Passwords, local CA
bundle paths, or local TLS test switches in their own secret stores and runtime
configuration. Core must not copy those adapter-side onboarding values into
proposal payloads or audit metadata, and Core admin screens must not become the
OpenClaw setup UI.
