# Release Candidate Version Matrix

Status: active RC preparation checklist.

This document records how to freeze the current Core + Adapter + Toolkit
release candidate without collapsing repository ownership boundaries.

## Current separate candidate: RC2 — 2026-10-16

`stack-rc2-2026-10-16` now includes the explicitly selected Cloud Addon
`f0e2a58` and Toolbox `569da58`, with Core `7904f5b` and the other four pinned
revisions. The exact seven-repository matrix passed 7/7 with exit 0 using
three clean worktree overrides; the full three-layer release acceptance passed
with zero skips. Default shared roots still contain separate owner work.

See [the RC2 manifest and acceptance record](history/stack-rc2-acceptance-2026-10-16.md)
and [machine-readable manifest](history/stack-rc2-manifest-2026-10-16.json).
The existing published snapshot is unchanged. No new tag or retag was performed;
subsequent documentation commits are outside this candidate manifest.

## Current Candidate Versions

The current stack-level RC candidate is:

| Repository | Role | Expected plugin version | Conventional release tag | Tag readiness |
| --- | --- | --- | --- | --- |
| `npcink-governance-core` | Governance layer | `0.3.0` | `v0.3.0` | Blocked: `v0.3.0` already points to historical commit `3357f916`; do not move it. |
| `npcink-ai-client-adapter` | Thin channel layer | `0.4.3` | `v0.4.3` | Available if the current Adapter commit is the release candidate; the current master exposes 0.4.3 and the conventional tag is not yet created. |
| `npcink-abilities-toolkit` | Ability implementation layer | `0.7.0` | `0.7.0` | Historical `0.7.0` points to `46c14054`; current master still exposes 0.7.0, but the exact-HEAD tag guard rejects the mismatch even for docs-only drift. |

Run the machine check from Core:

```bash
composer rc:version-matrix
```

That command verifies each plugin header version, version constant, and
`readme.txt` `Stable tag`, then reports whether the conventional tag can point
at the current repository HEAD.

## RC identity decision — 2026-10-16

The refreshed status-only central matrix was generated at
`2026-10-16T04:58:45+00:00`; all seven default roots were clean. The exact
revision manifest is:

| Repository | Exact revision | Branch / state | Declared version |
| --- | --- | --- | --- |
| Core | `8d9626b7a07ffca7126b400a404a6db05145d1e9` | `codex/docs-classification-lane1-2026-10-16` | `0.3.0` |
| AI Cloud | `fe9207965f4a5049f80736019114f360186ec96b` | detached clean acceptance head | service revision |
| Cloud Addon | `d8bcac9a192b04ad4aa2cc899cef32cdd6afe404` | `master` | `0.4.1` |
| Adapter | `eb73e503a8c102acd6f87b69f406ee4b94aac85f` | `codex/m4-pgy-stack-rc-docs-2026-10-15` | `0.4.3` |
| Toolkit | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | `master` | `0.7.0` |
| Toolbox | `216e10230f8e120903c6bb476268ff9d263d556e` | `codex/internal-link-recommendation-followup` | `0.6.0` |
| Eval Lab | `5362655d9f27f9db9a3bcc8a7717e75ac1d9659f` | `main` | development-only |

The exact RC identity decision is the stack snapshot name
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`. This name is
reserved as the candidate identity; no tag has been created or pushed.
Cloud Addon, Toolbox, AI Cloud, and Eval Lab remain bound by the exact
revision manifest rather than by a conventional plugin tag.

The refreshed `composer rc:version-matrix` reports Core `v0.3.0` at historical
commit `3357f916899ac4b013a753da6d99e712274f2221`, Adapter `v0.4.3` available,
and Toolkit `0.7.0` at historical commit
`46c1405422ae0ac1f8ea6820b391b48d09326a14`. The `--require-tag-ready` check
therefore fails as designed; it is not permission to move either historical
tag. A remote lookup confirms the proposed stack snapshot tag name is absent.
The exact-manifest `composer acceptance:cross-repo-release` rerun passed with
exit 0 at `2026-10-16T05:44:19Z` using Core `8d9626b`, Adapter `eb73e503`, and
Toolkit `921d1dc7`. Core, Adapter, and Toolkit source, WordPress, package,
release, signed-provider, PHPStan, Docker, and duplicate-refusal checks passed;
all temporary acceptance resources were cleaned. The subsequent Core docs
commit `4a23d35` is coordination-only; it is deliberately not substituted into
this candidate manifest. The snapshot tag remains uncreated and unpushed, and
explicit authorization is still required for any tag action.

## Final documentation closeout — 2026-10-16

PR #169, which recorded the exact-manifest acceptance, merged as
`34d75e2dc00804320a82086f175d69e7e5f14a76` at `2026-10-16T05:51:39Z`.
The selected stack identity remains an untagged candidate. Before publication,
refresh the seven-root matrix, confirm the exact manifest has not drifted, and
rerun exact-manifest acceptance if it has. Prepare reproducible artifact
checksums and release notes, then obtain explicit authorization before creating
or pushing the snapshot tag. Historical Core and Toolkit tags remain fixed;
SVN publication, production deployment, and real-usage recording are separate
authorized actions.

## Matrix refresh and manifest drift — 2026-10-16

The default `composer quality:matrix:run -- --fail-on-dirty` refresh completed
at `2026-10-16T06:10:45+00:00`. All seven gate rows passed, while the overall
command returned exit 1 because the Toolbox default root has two owner-held
working-tree changes (`docs/README.md` modified and
`docs/session-handoff-2026-10-16-internal-link-related-content-history.md`
untracked).

The selected manifest is unchanged for AI Cloud `fe920796`, Adapter `eb73e503`,
Toolkit `921d1dc7`, Toolbox `216e102`, and Eval Lab `5362655`. It drifted for
Core (`8d9626b` → `1995342`) and Cloud Addon (`d8bcac9` → `633c62f`). Both
drifts are documentation-only commits, but the candidate must not silently
absorb them. Release notes and reproducible checksums are therefore deferred
until the owner state is reconciled, the manifest is deliberately refreshed,
and exact-manifest acceptance passes again. No snapshot tag was created or
pushed.

## Owner closeout and revised acceptance — 2026-10-16

Toolbox remained owner-held after two stable snapshots: modified
`docs/README.md` and untracked
`docs/session-handoff-2026-10-16-internal-link-related-content-history.md`.
The shared root was not changed. A revision-bound clean copy may be used for
diagnostic gates, but it does not prove the shared root clean.

The revised three-repository acceptance passed with Core `ec01042`, Adapter
`eb73e503`, and Toolkit `921d1dc7` at approximately
`2026-10-16T14:39:02+08:00`, including M4 provider-log correlation, duplicate
refusal, package/release gates, PHPStan, Docker smoke, and cleanup. Core and
Adapter package digests were recorded as provisional run evidence only.

Cloud Addon was still moving during owner verification: `ac82a3b` was followed
by `5f223af`. That current owner work must be deliberately included or
excluded before the seven-root RC manifest can be frozen. Final release notes
and reproducible checksums remain deferred; no snapshot tag was created or
pushed.

## Tag Preparation Rule

Do not retag historical releases.

If the matrix reports `tag_status=exists_at_other_commit`, choose one of these
paths before final publication:

1. Bump that plugin's version and create a new conventional release tag after
   its repository gates pass.
2. Keep the plugin version unchanged and create a clearly named stack RC
   snapshot tag that is not the conventional release tag.

The previous Core `v0.1.0`, Adapter `v0.4.2`, and Toolkit `0.5.1` tags are
historical and must not be moved. The current matrix uses versions
`0.3.0`, `0.4.3`, and `0.7.0` to audit candidate metadata; passing the ordinary
matrix does not mean all three conventional tags are available.

At the 2026-10-15 local engineering checkpoint, full cross-repo acceptance
passed and the ordinary matrix passed, while `--require-tag-ready` stopped at
the existing Core tag. The preferred preparation path is a separate stack
snapshot RC, preserving plugin versions and historical tags. No snapshot or
release tag was created. Final publication requires a named release objective,
fresh exact-revision evidence, and the remaining manual release checks.

## Required Gate Before Any Tag

Before creating any stack RC or release tag, run:

```bash
composer acceptance:cross-repo-release
composer rc:version-matrix -- --require-tag-ready
```

Use `--require-tag-ready` only for a final conventional release-tag decision.
It intentionally fails if a repository's conventional tag already exists at a
different commit.

For a stack snapshot RC where Adapter and Toolkit versions remain unchanged,
run:

```bash
composer acceptance:cross-repo-release
composer rc:version-matrix
```

Then create a tag name that cannot be mistaken for a plugin release tag, for
example:

```bash
git tag stack-rc-2026-09-05-core-0.2.0-adapter-0.3.3-toolkit-0.5.5
```

Only push the tag after the exact matrix output and cross-repo acceptance result
are recorded in the release notes or pull request.

## Boundary

The matrix is release coordination only.

- Core remains the proposal, approval, preflight, execution-result recording,
  and audit truth owner.
- Adapter remains the signed AI-client channel and post-Core execution profile
  owner.
- Toolkit remains the WordPress Abilities API definition and callback owner.

The matrix must not become a dependency resolver, package bundler, workflow
runtime, updater, or second control plane.

## Final stable manifest and package evidence — 2026-10-16

The owner state closed and the default `composer quality:matrix
-- --fail-on-dirty` passed 7/7 with exit 0 at
`2026-10-16T10:26:15+00:00`. The final manifest is documented in
[Stack RC Release Notes — 2026-10-16](release-candidate-notes-2026-10-16.md):
Core `ed28f98`, AI Cloud `fe920796`, Cloud Addon `4978338`, Adapter `eb73e503`,
Toolkit `921d1dc`, Toolbox `ee814e0`, and Eval Lab `5362655`.

The exact three-repository acceptance passed against Core `ed28f98`, Adapter
`eb73e503`, and Toolkit `921d1dc7`. Reproducible Core and Adapter package
digests and the two-build Toolkit deterministic archive digest are recorded in
the release notes. This evidence does not create or imply a tag: historical
Core/Toolkit tags remain fixed. The named snapshot tag was later published at
the explicitly authorized verified Core commit; the publication record is in
the release notes.

## Snapshot tag published — 2026-10-16

After the final 7/7 matrix, checksum recheck, and exact acceptance evidence
were confirmed unchanged, the authorized annotated tag
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0` was created and
pushed without force. The remote tag object is
`ffb5e5d6d7b68fa952288524a181ff688a7698ee`; the peeled commit is the verified
Core candidate `ed28f984153200a8362fcd0928bbb32b83636159`.

This stack tag is coordination metadata for the exact seven-repository
manifest. It does not move Core `v0.3.0` or Toolkit `0.7.0`, and it does not
publish to WordPress.org SVN or deploy production.

## Post-publication checkout drift — 2026-10-16

After publication, Cloud Addon `master` advanced to `6c9d3c4` via dependency
update PR #279, and Toolbox acquired one owner-held uncommitted documentation
change. These are post-tag live checkout states and are excluded from the
published manifest. Do not force-move or recreate the snapshot tag; any future
candidate must receive its own matrix and acceptance evidence.

## Post-tag audit refresh — 2026-10-16

The live default matrix passed 7/7 with exit 0 at
`2026-10-16T11:29:44+00:00`. Cloud Addon `f0e2a58` and Toolbox `569da58` are
clean but are newer than the published manifest (`4978338` and `ee814e0`).
The existing snapshot tag remains unchanged and valid for its recorded exact
manifest. No new candidate was selected; any decision to include these newer
revisions must create a separate manifest and acceptance run, followed by a
new tag name rather than retagging the published one.
