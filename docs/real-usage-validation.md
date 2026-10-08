# Real Usage Validation

Status: active observation program (opened 2026-10-08).

Core's engineering and process debt was paid down through 2026-10. The one
unvalidated assumption left is the product one: that the governed chain
(Adapter -> Core proposal -> approval -> commit preflight -> Adapter
execution through Toolkit abilities -> audit readback) gets used for real
work on a real site. This page defines that validation so its outcome is
recorded evidence, not impressions.

The program runs for at least four consecutive weeks on one real operating
site (not the LocalWP smoke site). The owner uses the chain for real
AI-assisted operations and records one ledger row per week.

## Weekly Ledger

Copy this row format into a dated entry at the bottom of this page. Numbers
come from the Core admin queue and audit screens; friction entries are one
line each and name the screen or route.

| Week | Created | Approved | Rejected | Expired unseen | Preflights | Audit queries | Friction (one line each) |
| --- | --- | --- | --- | --- | --- | --- | --- |

Low usage is itself the primary product signal: a low-usage week should
record why (no need / forgot / flow too heavy / tool missing), because that
one line decides more roadmap than any audit round.

## Recorded-Evidence Exit Criteria

Each pending item fires only on a ledger entry; no item fires on opinion:

- `updated_since` polling filter (briefs Decision 2, option B): implement when
  a ledger entry records a real adapter polling loop complaining about
  `proposals_read` quota or `proposal.listed` audit noise.
- Core-sent email notifications (briefs Decision 1, option B add-on): reopen
  only when a ledger entry records a proposal that expired unseen.
- smart_guarded widening: see the observation standard in
  [Approval Policy Evaluator Standard](approval-policy-evaluator-standard.md);
  the ledger's misapproval column is its input.
- ADR-010 trigger checkpoint: the release-closeout checkpoint line cites this
  ledger for the current execution-consumer count.
- ADR-007 local automation runtime: its start trigger is a ledger entry
  recording a concrete unattended-automation requirement that reviewed
  governance cannot cover.

Closed ahead of the ledger (recorded waiver): the post-execution
verification-read contract was implemented on 2026-10-08 by owner decision
(Core PR #128, ADR-012) before any ledger entry existed. ADR-012 records
this as a one-item owner waiver of the trigger rule, not a precedent; every
remaining item above still fires only on a ledger row.

## Release Gating

The 0.4.0 scope is set by the first feedback batch from this ledger (at
least two recorded weeks), not by a calendar or a feature wish list. If the
first weeks show near-zero usage, the honest 0.4.0 is small and this
program's why-lines become the roadmap.

## Ledger Entries

(none yet — the program opens with the first real-use week)
