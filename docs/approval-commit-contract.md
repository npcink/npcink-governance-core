# Approval Commit Contract

Status: approval status implemented; commit execution planned after MVP.

The approval-commit path is the core reason this plugin exists. The MVP can
approve or reject proposals, but it does not execute commits yet; this document
freezes the direction before commit runtime is added.

## Commit Preconditions

A final write or destructive operation may execute only when all are true:

- the proposal exists;
- the proposal is approved;
- the target ability is still available;
- the current user or caller has permission;
- the request includes Core-generated approval context;
- idempotency checks pass;
- audit logging is available.

## MVP Approval Status

The current implementation supports:

- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/reject`

These routes update proposal status and write audit events. They do not execute
the target ability.

## Approved Context

Future commit calls should use new approval-commit semantics:

```php
array(
	'approval_commit_authorized' => true,
	'confirmation_state'        => 'approved_commit',
	'proposal_id'               => '<core proposal id>',
)
```

Do not accept legacy `confirm_token`, `write_confirmed`, or compatibility
confirmation parameters in the rebuilt Core.

## Fail-Closed Rules

Commit must fail when:

- the proposal is missing or not approved;
- the ability is not known;
- required permission is missing;
- approval context is absent, stale, or mismatched;
- the provider tries to bypass dry-run/proposal semantics;
- audit persistence fails.
