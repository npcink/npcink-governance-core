# Release Closeout - 2026-06-21

Status: release publication closeout record.

This note summarizes the current release history and the boundary decisions
that led to the Core `0.1.1`, Adapter `0.3.2`, and Toolkit `0.5.2` release
matrix.

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

## WordPress.org SVN Decision Point

WordPress.org SVN remains the release repository only. The next step is a
dry-run sync from the prepared package into a Core SVN checkout:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /path/to/wporg-npcink-governance-core
```

The dry run must be reviewed before any formal SVN apply or commit. A formal
commit should happen only if:

- the dry-run diff is limited to the expected `trunk/` release package and
  `tags/0.1.1`;
- `tags/0.1.1` does not already exist in SVN;
- no pre-existing, unrelated SVN working-copy changes are present;
- the public plugin identity remains `Npcink Governance Core` with slug,
  text domain, REST namespace, and package identity
  `npcink-governance-core`;
- the release package still respects Core's governance-only boundary.

Listing assets should be synced with `--assets` only when the modified
WordPress.org image exports are intentionally part of the release decision.
Do not include asset changes incidentally.

## SVN Dry-Run Result

Dry-run checkout:

```text
/Users/muze/wporg-svn/npcink-governance-core
```

SVN checkout revision:

```text
3580249
```

Dry-run command:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /Users/muze/wporg-svn/npcink-governance-core
```

Result:

- dry-run completed successfully;
- SVN working copy was restored to a clean local state after final
  verification;
- existing SVN tags list contained `0.1.0/` and did not contain `0.1.1/`;
- supplemental `rsync --dry-run --itemize-changes` showed 46 trunk changes
  from the existing WordPress.org `0.1.0` trunk to the prepared `0.1.1`
  package, including removal of the old `includes/Media/` directory and
  updates/additions under current Core governance, REST, security, assets, and
  language files;
- the dry run did not include `--assets`, so WordPress.org listing image
  exports were not part of this dry-run decision.

Decision: the WordPress.org SVN release appears ready for a formal
`--apply` and `svn commit`, but the formal SVN commit remains unperformed until
the maintainer explicitly approves that publish step.

Final verification found no committed WordPress.org SVN change. Any local
uncommitted SVN working-copy residue observed during verification was reverted
before closeout.
