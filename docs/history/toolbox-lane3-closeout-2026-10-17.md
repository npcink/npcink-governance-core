# Toolbox Lane 3 Closeout — 2026-10-17

Status: focused fixture repair merged and verified on real local WordPress;
the wider default matrix is not green.

**Subsequent readiness update:** Toolkit owner source merged and Cloud's
backend aggregate completed. A new full no-override matrix passed 7/7 at
`2026-10-17T02:45:41Z`. See the [owner readiness closeout](stack-owner-readiness-2026-10-17.md).
The non-green matrix below is the original earlier checkpoint, not the latest
default-root status.

## Delivered change

Toolbox [PR #272](https://github.com/npcink/npcink-workflow-toolbox/pull/272)
merged at `2026-10-17T02:36:45Z` as
`3ae12c4a6a167cceddb1a22c5f9501cbeb7ab7c9`. Its final topic head was
`eb0c24f638cf40cfcce32ca109bcebe4b1c699b0`. The required PHP contracts and
PR body checks passed, as did static analysis. The publisher recorded the
standard exception for the manually disabled advisory OpenCodeReview workflow;
no delivered AI review is claimed and no required check was bypassed.

The historical stash's two corrections are now implemented:

1. All required abilities must be present in WordPress's global registry.
   Only Toolkit-owned ids must also have callbacks in Toolkit's private
   registry. Toolbox's batch planner stays owned by Toolbox.
2. Fixture cleanup uses returned proposal ids and batch ids. Core's
   `proposal.plan_ingested` rows have no proposal id, so they are removed by
   their exact batch metadata and event name, with prepared/escaped SQL.

The five merged files are the smoke script, development workflow, documentation
index, Lane 3 closeout Markdown and its JSON evidence. Product implementation,
ability ids, Core authorization contracts and final execution are unchanged.
The first CI round caught missing documentation-index entries; both records
were indexed and the full final file set passed local and CI gates before merge.

## Real WordPress verification and cleanup

The original script reproduced the private-registry assertion failure before
intake. The repaired topic passed normal and deliberately failing post-intake
runs. After merge, the default Toolbox shared root was fast-forwarded to the
exact merged SHA only after two identical status/HEAD/tracked-mtime snapshots
**144.49 seconds** apart. No other sibling root or site plugin symlink was changed.

The merged default script was then rerun on WordPress **7.1.3**:

| Path | Result | Cleanup evidence |
| --- | --- | --- |
| Normal | Exit 0; 65 assertions; HTTP 201; one pending proposal with eight dry-run actions | One proposal and its batch plan-ingest audit fixtures purged |
| Fault after successful intake | A temporary `rest_request_after_callbacks` filter sets response `proposal_count=0`; expected exit 1 at the count assertion | Failure cleanup purges the same fixture categories |

Independent database snapshots before and after **each** merged-source run
are identical:

| Data | Before / after |
| --- | --- |
| Core proposals | 326 / 326 |
| Core audit rows | 4,140 / 4,140 |
| WordPress posts, including attachments | 2,391 / 2,391 |
| Attachments | 26 / 26 |
| Local-consent audit rows | 6 / 6 |

Full-row hashes of all existing proposal and audit rows also match. Thus this
run leaves no additional proposal/audit fixture, post, attachment or consent
event and does not delete unrelated existing records. Runtime PHP fingerprints,
HEADs and statuses were stable during the two merged-source runs. Toolkit was
then at local owner head `f989b580`; the exact full SHA and source fingerprints
are in the [evidence JSON](toolbox-lane3-closeout-2026-10-17.json). Its owner
continued working afterward. This is observed local integration evidence,
not adoption of that owner revision into the fixed RC2 manifest or user ledger.

## Wider matrix remains a separate finding

The final full default, no-override command was:

```bash
composer quality:matrix:run -- --fail-on-dirty --json --output=/tmp/toolbox-lane3-default-final-matrix-2026-10-17.json
```

At `2026-10-17T02:39:36Z`, six repository gates passed and Cloud returned
`needs_validation` / 78 for missing exact-SHA `backend` evidence. Overall
exit was **1**. Toolbox passed on clean synchronized `master@3ae12c4`.
The dirty gate also observed a Toolkit owner change in
`includes/Support/Internal_Link_Source_Matcher.php`. Those source trees were
actively moving; the raw report is a timestamped diagnostic, not a frozen
candidate. See the [unmodified matrix report](toolbox-lane3-default-matrix-2026-10-17.json).
An earlier in-progress attempt also sampled this session's unfinished docs;
it is superseded for closeout by this final no-override report.

No attempt was made to fix, discard or publish the other owners' work. A future
clean 7/7 and any new candidate acceptance need fresh owner alignment; this
Lane 3 milestone does not imply those wider claims.

## Preserved work and next step

- Original Toolbox stash `4830f19e` remains as historical saved work; Cloud
  stash `e4d7601c` and other owner branches/worktrees/files remain preserved.
- The temporary worktree created for this repair was removed cleanly after
  merge and validation. The topic branch remains preserved and was synchronized
  with its origin topic (ahead 0 / behind 0). Toolbox's shared root is clean
  synchronized master.
- Adapter's unpublished exact documentation pin, Core's weekly docs publication,
  Addon drift and owner candidate decisions remain separate follow-ups. Core's
  five earlier local docs commits plus this record await the weekly docs PR.
- Fixed RC2 manifest bytes are unchanged. The published snapshot still resolves
  to object `ffb5e5d6`, peeled commit `ed28f984`. No tag was created, pushed,
  moved or removed; no new release candidate or production deployment occurred.

Local Toolbox gates passed: `composer test:all`, Composer metadata, PHPStan,
PHPCS and `composer check:wporg`. Core's documentation closeout passed its default
test gate (55 tests / 207 assertions), Composer validation, JSON/evidence checks,
manifest/report hashes, Markdown links, bounded breadcrumb and `git diff --check`.
The immediate historical Lane 3 repair is
closed; remaining work resumes from the [stage plan](../next-stage-plan.md).
