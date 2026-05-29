# Product Positioning

Status: active for the new Magick AI Core rebuild.

Magick AI Core is the WordPress AI operation governance layer: it lets agents,
tools, and product plugins safely request, review, approve, and audit WordPress
changes.

## One-Sentence Positioning

Magick AI Core governs AI-assisted WordPress operations.

## Core Jobs

1. Discover available WordPress abilities.
2. Classify ability risk and write requirements.
3. Record proposals for write-like operations.
4. Require explicit host approval before final commits.
5. Audit every requested, approved, rejected, and committed operation.

## Non-Goals

Magick AI Core does not own:

- content generation products;
- article, media, comment, SEO, or toolbox workflows;
- model routing or provider connector configuration;
- prompt/preset authoring;
- workflow execution engines;
- batch processing consoles;
- cloud billing or operator portals.

## Product Split

| Project | Owns |
| --- | --- |
| `magick-ai-core` | Governance, proposal records, approval boundaries, audit logs, and host policy. |
| `magick-ai-abilities` | Reusable WordPress Abilities API definitions, schemas, callbacks, and dry-run previews. |
| `magick-ai-content-assistant` | Article, media, and comment product UX that consumes Core governance. |
| Connector plugins | Provider credentials and WordPress AI Client provider registration. |

## Design Rule

If a feature helps decide whether an AI operation may safely change WordPress,
it may belong in Core.

If a feature creates content, chooses a model, schedules batch work, or owns a
domain workflow, it belongs outside Core.

