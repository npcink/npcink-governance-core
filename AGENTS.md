# AGENTS.md — Magick AI Core

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

Magick AI Core is the WordPress AI operation governance layer.

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
- workflow runtime, queues, batch consoles, MCP runtime, or Agent Gateway task
  catalogs;
- reusable WordPress ability definitions, which belong in
  `/Users/muze/gitee/magick-ai-abilities`.

## Hard Blocks

Do not introduce:

- legacy `confirm_token` or `write_confirmed` behavior;
- copied code from `magick-ai-root/magick-ai/includes/open-platform/**`;
- workflow definition registries or `workflow/*` runtime ownership;
- Agent Gateway catalogs/projections;
- MCP runtime;
- Content Assistant product UX;
- provider credential storage;
- batch/queue/operator console code.

If a feature needs any of the above, stop and write a boundary note instead of
implementing it inside Core.

## Development Rules

- Check `git status --short --branch` before edits.
- Keep changes scoped to one module per session.
- Update docs when public REST, data shape, lifecycle, or product boundary
  changes.
- Add or update `tests/run.php` static contracts for public behavior.
- Run `composer test:all` for every code change.
- Run `composer smoke:wp` when behavior depends on WordPress activation, tables,
  REST routing, or `magick-ai-abilities`.
- Stage only files changed for the current task. Do not use `git add -A`.

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
