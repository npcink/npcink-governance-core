# Create Draft Governance Scenario

Status: active Core scenario.

This document records the first practical consumer-side governance loop that
Core should keep stable: `npcink-abilities-toolkit/create-draft`.

The scenario proves a host-governed write can move through Core without making
Core a content product, workflow runtime, MCP runtime, or final WordPress write
executor.

## Scenario Boundary

Core owns:

- discovering `npcink-abilities-toolkit/create-draft` through ability intake;
- preserving the real `ability_id` and input schema in capability responses;
- creating a proposal for the draft intent;
- letting an administrator approve or reject the proposal;
- returning approval context from commit preflight;
- auditing the lifecycle.

Core does not own:

- writing the post;
- generating the article body;
- choosing models or prompts;
- executing MCP tools;
- running workflow queues or retry logic.

## Required Flow

1. A consumer calls `GET /wp-json/npcink-governance-core/v1/capabilities`.
2. The consumer locates `npcink-abilities-toolkit/create-draft` and verifies it is a
   write-risk ability with `requires_approval=true`.
3. The consumer reads the input schema, especially:
   - `title` is required;
   - `dry_run`, `commit`, and `idempotency_key` are governance controls;
   - default intent remains dry-run / no commit.
4. The consumer calls `POST /wp-json/npcink-governance-core/v1/proposals` with:
   - `ability_id=npcink-abilities-toolkit/create-draft`;
   - structured `input`;
   - a dry-run or handoff `preview`;
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
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-draft-proposal \
  --title="Draft title" \
  --content="<p>Draft body.</p>"
```

The command discovers capabilities first, validates the `create-draft` contract,
then creates a proposal with `dry_run=true` and `commit=false`. It does not
approve the proposal or execute the write.

After a human approves the proposal in WordPress:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \
  --proposal=<proposal_id>
```

## Verification

`composer smoke:wp` locks this scenario by checking that:

- `npcink-abilities-toolkit/create-draft` is discoverable from `npcink-abilities-toolkit`;
- the ability is write-risk and requires approval;
- the input schema includes required `title` and governance controls;
- proposal creation stores the real ability id;
- pending proposals fail commit preflight;
- approved proposals return approval context;
- preflight rediscovers the capability and returns `commit_execution=false`;
- audit filters can retrieve the lifecycle events.

If this scenario fails because `npcink-abilities-toolkit` changed schema or metadata,
do not patch Core with aliases or fallback definitions. Fix the ability contract
in `npcink-abilities-toolkit` or update this scenario document after an explicit
contract change.
