# Resume After A Break

Status: active.

This page is the shortest path back into Core work after days or weeks away
(for example after the 2026-08 gap month). It points at state, it does not
duplicate state: every fact below lives in exactly one maintained source, and
this page only names the order to read them.

## Shortest Path (about ten minutes)

1. `git status --short --branch`, then sync: `git checkout master && git pull
   --ff-only origin master`.
2. Read `.sisyphus/session-breadcrumb.md` — the rolling newest sessions. The
   last entry names the open thread and its owner decisions. Older months are
   in `.sisyphus/archive/YYYY-MM.md` and are read only when the task needs
   that period.
3. Read the `README.md` doc index and this page. Do not read all docs; the
   index is grouped by authority and `docs/history/` is frozen.
4. Check the live backlog: `gh issue list --state open`. Standing tracking
   issues are expected to stay open; look for the newest filed issues and the
   latest PR titles (`gh pr list --state merged --limit 10`).
5. Read `docs/next-stage-plan.md` — the "Not implemented" list is the current
   milestone truth.
6. For cross-repo work, start from
   `/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`, not
   from Core-local copies.

## Gate Cheat Sheet

- Every code change: `composer test:all` (lint, static contracts, fail-closed,
  PHPUnit unit suite, release package reproducibility, M4 evidence behavior).
- WordPress runtime behavior or Toolkit integration: add `composer smoke:wp`.
- Before `composer pr:publish`: advisory `ocr review --from origin/master --to
  HEAD`; treat findings as second opinion, fix or record.
- Static contracts and the CI advisory review both report the full failure
  list in one run — read all of it before editing.

## Known Pitfalls (each has cost a session before)

- `wp eval-file` includes files inside a method scope: top-level variables are
  local. Helpers must declare `global` explicitly (see the smoke fixture
  tracker comment).
- Never run `msgattrib --no-obsolete` on the bundled zh_CN catalog: obsolete
  entries include the contracted Toolbox menu label. Regeneration steps are in
  `docs/development-workflow.md`.
- LocalWP's MySQL socket path changes with the run identifier;
  `scripts/wp-cli-local.sh` discovers it — do not hardcode the socket.
- HTTP 503 right after a smoke run usually means a stale `.maintenance` file
  in the WordPress root.
- Adding a Composer dev dependency on a newer local PHP can lock packages the
  CI floor (PHP 8.0) cannot install; `config.platform.php` pins resolution,
  keep it.
- Pinned phrases in `tests/run.php` must survive unwrapped in one line; if a
  contract fails after a docs edit, check line wrapping first.
- Browser visual smoke is a release-gate and admin-surface-change requirement,
  not a per-fix-round requirement; cadence rules are in
  `docs/development-workflow.md`.

## What Not To Do When Re-entering

- Do not relitigate settled decisions: ADR-001 through ADR-010, the docs
  authority inventory, and the boundary hard blocks in `AGENTS.md` stand
  unless a new ADR or owner decision changes them.
- Do not re-read `docs/history/` to reconstruct "context" — it is frozen
  evidence, not current truth.
- Do not start speculative refactors before checking the open issues; if the
  gap was long, the first session back should be small and gated end to end.
