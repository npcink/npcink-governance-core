# Security Model

Status: active for MVP.

Magick AI Core is a governance plugin. The security model must fail closed.

## MVP Authorization

All MVP REST routes require:

```php
current_user_can( 'manage_options' )
```

This keeps the first implementation explicit while app-key, scope, and
rate-limit policy are designed.

## Future Authorization Layers

Future releases may add:

- app identity;
- signed request authentication;
- ability scopes;
- write-mode policy;
- rate limits;
- idempotency keys;
- per-app audit attribution.

These must be documented before implementation. They must not be inferred from
provider plugins or moved into `magick-ai-abilities`.

## Data Handling

Core may store:

- proposal metadata;
- sanitized ability input;
- sanitized preview or dry-run handoff payload;
- caller metadata that does not contain secrets;
- audit event metadata.

Core must not store:

- provider API keys;
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

## Local Development Credentials

Local WordPress smoke credentials are local-only and must not be committed.
Repository docs may mention the local username when useful, but the password
must remain redacted in memory notes and outside repository files.
