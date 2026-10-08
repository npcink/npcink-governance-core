# ADR-011: Post-Execution Verification Reads Use An Explicit Verification Read-Request Kind

## Status

Accepted (direction and boundary decided; implementation gated on a named
trigger)

## Date

2026-10-08

## Context

Npcink AI Client Adapter verifies governed writes with a post-execution
block readback (`block_write_readback_verification`): after an approved and
executed write, it reads the written object's blocks server-side through the
sensitive read abilities (`get-post-blocks`, `get-template-blocks`,
`get-template-part-blocks`) and records the evidence against the execution.

When a deployment requires Core read grants for those abilities (grant mode),
the execution-side readback has no `read_request_id`: the write flow was
authorized through proposal approval and commit preflight, and no read grant
exists for the verification read. The readback therefore degrades to
`readback_failed`, so a successful governed write silently loses its
block-readback evidence exactly in the deployments with the strictest read
policy. This was reproduced on 2026-10-08 (npcink-ai-client-adapter issue
#93, tracked in Core issue #125) against a grant-mode smoke simulation; no
production deployment has reported it yet.

Read authorization truth belongs to Core. The adapter cannot mint read
grants unilaterally, so the contract direction is Core's to decide.

## Decision

Core will close the gap with an explicit verification read-request kind
(option 2 of issue #125), not an execution-attached read credential
(option 1):

- Core exposes an automated, narrowly-scoped `verification` read-request
  kind for post-execution verification reads only.
- The adapter creates and preflights it with its existing app credentials
  and existing read scopes; no new trusted-adapter privilege is added.
- Core policy bounds the kind: an allowlist of verification abilities, an
  object scope bound to the just-executed proposal's target object,
  single-use or short-lived expiry, and audit rows that carry the originating
  `proposal_id` and commit-preflight `correlation_id` as verification reads.
- The kind creates no proposal, no admin approval loop beyond the existing
  read-request policy, and no execution path; sensitive read-result
  redaction rules still apply.

The existing single read-authorization surface stays the only truth for all
sensitive reads, including verification reads.

## Implementation Trigger

Implementation is deferred and fires only on recorded evidence, matching the
[Real Usage Validation](../real-usage-validation.md) gating:

- the 0.4.0 scope batch (the first feedback batch from the validation
  ledger, at least two recorded weeks) names this contract; or
- a ledger entry records a real grant-mode deployment observing the silent
  `readback_failed` degradation before that batch.

Until a trigger fires, grant-mode deployments keep the known degradation and
canonical no-grant deployments are unaffected. When Core ships the kind, the
adapter-side implementation returns to npcink-ai-client-adapter issue #93;
its grant-mode smoke assertion `pattern page execution verifies post-block
readback` is the acceptance signal.

## Alternatives Considered

### Option 1: Execution-attached verification reads

The approved-and-executed write's envelope carries or derives a read
authorization for the named readback abilities automatically.

Pros:

- No adapter-side second flow; readback just works after execution.

Cons:

- Grafts a second, implicit authorization path onto execution, weakening the
  single read-authorization truth;
- verification reads become harder to audit, rate-limit, or deny
  independently of the write that triggered them;
- the execution envelope gains credential semantics it does not need today.

Rejected because it trades audit clarity for convenience that only
grant-mode deployments need, and those deployments are exactly the ones that
value independent read authorization.

### Status quo: accept the degradation

Rejected because a governance chain that silently loses verification
evidence in its strictest configuration is a contract gap, not noise; it was
recorded as one in Core issue #125.

## Consequences

- Core's read-authorization surface gains one narrow, automated kind with an
  ability allowlist and object scope; it does not gain execution, proposal,
  or credential-storage authority.
- The sensitive-read contract docs and static contracts must cover the new
  kind when it is implemented.
- Grant-mode acceptance evidence before implementation must classify
  `readback_failed` as the known pre-trigger limitation instead of a
  regression.
