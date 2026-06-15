# Local Automation Runtime Phase 1 Schema

Status: planning schema.

This schema defines the dry-run replay shape for the future
`npcink-local-automation-runtime` plugin. It is documentation and test fixture
input only. Core does not load this schema at runtime and does not create
runtime jobs.

## Contract Version

All Phase 1 replay documents must use:

```json
{
  "contract_version": "npcink_local_automation_runtime.v1"
}
```

Future changes must be additive unless a later ADR supersedes ADR-007.

## Replay Document

Required top-level fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `contract_version` | string | yes | Must be `npcink_local_automation_runtime.v1`. |
| `fixture_id` | string | yes | Stable fixture id for tests. |
| `mode` | string | yes | Must be `dry_run_replay` for Phase 1. |
| `runtime_owner` | string | yes | Must be `npcink-local-automation-runtime`. |
| `core_runtime_execution` | boolean | yes | Must be `false`. |
| `background_execution` | boolean | yes | Must be `false` in Phase 1. |
| `job` | object | yes | Runtime job candidate. |
| `core_handoff` | object | yes | Core proposal/preflight evidence. |
| `operator_controls` | object | yes | Required operator controls. |
| `runtime_events` | array | yes | Operational event replay. |
| `acceptance` | object | yes | Boundary and replay expectations. |

## Job Object

Required fields:

- `job_id`
- `runtime_id`
- `source`
- `actor`
- `correlation_id`
- `idempotency_key`
- `title`
- `summary`
- `status`
- `eligibility_summary`
- `blocked_items`
- `actions`
- `limits`

Allowed `source` values:

- `operator_started`
- `scheduled`
- `cli`
- `local_product`

Allowed Phase 1 `status` values:

- `planned`
- `blocked`
- `awaiting_core_proposal`
- `awaiting_core_approval`
- `awaiting_core_preflight`
- `ready`

Phase 1 fixtures must not use `running`, `retry_wait`, `succeeded`,
`partially_failed`, `failed`, `cancelled`, or `dead_lettered` as the job status
because Phase 1 has no background execution.

## Eligibility Summary

Required fields:

- `scope`
- `candidate_count`
- `eligible_count`
- `blocked_count`
- `needs_input_count`
- `allowed_ability_ids`
- `disallowed_ability_ids`
- `risk_level`
- `operator_next_action`
- `retryable`

`blocked_count` must equal the number of `blocked_items`.

## Blocked Item

Required fields:

- `item_id`
- `item_type`
- `target_ability_id`
- `reason_code`
- `reason_message`
- `operator_next_action`
- `retryable`

Blocked items must be explicit. A fixture must not hide blocked or skipped
objects in summary-only counts.

## Action Object

Required fields:

- `action_id`
- `target_ability_id`
- `execution_profile`
- `input_hash`
- `preview_ref`
- `depends_on`
- `status`
- `attempt_count`
- `max_attempts`
- `retryable`
- `blocked_reason`
- `output_refs`
- `idempotency_key`

Allowed Phase 1 action statuses:

- `blocked`
- `waiting`
- `ready`
- `skipped`

Phase 1 fixtures must not use `leased`, `running`, `succeeded`, `failed`,
`retry_wait`, or `dead_lettered` as action status.

## Core Handoff

Required fields:

- `proposal_id`
- `proposal_status`
- `preflight_status`
- `correlation_id`
- `approved_input_hash`
- `batch_review_summary`
- `core_execution`
- `commit_execution`

`core_execution` and `commit_execution` must both be `false`.

Allowed `proposal_status` values:

- `not_created`
- `pending`
- `approved`
- `rejected`
- `expired`
- `archived`

Allowed `preflight_status` values:

- `not_requested`
- `passed`
- `blocked`
- `expired`

## Operator Controls

Required boolean fields:

- `pause`
- `resume`
- `cancel`
- `retry`
- `kill_switch`

`kill_switch` must be `true` before any scheduled unattended job can ship in a
future phase.

## Runtime Events

Each event must include:

- `event`
- `job_id`
- `correlation_id`
- `contract_version`
- `created_at`
- `metadata`

Allowed Phase 1 event names:

- `runtime.job.created`
- `runtime.job.eligibility_evaluated`
- `runtime.job.core_proposal_created`
- `runtime.job.awaiting_approval`
- `runtime.job.preflight_passed`

Action execution events are intentionally excluded from Phase 1 fixtures.

## Acceptance Object

Required fields:

- `phase`
- `schema_only`
- `dry_run_replay_only`
- `requires_future_runtime_repo`
- `core_tables_created`
- `core_routes_created`
- `worker_created`
- `scheduler_created`
- `lease_store_created`
- `dead_letter_processor_created`

For Phase 1, these values are required:

```json
{
  "phase": "phase_1_contract_only",
  "schema_only": true,
  "dry_run_replay_only": true,
  "requires_future_runtime_repo": true,
  "core_tables_created": false,
  "core_routes_created": false,
  "worker_created": false,
  "scheduler_created": false,
  "lease_store_created": false,
  "dead_letter_processor_created": false
}
```
