# Operation Classification Contract

Status: contract implemented as the Core `Operation_Classifier` policy helper;
the first low-risk Toolbox proof and high-risk Core proposal proof are
implemented.

This contract defines how Npcink modules decide whether an AI-assisted action
is suggestion-only, eligible for local admin consent, needs strong local
confirmation, or must enter Core proposal review.

It applies to Core consumers such as Adapter, Toolbox, future MCP adapters,
browser-agent adapters, cloud-agent adapters, and other product modules.

Core implementation:

- `Npcink\GovernanceCore\Governance\Operation_Classifier`
- policy version: `operation-classification-v1`

The classifier is intentionally a pure policy helper. It does not create
proposals, execute writes, record audit events, or decide product UI copy.

## Classification Values

| Classification | Meaning | Core proposal |
| --- | --- | --- |
| `suggestion_only` | AI produces candidates, analysis, copy, or plans without writing WordPress state. | No. |
| `local_admin_consent` | A present WordPress administrator sees one bounded final result and clicks to apply a low-risk single-object change. | No, but audit/activity logging is required. |
| `strong_local_confirmation` | A present WordPress administrator sees one high-impact result, but the action may publish, replace, overwrite, or otherwise affect important state. | Case by case; use stronger confirmation or Core proposal. |
| `core_proposal_required` | The action is external, automated, batch, destructive, high-impact, or insufficiently previewed. | Yes. |

## Classification Inputs

An implementation should classify using these fields before execution:

| Field | Example values | Purpose |
| --- | --- | --- |
| `request_source` | `wp_admin_ui`, `external_adapter`, `scheduled_task`, `cli`, `cloud_callback` | External or background sources bias toward Core proposal review. |
| `actor_presence` | `present_click`, `background`, `delegated` | Local admin consent requires a present click. |
| `preview_completeness` | `exact_final`, `sufficient`, `partial`, `none` | Local admin consent requires exact or sufficient preview. |
| `scope` | `one_field`, `one_object`, `multiple_objects`, `site_wide`, `external_account` | Multiple objects or broader state require Core proposal review. |
| `reversibility` | `easy_undo`, `backup_restore`, `hard_restore`, `irreversible` | Lower reversibility increases review requirements. |
| `operation_kind` | `suggest`, `create_draft`, `update_metadata`, `publish`, `delete`, `replace_file`, `settings_change`, `permission_change`, `batch_plan` | Destructive, publishing, settings, permission, and batch operations require stronger review. |

## Decision Rules

Return `suggestion_only` when no WordPress state is written.

Return `local_admin_consent` only when all of these are true:

- `request_source=wp_admin_ui`;
- `actor_presence=present_click`;
- `preview_completeness` is `exact_final` or `sufficient`;
- `scope` is `one_field` or `one_object`;
- `operation_kind` does not publish, delete, replace files, change settings,
  change permissions, or execute a batch plan;
- `reversibility` is `easy_undo` or otherwise low cost to correct;
- the module records audit/activity evidence.

Return `strong_local_confirmation` for one-object admin actions that are fully
visible but high impact, such as publishing, replacing one media file, or
overwriting substantial existing content. The product may choose strong local
confirmation only when reversibility and preview completeness are strong enough;
otherwise it must use Core proposal review.

Return `core_proposal_required` when any of these are true:

- the source is an external adapter, scheduled task, cloud callback, or other
  non-present channel;
- the action affects multiple objects or site-wide state;
- the action is destructive or hard to reverse;
- the action publishes, unpublishes, deletes, replaces files, changes settings,
  changes permissions, or mutates external account configuration;
- the final result cannot be fully or sufficiently previewed before the user
  acts;
- the action depends on an AI-generated plan with multiple write actions.

## Required Evidence

For `local_admin_consent`, Core records audit evidence through the
`npcink_governance_core_record_local_admin_consent` filter. This is an
audit-only integration point: it does not create proposals, approve proposals,
preflight commits, or execute abilities. Record at least:

- actor user id;
- source module and route/action id;
- target object id and object type;
- classification value;
- summary of the AI suggestion or candidate;
- timestamp;
- request id or correlation id when available.

For `core_proposal_required`, the proposal preview should preserve:

- target ability id;
- target input or safe summary;
- before/after or dry-run evidence where available;
- reason, risk, and required scopes;
- caller/source metadata;
- batch item details when the action is a batch.

## Scenario Proofs

### Low-Risk Toolbox Proof

Implemented scenario: set one displayed existing WordPress image attachment as
the featured image for one post.

Expected classification: `local_admin_consent`.

Acceptance:

- the user is in WordPress admin;
- the target post and image candidate are visible before execution;
- the user clicks one explicit apply action;
- only one post's featured image changes;
- the action records Core-owned `local_admin_consent.requested` and
  `local_admin_consent.completed` audit evidence;
- no Core proposal is created.
- external URLs, media import, media metadata writes, generated image adoption,
  and batch image selection remain Core proposal paths.

### High-Risk Core Proof

Implemented scenario: reviewed article plus image batch handoff, where one
operator-reviewed batch creates draft actions, media upload actions, media
metadata actions, and featured-image actions for article/image pairs.

Continuing contrast cases such as batch image selection, batch SEO updates, and
batch article edits must also resolve to `core_proposal_required`; they are
examples of the same boundary, not Local Admin Consent expansion targets.

Expected classification: `core_proposal_required`.

Acceptance:

- the operation affects more than one target or action;
- the operation includes media import, media metadata, and featured-image
  actions rather than one existing attachment assignment;
- Core stores one reviewable `plan_to_proposal_batch` proposal;
- preview evidence is preserved for each affected object or write action;
- proposal input and preview remain dry-run and non-commit;
- Adapter or a product module executes only after approved preflight;
- no local admin consent audit event or bypass is available;
- Core remains proposal, approval, preflight, and audit truth.

## Non-Goals

This contract does not add Core final execution, workflow queues, batch
runtimes, or product workflow ownership. It classifies actions so each module
uses the right authorization path.
