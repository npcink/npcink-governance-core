# WordPress.org Release Gate

Status: active release gate.

Before uploading this plugin to WordPress.org, run:

```sh
composer release:verify
composer smoke:wp
composer package:release
```

This release gate exists because functional tests and local smoke tests can pass
while WordPress.org rejects the package for review-policy issues.

The local `check:wporg` guard blocks recurring review problems:

- direct `wp-admin/includes/*` path construction, except the common
  `upgrade.php` activation helper for `dbDelta()`;
- admin request parameters read directly from `$_GET`;
- inline admin CSS or JS emitted from PHP;
- raw `<script>` or `<style>` tags in PHP admin views;
- missing `permission_callback` entries on REST routes;
- legacy `mai_core` / `mai_` Magick AI token or id prefixes in release PHP;
- raw `SELECT` strings passed directly to `$wpdb->get_var()`;
- inline SQL `WHERE` assembly with `implode()`;
- dynamic transient keys whose `get_transient()` or `set_transient()` call site
  does not visibly include the `npcink_governance_core` prefix.

When WordPress.org sends a review email, decode the current top-level message,
extract every cited file and line, fix the whole pattern class, and add a local
guard when the pattern is statically checkable.

For option and transient names, do not rely on cross-function proof such as
"this variable was already prefix-checked." WordPress.org review may flag a
variable-only call like `set_transient( $prefixed_key, ... )` even when the key
is safe at runtime. Build the key so the prefix is visible at the WordPress API
call site, for example `set_transient( PREFIX . $suffix, ... )`, and keep
`composer check:wporg` strict enough to reject variable-only transient keys.

## Current Submission Identity

Use this public identity for the WordPress.org upload:

- plugin display name: `Npcink Governance Core`;
- package slug and text domain: `npcink-governance-core`;
- top-level menu and product suite: `Npcink AI`;
- reviewer-facing description: `Npcink AI governance layer for WordPress operations`;
- main package file: `npcink-governance-core/npcink-governance-core.php`;
- REST namespace: `/wp-json/npcink-governance-core/v1/`;
- author: `Npcink`.

Internal PHP namespaces, function prefixes, table prefixes, hooks, options, and
error codes use the Npcink Governance Core identity. Do not reintroduce legacy
Magick AI compatibility identifiers during a release-only pass.

The upload artifact is:

```text
build/npcink-governance-core.zip
```

Before upload, confirm the package root is `npcink-governance-core/` and the
plugin header contains:

```text
Plugin Name: Npcink Governance Core
Author: Npcink
Text Domain: npcink-governance-core
```

## Plugin Check Notes

Run Plugin Check through Composer so the local symlink and exclusions match the
release gate:

```sh
composer plugin-check:release
```

The current expected Plugin Check result is exit code `0` with warnings only.
Warnings around `WordPress.DB.DirectDatabaseQuery.DirectQuery` and
`WordPress.DB.DirectDatabaseQuery.NoCaching` are expected in these repository
classes because Core owns custom WordPress database tables for governance
state:

- `includes/Governance/Proposal_Repository.php`;
- `includes/Audit/Audit_Log_Repository.php`;
- `includes/Security/App_Key_Repository.php`;
- `includes/Security/App_Rate_Limiter.php`.

Do not hide these warnings globally. If a warning is a narrow false positive,
use a local `phpcs:disable` with a reason next to the affected query.

If Plugin Check reports any `ERROR`, treat it as a release blocker. If it
reports text-domain, slug, readme, or remote asset issues, fix those before
uploading a new zip.

## WordPress.org Additional Information

Use the Additional Information field to help reviewers understand the plugin's
technical boundary. Prefer this reviewer-facing text over marketing copy:

```text
Npcink Governance Core is the Npcink AI governance layer for WordPress operations, reviewing and approving AI-initiated WordPress actions before execution.

The plugin registers REST endpoints under /wp-json/npcink-governance-core/v1/ and stores proposals, audit events, app keys, and rate-limit state in custom WordPress database tables. These direct database queries are intentional because the plugin owns those tables.

The plugin does not execute final write actions itself. It creates proposals, records approvals/rejections, exposes commit-preflight context, and keeps final execution disabled for a trusted downstream adapter or human-reviewed workflow.

The bundled examples use Npcink ability ids, but the base governance proposal lifecycle is not limited to that provider. Other WordPress Abilities API provider plugins can expose stable ability ids, schemas, permission callbacks, risk metadata, and dry-run previews, then submit write/destructive actions for Core review.

The plugin does not call external services, does not load remote assets, and does not send site data to third parties. App secrets are hashed before storage, and one-time bearer tokens are only shown at creation time.

Internal PHP namespaces, function prefixes, table prefixes, hooks, options, and error codes use the Npcink Governance Core identity. The public plugin name, slug, text domain, REST namespace, and WordPress.org package identity are Npcink Governance Core / npcink-governance-core.
```

If the form field is short, use this condensed version:

```text
This plugin provides a local WordPress governance layer for AI-initiated actions. It creates reviewable proposals for WordPress Abilities API providers, records audit events, manages app keys, and exposes commit-preflight context under /wp-json/npcink-governance-core/v1/. It does not execute final write actions itself, does not call external services, and does not send site data to third parties. Direct database queries are intentional because the plugin owns its custom proposal, audit, app key, and rate-limit tables.
```

## Handoff Checklist For Agents

When resuming release work:

1. Read this file, `README.md`, `docs/testing-strategy.md`, and
   `docs/security-model.md`.
2. Run `git status --short --branch` and preserve unrelated user changes.
3. Run `composer release:verify` and `composer smoke:wp`.
4. Run `composer package:release` after any file change that affects the zip.
5. Inspect `build/npcink-governance-core.zip` before upload.
6. Upload the zip through the WordPress.org Add Plugin page only after the
   public identity and Plugin Check results match this file.
