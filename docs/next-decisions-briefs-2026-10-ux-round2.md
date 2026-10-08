# Next Decisions Briefs — 2026-10 UX Round 2

Status: open. Raised by the 2026-10-08 user-experience round-2 audit after the
batch 1 and batch 2 fixes landed (`fix/ux-round2`). Each brief states the
current state, the options, and a recommendation so the release owner can
close them in one sitting.

## Decision 1: How should waiting proposals reach the operator?

**Current state.** After round 2 batch 1, the WordPress admin menu carries a
pending-count badge, so waiting proposals are visible to any logged-in
administrator browsing wp-admin. Nothing reaches an operator who is not in
wp-admin: no email, no admin-bar counter outside Core's own screen, no
webhook, no push. Pending proposals still expire silently after 24 hours if
nobody opens the queue. Machine consumers (OpenClaw-style adapters) poll
`GET /proposals` on their own schedule; every poll writes a
`proposal.listed` audit row and consumes rate quota.

**Options.**

- A. Status quo: menu badge only. Cost: a proposal can expire while the only
  administrator is away from wp-admin.
- B. Core sends email on first pending proposal (and at most once per N
  hours) to `admin_email`. Cheap, uses `wp_mail`, but Core gains a delivery
  concern, an address policy, and a spam surface; on local networks mail
  often silently fails.
- C. Adapter-owned notification: Core keeps its local
  `npcink_governance_core_observability_event` hook and documents it as the
  integration point; a channel adapter or companion plugin subscribes and
  implements its own notification UX (desktop, IM, email) with its own retry
  policy. Core stays a governance kernel and never gains webhook delivery,
  queues, or retry workers — consistent with ADR-002/ADR-005 and the
  Non-Goals in [Core Governance Operability](core-governance-operability.md).
- D. Core-native webhooks: Core POSTs signed events to configured URLs.
  Rejected for now: delivery queues, retries, dead-lettering, and secret
  management are exactly the runtime ownership Core must not take on.

**Recommendation.** C, with B as a possible later add-on behind an
opt-in setting if real operators report the gap. The observability hook
already fires for `core.proposal.create` with `status=ok` and carries
`proposal_id`; documenting it as the notification seam lets Adapter or a
small companion plugin own the operator-facing push without a new Core
decision each time. While polling remains the machine path, batch 2's
`X-RateLimit-*` headers let adapters back off cheaply.

## Decision 2: Cheaper status polling for machine consumers?

**Current state.** Adapters poll `GET /proposals?status=pending`. Each poll
writes an audit row and consumes `proposals_read` quota; there is no ETag or
304 path, and no lightweight "changes since" cursor.

**Options.**

- A. Status quo plus documented quota awareness (round 2 batch 2 already
  shipped `X-RateLimit-Remaining`).
- B. A `updated_since` filter on `GET /proposals` so an adapter polls a
  bounded window and usually gets `items: []`. Still writes `proposal.listed`
  audit rows; cheap to implement on the existing index.
- C. ETag/`If-None-Match` support. Rejected for now: Core-side caching
  invalidation across proposal mutations adds hidden state, and GETs that
  mutate audit rows make conditional caching semantically fragile.

**Recommendation.** B when a real adapter polling loop complains about quota
or audit noise; A until then. Neither changes the boundary.

Both decisions stay inside the current product boundary: no workflow runtime,
no queues, no Cloud writeback, no execution ownership. If any option starts
needing those, write a boundary note instead.
