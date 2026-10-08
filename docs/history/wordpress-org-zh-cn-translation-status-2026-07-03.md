# WordPress.org zh_CN Translation Status - 2026-07-03

Status: active local handoff note.

## Purpose

This note records the Simplified Chinese translation state for
`npcink-governance-core` after checking both the local repository history and
the public WordPress.org translation project on July 3, 2026.

The main distinction is:

- local bundled runtime translations already exist in this repository;
- WordPress.org `zh_CN` translation suggestions have been submitted;
- those public suggestions are not current/approved yet, so they do not publish
  a public `zh_CN` language pack or localized plugin-directory page.

## Local Repository State

Core already keeps a bundled Simplified Chinese runtime baseline:

- `languages/npcink-governance-core-zh_CN.po`
- `languages/npcink-governance-core-zh_CN.mo`
- `languages/npcink-governance-core.pot`
- `docs/translation-glossary-zh.md`

The local history shows the translation track has been maintained over time:

- `dcef708 Add WordPress.org readme translation drafts` added
  `sj/wporg-readme-translations/stable-readme-zh_CN.md` together with the
  other first public-page locale drafts.
- `82df140 Localize shared admin menu labels` and
  `5daf93c Localize governance admin menu labels` aligned Core-owned
  Simplified Chinese admin labels.
- `669c2c4 Add workflow toolbox to Npcink overview` updated the zh_CN runtime
  language pack and glossary for the Workflow Toolbox overview entry.
- `818318b Document admin identity and tab visual closeout` kept the latest
  admin identity and translation closeout discoverable.

At this checkpoint, `git status --short --branch` reported
`master...origin/master` with no local modifications before this note was
added.

## WordPress.org Public Translation State

The public translation project is:

```text
https://translate.wordpress.org/projects/wp-plugins/npcink-governance-core/
```

The Simplified Chinese locale status page is:

```text
https://translate.wordpress.org/locale/zh-cn/default/wp-plugins/npcink-governance-core/
```

The July 3, 2026 public snapshot for `Chinese (China)` / `zh_CN` was:

| Sub-project | Current translated | Waiting/suggested | Untranslated |
| --- | ---: | ---: | ---: |
| Stable (latest release) | 0 | 0 | 681 |
| Stable Readme (latest release) | 0 | 49 | 0 |
| Development (trunk) | 0 | 277 | 404 |
| Development Readme (trunk) | 0 | 0 | 49 |

The project overview showed `Chinese (China)` with `326` Waiting/Fuzzy entries,
which matches `49` Stable Readme suggestions plus `277` Development suggestions.

The contributors page showed:

```text
Chinese (China) #zh_CN
Editors: None
Contributors: Npcink
```

So the correct public status is: translations were submitted as suggestions,
but no Core-specific `zh_CN` Project Translation Editor had approved them at
this checkpoint.

## What Has Been Submitted

The submitted public WordPress.org work appears to cover:

- `Stable Readme`: 49 suggested strings for the plugin directory description,
  installation text, FAQ, and changelog surfaces.
- `Development (trunk)`: 277 suggested runtime strings.

The local source draft for the public plugin-page Simplified Chinese text is:

```text
sj/wporg-readme-translations/stable-readme-zh_CN.md
```

That draft is not loaded by the plugin at runtime. It exists to support manual
submission through translate.wordpress.org.

## What Is Not Done Yet

The following are still pending:

- `zh_CN` suggestions need review by a General Translation Editor or a
  Core-specific Project Translation Editor.
- The `Stable (latest release)` runtime set still shows `0` waiting and `681`
  untranslated, so the latest-release runtime language pack is not approved.
- WordPress.org will generate the initial public language pack only after the
  Stable sub-project reaches its required translation threshold.
- The public plugin page will keep behaving as untranslated until the
  WordPress.org `Stable Readme` strings are approved.

## PTE Request Path

If the maintainer wants to approve and maintain Core Simplified Chinese
translations directly, request Project Translation Editor access for this
specific plugin and locale:

```text
Plugin: Npcink Governance Core
Slug: npcink-governance-core
Locale: #zh_CN
Project: https://translate.wordpress.org/projects/wp-plugins/npcink-governance-core/
```

Post the request on Make WordPress Polyglots and include the plugin project,
locale tag, translation project URL, and the fact that existing `zh_CN`
suggestions are already waiting for review.

Any PTE access for another plugin, such as `npcink-cloud-addon`, is separate
and does not grant approval rights for `npcink-governance-core`.

## Boundary

This translation work does not change Core's product boundary. Core remains
the WordPress AI operation governance layer. It still does not execute final
writes, own product workflows, route models, store provider credentials, own
workflow runtime, own task queues, host MCP runtime, or provide Agent Gateway
catalogs.

