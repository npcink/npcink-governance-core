# Release Closeout Standard

Status: active operational standard.

This document records the release method proven during the Core 0.2.0,
Adapter 0.3.3, and Toolkit 0.5.5 closeout. It separates product readiness,
artifact identity, publication, and workspace housekeeping so that one green
signal is not overstated as proof of another.

## Scope

Use this standard for a coordinated release involving:

- `npcink-governance-core` as governance truth;
- `npcink-ai-client-adapter` as the signed client and post-Core execution
  boundary;
- `npcink-abilities-toolkit` as the reusable WordPress ability provider;
- the central matrix in `npcink-workflow-toolbox` for wider family status.

It does not transfer workflow runtime, queues, provider credentials, reusable
ability definitions, product UX, or final WordPress execution into Core.

## Four Independent Completion States

Never collapse these states into a single "done" claim:

| State | Required proof |
| --- | --- |
| Candidate verified | Repository gates, real WordPress smoke, package checks, and required cross-repository acceptance pass against named commits. |
| Artifact identified | The release ZIP is reproducible and its checksum is bound to the exact source and dependency revisions. |
| Git release published | Conventional annotated tags exist remotely and peel to the verified commits. |
| Workspace housekeeping complete | Every registered worktree and relevant local/remote topic branch has been audited; dirty or owner-held work is preserved or explicitly resolved. |

WordPress.org SVN publication is a fifth, separately authorized operation. A
Git tag does not prove that SVN was updated, and SVN must not be changed merely
because a candidate or tag exists.

## Required Sequence

### 1. Freeze Boundaries And Candidate Identities

1. Confirm the Core, Adapter, and Toolkit responsibilities still match their
   product and architecture contracts.
2. Record exact candidate commits and plugin versions.
3. Require clean participating checkouts before generating evidence.
4. Run `composer rc:version-matrix` and reject a conventional tag that already
   points to different history. Never move or reuse a released tag.

### 2. Prove Each Repository Locally

Run the repository-owned gates rather than replacing them with one aggregate
script. For Core this includes at least:

```bash
composer validate --no-check-publish
composer test:all
composer analyse:phpstan
composer smoke:wp
composer release:verify
```

Activation, table, REST, or Toolkit-dependent changes require the real
WordPress smoke. Static tests alone do not prove the installed WordPress path.

### 3. Bind The Release Artifact

Build from Git-tracked inputs only. Normalize archive ordering, timestamps,
and metadata, reject forbidden paths, and prove two equivalent builds produce
the same SHA-256. Record:

- source commit and source archive SHA-256;
- dependency commit and archive SHA-256 when applicable;
- release ZIP SHA-256;
- installed-from-ZIP status;
- actual WordPress, PHP, and Docker versions;
- assertion totals and completion status.

A checksum without reproducible inputs is not sufficient artifact identity.

### 4. Run Exact-Revision M4 Compatibility

When Docker is hosted on the M4 machine, use the repository M4 runner. Core
must install the exact release ZIP; Toolkit must exercise its declared minimum
and current WordPress/PHP profiles. Evidence must be revision-bound and fresh.

After a protected squash merge, regenerate evidence for the merged commit.
Evidence for the PR head, a pre-merge commit, or an earlier documentation
commit is stale even when the packaged PHP files appear unchanged.

Only a completed runner and accepted evidence file count. A silent SSH session
is not a pass. If the remote workload has exited while the local SSH process is
still hung, interrupt the runner, confirm its cleanup trap removed the Compose
projects and temporary workspace, then rerun. Do not hand-author or reuse old
evidence to bypass the failure.

### 5. Prove The Real Signed Chain

Run the full cross-repository release acceptance without diagnostic skips:

```bash
NPCINK_TOOLKIT_WORDPRESS_SMOKE_EVIDENCE=/absolute/path/to/exact-evidence.json \
composer acceptance:cross-repo-release
```

The Adapter fixture must prove proposal creation, Core approval, commit
preflight, Adapter-owned ability execution, outcome recording, duplicate
execution rejection, readback, and fixture cleanup. Do not use
`--skip-adapter-fixture` for a release claim.

### 6. Run The Central Matrix

From `npcink-workflow-toolbox`:

```bash
php scripts/cross-repo-quality-matrix.php --run-gates --fail-on-dirty
```

This proves the configured repository roots are clean and their gates pass. It
does not inspect every additional worktree registered by those repositories.
Perform the separate worktree audit below before claiming global cleanup.

### 7. Publish Tags Conservatively

Immediately before tagging, fetch the remote and confirm each `origin/master`
still equals the verified commit and each target tag is absent. Create an
annotated tag at the explicit commit, push only that tag ref, then verify the
remote peeled commit:

```bash
git ls-remote origin refs/tags/TAG 'refs/tags/TAG^{}'
```

Repository moves and stale proxy settings are transport failures, not reasons
to retag. Resolve the canonical remote and preserve the already-created local
tag object. Do not force a tag.

### 8. Audit Worktrees And Branches Separately

For every participating or family repository:

```bash
git status --short --branch
git worktree list --porcelain
git branch -vv
git branch --no-merged origin/master
git branch -r
gh pr list --state open
```

Then run `git status --short --branch` inside every listed worktree. A clean
main checkout does not imply clean auxiliary worktrees.

Do not automatically delete:

- a dirty worktree;
- a locked worktree owned by another task;
- a branch reported as unmerged after squash merge;
- a remote branch without checking its PR and ownership;
- a missing-path worktree registration before confirming it contains no
  recoverable administrative state.

`git branch --no-merged` is not authoritative after protected squash merges,
because the topic commit is not an ancestor of the squash commit. Use PR state,
reviewed diff or patch equivalence, and owner confirmation before cleanup.

## Failure Classification

### Signed Adapter Returns 401

A raw request to a private Adapter route may correctly return 401. A signed CLI
request returning 401 is different. Diagnose it in this order:

1. Confirm the profile file exists with restrictive permissions and inspect
   only non-secret metadata such as site URL, profile name, creation time, and
   declared scopes.
2. Confirm the profile targets the active LocalWP site and Adapter namespace.
3. Confirm Core, Adapter, and Toolkit are active.
4. Confirm WordPress still holds the approved public-key record.
5. If the site database was reset and the public-key record is absent, pair a
   new local key and approve it as the intended administrator.
6. Rerun the standalone fixture before rerunning the complete acceptance.

Never print the private JWK, application password, token, or raw credential
record. Never mark acceptance green by skipping the signed fixture.

### LocalWP Cannot Reach MySQL

Treat a missing LocalWP socket as environment drift before changing product
code. Prefer an explicit `WP_CLI_MYSQL_SOCKET`, then the documented legacy
path, then bounded discovery under Local's run directory. Report the selected
socket path but do not persist local passwords in the repository.

### Local Docker Is Unavailable

Do not treat a missing local Docker socket as a compatibility failure. Generate
exact-revision M4 evidence and pass it to the existing release verifier. The
evidence lane replaces only the duplicate local Docker leg, not PHPStan,
LocalWP, Plugin Check, packaging, or cross-repository acceptance.

### Warnings Versus Blockers

Plugin Check warnings must be reviewed and recorded, but a known warning is not
an error. Conversely, an assertion failure, stale evidence, missing signed
fixture, dirty participating root, or tag mismatch is a blocker and must not be
relabelled as noise.

## Closeout Record

The final record should state:

- exact commits, versions, tags, and package hashes;
- gates and M4 profiles that passed;
- whether tags were pushed;
- whether WordPress.org SVN was published;
- whether only release roots are clean or all registered worktrees were
  audited and cleaned;
- remaining open PRs, dirty owner-held worktrees, and external follow-up;
- confirmation that remote Docker containers and temporary workspaces were
  removed.

The correct stopping rule is: close the release session when the authorized
release objective is complete and remaining items are explicitly classified.
Do not claim that the entire development environment is clean when unrelated
owner-held branches or worktrees remain.
