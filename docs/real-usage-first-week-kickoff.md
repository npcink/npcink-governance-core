# Real Usage Validation — First-Week Kickoff

Status: active. Companion to the [Real Usage Validation](real-usage-validation.md)
program page: that page defines the four-week ledger and its triggers; this
page is the executable runway for the owner's first ledger row. Nothing here
adds code or governance — it only removes setup friction so week one starts
on the real site, not in planning.

## 0. Pick the real site (owner, one decision)

One real operating site, not the LocalWP smoke site (`magick-ai.local` is the
smoke environment and stays fixture-only). Whatever site carries real content
work is the right answer; the choice is recorded once in the first ledger row
context line.

## 1. One-time environment preparation

Checked once; re-check only after a plugin update.

- [ ] Npcink stack active on the real site at the 0.3.0 stack versions or
      newer: Governance Core 0.3.0, Abilities Toolkit 0.5.10, Client Adapter
      0.4.1, plus the Toolbox / Cloud Addon versions that site runs.
- [ ] WordPress `ai` provider plugin updated to 1.4.0+ (the 1.3.0 → 1.4.0
      update exists; provider-log correlation should run current code).
- [ ] One Core app token issued on the real site (`Npcink AI → Core →
      Settings → Client Access Tokens`, purpose preset "Adapter default
      access") and configured as `NPCINK_OPENCLAW_ADAPTER_CORE_APP_TOKEN` on
      the Adapter host. Do not reuse a token from another site.
- [ ] Adapter health check green from its admin screen
      (`dependency_contracts_ready` true).
- [ ] Smoke-site rehearsal already proven: the Core 0.3.0 release chain
      verified proposal → approval → execution → duplicate-reject → readback
      → cleanup with a signed fixture; the same chain on the real site needs
      no further proof before week one — using it for real work IS the test.

## 2. Week-one operating loop

- Use the governed chain for real work whenever it genuinely fits. Do not
  manufacture proposals to feed the ledger; a quiet week is a valid, valuable
  row when its why-line says so.
- For each real operation, stay aware of (do not deliberately perform) the
  friction points: queue pagination and filters, decision notes, open-next,
  the one-time token screen, verification readback surfaces.
- When something annoys you, write one line naming the screen or route
  immediately (notes app is fine); lines get transcribed into the ledger at
  week end.

## 3. Recording the first ledger row

At the end of week one, append one row to the
[weekly ledger](real-usage-validation.md#weekly-ledger):

| Column | Where the number comes from |
| --- | --- |
| Created | Review Queue → summary strip "Needs review" history; or Activity Log filtered `proposal.created`. |
| Approved / Rejected | Activity Log filtered `proposal.approved` / `proposal.rejected`. |
| Expired unseen | Activity Log filtered `proposal.expired` (a non-zero here also arms the Decision 1 email-notification trigger). |
| Preflights | Activity Log filtered `commit.preflighted`. |
| Audit queries | rough count of your own Activity Log sessions that were lookups, not decisions. |
| Friction | your one-line notes, one per line, each naming a screen or route. |

If the week had zero usage, the row still lands — with the why-line
(no need / forgot / flow too heavy / tool missing). That single line steers
the 0.4.0 scope more than any review round.

## 4. What happens after week one (already wired, no action needed)

- After **two** recorded weeks, the 0.4.0 scope review reads the ledger
  (real-usage-validation.md, Release Gating).
- Individual triggers (email notifications, `updated_since` polling,
  smart_guarded widening, ADR-007 runtime start, ADR-010 checkpoint) fire
  only on ledger entries — see that page's exit-criteria list.

## Non-goals

No new Core features, no workflow runtime, no unattended automation, and no
changes to the ledger format during the four weeks; format amendments wait
for the program's own review.
