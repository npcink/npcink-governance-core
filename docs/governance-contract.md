# Governance Contract

Status: MVP contract.

This document defines the first Magick AI Core governance boundary.

## Operation Lifecycle

1. `discover`: Core lists available abilities from WordPress Abilities API and
   known provider APIs.
2. `request`: a caller submits an intended operation.
3. `proposal`: Core records a reviewable proposal and normalizes metadata.
4. `review`: a human or trusted host policy approves or rejects the proposal.
5. `commit`: a future Core service executes only after approval.
6. `audit`: Core records every lifecycle event.

The MVP implements discovery, proposal records, approval/rejection status, and
audit records. Commit execution is intentionally contract-first follow-up work.

## Proposal Shape

Required fields:

- `ability_id`: the target WordPress ability id.
- `input`: caller-supplied structured input.

Optional fields:

- `title`: human-readable proposal title.
- `summary`: human-readable proposal summary.
- `preview`: dry-run result, diff, or handoff payload from the provider.
- `caller`: caller identity metadata.

Core-generated fields:

- `proposal_id`
- `status`
- `created_at`
- `updated_at`

Allowed MVP statuses:

- `pending`
- `approved`
- `rejected`

## Approval Boundary

Write and destructive commits must fail closed unless the commit request carries
host approval context created by Core.

The new Core uses approval-commit terminology. It must not reintroduce
`confirm_token`, `write_confirmed`, or other legacy confirmation parameters.

## Audit Events

MVP event names:

- `proposal.created`
- `proposal.approved`
- `proposal.rejected`
- `proposal.listed`
- `capabilities.listed`
- `audit.listed`

Future event names:

- `commit.requested`
- `commit.succeeded`
- `commit.failed`

## Security Defaults

- REST routes require `manage_options` in the MVP.
- Inputs are sanitized before persistence.
- Outputs are escaped by callers or REST serialization.
- SQL writes must use `$wpdb->insert()` or prepared queries.
- Secrets, provider keys, raw cookies, and passwords must not be stored in
  proposals or audit metadata.
