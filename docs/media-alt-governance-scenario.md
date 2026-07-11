# Missing Media ALT Governance Scenario

Status: accepted v1 governance contract.

## Contract

`npcink-abilities-toolkit/build-media-alt-apply-plan` is the only accepted
plan-to-proposal entry for the first missing media ALT write slice. It must
produce `media_alt_apply_plan.v1` with exactly one dry-run action targeting
`npcink-abilities-toolkit/update-media-details`.

Core accepts the plan only when:

- one image attachment is identified;
- `expected_current_alt` is explicitly empty;
- the reviewed ALT is 3 to 160 characters and contains no runtime provenance;
- `operator_visual_review_confirmed=true` exists in both input and review evidence;
- the action contains only ALT-only fields plus host controls;
- `media_alt_caption_review_set.v1` and `media_alt_apply_plan.v1` evidence agree;
- a stable idempotency key is present;
- operation classification is `core_proposal_required`.

Core stores the old value, proposed value, attachment id, visual confirmation,
evidence refs, plan version, actor/caller attribution, and idempotency key in
the existing proposal and audit lifecycle. It does not add a media workflow,
queue, execution engine, or second attachment truth source.

## Approval Policy

The default `manual` mode keeps the proposal pending for human approval. If the
site operator explicitly enables `smart_guarded`, Core may auto-approve only
the same missing-only v1 contract after its existing caller-scope, quota, and
audit checks pass. Weak, filename-like, non-empty, caption, title, description,
or attribution updates are not eligible for this rule.

## Commit Preflight And Live Drift

Commit preflight revalidates that the approved input still matches the stored
ALT-only review evidence. It returns `media_alt_guard` with
`requires_live_value_check=true`.

Core does not read or mutate attachment metadata during preflight. Adapter must
run the Toolkit dry-run immediately before commit; Toolkit compares the live
attachment ALT with `expected_current_alt` and rejects drift with a stable
conflict error. Final execution and rollback remain outside Core.

