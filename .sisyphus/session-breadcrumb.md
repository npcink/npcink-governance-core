# Session Breadcrumb

## 2026-06-02 — Article writing plan governance handoff started

- **Module**: Core plan-to-proposal article writing handoff.
- **Status**: Core now recognizes the P0 Toolbox
  `magick-ai-toolbox/build-article-write-plan` handoff as a governed
  single-draft proposal source.
- **Completed**:
  - Added `docs/article-writing-workflow-contract.md` to freeze the cross-plugin
    ownership split for Toolbox, Abilities, Core, Adapter, and Cloud Addon.
  - Extended `Plan_Proposal_Service` to accept the Toolbox article plan only
    when it is a direct-read `article_write_plan` with required workflow
    artifacts, passing risk report, no blocked claims, and exactly one
    draft-only `magick-ai/create-draft` action.
  - Preserved article workflow artifacts under proposal
    `preview.article_workflow` for Core review without adding generation,
    execution, workflow runtime, queueing, or Cloud control-plane behavior.
  - Updated REST, governance, ability-intake, plan-to-proposal, README, and
    next-stage docs plus static/fail-closed contracts.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Next Steps**:
  - Implement the Toolbox-side `magick-ai-toolbox/build-article-write-plan`
    ability and operator flow artifacts.
  - Then expose the OpenClaw relay/execute path through Adapter using the
    existing Core preflight and allowlisted `magick-ai/create-draft` execution.

## 2026-06-02 — Development approval policy modes enabled

- **Module**: Core approval policy evaluator and admin governance setting.
- **Status**: Core now supports `manual`, `dry_run_guarded`, and
  `local_guarded` policy modes while keeping final execution outside Core.
- **Completed**:
  - Added the `magick_ai_core_approval_policy_mode` option with a lightweight
    `Development Approval Policy` admin disclosure.
  - Implemented `dry_run_guarded` candidate recording for trusted
    `build-test-content-cleanup-plan` batch proposals that only target
    `magick-ai/trash-post`.
  - Implemented `local_guarded` auto approval for the same cleanup batch shape
    when test-content evidence, app/admin authorization, quota, and audit pass.
  - Added `proposal.auto_approved` audit, fail-closed rollback for failed auto
    approval audit, hourly/daily auto-approval quotas, and app scope context
    checks across all app key scopes.
  - Preserved plan preview evidence in batch proposal previews so the policy
    evaluator does not trust caller claims alone.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - Default mode remains `manual`. `local_guarded` does not auto-approve media
    deletion, post deletion, terms, comments, replies, create-draft, or
    published-content updates. Adapter remains thin and still executes only
    approved proposals after Core commit preflight.

## 2026-06-02 — Smoke fixture cleanup hardened

- **Module**: Core WordPress smoke gate fixture lifecycle.
- **Status**: WordPress smoke now cleans transient local fixtures while keeping
  governance evidence persistent by default.
- **Completed**:
  - Expanded smoke shutdown cleanup from media-only to posts, comments, terms,
    and attachments, with per-run fixture titles and explicit end-of-run
    deletion assertions.
  - Added app-key fixture tracking so smoke-created app keys are revoked even
    when later assertions fail.
  - Added optional `MAGICK_AI_CORE_SMOKE_PURGE=1` local cleanup mode for tracked
    proposal, app-key, rate-limit, and audit rows after assertions complete.
  - Documented the default persistence boundary and added static contracts for
    fixture cleanup and optional purge behavior.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `MAGICK_AI_CORE_SMOKE_PURGE=1 composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - Core still records real proposal and audit rows by default; the purge path
    is explicit local smoke cleanup only and does not change runtime governance
    behavior.

## 2026-06-02 — Approval policy standard and roadmap documented

- **Module**: Core approval policy documentation.
- **Status**: The observation-only evaluator, future guarded candidate path,
  and auto-approval stop conditions are now captured in a dedicated standard
  for future AI development sessions.
- **Completed**:
  - Added `docs/approval-policy-evaluator-standard.md` with evaluator
    boundaries, decision shape, storage/audit expectations, spam guardrails,
    candidate scenarios, non-candidates, implementation phases, and test gates.
  - Linked the standard from README, governance contract, security model, and
    next-stage plan.
  - Added static contracts so future edits keep the policy boundary and staged
    roadmap visible.
- **Next Steps**:
  - If policy work continues, implement only Phase 1 dry-run `guarded`
    candidate evaluation for trusted test cleanup trash batches while keeping
    proposal status `pending`.

## 2026-06-02 — Media delete smoke aligned with abilities policy

- **Module**: Core plan-to-proposal smoke coverage and contracts.
- **Status**: WordPress smoke now matches the current media planning ability
  contract before exercising Core's separate destructive media delete gate.
- **Completed**:
  - Updated the explicit media delete smoke fixture to pass the abilities-side
    `include_unattached_test_media=true` opt-in before expecting a
    `magick-ai/delete-media-permanently` plan action.
  - Documented that Core's `plan_input.include_delete_candidates=true` gate is
    in addition to the media planning ability's own narrow destructive-media
    flags, such as `include_unattached_test_media` and
    `include_trash_parent_media`.
  - Added static contracts so the smoke fixture and plan-to-proposal docs keep
    this two-layer guard visible.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Next Steps**:
  - Keep the approval policy evaluator observation-only unless the next slice
    adds explicit auto-approval authorization, trusted test-content evidence,
    and per-window auto-approval quotas.

## 2026-06-02 — Local proposal observability hook documented

- **Module**: Core proposal REST operability.
- **Status**: Proposal create, plan intake, approve, reject, and
  commit-preflight REST operations emit metadata-only local observability
  events.
- **Completed**:
  - Added the `Observability` bridge for local
    `magick_ai_observability_event` actions.
  - Emitted safe operation metadata from proposal REST callbacks, including
    status, error code, latency, proposal id, ability id, and correlation id
    when available.
  - Documented the hook as optional local operational detail, not an audit
    replacement, Cloud transport client, remote telemetry requirement, or
    second governance truth.
  - Added static contracts for the bridge, event kinds, and operability
    boundary text.
- **Verification**:
  - `composer test:all`
- **Boundary**:
  - Local audit remains the governance record. Cloud Addon may listen locally,
    but Core does not call Cloud, ship logs remotely, execute abilities, own a
    workflow runtime, or move proposal/approval/preflight truth out of Core.

## 2026-06-02 — Observation-only approval policy evaluator added

- **Module**: Core proposal creation governance.
- **Status**: Proposal creation now evaluates and records a lightweight
  approval policy decision while keeping every proposal manual by default.
- **Completed**:
  - Added a hardcoded `Approval_Policy_Evaluator` skeleton with reserved
    decisions `manual_required`, `auto_approved`, and `blocked`.
  - Stored non-secret `caller.core_policy` metadata and promoted
    `policy_decision`, `policy_profile`, `policy_version`, and
    `policy_reasons` into proposal responses.
  - Recorded `proposal.policy_evaluated` on successful proposal creation and
    fail-closed behavior when policy decision audit cannot be written.
  - Documented that the first evaluator is observation-only: no auto approval,
    no rules DSL, no workflow runtime, no scheduler, and no policy UI.
- **Next Steps**:
  - Before enabling real auto approval, persist enough cleanup-plan evidence to
    prove every `trash-post` action targets trusted test content, add explicit
    auto-approval authorization/scope or equivalent app context, and add
    per-window auto-approval quotas.
- **Boundary**:
  - This keeps Core as proposal/approval/preflight/audit truth. Adapter remains
    thin and must still execute only approved proposals that pass preflight.
    The change does not add final WordPress execution, workflow runtime,
    queues, MCP runtime, provider credentials, or a configuration center.

## 2026-06-01 — Review queue proposal trace restored

- **Module**: Core admin governance review queue.
- **Status**: Pending proposal rows now keep `Proposal ID` visible by default
  and show compact source metadata when Adapter/OpenClaw caller context exists.
- **Completed**:
  - Restored a clickable `Proposal ID` in the default `Needs Review` list.
  - Added a compact source trace for plan-to-proposal source, batch id, action
    id, caller type, and app id.
  - Documented that Core must preserve traceability while Adapter owns the
    productized OpenClaw connection and approve-and-execute experience.
- **Boundary**:
  - This is Core admin traceability only. It does not move OpenClaw onboarding,
    client export, adapter transport, final write execution, workflow runtime,
    or MCP runtime into Core.

## 2026-06-01 — Admin bulk rejection for obsolete pending proposals

- **Module**: Core admin governance review queue.
- **Status**: Administrators can now reject selected pending proposals from
  the Review Queue without opening each proposal detail page.
- **Completed**:
  - Added a bounded Review Queue bulk reject form with selected proposal
    checkboxes and a rejection note.
  - Added `admin_post_magick_ai_core_bulk_reject_proposals`, capped at 50
    selected proposal ids per request.
  - Reused `Proposal_Service::reject()` per proposal so existing pending-only,
    TTL, rollback, and `proposal.rejected` audit behavior remains the source
    of truth.
  - Documented the admin-only bulk rejection behavior in Core admin surface and
    operability docs.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - This is an admin governance convenience only. It does not expose Adapter or
    generic REST bulk cancellation, add a cancel-proposal ability, or execute
    WordPress writes.

## 2026-06-01 — Proposal flood guardrails added

- **Module**: Core proposal creation governance.
- **Status**: Core now reduces proposal queue flooding by reusing equivalent
  pending proposals and blocking app-authenticated callers that exceed their
  pending proposal quota.
- **Completed**:
  - Added non-secret `caller.core_guardrails` metadata with a stable input hash
    and quota bucket for proposal creation.
  - Reused an existing pending proposal when the same caller submits the same
    `ability_id` and sanitized `input` again.
  - Added app pending proposal quota enforcement with stable
    `magick_ai_core_pending_proposal_quota_exceeded` HTTP 429 responses.
  - Kept administrator quota intentionally high to avoid blocking local
    governance/admin smoke queues, while app keys remain tightly bounded.
  - Documented the guardrail in REST, security, app-auth, and governance docs.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - This is proposal intake hardening only. It does not add final execution,
    queues, workflow runtime, or proposal cancellation/bulk cancellation.

## 2026-06-01 — Cleanup plans request single batch approval

- **Module**: Abilities cleanup planning output and Core plan-to-proposal
  intake.
- **Status**: `magick-ai/build-test-content-cleanup-plan` now declares
  `proposal_mode=batch` and `batch_approval=true`, and Core honors explicit
  plan-level batch approval flags before falling back to dependency/output
  reference detection.
- **Completed**:
  - Capped cleanup plan `max_actions` at 50 so a single generated batch stays
    within Adapter's existing execution action limit.
  - Preserved explicit batch approval metadata in Core batch proposal previews.
  - Updated Core plan-to-proposal REST/governance docs for explicit batch
    approval semantics and Adapter execution boundary.
  - Added smoke coverage proving two independent cleanup `trash-post` actions
    become one `plan_to_proposal_batch` proposal.
- **Verification**:
  - `composer test:all` in `magick-ai-abilities`
  - `composer smoke:wp` in `magick-ai-abilities`
  - `composer test:all` in `magick-ai-core`
  - `composer smoke:wp` in `magick-ai-core`
- **Boundary**:
  - This changes planning metadata and Core governance intake only. It does not
    add final write execution to Core, change Adapter's executor, add queues,
    or add proposal cancellation/bulk cancellation.

## 2026-06-01 — Core fail-closed fault injection documented and gated

- **Module**: Core governance reliability documentation and test gates.
- **Status**: Current-stage reliability rules are consolidated in a dedicated
  standard, and fail-closed governance persistence now has a standalone
  fault-injection gate wired into `composer test:all`.
- **Completed**:
  - Added the current-stage governance reliability standard covering Core's
    governance-only boundary, app-key scope, deferred rotation/expiry, commit
    preflight binding, and fail-closed rules.
  - Added `tests/fail-closed.php` with an injectable in-memory `$wpdb` that
    exercises real proposal, audit, app-key, and Apps REST classes.
  - Covered proposal insert failure, unaudited proposal creation cleanup,
    approve/reject audit rollback, app-key insert failure, and app creation
    audit failure revocation with stable error-code assertions.
  - Split Composer scripts into `test:contracts` and `test:fail-closed`, with
    `test` and `test:all` running both.
  - Updated README, testing strategy, next-stage plan, and approval handoff
    sample formatting to match the new gate.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `composer validate --no-check-publish`
  - `git diff --check`
- **Boundary**:
  - This adds documentation and tests only. It does not add app-key rotation or
    expiry automation, final execution, `/execute`, `/proxy-execute`, workflow
    runtime, queue, MCP runtime, provider credential storage, cloud control
    plane, or product UX to Core.

## 2026-06-01 — Core governance fail-closed contract hardened

- **Module**: Core WordPress governance contract, REST/app-key/proposal
  persistence, and commit-preflight handoff.
- **Status**: Core now records ADR-003 to keep final WordPress execution
  outside Core for the current stage, aligns REST permission docs with scoped
  app-key behavior, and fails closed when app/proposal persistence or required
  lifecycle audit writes fail.
- **Completed**:
  - Added ADR-003: Core remains governance-only; Adapter/product plugins own
    final WordPress Abilities API execution after Core approval and preflight.
  - Added approved input/preview hashes and policy version to commit-preflight
    approval context and Adapter execution handoff.
  - Made proposal and app-key creation return stable `WP_Error` failures when
    database writes fail.
  - Made proposal creation delete unaudited rows, proposal decisions roll back
    unaudited status changes, and app-key creation revoke keys when creation
    cannot be audited before the one-time token is shown.
  - Updated REST, approval-commit, Agent/MCP, next-stage, README, testing, static
    contract, and WordPress smoke coverage for the hardened boundary.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - This hardens governance persistence, audit guarantees, and Adapter handoff
    only. No `/execute`, `/proxy-execute`, final commit route, workflow runtime,
    queue, MCP runtime, provider credential storage, cloud control plane, or
    product UX was added to Core.

## 2026-06-01 — Core admin long lists paginated

- **Module**: Core WordPress admin governance surface.
- **Status**: `Magick AI -> Core` now keeps long review, archive, audit, and
  advanced app-key lists paginated, and Core app-key management no longer
  appears as a first-level Core tab.
- **Completed**:
  - Added repository offset/count support for proposal, audit, and app-key
    admin lists.
  - Paginated the default review queue, `Expired / Archived`, full governance
    audit, and advanced app-key management surfaces.
  - Added status filtering to the expired/archive list.
  - Moved Core app-key management behind the default page's collapsed
    `Advanced Access` entry while preserving creation, one-time token display,
    disable action, scoped app auth, REST routes, and audit attribution.
  - Updated admin-surface, operability, app-auth, next-stage, README, and
    static contracts for the reduced first-level admin hierarchy.
- **Boundary**:
  - This is admin hierarchy and list ergonomics only. Core app-auth remains a
    governance credential fallback for trusted external clients; no Adapter,
    OpenClaw onboarding, workflow runtime, queue, provider credential, cloud
    control plane, or final WordPress write execution was added.

## 2026-05-31 — Core admin lifecycle cleanup shipped

- **Module**: Core WordPress admin governance lifecycle and surface.
- **Status**: `Magick AI -> Core` now keeps stale proposals out of the active
  review queue, gives expired/archived records a dedicated tab, and removes
  placeholder-heavy audit columns from the default operator path.
- **Completed**:
  - Added a pending proposal TTL that lazily expires stale review items before
    admin and REST reads.
  - Added expired/archive counts, an `Expired / Archived` tab, archive action
    for expired proposals, and reopen action for expired or archived proposals.
  - Tightened proposal detail hierarchy with status, age, expiry, lifecycle
    controls, and pending-only approve/reject controls.
  - Hid low-value read/list audit events by default and collapsed optional
    app, scope, and correlation metadata into a compact detail cell.
  - Updated lifecycle, REST, schema, operability, admin-surface, static, and
    WordPress smoke contracts.
- **Boundary**:
  - This remains Core governance lifecycle and admin display work only. It
    records and curates proposal state; it does not execute abilities, run a
    workflow/queue runtime, own Adapter/OpenClaw setup, store provider
    credentials, manage prompt/preset policy, or perform final WordPress write
    execution.

## 2026-05-31 — WordPress.org release preparation tightened

- **Module**: Cross-plugin WordPress.org listing and release packaging.
- **Status**: Core, Abilities, Adapter, and Cloud Addon listing metadata now
  use the shared `magick-ai` contributor slug, Cloud Addon has explicit
  external-service disclosure, and Core/Cloud Addon have repeatable release
  package and Plugin Check scripts.
- **Completed**:
  - Updated Core contributor metadata and added release package / Plugin Check
    Composer scripts.
  - Updated Abilities listing drafts from the stale WordPress 6.9 / PHP 7.2
    wording to the shared WordPress 7.0 / PHP 8.0 baseline.
  - Updated Cloud Addon contributor/author metadata, external-service
    disclosure, release packaging metadata, language folder, and Plugin Check
    hygiene.
  - Replaced the Cloud Addon listing assets' draft local WordPress mark with a
    generic local-site icon.
- **Boundary**:
  - This is release metadata, listing copy, package hygiene, and asset work.
    Core remains governance truth, Abilities remains the ability package
    provider, Adapter remains the OpenClaw channel, and Cloud Addon remains a
    thin service connector rather than a second control plane.

## 2026-05-31 — Core admin surface switched to tabbed review IA

- **Module**: Core WordPress admin governance surface
- **Status**: `Magick AI -> Core` now uses focused tabs for `Review Queue`,
  `Governance Audit`, and `Core App Keys`; the default view emphasizes the
  pending proposal queue with a compact status strip.
- **Completed**:
  - Added WordPress admin tabs for queue, audit, and app-key surfaces.
  - Removed the default inline Administration entry table.
  - Collapsed recent activity behind an explicit disclosure on the default
    review queue.
  - Collapsed Core app-key creation behind an explicit disclosure on the
    app-key tab while keeping recent keys visible.
  - Updated the admin surface standard and static contracts for the new
    hierarchy.
- **Boundary**:
  - This is admin information architecture only. Core still owns governance
    proposal records, approval/rejection, commit preflight, audit evidence, and
    fallback app-key management. It still does not own Adapter/OpenClaw
    product setup, ability execution, workflow runtime, provider credentials,
    prompt/preset management, or final WordPress write execution.

## 2026-05-31 — Cloud Addon submenu slug and label standardized

- **Module**: Cross-plugin WordPress admin navigation
- **Status**: Cloud Addon now uses the canonical admin page slug
  `magick-ai-cloud-addon`, displays as `Cloud Addon` under `Magick AI`, and
  keeps the page title `Magick AI Cloud Addon`.
- **Completed**:
  - Updated Core's shared admin menu standard and overview link for the Cloud
    Addon surface.
  - Updated Cloud Addon's submenu registration, direct page slug, page title,
    overview row, and user-facing admin path docs.
  - Updated Adapter's shared overview link so it opens the new Cloud Addon
    slug.
- **Boundary**:
  - This is navigation/copy only. Cloud Addon remains a thin connector for
    Cloud Base URL/API key entry, signed verification, local connection state,
    and read-only entitlement summary. It does not add billing, routing,
    prompt/preset, queue, workflow, approval, proposal, or WordPress write
    ownership.

## 2026-05-31 — Core admin workbench split into focused views

- **Module**: Core WordPress admin governance surface
- **Status**: `Magick AI -> Core` now defaults to a compact governance
  review workbench instead of one long mixed page.
- **Completed**:
  - Replaced the default summary table with a compact status strip.
  - Kept the main default task focused on pending proposal review.
  - Reduced default audit exposure to a short recent activity table with an
    explicit full `Governance Audit` entry.
  - Moved Core app-key creation and revocation into a dedicated `Core App
    Keys` view.
  - Made proposal detail a focused detail view and moved the approve/reject
    decision form directly after review context.
- **Boundary**:
  - This is admin information architecture only. Core still owns governance
    proposal records, approval/rejection, commit preflight, audit evidence, and
    fallback app-key management. It still does not execute abilities, run
    workflow runtime, own productized OpenClaw setup, store provider
    credentials, or provide Content Assistant product UX.

## 2026-05-31 — Abilities and Cloud submenu labels standardized

- **Module**: Cross-plugin WordPress admin navigation
- **Status**: Abilities now uses the canonical `magick-ai-abilities` admin
  slug and displays as `Abilities`; Cloud displays as `Cloud` and is ordered
  below Abilities in the shared `Magick AI` menu.
- **Completed**:
  - Updated Core's shared admin menu standard to list `Core`, `Adapter`,
    `Abilities`, and `Cloud` in stable priority order.
  - Updated Core's shared overview links to point at `magick-ai-abilities` and
    `magick-ai-cloud`.
  - Coordinated with `magick-ai-abilities`, `magick-ai-cloud-addon`, and
    `magick-ai-adapter` so their shared overview rows use the same labels and
    slugs.
- **Boundary**:
  - This is navigation/copy only. Core remains governance truth, Abilities
    remains the WordPress Abilities API package provider, Cloud Addon remains a
    thin cloud connector, and Adapter remains the OpenClaw channel layer.

## 2026-05-31 — Adapter submenu slug and label standardized

- **Module**: Cross-plugin WordPress admin navigation
- **Status**: Adapter now uses the canonical admin page slug
  `magick-ai-adapter`, displays as `Adapter` under `Magick AI`, and keeps the
  page title `Magick AI Adapter`.
- **Completed**:
  - Updated Core's shared admin menu standard from
    `Magick AI -> OpenClaw Connection` to `Magick AI -> Adapter`.
  - Updated Core's shared overview link to point to `magick-ai-adapter`.
  - Coordinated with `magick-ai-adapter` and `magick-ai-cloud-addon` so shared
    overview rows no longer point at the old `magick-ai-adapter-openclaw`
    slug.
- **Boundary**:
  - This is navigation/copy only. Adapter remains the OpenClaw channel layer,
    Core remains governance truth, and no REST route, ability definition,
    cloud connector, workflow runtime, provider credential, or final write
    policy changed.

## 2026-05-31 — Core submenu label shortened

- **Module**: Core WordPress admin navigation
- **Status**: The shared `Magick AI` admin submenu for `magick-ai-core` now
  displays as `Core` instead of `Governance`.
- **Completed**:
  - Changed the Core submenu title and overview row label to `Core`.
  - Updated current user-facing admin path documentation from
    `Magick AI -> Governance` to `Magick AI -> Core`.
  - Tightened the static contract so the submenu label and current admin path
    docs do not drift back.
- **Boundary**:
  - This is navigation/copy only. Core remains the governance authority and no
    REST routes, database schema, ability execution, workflow runtime, provider
    credentials, or OpenClaw adapter ownership changed.

## 2026-05-31 — Shared platform baseline raised

- **Module**: Cross-plugin release/runtime metadata
- **Status**: Core, Abilities, Adapter, and Cloud Addon now share a WordPress
  7.0 / PHP 8.0 minimum runtime baseline.
- **Completed**:
  - Added `docs/platform-baseline.md` as the local standard for WordPress and
    PHP minimum requirements across the four plugins.
  - Updated Core plugin header, Composer PHP constraint, `README.md`,
    `readme.txt`, and static contracts.
  - Updated Abilities plugin header, Composer PHP constraint, `README.md`,
    `readme.txt`, demo plugin header, and static contracts.
  - Updated Adapter plugin header, Composer PHP constraint, `readme.txt`,
    OpenClaw contract docs, and static contracts.
  - Updated Cloud Addon plugin header and `readme.txt`.
- **Boundary**:
  - This is release/runtime metadata only. It does not change Core governance,
    Abilities definitions, Adapter channel behavior, Cloud Addon connector
    scope, REST surfaces, approval policy, or write execution ownership.

## 2026-05-31 — Shared Magick AI admin menu standardized

- **Module**: Cross-plugin WordPress admin navigation
- **Status**: Core, Adapter, Cloud Addon, and Abilities now share a single
  `Magick AI` top-level admin menu while preserving their independent runtime
  and product boundaries.
- **Completed**:
  - Added `docs/admin-menu-standard.md` as the local standard for the shared
    parent menu, submenu names, order, and boundary rules.
  - Moved Core governance from `Tools -> Magick AI Core` to
    `Magick AI -> Governance`.
  - Moved Adapter OpenClaw handoff from `Settings -> OpenClaw Connection` to
    `Magick AI -> OpenClaw Connection`.
  - Moved Cloud Addon settings from `Settings -> Magick AI Cloud` to
    `Magick AI -> Cloud Connection`.
  - Made `magick-ai-abilities` attach to `Magick AI -> Ability Packages` when
    the shared parent exists, while retaining the standalone
    `Tools -> Abilities API Packages` fallback.
- **Boundary**:
  - The menu is navigation only. Core remains governance authority, Adapter
    remains OpenClaw channel, Cloud Addon remains a thin cloud connector, and
    Abilities remains an independent WordPress Abilities API package plugin.

## 2026-05-31 — Plugin Check release blockers reduced

- **Module**: Core package metadata / release quality gate
- **Status**: Plugin Check error-class blockers were reduced without
  changing Core's governance/runtime boundary.
- **Completed**:
  - Removed the placeholder `example.com` Plugin URI from the plugin header.
  - Added WordPress.org-format `readme.txt` metadata for release scans.
  - Added `.distignore` so development docs, examples, tests, and local agent
    files are excluded from release packaging.
  - Marked custom-table SQL reads with narrow PHPCS explanations where table
    names are generated from the WordPress prefix and values still use
    placeholders.
  - Sanitized app-key scope POST values before repository validation.
- **Boundary**:
  - This work is packaging, static quality, and input-hardening only. It does
    not add ability execution, final commit execution, MCP runtime, workflow
    runtime, provider credentials, or Adapter-owned OpenClaw onboarding.

## 2026-05-31 — Core admin surface simplified for Adapter ownership

- **Module**: Core WordPress admin governance surface
- **Status**: The Tools page is now optimized as a governance fallback instead
  of an OpenClaw setup or adapter onboarding screen.
- **Completed**:
  - Kept default view focused on governance summary, pending proposals, and
    recent governance audit.
  - Folded Core App Keys into an advanced disclosure and removed the default
    env-template display from the main page.
  - Folded detailed audit filters into an advanced disclosure.
  - Changed proposal detail to show review context first and keep raw
    caller/input/preview JSON behind a disclosure.
- **Boundary**:
  - Core still owns governance approval, app-key fallback management, and audit
    evidence. Magick AI Adapter owns productized OpenClaw setup, client
    handoff, and everyday approve-and-execute UX.

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
