# Testing Strategy

Status: active.

Magick AI Core starts with a small but strict test pyramid.

## Test Layers

| Layer | Command | Purpose |
| --- | --- | --- |
| PHP syntax lint | `composer lint:php` | Prevent parse errors in plugin PHP files. |
| Static contracts | `composer test` | Freeze product boundary, REST routes, public lifecycle, and forbidden legacy terms. |
| Full local suite | `composer test:all` | Run lint and static contracts together. |
| Real WordPress smoke | `composer smoke:wp` | Prove activation, schema creation, REST behavior, and `magick-ai-abilities` integration. |

## Static Contract Rules

Static contracts live in `tests/run.php`.

Use them to assert:

- public REST route names;
- required lifecycle events;
- allowed statuses;
- product boundaries;
- forbidden legacy behavior;
- docs and code stay aligned.

Do not use static contracts to test implementation details that may legitimately
change.

## WordPress Smoke Rules

WordPress smoke lives in `tests/smoke-wp.php`.

Use it for behavior that requires real WordPress:

- activation hooks;
- custom tables;
- REST dispatch;
- current user permissions;
- integration with `magick-ai-abilities`;
- runtime workflow definition discovery through
  `magick_ai_abilities_get_workflow_definitions()`, with fixture fallback from
  `magick-ai-abilities/tests/fixtures/agent-workflow-replay.json`;
- the primary `magick-ai/create-draft` governance scenario, including schema
  controls, proposal creation, approval, and commit preflight;
- the second `magick-ai/set-post-seo-meta` governance scenario, including
  field-level update input and commit preflight without final execution;
- the third `magick-ai/approve-comment` governance scenario, including pending
  comment setup, moderation preview input, and commit preflight without final
  execution;
- the taxonomy terms preview governance scenario, including
  `magick-ai/propose-post-taxonomy-terms` helper execution through WordPress
  Abilities API, `magick-ai/set-post-terms` proposal creation, approval,
  commit preflight, audit correlation, and no post term mutation;
- governance operability coverage, including proposal `audit_timeline`,
  commit-preflight `correlation_id`, app `scope_decision`, and audit filters
  for ability, app, key, caller type, and correlation id;
- real proposal and audit persistence.

The smoke test should stay small. It is a confidence gate, not a full end-to-end
suite.

## When To Add Tests

Add or update tests when changing:

- REST routes;
- proposal status transitions;
- audit events;
- table schema;
- ability intake normalization;
- approval-commit contract;
- security or permission behavior.

## Required Verification

For documentation-only changes:

```bash
git diff --check
```

For Core documentation or static contract updates:

```bash
composer test:all
```

For code changes:

```bash
composer test:all
```

For WordPress runtime behavior:

```bash
composer test:all
composer smoke:wp
```

For package metadata changes:

```bash
composer validate --no-check-publish
```

## Forbidden Regression Targets

Tests should keep these out of Core runtime:

- `confirm_token`
- `write_confirmed`
- workflow definition registries;
- Agent Gateway projection;
- MCP runtime;
- Content Assistant product workflow ownership;
- provider credential storage;
- batch/queue/operator console logic.

## Adapter-Owned Acceptance

Real AI provider request log correlation should be validated in Magick AI
Adapter, not Core. The Adapter smoke should prove that a real provider call,
for example a local Ollama request, writes AI Request Logs context containing
Core `proposal_id` and `correlation_id`.

Core static contracts only document this boundary. Core does not run provider
requests, store model credentials, or merge AI Request Logs into Core audit.
