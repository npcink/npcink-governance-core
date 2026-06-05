# Approve Comment Governance Scenario

Status: active Core scenario.

This document records the third practical consumer-side governance loop that
Core should keep stable: `npcink-abilities-toolkit/approve-comment`.

The scenario proves Core can govern comment moderation writes for a non-post
resource without becoming a comment product, workflow runtime, MCP runtime, or
final WordPress write executor.

## Scenario Boundary

Core owns:

- discovering `npcink-abilities-toolkit/approve-comment` through ability intake;
- preserving the real `ability_id` and input schema in capability responses;
- creating a proposal for a pending comment moderation action;
- letting an administrator approve or reject the proposal;
- returning approval context from commit preflight;
- auditing the lifecycle.

Core does not own:

- changing the comment status;
- generating moderation replies;
- choosing moderation policy, prompts, or providers;
- executing MCP tools;
- running workflow queues or retry logic.

## Required Flow

1. A consumer calls `GET /wp-json/npcink-governance-core/v1/capabilities`.
2. The consumer locates `npcink-abilities-toolkit/approve-comment` and verifies it is a
   write-risk ability with `requires_approval=true`.
3. The consumer reads the input schema, especially:
   - `comment_id` is required and identifies an existing WordPress comment;
   - `dry_run`, `commit`, and `idempotency_key` are governance controls;
   - default intent remains dry-run / no commit.
4. The consumer calls `POST /wp-json/npcink-governance-core/v1/proposals` with:
   - `ability_id=npcink-abilities-toolkit/approve-comment`;
   - structured `input` containing `comment_id`;
   - a moderation preview containing `comment_id`, current status, target
     action, `dry_run=true`, and `commit=false`;
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
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-comment-approval-proposal \
  --comment-id=123 \
  --current-status=hold \
  --post-id=456
```

The command discovers capabilities first, validates the comment approval
contract, then creates a proposal with `dry_run=true`, `commit=false`, and a
preview containing `comment_id`, `current_status`, and
`target_action=approve`. It does not approve the proposal or execute the
comment mutation.

After a human approves the proposal in WordPress:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \
  --proposal=<proposal_id>
```

## Verification

`composer smoke:wp` locks this scenario by checking that:

- `npcink-abilities-toolkit/approve-comment` is discoverable from `npcink-abilities-toolkit`;
- the ability is write-risk and requires approval;
- the input schema includes required `comment_id` and governance controls;
- smoke creates a real pending comment for the proposal;
- proposal creation stores the real ability id;
- pending proposals fail commit preflight;
- approved proposals return approval context;
- preflight rediscovers the capability and returns `commit_execution=false`;
- preflight preserves the dry-run input without turning it into a commit
  request;
- the comment remains pending after the Core governance loop;
- audit filters can retrieve the lifecycle events.

If this scenario fails because `npcink-abilities-toolkit` changed schema or metadata,
do not patch Core with aliases or fallback definitions. Fix the ability contract
in `npcink-abilities-toolkit` or update this scenario document after an explicit
contract change.
