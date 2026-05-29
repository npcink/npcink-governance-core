# Development Workflow

Status: active.

This project is developed as a standalone WordPress plugin mounted into the
local `magick-ai` WordPress site.

## Local Paths

Project:

```text
/Users/muze/gitee/magick-ai-core
```

Local WordPress root:

```text
/Users/muze/Local Sites/magick-ai/app/public
```

Plugin symlink:

```text
/Users/muze/Local Sites/magick-ai/app/public/wp-content/plugins/magick-ai-core
-> /Users/muze/gitee/magick-ai-core
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

Composer metadata:

```bash
composer validate --no-check-publish
```

## Smoke Test Scope

`composer smoke:wp` verifies:

- plugin activation;
- proposal and audit table creation;
- ability listing from `magick-ai-abilities`;
- proposal creation;
- proposal approval;
- proposal rejection;
- audit REST listing.

The smoke test creates local proposal and audit records. That is expected.

## Wrapper

The smoke script currently uses the existing LocalWP wrapper from
`/Users/muze/gitee/magick-ai-root/scripts/local-wp.sh` and passes this site's
WordPress root explicitly. This keeps the new plugin small while reusing the
known-good LocalWP runtime wrapper.

If this project later becomes fully independent, copy or replace only the
minimal LocalWP wrapper behavior needed for this plugin. Do not import old
Magick AI runtime scripts.

## Git Rules

- Check `git status --short --branch` before editing.
- Stage only files changed by the current task.
- Keep commits small and named with the format:

```text
core: <verb> <short description>
docs: <verb> <short description>
test: <verb> <short description>
```

