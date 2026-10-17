# RC2 owner closeout and default matrix — 2026-10-17

Status: owner requests fulfilled; full default-root matrix passed 7/7, exit 0.
Live revisions drift from fixed RC2. The RC2 manifest and published snapshot
remain unchanged; this is not a new candidate selection or tag publication.

## Change envelope

The focused module is release coordination in Core. Changes are this record,
its audit JSON, the original matrix report, current-stage pointers, the earlier
RC2 record and bounded breadcrumb/archive. Public REST, data shapes, product
boundaries and runtime code are unchanged by this coordinating task. Sibling
owner files, active branches and M4 runtime are not edited by this task.
Required gates are the central full default matrix with `--fail-on-dirty`,
SHA/manifest/tag comparison, Core documentation gates and JSON integrity.
Rollback is a documentation revert. These local records accumulate for the
weekly docs PR rather than a separate publication per session.

## Authorized requests and completed owner work

The user authorized exactly one formal closeout message to each existing owner
chat. Both were sent once, with the no-tag/no-production boundary. The AI Cloud
routing request reused the already active human request instead of creating a
duplicate PR. No further cross-chat requests were sent.

| Repository / scope | PR | Merged SHA |
| --- | --- | --- |
| AI Cloud routing documentation | [#1120](https://github.com/npcink/npcink-ai-cloud/pull/1120) | `d06ff4820027f3dcdb255c10032fa71cb463a662` |
| AI Cloud anchor performance / metadata | [#1121](https://github.com/npcink/npcink-ai-cloud/pull/1121) | `a40625b59e60a957021190febbb22c95203ff242` |
| AI Cloud separate OCR startup repair | [#1122](https://github.com/npcink/npcink-ai-cloud/pull/1122) | `ea62b3f2eea225eafd72cbd55296e69e998af863` |
| Cloud Addon metadata transport | [#283](https://github.com/npcink/npcink-cloud-addon/pull/283) | `b80b966151f26f114271a5534082c336ab2fea50` |
| Toolbox baseline / metadata | [#271](https://github.com/npcink/npcink-workflow-toolbox/pull/271) | `135090bdc2c8a70389f81065fe0bd44f9a6959c8` |

The initial 33 default-root owner files are now committed. AI Cloud, Addon and
Toolbox shared roots are clean, synchronized `master`. The retained independent
Cloud acceptance worktree is clean detached at `ea62b3f2`; its task lock was
released. Other preserved owner worktrees are inventoried in the audit JSON;
this record does not declare them disposable.

Addon first failed Release static gates because review-driven error-copy
changes had stale translation catalogs. Its owner refreshed POT/PO/MO and
package evidence; all final PR checks passed before protected merge. Cloud
#1122 advanced master after the first recommendation acceptance; only two
workflows, one policy document and a startup contract test changed. Its
merged-master CI was awaited, rather than using the earlier PR head as proof.

## One complete no-override seven-repository run

```bash
cd /Users/muze/gitee/npcink-workflow-toolbox
composer quality:matrix:run -- --fail-on-dirty --json --output=/tmp/npcink-rc2-default-final-matrix-2026-10-17.json
```

The report generated at `2026-10-17T01:39:54+00:00` (09:39:54 Asia/Shanghai)
returned **exit 0, 7/7 passed, zero failures**. All seven rows used
`path_source=default` under `/Users/muze/gitee`, with dirty count zero; no
repository filters or worktree overrides were supplied. This is a new complete
run, not the owner's narrower three-repository override run joined to older
results. The ordinary default matrix did not request `--cloud-m4`.

Two pre-run snapshots 211 seconds apart and the post-run snapshot have identical
SHAs, branches, clean status and tracked-file mtime digests in all seven roots.
The raw report is retained byte-for-byte as
[stack-rc2-default-matrix-2026-10-17.json](stack-rc2-default-matrix-2026-10-17.json),
SHA-256 `1bc2ac7b9c8301d5728e0806ee3cec46802aa1d9232a5b3baa3b4a35add65765`.
The audit, exact revisions, PR metadata and copied owner receipts are in
[stack-rc2-owner-closeout-2026-10-17.json](stack-rc2-owner-closeout-2026-10-17.json).

## Exact comparison with fixed RC2

| Repository | Fixed RC2 | Tested default root | Classification |
| --- | --- | --- | --- |
| Core | `7904f5bec66b859bcc68f0f9b06a53cfeb0af5c0` | `0a63aab18e99c1febf1f81f852854fb79548109b` | Coordination docs only; not adopted. |
| AI Cloud | `fe9207965f4a5049f80736019114f360186ec96b` | `ea62b3f2eea225eafd72cbd55296e69e998af863` | Runtime, metadata, docs and CI source drift. |
| Cloud Addon | `f0e2a5854bbe373e45240b23b5fce30b00df57fa` | `b80b966151f26f114271a5534082c336ab2fea50` | Docs, metadata transport, translations/package drift. |
| Adapter | `eb73e503a8c102acd6f87b69f406ee4b94aac85f` | `eb73e503a8c102acd6f87b69f406ee4b94aac85f` | Exact match. |
| Toolkit | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | Exact match. |
| Toolbox | `569da58106c877fadb65316e4375a8bd3d1f6b1e` | `135090bdc2c8a70389f81065fe0bd44f9a6959c8` | Runtime/assets, baseline and metadata drift. |
| Eval Lab | `5362655d9f27f9db9a3bcc8a7717e75ac1d9659f` | `5362655d9f27f9db9a3bcc8a7717e75ac1d9659f` | Exact match. |

Four HEADs differ; three match. Core's tested `0a63aab` contains the three
preceding local documentation commits; this new documentation commit is also
excluded from both the tested SHAs above and the fixed candidate. Adapter's
remote master `ab32e50` is an ancestor of pinned/default `eb73e50`; the latter
adds one documentation commit, so that difference is not new runtime drift.

The fixed [RC2 manifest](stack-rc2-manifest-2026-10-16.json) is byte-for-byte
unchanged, SHA-256
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Its historical matrix fields continue to refer to the earlier pinned validation with overrides. Today's default-root
pass does not rebind those fixed revisions or repeat their exact release
acceptance. `composer acceptance:cross-repo-release` was not run in this task.

## M4 receipt and verification limits

The owner first accepted recommendation merge `a40625b5`, then aligned the
accepted record to final clean Cloud master `ea62b3f2` after its exact CI passed.
Two LAN source promotions took 20.04s and 20.25s; there were zero image deploys.
The last smoke attempt initially found no listener on local tunnel port 18010,
while M4 LAN health remained 200. The owner restored only the missing standard
LAN tunnel, then repeated metadata feedback and three uncached rounds. Both
recommendation intents returned eight candidates in stable order; the final
internal-link rounds were about 3.83–3.87 seconds. Metadata receipts and the
positive draft's two-link Apply, Undo and native save passed; fixtures were
cleaned. The coordinating task performed no M4 operations.

These are development/operator acceptance records, not ranking-quality or
real-user effectiveness claims. Toolkit still does not consume Cloud anchor
hints; two natural samples could not Apply and the positive-draft evidence
remains separate. No ranking/model/weight change was introduced by this scope.

Advisory AI review was not fully delivered: Cloud's old review workflow failed
before the model step; Addon local review was partial, with three findings
triaged and fixes/tests applied; Toolbox's workflow was manually disabled and
its publisher recorded the supported exception. Required deterministic checks
were not bypassed. The later OCR startup repair does not retroactively turn
those historical reviews into completed approvals.

## Next decision and immutable tags

The owner-cleanliness blocker is closed. Before publishing a future candidate,
explicitly choose whether to include the new owner revisions, then refresh
that candidate manifest and exact release acceptance. Do not silently update
RC2 from a green live matrix. Historical pinned acceptance remains evidence
only for its recorded revisions.

A fresh remote lookup still gives the published snapshot tag
`stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0` object
`ffb5e5d6d7b68fa952288524a181ff688a7698ee`, peeled to
`ed28f984153200a8362fcd0928bbb32b83636159`. Historical Core `v0.3.0` still peels
to `3357f916899ac4b013a753da6d99e712274f2221`; Toolkit `0.7.0` remains
`46c1405422ae0ac1f8ea6820b391b48d09326a14`; Adapter `v0.4.3` remains absent.
No tag was created, pushed or moved, and no production deployment occurred.


## Documentation closeout checks

`composer validate --no-check-publish` and `composer test:all` passed, including
55 unit tests / 207 assertions, static/fault-injection contracts and reproducible
Core packaging. JSON checks confirmed the original report hash, unchanged RC2
manifest hash, all seven clean/default passing rows, stable revision binding,
four drifted HEADs, five merged PRs and exactly two coordinating messages.
`git diff --check` passed. The breadcrumb holds ten entries; the oldest moved
to the October archive. Only this coordinating task's documentation is staged.
