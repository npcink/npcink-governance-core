# Npcink Platform Baseline

Status: active for the Core, Abilities, Adapter, and Cloud Addon plugins.

Authority note: Cross-project platform coordination now starts from
`/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md`. This Core
document is retained as a release/consumer baseline note; do not expand it into
the platform coordination source of truth.

## Baseline

All Npcink WordPress plugins in this product group use the same minimum
runtime requirements:

- WordPress minimum: `7.0`
- WordPress tested up to: `7.0`
- PHP minimum: `8.0`
- Composer PHP constraint: `>=8.0` when the plugin has `composer.json`

## Applies To

- `npcink-governance-core`
- `npcink-abilities-toolkit`
- `npcink-ai-client-adapter`
- `npcink-cloud-addon`

## Rules

- Plugin headers, `readme.txt`, `README.md`, Composer constraints, and static
  contract tests must stay aligned with this baseline.
- New Npcink WordPress plugins should inherit this baseline unless a later
  platform decision supersedes this document.
- Do not advertise WordPress 6.x or PHP 7.x compatibility for these four
  plugins after this baseline takes effect.
- The baseline does not change product ownership: Core governs, Abilities
  defines ability packages, Adapter exposes the OpenClaw channel, and Cloud
  Addon connects to hosted services.

## Rationale

The four plugins now form one coordinated WordPress product surface. A shared
WordPress 7.0 and PHP 8.0 baseline keeps release metadata, test expectations,
and support policy consistent while avoiding legacy compatibility work for
WordPress 6.x and PHP 7.x.
