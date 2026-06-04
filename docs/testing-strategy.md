# Testing Strategy

Status: active.

Magick AI Core starts with a small but strict test pyramid.

## Test Layers

| Layer | Command | Purpose |
| --- | --- | --- |
| PHP syntax lint | `composer lint:php` | Prevent parse errors in plugin PHP files. |
| Static contracts | `composer test:contracts` | Freeze product boundary, REST routes, public lifecycle, and forbidden legacy terms. |
| Fail-closed fault injection | `composer test:fail-closed` | Inject database and audit persistence failures against Core classes and assert rollback or cleanup. |
| Full local suite | `composer test:all` | Run lint, static contracts, and fault injection together. |
| Real WordPress smoke | `composer smoke:wp` | Prove activation, schema creation, REST behavior, and `magick-ai-abilities` integration. |
| Plugin Check release scan | `wp plugin check magick-ai-core --ignore-warnings` | Catch WordPress.org packaging and runtime security blockers before release. |

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

## Fail-Closed Fault Injection Rules

Fail-closed fault injection lives in `tests/fail-closed.php`.

Use it for governance persistence paths where returning success without durable
evidence would be unsafe:

- proposal and app-key row insert failures return stable `WP_Error` codes;
- proposal creation deletes the unaudited row when `proposal.created` audit
  cannot be written;
- proposal creation deletes the row when `proposal.policy_evaluated` cannot be
  written;
- local guarded auto approval writes `proposal.auto_approved`, and audit
  failure must not leave the proposal approved;
- approval and rejection roll back to the previous proposal status when
  decision audit cannot be written;
- app-key creation revokes the newly created key and withholds the one-time
  token when `app.created` audit cannot be written.

The test should inject failures through a fake `$wpdb` while still exercising
the real repository, service, and REST controller classes. Source-code string
checks belong in static contracts; cleanup and rollback behavior belongs in
fault injection.

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
- the plan-to-proposal bridge for
  `magick-ai/build-content-inventory-fix-plan`,
  `magick-ai/build-test-content-cleanup-plan`, and
  `magick-ai/build-media-inventory-fix-plan`, plus media reference repair
  planning for post content and setting/theme-mod URL patches, including generated proposals,
  destructive media delete exclusion by default, and `requires_input`
  preflight blocking;
- bounded batch plan contracts for
  `magick-ai-toolbox/build-article-batch-write-plan` and
  `magick-ai/build-media-optimization-plan`, including explicit batch approval
  and fail-closed rejection of publish, missing derivative, or multi-attachment
  cases;
- bounded media rename plan contracts for `magick-ai/build-media-rename-plan`,
  including one reviewed `magick-ai/rename-media-file` action and fail-closed
  rejection of missing target filename or multi-attachment cases;
- governance operability coverage, including proposal `audit_timeline`,
  commit-preflight `correlation_id`, app `scope_decision`, and audit filters
  for ability, app, key, caller type, and correlation id;
- trusted Adapter approval coverage, including an app key with
  `proposals:approve`, app-authenticated approval, app-authenticated preflight,
  and approval audit attribution;
- real proposal and audit persistence.

The smoke test should clean up transient WordPress content fixtures on shutdown,
including posts, comments, terms, and media attachments, and should revoke app
keys created for the run. Proposal and audit rows remain persistent by default
to preserve the governance evidence checked by the smoke gate. Local-only runs
may set `MAGICK_AI_CORE_SMOKE_PURGE=1` to purge tracked proposal, app-key,
rate-limit, and audit rows for the current run after assertions complete.

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

Fail-closed governance paths must be covered when changed:

- proposal and app-key row insert failures return stable `WP_Error` codes;
- write-like lifecycle events fail rather than reporting success when audit
  persistence fails;
- status changes that cannot be audited roll back to the previous status where
  Core can safely do so;
- one-time app tokens are not returned when app creation cannot be audited.

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

For WordPress.org packaging or Plugin Check fixes, run Plugin Check against the
runtime plugin files. Local development checkouts may contain docs, tests, and
examples that are excluded from release packages by `.distignore`; use explicit
exclusions when scanning the symlinked development checkout:

```bash
wp plugin check magick-ai-core --ignore-warnings \
  --exclude-directories=tests,examples,docs,.sisyphus \
  --exclude-files=README.md,AGENTS.md,.gitignore
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
- workflow/task queue, batch execution, or operator runtime console logic.

Allowed governance terms such as Review Queue, pending proposal queue, bounded
bulk rejection, and `plan_to_proposal_batch` must stay tied to proposal
lifecycle records. They must not become workflow/task queue ownership, batch
execution, retries, leases, schedulers, or operator runtime consoles.

## Adapter-Owned Acceptance

Real AI provider request log correlation should be validated in Magick AI
Adapter, not Core. The Adapter smoke should prove that a real provider call,
for example a local Ollama request, writes AI Request Logs context containing
Core `proposal_id` and `correlation_id`.

Core static contracts only document this boundary. Core does not run provider
requests, store model credentials, or merge AI Request Logs into Core audit.
