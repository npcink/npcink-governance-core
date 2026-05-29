# Architecture

Status: MVP architecture.

## Components

| Component | Responsibility |
| --- | --- |
| `Plugin` | Bootstrap hooks, activation, REST registration, and shared services. |
| `Ability_Registry_Adapter` | Read-only intake from `magick-ai-abilities` or WordPress Abilities API. |
| `Proposal_Repository` | Persistence for proposal records. |
| `Proposal_Service` | Proposal creation and audit coordination. |
| `Audit_Log_Repository` | Append-only event records. |
| REST controllers | Minimal admin-facing REST API. |

## Data Tables

MVP custom tables:

- `{prefix}magick_ai_core_proposals`
- `{prefix}magick_ai_core_audit_log`

The schema is intentionally small. The first version favors clear lifecycle
records over generalized workflow state.

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

