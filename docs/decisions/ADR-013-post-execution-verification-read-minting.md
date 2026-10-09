# ADR-013: Post-Execution Verification Read Minting

## Status
Proposed

First filed as a second `ADR-012` in PR #135; renumbered to ADR-013 to
resolve the number collision with the standing execution-attached decision,
and its internal references updated to the canonical numbering (ADR-012 is
the execution-attached decision; ADR-011 is its superseded predecessor).

## Date
2026-10-09

## Context
ADR-012 mints execution-attached verification reads at commit preflight. Its
recorded limitation is confirmed by acceptance evidence (2026-10-09): with
npcink-ai-client-adapter#94 merged (positional action binding included), the
grant-mode smoke assertion `adapter pattern page execution verifies post-block
readback` still fails for batch proposals whose update action targets a post
created by an earlier action in the same execution — the object does not exist
at preflight time, so no preflight mint can address it, and the readback
degrades to `readback_failed`.

The minting moment must move to after the write for in-transaction objects.
The binding rule from ADR-012 stands unchanged: Core binds the grant to
Core-verified evidence only, never to an Adapter-asserted object id.

## Options

1. **Two-phase record**: the Adapter records the execution result (status
   `executed`, per-action results with resolved object ids) BEFORE the
   readback, Core's record-execution response carries newly minted
   verification reads bound to the recorded results, and the Adapter records
   a verification supplement afterwards. Cost: record-execution gains a
   second phase and the execution record contract changes shape.
2. **Provisional record, final record**: the Adapter records a provisional
   execution (actions executed, objects resolved), runs readbacks against
   minted grants, then finalizes the record with verification evidence.
   Cost: a new record lifecycle state; benefit: the finalized record is the
   complete audit object it is today.
3. **Evidence-checked preflight mint**: read-preflight accepts an
   `execution_verification` presentation whose object must match an
   execution the Adapter has already recorded. Cost: the readback must
   happen after record-execution, which inverts today's order; benefit: no
   record-contract change, one new preflight presentation.

## Decision
Proposed: option 2 (provisional record, final record). It keeps the audit
object complete-at-rest, binds every grant to recorded evidence, and leaves
read-preflight's contract untouched. The pairing map and same-object rules
from ADR-012 apply unchanged to post-execution mints; grants stay
single-use, verification-sourced, and TTL-bounded by the record window.

## Consequences
- In-transaction batch objects gain verified readbacks; the grant-mode
  acceptance assertion in npcink-ai-client-adapter#93 becomes satisfiable.
- Core gains a record lifecycle state (provisional -> final) but no
  execution role and no new channel scope.
- The Adapter changes its execution order: execute -> provisional record ->
  readback -> final record.
- Supersedes the Limitations section of ADR-012 for the in-transaction case;
  preflight minting remains for statically-addressed objects.
