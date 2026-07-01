# Eval-Lab Governance Hardening Closeout - 2026-07-01

## Status

Closed and merged.

- Implementation commit: `41bc5f6` (`Harden governance fail-closed paths`)
- Pull request: [#43](https://github.com/muze-page/npcink-governance-core/pull/43)
- Merge commit: `0769c54`
- Merged at: `2026-07-01T01:51:46Z`

## Context

An adversarial Eval-Lab review focused on Core's current product positioning,
module boundary, security posture, performance posture, and failure behavior.
The review produced one focused implementation target: harden governance
persistence paths without expanding Core into an executor, workflow runtime,
provider runtime, or product workflow owner.

The scope stayed inside Npcink Governance Core's existing authority:

- proposal records;
- approval and rejection lifecycle;
- commit preflight handoff;
- sensitive read request authorization;
- audit log persistence;
- minimal governance contracts and documentation.

## Scope Completed

The merged change hardened these paths:

- Commit preflight now uses a deterministic audit `event_id` for successful
  handoffs, so the audit table unique key also guards duplicate handoff races.
- Commit preflight denial now fails closed if the `commit.preflight_failed`
  audit event cannot be stored.
- One-time sensitive read grants roll back from `consumed` to `approved` if
  the `read_request.consumed` audit event cannot be stored.
- Sensitive read approval field updates are guarded by pending status and are
  restored when the later approval transition or audit write fails.
- Plan-to-proposal intake deletes proposals created in the current ingest when
  the aggregate `proposal.plan_ingested` audit event cannot be stored.
- App-key list queries no longer select `secret_hash`.
- Pending proposal guardrail checks use payload-light rows and avoid loading
  full `input_json` or `preview_json` for quota and duplicate checks.
- Duplicate pending proposal responses still reload the full proposal before
  returning, preserving the existing response contract.
- Canonical plan allowlists were synchronized across implementation, REST
  contract, governance contract, ability intake contract, plan-to-proposal
  documentation, and static contracts.

## Boundary

This was governance hardening only.

Core still does not:

- execute WordPress abilities;
- mutate WordPress content;
- own article, media, comment, SEO, or Toolbox product workflows;
- own model routing, provider keys, prompt or preset management, or billing;
- own workflow runtime, task queues, batch execution consoles, MCP runtime, or
  Agent Gateway catalogs;
- copy reusable ability definitions from `npcink-abilities-toolkit`.

Final writes remain outside Core. Core records, approves, preflights, and
audits.

## Verification

Local Core gates passed before the PR was opened:

- `composer test:all`
- `composer analyse:phpstan`
- `composer smoke:wp`

GitHub required checks passed on PR #43:

- `PR body contract`
- `Static contracts`

The first `PR body contract` run failed because the PR body used `Validation`
instead of the required `Verification` heading and omitted `Scope` and `Risk`.
The PR body was updated without code changes, and the rerun passed.

The targeted cross-repo gate matrix also passed for the repositories directly
relevant to this Core hardening:

| Repository | Gate | Result |
| --- | --- | --- |
| `npcink-abilities-toolkit` | `composer test:all` | passed |
| `npcink-governance-core` | `composer test:all` | passed |
| `npcink-ai-client-adapter` | `composer test:all` | passed |
| `npcink-workflow-toolbox` | `composer test:all` | passed |
| `npcink-cloud-addon` | `composer test:all` | passed |

## Notes From Matrix

The status-only matrix also reported background state that was intentionally not
handled in this Core closeout:

- `npcink-ai-cloud` had 17 local uncommitted changes.
- `npcink-ai-client-adapter` local `master` was ahead by one commit.
- `wp-magick-toolbox` local `main` was ahead by one commit.

Those are separate follow-up lines. They should not be mixed into Core
governance hardening or used as a reason to reopen PR #43.

## Decision

The Core hardening line is complete for this stage. Further work should be
opened as a separate branch and task unless a regression is found in one of the
merged fail-closed paths.

The next useful follow-up is not more Core product expansion. It is a separate
cleanup pass for the background repository states or a cross-repo contract
review focused on current Adapter, Toolbox, Toolkit, and Cloud boundaries.
