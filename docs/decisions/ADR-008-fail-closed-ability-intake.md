# ADR-008: Fail Closed At Ability Intake

## Status

Accepted.

## Date

2026-07-14

## Context

Core previously treated an ability with missing risk metadata as a public
read. It also ignored the WordPress-standard `meta.annotations` and
`meta.show_in_rest` fields. A provider contract that was incomplete,
contradictory, or REST-hidden could therefore be projected as executable.

## Decision

Core keeps every discovered ability diagnosable but admits only rows whose
execution contract is unambiguous. Capability rows expose
`intake_contract_version`, `intake_status`, and `intake_reasons`.

Core derives risk from the exact closed provider risk set (`read`, `write`,
`destructive`) and from WordPress `meta.annotations.readonly` /
`meta.annotations.destructive`. Every supported declaration is retained and
compared before duplicate discovery rows are merged;
missing, invalid, aliased, or conflicting risk evidence blocks intake. Only an
unambiguous literal boolean `meta.show_in_rest=true` admits an ability because Core's
current execution handoffs use the WordPress Abilities REST surface. Write and
destructive abilities cannot declare that approval is unnecessary.

Blocked rows remain visible through `/capabilities`, but use
`governance_mode=blocked` and `execution_surface=none`. Proposal creation,
plan intake, sensitive read authorization, and commit preflight must reject
blocked rows. Missing read sensitivity no longer becomes public by inference;
it defaults to sensitive with redaction and Core read authorization required.

## Consequences

- Provider mistakes are visible without becoming executable defaults.
- Existing providers may need to correct standard annotations or REST
  exposure before Core accepts them.
- No ability definition, execution route, database table, workflow runtime,
  queue, or provider credential ownership moves into Core.
- Rollback is a single Core commit; no data migration is required.
