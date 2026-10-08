# Admin Identity And Tab Visual Closeout - 2026-07-02

Status: local cross-repository UI closeout record.

## Context

The Npcink WordPress admin surfaces had drifted in three visible ways:

- product page titles were translated into generic Chinese names in places
  where the plugin identity should stay fixed;
- sidebar/menu labels mixed product names, capability labels, and technical
  page titles;
- tab controls were inconsistent across plugins, with some pages still using
  boxed WordPress `nav-tab` styling and others using newer underline tabs.

The goal of this pass was visual and naming alignment only. It did not change
proposal authority, ability ownership, Cloud runtime ownership, Adapter
channel behavior, workflow execution, billing, credentials, or final
WordPress write paths.

## Decisions

### Keep Product Titles Untranslated

Top-level product page titles should remain fixed English product identities:

- `Npcink Governance Core`
- `Npcink Abilities Toolkit`
- `Npcink AI Client Adapter`
- `Npcink Cloud Addon`

Short navigation labels may still be localized when they identify a module or
task rather than the product brand. For example, the Abilities Toolkit menu
entry under the shared Npcink parent uses `AI Ability Set`, localized as
`AI 能力集`.

### Use One Shared Tab Visual Standard

Core owns the shared visual standard in `docs/admin-surface-standard.md`.
Product-level tabs across the plugin family should use an underline style:

- shared classes: `npcink-ai-tabs`, `npcink-ai-tab`, and
  `npcink-ai-tab-active`;
- active item carries `aria-current="page"` where the tab is a page
  navigation item;
- inactive tabs are muted text without gray boxes or filled backgrounds;
- active and hover/focus affordances use Gutenberg blue `#3858e9`;
- the group keeps a light bottom divider and horizontal scrolling for narrow
  screens.

This intentionally follows the modern Gutenberg tab direction without copying
the centered privacy/settings modal layout into every admin workbench. Local
operator surfaces remain left-aligned because they are working pages, not
modal headers or marketing views.

### Keep Work Surfaces Dense But Not Cramped

After removing boxed tab borders, the Cloud Addon status panel needed explicit
space below the tab row. The tab panel now uses a normal bordered container
with `margin: 16px 0` and `padding: 16px`, so the first section does not touch
the active underline.

The Core shared overview table also received a dedicated class so installed
surface labels are more prominent and row actions stay visually centered.

## Repository Outcomes

### `npcink-governance-core`

- Fixed the Core page title to `Npcink Governance Core`.
- Added the shared tab CSS and migrated Core admin tabs, token subtabs, and
  proposal detail tabs away from boxed `nav-tab` markup.
- Added the installed-surfaces overview table alignment class.
- Recorded the shared tab standard and this closeout note.
- Updated Simplified Chinese runtime translation files and static contracts.

### `npcink-abilities-toolkit`

- Fixed the product title to `Npcink Abilities Toolkit`.
- Changed the shared parent menu label source to `AI Ability Set`, localized
  as `AI 能力集`.
- Migrated the Abilities admin tabs to the shared underline classes.
- Updated documentation, WordPress.org/readme copy, Simplified Chinese
  translation files, and static contracts.

### `npcink-ai-client-adapter`

- Fixed the product title to `Npcink AI Client Adapter`.
- Removed the stale runtime translation path for the old generic Adapter page
  title.
- Updated Simplified Chinese translation files and static contracts.

### `npcink-cloud-addon`

- Kept `Npcink Cloud Addon` as the page title.
- Migrated Cloud Addon settings tabs to the shared underline classes.
- Folded Cloud detail and runtime status surfaces into clearer connector
  status, Site Knowledge, troubleshooting, and connection-management areas
  without adding local control-plane ownership.
- Added 16px vertical panel spacing below the tab row.
- Updated Simplified Chinese translation files and static contracts.

### `npcink-workflow-toolbox`

- Updated the existing top-level tab active/hover color to Gutenberg blue
  `#3858e9` so it visually matches the shared tab standard.
- Did not change Toolbox tab structure in this closeout.

## Boundaries Preserved

This closeout is UI identity, spacing, and tab styling only.

It does not:

- move reusable abilities out of Abilities Toolkit;
- make Core execute abilities or own workflow runtime;
- make Cloud Addon a billing, workflow, proposal, or WordPress write control
  plane;
- make Toolbox own queues, schedulers, final writes, provider billing, or
  approval truth;
- change Adapter execution/channel contracts.

## Verification

The closeout was verified with each repository's focused local gate:

- Core: `composer test:all` and `git diff --check`
- Abilities Toolkit: `composer test:all` and `git diff --check`
- AI Client Adapter: `composer test:all` and `git diff --check`
- Cloud Addon: `composer run test:all`, `git diff --check`, and the forbidden
  pattern scan for `/v1/runtime/workflows/runs|wp_insert_post|wp_update_post`
- Workflow Toolbox: `composer test:all` and `git diff --check`

Future follow-up should keep Core as the documentation source of truth for the
shared admin tab visual standard, while each plugin keeps only the local CSS
needed to render that standard on its own page.
