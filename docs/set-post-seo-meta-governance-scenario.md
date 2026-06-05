# Set Post SEO Meta Governance Scenario

Status: active Core scenario.

This document records the second practical consumer-side governance loop that
Core should keep stable: `npcink-abilities-toolkit/set-post-seo-meta`.

The scenario proves Core can govern field-level updates to an existing
WordPress resource without becoming an SEO product, workflow runtime, MCP
runtime, or final WordPress write executor.

## Scenario Boundary

Core owns:

- discovering `npcink-abilities-toolkit/set-post-seo-meta` through ability intake;
- preserving the real `ability_id` and input schema in capability responses;
- creating a proposal for an existing post's SEO metadata field update;
- letting an administrator approve or reject the proposal;
- returning approval context from commit preflight;
- auditing the lifecycle.

Core does not own:

- writing SEO metadata;
- choosing SEO strategy, keywords, prompts, or providers;
- resolving SEO plugin compatibility;
- executing MCP tools;
- running workflow queues or retry logic.

## Required Flow

1. A consumer calls `GET /wp-json/npcink-governance-core/v1/capabilities`.
2. The consumer locates `npcink-abilities-toolkit/set-post-seo-meta` and verifies it is a
   write-risk ability with `requires_approval=true`.
3. The consumer reads the input schema, especially:
   - `post_id` is required and identifies an existing WordPress post;
   - `seo_title` and `seo_description` are the reviewable field update inputs;
   - `dry_run`, `commit`, and `idempotency_key` are governance controls;
   - default intent remains dry-run / no commit.
4. The consumer calls `POST /wp-json/npcink-governance-core/v1/proposals` with:
   - `ability_id=npcink-abilities-toolkit/set-post-seo-meta`;
   - structured `input` containing `post_id` and one or more SEO fields;
   - a field-level `preview.field_patch`;
   - non-secret caller attribution.
5. A WordPress administrator approves or rejects in Core.
6. After approval, the consumer calls
   `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`.
7. Core returns:
   - the stored proposal;
   - the rediscovered capability row;
   - `approval_context.approval_commit_authorized=true`;
   - `approval_context.confirmation_state=approved_commit`;
   - `commit_execution=false`.

## Adapter Path

The OpenClaw example adapter has a dedicated command for this scenario:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-seo-meta-proposal \
  --post-id=123 \
  --seo-title="SEO title" \
  --seo-description="SEO description"
```

The command discovers capabilities first, validates the SEO metadata contract,
then creates a proposal with `dry_run=true`, `commit=false`, and a
`preview.field_patch` containing only the SEO fields under review. It does not
approve the proposal or execute the write.

After a human approves the proposal in WordPress:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \
  --proposal=<proposal_id>
```

## Verification

`composer smoke:wp` locks this scenario by checking that:

- `npcink-abilities-toolkit/set-post-seo-meta` is discoverable from `npcink-abilities-toolkit`;
- the ability is write-risk and requires approval;
- the input schema includes required `post_id`, SEO fields, and governance
  controls;
- proposal creation stores the real ability id;
- pending proposals fail commit preflight;
- approved proposals return approval context;
- preflight rediscovers the capability and returns `commit_execution=false`;
- preflight preserves the field-level dry-run input without turning it into a
  commit request;
- audit filters can retrieve the lifecycle events.

If this scenario fails because `npcink-abilities-toolkit` changed schema or metadata,
do not patch Core with aliases or fallback definitions. Fix the ability contract
in `npcink-abilities-toolkit` or update this scenario document after an explicit
contract change.
