# AI Development Handoff Summary

Status: active.

This document summarizes the current development system for future AI agents.
It is the quick handoff entry after `AGENTS.md`, `.sisyphus/session-breadcrumb.md`,
`README.md`, and `docs/solo-ai-development-workflow.md`.

## Current Repository Boundary

`npcink-governance-core` is the Npcink AI governance layer for WordPress
operations.

Core owns:

- ability intake;
- proposal records;
- approval, rejection, and preflight boundaries;
- app-key governance;
- audit records;
- minimal governance REST/admin surfaces.

Core must not own:

- final WordPress write execution;
- article, media, comment, SEO, or toolbox product workflows;
- provider credentials, model routing, prompt or preset management;
- workflow runtime, task queues, batch execution consoles, MCP runtime, or
  Agent Gateway catalogs;
- reusable WordPress ability definitions, which belong in
  `npcink-abilities-toolkit` or another provider plugin.

## What Has Been Set Up

GitHub is now the development repository. WordPress.org SVN is release-only.

Completed development support:

- `Core CI` runs `composer validate --no-check-publish`, `composer test:all`,
  and `composer check:wporg` on pull requests and pushes to `master`.
- `master` is protected and requires the `Static contracts` status check.
- Pull request and issue templates record scope, boundary, verification, and
  release impact.
- Dependabot checks GitHub Actions and Composer metadata.
- Vulnerability alerts, automated security fixes, and private vulnerability
  reporting are enabled.
- `Release Package` is a manual GitHub Actions workflow that builds a review
  artifact only. It does not publish to WordPress.org.
- The `npcink-governance-core Release Board` GitHub Project tracks standing
  workstreams using `Release Stage` and `Gate` fields.

## Source Of Truth Rule

Human memory and chat history are not the source of truth for future AI work.

If guidance should constrain future work, record it in:

- repository docs;
- GitHub Issue bodies;
- pull request templates or checklists;
- static contracts or tests when the rule can be checked automatically.

Future agents must prefer durable repository and GitHub records over chat-only
instructions, unless the maintainer gives a newer explicit instruction.

## Required AI Workflow

For non-emergency work:

1. Start from a clean `master`.
2. Read startup docs:
   - `AGENTS.md`;
   - `.sisyphus/session-breadcrumb.md`;
   - `README.md`;
   - `docs/solo-ai-development-workflow.md`;
   - this file;
   - area-specific docs for the task.
3. Create or identify one GitHub Issue.
4. Add the issue to the `npcink-governance-core Release Board`.
5. Create a `codex/<task-name>` branch.
6. Implement the smallest coherent change.
7. Run the required verification gate.
8. Open a pull request using the repository template.
9. Merge only after `Static contracts` passes.
10. Update the Project item stage after merge.

## Current Project Backlog

Standing issues:

- [#2 Prepare next WordPress.org release](https://github.com/muze-page/npcink-governance-core/issues/2)
- [#3 Track WordPress.org reviewer feedback and recurring guards](https://github.com/muze-page/npcink-governance-core/issues/3)
- [#4 Improve LocalWP smoke reliability](https://github.com/muze-page/npcink-governance-core/issues/4)
- [#5 Harden Core boundary regression checks](https://github.com/muze-page/npcink-governance-core/issues/5)
- [#6 Track npcink-abilities-toolkit compatibility](https://github.com/muze-page/npcink-governance-core/issues/6)

Completed process issues:

- [#7 Document solo AI development workflow](https://github.com/muze-page/npcink-governance-core/issues/7)
- [#9 Document priority guardrails for AI development](https://github.com/muze-page/npcink-governance-core/issues/9)

This document was added under:

- [#11 Add AI development handoff summary](https://github.com/muze-page/npcink-governance-core/issues/11)

For the current-stage history behind the #5 boundary regression and #4 smoke
reliability slices, see
`docs/ai-development-workstream-summary.md`.

## Current Priority Order

Unless the maintainer gives newer instructions, future agents should prioritize:

1. [#5 Harden Core boundary regression checks](https://github.com/muze-page/npcink-governance-core/issues/5)

   This is the first recommended next workstream because frequent AI-assisted
   changes increase the risk of boundary drift. The durable guard should live
   in tests, docs, PR templates, and review checklists.

2. [#4 Improve LocalWP smoke reliability](https://github.com/muze-page/npcink-governance-core/issues/4)

   This is the second recommended next workstream because frequent code updates,
   plugin packaging, and WordPress.org releases need trustworthy local smoke
   evidence.

Do not start speculative GitHub automation, new product surface, or broad
refactors before #5 and #4 unless the maintainer gives a newer explicit
priority.

## Verification Gates

Documentation-only:

```sh
git diff --check
```

Code or static contract changes:

```sh
composer validate --no-check-publish
composer test:all
composer check:wporg
```

WordPress runtime behavior, activation, tables, REST routing, or toolkit
integration:

```sh
composer test:all
composer smoke:wp
```

WordPress.org release preparation:

```sh
composer prepare:release -- --version <version>
```

SVN release sync is release-only and must start with a dry run:

```sh
composer sync:wporg -- --version <version> --svn-dir /path/to/wporg-npcink-governance-core
```

## Release Boundary

GitHub Releases may record release history after WordPress.org publication, but
GitHub is not the WordPress.org publishing path.

For real WordPress.org releases:

1. Run the local release gate.
2. Build and inspect the package.
3. Run the SVN sync dry run.
4. Apply SVN sync only after review.
5. Commit to WordPress.org SVN.
6. Create a GitHub Release record with the Git commit, SVN revision, public URL,
   verification gates, and listing asset status.

## Handoff Prompt For Future AI

Use this prompt when starting another AI session:

```text
Read AGENTS.md, .sisyphus/session-breadcrumb.md, README.md,
docs/solo-ai-development-workflow.md, and
docs/ai-development-handoff-summary.md first.

Preserve the Core boundary: governance intake, proposals, approval/preflight,
app-key governance, and audit only. Do not add final WordPress write execution,
provider credentials, workflow runtime, MCP runtime, task queues, batch
execution consoles, product UX, or reusable ability definitions to Core.

Use GitHub Issues and the Release Board as the source of truth. Unless there is
a newer maintainer instruction, start with #5, then #4. Report the module,
boundary, and focused verification gate before editing.
```
