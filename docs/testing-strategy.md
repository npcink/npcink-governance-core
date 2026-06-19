# Testing Strategy

Status: active.

Npcink Governance Core starts with a small but strict test pyramid.

## Test Layers

| Layer | Command | Purpose |
| --- | --- | --- |
| PHP syntax lint | `composer lint:php` | Prevent parse errors in plugin PHP files. |
| Static contracts | `composer test:contracts` | Freeze product boundary, REST routes, public lifecycle, and forbidden legacy terms. |
| Fail-closed fault injection | `composer test:fail-closed` | Inject database and audit persistence failures against Core classes and assert rollback or cleanup. |
| Full local suite | `composer test:all` | Run lint, static contracts, and fault injection together. |
| Real WordPress smoke | `composer smoke:wp` | Prove activation, schema creation, REST behavior, and `npcink-abilities-toolkit` integration. |
| Optional eval-lab quality gate | `composer eval:lab -- --list`, `composer eval:project:review -- dry_run=true`, or `composer eval:gutenberg:judge -- dry_run=true limit=3` | Validate local AI-output evaluation wiring without making it a Core runtime or default test dependency. |
| WordPress.org review guard | `composer check:wporg` | Catch locally reproducible reviewer policy patterns that Plugin Check may miss. |
| Plugin Check release scan | `composer plugin-check:release` | Catch WordPress.org packaging and runtime security blockers before release. |

## Static Contract Rules

Static contracts live in `tests/run.php`.

Use them to assert:

- public REST route names;
- required lifecycle events;
- allowed statuses;
- Core-owned sensitive read authorization fields, routes, statuses, grant
  context, and app scopes;
- product boundaries;
- forbidden legacy behavior;
- runtime-code markers for final execution, provider credentials, model
  routing, workflow runtime, MCP runtime, Agent Gateway catalogs, task queues,
  batch execution consoles, and operator runtime consoles;
- runtime-shaped REST paths, filenames, and class names such as execute,
  proxy-execute, jobs, tasks, runs, executor, queue, scheduler, worker, and
  workflow runtime markers;
- pull request and boundary-review templates that force AI-assisted changes to
  state the Core ownership boundary before merge;
- WordPress.org review guard coverage for recurring release-policy issues;
- shared `npcink-abilities-toolkit` workflow replay fixture structure and
  host-owned write boundary semantics;
- docs and code stay aligned.

Do not use static contracts to test implementation details that may legitimately
change.

## Optional Eval-Lab Rules

The Magick AI Evaluation Lab integration is optional local development
infrastructure. The `project_boundary_review_triad` task is useful when Core
needs multi-model review of a local boundary-sensitive diff. Other eval-lab
tasks are useful when a product or ability provider needs cross-model review of
generated recommendations, Gutenberg plans, Site Knowledge evidence, or image
candidates before submitting a governed handoff to Core.

Eval-lab commands must stay out of `composer test:all`, CI-required Core gates,
release packages, and plugin runtime behavior. They must not create proposals,
approve proposals, write audit rows, read provider credentials from Core, or
mutate WordPress state. Core's deterministic gates still own lifecycle,
authorization, redaction, app scope, rate limit, REST, and persistence
correctness. The project review report uses eval-lab's
`project_boundary_review_triad.v1` contract, not a Core persistence or audit
contract.

## Fail-Closed Fault Injection Rules

Fail-closed fault injection lives in `tests/fail-closed.php`.

Use it for governance persistence paths where returning success without durable
evidence would be unsafe:

- proposal and app-key row insert failures return stable `WP_Error` codes;
- proposal creation deletes the unaudited row when `proposal.created` audit
  cannot be written;
- proposal creation deletes the row when `proposal.policy_evaluated` cannot be
  written;
- proposal creation preserves reviewed `npcink-abilities-toolkit/update-post-blocks`
  Gutenberg block key case, including nested batch `write_actions[]`, while
  still sanitizing unsafe block HTML;
- proposal and audit persistence redact secret-shaped caller metadata,
  authorization values, API keys, cookies, and token-like strings before they
  can be returned or stored;
- plan-to-proposal intake rejects oversized plan payloads, global
  over-25-action plans, and narrower media optimization / block theme site
  action caps before storing proposal rows;
- smart guarded cleanup and draft-only create-draft auto approval write
  `proposal.auto_approved`, and audit failure must not leave the proposal
  approved;
- approval and rejection roll back to the previous proposal status when
  decision audit cannot be written;
- app-key creation revokes the newly created key and withholds the one-time
  token when `app.created` audit cannot be written.
- sensitive read request create/approve/reject/preflight paths preserve Core as
  authorization truth, reject changed `ability_id` or `input_hash`, reject
  expired or rejected records, consume one-time grants, audit each lifecycle
  event, and never emit secret-shaped payload values.

The test should inject failures through a fake `$wpdb` while still exercising
the real repository, service, and REST controller classes. Source-code string
checks belong in static contracts; cleanup and rollback behavior belongs in
fault injection.

## WordPress Smoke Rules

WordPress smoke lives in `tests/smoke-wp.php`.
The wrapper `tests/smoke-wp.sh` owns LocalWP environment preflight diagnostics
for WP-CLI, Local PHP, database socket, plugin symlink, and
`npcink-abilities-toolkit` path assumptions.
Classify smoke failures before editing code: preflight `environment` failures
are local setup or targeting issues, preflight `toolkit` failures are provider
mount/setup issues, and post-preflight PHP smoke `[fail]` lines are Core or
Toolkit contract regressions that require assertion-level investigation.

Use it for behavior that requires real WordPress:

- activation hooks;
- custom tables;
- REST dispatch;
- current user permissions;
- integration with `npcink-abilities-toolkit`;
- Core-managed sensitive read authorization for diagnostics abilities, including
  capability flags, request creation, approval, bounded read preflight, changed
  input rejection, audit timeline, and no secret emission;
- runtime workflow definition discovery through
  `npcink_abilities_toolkit_get_workflow_definitions()`, with fixture fallback from
  `npcink-abilities-toolkit/tests/fixtures/agent-workflow-replay.json`;
- the primary `npcink-abilities-toolkit/create-draft` governance scenario, including schema
  controls, safe HTML preservation for `content_format=html`, proposal
  creation, approval, and commit preflight;
- the second `npcink-abilities-toolkit/set-post-seo-meta` governance scenario, including
  field-level update input and commit preflight without final execution;
- the third `npcink-abilities-toolkit/approve-comment` governance scenario, including pending
  comment setup, moderation preview input, and commit preflight without final
  execution;
- the taxonomy terms preview governance scenario, including
  `npcink-abilities-toolkit/propose-post-taxonomy-terms` helper execution through WordPress
  Abilities API, `npcink-abilities-toolkit/set-post-terms` proposal creation, approval,
  commit preflight, audit correlation, and no post term mutation;
- the plan-to-proposal bridge for
  `npcink-abilities-toolkit/build-content-inventory-fix-plan`,
  `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`, and
  `npcink-abilities-toolkit/build-media-inventory-fix-plan`, plus media reference repair
  planning for post content and setting/theme-mod URL patches, including generated proposals,
  destructive media delete exclusion by default, and `requires_input`
  preflight blocking;
- bounded batch plan contracts for
  `npcink-toolbox/build-article-batch-write-plan` and
  `npcink-abilities-toolkit/build-media-optimization-plan`, including explicit batch approval
  and fail-closed rejection of publish, missing derivative, or unpaired
  attachment action cases;
	- bounded media rename plan contracts for `npcink-abilities-toolkit/build-media-rename-plan`,
	  including one reviewed `npcink-abilities-toolkit/rename-media-file` action and fail-closed
	  rejection of missing target filename or multi-attachment cases;
	- media adoption enhancement plan contracts for
	  `npcink-abilities-toolkit/build-media-adoption-enhancement-plan`, including one upload
	  action, one `npcink-abilities-toolkit/optimize-media-asset` action, optional
	  reference repair with `preview.media_adoption_enhancement`, and fail-closed
	  rejection of missing optimize or unreviewed repair replacement cases;
	- existing article optimization plan intake for
  `npcink-abilities-toolkit/build-article-optimization-apply-plan`, including a reviewed
  excerpt proposal, `preview.article_optimization`, approval/preflight, and no
  post excerpt mutation;
- Gutenberg pattern page plan intake for
  `npcink-abilities-toolkit/build-pattern-page-plan`, including a two-action
  batch proposal, `preview.pattern_page`, class allowlist rejection, and no
  draft page mutation during intake or preflight;
- block theme template layout malicious fixtures, including roundtrip evidence
  omission, custom HTML/freeform blocks, navigation blocks, embedded/scriptable
  HTML, non-allowlisted template slugs, oversized block trees, and no template
  mutation during intake or preflight;
- governance operability coverage, including proposal `audit_timeline`,
  commit-preflight `correlation_id`, app `scope_decision`, and audit filters
  for ability, app, key, caller type, and correlation id;
- bounded history retention cleanup, including WP-Cron scheduling on activation,
  cleanup hook removal on deactivation, manual Settings cleanup action, and
  audited deletion of expired/archived proposal history plus revoked client
  access tokens only;
- trusted Adapter approval coverage, including an app key with
  `proposals:approve`, app-authenticated approval, app-authenticated preflight,
  and approval audit attribution;
- real proposal and audit persistence.

The smoke test should clean up transient WordPress content fixtures on shutdown,
including posts, comments, terms, and media attachments, and should revoke app
keys created for the run. Proposal and audit rows remain persistent by default
to preserve the governance evidence checked by the smoke gate. Local-only runs
may set `NPCINK_GOVERNANCE_CORE_SMOKE_PURGE=1` to purge tracked proposal, app-key,
rate-limit, and audit rows for the current run after assertions complete.

The smoke test should stay small. It is a confidence gate, not a full end-to-end
suite.

## When To Add Tests

Add or update tests when changing:

- REST routes;
- proposal status transitions;
- sensitive read request status transitions and grant context shape;
- audit events;
- table schema;
- ability intake normalization;
- approval-commit contract;
- security or permission behavior.
- performance-sensitive bounded intake or indexed-filter behavior.

Fail-closed governance paths must be covered when changed:

- proposal and app-key row insert failures return stable `WP_Error` codes;
- write-like lifecycle events fail rather than reporting success when audit
  persistence fails;
- status changes that cannot be audited roll back to the previous status where
  Core can safely do so;
- lifecycle status changes that depend on a current state use conditional
  repository updates so stale transitions fail closed;
- app rate-limit changes keep the fixed-window increment atomic under the
  app/route/window uniqueness constraint;
- one-time app tokens are not returned when app creation cannot be audited.
- sensitive read grants are never returned when approval, expiry, ability, input,
  or audit requirements fail.

## Governance Hardening Matrix

The next Core hardening work should add tests that prove non-escalation rather
than broadening product behavior. These are the priority groups:

| Test group | Required proof |
| --- | --- |
| Proposal state transition matrix | Pending, approved, rejected, expired, archived, executed, and execution-failed records cannot jump to invalid lifecycle states, and public REST handlers preserve the same fail-closed code/status mapping. |
| Commit preflight race and duplicate handoff | Repeated or stale commit-preflight attempts must fail closed when they would reuse an expired, mismatched, or already-consumed handoff context. |
| Ability drift | Changed input schema, risk metadata, permission capability, required scopes, approval requirement, or execution guidance must block preflight or execution handoff instead of silently trusting stale approval. |
| App-key scope isolation | Trusted Adapter scopes stay additive; `proposals:approve`, `commit:preflight`, and `commit:record_execution` remain separately authorized, and each missing sensitive scope returns `403` with denied app-scope audit metadata. |
| Redaction persistence | Secret-shaped values, authorization headers, cookies, app tokens, and provider credentials are redacted before proposal or audit persistence. |
| Sensitive read one-time consumption | One-time read grants can be consumed once, and changed ability/input/expiry evidence blocks reuse. |
| Block theme malicious fixtures | Scriptable blocks, custom HTML/freeform, iframe/embed/shortcode, unknown blocks, oversized trees, and non-allowlisted templates are rejected before proposal storage. |
| From-plan static contracts | Plans outside allowlisted artifact types, target abilities, scopes, payload sizes, or action counts fail before proposal creation. |
| Audit completeness | Every approval, rejection, preflight, record-execution, policy denial, and fail-closed lifecycle decision has durable audit evidence or returns failure. |

Do not satisfy this matrix by adding Core execution, queues, workflow runtime,
MCP runtime, product recommendation logic, or final WordPress writes.

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
composer plugin-check:release
```

Also run `composer check:wporg` for reviewer-policy patterns that may not
surface as Plugin Check errors. In particular, option and transient names must
show the `npcink_governance_core` prefix at the WordPress API call site; a
variable-only call such as `set_transient( $prefixed_key, ... )` is not enough
for release review even when runtime code validates the prefix earlier.

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
