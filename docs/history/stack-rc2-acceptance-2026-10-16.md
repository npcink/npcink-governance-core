# Stack RC2 candidate and acceptance — 2026-10-16

Status: exact candidate validated; not tagged or published. Default shared
roots still contain owner work. This record does not supersede the published
snapshot manifest.

## Change envelope and candidate decision

The owner explicitly selected Cloud Addon `f0e2a58` and Toolbox `569da58` for a
new candidate. Use internal candidate id `stack-rc2-2026-10-16`; this is not a
created or reserved Git tag. Core is pinned to merged `origin/master` at
`7904f5b`, before this documentation closeout. The remaining four repositories
retain their earlier exact revisions.

The focused module is release coordination. Only Core historical/planning
records change; public REST, data shape, lifecycle, and governance boundaries
are unchanged. No sibling owner files, runtime code, tags, SVN, deployment,
or real-usage ledger rows are modified. Required gates are the central full
matrix and existing exact three-repository release acceptance. Rollback is a
revert of this documentation commit, with no change to published tags.

The machine-readable manifest is
[stack-rc2-manifest-2026-10-16.json](stack-rc2-manifest-2026-10-16.json).

| Repository | Pinned revision | Gate checkout |
| --- | --- | --- |
| Core | `7904f5bec66b859bcc68f0f9b06a53cfeb0af5c0` | clean default |
| AI Cloud | `fe9207965f4a5049f80736019114f360186ec96b` | clean dedicated worktree |
| Cloud Addon | `f0e2a5854bbe373e45240b23b5fce30b00df57fa` | clean dedicated worktree |
| Adapter | `eb73e503a8c102acd6f87b69f406ee4b94aac85f` | clean default |
| Toolkit | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | clean default |
| Toolbox | `569da58106c877fadb65316e4375a8bd3d1f6b1e` | clean dedicated worktree |
| Eval Lab | `5362655d9f27f9db9a3bcc8a7717e75ac1d9659f` | clean default |

Compared with the published manifest, Cloud Addon includes OCR workflow
maintenance and closeout documentation. Toolbox includes the contextual ALT
card fix and internal-link stage-zero evidence. Core includes the merged
coordination records. This closeout commit is excluded from the candidate;
future documentation does not silently update its pinned revision.

## Complete seven-repository matrix

The default `composer quality:matrix:run -- --fail-on-dirty` report generated
at `2026-10-16T12:09:32+00:00` had seven passing gate rows but one dirty
Toolbox owner document and returned exit 1. Subsequent live runs also saw
AI Cloud frontend owner changes and Cloud Addon owner documentation changes.
They were preserved, not reset or silently incorporated into the candidate.

The exact candidate run at `2026-10-16T12:15:50+00:00` used the central
matrix with `--fail-on-dirty` and explicit clean worktrees for Toolbox,
AI Cloud, and Cloud Addon. All seven gate rows passed, with dirty count zero
in all tested checkouts and exit 0. AI Cloud authority remains required
GitHub CI for exact clean `fe920796`; M4 service deployment was not requested.

The report SHA-256 is
`8d01d5bc2a12be32fa3c581a1e00f93f466b7d31fa432b28a060b41b42fec1b3`.
This proves the pinned candidate. It does not prove that all default shared
roots are clean, or that auxiliary owner worktrees are disposable.

## Exact acceptance

`composer acceptance:cross-repo-release` passed with exit 0 at
`2026-10-16T12:27:22Z` against Core `7904f5b`, Adapter `eb73e503`, and
Toolkit `921d1dc7`, with no diagnostic skip flags. This existing wrapper
covers these three layers; the other four revisions are covered by the full
central matrix, not additional release scripts invented for this run.

Passed evidence includes:

- Core validation, 55 tests / 207 assertions, WordPress smoke, Plugin Check,
  release verification and reproducible packaging;
- Adapter complete WordPress smoke with 27 temporary read grants enabled via
  `MAA_ADAPTER_SMOKE_CREATE_READ_GRANTS=1`, release/package verification and
  package install/restore;
- signed M4 Ollama `qwen2.5:1.5b` fixture, persisted provider-log correlation,
  execution readback, duplicate refusal and draft cleanup;
- Toolkit tests, PHPStan, official WordPress 6.9.4/PHP 8.0 Docker smoke
  (478 assertions), LocalWP default smoke (479 assertions), flagged-media
  checks, light profile (58 assertions), and packaged Plugin Check;
- ordinary RC version matrix, preserving historical Core `v0.3.0` and Toolkit
  `0.7.0` tags. Adapter `v0.4.3` is still available; no conventional tag was
  created.

The successful log contains zero `[skip]` entries. Its SHA-256 is
`005d1307d98d3a9c1983968aefe40124a71b7ce2ff9d8df86797bfe3180007fe`.

The first full run exceeded Composer's default 300-second process timeout.
The interrupted run left subscriber fixture id `166`, registered at
`2026-10-16 12:17:15`, causing a reproducible `existing_user_login` failure
on the next Toolkit run. After verifying that exact fixture identity, only
that timeout-owned subscriber was removed. The narrow Toolkit smoke passed,
and the final full run passed with `COMPOSER_PROCESS_TIMEOUT=1800`. No product
or test source was changed to obtain the pass.

The final run removed the temporary SDK loader, marker fixture, SSH tunnel,
model-discovery cache, drafts and Docker smoke resources. The Core symlink
remains the shared Core checkout; the provider registry is `npcink-cloud`
only. The diagnostic subscriber is absent. Existing owner resources were
preserved.

The three task-created verification worktrees and their temporary dependency
symlinks were removed after the reports were saved. Owner worktrees were not
removed.

## Publication state

The existing snapshot remains unchanged:

- tag: `stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`;
- tag object: `ffb5e5d6d7b68fa952288524a181ff688a7698ee`;
- peeled commit: `ed28f984153200a8362fcd0928bbb32b83636159`.

No new tag was created or pushed and no retag occurred. RC2 is a separately
validated candidate, not a release of the current dirty shared roots. Before
any future publication, resolve owner state or explicitly select clean pinned
checkouts, recheck this manifest and evidence, and obtain authorization for a
new tag name. Historical closeout records accumulate for the weekly docs PR.
