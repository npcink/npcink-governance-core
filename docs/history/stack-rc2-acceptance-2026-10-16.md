# Stack RC2 candidate and acceptance — 2026-10-16

Status: fixed RC2 remains validated and untagged. The owner closeout and full
no-override default matrix passed 7/7, exit 0 at `2026-10-17T01:39:54Z`.
Current roots drift from RC2; the fixed manifest and published snapshot remain
unchanged. See [the latest closeout](stack-rc2-owner-closeout-2026-10-17.md).

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

## Default shared-root owner refresh — 2026-10-16 12:43 UTC

This follow-up targets release coordination in Core only. Expected files are
this record, the separate audit JSON, the two current-stage pointers and the
bounded breadcrumb/archive. Public contracts and runtime code are unchanged.
Sibling roots, owner files, active branches, M4 services, and all tags must not
be changed by this audit. Required gates are the full central matrix with
`--fail-on-dirty`, no repository overrides or filtering, Core documentation
checks, and a read-only remote tag check. Rollback is a documentation revert.

Owner activity was reverified rather than inferred from the earlier closeout:

| Default root | Observed state | Owner closeout |
| --- | --- | --- |
| AI Cloud | clean topic `3ad10d70`; [PR #1119](https://github.com/npcink/npcink-ai-cloud/pull/1119) open | Required `frontend` CI still running its critical paths. Owner retains M4 until protected merge and clean-master promotion. |
| Cloud Addon | clean `master` at `0ae75fe` | [PR #282](https://github.com/npcink/npcink-cloud-addon/pull/282) merged at `12:39:24Z`; shared root restored by its owner. Further read-only reconciliation evidence is still being collected. |
| Toolbox | `master` at `569da58`, three modified files | Owner is waiting for M4 release before retrieval/browser verification and publication. Handoff document and two test files were preserved. |

Two correctly parsed Toolbox status/mtime snapshots 125 seconds apart were
stable, but the owner session remained active. Stability does not transfer
ownership. No shared-root branch switch, staging, commit, stash, or cleanup
was performed by this audit.

The full command was:

```bash
composer quality:matrix:run -- --fail-on-dirty --json --output=/tmp/npcink-rc2-default-owner-matrix.json
```

All seven rows used `path_source=default` under `/Users/muze/gitee`. At
`2026-10-16T12:43:50+00:00`, Toolkit, Core, Adapter, Toolbox, Addon and Eval Lab
passed. AI Cloud returned exit 78 / `needs_validation` because exact clean
`3ad10d70` had pending `frontend` CI. Toolbox also had dirty count 3:

- `docs/session-handoff-2026-10-16-internal-link-related-content-history.md`;
- `tests/run.php`;
- `tests/smoke-editor-progressive-browser.mjs`.

The matrix therefore returned exit 1 with two blocking conditions. It is a
6/7 gate result, not a successful default-root 7/7. Toolbox's passing gate
includes its uncommitted test changes and does not validate a new immutable
revision. Full report and SHA observations are retained in
[the owner audit JSON](stack-rc2-default-owner-audit-2026-10-16.json).
The original report SHA-256 is
`e783eaa7e60c4ff1627dbef40009b0e876ad1501ea511a93d329a5a758ad0e2f`.

### RC2 drift decision and remaining order

The pinned RC2 manifest is unchanged. Relative to that manifest:

- Core `cac1689` contains only the preceding coordination records; it remains
  excluded from the pinned `7904f5b` candidate.
- AI Cloud `3ad10d70` includes frontend error handling and tests beyond pinned
  `fe920796`; this is runtime source drift, not documentation-only drift.
- Addon `0ae75fe` differs from pinned `f0e2a58` in four documentation files.
- Toolbox HEAD still matches `569da58`, but its three uncommitted owner files
  are outside the candidate. Adapter, Toolkit and Eval Lab still match exactly.

Next, let the active AI Cloud owner finish required CI, protected merge and
M4 release; then let the Toolbox owner finish its already active verification
and publication. Recheck all default roots and rerun the same full no-override
matrix after those changes. If the finished owner revisions are selected for
a candidate, explicitly refresh its manifest and exact acceptance before
publication. A green live matrix alone does not update RC2's fixed SHAs.
The earlier pinned matrix and three-layer acceptance remain historical evidence
for their recorded revisions; acceptance was not rerun during this audit.

A read-only remote lookup still resolves the published snapshot to tag object
`ffb5e5d6d7b68fa952288524a181ff688a7698ee` and peeled Core commit
`ed28f984153200a8362fcd0928bbb32b83636159`. No tag was created, pushed or
moved. This docs-only closeout stays local for the weekly docs PR.


Core documentation verification passed: `composer validate --no-check-publish`,
`composer test:all` (55 tests / 207 assertions), JSON parsing with all seven
`default` rows and the unchanged manifest hash, and `git diff --check`.
The breadcrumb remains bounded to ten entries. These checks validate this
coordination record; the failed default-root matrix remains unresolved.


## PR #1119 handoff verified; owner publication still pending

This follow-up remains release coordination in Core: this record, the new
handoff audit JSON, the two stage pointers and the bounded breadcrumb/archive.
No public contract, candidate SHA, sibling file, M4 runtime or tag is changed
by this audit. The full no-override matrix is conditional on owner closeout;
documentation gates are Core tests, JSON integrity and diff checks. Rollback
is a documentation revert. These records remain local for the weekly docs PR.

### Completed AI Cloud task and M4 handoff

[PR #1119](https://github.com/npcink/npcink-ai-cloud/pull/1119) merged at
`2026-10-16T13:00:36Z` as
`2fd7282fe28bd4e73697587ebe961afd2cee2c3d`. The initial frontend run failed
on ten obsolete English-error assertions; ten focused fixes and four matching
regressions passed before the final head `3e3c17ab` completed required CI.
The final run reported 294 unit tests and browser groups 48/48, 91/91 and 4/4.
This was a real CI failure and fix, not merely a long pending check.

The owner's completion receipt was read from
`/Users/muze/gitee/npcink-ai-cloud/.runtime/localize-errors-closeout.md` and
retained in the separate audit JSON. It records:

- `M4_RELEASED=true` at `2026-10-16T13:04:13.175448+00:00`;
- accepted source and frontend revision `2fd7282f`, clean `master` source;
- post-merge M4 smoke 3/3 passed in 9.97s, promotion completed in 88.74s;
- no production deployment; existing operator tunnel retained.

Toolbox explicitly acknowledged the release and rebased its independent Cloud
performance worktree onto `2fd7282f`. M4 was then used for the Toolbox candidate;
the #1119 accepted receipt is the handoff baseline, not proof that subsequent
uncommitted metadata changes have been formally accepted.

### Toolbox development verification versus formal closeout

The Toolbox owner completed three rounds of two real recommendation intents:
each returned eight candidates in stable order; internal links took about
3.7–3.8 seconds. Candidate display, copy and feedback passed on a real article.
A positive disposable draft separately passed two-link Apply, Undo and native
save. Metadata was then verified through WordPress, Addon and Cloud, including
version, candidate positions, score buckets and retrieval/rerank state. Local
Toolbox/Addon full tests and static analysis, plus focused Cloud tests, passed.

There is a recorded limitation: Toolkit does not consume Cloud's
`anchor_or_context`; two real samples had no safe applicable anchor and Apply
stayed disabled. The positive draft test does not erase that limitation.
See the owner's baseline at
`/Users/muze/gitee/npcink-workflow-toolbox/docs/recommendation-ranking-baseline-v1.md`.

The owner ended the task with **21 Toolbox, five Addon and five Cloud files
uncommitted**. Cloud work is in the dedicated
`/Users/muze/gitee/.worktrees/cloud-site-anchor-performance` worktree.
Formal PRs, exact-SHA CI and new M4 accepted closeout were not performed.
Development verification is complete; shared-root/release closeout is not.

A subsequent, separate AI Cloud connection-routing task also ended with seven
uncommitted documentation files on `codex/m4-lan-first-routing`. It performed
read-only LAN SSH checks and no M4 sync, promotion or deployment. Two snapshots
339 seconds apart showed the same status and mtimes. Those owner files were
preserved, not staged, committed, moved or stashed by this audit.

### Default-root readiness and unchanged RC2 decision

The latest **status-only** central matrix has seven default rows. The shared
roots now contain 33 owner files: AI Cloud seven, Addon five and Toolbox 21.
It is not a new full matrix or a passing gate result. Its complete report,
SHA observations, file identities and handoff receipt are retained in
[the handoff audit JSON](stack-rc2-owner-handoff-2026-10-16.json).

The requested full `composer quality:matrix:run -- --fail-on-dirty` is deferred
because its owner-closeout prerequisite has not been met. The last completed
full default run remains `12:43:50Z`, exit 1 / 6/7; the older exact pinned
7/7 with overrides remains separate historical evidence. No override was used
to turn this observation into a pass, and no new acceptance run was performed.

Relative to fixed RC2, Core is local coordination head `a249fcb`; AI Cloud is
now `2fd7282f` with frontend source drift plus its seven routing docs; Addon
is `0ae75fe` with committed docs drift plus five new metadata files; Toolbox
HEAD remains `569da58` but its 21 owner files are outside RC2. Adapter, Toolkit
and Eval Lab default HEADs still match exactly. Adapter remote `ab32e50` is
an ancestor of pinned `eb73e50`; the pin adds one documentation commit, so
that remote SHA difference is not new source drift.

The machine manifest remains byte-for-byte unchanged. Next, finish owner
commits/PRs and required checks, restore clean default roots, then run the full
no-override dirty gate and recheck all exact revisions. New owner revisions
are not silently adopted into RC2; candidate selection and fresh exact
acceptance remain separate decisions. Cross-thread closeout requests require
the user's explicit authorization, which was requested; none was sent in this
follow-up. No tag was created, pushed or moved; the published object and
peeled commit were remotely reverified unchanged.

Follow-up documentation gates passed: Composer metadata validation,
`composer test:all` (55 tests / 207 assertions), JSON checks for seven default
status-only rows / 33 owner files and the unchanged manifest hash,
`git diff --check`, and the ten-entry breadcrumb bound. These documentation
gates do not substitute for the deferred full matrix.


## 2026-10-17 default-root closeout supersedes the pending checkpoint

The explicitly authorized one-time requests to both owner chats completed.
Formal PRs merged and all seven default roots passed a complete no-override
`--fail-on-dirty` matrix at `01:39:54Z`, exit 0. This supersedes the earlier
pending owner/default-root checkpoints, not the fixed RC2 manifest or its
historical exact acceptance. Current source drift, retained report and owner
M4 receipts are in [the new closeout](stack-rc2-owner-closeout-2026-10-17.md).
No new tag or retag was performed.
