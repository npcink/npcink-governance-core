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
| `Audit_Log_Repository` | Append-only event records and narrow governance filters. |
| `App_Key_Repository` | Scoped app identity and hashed secret storage. |
| `App_Rate_Limiter` | Fixed-window app rate counters by route family. |
| `App_Authenticator` | WordPress admin or scoped app-key REST authorization. |
| `Request_Context` | Request-scoped app attribution for proposals and audit events. |
| `Observability` | Local metadata-only action bridge for bounded operational collection by other local plugins. |
| REST controllers | Minimal admin-facing REST API. |
| `Admin_Page` | Minimal WordPress admin screen for governance status, pending proposal review, focused proposal detail, recent activity, full audit inspection, and Core app-key management. |

## Data Tables

MVP custom tables:

- `{prefix}npcink_governance_core_proposals`
- `{prefix}npcink_governance_core_audit_log`
- `{prefix}npcink_governance_core_app_keys`
- `{prefix}npcink_governance_core_app_rate_limits`

The schema is intentionally small. The first version favors clear lifecycle
records over generalized workflow state.

The governance operability layer reuses these lifecycle records. It adds
proposal `audit_timeline` reads, app scope-decision attribution, and
commit-preflight `correlation_id` metadata without adding workflow state,
execution queues, or a separate logging subsystem.

Core also emits local metadata-only observability hooks for proposal and
commit-preflight REST operations. These hooks are optional local signals; they
do not replace audit records, do not call Cloud directly, and do not become a
second governance or execution truth.

The plan-to-proposal bridge also reuses these lifecycle records. It stores one
proposal row per accepted plan `write_action` and records a
`proposal.plan_ingested` audit event; it does not add batch tables, queues, or
workflow runtime state.

## Dependency Direction

Core may depend on WordPress and public provider APIs. Provider plugins must not
depend on Core internals.

Allowed:

- Core discovers public abilities.
- Product plugins submit proposals to Core.
- Product plugins or adapters submit supported read-only plan outputs to Core
  for proposal creation.
- Product plugins later ask Core to approve and commit.

Disallowed:

- Core requiring provider plugin internal files.
- Provider plugins writing directly into Core tables.
- Core owning product workflow definitions.
