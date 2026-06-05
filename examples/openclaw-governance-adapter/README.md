# OpenClaw Governance Adapter Example

This is a minimal external-client example for connecting OpenClaw-like agent
software to Npcink Governance Core governance.

It is not an MCP server, tool catalog, workflow runtime, or WordPress ability
executor. It only calls Core REST governance routes:

- `GET /wp-json/npcink-governance-core/v1/capabilities`
- `POST /wp-json/npcink-governance-core/v1/proposals`
- `POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight`

Generic adapters should not approve proposals by default. Approval should stay
with the WordPress admin UI or a separately contracted trusted host policy.

Capability rows include execution guidance for OpenClaw-like clients:

- `governance_mode=direct_read` and `execution_surface=wp_abilities_rest` mean
  a read-only ability should be executed by the adapter through WordPress
  Abilities API.
- `governance_mode=proposal_required` and
  `execution_surface=adapter_after_core_preflight` mean a write or destructive
  ability must go through Core proposal approval and commit preflight first.
- `core_proxy_execute=false` means Core is not an ability proxy and this example
  intentionally does not call ability callbacks.

## Authentication

Use a scoped Npcink Governance Core app token when available. The token is returned once
from `POST /wp-json/npcink-governance-core/v1/apps` and should not be committed.

Required environment variables:

```bash
export NPCINK_GOVERNANCE_CORE_BASE_URL="https://example.test"
export NPCINK_GOVERNANCE_CORE_APP_TOKEN="mai_core.key_xxx.secret_xxx"
```

## Local TLS

Local by Flywheel and similar macOS development stacks often use local
self-signed certificates for `.local` domains. Prefer passing a trusted CA
bundle when available:

```bash
export NPCINK_GOVERNANCE_CORE_CA_BUNDLE="/path/to/local-ca.pem"
```

For throwaway local PoC work against `localhost`, `127.0.0.1`, `::1`, or
`.local` hosts, the example adapter can disable PHP cURL certificate validation:

```bash
export NPCINK_GOVERNANCE_CORE_INSECURE_SSL="true"
```

Do not use `NPCINK_GOVERNANCE_CORE_INSECURE_SSL=true` for production or public hosts.
The script refuses that mode outside local-only hostnames.

During early local PoC work, the script can still fall back to a WordPress
Application Password for a `manage_options` user:

```bash
export NPCINK_GOVERNANCE_CORE_USER="admin-user"
export NPCINK_GOVERNANCE_CORE_APPLICATION_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
```

## Commands

List capabilities:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php capabilities
```

Create the primary host-governed draft scenario. This command discovers
capabilities first, verifies `magick-ai/create-draft` is still a write-risk
ability requiring approval, checks the schema governance controls, and then
creates a proposal:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-draft-proposal \
  --title="Draft title" \
  --content="<p>Draft body.</p>"
```

Create the second host-governed field update scenario. This command discovers
capabilities first, verifies `magick-ai/set-post-seo-meta` is still a
write-risk ability requiring approval, checks the field-level schema, and then
creates a proposal:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-seo-meta-proposal \
  --post-id=123 \
  --seo-title="SEO title" \
  --seo-description="SEO description"
```

Create the third host-governed comment moderation scenario. This command
discovers capabilities first, verifies `magick-ai/approve-comment` is still a
write-risk ability requiring approval, checks the moderation schema, and then
creates a proposal:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-comment-approval-proposal \
  --comment-id=123 \
  --current-status=hold \
  --post-id=456
```

Create the taxonomy terms preview handoff scenario. Product adapters should
first run `magick-ai/propose-post-taxonomy-terms` through WordPress Abilities
API, then pass that helper output to this command. The command discovers
capabilities first, verifies the helper is direct-read, verifies
`magick-ai/set-post-terms` is still a write-risk ability requiring approval,
and then creates a dry-run proposal:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-taxonomy-terms-proposal \
  --helper-output=@taxonomy-preview.json
```

For local hand-authored smoke input, pass existing resolved term ids directly:

```bash
php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-taxonomy-terms-proposal \
  --post-id=123 \
  --taxonomy=post_tag \
  --mode=append \
  --term-ids=12,13
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
