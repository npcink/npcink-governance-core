# Architecture

Status: MVP architecture.

## Components

| Component | Responsibility |
| --- | --- |
| `Plugin` | Bootstrap hooks, activation, REST registration, and shared services. |
| `Ability_Registry_Adapter` | Read-only intake from `magick-ai-abilities` or WordPress Abilities API. |
| `Proposal_Repository` | Persistence for proposal records. |
| `Proposal_Service` | Proposal creation and audit coordination. |
| `Commit_Preflight_Service` | Approval-commit readiness checks without executing abilities. |
| `Audit_Log_Repository` | Append-only event records and narrow governance filters. |
| `App_Key_Repository` | Scoped app identity and hashed secret storage. |
| `App_Rate_Limiter` | Fixed-window app rate counters by route family. |
| `App_Authenticator` | WordPress admin or scoped app-key REST authorization. |
| `Request_Context` | Request-scoped app attribution for proposals and audit events. |
| REST controllers | Minimal admin-facing REST API. |
| `Admin_Page` | Minimal WordPress Tools screen for pending proposal review, capability summary, audit timeline, and decisions. |

## Data Tables

MVP custom tables:

- `{prefix}magick_ai_core_proposals`
- `{prefix}magick_ai_core_audit_log`
- `{prefix}magick_ai_core_app_keys`
- `{prefix}magick_ai_core_app_rate_limits`

The schema is intentionally small. The first version favors clear lifecycle
records over generalized workflow state.

The governance operability layer reuses these lifecycle records. It adds
proposal `audit_timeline` reads, app scope-decision attribution, and
commit-preflight `correlation_id` metadata without adding workflow state,
execution queues, or a separate logging subsystem.

## Dependency Direction

Core may depend on WordPress and public provider APIs. Provider plugins must not
depend on Core internals.

Allowed:

- Core discovers public abilities.
- Product plugins submit proposals to Core.
- Product plugins later ask Core to approve and commit.

Disallowed:

- Core requiring provider plugin internal files.
- Provider plugins writing directly into Core tables.
- Core owning product workflow definitions.
