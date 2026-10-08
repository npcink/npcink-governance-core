# Next Decisions Briefs — 2026-10

Status: decided 2026-10-08. Each brief states the current state, the
options, and a recommendation; the release owner closed all three before the
0.3.0 release closeout (see the Decision Record at the end). These briefs
exist so the three open decisions from
[Core Governance Operability](core-governance-operability.md) can be closed
in one sitting instead of drifting across sessions.

## Decision 1: Productize app-key rotation and expiry?

**Current state.** Rotation is manual: an administrator issues a replacement
token in Core, then updates `NPCINK_OPENCLAW_ADAPTER_CORE_APP_TOKEN` on the
Adapter host. Expiry timestamps are set per key. As of 0.3.0 the failure path
is fully legible — expired keys return
`npcink_governance_core_app_auth_expired` and the Adapter appends a
rotate-key hint naming the env constant. `docs/next-stage-plan.md` defers
automation "until a real external client needs long-lived credentials".

**Options.**

- A. Keep manual rotation (status quo). Cost: an operator interrupt whenever
  a key expires; today that is one key on one host.
- B. Expiry awareness without automation: Core records a warning audit event
  and surfaces an "expiring soon" admin notice (the list already shows
  `expires_soon` / `rotation_recommended` hints), plus a documented rotation
  runbook. No new endpoints, no credential flow changes.
- C. Full self-service rotation: an authenticated endpoint where a valid
  current key exchanges itself for a successor. Requires signing/exchange
  design, revocation grace windows, and audit semantics — real security
  surface.

**Recommendation.** B now, C only when a second long-lived external consumer
actually exists. With a single Adapter consumer, C's security surface buys
nothing the runbook does not.

## Decision 2: Promote provider-log correlation to a release gate?

**Current state.** The Adapter carries `proposal_id` and commit-preflight
`correlation_id` into AI Request Log context, per
[AI Provider Log Correlation](ai-provider-log-correlation.md). Verification
today is a documented manual check on the Adapter side
(`docs/openclaw-consumer-acceptance.md`), not an assertion inside
`composer acceptance:cross-repo-release`.

**Options.**

- A. Keep it a documented manual acceptance step (status quo).
- B. Add an assertion to the cross-repo release acceptance: after the signed
  Adapter fixture executes a draft, assert an AI Request Log row exists that
  carries the same `correlation_id`. Adds a dependency on the `ai` plugin
  being active in the acceptance environment.

**Recommendation.** B, but only as part of the next cross-repo acceptance
authoring pass — not as a hotfix to the current script. The correlation
contract is governance-critical (it is what makes Core audit and provider
logs joinable), so it should not rest on manual discipline forever. Accept
the environment dependency by skipping-with-warning when the `ai` plugin is
absent, failing when it is present but the row is missing.

## Decision 3: Revisit final commit execution ownership (new ADR)?

**Current state.** ADR-003 keeps final WordPress writes outside Core; the
Adapter executes approved abilities through the WordPress Abilities API after
Core preflight. Execution profiles are allowlisted and audited; the
idempotency and failure contracts ADR-003 named as preconditions are now
covered by tests (per-action fingerprints, idempotency keys,
`record-execution` binding). The current consumer count for execution is one
(the Adapter).

**Options.**

- A. Reaffirm ADR-003 unchanged. Rationale: moving execution into Core would
  concentrate blast radius, duplicate the Abilities API surface, and buy
  nothing while a single channel executes.
- B. Open ADR-010 with explicit re-evaluation triggers instead of an open
  question: e.g. "a second execution surface ships", "the unattended local
  automation runtime (ADR-007) becomes real", or "a consumer requires Core
  itself to execute under audit". Until a trigger fires, ADR-003 stands.

**Recommendation.** B. The open question is more expensive than the answer;
converting it into named triggers closes the decision without changing any
behavior.

## Decision Record — 2026-10-08

The release owner closed all three briefs before the 0.3.0 release closeout:

1. **App-key rotation: Option B adopted.** Expiry awareness stays
   manual-first. The 0.3.0 `expires_soon` / `rotation_recommended` key-list
   hints and the Adapter's rotate-key relay cover awareness, and the rotation
   runbook is now recorded in
   [App Auth Scope Policy](app-auth-scope-policy.md). A dedicated
   expiring-soon audit event remains an optional small follow-up slice, not a
   release blocker. Full self-service key exchange (Option C) stays deferred
   until a second long-lived external consumer exists.
2. **Provider-log correlation gate: Option B adopted on the recommended
   schedule.** The correlation assertion is added in the next cross-repo
   acceptance authoring pass, not as a hotfix to the 0.3.0 script. Until
   then the documented manual Adapter acceptance check remains the evidence
   path.
3. **Final commit execution ownership: Option B adopted.**
   [ADR-010](decisions/ADR-010-defer-final-commit-ownership-until-trigger.md)
   is accepted and converts the open question into named re-evaluation
   triggers. ADR-003 stands until one fires.

## Also pending (not a brief)

- [Flagged media deletion policy](flagged-media-deletion-policy.md): the five
  pre-implementation checkboxes are a review task for the release owner, not
  a decision brief. Half an hour with the policy document closes it.
- Editor adoption gate: moot as of 2026-10-07 — drafts 286721/286722 no
  longer exist on the local site, so the article discovery pilot can start
  from current drafts.
