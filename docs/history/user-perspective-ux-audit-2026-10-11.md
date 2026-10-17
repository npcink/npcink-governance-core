# User-perspective UX audit — 2026-10-11

Status: historical synthesis reconstructed on 2026-10-17 from current master
`289ce777eff158e8f2d5088c30d90866efc87e2c`. The implementation was already
merged; this document preserves its rationale and engineering lessons.

## Provenance and evidence limits

The original owner record is local branch
`zcode/docs-2026-10-11-ux-audit-arc`, commit
`3c45612c5ee15705374a19743b983dfce9a0258e`. That branch remains intact.
The owner reported a browser walkthrough on the local WordPress admin that
found four substantial and seven polish issues beyond the earlier #91 work.
This is a dated report, not a new browser acceptance run. On 2026-10-17,
GitHub merge records and ancestry in current origin/master were rechecked for
all four implementation PRs below. Historical owner reports of passing OCR
and runtime checks do not establish a fresh review of today's checkout.

## What the review improved

The review followed an administrator's decisions: understand a submitted
action, find its audit trail, explain why a pending item disappeared, and
interpret policy and token state. Missing explanations made governance state
hard to assess even when the underlying lifecycle already worked.

| Merged PR | Result | Merge commit |
| --- | --- | --- |
| [#151](https://github.com/npcink/npcink-governance-core/pull/151) | Actions detail shows submitted input; third-party abilities use registry labels with request-local memoization. | `c7d5847ee682c65059b3f62ebf2f291a66941408` |
| [#152](https://github.com/npcink/npcink-governance-core/pull/152) | Labels and filter choices cover emitted audit events; a static completeness contract detects event-label drift. | `e1efe262196ecefe027720b135b36c32c3976f5c` |
| [#153](https://github.com/npcink/npcink-governance-core/pull/153) | Expired unreviewed items gain a summary card and empty-state guidance linking to the expiry audit filter; History remains read-only. | `db704db14be5e944240cf54489ef3096d55e540b` |
| [#154](https://github.com/npcink/npcink-governance-core/pull/154) | Approval Policy and Smart guarded approval wording, preset deduplication copy, label fallback, revoked-token retention guidance, technical provider-posture disclosure, and 401 copy naming both authentication paths. | `16a7ec895a1e2e51d09f1551d7fd335d02e2ea1c` |

One initial finding was wrong: **token revocation already had an inline
confirmation**. Verify the existing interaction before prescribing a new
confirmation flow. The work did not add a previously missing revoke safeguard.

The enduring UX approach is to show the information needed for the present
review decision, explain lifecycle transitions, and make audit navigation
available without adding execution controls. Current surface rules remain in
[Admin Surface Standard](../admin-surface-standard.md).

## Development lessons and repeatable checks

1. **Bind review evidence to a stable checkout.** A branch switch during OCR
   invalidates the review target. Use dedicated worktrees for parallel edits,
   record the start/end SHA and tracked changes, and rerun when the reviewed
   content changes. A failed or partial review is not a clean review.
2. **Check generated catalog identity.** `wp i18n make-pot` can derive
   `Report-Msgid-Bugs-To` from the directory name. In an isolated worktree,
   verify the canonical `npcink-governance-core` slug after generation rather
   than trusting a temporary directory name. The present POT already has the
   canonical slug; no catalog was regenerated in this documentation follow-up.
3. **Follow the actual plural header.** Core's current zh_CN PO declares
   `nplurals=2; plural=0;` and contains `msgstr[0]` and `msgstr[1]`. Preserve
   completeness against that header and validate catalogs. This is a fact
   about this repository, not a general recommendation for Chinese locales.
4. **Check integration source movement before assigning blame.** If a smoke
   failure appears while sibling Toolkit work is moving, inspect the exact
   checkout, status and tracked-file mtimes. Respect owner boundaries; record
   the tested source set and rerun only after it stabilizes.
5. **Preserve conclusions outside rolling breadcrumbs.** Keep dated source
   SHAs, corrected findings, merged implementation links and evidence limits
   in a durable history document. Link operational guidance to that record;
   do not replay an old breadcrumb with expired release or owner status.

Apply these alongside [Development Workflow](../development-workflow.md).
They preserve Core's governance boundary and do not grant new runtime authority.

## Reconstruction scope

This follow-up salvages the synthesis only. It does not replay old source,
restore the old pre-tag pause or ADR conflict, assert a current ledger count,
or reuse an obsolete package checksum. The source branch and other owners'
unfinished work are retained. Snapshot tags and the fixed RC2 manifest remain
unchanged; no new candidate or cross-repository acceptance is claimed.
