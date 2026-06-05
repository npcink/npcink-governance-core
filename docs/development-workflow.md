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
/Users/muze/gitee/magick-ai-abilities
```

`magick-ai-abilities` is responsible for reusable WordPress atomic abilities.
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
MAGICK_AI_ABILITIES_PATH="/Users/muze/gitee/magick-ai-abilities" \
composer smoke:wp
```

For LocalWP environments where `wp` is not on `PATH`, the script can run through
`/tmp/wp-cli.phar` and Local's PHP runtime. Override these only when needed:

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

## Smoke Test Scope

`composer smoke:wp` verifies:

- plugin activation;
- proposal and audit table creation;
- ability listing from `magick-ai-abilities`;
- the primary `magick-ai/create-draft` scenario, including discovered schema
  controls and commit preflight without final execution;
- the second `magick-ai/set-post-seo-meta` scenario, including field-level
  update input and commit preflight without final execution;
- the third `magick-ai/approve-comment` scenario, including pending comment
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
For local cleanup-only runs, set `MAGICK_AI_CORE_SMOKE_PURGE=1` to purge the
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
