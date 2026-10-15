# Release Candidate Version Matrix

Status: active RC preparation checklist.

This document records how to freeze the current Core + Adapter + Toolkit
release candidate without collapsing repository ownership boundaries.

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
