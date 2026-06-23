# Metadata Apply Bridge Closeout - 2026-06-22

## Status

This line is closed. The metadata apply bridge work has been implemented,
verified, merged, and documented as a cross-repository boundary record.

No additional Core implementation is recommended for this stage.

## Historical Context

The session started from boundary questions:

- whether the current project positioning was clear;
- whether any work was overflowing the project boundary;
- what the next stage should focus on;
- whether to continue implementation, merge, or stop.

The conclusion was that the boundary is clear and should not be expanded:

- `npcink-governance-core` is the WordPress AI operation governance layer.
- `npcink-abilities-toolkit` owns reusable WordPress abilities and plan
  builders.
- `npcink-ai-client-adapter` stays a thin client/channel bridge and may forward
  explicitly supported plans into Core governance.
- `npcink-toolbox` stays an editor/operator-facing planning and handoff
  surface.

The useful next stage was therefore not new Core capability. It was execution
closure across Toolkit, Adapter, and Toolbox so that metadata suggestions could
be proven end to end while remaining reviewable, approveable, and auditable.

## Boundary Decisions

Core remains governance-only. It owns ability intake, proposal records,
approval and rejection status, future approval-commit authorization, audit
logs, and minimal governance REST/admin surfaces.

Core still does not own:

- article, media, comment, SEO, or Toolbox product workflows;
- model routing, provider keys, prompt or preset management, or cloud billing;
- workflow runtime, workflow or task queues, batch execution consoles, MCP
  runtime, or Agent Gateway task catalogs;
- reusable WordPress ability definitions.

The metadata apply bridge is valid only because it routes through the existing
governed proposal lifecycle. It does not add a second workflow runtime, a
generic write executor, or a new product UX inside Core.

## Adapter Work Completed

Repository: `/Users/muze/gitee/npcink-ai-client-adapter`

- Branch: `codex/metadata-apply-adapter-bridge`
- Commit: `1480fdd Add content metadata apply plan bridge smoke`
- Pull request: <https://github.com/muze-page/npcink-ai-client-adapter/pull/14>
- Merge commit: `f8a76cd9c1b21534e74494a1fda99bc4afd4ed44`

Changes:

- Added `npcink-abilities-toolkit/build-content-metadata-apply-plan` to the
  Adapter supported plan ability allowlist.
- Added static contract coverage for the supported plan ability surface.
- Added a WordPress smoke path that:
  - creates temporary post, category, and tag fixtures;
  - submits Adapter
    `POST /npcink-openclaw-adapter/v1/proposals/from-plan`;
  - verifies Core creates one batch proposal;
  - approves and executes the proposal;
  - verifies excerpt, category, and tag writes through approved abilities.
- Updated the supported plan ability hash snapshot.

Verification:

- `composer test:all` passed.
- `composer smoke:wp` passed.
- `git diff --check` passed.

Boundary:

- Adapter only gained an explicit allowlist and smoke proof for this governed
  plan bridge.
- Adapter did not absorb Core governance logic.
- Adapter did not become a workflow runtime, provider router, or generic write
  executor.

## Toolbox Work Completed

Repository: `/Users/muze/gitee/npcink-toolbox`

- Branch: `codex/metadata-apply-toolbox-bridge`
- Commit: `3c3dfd2 Cover metadata apply Adapter bridge`
- Pull request: <https://github.com/muze-page/npcink-toolbox/pull/18>
- Merge commit: `11fceae57ef40d3d3fd946a5e454e413dd40a583`

Changes:

- Extended `tests/smoke-content-metadata-delta.php`.
- Preserved the existing direct Core from-plan proof.
- Added optional Adapter bridge coverage:
  - if the Adapter route exists, the smoke submits the same Toolbox apply plan
    through Adapter;
  - verifies one pending Core batch proposal;
  - verifies the same action count;
  - verifies non-commit proposal semantics;
  - verifies `plan_to_proposal_batch`;
  - verifies the `content_metadata_apply` preview is preserved.
- If Adapter is absent, the smoke logs a skip so standalone Toolbox installs
  remain valid.
- Improved REST helper failure messages with HTTP status and error code.
- Added static contract text stating that Content Metadata Delta smoke covers
  the editor-facing Adapter from-plan bridge.

Verification:

- `composer smoke:metadata-delta` with Adapter inactive passed and skipped the
  optional Adapter bridge.
- `composer smoke:metadata-delta` with Adapter active passed and verified the
  Adapter bridge.
- `composer test:all` passed.
- `git diff --check` passed.

Boundary:

- Toolbox remains an operator-facing planning and handoff surface.
- Toolbox does not require Adapter to be active.
- Toolbox does not approve, execute, or directly write WordPress content.

## Merge Closeout

Both pull requests were first opened as drafts, then checked for mergeability.
Both were `MERGEABLE` and `CLEAN`, with no required status checks reported.

Merge order:

1. Adapter pull request #14 was marked ready and merged first.
2. Toolbox pull request #18 was marked ready and merged second.

Both remote PR branches were deleted after merge and local remotes were pruned.

## Local State Notes

Core was clean when this closeout document was written.

Adapter had a local `codex/metadata-apply-adapter-bridge` branch whose upstream
branch had been deleted after merge. Adapter also had unrelated local release
or translation files that were intentionally left untouched.

Toolbox had a separate local branch,
`codex/zhihu-topic-research-editor-flows`, preserving unrelated work at commit
`5e37b39 Add Zhihu topic research editor flows`. That branch was not part of
the metadata apply bridge closeout.

The local WordPress smoke environment required temporary plugin activation
changes while validating Core, Toolkit, Toolbox, and Adapter behavior. Adapter
was restored to inactive for the Toolbox standalone smoke path.

WP-CLI produced PHP 8.5 deprecation noise from CLI color handling during local
commands. The commands still succeeded, and the noise was treated as local
tooling output rather than product behavior.

## Final Decision

Stop here for this line.

The metadata apply bridge now has:

- clear project boundary;
- end-to-end Adapter bridge proof;
- Toolbox coverage for both standalone and Adapter-active installs;
- merged PRs;
- recorded verification;
- no Core boundary expansion.

Future work should be handled as separate scope:

- local branch and dirty-file housekeeping;
- the unrelated Toolbox Zhihu topic research branch;
- optional editor UI/browser smoke;
- release packaging or publication steps.

