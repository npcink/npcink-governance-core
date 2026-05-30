# Taxonomy Terms Preview Governance Scenario

Status: active Core scenario.

This document records the fourth consumer-side governance loop that Core should
keep stable: `magick-ai/propose-post-taxonomy-terms` feeding a
`magick-ai/set-post-terms` proposal.

The scenario proves Core can consume a deterministic taxonomy preview helper
without becoming a taxonomy product, WordPress Abilities API runtime, MCP
runtime, or final WordPress write executor.

## Scenario Boundary

Core owns:

- discovering `magick-ai/propose-post-taxonomy-terms` and
  `magick-ai/set-post-terms` through ability intake;
- preserving the real ability ids and schemas in capability responses;
- accepting a proposal for `magick-ai/set-post-terms` using dry-run input
  produced by the preview helper;
- letting an administrator approve or reject the proposal;
- returning approval context from commit preflight;
- correlating the proposal lifecycle through audit filters.

Core does not own:

- executing `magick-ai/propose-post-taxonomy-terms`;
- assigning taxonomy terms to a post;
- creating missing terms;
- deciding editorial taxonomy policy;
- executing MCP tools;
- running workflow queues or retry logic.

## Required Flow

1. A consumer calls `GET /wp-json/magick-ai-core/v1/capabilities`.
2. The consumer locates:
   - `magick-ai/propose-post-taxonomy-terms` as a read-risk helper with
     `governance_mode=direct_read` and `execution_surface=wp_abilities_rest`;
   - `magick-ai/set-post-terms` as a write-risk ability with
     `requires_approval=true`.
3. The consumer runs the preview helper through WordPress Abilities API, not
   through Core.
4. The helper resolves existing terms only and returns a dry-run proposal
   payload targeting `magick-ai/set-post-terms`.
5. The consumer calls `POST /wp-json/magick-ai-core/v1/proposals` with:
   - `ability_id=magick-ai/set-post-terms`;
   - `input` from the helper's `proposal.input`;
   - `preview.proposal_helper_ability_id=magick-ai/propose-post-taxonomy-terms`;
   - `preview.target_ability_id=magick-ai/set-post-terms`;
   - `dry_run=true`, `commit=false`, `create_missing=false`, and
     `commit_execution=false`;
   - non-secret caller attribution.
6. A WordPress administrator approves or rejects in Core.
7. After approval, the consumer calls
   `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`.
8. Core returns:
   - the stored proposal;
   - the rediscovered `magick-ai/set-post-terms` capability row;
   - `approval_context.approval_commit_authorized=true`;
   - `approval_context.confirmation_state=approved_commit`;
   - `commit_execution=false`.

## Adapter Path

The OpenClaw example adapter has a dedicated command for the Core proposal
handoff:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-taxonomy-terms-proposal \
  --helper-output=@taxonomy-preview.json
```

The `taxonomy-preview.json` file should be the output from running
`magick-ai/propose-post-taxonomy-terms` through WordPress Abilities API. The
adapter validates both capability rows through Core first, then creates a
`magick-ai/set-post-terms` proposal with `dry_run=true`, `commit=false`, and
`create_missing=false`. It does not approve the proposal, create terms, assign
terms, or execute the final mutation.

For local hand-authored smoke input, the same command can accept already
resolved existing terms:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-taxonomy-terms-proposal \
  --post-id=123 \
  --taxonomy=post_tag \
  --mode=append \
  --term-ids=12,13
```

After a human approves the proposal in WordPress:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \
  --proposal=<proposal_id>
```

## Verification

`composer smoke:wp` locks this scenario by checking that:

- `magick-ai/propose-post-taxonomy-terms` is discoverable from
  `magick-ai-abilities`;
- the helper is read-risk, direct-read, and routed to WordPress Abilities API;
- `magick-ai/set-post-terms` is discoverable as a write-risk ability requiring
  approval;
- the helper runs through WordPress Abilities API and targets
  `magick-ai/set-post-terms`;
- the helper output keeps `dry_run=true`, `commit=false`, and
  `create_missing=false`;
- Core creates, approves, and preflights the `magick-ai/set-post-terms`
  proposal without executing the final write;
- post terms remain unchanged after the Core governance loop;
- audit filters correlate the taxonomy proposal lifecycle and the
  `commit.preflighted` event.

If this scenario fails because `magick-ai-abilities` changed schema, metadata,
or helper output shape, do not patch Core with aliases or fallback definitions.
Fix the ability contract in `magick-ai-abilities` or update this scenario
document after an explicit contract change.
