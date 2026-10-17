# Adapter release and Cloud Addon drift review — 2026-10-17

Status: Adapter standalone publication evidence verified; update-channel
availability remains unconfirmed. Addon's two-commit drift is explained and
preserved. This is not a new stack release or a live recovery acceptance run.
[Structured evidence](adapter-release-addon-drift-review-2026-10-17.json)
contains remote readbacks, exact identities and verification limits.

## Change envelope

Core documentation only: this report/JSON, next-stage plan and bounded
breadcrumb/archive. No public contract, runtime source, sibling checkout,
manifest or release identity change. Gates: remote artifact/SVN/API reads,
existing evidence hashes, PR/check/source comparison, JSON/links/count/diff,
Composer metadata and Core default tests. No new matrix/acceptance is required
for this documentary reconciliation. Rollback is a docs-only revert. Retain
original source branches and other owners' unfinished work; perform no tag action.

## Adapter v0.4.3: standalone publication verified

[Publication PR #121](https://github.com/npcink/npcink-ai-client-adapter/pull/121)
is merged as `0159f2c7ffe9f2ffa310b6760efded1f5a618dfe`; PHP, PR-body,
advisory static-analysis and code-review check results are successful. The
workflow result alone is not asserted to be a newly delivered model review.
Current Adapter master is clean at that SHA. #120 and #121 add documentation;
they do not replace the published source identity.

| Evidence | Independently observed result |
| --- | --- |
| Annotated tag | `v0.4.3`, object `34c0b788885746d42bc41119d4cff33a5d1d5f7e`, peeled source `d3b114f73029836277f42d04c292f661b255a332`. |
| [GitHub Release](https://github.com/npcink/npcink-ai-client-adapter/releases/tag/v0.4.3) | Published `2026-10-17T06:31:44Z`; neither draft nor prerelease. Only Adapter is in publication scope. |
| Downloaded GitHub ZIP | 176,899 bytes; SHA-256 `4356f90721d56b7484d42ad4a73b1ed0500295f21580ce75a656b5f9cba68fbc`, matching the frozen review artifact and Release asset digest. |
| WordPress.org SVN | Read-only log confirms `r3751468`, `2026-10-17T06:32:02.667055Z`, releasing the approved `d3b114f` package. The version tag lists 26 files. |
| [Official version ZIP](https://downloads.wordpress.org/plugin/npcink-ai-client-adapter.0.4.3.zip) | 176,503 bytes; SHA-256 `0aa2d93bbd6f6b5e8730f33c8754c15f3d5bd13e2e2113d41813da1b49b4dc7b`. All 26 unpacked paths and file hashes equal the GitHub ZIP. Container bytes differ. |
| Plugin-information API | Fresh read reports `0.4.3`, with the official version ZIP link. A cached directory-page rendering still showed 0.4.2; API and package readbacks are separately recorded. |
| Update API at `06:48:44Z` | Read-only probes from installed 0.4.1 receive 0.4.2; from 0.4.2 no update entry is returned. Neither probe offers 0.4.3. No claim of universal update availability. |

The owner's accepted scope includes Git tag, GitHub Release and WordPress.org,
as recorded in its merged closeout. This coordinating audit performed only
reads/downloads; it made no publication write and did not install either ZIP.
Original Adapter `eb73e503` remains remotely available on
`codex/m4-pgy-stack-rc-docs-2026-10-15` for historical stack evidence.

Remaining standalone release follow-up: the owner/operator should recheck the
update service after the waiting window in the release receipt; if it remains
old, inspect any WordPress.org committer notification. A failed-to-offer update
is not enough to diagnose a security rejection or cache fault. Do not republish
SVN or move tags to force distribution. Production installation, backup/rollback
execution and real-user observation remain separately scoped work.

## Cross-repository evidence: separate frozen compatibility candidate

Adapter's merged record contains candidate `adapter-0.4.3-rc3-2026-10-17`,
manifest SHA-256
`38abcf4d0a7ed60c220bc75083c67ffe05539e83388d9a5f452b44965360faf8`.
Its historical `publication_authorized=false` captures the pre-approval freeze;
the later publication receipt records approval without rewriting that manifest.
It is distinct from Core's fixed `stack-rc2-2026-10-16` and the published stack
snapshot. The prior Core checklist had not yet incorporated this owner evidence.

| Component | Exact compatibility revision |
| --- | --- |
| Adapter | `d3b114f7` |
| Core | `289ce777` |
| Toolkit | `b09ad300` |
| Toolbox | `a2ad8981` |
| Cloud Addon | `3e650d1b` |
| Eval Lab | `5362655d` |
| AI Cloud | `6fddd93d` |

This audit verified all seven recorded Git trees and source archive SHA-256s
against existing objects, both stored matrix report hashes, and all 19 retained
log hashes. The owner's evidence records:

- default/no-override matrix 7/7 at `06:00:34Z`, then the separate exact-candidate
  matrix 7/7 at `06:01:20Z` using seven isolated override checkouts;
- cross-repository release acceptance exit 0, zero skips, completed
  `06:09:05Z`, plus signed-client/flagged-media HTTPS and M4 package-smoke evidence;
- temporary resources cleaned, with automated administrator test approval;
  no new human acceptance or real-user observation.

The default matrix does not itself record exact source SHAs: its Addon default
root was still `b80b9661`. The candidate manifest, source/archive checks and
isolated-candidate evidence bind the compatibility claim to `3e650d1b` instead.
Do not label the candidate matrix “no override,” or claim the default site
loaded the new Addon code. Raw log hash equality establishes unchanged evidence;
it does not mean this audit reran the recorded tests.

Engineering compatibility acceptance therefore exists for **this exact frozen
owner candidate**. It does not cover current moving heads, newer Core docs or
Eval Lab #111, refresh old RC2 pins, republish Core/Toolkit, authorize Cloud
production, or prove Cloud-transport Provider-log parity. Provider-log evidence
in the receipt uses Ollama; the Cloud transport remains a separate owner item.

## Cloud Addon: two commits with different effects

The shared default root remains clean `master@b80b9661`; actual remote master
is `3e650d1bc74d9428d6671f27dd668938c6b6078b`, exactly two commits ahead.

| Commit / merged PR | Classification and effect | What remains unproven |
| --- | --- | --- |
| `ba6a621c`, [#284](https://github.com/npcink/npcink-cloud-addon/pull/284) | Runtime bridge fix plus tests and catalog-reference refresh. Missing persistent cursor starts from the oldest candidate instead of borrowing `last_delivered_at`. Bounded scan accepts only available buffer capacity, preserves pending live changes, and advances through retained IDs. | Shared site still loads the older root; merged code/CI do not prove two historically missing Cloud records recovered. |
| `3e650d1b`, [#285](https://github.com/npcink/npcink-cloud-addon/pull/285) | Five documentation files: retrospective, handoff, current-state/owner drafts and evidence standards. No runtime change. | Documents explicitly preserve historical coverage, OCR and owner-decision gaps. |

Both final PRs have passing PHP 8.0/8.2/8.4, WP compatibility, release static
and PR-body checks. Owner-recorded tests/Playground evidence are not a fresh
site/Cloud run here. Cloud continues to own index execution/lifecycle/freshness;
Addon remains the bounded transport bridge. No new runtime ownership is introduced.

The two-commit delta is covered by Adapter's isolated compatibility candidate,
**not deployed to the shared default root**. Do not fast-forward that root as
“housekeeping”: it is loaded by the local WordPress site. The owner intentionally
retained it to avoid an implicit deployment.

## Classified remaining actions and preserved boundaries

- **Closed:** Adapter public release/upload/file-equivalence evidence; identity
  and exact frozen compatibility evidence; Addon two-commit provenance and CI.
- **External follow-up:** Adapter update-channel offering is still unconfirmed.
  Owner/operator checks the service and any notification, without retagging.
- **Needs separate site-update scope:** Addon source synchronization, followed
  by revision/cursor evidence and natural-run recovery verification. A cursor
  already beyond the two missing targets will not be repaired by initialization;
  exact resend would need a separate decision. Do not reset cursor or run cron,
  flush, rebuild, full refresh or Cloud cleanup under this audit.
- **Recovery bar:** compare the stable current public-source denominator with
  exact Cloud documents/chunks and all gaps. Historical 1,999/1,999 is only the
  target if that denominator still holds. PR merge, readiness or delivery counts
  alone cannot establish complete coverage.
- **Other owners remain open:** Addon F-01 historical 64-record disposition,
  F-02 real OCR coverage, Cloud dependency/watch/transport/budget/production
  acceptance, Toolkit legacy source, Toolbox/Eval Lab human quality, retained
  worktrees/stashes and other original branches. This review closes none of them.
- **Any later stack publication:** explicitly choose identity and exact sources,
  use a separate manifest and matching gates; no snapshot creation or movement
  is authorized by Adapter's standalone release.

Core fixed RC2 manifest remains byte-identical, SHA-256
`79bfa99b7ad036df1d1f93990c45bf25f8576543e184641a2d9c6cf8402ee2a0`.
Historical stack tag object `ffb5e5d6d7b68fa952288524a181ff688a7698ee`
remains unchanged. Preserve original refs and other owner resources. This
record joins the two local Core receipts for the next weekly documentation PR;
no new PR, source synchronization, runtime test or tag operation is performed.

## Documentation closeout verification

Composer metadata and `composer test:all` passed (55 PHPUnit tests / 207
assertions and component gates). JSON/local links, ten-entry breadcrumb and
diff checks passed. Final read-only recheck found the original Core/Adapter/
Addon branches, local/remote tag maps, worktrees and stashes unchanged; sibling
HEAD/status are unchanged. Core changes only these documentation records.
The fixed RC2 manifest hash is unchanged. This is a local committed weekly
record, with no PR publication or remote mutation in this session.
