# Architecture

Status: MVP architecture.

## Components

| Component | Responsibility |
| --- | --- |
| `Plugin` | Bootstrap hooks, activation, REST registration, and shared services. |
| `Ability_Registry_Adapter` | Read-only intake from `npcink-abilities-toolkit` or WordPress Abilities API. |
| `Proposal_Repository` | Persistence for proposal records. |
| `Proposal_Service` | Proposal creation and audit coordination. |
| `Plan_Proposal_Service` | Converts supported read-only planning ability outputs into pending Core proposals without running abilities or writes. |
| `Commit_Preflight_Service` | Approval-commit readiness checks without executing abilities. |
| `Read_Request_Repository` | Persistence for Core-owned sensitive read authorization requests. |
| `Read_Request_Service` | Sensitive read request creation, approval/rejection, expiry, one-time consumption, bounded read preflight, and audit coordination. |
| `Audit_Log_Repository` | Append-only event records, shared metadata redaction, and indexed governance filters. |
| `App_Key_Repository` | Scoped app identity and hashed secret storage. |
| `App_Rate_Limiter` | Fixed-window app rate counters by route family with conditional under-limit increments. |
| `App_Authenticator` | WordPress admin or scoped app-key REST authorization. |
| `Request_Context` | Request-scoped app attribution for proposals and audit events. |
| `Observability` | Local metadata-only action bridge for bounded operational collection by other local plugins. |
| REST controllers | Minimal admin-facing REST API. |
| `Admin_Page` | Minimal WordPress admin screen for governance status, pending proposal review, focused proposal detail, recent activity, full audit inspection, and Core app-key management. |

## Data Tables

MVP custom tables:

- `{prefix}npcink_governance_core_proposals`
- `{prefix}npcink_governance_core_read_requests`
- `{prefix}npcink_governance_core_audit_log`
- `{prefix}npcink_governance_core_app_keys`
- `{prefix}npcink_governance_core_app_rate_limits`

The schema is intentionally small. The first version favors clear lifecycle
records over generalized workflow state.

The governance operability layer reuses these lifecycle records. It adds
proposal `audit_timeline` reads, app scope-decision attribution, and
commit-preflight `correlation_id` metadata without adding workflow state,
execution queues, or a separate logging subsystem. Common audit filters
(`ability_id`, `app_id`, `key_id`, `caller_type`, and `correlation_id`) are
promoted into indexed audit columns while the full sanitized metadata remains
in `metadata_json`.

Core also emits local metadata-only observability hooks for proposal and
commit-preflight REST operations. These hooks are optional local signals; they
do not replace audit records, do not call Cloud directly, and do not become a
second governance or execution truth.

The plan-to-proposal bridge also reuses these lifecycle records. It stores one
proposal row per accepted plan `write_action` and records a
`proposal.plan_ingested` audit event; it does not add batch tables, queues, or
workflow runtime state.

Sensitive read authorization adds a separate lifecycle record table because it
is not a write proposal. The read request row binds `ability_id`, approved
`input_hash`, purpose, sensitivity, data classes, redaction level, expiry,
bounds, caller metadata, and correlation id. It returns only bounded
`read_authorization_context` for Adapter checks; Core still does not execute
the read ability and does not proxy read results.

## Dependency Direction

Core may depend on WordPress and public provider APIs. Provider plugins must not
depend on Core internals.

Allowed:

- Core discovers public abilities.
- Product plugins submit proposals to Core.
- Product plugins or adapters submit supported read-only plan outputs to Core
  for proposal creation.
- Product plugins later ask Core to approve and commit.
- Adapters submit sensitive read requests to Core and execute the read through
  WordPress Abilities API only after Core returns bounded read authorization
  context.

Disallowed:

- Core requiring provider plugin internal files.
- Provider plugins writing directly into Core tables.
- Core owning product workflow definitions.
