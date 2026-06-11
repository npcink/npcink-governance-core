# Solo AI Development Workflow

Status: active.

This repository is primarily developed by one maintainer using AI coding
agents. The workflow is intentionally lightweight, but every change must still
leave reviewable evidence in GitHub.

For a condensed handoff summary that future agents can read after this workflow,
see `docs/ai-development-handoff-summary.md`.

## Operating Principle

Use GitHub Issues for intent, pull requests for execution evidence, and local
Composer gates for runtime confidence.

Human memory is not the source of truth for AI-agent process guidance. If a
recommendation should constrain future work, record it in this repository's
docs, the relevant GitHub Issue, or both. Chat-only guidance is useful for the
current session, but future agents are expected to follow durable repository and
GitHub records.

AI agents must preserve the Core product boundary: Core owns ability intake,
proposal records, approval/preflight boundaries, app-key governance, and audit
records. Core must not add final WordPress write execution, provider credential
storage, model routing, workflow runtime, MCP runtime, Agent Gateway catalogs,
task queues, batch execution consoles, or product workflow UX.

## Required Task Flow

1. Start from a clean `master`.

```sh
git checkout master
git pull --ff-only origin master
git status --short --branch
```

2. Create or identify one GitHub Issue before implementation.

The issue should state:

- the user-facing goal;
- the module to touch;
- the Core boundary that must be preserved;
- the narrowest required verification gate;
- whether the change affects WordPress.org release readiness.

3. Add the issue to the `npcink-governance-core Release Board` project.

Use `Release Stage` for delivery state:

```text
Backlog -> In Progress -> Needs Smoke -> Ready For Release -> SVN Prepared -> Released
```

Use `Gate` for the strongest required verification:

```text
Static CI | Local Smoke | Release Package | WordPress.org SVN | Docs Only
```

4. Create a task branch.

```sh
git checkout -b codex/<short-task-name>
```

5. Implement the smallest coherent change.

Keep the change scoped to one module. Do not include generated build output,
local environment files, unrelated cleanup, or changes from another repo.

6. Run the appropriate gate.

For docs-only changes:

```sh
git diff --check
```

For code or contract changes:

```sh
composer validate --no-check-publish
composer test:all
composer check:wporg
```

For WordPress runtime behavior:

```sh
composer test:all
composer smoke:wp
```

For release preparation:

```sh
composer prepare:release -- --version <version>
```

7. Open a pull request.

The pull request body must complete the repository PR template. Link the issue
with `Closes #<issue-number>` when the PR should close the issue.

8. Use a separate review pass before merging.

For AI-assisted work, the implementer and reviewer should be different agent
contexts when practical. The review should check:

- boundary drift;
- missing or weak verification;
- accidental release-package changes;
- WordPress.org review risk;
- unrelated file churn.

9. Merge only after `Static contracts` passes.

`master` is protected and requires PR review flow plus the `Static contracts`
status check. Administrator bypass is only for emergency release repair.

10. Update the Project item.

After merge, move the issue to the next accurate `Release Stage`. Do not mark
release work as `Released` until the WordPress.org SVN release and GitHub
release record are complete.

## AI Agent Start Checklist

Every AI agent resuming work should:

1. Run `git status --short --branch`.
2. Read `.sisyphus/session-breadcrumb.md`.
3. Read `README.md`.
4. Read this file and the area-specific docs for the task.
5. Report the module, Core boundary, and focused verification gate before
   editing.

If the task is not tied to an existing issue, create or ask for one before
implementation unless the change is an urgent one-line repair.

## Current Priority Guardrails

When there is no newer maintainer instruction, prioritize the current backlog in
this order:

1. [#5 Harden Core boundary regression checks](https://github.com/muze-page/npcink-governance-core/issues/5)

   This is the first recommended next workstream because the repository is now
   developed through frequent AI-assisted changes. Future agents should improve
   static contracts, docs, and review checklists that prevent Core from drifting
   into product UX, provider credentials, workflow runtime, MCP runtime, task
   queues, batch execution consoles, or final WordPress write execution.

2. [#4 Improve LocalWP smoke reliability](https://github.com/muze-page/npcink-governance-core/issues/4)

   This is the second recommended next workstream because frequent code updates,
   plugin packaging, and WordPress.org releases depend on trustworthy local
   smoke evidence. Future agents should make smoke failures easier to diagnose
   and keep LocalWP, WP-CLI, database socket, plugin symlink, and
   `npcink-abilities-toolkit` assumptions documented.

Do not start speculative GitHub automation, new product surface, or broad
refactors before these two workstreams unless the maintainer gives a newer
explicit priority.

## Issue Labels

Use labels to make AI triage explicit:

- `core-boundary` for scope or authority questions;
- `release-blocker` for items blocking a GitHub or WordPress.org release;
- `wporg` for WordPress.org packaging, SVN, or reviewer work;
- `smoke-required` when LocalWP smoke must run before merge or release;
- `docs-required` when public docs must change with behavior;
- `toolkit-contract` when `npcink-abilities-toolkit` compatibility is involved;
- `security` for authorization, secret, app-key, or sensitive-read risk;
- `dependencies`, `github-actions`, and `composer` for dependency maintenance.

## Seed Backlog

The first project backlog should contain these standing issues:

- [#2 Prepare next WordPress.org release](https://github.com/muze-page/npcink-governance-core/issues/2);
- [#3 Track WordPress.org reviewer feedback and recurring guards](https://github.com/muze-page/npcink-governance-core/issues/3);
- [#4 Improve LocalWP smoke reliability](https://github.com/muze-page/npcink-governance-core/issues/4);
- [#5 Harden Core boundary regression checks](https://github.com/muze-page/npcink-governance-core/issues/5);
- [#6 Track npcink-abilities-toolkit compatibility](https://github.com/muze-page/npcink-governance-core/issues/6).

These are not placeholders for process theater. They are the recurring work
streams that should capture concrete PRs as the plugin evolves.
