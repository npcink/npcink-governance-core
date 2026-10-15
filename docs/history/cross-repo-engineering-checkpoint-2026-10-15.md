# Cross-repository engineering checkpoint — 2026-10-15

Status: local engineering acceptance passed; release publication and global
workspace cleanup are separate, incomplete states.

## Scope and identities

This session revalidated the approved closeout sequence without changing
runtime code or sibling owner files. Core remains governance truth; Adapter
owns signed execution after preflight; Toolkit owns reusable abilities.

The first no-override matrix passed 7/7 at `2026-10-15T07:45:04Z`.
Its Core topic `6639b95d` has the same tree as merged master `9716cf5e`.
The subsequent full release acceptance used these clean master commits:

| Repository | Commit | Plugin version |
| --- | --- | --- |
| Core | `9716cf5eaae259874c03ad40363382fd065aded2` | `0.3.0` |
| Adapter | `ab32e5031765bce0d72e2340510ff34d47b26706` | `0.4.3` |
| Toolkit | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | `0.7.0` |

AI Cloud PR #1115 merged as `0bb4d1fc`; shared topic `fca129f8` is
clean, pushed, tree-identical to master, and passed exact-SHA backend,
frontend, Secret scan, and both CodeQL checks. The prior dirty shared-root
blocker is resolved. Toolbox remains clean on the still-open PR #269 topic;
7/7 does not mean every family topic is merged.

## Verification and cleanup

- `composer quality:matrix:run -- --fail-on-dirty`: passed 7/7 with default
  paths and no detached override. This is source validation, not M4 runtime
  acceptance.
- `composer acceptance:cross-repo-release`: passed with no diagnostic skips.
  It covered Core source/WordPress/release/package, Adapter source/WordPress/
  release/package/install, real signed provider fixture, and Toolkit source/
  PHPStan/release/Docker/WordPress smoke. Toolkit minimum Docker profile ran
  WordPress 6.9.4/PHP 8.0; this is not a new Core M4 two-profile evidence run.
- Core `composer analyse:phpstan`: passed. Core unit suite: 55 tests /
  207 assertions. Toolkit source assertions: 17,128. Plugin Check reported
  zero errors; warnings remain distinct from errors.
- Real Ollama `qwen2.5:1.5b` success log matched proposal
  `b5794ef0-7b7c-462f-b7df-c47909fe2a50`, correlation
  `f437df16-6140-46ce-9be0-e347844e46ea`, and Adapter request
  `f1df16c4-6077-44f3-a4fe-896663e37261`. Duplicate refusal and readback
  passed; draft `282402` was deleted.
- Temporary SDK loader, marker loader, draft, host-specific discovery cache,
  and loopback SSH tunnel were removed. Provider registry returned to
  `npcink-cloud` only; temporary smoke containers were removed. Existing
  AI Cloud containers were preserved. No persistent provider configuration
  or plugin mount was changed.

## Environment recovery lessons

The first aggregate attempt stopped at the obsolete LocalWP database socket.
The active socket was confirmed against `magick-ai.local` and passed through
`WP_CLI_MYSQL_SOCKET` / `WP_DB_SOCKET`; no product fix was needed. Docker
Desktop was started for the Toolkit minimum profile.

M4 alternate-address SSH timed out, while documented LAN SSH still reached
the existing Ollama service and model. No service restart or model pull was
needed. A temporary logging-provider pattern initially included the tunnel
port; the AI plugin matches only the parsed hostname. That setup produced a
successful SDK request without matching persisted provider evidence, and the
strict fixture correctly failed. Matching the hostname fixed the setup. The
isolated fixture and then full aggregate passed without weakening assertions.

Local raw logs: `/tmp/npcink-release-acceptance-20261015-final.log` and
`/tmp/npcink-provider-fixture-20261015.log`. They are development evidence,
not real-user ledger input.

## Workspace audit

Read-only inventory at `2026-10-15T07:45:17Z` covered all 7 repositories,
28 registered worktrees, 98 local branches, remote refs, and open PRs.
All configured roots were clean. Five auxiliary worktrees were locked; one
locked AI Cloud worktree retained three untracked files. No worktree or
branch was deleted.

37 local branch tips were not contained by a fetched remote ref. Many have
merged squash PRs; this count is not a count of unmerged changes. In particular,
unpublished OCR branches, Toolkit `codex/zh-cn-docs-mirror-default`, Core
`zcode/docs-2026-10-11-ux-audit-arc`, and Addon
`codex/local-ocr-addon-adapter` require owner reconciliation. Preserve their
commits until PR/patch identity and keep/discard decisions are established.

| Repository | Registered worktrees | Open PRs |
| --- | ---: | --- |
| `npcink-abilities-toolkit` | 1 | none |
| `npcink-governance-core` | 1 | [#156](https://github.com/npcink/npcink-governance-core/pull/156), [#155](https://github.com/npcink/npcink-governance-core/pull/155) |
| `npcink-ai-client-adapter` | 1 | none |
| `npcink-workflow-toolbox` | 13 | [#269](https://github.com/npcink/npcink-workflow-toolbox/pull/269), [#265](https://github.com/npcink/npcink-workflow-toolbox/pull/265) |
| `npcink-cloud-addon` | 4 | [#262](https://github.com/npcink/npcink-cloud-addon/pull/262), [#261](https://github.com/npcink/npcink-cloud-addon/pull/261), [#260](https://github.com/npcink/npcink-cloud-addon/pull/260) |
| `npcink-eval-lab` | 1 | none |
| `npcink-ai-cloud` | 7 | [#1105](https://github.com/npcink/npcink-ai-cloud/pull/1105), [#1104](https://github.com/npcink/npcink-ai-cloud/pull/1104), [#1103](https://github.com/npcink/npcink-ai-cloud/pull/1103), [#1102](https://github.com/npcink/npcink-ai-cloud/pull/1102), [#1101](https://github.com/npcink/npcink-ai-cloud/pull/1101) |

The snapshot includes dependency PRs; their presence does not invalidate
the passed release triad acceptance. Toolbox #265/#269 are product changes
still open, so they are not reported as merged.

| Worktree path (relative to `/Users/muze/`) | HEAD | State |
| --- | --- | --- |
| `gitee/npcink-abilities-toolkit` | `921d1dc7` | clean |
| `gitee/npcink-governance-core` | `9716cf5e` | clean |
| `gitee/npcink-ai-client-adapter` | `ab32e503` | clean |
| `gitee/npcink-workflow-toolbox` | `90eb4376` | clean |
| `.codex/worktrees/local-ocr-bootstrap-slice-1` | `60062a18` | clean |
| `.codex/worktrees/local-ocr-bootstrap-slice-2` | `d74ffab9` | clean |
| `.codex/worktrees/local-ocr-bootstrap-slice-3` | `2f69d7ba` | clean |
| `.codex/worktrees/local-ocr-bootstrap-slice-4` | `a6842ee2` | clean |
| `.codex/worktrees/local-ocr-compat-wrapper` | `49f881e7` | clean |
| `.codex/worktrees/local-ocr-gate-core` | `d068af20` | clean |
| `.codex/worktrees/local-ocr-gate-implementation` | `b101e788` | clean |
| `.codex/worktrees/local-ocr-publish-gate/npcink-workflow-toolbox` | `13bc85a3` | clean |
| `.codex/worktrees/site-knowledge-recovery-status/npcink-workflow-toolbox` | `6db9e113` | clean |
| `.codex/worktrees/wp-ai-central-matrix/npcink-workflow-toolbox` | `d593c874` | clean |
| `gitee/.worktrees/toolbox-classification-smoke` | `d4bc1503` | clean |
| `gitee/.worktrees/toolbox-flagged-media-matrix` | `30e62706` | clean |
| `gitee/npcink-cloud-addon` | `0352ca11` | clean |
| `.codex/worktrees/041-final-release-record/npcink-cloud-addon` | `4e8c8e65` | clean |
| `.codex/worktrees/041-refresh-closeout/npcink-cloud-addon` | `000ca35f` | clean |
| `.codex/worktrees/local-ocr-addon-adapter/npcink-cloud-addon` | `0611cbb4` | clean |
| `gitee/npcink-eval-lab` | `5362655d` | clean |
| `gitee/npcink-ai-cloud` | `fca129f8` | clean |
| `.codex/worktrees/knowledge-capacity-clarity/npcink-ai-cloud` | `cfca8393` | clean; locked |
| `.codex/worktrees/local-release-readiness/npcink-ai-cloud` | `c24d3a34` | clean; locked |
| `.codex/worktrees/m4-resource-baseline/npcink-ai-cloud` | `9944f621` | dirty: 3 untracked files; locked |
| `.codex/worktrees/production-merge-20261015` | `7d539b1b` | clean; locked |
| `.codex/worktrees/usage-health-memory/npcink-ai-cloud` | `49d222ee` | clean; locked |
| `gitee/.worktrees/npcink-ai-cloud-m4-ops` | `0bb4d1fc` | clean |

The dirty AI Cloud resource-baseline files are
`docs/runtime-resource-baseline-v1.md`, `scripts/runtime_resource_baseline.py`,
and `tests/dev/test_runtime_resource_baseline.py`. Full local inventories are
`/tmp/npcink-closeout-audit-20261015.json` and
`/tmp/npcink-closeout-branch-ownership-20261015.json`; regenerate them before
acting because parallel owners can change state.

## Release and product posture

Ordinary RC metadata matrix passed: Core `0.3.0`, Adapter `0.4.3`, Toolkit
`0.7.0`. Strict conventional tag readiness failed at historical Core
`v0.3.0` (`3357f916`); Toolkit `0.7.0` also points to historical `46c14054`.
No tag was moved or created. Prefer a separately named stack snapshot RC
when an actual release objective is selected; do not bump plugin versions
merely to make an engineering checkpoint green. Final publication still
requires fresh exact-revision evidence and manual release checks.

ADR-010 checkpoint: the documented execution-consumer baseline remains the
Adapter (one implementation); real production usage is not established in
this local-development session. No local automation runtime release evidence
or new hard Core-execution audit requirement was reported. No ownership
trigger change was established, and no Core execution feature was added.

Owner reconfirmed **local development only** on 2026-10-15. The real-usage
ledger stays empty; first-week kickoff is prepared and parked. Classification
lane 1 still needs a real present-administrator editor action. Fixtures must
not be relabeled as that manual evidence or as real usage.

No tag push, GitHub release, SVN publication, or production deployment was
performed. Historical and breadcrumb updates accumulate on the weekly docs
follow-up branch; this session does not create a separate docs-only PR.
