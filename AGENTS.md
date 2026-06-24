# AGENTS.md — Npcink Governance Core

## Session Startup Protocol

Every new AI development session should start with:

1. Run `git status --short --branch`.
2. Read `.sisyphus/session-breadcrumb.md`.
3. Read `README.md`.
4. Read these docs when the task touches their area:
   - `docs/product-positioning.md`
   - `docs/architecture.md`
   - `docs/governance-contract.md`
   - `docs/rest-api-contract.md`
   - `docs/database-schema.md`
   - `docs/security-model.md`
   - `docs/ability-intake-contract.md`
   - `docs/approval-commit-contract.md`
   - `docs/development-workflow.md`
   - `docs/testing-strategy.md`
   - `docs/next-stage-plan.md`
   - `docs/decisions/ADR-001-rebuild-core-as-governance-layer.md`
   - `docs/decisions/ADR-002-no-workflow-runtime-in-core.md`
5. Briefly report the current module, relevant boundary, and intended focused
   gate before editing.

## Product Boundary

Npcink Governance Core is the WordPress AI operation governance layer.

Core owns:

- ability intake;
- proposal records;
- approval/rejection status;
- future approval-commit authorization;
- audit logs;
- minimal governance REST/admin surfaces.

Core does not own:

- article, media, comment, SEO, or toolbox product workflows;
- model routing, provider keys, prompt/preset management, or cloud billing;
- workflow runtime, workflow/task queues, batch execution consoles, MCP
  runtime, or Agent Gateway task catalogs;
- reusable WordPress ability definitions, which belong in
  `/Users/muze/gitee/npcink-abilities-toolkit`.

## Hard Blocks

Do not introduce:

- legacy `confirm_token` or `write_confirmed` behavior;
- copied code from `npcink-root/npcink-abilities-toolkit/includes/open-platform/**`;
- workflow definition registries or `workflow/*` runtime ownership;
- Agent Gateway catalogs/projections;
- MCP runtime;
- Content Assistant product UX;
- provider credential storage;
- workflow/task queue, batch execution, or operator runtime console code.

Core may still use governance-specific review terms such as Review Queue,
pending proposal queue, bounded bulk rejection, and `plan_to_proposal_batch`.
Those are proposal lifecycle records and review affordances, not workflow/task
queue ownership or batch execution.

If a feature needs any of the above, stop and write a boundary note instead of
implementing it inside Core.

## Development Rules

- Check `git status --short --branch` before edits.
- Keep changes scoped to one module per session.
- For AI-assisted work, write a short change envelope before editing: target
  repositories, focused module, intended change, explicit non-goals, public
  contracts touched, expected files, files or areas that must not change,
  required gates, cross-repo matrix requirement, and rollback plan.
- Update docs when public REST, data shape, lifecycle, or product boundary
  changes.
- Add or update `tests/run.php` static contracts for public behavior.
- Run `composer test:all` for every code change.
- Run `composer smoke:wp` when behavior depends on WordPress activation, tables,
  REST routing, or `npcink-abilities-toolkit`.
- Stage only files changed for the current task. Do not use `git add -A`.
- Do not run `git reset --hard`, `git checkout -- .`, or equivalent destructive
  cleanup unless the user explicitly asks for that exact operation.
- For cross-repo milestones, use the central matrix from
  `/Users/muze/gitee/npcink-toolbox` instead of copying the script into Core:
  `composer quality:matrix` for status and `composer quality:matrix:run` before
  multi-repo closeout.

## Verification Gates

Default gate:

```bash
composer test:all
```

WordPress smoke gate:

```bash
composer smoke:wp
```

Composer metadata:

```bash
composer validate --no-check-publish
```

Before finishing a code session, run the narrowest useful gate and report
exactly what passed or failed.

## Local WordPress Context

The local WordPress smoke environment is documented in
`docs/development-workflow.md`. Do not write the local admin password into repo
files. The memory note keeps it redacted.

## Session Closeout

Before final response:

- run the relevant verification gate;
- commit if the task produced a complete change;
- update `.sisyphus/session-breadcrumb.md` when the session changes project
  direction or leaves important next steps;
- report changed files, commit hash, and verification results.
