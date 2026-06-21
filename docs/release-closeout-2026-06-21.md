# Release Closeout - 2026-06-21

Status: release publication closeout record.

This note summarizes the current release history and the boundary decisions
that led to the Core `0.1.1`, Adapter `0.3.2`, and Toolkit `0.5.2` release
matrix.

## Historical Summary

This stage started from repeated boundary checks around whether Core was clear
enough to release and whether any implementation had drifted outside the
governance layer. The answer at closeout is that the boundary is clear enough
for the current release candidate: Core is the review, approval, preflight,
and audit truth; Adapter remains the signed channel and execution bridge; and
Toolkit remains the ability definition and dry-run preview owner.

The main product decision was to stop broadening Core. Any work that smelled
like article workflow UX, media workflow UX, model routing, provider
configuration, workflow runtime, task queues, batch execution, MCP runtime, or
Agent Gateway catalog ownership was treated as outside Core. Release work was
therefore limited to governance contracts, boundary wording, release gates,
WordPress.org packaging hygiene, and local validation tooling.

Three pressure points were resolved before release closeout:

- plan handoffs stay admitted only through explicit allowlists and proposal
  review, with Toolkit-owned ability ids where Toolkit owns the capability;
- Cloud Addon and Adapter are documented as external-owner surfaces rather
  than Core-owned control planes;
- `record-execution` is an external execution-result recording route, not Core
  execution ownership.

The release candidate then moved from boundary cleanup to publication
readiness. WordPress.org listing copy, translation drafts, Plugin Check
diagnostics, version matrix checks, cross-repository acceptance, package
builds, and LocalWP smoke checks were tightened without adding runtime
responsibility to Core.

Late in the release process, local validation noise was handled as tooling
hygiene instead of product scope: `scripts/wp-cli-local.sh` centralizes local
WP-CLI defaults, and the smoke test stopped using deprecated
`get_page_by_title()`. These post-tag changes improve local release
confidence, but they do not change the already-published Core `v0.1.1` release
artifact.

The current stop point is deliberate:

- GitHub release records are public for Core `v0.1.1`, Adapter `v0.3.2`, and
  Toolkit `0.5.2`;
- WordPress.org SVN has been formally published for Core `0.1.1` at revision
  `3580253`;
- the WordPress.org download package now resolves to `Npcink Governance Core`
  `0.1.1`;
- the remaining publication work is public page cache observation and
  translate.wordpress.org `Stable Readme` submission.

## Product Boundary

Npcink Governance Core remains the WordPress AI operation governance layer.
It owns ability intake, proposal records, approval and rejection lifecycle,
future approval-commit authorization, audit logs, scoped app-key access, and
minimal governance REST/admin surfaces.

Core does not own article, media, comment, SEO, or toolbox product workflows.
It does not route models, store provider credentials, own prompt presets,
manage cloud billing, execute final WordPress writes, run workflows, own task
queues, host an MCP runtime, or provide Agent Gateway catalogs. Reusable
WordPress ability definitions remain outside Core and belong in
`npcink-abilities-toolkit` or another provider plugin.

The release stack keeps three separate responsibilities:

- `npcink-governance-core`: governance truth, proposal lifecycle, preflight,
  and audit.
- `npcink-ai-client-adapter`: thin signed AI-client/channel layer that executes
  only after Core approval and commit preflight, then records the result back
  to Core.
- `npcink-abilities-toolkit`: WordPress Abilities API definitions, schemas,
  metadata, dry-run previews, and host-governed callbacks.

## Version Matrix

| Repository | Role | Release version | Tag | Peeled commit |
| --- | --- | --- | --- | --- |
| `npcink-governance-core` | Governance layer | `0.1.1` | `v0.1.1` | `1829fb828ea4d9e8f7caabea74d5b89117824355` |
| `npcink-ai-client-adapter` | Channel adapter | `0.3.2` | `v0.3.2` | `65f1012c5f4072dd6c1251f2e2db98d5e836c598` |
| `npcink-abilities-toolkit` | Ability provider | `0.5.2` | `0.5.2` | `98974bd613da8091c5bd648ef515c67afe254c29` |

The stack release-candidate tag is:

```text
stack-rc-2026-06-21-core-0.1.1-adapter-0.3.2-toolkit-0.5.2
```

It points to the Core release commit
`1829fb828ea4d9e8f7caabea74d5b89117824355`.

Historical tags were not moved. Core `v0.1.0`, Adapter `v0.3.1`, and Toolkit
`0.5.1` remain historical release records.

## Why Core 0.1.1 Exists

Core `v0.1.0` had already been pushed before the release-candidate matrix
found an annotated-tag comparison issue. The matrix script compared annotated
tag object SHAs instead of peeled commit SHAs.

The fix was to update the matrix check to compare `${tag}^{}` and reserve
Core `0.1.1` for the corrected candidate. The release process deliberately
did not move the already-pushed `v0.1.0` tag.

## Verified Gates

The release candidate was accepted after the following gates passed:

- `composer test:all`
- `composer rc:version-matrix -- --require-tag-ready`
- `composer acceptance:cross-repo-release`
- `composer prepare:release -- --version 0.1.1`
- `composer plugin-check:release`
- LocalWP three-package install smoke using Core, Adapter, and Toolkit release
  packages.

These gates are release acceptance checks only. They do not turn Core into the
owner of Adapter execution, Toolkit abilities, product workflows, model
routing, or runtime queues.

## Release Artifacts

Core and Toolkit GitHub draft release assets were published on 2026-06-21.
Adapter `v0.3.2` was already published as a public GitHub release and was not
replaced.

| Package | GitHub release | GitHub release asset digest |
| --- | --- | --- |
| `npcink-governance-core.zip` | `https://github.com/muze-page/npcink-governance-core/releases/tag/v0.1.1` | `sha256:898ce2b03771506b473236a5fe9539a8edc6b2d23f502eeab8541fe1cc0c34d9` |
| `npcink-ai-client-adapter.zip` | `https://github.com/muze-page/npcink-ai-client-adapter/releases/tag/v0.3.2` | `sha256:f1a1fa431b0550286566973a3d03bda8ae76c785ba598253a1f72d4bc3ff9a44` |
| `npcink-abilities-toolkit-0.5.2.zip` | `https://github.com/muze-page/npcink-abilities-toolkit/releases/tag/0.5.2` | `sha256:75d225dc78c74421e967d0ae1da3d8809fa576cd3528761736c4f0f32137fe99` |

Do not replace Adapter `v0.3.2` unless a future release decision explicitly
requires a new Adapter patch version.

## Post-Tag Release Hygiene

Two Core commits landed after `v0.1.1`:

- `6fce6c1 Add local WP-CLI wrapper`
- `b0db79a Remove deprecated smoke title lookup`

These are local release/smoke hygiene changes on `master`. They reduce WP-CLI
and WordPress deprecation noise in the local validation path. They do not
change Core's runtime ownership boundary, and they do not justify moving the
already-created `v0.1.1` release tag.

If those local tooling improvements must be represented inside a published
release artifact, the correct path is a new patch release such as `0.1.2`, not
retagging `v0.1.1`.

## WordPress.org SVN Publication

WordPress.org SVN remains the release repository only. Core `0.1.1` was
published from the prepared package into the official checkout:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /Users/muze/wporg-svn/npcink-governance-core --assets
composer sync:wporg -- --version 0.1.1 --svn-dir /Users/muze/wporg-svn/npcink-governance-core --assets --apply
svn commit /Users/muze/wporg-svn/npcink-governance-core -m "Release npcink-governance-core 0.1.1"
```

The formal SVN commit is:

```text
Committed revision 3580253.
```

The sync included `--assets`, so the refreshed WordPress.org listing image
exports were intentionally part of the release.

## SVN Publication Result

SVN checkout:

```text
/Users/muze/wporg-svn/npcink-governance-core
```

SVN checkout base revision before publication:

```text
3580249
```

Published SVN paths:

- `trunk/` updated to the `0.1.1` release package;
- `tags/0.1.1/` added;
- `assets/banner-1544x500.png`, `assets/banner-772x250.png`,
  `assets/icon-256x256.png`, and `assets/icon-128x128.png` updated.

Verification after publication:

- official SVN `tags/0.1.1/readme.txt` reports `Stable tag: 0.1.1`;
- official SVN `trunk/npcink-governance-core.php` reports
  `Plugin Name: Npcink Governance Core` and `Version: 0.1.1`;
- `https://downloads.wordpress.org/plugin/npcink-governance-core.zip`
  resolves to a package containing `Npcink Governance Core` `0.1.1`;
- public plugin page cache may lag behind SVN and download package refresh.
