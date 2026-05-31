# Session Breadcrumb

## 2026-05-31 — Trusted Adapter approval handoff clarified

- **Module**: Core app auth / Adapter approve-and-execute support
- **Status**: Core now documents and tests the trusted Adapter path for a
  single user-facing approve-and-execute flow without adding Core execution.
- **Completed**:
  - Documented that generic MCP keys still exclude `proposals:approve`, while
    a productized trusted Adapter may receive a separate approval-capable key.
  - Added commit-preflight `execution_handoff` guidance so Adapter knows final
    execution belongs after Core preflight and outside Core.
  - Added smoke coverage for trusted app-key approval, app-key preflight, and
    approval/preflight audit attribution.
- **Boundary**:
  - Core still does not expose approve-and-execute, execute target abilities,
    proxy WordPress Abilities API, run workflow runtime, or perform final
    commits.

## 2026-05-31 — Plan-to-proposal destructive gate hardened

- **Module**: Core governance intake / plan action safety
- **Status**: Plan-to-proposal intake now validates action-level approval and
  execution flags before creating proposals.
- **Completed**:
  - Rejects plan `write_action` rows that do not declare
    `requires_approval=true`.
  - Rejects plan `write_action` rows that claim `commit_execution=true`.
  - Trusts only request `plan_input.include_delete_candidates=true` for
    permanent media delete proposal creation; a flag embedded in the plan
    payload does not open the destructive gate.
  - Added smoke coverage and documentation for those safety checks.
- **Boundary**:
  - Core still creates governance proposals only. It does not execute plans,
    write abilities, destructive media deletion, workflow runtime, MCP runtime,
    or final commits.

## 2026-05-30 — Plan-to-proposal governance bridge added

- **Module**: Core governance intake / planning ability bridge
- **Status**: Core now accepts supported read-only plan outputs and converts
  `write_actions` into pending Core proposals without executing abilities or
  final WordPress mutations.
- **Completed**:
  - Added `POST /wp-json/magick-ai-core/v1/proposals/from-plan`.
  - Added `Plan_Proposal_Service` for content inventory fix, test content
    cleanup, and media inventory fix plan outputs.
  - Preserved `before`, `after_suggestion`, `reason`, `risk`,
    `required_scopes`, `manual_review`, `skipped_destructive_candidates`,
    `dry_run=true`, `commit=false`, and `commit_execution=false` in proposal
    previews.
  - Added commit-preflight item readiness checks so unresolved
    `requires_input` actions remain reviewable but not committable.
  - Added the destructive media delete guard requiring explicit
    `include_delete_candidates=true` before a permanent delete proposal can be
    generated.
- **Boundary**:
  - Core still does not run planning abilities, execute target write abilities,
    add workflow runtime, add MCP runtime, proxy execution, or perform final
    commit execution.

## 2026-05-30 — AI provider log correlation contract documented

- **Module**: Core / Adapter observability handoff
- **Status**: Core now documents how provider request logs should correlate
  with Core governance audit without moving provider execution or AI Request
  Logs into Core.
- **Completed**:
  - Added an AI provider log correlation contract based on the local Ollama
    `qwen3.5:0.8b` proof.
  - Documented the required Adapter-injected context fields:
    `proposal_id`, `correlation_id`, `ability_id`, `adapter_request_id`,
    `adapter_route`, `ai_provider`, `ai_model`, and
    `governance_source=magick-ai-core`.
  - Updated README, governance operability, next-stage planning, testing
    strategy, and static contracts to keep productized validation in Magick AI
    Adapter.
- **Boundary**:
  - Core still does not execute AI provider calls, store provider credentials,
    log prompts/responses/tokens, merge AI Request Logs, add proxy execution,
    or perform final WordPress mutation.

## 2026-05-30 — OpenClaw acceptance moved to Adapter

- **Module**: Core / Adapter handoff documentation
- **Status**: Productized OpenClaw acceptance now belongs to Magick AI Adapter,
  with Core only cross-referencing the acceptance checklist.
- **Completed**:
  - Pushed `magick-ai-abilities` 0.5 readiness commit so Core/Adapter
    acceptance does not depend on an unpublished local ability contract.
  - Added an Adapter-side OpenClaw consumer acceptance checklist covering
    health, help, capabilities, direct reads, diagnostics reads, proposal
    status polling, Core admin approval/rejection, commit preflight, and
    log correlation.
  - Updated Core planning docs to point productized OpenClaw clients to
    Adapter instead of recreating onboarding in Core.
- **Boundary**:
  - Core remains the governance authority behind Adapter. This session does
    not add final commit execution, Core proxy execution, Adapter approval
    proxying, MCP runtime, workflow runtime, or product workflow ownership.

## 2026-05-30 — Governance audit admin view added

- **Module**: Core governance audit / operator review
- **Status**: Core now has a clearer admin-side governance audit view without
  merging with AI Request Logs.
- **Completed**:
  - Added a `Core Governance Audit` section to `Tools -> Magick AI Core`.
  - Added admin filters for proposal id, event name, ability id, app id,
    caller type, correlation id, and limit.
  - Documented that AI Request Logs remain owned by the WordPress `ai` plugin
    and should be correlated with Core audit through `proposal_id` or
    `correlation_id`.
- **Boundary**:
  - Core still stores governance audit only. It does not log provider/model
    requests, tokens, prompts, responses, final execution, or workflow runtime.

## 2026-05-30 — Adapter proposal status bridge boundary documented

- **Module**: OpenClaw Adapter governance handoff / proposal status bridge
- **Status**: Core guidance now distinguishes adapter proposal status reads
  from approval proxying.
- **Completed**:
  - Documented that productized OpenClaw should use Magick AI Adapter for
    proposal status polling through adapter-owned `GET /proposals` and
    `GET /proposals/{proposal_id}` routes that forward to Core with
    `proposals:read`.
  - Reaffirmed that Adapter should not expose approve/reject by default;
    approval remains in Core/WordPress admin unless a separate trusted host
    approval policy is accepted.
  - Updated Agent/MCP call flow and next-stage planning to reflect the status
    bridge.
- **Boundary**:
  - Core routes already exist; this session did not add execution, MCP runtime,
    final write mutation, or Adapter implementation code.

## 2026-05-30 — Core app-key screen trimmed for Adapter ownership

- **Module**: admin app-key surface / Adapter boundary
- **Status**: Core app-key UI is now a credential management surface, not an
  OpenClaw onboarding or handoff surface.
- **Completed**:
  - Renamed the direct access area to Core App Keys.
  - Removed Direct Core Handoff textarea, Agent rules, OpenClaw example
    commands, Adapter REST URL export, and LocalWP TLS export checkbox from
    Core admin.
  - Kept scoped app-key creation, one-time token display, minimal Core env,
    rate policy, scope selection, and key disable action.
  - Updated README, security, app-auth, development workflow, next-stage plan,
    and static contracts to state that OpenClaw setup, local TLS switches, and
    handoff instructions belong in Magick AI Adapter.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
  - `git diff --check` passed.
- **Boundary**:
  - Core still owns app identity, scopes, rate policy, and audit attribution.
    Adapter owns productized OpenClaw onboarding and client-side runtime
    configuration.

## 2026-05-30 — Governance operability baseline added

- **Module**: Core governance operability / audit traceability
- **Status**: Core now has a minimal operational review layer around the
  existing proposal, approval, and commit-preflight loop.
- **Completed**:
  - Added proposal `audit_timeline` to proposal detail REST responses.
  - Added admin proposal detail capability summary and audit timeline.
  - Added audit filters for `ability_id`, `app_id`, `key_id`, `caller_type`,
    and `correlation_id`.
  - Added app `scope_decision` attribution for allowed, denied, and
    rate-limited app-authenticated requests.
  - Added commit-preflight `correlation_id` in the response, approval context,
    and `commit.preflighted` audit metadata.
  - Added the Core Governance Operability handoff document and synchronized
    REST, governance, security, app auth, schema, workflow, and testing docs.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
  - `git diff --check` passed.
- **Boundary**:
  - Core still does not execute abilities, add proxy execution, implement MCP
    runtime, add workflow runtime, or perform final WordPress mutation.

## 2026-05-30 — Taxonomy terms preview governance proof added

- **Module**: consumer-side governance loop / taxonomy terms preview handoff
- **Status**: Core now proves consumption of
  `magick-ai/propose-post-taxonomy-terms` as a direct-read helper that feeds a
  governed `magick-ai/set-post-terms` proposal.
- **Completed**:
  - Added a dedicated taxonomy terms preview scenario document.
  - Added `create-taxonomy-terms-proposal` to the OpenClaw governance adapter
    example. It validates both capability rows, consumes helper output or
    resolved existing terms, and creates a dry-run `set-post-terms` proposal.
  - Extended WordPress smoke to run the preview helper through WordPress
    Abilities API, create and approve the Core proposal, run commit preflight,
    verify post terms are not mutated, and correlate audit events by proposal.
  - Updated README, intake, readiness, next-stage, testing, admin handoff, and
    static contracts.
- **Verified**:
  - `git diff --check` passed.
  - `composer test:all` passed.
  - `php examples/openclaw-governance-adapter/openclaw-governance-adapter.php --help`
    passed.
  - `composer smoke:wp` passed.
- **Boundary**:
  - Core still does not execute the preview helper, assign taxonomy terms,
    create missing terms, implement MCP runtime, or execute final commits.

## 2026-05-30 — Direct Core access wording clarified

- **Module**: admin direct app access / OpenClaw Adapter positioning
- **Status**: Core admin copy now frames app-key export as Direct Core
  Governance Access instead of an OpenClaw product setup surface.
- **Completed**:
  - Renamed the visible access section, handoff labels, generated result page,
    and default app label to direct Core governance wording.
  - Added an explicit Magick AI Adapter pointer for productized OpenClaw setup.
  - Updated README, app-auth, security, development workflow, next-stage plan,
    and static contracts to match the revised positioning.
- **Boundary**:
  - Core still issues scoped app keys only for governance routes. OpenClaw
    product entry, channel execution, and adapter UX remain outside Core.

## 2026-05-30 — Governance versus execution boundary explained

- **Module**: OpenClaw execution guidance documentation
- **Status**: The OpenClaw guidance now explains why Core remains a governance
  middle layer instead of combining governance and execution in the same
  runtime.
- **Completed**:
  - Documented the distinct questions answered by governance and execution.
  - Captured the cost and benefit of keeping Core governance-only for now.
  - Added the ADR conditions required before Core execution can be reconsidered.
- **Boundary**:
  - Core still returns `core_proxy_execute=false` and
    `commit_execution=false`; no execution route was added.

## 2026-05-30 — OpenClaw execution guidance added

- **Module**: capability intake / OpenClaw governance bridge
- **Status**: Core capability rows now include machine-readable execution
  guidance for OpenClaw and adapter clients without adding proxy execution.
- **Completed**:
  - Added an OpenClaw execution guidance contract document that positions Core
    as the governance bridge, not the execution gateway.
  - Added `governance_mode`, `execution_surface`, `core_proxy_execute=false`,
    and `commit_execution=false` to normalized capability rows.
  - Updated REST, Agent/MCP, next-stage, README, OpenClaw adapter docs, and
    admin handoff text so read abilities route through WordPress Abilities API
    while write/destructive abilities route through Core proposal and preflight.
  - Kept OpenClaw Adapter / Agent Gateway planning outside Core.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
- **Boundary**:
  - Core still does not add `/execute`, `/proxy-execute`, MCP runtime, workflow
    runtime, or final WordPress mutation.

## 2026-05-30 — Core 0.4 consumer readiness documented

- **Module**: consumer readiness documentation / next-stage planning
- **Status**: Core 0.4 consumer readiness is documented as complete for the
  `magick-ai-abilities` 0.4.0 representative scenarios.
- **Completed**:
  - Added a roll-up readiness document covering create-draft,
    set-post-seo-meta, and approve-comment with their commits.
  - Updated README and next-stage planning so future work starts from the
    readiness conclusion instead of redoing the three representative scenarios.
  - Shifted the next decision point to whether final commit execution deserves
    a separate ADR.
- **Verified**:
  - `composer test:all` passed.
  - `php examples/openclaw-governance-adapter/openclaw-governance-adapter.php --help`
    passed.
- **Boundary**:
  - Core remains discovery, proposal, approve/reject, commit preflight, and
    audit. It still does not execute final WordPress mutation.

## 2026-05-30 — Approve-comment governance scenario solidified

- **Module**: consumer-side governance loop / comment moderation proposal
- **Status**: `magick-ai/approve-comment` is now the third solidified Core
  host-governed write scenario.
- **Completed**:
  - Added a dedicated approve-comment scenario document for future humans and AI
    agents.
  - Added `create-comment-approval-proposal` to the OpenClaw example adapter.
    It discovers capabilities first, validates the real
    `magick-ai/approve-comment` contract, requires `comment_id`, includes
    current status and `target_action=approve` in preview, and forces
    `dry_run=true` and `commit=false`.
  - Updated the admin OpenClaw handoff to point at the dedicated comment
    approval adapter path.
  - Hardened WordPress smoke coverage by creating a real pending comment,
    creating the proposal, approving it in Core, running commit preflight, and
    verifying Core did not mutate the comment status.
  - Updated README, intake, handoff validation, workflow, next-stage, testing,
    adapter docs, and static contracts.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
- **Boundary**:
  - Core still only performs discovery, proposal, approve/reject, preflight,
    and audit. It does not execute `magick-ai/approve-comment`, change comment
    status, own comment product UX, or own workflow runtime.

## 2026-05-30 — Set-post-seo-meta governance scenario solidified

- **Module**: consumer-side governance loop / field-level proposal handoff
- **Status**: `magick-ai/set-post-seo-meta` is now the second solidified Core
  host-governed write scenario.
- **Completed**:
  - Pushed the previous six local `master` commits to `origin/master` before
    starting new work.
  - Added a dedicated SEO metadata scenario document for future humans and AI
    agents.
  - Added `create-seo-meta-proposal` to the OpenClaw example adapter. It
    discovers capabilities first, validates the real
    `magick-ai/set-post-seo-meta` contract, requires `post_id` plus at least one
    SEO field, emits a field-level preview patch, and forces `dry_run=true` and
    `commit=false`.
  - Updated the admin OpenClaw handoff to point at the dedicated SEO metadata
    adapter path.
  - Hardened WordPress smoke coverage for SEO schema fields, proposal creation,
    admin approval, and commit preflight without final execution.
  - Updated README, intake, handoff validation, workflow, next-stage, testing,
    adapter docs, and static contracts.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
- **Boundary**:
  - Core still only performs discovery, proposal, approve/reject, preflight,
    and audit. It does not execute `magick-ai/set-post-seo-meta`, choose SEO
    strategy/providers, or own workflow runtime.

## 2026-05-30 — Create-draft governance scenario solidified

- **Module**: consumer-side governance loop / OpenClaw adapter handoff
- **Status**: `magick-ai/create-draft` is now the first solidified Core
  host-governed write scenario.
- **Completed**:
  - Added a dedicated create-draft scenario document for future humans and AI
    agents.
  - Added `create-draft-proposal` to the OpenClaw example adapter. It discovers
    capabilities first, validates the real `magick-ai/create-draft` contract,
    forces `dry_run=true` and `commit=false`, and creates a Core proposal.
  - Updated the admin OpenClaw handoff to point at the dedicated create-draft
    adapter path.
  - Hardened WordPress smoke coverage for create-draft schema controls,
    proposal creation, admin approval, and commit preflight without final
    execution.
  - Updated README, intake, handoff validation, workflow, next-stage, testing,
    adapter docs, and static contracts.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
- **Boundary**:
  - Core still only performs discovery, proposal, approve/reject, preflight,
    and audit. It does not execute `magick-ai/create-draft` or own content
    generation/workflow runtime.

## 2026-05-30 — OpenClaw local TLS handoff option added

- **Module**: external app governance authorization / OpenClaw handoff
- **Status**: App key creation now has an explicit local testing export option
  for OpenClaw TLS config.
- **Completed**:
  - Added an `include_local_tls` checkbox to app-key creation UI, defaulting on
    only for local hosts.
  - Generated `MAGICK_AI_CORE_INSECURE_SSL=true` only when the operator
    explicitly includes local TLS in result env/handoff.
  - Kept placeholder handoff as guidance only and documented that this is a
    client-side adapter setting, not Core server policy.
  - Updated README, app auth policy, security model, development workflow,
    next-stage plan, and static contracts.
- **Verified**:
  - `composer test:all` passed.
  - WP-CLI result-page checks passed for checked and unchecked local TLS export.
  - `composer smoke:wp` passed.

## 2026-05-30 — OpenClaw adapter local TLS switches added

- **Module**: external agent governance adapter example
- **Status**: The OpenClaw example adapter now supports LocalWP `.local`
  self-signed certificate workflows without weakening production defaults.
- **Completed**:
  - Added `MAGICK_AI_CORE_CA_BUNDLE` for a preferred trusted local CA bundle.
  - Added `MAGICK_AI_CORE_INSECURE_SSL=true` for local-only hosts:
    `localhost`, `127.0.0.1`, `::1`, and `.local`.
  - Refused insecure SSL mode for non-local/public hosts.
  - Updated the OpenClaw README, admin handoff text, and static contracts.
- **Verified**:
  - `composer test:all` passed.
  - Local adapter capabilities call passed against `https://magick-ai.local`
    with `MAGICK_AI_CORE_INSECURE_SSL=true`.
  - Non-local insecure SSL mode was rejected for `https://example.com`.
  - `composer smoke:wp` passed.

## 2026-05-30 — OpenClaw handoff and key disable UI added

- **Module**: external app governance authorization
- **Status**: The Core admin external access section now supports practical
  OpenClaw handoff and minimal leaked-token response.
- **Completed**:
  - Added a copyable OpenClaw handoff guide with environment variables, agent
    rules, and example governance commands.
  - Added admin-only app-key disable action backed by `revoked` status.
  - Added `app.revoked` audit event recording for admin disables.
  - Kept approval, final write execution, protocol runtime, and product
    workflow ownership outside Core.
  - Updated docs, static contracts, and WordPress smoke coverage.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed, including revoked-token `401` coverage.

## 2026-05-29 — App-key admin-post result page fixed

- **Module**: external app governance authorization
- **Status**: Fixed the app-key creation result page so it no longer loads the
  full WordPress admin header from `admin-post.php`.
- **Completed**:
  - Confirmed the reported failure was caused by `admin-header.php` firing
    `admin_enqueue_scripts` with a null hook suffix in the `admin-post` context.
  - Replaced the full admin chrome include with a standalone no-cache result
    page that shows the one-time token and OpenClaw env snippet.
  - Added static coverage to prevent reintroducing `admin-header.php` in this
    handler.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed.
  - Local WP-CLI handler simulation returned `App Key Created` and a redacted
    `MAGICK_AI_CORE_APP_TOKEN` without the previous fatal error.

## 2026-05-29 — External app access admin UI added

- **Module**: external app governance authorization
- **Status**: WordPress administrators can now copy Core connection values and
  issue scoped one-time app tokens from `Tools -> Magick AI Core`.
- **Completed**:
  - Added an `External App Access` section to the existing Core admin page.
  - Added a nonce/capability-checked admin-post handler for app-key creation.
  - Reused the Core app identity store, default scopes, and rate-limit policy.
  - Displayed the raw token only on the creation result screen and kept recent
    app-key listings free of secret material.
  - Updated security/app-auth docs and static contracts.
- **Next recommended step**:
  - Use this panel for OpenClaw or similar adapter PoCs. Keep lifecycle features
    narrow until real use requires them: rotation, revoke UI, expiry, and export
    formats can follow later. Do not add OAuth, MCP runtime, or final write
    execution inside Core.

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
