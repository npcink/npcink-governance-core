# AI Development Workstream Summary

Status: active.

Authority note: Cross-project AI development coordination now starts from
`/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`. This Core
document is retained as Core-local workstream history and stop guidance.

This document summarizes the recent AI-development workstreams so future
maintainers and agents can understand what was done, why it was worth doing,
and when to continue.

## Context

The repository is now maintained through frequent AI-assisted changes. That
makes two risks more important than ordinary feature throughput:

- Core boundary drift: an agent may accidentally turn Core into a product
  workbench, workflow runtime, task queue, MCP runtime, provider settings
  surface, or final WordPress executor.
- Weak local smoke evidence: a `composer smoke:wp` failure may be caused by
  LocalWP targeting, WP-CLI/PHP/socket setup, Toolkit mounting, Toolkit
  contract drift, or a real Core regression.

The current work did not add product features. It strengthened review evidence,
static contracts, and smoke diagnostics so future changes can move faster with
less ambiguity.

## Completed Workstreams

### AI Development Handoff

Completed in issue #11 and PR #12.

The handoff summary added `docs/ai-development-handoff-summary.md` as a quick
entrypoint for future agents after `AGENTS.md`, `.sisyphus/session-breadcrumb.md`,
`README.md`, and `docs/solo-ai-development-workflow.md`.

It records:

- the Core ownership boundary;
- the issue-first and PR-first AI workflow;
- the standing backlog;
- the current priority order;
- release and WordPress.org SVN boundaries;
- a paste-ready startup prompt for future AI sessions.

### Core Boundary Regression Checks

Completed as current-stage slices under issue #5:

- PR #13: hardened boundary regression checks.
- PR #14: expanded runtime drift marker scans.

The current baseline now guards:

- pull request and boundary-review templates that must state the Core boundary;
- forbidden legacy confirmation behavior;
- execution routes such as `execute`, `proxy-execute`, `jobs`, `tasks`, and
  `runs`;
- provider credential and model-routing markers;
- workflow runtime, MCP runtime, Agent Gateway, task queue, worker, scheduler,
  executor, batch execution console, and operator runtime console markers;
- runtime-shaped PHP filenames and symbols inside plugin runtime files.

The intent is not to make static contracts perfect. The intent is to catch the
most likely AI-assisted boundary drift early, before review has to inspect a
larger implementation.

### LocalWP Smoke Reliability

Completed as current-stage slices under issue #4:

- PR #15: added smoke preflight diagnostics.
- PR #16: documented smoke failure classification.

The smoke wrapper now prints preflight diagnostics for:

- repository root;
- `WP_PATH`;
- WP-CLI path;
- Local PHP path;
- MySQL socket;
- Core plugin symlink;
- Toolkit plugin file;
- Toolkit replay fixture candidate.

Smoke failures are now classified before changing code:

- `[smoke:preflight:fail] environment:` means LocalWP, WP-CLI, PHP, socket,
  plugin directory, or Core symlink targeting is wrong.
- `[smoke:preflight:fail] toolkit:` means `npcink-abilities-toolkit` is not
  mounted where WordPress can load it.
- post-preflight `[fail]` lines from `tests/smoke-wp.php` mean WordPress loaded
  and the failure is a Core or Toolkit contract regression that needs
  assertion-level investigation.

## Current Baseline

The current baseline is intentionally conservative:

- Core stays the governance layer for ability intake, proposals,
  approval/preflight, app-key governance, and audit.
- Core does not own final WordPress execution, product workflow UX, provider
  credentials, model routing, prompt or preset management, workflow runtime,
  task queues, batch execution consoles, MCP runtime, or Agent Gateway catalogs.
- Reusable WordPress ability definitions remain in `npcink-abilities-toolkit`
  or another provider plugin.
- GitHub remains the development repository.
- WordPress.org SVN remains release-only.
- LocalWP smoke remains the required runtime confidence gate for activation,
  tables, REST routing, and Toolkit integration.

## Verification Evidence

The merged slices were verified with the appropriate gates:

- `git diff --check`;
- `composer validate --no-check-publish`;
- `composer test:all`;
- `composer check:wporg`;
- `composer smoke:wp` for LocalWP smoke reliability slices.

GitHub `Static contracts` passed on the corresponding pull requests before
merge.

## Current Stop Point

This line of work should pause at the current baseline.

Continue #5 only when:

- a review finds a concrete new Core boundary drift pattern;
- a static contract misses a real prohibited runtime/product marker;
- a future feature creates a new boundary-sensitive surface that needs a guard.

Continue #4 only when:

- `composer smoke:wp` fails and the current classification is insufficient;
- release preparation exposes a recurring LocalWP smoke setup gap;
- Toolkit compatibility work needs clearer provider-boundary diagnostics.

Do not keep adding guards merely because more strings or checks are possible.
The current value is in making the common failure modes obvious, not in turning
the repository into a generic policy scanner.

## Why This Was Worth Doing

The work was worth doing because it converted chat-only process guidance into
durable repository evidence:

- future agents get a concrete startup path instead of relying on memory;
- boundary drift is checked earlier and more mechanically;
- smoke failures are easier to classify before changing code;
- issue comments record why #4 and #5 are paused rather than forgotten.

The work should not expand into speculative automation right now. The next
useful step should be triggered by a real review finding, smoke failure,
release-preparation issue, or maintainer priority change.
