# AGENTS.md — Npcink Governance Core

## Session Startup Protocol

Every new AI development session should start with:

1. Run `git status --short --branch`.
2. Read `.sisyphus/session-breadcrumb.md` (rolling newest sessions only).
   Older session history lives in `.sisyphus/archive/YYYY-MM.md`; consult an
   archive file only when the task needs that period.
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
- Multiple AI sessions may run in parallel across the repo family. Work on a
  sibling repository only through a dedicated worktree (for example under
  `/Users/muze/gitee/.worktrees/`); never switch a sibling repo's shared main
  checkout to your branch, and restore it to master if you already did. Before
  publishing or tagging, `git fetch` and re-verify `origin/master`; before
  blaming Core for a LocalWP site fatal, check sibling checkout mtimes first.
- Breadcrumb "pending" claims go stale within hours under parallel sessions.
  Re-verify each one against live repository state (git status, tags, remote
  log, matrix) before acting on it, and record the recalibration instead of
  trusting the note (stale-toolkit, stale-tag, and stale-unpushed-commit
  reads all happened on 2026-10-08/09).
- Before touching a sibling shared root, confirm no session is actively
  working it: take two `git status --short` snapshots a few minutes apart and
  check tracked-file mtimes. If anything moved between snapshots, hands off.
- If a sibling root must return to master but a dedicated worktree holds the
  master branch name, detach that worktree at its current commit
  (content-identical, evidence binds SHAs not branch names) and check master
  out in the shared root. Preserve superseded branches that hold unpushed
  commits instead of deleting them; the keep/discard call belongs to the
  owner.
- Before filing an ADR, list `docs/decisions/` and take the next free number:
  parallel sessions file ADRs the same day, and duplicate numbers have
  happened twice (2026-10-08 ADR-011, 2026-10-09 ADR-012). When statuses
  differ (accepted versus proposed), renumber-with-note is the mechanical
  fix; contradictory accepted verdicts need owner arbitration.
- Do not run `git reset --hard`, `git checkout -- .`, or equivalent destructive
  cleanup unless the user explicitly asks for that exact operation.
- For cross-repo milestones, use the central matrix from
  `/Users/muze/gitee/npcink-workflow-toolbox` (run `composer quality:matrix`
  for status and `composer quality:matrix:run` with `--fail-on-dirty` before
  multi-repo closeout in that repository) instead of copying the script into
  Core.
- Publish a completed clean topic branch with
  `composer pr:publish -- --title "<title>" --body-file <path>`. Start the body
  from `.github/pull_request_template.md`; do not replace it with ad hoc
  `gh pr create --body` text that omits `Scope`, `Boundary`, `Verification`, or
  `Risk`.
- The publisher checks the current `origin/master` baseline, creates the PR,
  and requests protected squash auto-merge. It never bypasses required checks
  and never deletes local or remote branches, because this repository commonly
  uses multiple worktrees.
- The cross-repository contract is
  `/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/pr-publishing-standard-v1.md`.

## Verification Gates

Default gate:

```bash
composer test:all
```

Advisory AI review gate (run before `composer pr:publish`):

```bash
ocr review --from origin/master --to HEAD
```

Treat findings as a second opinion: fix real defects or record why they are
acceptable. Follows AI Code Review Standard v1 in `npcink-workflow-toolbox`
`docs/platform/ai-code-review-standard-v1.md`; the CI workflow posting the same
review on pull requests is advisory and never a required check.

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
- batch docs-only closeout records: historical records and breadcrumb
  updates that are not part of a code change accumulate locally and are
  published as one weekly docs PR through `composer pr:publish` instead of
  one PR per session (master protection still requires the PR flow);
- update `.sisyphus/session-breadcrumb.md` when the session changes project
  direction or leaves important next steps;
- keep `.sisyphus/session-breadcrumb.md` bounded: it must hold at most 10
  session entries. When appending an entry makes it exceed 10, move the oldest
  entries into `.sisyphus/archive/<session-month>.md` in the same commit;
- report changed files, commit hash, and verification results.
