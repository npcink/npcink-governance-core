# Session Breadcrumb

## 2026-05-29 — Minimal app-key governance entry implemented

- **Module**: external app governance authorization
- **Status**: Minimal app key, scope, fixed-window rate limit, and audit
  attribution are implemented for Core REST governance routes.
- **Completed**:
  - Added app identity and rate-limit tables.
  - Added admin-only `/apps` REST management for creating one-time bearer
    tokens and listing app identities without secret material.
  - Added scoped app authorization for capabilities, proposals, commit
    preflight, and audit routes.
  - Added default external adapter scopes that exclude approval and audit read.
  - Added request-context attribution in proposal caller payloads and audit
    metadata, plus scope-denied and rate-limited audit events.
  - Updated docs, static contracts, and WordPress smoke coverage.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed against the LocalWP `magick-ai` site.
- **Next recommended step**:
  - Keep app-key lifecycle minimal unless needed by a real adapter: rotation,
    revocation UI, expiry automation, and app-specific admin screens can wait.
    Do not add OAuth, MCP session management, or final write execution here.

## 2026-05-29 — OpenClaw governance adapter example added

- **Module**: external agent governance adapter example
- **Status**: Added a minimal CLI example for OpenClaw-like clients to call
  Core governance REST routes without making Core an MCP runtime.
- **Completed**:
  - Added `examples/openclaw-governance-adapter/` with commands for capability
    discovery, proposal creation, and commit preflight.
  - Kept approval, MCP tools, natural-language routing, workflow runtime, and
    final WordPress ability execution out of the example.
  - Documented WordPress Application Password use through environment variables
    only.
  - Extended static contracts and lint coverage to include the example.
- **Next recommended step**:
  - Use this adapter only for PoC work while Core still requires
    `manage_options`. Production adapter access should wait for app-key scope
    and rate-limit implementation.

## 2026-05-29 — Agent/MCP entry contracts documented

- **Module**: agent and MCP governance entry contracts
- **Status**: The adapter entry and app authorization policies are documented;
  no MCP runtime or app-key implementation was added.
- **Completed**:
  - Added `docs/agent-mcp-entry-contract.md` to define how WordPress/MCP
    adapters consume Core governance without making Core an MCP server,
    channel projection registry, natural-language router, or workflow runtime.
  - Added `docs/app-auth-scope-policy.md` to freeze the future app identity,
    scope, rate-limit, and audit-attribution target before implementation.
  - Updated REST, security, strategy, next-stage, README, and static contract
    coverage to point at the new contracts.
  - Used the root Magick AI channel/cloud contracts as reference material while
    keeping Core independent from the legacy open-platform implementation.
- **Next recommended step**:
  - Implement the smallest app-key/scoped-auth skeleton only after database,
    REST error, and smoke-test contracts are finalized. Keep MCP Adapter outside
    Core.

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
