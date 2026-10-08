# Admin Menu Localization Closeout - 2026-06-21

Status: active local handoff note.

## Purpose

This note records the June 21, 2026 shared Npcink AI admin-menu localization
closeout. It explains why the Simplified Chinese sidebar labels changed, which
repositories were touched, what remains pending, and how to debug stale labels
in the LocalWP smoke site.

## Final Simplified Chinese Menu Labels

The English source strings and public product identities remain stable. The
Simplified Chinese runtime display should use these operator-facing labels:

| English source string | Simplified Chinese display | Owner |
| --- | --- | --- |
| `Overview` | `概览` | shared parent owner |
| `Core` | `治理核心` | `npcink-governance-core` |
| `Adapter` | `渠道适配器` | `npcink-ai-client-adapter` |
| `Abilities` | `原子能力` | `npcink-abilities-toolkit` |
| `Toolbox` | `工具箱` | `npcink-toolbox` |
| `Cloud Addon` | `云端扩展` | `npcink-cloud-addon` |

Use the Chinese labels for wp-admin navigation and overview rows. Keep English
product names, plugin slugs, REST namespaces, text domains, ability ids, audit
event names, and database identifiers untranslated.

## Why These Labels Changed

The first Chinese labels were too generic:

- `核心` did not say which core. It is now `治理核心` to match Core's real
  responsibility: proposal records, approval/rejection, commit preflight, app
  keys, and audit evidence.
- `适配器` did not say what kind of adapter. It is now `渠道适配器` so the menu
  describes the thin external-client channel layer without tying the display
  name to only OpenClaw.
- `能力` sounded like a general feature or permission page. It is now
  `原子能力` to describe reusable WordPress Abilities API building blocks.
- `Cloud Addon` was left in English in some bundled language files. It is now
  `云端扩展` when used as a wp-admin menu label, while the product identity
  remains `Npcink Cloud Addon`.

The top-level `Npcink AI` brand remains untranslated.

## Repository Changes

### Core

Core owns the shared overview copy and the Governance submenu. The bundled
Simplified Chinese runtime language file was updated so Core-owned menus and
overview rows use:

- `Core` -> `治理核心`
- `Adapter` -> `渠道适配器`
- `Abilities` -> `原子能力`
- `Cloud Addon` -> `云端扩展`

Committed and pushed to `master`:

- `82df140 Localize shared admin menu labels`
- `5daf93c Localize governance admin menu labels`

Core `composer test:all` passed after the language updates.

### Adapter

Adapter owns the real sidebar label for its submenu. Core can show a translated
overview row, but the selected left-nav item is controlled by Adapter's own text
domain and `.mo` file.

The Adapter localization PR updates:

- `Adapter` -> `渠道适配器`
- `Client Adapter` -> `渠道适配器`
- overview labels for `Core`, `Abilities`, and `Cloud Addon`

Open PR:

- `npcink-ai-client-adapter` PR #13,
  `codex/adapter-admin-localization`
- latest relevant commit: `c0aa29c Fix adapter admin menu label localization`

Adapter `composer test:all` passed. LocalWP verification with
`switch_to_locale( 'zh_CN' )` returned:

```text
Adapter => 渠道适配器
Client Adapter => 渠道适配器
Core => 治理核心
Abilities => 原子能力
Cloud Addon => 云端扩展
```

### Abilities Toolkit

The Abilities Toolkit submenu label now uses `原子能力`.

Open PR:

- `npcink-abilities-toolkit` PR #62,
  `codex/toolkit-admin-menu-localization`
- commit: `ff9677e Localize atomic abilities menu label`

Toolkit `composer test:all` and `composer analyse:phpstan` passed.

### Cloud Addon

Cloud Addon's bundled Simplified Chinese runtime language file now uses:

- `Core` -> `治理核心`
- `Adapter` -> `渠道适配器`
- `Abilities` -> `原子能力`
- `Cloud Addon` -> `云端扩展`

This was pushed onto the existing Cloud Addon PR branch:

- `npcink-cloud-addon` PR #1,
  `codex/github-management-ci`
- latest relevant commit: `ac1263c Localize cloud addon menu labels`

Cloud Addon `composer test:all` passed. Runtime-code forbidden-surface scans
found no PHP matches for the blocked workflow/write markers.

### Toolbox

Toolbox already displayed `工具箱`. No Toolbox menu localization change was
included in this closeout. The Toolbox workspace had unrelated dirty files
during this work; they were intentionally left untouched.

## LocalWP Debugging Notes

If the browser sidebar still shows an old label after the language files are
updated:

1. Confirm the active plugin is symlinked to the expected repository.
2. Confirm the plugin is active.
3. Confirm the loaded text domain returns the expected translation with
   `switch_to_locale( 'zh_CN' )`.
4. Flush the WordPress object cache.
5. Refresh the wp-admin page.

For the Adapter case, the root cause was not Core. The Adapter `zh_CN` file
still translated `Adapter` as `适配器`, so the selected sidebar item kept the
old label until `npcink-ai-client-adapter-zh_CN.po` and `.mo` were fixed.

## Boundary Notes

This localization closeout does not merge ownership between plugins:

- `npcink-governance-core` remains the governance truth.
- `npcink-ai-client-adapter` remains the channel adapter.
- `npcink-abilities-toolkit` remains the atomic ability package.
- `npcink-cloud-addon` remains a Cloud connector.
- `npcink-toolbox` remains the operator-facing toolbox surface.

The top-level `Npcink AI` menu is only a navigation shell. Localized labels
make operator navigation clearer; they do not change runtime authority,
proposal ownership, final write execution, provider configuration, or Cloud
control-plane ownership.

## Follow-Up

- Merge Adapter PR #13 so LocalWP and packaged Adapter releases consistently
  show `渠道适配器`.
- Merge Toolkit PR #62 so the standalone Abilities menu consistently shows
  `原子能力`.
- Merge Cloud Addon PR #1 when its CI-baseline branch is ready.
- Keep future Simplified Chinese menu changes aligned with
  `docs/admin-menu-standard.md` and `docs/translation-glossary-zh.md`.
