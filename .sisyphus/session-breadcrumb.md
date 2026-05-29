# Session Breadcrumb

## 2026-05-29 — Core and Abilities handoff documented

- **Module**: ability governance handoff documentation
- **Status**: The cross-repository handoff rules between
  `magick-ai-abilities` and Core are documented.
- **Completed**:
  - Added the Abilities-side Core Governance Handoff Guide.
  - Linked Core ability intake to the handoff guide.
  - Clarified that Core proposal, approval, preflight, and audit records use
    real WordPress Abilities API ids, not planning-label aliases such as
    `site/read`.
  - Marked CDN purge preview and site-level backup restore preflight as deferred
    operations/toolbox candidates, not current Core features.
- **Next recommended step**:
  - Keep Core focused on governance hardening. Use the handoff guide when
    selecting first ability-backed proposal scenarios, and do not add runtime
    short-name mapping, workflow routing, CDN execution, or backup execution to
    Core.

## 2026-05-29 — Core governance loop implementation

- **Module**: core governance REST/admin
- **Status**: Proposal detail, audit filters, commit preflight, and minimal
  admin approval UI are implemented.
- **Completed**:
  - Added proposal detail REST route with 404 behavior and viewed/listed audit.
  - Added audit filters for `proposal_id`, `event_name`, and `limit`.
  - Added commit preflight service and REST route that returns approval context
    without executing abilities and rejects legacy confirmation parameters.
  - Expanded Tools -> Magick AI Core with pending proposal review and
    nonce/capability-checked approve/reject forms.
  - Updated REST, governance, approval-commit, security, schema, architecture,
    README, static contracts, and WordPress smoke coverage.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed against the LocalWP `magick-ai` site.
- **Next recommended step**:
  - Design app identity, scope, and rate-limit policy before implementing any
    app-key access. Do not add final commit execution until idempotency and
    failure contracts are documented and tested.

## 2026-05-29 — Strategy and product split documented

- **Module**: product strategy / governance boundary
- **Status**: Documented the planning conclusion from the WordPress 7.0
  research review and the current Core positioning.
- **Completed**:
  - Added `docs/strategy-and-product-split.md`.
  - Linked the strategy guide from `README.md`, product positioning, and the
    next-stage plan.
  - Clarified that Core remains the governance kernel while abilities and
    product plugins own reusable capabilities and market-specific workflows.
- **Next recommended step**:
  - Continue implementation in this order: proposal detail endpoint, audit
    filters, commit preflight, minimal admin approval UI, then app auth/scope
    policy. Keep China-toolbox and content workflows outside Core as product
    plugins that consume Core governance.

## 2026-05-29 — Magick AI Core MVP smoke baseline

- **Module**: core scaffold / governance MVP / WordPress smoke
- **Status**: New `magick-ai-core` plugin is scaffolded and committed on
  `master`.
- **Completed**:
  - Created clean WordPress plugin skeleton with a runtime autoloader.
  - Added product positioning, architecture, ability intake, governance, and
    approval-commit contracts.
  - Implemented read-only ability intake from `magick-ai-abilities` first, then
    WordPress Abilities API fallback.
  - Implemented proposal creation, approve/reject status transitions, and audit
    event records.
  - Added minimal admin overview and REST routes.
  - Added static contracts and real WordPress smoke tests.
- **Verified**:
  - `composer validate --no-check-publish` passed.
  - `composer test:all` passed.
  - `composer smoke:wp` passed against the LocalWP `magick-ai` site.
- **Commits**:
  - `d3fab0a core: scaffold governance plugin mvp`
  - `6dd9efd core: add proposal approval status flow`
  - `7a6403c core: add wordpress smoke test`
- **Next recommended step**:
  - Build the next layer in this order: proposal detail endpoint, audit filters,
    commit preflight contract, minimal admin approval UI, then app auth/scope
    policy. Do not add final commit execution until preflight and idempotency
    contracts are written and tested.
