# ADR-012: Execution-Attached Verification Reads

## Status
Accepted — supersedes ADR-011

First filed as a second `ADR-011` in PR #128; renumbered to ADR-012 to
resolve the same-day number collision with the option-2 ADR-011, whose
direction this ADR reverses.

## Date
2026-10-08

## Context
Adapter issue #93 and Core issue #125 recorded a verification-integrity gap:
the OpenClaw Adapter executes governed writes after Core commit preflight,
then verifies the write with a post-execution block readback through the
sensitive-read path. When a deployment requires Core read authorization
(`core_read_authorization_required`), that readback has no read request: the
write flow authorized through proposal approval, and sensitive-read grants
exist only for operator-reviewed read requests. The write commits, but its
verification evidence silently degrades to `readback_failed`.

Two designs were considered:

1. Execution-attached verification reads: Core mints a narrowly scoped,
   single-use, approved read request at commit preflight, attached to the
   execution envelope the Adapter already consumes.
2. An explicit verification read-request type the Adapter creates through
   the standard read-request lifecycle.

Option 2 would require granting the channel layer `read_requests:create`
(and an auto-approval policy for the type), widening the sensitive-read
surface and moving authorization initiation into the Adapter. Option 1 adds
zero Adapter privileges: the mint happens inside Core's existing
authorization moment (commit preflight), and the Adapter only presents the
request id through the existing `read_requests:preflight` scope it already
holds.

## Decision
Core adopts execution-attached verification reads (option 1):

- At commit preflight, the caller may name `verification_reads`
  (`[{ability_id, input}]`) the execution will need after the write.
- Core validates each against a strict pairing map
  (`VERIFICATION_READ_ABILITIES`) that binds only the Adapter execution
  contract's block readback pairs (`update-post-blocks -> get-post-blocks`,
  template and template-part equivalents) and requires the read input to
  target the same object (post id, or template id/slug) as the approved
  write input.
- Valid requests are minted through the normal read-request lifecycle as
  approved, single-use, verification-sourced requests with the preflight
  TTL; consumption, expiry, hash binding, audit, and redaction all reuse
  the existing machinery unchanged.
- Mint failures never block the write: verification reads are an
  enhancement, and denial reasons are returned and audited so the readback
  degrades exactly as it does today, but visibly.
- The pairing map is deliberately narrow. Any broader verification-read
  contract requires a new decision record.

Ownership stays put: Core remains the read-authorization truth source, the
Adapter remains the execution surface that names what it will verify, and
no new scope is granted to any channel.

## Hardening (2026-10-08, post-merge review)

- The requested `verification_reads` list is deduplicated per read ability
  and object and capped at four granted entries per preflight, so a caller
  cannot mint an unbounded number of approved rows. Failed mints or
  approvals do not consume slots, and unencodable inputs are denied as
  `invalid_input` instead of colliding into one dedupe key.
- Granted request ids travel only inside the client-bound execution
  handoff. The top-level preflight response exposes granted ability ids
  and denial reasons, never usable request ids.
- Unconsumed grants are invalidated by the preflight TTL (300 seconds);
  there is no separate rollback path, and expired single-use rows are
  inert by construction.

## Consequences
- Governed writes keep their block-readback verification evidence under
  read-authorization-requiring deployments (acceptance: the Adapter
  grant-mode smoke `pattern page execution verifies post-block readback`
  assertion).
- Verification reads are auditable per execution via the minted request's
  correlation id and the preflight correlation id.
- Core gains no execution role: it mints authorization, never runs reads.
- The Adapter implements the request/response plumbing in
  npcink-ai-client-adapter issue #93.
- Trigger-waiver record: the real-usage validation program gates pending
  contract items on recorded ledger entries, and this implementation merged
  (PR #128, 2026-10-08) ahead of any recorded ledger entry. The release
  owner ratified keeping it the same day as an explicit owner waiver of the
  ledger trigger for this one item; it is a waiver, not a precedent, and
  every other gated item still fires only on a ledger row.
