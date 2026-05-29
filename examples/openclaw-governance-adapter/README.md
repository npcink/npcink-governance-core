# OpenClaw Governance Adapter Example

This is a minimal external-client example for connecting OpenClaw-like agent
software to Magick AI Core governance.

It is not an MCP server, tool catalog, workflow runtime, or WordPress ability
executor. It only calls Core REST governance routes:

- `GET /wp-json/magick-ai-core/v1/capabilities`
- `POST /wp-json/magick-ai-core/v1/proposals`
- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`

Generic adapters should not approve proposals by default. Approval should stay
with the WordPress admin UI or a separately contracted trusted host policy.

## Authentication

Use a scoped Magick AI Core app token when available. The token is returned once
from `POST /wp-json/magick-ai-core/v1/apps` and should not be committed.

Required environment variables:

```bash
export MAGICK_AI_CORE_BASE_URL="https://example.test"
export MAGICK_AI_CORE_APP_TOKEN="mai_core.key_xxx.secret_xxx"
```

During early local PoC work, the script can still fall back to a WordPress
Application Password for a `manage_options` user:

```bash
export MAGICK_AI_CORE_USER="admin-user"
export MAGICK_AI_CORE_APPLICATION_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
```

## Commands

List capabilities:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php capabilities
```

Create a proposal with a real, discoverable `ability_id`:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-proposal \
  --ability=magick-ai/create-draft \
  --title="OpenClaw draft proposal" \
  --summary="Review before creating a draft." \
  --input='{"title":"Draft title","content":"<p>Draft body.</p>","dry_run":true}' \
  --preview='{"dry_run":true,"source":"openclaw"}'
```

Run commit preflight after a human or trusted host approves the proposal:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \
  --proposal=00000000-0000-0000-0000-000000000000
```

`commit-preflight` returns Core approval context and `commit_execution=false`.
The external adapter must not execute a write unless the target WordPress
ability contract accepts that approval context and idempotency protection.
