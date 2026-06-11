## Scope

- [ ] This change is limited to the stated module.
- [ ] Public REST, data shape, lifecycle, or product boundary docs were updated if changed.
- [ ] No unrelated generated files or local environment files are included.

## Core Boundary

- [ ] Core remains the governance layer for ability intake, proposals, approval/preflight, and audit.
- [ ] This does not add final WordPress write execution to Core.
- [ ] This does not add provider credential storage, model routing, workflow runtime, MCP runtime, Agent Gateway catalogs, task queues, batch execution consoles, or product workflow UX.
- [ ] Reusable WordPress ability definitions remain outside Core, in `npcink-abilities-toolkit` or another provider plugin.

## Verification

- [ ] `composer validate --no-check-publish`
- [ ] `composer test:all`
- [ ] `composer check:wporg`
- [ ] `composer smoke:wp` if the change touches activation, tables, REST routing, WordPress runtime behavior, or `npcink-abilities-toolkit` integration.
- [ ] `composer prepare:release -- --version <version>` if preparing a WordPress.org release.

## Release Impact

- [ ] No release impact.
- [ ] Requires a GitHub release package build.
- [ ] Requires WordPress.org SVN sync after local release gate passes.
