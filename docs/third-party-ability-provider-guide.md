# Third-Party Ability Provider Guide

Status: active integration guide.

Npcink Governance Core can govern WordPress Abilities API providers outside the
first-party `npcink-abilities-toolkit` package. The Toolkit remains the
reference provider used by Core smoke tests, but Core's base proposal lifecycle
only requires a currently discoverable WordPress ability id and a provider-side
contract that makes the operation reviewable.

## What Providers Own

Third-party provider plugins own:

- stable namespaced `ability_id` values;
- WordPress Abilities API registration;
- permission callbacks;
- input and output schemas;
- risk metadata;
- dry-run previews for write or destructive operations;
- final WordPress execution after Core approval and commit preflight.

Core does not copy provider schemas, register fallback abilities, store provider
credentials, route model calls, or execute provider callbacks.

## Governable Ability Shape

Use stable, vendor-scoped ability ids:

```text
vendor-plugin/create-resource
vendor-plugin/update-resource
vendor-plugin/delete-resource
```

Avoid short labels such as `content/draft-preview` in runtime proposal records.
Core stores real `ability_id` values only, because approval and commit preflight
must rediscover the same target ability before any downstream execution.

Every ability should expose:

- a strict `permission_callback`;
- machine-readable input and output schemas;
- a risk level that distinguishes read, write, and destructive operations;
- whether approval is required;
- enough label and description text for an operator to understand the action.

Read-only helpers may be called directly by an adapter or product plugin through
WordPress Abilities API. Write and destructive abilities should be submitted to
Core as proposals before final execution.

## Dry-Run Contract

Write and destructive abilities should support a preview path with:

- `dry_run=true`;
- `commit=false`;
- the exact target resource identifier where possible;
- `preview.before` when the provider can safely read current state;
- `preview.after_suggestion` or an equivalent proposed state;
- warnings, validation errors, and irreversible-risk flags.

The provider or adapter runs the dry-run preview outside Core, then submits the
result to `POST /wp-json/npcink-governance-core/v1/proposals`. Core records the
proposal, approval decision, commit-preflight context, and audit events; the
provider or trusted adapter still performs the final write outside Core.

## Plan Output Is Allowlisted

`POST /wp-json/npcink-governance-core/v1/proposals/from-plan` is not a generic workflow runtime.
It accepts only plan shapes that Core has explicitly documented and validated,
because a plan can fan out into multiple governed write actions.

Third-party providers that need plan-to-proposal support should first document:

- the read-only planning ability id;
- the supported `plan_type`;
- exact action count and resource bounds;
- allowed `write_action.target_ability_id` values;
- required dry-run evidence;
- how final execution stays outside Core.

Until that contract is added to Core, third-party providers should create
ordinary single-action proposals through `POST /proposals`.

## Client Access Token Access

External adapters should use scoped Core client access tokens rather than
administrator cookies. The default non-admin integration scopes are:

- `capabilities:read`;
- `proposals:create`;
- `proposals:read`;
- `commit:preflight`.

Do not grant `proposals:approve`, `proposals:reject`, or `audit:read` to generic
third-party adapters by default. Approval scopes are for trusted local hosts
with a reviewed approve-and-execute boundary.

## Integration Checklist

1. Register abilities through WordPress Abilities API.
2. Verify the abilities appear in Core `GET /capabilities`.
3. Run provider dry-runs outside Core for write or destructive operations.
4. Submit proposals with the real `ability_id` and preview evidence.
5. Require Core approval and successful commit preflight before final execution.
6. Execute the approved ability through the provider or adapter, not through
   Core.
7. Keep provider credentials, prompts, model routing, workflow queues, and
   product UX outside Core.
