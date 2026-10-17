# Branch, worktree and stash ownership audit — 2026-10-17

Status: inventory and cleanup plan complete; **no owner resource was deleted**.
Global housekeeping remains incomplete. Scope is the seven matrix repositories
and their registered worktrees, not every project on this machine.

**Subsequent authorized execution:** the human later authorized the exact Core
subset. Nine local refs and the one snapshot-backed remote ref were rechecked
and removed; all other owner work/source refs were preserved. The audit below
remains its original pre-cleanup snapshot, not current branch counts. See the
[scoped execution receipt](core-scoped-branch-cleanup-2026-10-17.md).

## Current inventory

The first collection and final recheck cover `2026-10-17T04:56:33.725062+00:00`
to `2026-10-17T05:01:12.953403+00:00`. Actual origin heads were queried;
shared remote-tracking refs were not refreshed or pruned. Local heads, actual
remote heads, all local/remote tag refs, stash refs and every registered worktree
HEAD/status were unchanged between the collection and final recheck.

| Repository | Local branches | Actual origin branches | Worktrees | Stashes | Open PRs |
| --- | ---: | ---: | ---: | ---: | ---: |
| npcink-governance-core | 18 | 16 | 1 | 0 | 2 |
| npcink-ai-cloud | 5 | 7 | 2 | 0 | 5 |
| npcink-cloud-addon | 2 | 17 | 1 | 0 | 0 |
| npcink-ai-client-adapter | 2 | 2 | 1 | 0 | 0 |
| npcink-abilities-toolkit | 3 | 1 | 1 | 0 | 0 |
| npcink-workflow-toolbox | 5 | 17 | 1 | 1 | 0 |
| npcink-eval-lab | 21 | 31 | 1 | 0 | 0 |
| **Total** | **56** | **91** | **8** | **1** | **7** |

All eight registered worktrees exist, are clean including non-ignored untracked
files, and are unlocked/non-prunable. Seven are primary checkouts. The only
auxiliary registration is Cloud's detached
`/Users/muze/gitee/.worktrees/cloud-site-anchor-performance` at `ea62b3f2`.
It has 296 ignored entries: 279 Python bytecode, 15 tool-cache files and two
frontend dependency/build paths. Clean tracked status is not removal permission.

The old inventory had 55/89 branches, nine worktrees and two stashes. Addon's
former reconciliation auxiliary registration and Cloud's former stash were
already absent at this audit's first observation. This task did not remove them.
Addon default master remains `b80b9661`; actual origin/master is `3e650d1b`
(two commits ahead). No checkout synchronization or new candidate adoption occurred.

## Ownership and active work

Core release-coordination topics are owned by this audit/coordination chat;
legacy Core feature and unrelated documentation topics need their original
owner's disposition. Cloud's “服务器资源占用监测” and “归纳用户中心优化进展”
chats are active. Toolbox's “归纳会话并制定下一阶段建议” is active again and
is also using Eval Lab generated evaluation artifacts. Preserve all of those
resources. Exact current owners of Addon/Toolkit legacy branches are unresolved.
PR authors and branch names are evidence of origin, not current ownership proof.
No new owner message was sent.

## Safe cleanup candidates, without executing cleanup

The following **nine local Core refs** have proven merged changes, no worktree
occupancy/open PR, and the exact same SHA on their named origin branch. They
are candidates for a future **local-ref-only** cleanup, retaining that remote
source ref. Before executing, recheck the full SHA, worktree/owner state and
remote preservation. Short hashes below are display labels; JSON holds full SHAs.

| Local Core branch | SHA | Merged PR / tree and patch proof |
| --- | --- | --- |
| `codex/docs-publication-receipt-2026-10-17` | `b07972c1` | #178 |
| `codex/docs-rc-acceptance-2026-10-16` | `4006c644` | #169 |
| `codex/docs-rc-acceptance-owner-handoff-2026-10-16` | `ed28f984` | #172 |
| `codex/docs-rc-audit-refresh-2026-10-16` | `0d7153a9` | #176 |
| `codex/docs-rc-final-closeout-2026-10-16` | `19953429` | #170 |
| `codex/docs-rc-matrix-drift-2026-10-16` | `ec01042c` | #171 |
| `codex/docs-rc-release-notes-2026-10-16` | `fe70ede9` | #173 |
| `codex/docs-rc-tag-published-2026-10-16` | `94af361f` | #174 |
| `codex/rc2-validation-2026-10-16` | `f5181a3e` | #177 |

Do not interpret this as permission to delete the matching remote refs too.
In particular, `codex/docs-publication-receipt-2026-10-17` preserves original
matrix-tested `03be509f`, and `codex/rc2-validation-2026-10-16` preserves RC2
history. Keep their exact remote source anchors.

There is **one separately supported remote Core candidate**:
`codex/docs-rc-acceptance-owner-handoff-2026-10-16` at full
`ed28f984153200a8362fcd0928bbb32b83636159`. PR #172 is merged, tree/patch match,
and the existing published snapshot tag independently retains this exact commit.
The tag must remain unchanged. This does not generalize to other remote topics.

Another **17 local refs** (Toolbox three, Eval Lab thirteen, Addon one) have the
same mechanical merge/remote-backup conditions, but remain **owner-conditional**.
Their full list is in JSON. They are not part of the current Core-owned safe
list, and their remote refs must stay. Other merged remote topics remain held
until owner and exact-source retention are settled.

## Worktree and stash disposition

- **Auxiliary Cloud worktree**: clean detached source already included in
  default history, but active Cloud owners and ignored dependency/cache paths
  require explicit owner disposition and resource checks before removal.
  Preserve it now. Never use a force removal merely because tracked status is clean.
- **Toolbox stash** `4830f19e350f9a7e8447cbe8d0a5b3eedd222f21`: index has no
  staged changes; the saved worktree changes one smoke fixture; its untracked
  parent is empty. Current merged source differs only by whitespace and comments.
  PHP `token_get_all` comparison, excluding whitespace/comments without executing
  the source, yields the same hash
  `4e22ef21b7fcb232f97d94b414970c61e37cd220097e8efbe60e3699cb7a8efc`.
  The Lane 3 fix is therefore functionally incorporated. Dropping the saved
  historical snapshot still needs owner disposition; this audit did not drop it.
- **Historical Cloud stash** `e4d7601cb37b800ba393525b5f38553c757f7dc7`: no
  current stash ref; its original object remains readable. Its three untracked
  resource-baseline paths now exist in source but have different blobs. Do not
  infer exact archival equivalence or deletion provenance. Record the original
  SHA for its owner; no restoration, new ref or cleanup was performed here.

## Preserve unresolved branch tips

These non-base, non-open-PR branch tips lack full merge proof, including
post-PR receipts and rebased/diverged history. This is a **preserve list**, not a
claim that all their implementation is absent from master. A previously merged
PR proves only its own reviewed head; later commits must be reconciled separately.

| Repository | Branch | SHA | Locations |
| --- | --- | --- | --- |
| npcink-governance-core | `codex/docs-classification-lane1-2026-10-16` | `31279cf6` | local |
| npcink-governance-core | `codex/docs-rc-identity-2026-10-16` | `8d6083e2` | local, remote |
| npcink-governance-core | `codex/docs-rc-post-tag-drift-2026-10-16` | `e93c3303` | local |
| npcink-governance-core | `codex/docs-week-2026-10-13-followup` | `418a081d` | local, remote |
| npcink-governance-core | `codex/execution-verification-reads` | `e73f9684` | local |
| npcink-governance-core | `zcode/docs-2026-10-11-ux-audit-arc` | `3c45612c` | local |
| npcink-cloud-addon | `codex/ocr-ci-diagnostics-20261013` | `a8431153` | remote |
| npcink-abilities-toolkit | `codex/zh-cn-docs-mirror-default` | `aee42eae` | local |
| npcink-eval-lab | `codex/wordpress-ai-provider-ci` | `90bcdefe` | local |
| npcink-eval-lab | `codex/wp-ai-quality-evidence-sync` | `156ed2fc` | local |

Notable cases:

- Core `docs-classification-lane1`, `docs-rc-identity`, `docs-rc-post-tag-drift`,
  `docs-week-2026-10-13-followup` and `execution-verification-reads` contain
  commits after their merged PR heads. Preserve/reconcile the later documents;
  do not delete by matching the old PR's name. The current `ac69fa72` receipt is
  deliberately local for weekly publication. `zcode/docs-2026-10-11-ux-audit-arc`
  has no matching PR and remains owner-held.
- Core `verification-reads-hardening` is an ancestor of the reviewed #131 head;
  final head/squash equivalence proves implementation inclusion. It is not an
  unmerged code defect. Keep the local source ref pending owner/source archival.
- Toolkit `zh-cn-docs-mirror-default@aee42eae` is an unpushed **PHP feature and
  tests**, not a docs-only receipt. Owner decision is required.
- Addon `ocr-ci-diagnostics-20261013@a8431153` has four workflow diagnostic
  commits and no matching PR; retain its remote branch.
- Adapter `m4-pgy-stack-rc-docs-2026-10-15@eb73e503` remains checked out,
  remotely saved and fixed by RC2 / the published snapshot manifest. It was not
  merged to Adapter master; this exact preservation branch is not cleanup debris.
- Eval Lab's four rebased quota/stage/title topics have matching squash patch
  IDs, so their implementation is merged. Their original local SHAs have no
  current origin-head reachability and remain retained. Two further legacy
  tips (`wordpress-ai-provider-ci`, `wp-ai-quality-evidence-sync`) need owner
  reconciliation; a merged same-name PR alone was insufficient proof.

## Open PRs remain owner work

| Repository | Open PR | Branch |
| --- | --- | --- |
| npcink-governance-core | [#156](https://github.com/npcink/npcink-governance-core/pull/156) | `dependabot/composer/phpstan/phpstan-2.3.1` |
| npcink-governance-core | [#155](https://github.com/npcink/npcink-governance-core/pull/155) | `dependabot/github_actions/alibaba/open-code-review-1.12.13` |
| npcink-ai-cloud | [#1105](https://github.com/npcink/npcink-ai-cloud/pull/1105) | `dependabot/uv/ruff-0.16.10` |
| npcink-ai-cloud | [#1104](https://github.com/npcink/npcink-ai-cloud/pull/1104) | `dependabot/uv/fastapi-0.143.0` |
| npcink-ai-cloud | [#1103](https://github.com/npcink/npcink-ai-cloud/pull/1103) | `dependabot/npm_and_yarn/tailwind-merge-3.7.0` |
| npcink-ai-cloud | [#1102](https://github.com/npcink/npcink-ai-cloud/pull/1102) | `dependabot/npm_and_yarn/autoprefixer-10.6.1` |
| npcink-ai-cloud | [#1101](https://github.com/npcink/npcink-ai-cloud/pull/1101) | `dependabot/github_actions/alibaba/open-code-review-1.12.13` |

All seven are separate dependency updates. No PR was closed, no branch pushed,
and no merge or dependency change was attempted by this audit.

## Method and evidence limits

Use [the machine-readable inventory](branch-worktree-stash-audit-2026-10-17.json)
for every local/actual-remote tip, full SHA, PR proof, owner category, worktree,
stash/index/untracked coverage and final recheck. Default reachability or exact
merged PR head plus tree/stable-patch equivalence establishes inclusion; branch
name and `--no-merged` output alone do not.

Eval Lab is a shallow source checkout. Missing parents initially made ordinary
ancestry analysis incomplete. Its shallow boundary/history were completed only
inside a temporary bare audit database; the source checkout remained shallow
and unchanged. All sibling object/ref fetching was confined to temporary audit
refs, without tag refs or source-root writes. Ignored files were examined only
as paths/counts; no credential or article contents were read or recorded.

RC2 manifest SHA-256 stays
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
All seven repositories' local and remote tag inventories stayed identical.
No tag creation/push/move/delete, branch/worktree/stash cleanup, new candidate,
full matrix, acceptance, production or M4 operation occurred.

## 工作审视报告

### 原定目标与完成情况

- [x] 审计七仓所有本地／实际远端分支、注册 worktree 和当前 stash。
- [x] 区分已合并实现、精确源码保留、后续未归档提交及 owner 活动。
- [x] 形成分范围清理候选和保留清单；更新文档，不执行删除。
- [ ] 全环境清理仍未完成：本轮没有删除授权，owner 工作与保留决策尚未收口。

### 发现的问题

| 严重程度 | 具体问题 | 方法层面的原因 | 改进建议 |
| --- | --- | --- | --- |
| 必须保留 | Toolkit `aee42eae` 包含 PHP／测试且无远端同版保存 | 分支名称不能证明内容或归属 | 先由 owner 正式保存／PR 或作出处置决定 |
| 应当核对 | Core 五个旧 topic 含合并后的文档提交 | 只看同名 PR 会漏掉后续 HEAD | 比较精确 tip、PR head 及全部后续提交 |
| 应当纠正 | 旧记录的九个 worktree／两个 stash 现为八／一 | 并行 owner 会改变历史盘点 | 明确快照时间和差异，不归因为本会话清理 |
| 建议改进 | Eval Lab 浅克隆缺少父对象 | 本地祖先关系未必完整 | 隔离补齐历史后再做 patch／祖先判定 |

### 做得好的地方与下次重点

本轮没有把 squash 的非祖先关系误判为实现未合并，也没有将干净工作树
等同于可删除。清理时优先采用 Core 明确归属的小范围候选，重新核对精确
SHA 和 owner 活动；本地／远端动作分开，始终保留候选与测试证据所需源码。
owner 不明、原始提交未归档或存在忽略资源的项目继续保留。

## Documentation verification and closeout

`composer test:all` passed (55 PHPUnit tests / 207 assertions and all component
gates); Composer metadata, JSON count/candidate guards, relative links, ten-entry
breadcrumb, diff checks and unchanged manifest hash passed. The former Addon
auxiliary path is absent as well as unregistered. Eval Lab's source checkout
remains shallow. Seven disposable audit-only bare databases were removed after
evidence capture; none had tag refs. No owner branch, worktree or stash was
removed. The inventory binds Core `ac69fa72` before documentation editing; this
later audit commit is outside the captured source snapshot.

This local audit joins the existing post-#178 receipt for the next weekly docs
PR. It does not reopen the already merged three-commit batch or establish a new
release/candidate acceptance.
