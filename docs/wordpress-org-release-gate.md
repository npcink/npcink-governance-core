# WordPress.org Release Gate

Status: active release gate.

Before uploading this plugin to WordPress.org, run:

```sh
composer prepare:release
```

This release gate exists because functional tests and local smoke tests can pass
while WordPress.org rejects the package for review-policy issues.

When a release candidate also depends on the current Adapter and Toolkit
contracts, run the broader cross-repository gate before publishing or tagging:

```sh
composer acceptance:cross-repo-release
composer rc:version-matrix
```

See [Cross-Repo Release Acceptance](cross-repo-release-acceptance.md). That
gate is intentionally broader than the Core-only WordPress.org package check:
it proves the Governance Core, AI Client Adapter, and Abilities Toolkit still
compose without moving execution, ability ownership, or audit truth into the
wrong repository.

`composer prepare:release` wraps the current required local gate:

```sh
composer validate --no-check-publish
composer release:verify
composer smoke:wp
composer package:release
```

It also checks that the plugin header `Version`, the
`NPCINK_GOVERNANCE_CORE_VERSION` constant, and the `readme.txt` `Stable tag`
match before packaging, and confirms the final zip root is
`npcink-governance-core/`.

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

- plugin display name: `npcink-governance-core`;
- package slug and text domain: `npcink-governance-core`;
- top-level menu and product suite: `Npcink AI`;
- reviewer-facing description: `Npcink AI governance layer for WordPress operations`;
- main package file: `npcink-governance-core/npcink-governance-core.php`;
- REST namespace: `/wp-json/npcink-governance-core/v1/`;
- author: `Npcink`.

Internal PHP namespaces, function prefixes, table prefixes, hooks, options, and
error codes use the npcink-governance-core identity. Do not reintroduce legacy
Magick AI compatibility identifiers during a release-only pass.

The upload artifact is:

```text
build/npcink-governance-core.zip
```

Before upload, confirm the package root is `npcink-governance-core/` and the
plugin header contains:

```text
Plugin Name: npcink-governance-core
Author: Npcink
Text Domain: npcink-governance-core
```

For a specific release, pass the expected version:

```sh
composer prepare:release -- --version 0.1.1
```

Do not use `--skip-smoke` for a real WordPress.org release. That option exists
only for local script diagnostics when the LocalWP smoke environment is
temporarily unavailable.

The GitHub `Release Package` workflow may be used to build a downloadable zip
artifact for review after Core CI passes. It is not a publication gate and does
not run the LocalWP smoke test, Plugin Check release scan, or WordPress.org SVN
sync. Treat it as a secondary package sanity check before the local release gate.

## WordPress.org SVN Sync

GitHub is the development repository. WordPress.org SVN is the release
repository only: do not commit every development change to SVN.

After `composer prepare:release` succeeds, sync the built package to an
existing SVN checkout with a dry run first:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /path/to/wporg-npcink-governance-core
```

Apply the sync only after reviewing the dry-run output:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /path/to/wporg-npcink-governance-core --apply
svn status /path/to/wporg-npcink-governance-core
svn commit /path/to/wporg-npcink-governance-core -m "Release npcink-governance-core 0.1.1"
```

If the WordPress.org listing assets changed, include `--assets` so
`sj/exports/wordpress-org` is copied to SVN `/assets` and PNG MIME types are
set:

```sh
composer sync:wporg -- --version 0.1.1 --svn-dir /path/to/wporg-npcink-governance-core --assets --apply
```

The sync helper refuses to overwrite an existing `tags/<version>` directory.
For normal releases, create each tag once and publish a newer version for
follow-up fixes.

## Plugin Check Notes

Run Plugin Check through Composer so the local symlink and exclusions match the
release gate:

```sh
composer plugin-check:release
```

The current expected Plugin Check result is exit code `0` with no errors or
warnings.

Core owns custom WordPress database tables for governance state in these
repository classes:

- `includes/Governance/Proposal_Repository.php`;
- `includes/Audit/Audit_Log_Repository.php`;
- `includes/Security/App_Key_Repository.php`;
- `includes/Security/App_Rate_Limiter.php`.

Direct database calls against those tables must use narrow local
`phpcs:disable` comments with a reason next to the affected query. Do not hide
database warnings globally, and do not suppress direct database warnings for
queries outside Core-owned governance tables.

If Plugin Check reports any `ERROR` or `WARNING`, treat it as a release
blocker until the issue is fixed or the local suppression is narrow,
reviewable, and documented next to the specific custom-table query.

## WordPress.org Additional Information

Use the Additional Information field to help reviewers understand the plugin's
technical boundary. Prefer this reviewer-facing text over marketing copy:

```text
The npcink-governance-core plugin is the Npcink AI governance layer for WordPress operations, reviewing and approving AI-initiated WordPress actions before execution.

The plugin registers REST endpoints under /wp-json/npcink-governance-core/v1/ and stores proposals, audit events, app keys, and rate-limit state in custom WordPress database tables. These direct database queries are intentional because the plugin owns those tables.

The plugin does not execute final write actions itself. It creates proposals, records approvals/rejections, exposes commit-preflight context, and keeps final execution disabled for a trusted downstream adapter or human-reviewed workflow.

The bundled examples use Npcink ability ids, but the base governance proposal lifecycle is not limited to that provider. Other WordPress Abilities API provider plugins can expose stable ability ids, schemas, permission callbacks, risk metadata, and dry-run previews, then submit write/destructive actions for Core review.

The plugin does not call external services, does not load remote assets, and does not send site data to third parties. App secrets are hashed before storage, and one-time bearer tokens are only shown at creation time.

Internal PHP namespaces, function prefixes, table prefixes, hooks, options, and error codes use the npcink-governance-core identity. The public plugin name, slug, text domain, REST namespace, and WordPress.org package identity are npcink-governance-core.
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
3. Run `composer prepare:release -- --version <version>`.
4. For cross-repository release candidates, run
   `composer acceptance:cross-repo-release` and `composer rc:version-matrix`.
5. Run `composer sync:wporg -- --version <version> --svn-dir <checkout>` as a
   dry run, then re-run with `--apply` after reviewing the output.
6. Inspect `build/npcink-governance-core.zip` before upload.
7. Commit the SVN checkout only after the public identity, Plugin Check
   results, and SVN status match this file.
