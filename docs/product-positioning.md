# Product Positioning

Status: active for the new Npcink Governance Core rebuild.

Npcink Governance Core is the Npcink AI governance layer for WordPress operations: it lets agents,
tools, and product plugins safely request, review, approve, and audit WordPress
changes.

## One-Sentence Positioning

Npcink Governance Core governs AI-assisted WordPress operations.

External explanation: Npcink AI governance layer for WordPress operations.

## Core Jobs

1. Discover available WordPress abilities.
2. Classify ability risk and write requirements.
3. Record proposals for write-like operations.
4. Require explicit host approval before final commits.
5. Audit every requested, approved, rejected, and committed operation.

## Non-Goals

Npcink Governance Core does not own:

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
| `npcink-governance-core` | Governance, proposal records, approval boundaries, audit logs, and host policy. |
| `npcink-abilities-toolkit` | Reference reusable WordPress Abilities API definitions, schemas, callbacks, and dry-run previews. |
| Third-party ability providers | Vendor-scoped WordPress Abilities API definitions, schemas, callbacks, dry-run previews, and final execution outside Core. |
| `npcink-content-assistant` | Article, media, and comment product UX that consumes Core governance. |
| Toolbox or market-specific product plugins | Domestic environment fixes, search/index operations, WeChat integrations, CDN/storage workflows, compliance helpers, and other product UX that consumes Core governance. |
| Connector plugins | Provider credentials and WordPress AI Client provider registration. |

See [Strategy And Product Split](strategy-and-product-split.md) for the
current planning conclusion from the WordPress 7.0 and China-market research.
See
[ADR-004: Suite Consolidation And Local Admin Consent](decisions/ADR-004-suite-consolidation-and-local-admin-consent.md)
for the product packaging direction: Core, Adapter, and Toolbox may be shipped
through one Npcink AI plugin or suite entry, but proposal, approval,
commit-preflight, and audit truth must remain a distinct Governance module
boundary.

## Design Rule

If a feature helps decide whether an AI operation may safely change WordPress,
it may belong in Core.

If a feature creates content, chooses a model, schedules batch work, or owns a
domain workflow, it belongs outside Core.

If a WordPress administrator is present, sees one bounded AI-generated result,
and intentionally applies a low-risk single-object change from the admin UI,
the product surface may use local admin consent with audit instead of a Core
proposal. External, automated, batch, destructive, high-impact, or
insufficiently previewed writes still require Core governance review.

The default first-party provider is `npcink-abilities-toolkit`, but Core should
stay provider-neutral at the base proposal layer: any currently discoverable
WordPress Abilities API provider can submit real write or destructive
`ability_id` targets for Core governance. Provider-specific plan fan-out is
only supported after an explicit Core contract documents the plan shape and
bounds.
