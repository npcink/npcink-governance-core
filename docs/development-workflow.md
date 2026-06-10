# Development Workflow

Status: active.

This project is developed as a standalone WordPress plugin mounted into the
local `magick-ai` WordPress site.

## Local Paths

Project:

```text
/Users/muze/gitee/npcink-governance-core
```

Local WordPress root:

```text
/Users/muze/Local Sites/magick-ai/app/public
```

Plugin symlink:

```text
/Users/muze/Local Sites/magick-ai/app/public/wp-content/plugins/npcink-governance-core
-> /Users/muze/gitee/npcink-governance-core
```

Atomic ability provider:

```text
/Users/muze/gitee/npcink-abilities-toolkit
```

`npcink-abilities-toolkit` is responsible for reusable WordPress atomic abilities.
Core consumes those abilities; it must not copy their definitions.

## Local WordPress Access

The LocalWP smoke site uses administrator username `1`.

The local password is intentionally not stored in this repository. It is kept in
local memory notes as `[REDACTED_SECRET]`. Treat this as local-only smoke-test
context, never production access.

## Commands

Static contracts and PHP lint:

```bash
composer test:all
```

Real WordPress smoke:

```bash
composer smoke:wp
```

If the site is not using the default LocalWP paths, pass the WordPress root and
shared ability provider explicitly:

```bash
WP_PATH="/path/to/site/app/public" \
NPCINK_ABILITIES_TOOLKIT_PATH="/Users/muze/gitee/npcink-abilities-toolkit" \
composer smoke:wp
```

For the default LocalWP environment, the script prefers `/tmp/wp-cli.phar` when
available so it can pass Local's PHP runtime and MySQL socket explicitly.
Override these only when needed:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP="/path/to/local/php" \
WP_CLI_MYSQL_SOCKET="/path/to/mysql/mysqld.sock" \
composer smoke:wp
```

Composer metadata:

```bash
composer validate --no-check-publish
```

GitHub Actions runs the non-LocalWP gate on push and pull requests:

```bash
composer validate --no-check-publish
composer test:all
composer check:wporg
```

Release preparation for WordPress.org:

```bash
composer prepare:release -- --version <version>
```

SVN sync is a release-only step. Run it as a dry run first, then apply it only
after the release package is verified:

```bash
composer sync:wporg -- --version <version> --svn-dir /path/to/wporg-npcink-governance-core
composer sync:wporg -- --version <version> --svn-dir /path/to/wporg-npcink-governance-core --apply
```

## Smoke Test Scope

`composer smoke:wp` verifies:

- plugin activation;
- proposal and audit table creation;
- ability listing from `npcink-abilities-toolkit`;
- the primary `npcink-abilities-toolkit/create-draft` scenario, including discovered schema
  controls and commit preflight without final execution;
- the second `npcink-abilities-toolkit/set-post-seo-meta` scenario, including field-level
  update input and commit preflight without final execution;
- the third `npcink-abilities-toolkit/approve-comment` scenario, including pending comment
  setup, moderation preview input, and commit preflight without final execution;
- proposal creation;
- proposal approval;
- proposal rejection;
- audit REST listing;
- proposal audit timeline, audit filters, app scope-decision attribution, and
  commit-preflight correlation id.

The smoke test deletes its local WordPress content fixtures, including posts,
comments, terms, and media attachments, and revokes app keys created during the
run. It creates local proposal and audit records by default. That is expected.
For local cleanup-only runs, set `NPCINK_GOVERNANCE_CORE_SMOKE_PURGE=1` to purge the
tracked proposal, app-key, rate-limit, and audit rows for the current smoke run
after assertions have completed.

## Adapter Local TLS

Core no longer exports LocalWP TLS switches or OpenClaw handoff text from the
app-key screen. Productized OpenClaw setup, LocalWP `.local` certificate
workarounds, local CA bundle paths, and agent usage instructions belong in
Magick AI Adapter. Core only issues scoped governance app keys.

## Smoke Wrapper

The smoke script is self-contained in this repository. It uses WP-CLI against
the LocalWP site and does not depend on the abandoned legacy Magick AI
repository.

## Git Rules

- Check `git status --short --branch` before editing.
- Stage only files changed by the current task.
- Keep commits small and named with the format:

```text
core: <verb> <short description>
docs: <verb> <short description>
test: <verb> <short description>
```
