# Session Breadcrumb

## 2026-06-17 — Runtime contract endpoint exposes Adapter-safe bindings

- **Module**: Core runtime contract endpoint and Core-issued preflight
  contexts.
- **Status**: `/contract` now reports Adapter-facing runtime compatibility,
  Core truth ownership, forbidden payload families, and context binding
  support. Commit preflight and sensitive-read preflight now include
  `site_url`, `home_url`, and `blog_id` in Core-issued contexts.
- **Completed**:
  - Added contract metadata for Adapter compatibility, metadata-only discovery,
    commit preflight availability, sensitive-read preflight availability, and
    pending signed client fingerprint binding.
  - Added site binding fields to `approval_context`, `execution_handoff`, and
    `read_authorization_context`.
  - Updated REST, approval-commit, sensitive-read, and README docs.
  - Added static contract and WordPress smoke coverage for the runtime contract
    endpoint and site-bound preflight contexts.
- **Verification**:
  - `composer test:all`
  - `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" composer smoke:wp`
  - `composer validate --no-check-publish`
  - `git diff --check`
- **Boundary**:
  - Core still does not proxy reads, execute final writes, own Adapter
    execution profiles, own Toolkit ability definitions, own workflow runtime,
    own queues, own MCP runtime, own Agent Gateway catalogs, or store provider
    credentials. Client-key fingerprint binding remains pending until Core
    emits a signed client identity field.

## 2026-06-16 — Block theme profile compiler contract enforced

- **Module**: Plan-to-proposal intake for block theme template layout profiles.
- **Status**: Core now validates `customize_template_layout` plans as
  versioned profile compiler outputs, not only as safe block trees.
- **Completed**:
  - Required `template_layout_contract.compiler_version` or plan
    `compiler_version` to be `block_theme_profile_compiler@0.2`.
  - Required `template_layout_contract.forbidden_policy_version` to be
    `block_theme_safe_core_blocks@0.2`.
  - Required accepted versioned profiles such as `homepage_landing@0.2` and
    per-profile rows with matching profile version, operation, modules, and
    forbidden-output policy.
  - Updated fail-closed fixtures, static contracts, and governance/API docs.
- **Boundary**:
  - Core still does not choose profiles, compile Gutenberg block trees, approve
    proposals, execute writes, modify theme files, edit navigation, patch
    global styles, or write `theme.json`.

## 2026-06-16 — Homepage block theme layout proposal intake accepted

- **Module**: Plan-to-proposal intake for block theme homepage layouts.
- **Status**: Core now accepts `homepage_landing` block theme site plans that
  include the safe dynamic `core/categories` block for category entry sections.
- **Completed**:
  - Added `core/categories` to Core's block theme safe core block allowlist.
  - Added fail-closed coverage for a representative `front-page`
    `homepage_landing` layout with hero, CTA button, latest posts, and
    categories.
  - Added WordPress smoke coverage that runs the real Toolkit
    `build-block-theme-site-plan` homepage layout through
    `POST /proposals/from-plan`.
  - Updated ability intake, governance, REST, and plan-to-proposal docs so the
    homepage profile's latest-posts and category-entry reader blocks are
    explicit.
- **Verification**:
  - `composer test`
  - `php tests/fail-closed.php`
  - `git diff --check`
  - `composer test:all`
  - `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" composer smoke:wp`
  - `composer validate --no-check-publish`
- **Boundary**:
  - Core still only validates and stores proposal/audit context. This does not
    add Core final write execution, navigation edits, global styles edits,
    theme file edits, `theme.json` mutation, raw HTML acceptance, or Toolkit
    ability ownership.

## 2026-06-15 — Runtime owner can be bundled in Toolbox release

- **Module**: Future runtime packaging decision.
- **Status**: ADR-007 now clarifies that `npcink-local-automation-runtime`
  remains the independently developed and independently testable runtime owner,
  while release packaging may bundle it inside Toolbox as an isolated
  `modules/local-automation-runtime/` module.
- **Completed**:
  - Updated ADR-007 to allow Toolbox release bundling without collapsing
    runtime ownership into Toolbox fixed-flow buttons.
  - Updated the runtime contract and README with the required isolated module
    identity: namespace, table prefix, capabilities, contract version, kill
    switch, tests, and boundary docs.
  - Updated static contracts so default tests protect the bundled-module
    boundary.
- **Boundary**:
  - This pass changes packaging policy only. It does not create the runtime
    repo/module and does not add Core REST routes, Core tables, workers,
    schedulers, lease stores, retry processors, dead-letter processors,
    unattended approval, or final WordPress writes.

## 2026-06-15 — Local automation runtime owner and Phase 1 replay fixed

- **Module**: Future runtime owner decision and Phase 1 contract artifacts.
- **Status**: ADR-007 now names the future independent runtime owner as
  `npcink-local-automation-runtime`, with Phase 1 limited to schema and
  dry-run replay artifacts.
- **Completed**:
  - Added ADR-007 to select the future repo/plugin owner and keep Core,
    Adapter, Toolbox, and Toolkit out of unattended runtime ownership.
  - Added `docs/local-automation-runtime-phase-1-schema.md` for the Phase 1
    dry-run replay schema.
  - Added `tests/fixtures/local-automation-runtime-dry-run-replay.json` as the
    first contract fixture for the future runtime repo.
  - Updated README, the runtime contract, and static tests to cover owner,
    schema, replay, and no-background-execution guarantees.
- **Boundary**:
  - This pass still does not create `/Users/muze/gitee/npcink-local-automation-runtime`
    and does not add Core REST routes, Core tables, workers, schedulers, lease
    stores, retry processors, dead-letter processors, unattended approval, or
    final WordPress writes.

## 2026-06-15 — Local automation runtime contract drafted

- **Module**: Future local automation runtime planning contract.
- **Status**: A planning-only runtime contract now defines the required job
  model, action model, state machine, Core handoff, lease/retry/dead-letter
  behavior, idempotency, dependency resolution, authorization, operator
  controls, audit events, and acceptance gates for future unattended batch
  automation.
- **Completed**:
  - Added `docs/local-automation-runtime-contract.md` as the contract-first
    specification for any future dedicated runtime owner.
  - Linked the contract from README and ADR-006.
  - Added static contract assertions so default tests protect the boundary and
    required runtime semantics.
- **Boundary**:
  - This pass is documentation and contract only. Core still has no runtime
    job table, scheduler, lease store, retry worker, dead-letter processor,
    unattended approval loop, or final WordPress write execution.

## 2026-06-15 — Unattended batch automation boundary recorded

- **Module**: Core architecture decisions and batch automation boundary.
- **Status**: ADR-006 now records that unattended batch automation must wait
  for a dedicated local automation runtime contract instead of being added to
  Core or the OpenClaw Adapter.
- **Completed**:
  - Added ADR-006 to define current-stage reviewed batch governance versus
    future unattended runtime ownership.
  - Required a future runtime contract to define job storage, leases, locks,
    retry backoff, dead-letter handling, idempotency, dependency resolution,
    kill switch, pause/cancel behavior, rate limits, runtime audit, and
    operator-visible recovery guidance before implementation.
  - Updated README and static contracts so the ADR is part of the default
    development entrypoint and test gate.
- **Boundary**:
  - This pass does not add jobs, queues, schedulers, retry workers, unattended
    approval, runtime state, or final WordPress writes to Core.

## 2026-06-15 — Batch proposal review summary added

- **Module**: Plan-to-proposal batch review visibility.
- **Status**: Core batch proposals now expose a stable
  `preview.batch_review_summary`, and commit preflight returns the same summary
  under `proposal_item_preflight` for operator recovery guidance.
- **Completed**:
  - Added `core-batch-review-summary-v1` for grouped
    `plan_to_proposal_batch` proposals, including action counts, blocked counts,
    target ability ids, retryability, operator next action, and explicit
    `final_execution_owner=adapter_after_core_preflight`.
  - Preserved `core_execution=false` and `commit_execution=false` in the
    summary so it cannot be mistaken for Core-owned runtime or execution
    authority.
  - Bounded the commit-preflight summary response shape so unknown queue-like
    fields and secret-shaped fields are not surfaced from proposal preview.
  - Updated fail-closed/static tests and REST/governance/plan-intake docs.
- **Boundary**:
  - Core still does not own queues, schedulers, retry leases, background
    workers, unattended approval, or final WordPress writes.

## 2026-06-13 — Block theme layout proposal intake accepted

- **Module**: Plan-to-proposal intake for block theme template layouts.
- **Status**: Core now accepts bounded `customize_template_layout` block theme
  site plans as proposal batches when Toolkit supplies a passing template
  layout contract.
- **Completed**:
  - Extended `build-block-theme-site-plan` intake beyond `add_breadcrumbs` to
    also allow `intent=customize_template_layout`.
  - Added Core-side validation for bounded layout profiles:
    `article_standard`, `page_standard`, and `homepage_landing`.
  - Required `template_layout_contract.contract_status=pass` and
    `placement_model=bounded_template_layout_profile` before proposal creation.
  - Preserved layout intent, profile, and contract details in
    `preview.block_theme_site`.
  - Updated fail-closed tests, static contracts, REST/intake docs, and the
    WordPress smoke scenario to verify layout proposal intake instead of relying
    on a breadcrumb action that may already be valid on the local site.
- **Verification**:
  - `composer test`
  - `git diff --check`
  - `composer validate --no-check-publish`
  - `composer test:all`
  - Manual LocalWP REST check for `customize_template_layout` proposal intake
    returning HTTP 201 with one proposal.
  - `WP_CLI=/tmp/wp-cli.phar WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" composer smoke:wp`
- **Boundary**:
  - Core only validates and stores proposal/audit context. It does not define
    Toolkit abilities, generate block layouts, approve proposals, execute
    writes, edit theme files, mutate navigation/global styles, or become a
    final WordPress write executor.

## 2026-06-12 — Eval-lab quality gate boundary documented

- **Module**: Development-only AI output quality evidence.
- **Status**: Optional eval-lab wiring is documented for local use, has a
  project boundary review entrypoint, and now pins a redacted project label plus
  eval-lab output contract without changing Core runtime behavior.
- **Completed**:
  - Added a thin `scripts/eval-lab.sh` wrapper that calls the sibling
    Magick AI Evaluation Lab through `MAGICK_AI_EVAL_LAB_PATH` or the default
    sibling checkout.
  - Added opt-in Composer commands for listing eval-lab tasks and dry-running
    the Gutenberg cross-judge task.
  - Added `composer eval:project:review` as an opt-in wrapper for the
    eval-lab `project_boundary_review_triad` task.
  - Re-ran provider-backed three-model review after pushing, then tightened the
    wrapper to pass `project_label=npcink-governance-core` and
    `contract=project_boundary_review_triad.v1`.
  - Strengthened static contracts so default Composer test and release gates
    cannot indirectly invoke eval-lab.
  - Documented the boundary: eval-lab output is local review evidence only and
    must not create Core proposals, approvals, preflights, execution records,
    audit truth, provider credential storage, or WordPress writes.
- **Verification**:
  - `git diff --check`
  - `composer validate --no-check-publish`
  - `composer test:all`
  - `composer eval:lab -- --list`
  - `composer eval:gutenberg:judge -- dry_run=true limit=3`
  - `composer eval:project:review -- dry_run=true mode=working_diff`
  - `composer eval:project:review -- mode=working_diff`
- **Boundary**:
  - Core still owns deterministic governance lifecycle tests. Eval-lab remains
    outside default Core gates, CI-required tests, release packages, and plugin
    runtime behavior.

## 2026-06-12 — App scope and guardrail query follow-up hardening

- **Module**: App-auth scopes, proposal guardrail persistence, and lifecycle
  repository APIs.
- **Status**: Follow-up P1/P2/P3 findings from the hardening review were
  addressed inside Core's governance boundary.
- **Completed**:
  - Split post-preflight execution-result recording into
    `commit:record_execution`, separate from `commit:preflight`, and kept it
    out of default app scopes.
  - Changed explicit empty or invalid app scopes to fail closed with a stable
    `npcink_governance_core_app_scopes_empty` error instead of silently
    granting default scopes.
  - Added indexed `pending_quota_key` proposal storage so pending quota and
    duplicate checks no longer scan `caller_json` with `LIKE`.
  - Reduced accidental future lifecycle-race risk by making unconditional
    repository status update helpers private while preserving conditional
    transition APIs.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `composer validate --no-check-publish`
  - `composer check:wporg`
- **Boundary**:
  - Core still records governance lifecycle and audit evidence only. This pass
    does not add workflow runtime, execution queues, provider credentials, or
    final WordPress write authority.

## 2026-06-12 — Security and performance hardening pass

- **Module**: Governance persistence, app-auth rate limits, audit filters, and
  plan-to-proposal intake bounds.
- **Status**: P1/P2/P3 audit findings were addressed inside Core's governance
  lifecycle boundary.
- **Completed**:
  - Added shared sensitive-data redaction for proposal payloads, sensitive read
    request payloads, and audit metadata.
  - Switched proposal/read-request lifecycle writes to conditional status
    transitions and made fixed-window app rate counters increment under-limit
    in SQL.
  - Promoted common audit metadata filters into indexed audit columns.
  - Bounded from-plan payload size and write-action counts, with narrower caps
    for media optimization and block theme site batch reviews.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `composer validate --no-check-publish`
  - `composer check:wporg`
- **Boundary**:
  - Core still owns governance records, review, preflight, audit, and app-key
    enforcement only. This pass does not add workflow runtime, execution
    queues, provider credentials, or final WordPress write authority.

## 2026-06-11 — Post-preflight execution outcomes recorded

- **Module**: Proposal lifecycle and Adapter handoff audit.
- **Status**: Core now records terminal post-preflight execution outcomes
  reported by the thin Adapter after an allowlisted approved write.
- **Completed**:
  - Added Core proposal statuses `executed` and `execution_failed`.
  - Added `POST /proposals/{proposal_id}/record-execution` so the Adapter can
    record a public-safe execution result bound to a matching
    `commit.preflighted` audit event.
  - Added audit events `proposal.executed` and `proposal.execution_failed`,
    with rollback to `approved` if outcome audit recording fails.
  - Updated Adapter integration docs and tests so completed block-theme writes
    surface Core top-level `status=executed` instead of only an
    Adapter-derived effective status.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer smoke:wp`
  - In `/Users/muze/gitee/magick-ai-adapter`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-adapter`: `composer smoke:wp` with the
    LocalWP PHP and MySQL socket exported explicitly.
  - Live block-theme proposal `b710f978-598b-4c3a-b255-b207202e4a75` executed
    through Adapter and now reads back as Core `status=executed` with
    `proposal.executed` audit and the `openclaw-breadcrumbs` template block.
- **Boundary**:
  - Core records execution outcomes only. It still does not execute target
    abilities or become the final WordPress write authority. Adapter remains
    the explicit post-Core execution profile for allowlisted writes, with
    `commit_execution=false` and `core_proxy_execute=false`.

## 2026-06-11 — AI development workstream summary documented

- **Module**: AI development workstream documentation.
- **Status**: Current #4/#5 history and stop criteria are captured in repo
  docs for future agents.
- **Completed**:
  - Added `docs/ai-development-workstream-summary.md` to summarize the recent
    AI development handoff, Core boundary regression, and LocalWP smoke
    reliability slices.
  - Linked the workstream summary from README and the AI development handoff
    summary.
  - Added static contracts that preserve the summary's main boundary and stop
    criteria.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Documentation and static contracts only. No Core runtime authority,
    proposal lifecycle, REST behavior, database schema, Toolkit ability
    ownership, smoke runtime behavior, or final WordPress execution behavior
    changed.

## 2026-06-11 — Smoke failure classification documented

- **Module**: LocalWP smoke reliability.
- **Status**: Second #4 smoke diagnostics slice is ready for review.
- **Completed**:
  - Added a smoke failure classification section to the development workflow.
  - Documented the distinction between preflight `environment` failures,
    preflight `toolkit` failures, and post-preflight Core or Toolkit contract
    regressions.
  - Added static contracts so future edits keep the smoke classification and
    preserve Toolkit ability ownership.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer smoke:wp`
- **Boundary**:
  - Smoke troubleshooting documentation and static contracts only. No Core
    runtime authority, proposal lifecycle, REST behavior, database schema,
    reusable ability definitions, Toolkit ability ownership, or final WordPress
    execution behavior changed.

## 2026-06-11 — LocalWP smoke preflight diagnostics added

- **Module**: LocalWP smoke reliability.
- **Status**: First #4 smoke diagnostics slice is ready for review.
- **Completed**:
  - Added `[smoke:preflight]` diagnostics to `tests/smoke-wp.sh` for
    repository root, `WP_PATH`, WP-CLI, Local PHP, MySQL socket, Core plugin
    symlink, Toolkit plugin file, and Toolkit replay fixture candidate.
  - Added fail-fast `[smoke:preflight:fail]` classification for missing
    WP-CLI/PHP, invalid WordPress root, missing plugins directory, missing
    Toolkit plugin file, broken Core symlink, or wrong Core symlink target.
  - Documented the smoke wrapper preflight responsibility in development and
    testing docs.
  - Added static contracts so future edits keep the smoke preflight checks.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `bash -n tests/smoke-wp.sh`
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer smoke:wp`
- **Boundary**:
  - Smoke wrapper diagnostics and documentation only. No Core runtime
    authority, proposal lifecycle, REST behavior, database schema, reusable
    ability definitions, Toolkit ability ownership, or final WordPress
    execution behavior changed.

## 2026-06-11 — Runtime drift marker scan expanded

- **Module**: Core boundary regression checks.
- **Status**: A second #5 static-contract slice is ready for review.
- **Completed**:
  - Expanded static runtime marker checks to catch accidental `/jobs`,
    `/tasks`, and `/runs` Core REST route additions.
  - Added runtime-shaped filename and PHP symbol scanning for executor, queue,
    scheduler, worker, workflow runtime, MCP server, and Agent Gateway markers
    inside runtime plugin files.
  - Documented the filename/class/route scan responsibility in the testing
    strategy.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Static regression checks and documentation only. No Core runtime authority,
    provider credential storage, model routing, workflow runtime, MCP runtime,
    task queue, batch execution console, REST behavior, database schema, or
    final WordPress execution behavior changed.

## 2026-06-11 — Core boundary regression checks hardened

- **Module**: Core boundary regression checks.
- **Status**: Static guardrails for #5 were strengthened in this session.
- **Completed**:
  - Added static contract coverage that keeps the pull request template and
    boundary-review issue template aligned with the Core ownership boundary.
  - Expanded runtime-file static scanning for execution routes, provider
    credential markers, model routing, workflow/MCP runtime markers, task
    queues, batch execution consoles, and operator runtime console markers.
  - Tightened the boundary-review issue template so AI-assisted planning must
    explicitly keep model routing, product workflow UX, batch execution
    consoles, and reusable ability definitions outside Core.
  - Documented these static contract responsibilities in the testing strategy.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `ruby -e 'require "yaml"; YAML.load_file(".github/ISSUE_TEMPLATE/boundary_review.yml")'`
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Regression checks and documentation only. No Core runtime authority,
    provider credential storage, model routing, workflow runtime, MCP runtime,
    task queue, batch execution console, REST behavior, database schema, or
    final WordPress execution behavior changed.

## 2026-06-11 — AI development handoff summary added

- **Module**: AI development handoff documentation.
- **Status**: A standalone AI development handoff summary is available for
  future agents.
- **Completed**:
  - Created issue #11 to track the standalone handoff summary.
  - Added `docs/ai-development-handoff-summary.md` as the condensed future-AI
    entrypoint for current GitHub setup, workflow rules, project backlog,
    current priorities, release boundaries, and startup prompt.
  - Linked the handoff summary from README and the solo AI workflow doc.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Documentation and GitHub planning process only. No Core runtime authority,
    provider credential storage, workflow runtime, proposal lifecycle, REST
    behavior, database schema, or final execution behavior changed.

## 2026-06-11 — Priority guardrails documented for solo AI work

- **Module**: Solo AI development priority guardrails.
- **Status**: Current AI-development priority guidance is now durable in repo
  docs and in the #4/#5 GitHub Issue bodies.
- **Completed**:
  - Created issue #9 to track durable documentation for current AI-development
    priorities.
  - Updated `docs/solo-ai-development-workflow.md` to make repository docs and
    GitHub Issues the source of truth over chat-only recommendations.
  - Documented the current priority order: #5 Core boundary regression checks
    first, then #4 LocalWP smoke reliability.
  - Linked the priority guardrails from GitHub development support docs.
  - Updated issues #5 and #4 with priority rationale, AI-agent checklists,
    boundaries, and required gates.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Documentation and GitHub planning process only. No Core runtime authority,
    provider credential storage, workflow runtime, proposal lifecycle, REST
    behavior, database schema, or final execution behavior changed.

## 2026-06-11 — Solo AI development workflow documented

- **Module**: Solo maintainer + AI development workflow.
- **Status**: Solo maintainer + AI agent development now has a documented
  issue-first workflow and seeded GitHub Project backlog.
- **Completed**:
  - Added `docs/solo-ai-development-workflow.md` for issue-first AI work,
    task branches, PR evidence, project board stages, and verification gates.
  - Linked the workflow from README, GitHub development support docs, and the
    development workflow.
  - Created backlog issues #2 through #6 and added them to the
    `npcink-governance-core Release Board` project with `Release Stage=Backlog`
    and focused `Gate` values.
  - Created issue #7 to track this documentation workflow change, with
    `Release Stage=In Progress` and `Gate=Docs Only`.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
- **Boundary**:
  - Documentation and GitHub planning process only. No Core runtime authority,
    provider credential storage, workflow runtime, proposal lifecycle, REST
    behavior, database schema, or final execution behavior changed.

## 2026-06-11 — GitHub development support added

- **Module**: GitHub development collaboration and release support.
- **Status**: The repository now has reusable GitHub templates, dependency
  monitoring configuration, a manual release package artifact workflow, and
  documentation for the GitHub/WordPress.org split.
- **Completed**:
  - Added pull request and issue templates for Core boundary review, bugs,
    release tasks, and WordPress.org reviewer findings.
  - Added Dependabot configuration for GitHub Actions and Composer.
  - Added `Release Package` workflow for manual static package artifact builds.
  - Added a security policy and GitHub development support documentation.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `ruby -e 'require "yaml"; ARGV.each { |f| YAML.load_file(f) }' .github/ISSUE_TEMPLATE/*.yml .github/dependabot.yml .github/workflows/*.yml`
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer package:release`
- **Boundary**:
  - Repository collaboration and CI/release support only. No Core runtime
    authority, provider credential storage, workflow runtime, proposal
    lifecycle, REST behavior, database schema, or final execution behavior
    changed.

## 2026-06-10 — WordPress.org release helper scripts added

- **Module**: WordPress.org release tooling.
- **Status**: Frequent Core code updates now have reusable local release
  preparation and conservative SVN sync helpers.
- **Completed**:
  - Added `scripts/prepare-release.sh` and Composer `prepare:release` to check
    version metadata, run the local release gate, package the plugin, and
    verify the zip root.
  - Added `scripts/sync-wporg-svn.sh` and Composer `sync:wporg` to dry-run or
    apply package sync into an existing WordPress.org SVN checkout, create a
    version tag, and optionally sync listing assets.
  - Adjusted the LocalWP smoke wrapper to prefer `/tmp/wp-cli.phar` when
    available so release preparation uses the Local PHP runtime and MySQL
    socket instead of an unrelated PATH `wp`.
  - Aligned the block theme site smoke assertion with the public contract that
    permits either `update-template-blocks` or `upsert-template-blocks` as the
    reviewed template write action.
  - Updated release and development docs to keep GitHub as the development
    repository, LocalWP smoke as the local runtime gate, and WordPress.org SVN
    as release-only.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `bash -n scripts/prepare-release.sh scripts/sync-wporg-svn.sh tests/smoke-wp.sh`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer smoke:wp`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer prepare:release -- --version 0.1.0 --allow-dirty`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer sync:wporg -- --version 0.1.1 --svn-dir <temporary-svn-checkout> --assets`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer sync:wporg -- --version 0.1.1 --svn-dir <temporary-svn-checkout> --assets --apply`
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
- **Boundary**:
  - Release tooling and documentation only. No Core runtime authority, provider
    credential storage, workflow runtime, proposal lifecycle, or WordPress
    execution behavior changed.

## 2026-06-10 — GitHub Actions static CI baseline added

- **Module**: GitHub Actions CI baseline.
- **Status**: The GitHub repository now has a lightweight Actions workflow for
  non-LocalWP verification on push and pull requests.
- **Completed**:
  - Added `.github/workflows/ci.yml` with PHP 8.0, Composer metadata
    validation, static contract tests, fail-closed tests, and WordPress.org
    review guard.
  - Added a CI checkout of the public `npcink-abilities-toolkit` repository and
    `NPCINK_ABILITIES_TOOLKIT_PATH` so Core's shared replay fixture contract
    remains enforced in GitHub Actions.
  - Kept `composer smoke:wp` out of GitHub Actions because it depends on the
    local LocalWP site, WP-CLI runtime, and local database socket.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer check:wporg`
  - In `/Users/muze/gitee/npcink-governance-core`: `git diff --check`
- **Boundary**:
  - Repository CI configuration only. No Core runtime authority, provider
    credential storage, workflow runtime, proposal lifecycle, or WordPress
    execution behavior changed.

## 2026-06-10 — Block theme site plan Core intake enabled

- **Module**: Core plan-to-proposal intake for block theme site plans.
- **Status**: `npcink-abilities-toolkit/build-block-theme-site-plan` is now a
  Core-allowlisted plan ability for reviewed active block theme template
  proposals.
- **Completed**:
  - Added `build-block-theme-site-plan` to Core's plan-to-proposal allowlist.
  - Added a narrow `block_theme_site_plan` contract validator for
    `intent=add_breadcrumbs`, `proposal_mode=batch`, active-theme evidence, and
    template write actions limited to `update-template-blocks` or
    `upsert-template-blocks`.
  - Preserved Gutenberg block-tree key case for template block write proposal
    inputs.
  - Updated public plan-to-proposal docs and smoke/fail-closed coverage.
- **Verification**:
  - In `/Users/muze/gitee/npcink-governance-core`: `composer test:all`
  - In `/Users/muze/gitee/npcink-governance-core`: `composer smoke:wp`
  - Adapter/OpenClaw local verification created pending proposal
    `f72ce913-4a80-46c8-a48c-a9b63b0199ea` for
    `npcink-abilities-toolkit/upsert-template-blocks` without executing it.
- **Boundary**:
  - Core stores proposal, approval, preflight, and audit truth only. It still
    does not edit theme files, navigation, global styles, render templates,
    approve proposals, execute WordPress writes, or add workflow/runtime
    ownership.

## 2026-06-10 — Repository moved to GitHub primary remote

- **Module**: Repository hosting and local checkout identity.
- **Status**: GitHub is now the primary Git remote for
  `npcink-governance-core`, and the local checkout path matches the plugin
  slug.
- **Completed**:
  - Created the public GitHub repository
    `https://github.com/muze-page/npcink-governance-core`.
  - Pushed `master` to GitHub with full Git history.
  - Changed `origin` from the old Gitee repository to
    `git@github.com:muze-page/npcink-governance-core.git`.
  - Moved the local checkout from `/Users/muze/gitee/magick-ai-core` to
    `/Users/muze/gitee/npcink-governance-core`.
  - Updated the LocalWP plugin symlink so
    `wp-content/plugins/npcink-governance-core` points to the new checkout
    path.
- **Verification**:
  - GitHub repository is public and non-empty.
  - `git status --short --branch` reports `master...origin/master`.
  - LocalWP symlink target resolves to
    `/Users/muze/gitee/npcink-governance-core`.
- **Boundary**:
  - Repository hosting and local path migration only. No plugin runtime,
    governance authority, provider credential storage, workflow runtime, or
    final write execution behavior changed.
- **Follow-up**:
  - Delete the old Gitee repository after confirming no external automation
    still references it.

## 2026-06-10 — Post-release rename leftovers cleaned

- **Module**: Core release identity cleanup.
- **Status**: Remaining non-historical `magick-ai-core` naming leftovers were
  aligned with `npcink-governance-core`.
- **Completed**:
  - Updated the app-token alternate header documentation to
    `X-Npcink-Governance-Core-App-Token`, matching the existing authenticator
    header lookup.
  - Added the alternate app-token header to the REST API contract and static
    contracts.
  - Renamed the fail-closed test WPDB stub from the old Magick AI Core class
    name to `Npcink_Governance_Core_Fail_Closed_WPDB`.
  - Updated the content metadata delta implementation prompt to refer to the
    active Core repo as `npcink-governance-core`.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `git diff --check`
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
- **Boundary**:
  - Naming/documentation/test consistency only. No Core runtime authority,
    provider credential storage, workflow runtime, product UX, or final write
    execution behavior changed.
- **Deferred**:
  - Local directory and Gitee remote still use `magick-ai-core`; defer until
    the repository itself is renamed.

## 2026-06-10 — WordPress.org SVN 0.1.0 released

- **Module**: WordPress.org SVN release.
- **Status**: `npcink-governance-core` 0.1.0 is published to the official
  WordPress.org plugin SVN repository.
- **Completed**:
  - Pushed `master` to the canonical Git remote.
  - Checked out `https://plugins.svn.wordpress.org/npcink-governance-core`.
  - Synced the release package contents into SVN `/trunk`.
  - Created SVN `/tags/0.1.0` from `/trunk`.
  - Copied WordPress.org listing images into top-level SVN `/assets`.
  - Set `svn:mime-type=image/png` on the PNG listing assets.
- **SVN**:
  - URL: `https://plugins.svn.wordpress.org/npcink-governance-core`
  - Revision: `3566809`
  - Commit message: `Release npcink-governance-core 0.1.0`
  - Public URL: `https://wordpress.org/plugins/npcink-governance-core/`
- **Verification**:
  - Remote SVN `/trunk`, `/tags/0.1.0`, and `/assets` listings were checked.
  - `svn info` for `/trunk` reports Last Changed Rev `3566809`.
  - Public plugin URL returned HTTP 200 after redirect normalization.
- **Boundary**:
  - Release publication only. No Core runtime behavior, governance authority,
    provider credentials, workflow runtime, or final execution path changed.

## 2026-06-10 — Release branch merged and public plugin name aligned

- **Module**: WordPress.org release identity and listing assets.
- **Status**: The release candidate branch was fast-forward merged into
  `master`, and the public plugin name is now `npcink-governance-core`.
- **Completed**:
  - Merged `codex/media-adoption-enhancement-plan` into `master`.
  - Updated the plugin header, `readme.txt`, admin title, release gate docs,
    translation catalogs, and `sj/` listing copy to use
    `npcink-governance-core` as the public plugin name.
  - Reworked the WordPress.org banner exports from the existing asset material
    so they no longer show the old `Magick AI Core` text.
  - Confirmed WordPress.org asset dimensions for `banner-1544x500.png`,
    `banner-772x250.png`, `icon-256x256.png`, and `icon-128x128.png`.
  - Rebuilt `build/npcink-governance-core.zip`; the package root remains
    `npcink-governance-core/`, and `sj/` remains excluded from the plugin zip.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `git diff --check`
  - In `/Users/muze/gitee/magick-ai-core`: `composer release:verify`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
  - In `/Users/muze/gitee/magick-ai-core`: `composer package:release`
- **Next steps**:
  - Push `master`.
  - Copy `build/npcink-governance-core.zip` contents to WordPress.org SVN
    `/trunk`, create `/tags/0.1.0`, and copy `sj/exports/wordpress-org/*` to
    SVN top-level `/assets`.
- **Boundary**:
  - Release identity and listing asset work only. Core remains the governance
    layer and still does not execute final writes, route models, store provider
    credentials, or add workflow/runtime ownership.

## 2026-06-10 — WordPress.org release gate warning cleared

- **Module**: Core WordPress.org release package readiness.
- **Status**: Release verification is clean after fixing the plugin action-link
  text domain.
- **Completed**:
  - Replaced the `Settings` action-link translation domain with
    `npcink-governance-core`.
  - Added the `Settings` string to the POT and Simplified Chinese PO catalog and
    regenerated the MO file.
  - Built `build/npcink-governance-core.zip` and confirmed the package root is
    `npcink-governance-core/`.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer release:verify`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
  - In `/Users/muze/gitee/magick-ai-core`: `composer package:release`
- **Next steps**:
  - Confirm the release should be cut from this branch or first merged into the
    canonical release branch.
  - Prepare WordPress.org SVN top-level `/assets` images if the first public
    listing should include a custom banner, icon, or screenshots.
- **Boundary**:
  - Release metadata/i18n fix only. Core still does not add workflow runtime,
    product UX, provider credentials, external calls, or final write execution.

## 2026-06-10 — Field patch review context surfaced

- **Module**: Core proposal detail admin review surface.
- **Status**: Proposal `preview.field_patch` values now appear as first-class
  field changes in the review context before raw JSON payload disclosure.
- **Completed**:
  - Added field-level review rendering for preview metadata such as SEO title
    and description handoffs from Toolbox.
  - Kept raw proposal payload available behind the existing disclosure.
  - Updated static contracts and Chinese translation catalogs.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
  - Browser-verified the local Core proposal detail shows `字段变更`,
    `seo_title`, and `seo_description` in the review context.
- **Boundary**:
  - Display-only admin review improvement. Core still does not choose SEO
    strategy, generate metadata, execute writes, or become a product workflow
    surface.

## 2026-06-09 — Multi-attachment media optimization intake enabled

- **Module**: Core plan-to-proposal intake for media optimization plans.
- **Status**: `npcink-abilities-toolkit/build-media-optimization-plan` now
  admits one reviewed batch proposal containing multiple attachments when each
  attachment has paired metadata update and derivative adoption actions.
- **Completed**:
  - Changed the media optimization contract validator from a global
    single-attachment check to per-attachment pairing checks.
  - Preserved fail-closed rejection for missing derivative/metadata pairing and
    for separate post-content reference repair write actions.
  - Updated public plan-to-proposal, REST, ability intake, governance, testing,
    README, and translation contract text.
  - Added fail-closed coverage proving a two-attachment/four-action media
    optimization plan creates one Core batch proposal.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Boundary**:
  - Core still only validates reviewed plan output and owns proposal,
    approval, preflight, and audit truth. It does not execute media writes,
    download Cloud artifacts, create derivatives, repair post content, or add
    workflow/runtime queue ownership.

## 2026-06-09 — Core-managed sensitive read authorization added

- **Module**: Core sensitive read request lifecycle.
- **Status**: Core now owns a reviewable, approvable, auditable read request /
  grant flow for read abilities that require extra authorization.
- **Completed**:
  - Added capability flags and route guidance for Core read authorization,
    including `read_authorization_required`,
    `requires_read_authorization`, `read_policy=core_read_authorization_required`,
    `authorization_mode=core_read_request`, and nested
    `read_authorization.required=true`.
  - Added the `npcink_governance_core_read_requests` table, read request
    repository/service, REST controller, app scopes, and activation wiring.
  - Added bounded read preflight context with Core authorization truth,
    `ability_id` + `input_hash` binding, expiry, redaction/bounds metadata,
    `commit_execution=false`, and `write_execution=false`.
  - Updated REST, schema, security, architecture, governance, ability intake,
    app scope, testing, and sensitive read authorization docs.
  - Added static, fail-closed, and WordPress smoke coverage for create,
    approve/reject, preflight/grant, mismatch, expiry, one-time consumption,
    audit timeline, and secret redaction.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Next steps**:
  - Adapter should treat capability read authorization fields as fail-closed
    signals, create/poll Core read requests, and call
    `/read-requests/{request_id}/read-preflight` immediately before executing
    the WordPress Abilities API read.
- **Boundary**:
  - Core still does not execute reads, proxy read results, store raw logs/files
    or secrets, own prompt truth, add Adapter approval truth, or introduce any
    workflow, queue, MCP, Cloud, database direct-read, file-read, or script
    runtime.

## 2026-06-09 — Approval policy stage closeout documented

- **Module**: Core approval policy evaluator documentation.
- **Status**: The guarded approval-policy stage is closed out as a Core
  implementation milestone, with remaining work redirected to operational
  observation and Adapter productization.
- **Completed**:
  - Added `docs/approval-policy-stage-closeout.md` as the historical summary
    for future AI sessions.
  - Recorded the current supported modes, implemented guardrails, verified
    evidence, explicit non-candidates, OpenClaw development usage, and the
    recommendation to stop expanding Core in this stage.
  - Linked the closeout note from the README documentation index.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `git diff --check`
- **Boundary**:
  - Documentation only. No Core REST routes, proposal lifecycle behavior,
    approval mode, Adapter execution behavior, workflow runtime, policy DSL, or
    WordPress mutation was added.

## 2026-06-09 — Local guarded create-draft auto approval added

- **Module**: Core approval policy evaluator.
- **Status**: Development `local_guarded` now auto-approves a second narrow
  class: single direct `npcink-abilities-toolkit/create-draft` proposals that
  create draft posts only.
- **Completed**:
  - Added fail-closed evaluator checks for direct create-draft proposals:
    draft post only, reviewed title, no existing target, no publish/schedule
    intent, dry-run/no-commit input, bounded content size, trusted caller/app
    approval scope, quota, and audit.
  - Kept `manual` as default and `dry_run_guarded` as observation-only.
  - Updated admin copy, REST/security/app-scope/approval-policy docs,
    translation catalogs, static contracts, fail-closed tests, and WordPress
    smoke coverage.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Boundary**:
  - No auto approval for publish, schedule, batch article plans, destructive
    operations, comments, terms, media deletes, settings, existing
    published-content updates, Core final execution, workflow runtime, rules
    DSL, scheduler, or policy configuration center.

## 2026-06-09 — Draft block batch validator helper extracted

- **Module**: Core plan-to-proposal intake contract validators.
- **Status**: The repeated draft-create plus `update-post-blocks` batch action
  shape used by article block and pattern page plans now lives behind one
  internal helper.
- **Completed**:
  - Extracted the shared ordered two-action validation for `create-draft` and
    `update-post-blocks`, including draft-only status, reviewed title,
    dry-run/no-commit guards, output-reference binding, and non-empty block
    trees.
  - Kept article block and pattern page validators responsible for their own
    artifact/template/style gates and CSS class policy.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
- **Boundary**:
  - Internal consolidation only. No new planning ability, REST field,
    proposal status, auto-approval behavior, Core execution path, workflow
    runtime, or WordPress mutation was added.

## 2026-06-09 — Article block from-plan Core bridge aligned

- **Module**: Core plan-to-proposal intake for Toolkit Gutenberg article block
  drafts.
- **Status**: `npcink-abilities-toolkit/build-article-block-plan` is now
  allowlisted and contract-validated by Core `/proposals/from-plan`.
- **Completed**:
  - Added Core allowlist support for the Toolkit article block planning
    ability.
  - Added fail-closed validation for `article_block_plan`: batch mode only,
    allowlisted editorial templates, `responsive_profile=article_standard`,
    exactly one draft post create action followed by one
    `update-post-blocks` action, output reference required, dry-run/no-commit,
    native quality evidence required, and custom block classes rejected.
  - Added `preview.article_block` review evidence and updated public Core
    contracts.
  - Added PHP and WordPress smoke coverage proving from-plan intake creates a
    pending batch proposal and does not create the post draft during intake or
    preflight.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Boundary**:
  - Core still does not generate article content, render Gutenberg blocks,
    approve proposals automatically, execute batch actions, or mutate WordPress
    content. Adapter/host execution remains after approval and commit
    preflight.

## 2026-06-09 — Current-stage closeout and handoff summarized

- **Module**: Core boundary documentation and cross-module handoff guidance.
- **Status**: The historical discussion about SEO/GEO suggestions, media alt
  text, pre-publish checks, taxonomy governance, safe draft writing, and human
  approval is now summarized as a Core stop/Toolbox-Abilities handoff note.
- **Completed**:
  - Added `docs/current-stage-closeout-and-handoff.md` to record what has been
    completed in Core, why Core should stop expanding in this area, and where
    the remaining product work belongs.
  - Linked the handoff note from the README documentation index.
- **Verification**:
  - `composer test:all`
  - `git diff --check`
- **Boundary**:
  - Documentation only. No Core REST routes, proposal lifecycle behavior,
    ability execution, product UX, recommendation generation, taxonomy
    creation, or final WordPress write execution was added.

## 2026-06-09 — Content Metadata Delta duplicate-slot guardrail added

- **Module**: Core plan-to-proposal intake for reviewed content metadata apply
  plans.
- **Status**: Core now rejects `content_metadata_apply_plan` batches that try to
  include duplicate excerpt, category, or post-tag action slots for the same
  reviewed metadata apply intent.
- **Completed**:
  - Published the existing local media optimization stop/guardrail commits to
    `origin/master`.
  - Added fail-closed validation so one content metadata apply plan may include
    at most one excerpt update action, one category assignment action, and one
    post-tag assignment action.
  - Added fault-injection coverage for duplicate excerpt and duplicate taxonomy
    actions.
  - Updated plan-to-proposal, REST, governance, and ability-intake contracts.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - Core still only validates reviewed plan output, creates proposal records,
    and owns approval/preflight/audit truth. This does not add metadata
    recommendation generation, Toolbox UI, taxonomy creation, direct
    WordPress writes, feedback storage, local consent execution, or Core final
    execution.

## 2026-06-09 — Media optimization regression guardrails locked

- **Module**: Core operation classification and media optimization governance
  regression contracts.
- **Status**: Media optimization is now documented as a cross-repo regression
  path rather than a new Core feature line, and the stage plan now records the
  stop decision: continue only with regression fixes, not new Core media
  optimization implementation.
- **Completed**:
  - Added an Operation Classification regression rule that distinguishes
    single-object media file replacement strong confirmation from media
    optimization batch plans requiring Core proposal review.
  - Added static classifier coverage proving a wp-admin, one-attachment
    `batch_plan` remains `core_proposal_required`.
  - Documented the cross-repo media optimization regression split: Core keeps
    proposal/preflight/audit, Adapter keeps derived readiness and execution
    state, Abilities keeps verification/replacement counts/restore behavior,
    and Cloud Addon stays runtime/detail only.
  - Recorded the stop/continue decision in the next-stage plan: stop expanding
    media optimization in Core and redirect new product energy to
    classifier-driven authorization paths.
- **Verification**:
  - `composer test:all`
- **Boundary**:
  - Documentation and static-contract guardrails only. No Core execution,
    health route, Cloud artifact truth, Adapter status API, Abilities callback,
    REST contract, database schema, or WordPress write behavior was added.

## 2026-06-09 — Pattern page from-plan Core bridge aligned

- **Module**: Core plan-to-proposal intake for Toolkit Gutenberg pattern pages.
- **Status**: `npcink-abilities-toolkit/build-pattern-page-plan` is now
  allowlisted and contract-validated by Core `/proposals/from-plan`.
- **Completed**:
  - Added Core allowlist support for the Toolkit pattern page planning ability.
  - Added fail-closed validation for `pattern_page_plan`: batch mode only,
    `openai-style-landing` plus `minimal-dark-light`, exactly one draft page
    create action followed by one `update-post-blocks` action, output reference
    required, dry-run/no-commit, and CSS class allowlist enforcement.
  - Added `preview.pattern_page` review evidence and updated public Core
    contracts.
  - Added PHP and WordPress smoke coverage proving from-plan intake creates a
    pending batch proposal and does not create the page draft during intake or
    preflight.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Boundary**:
  - Core still does not render patterns, approve proposals, execute batch
    actions, or mutate WordPress content. Adapter/host execution remains after
    approval and commit preflight.

## 2026-06-09 — High-risk article/media batch Core proof implemented

- **Module**: Core operation classification contract and Toolbox article/media
  batch handoff verification.
- **Status**: The high-risk contrast proof is now implemented: reviewed
  article/media batch plans remain `core_proposal_required` and become one
  Core `plan_to_proposal_batch`, not Local Admin Consent.
- **Completed**:
  - Added Core classifier coverage showing a wp-admin batch image plan still
    routes to Core proposal review because it touches multiple objects.
  - Documented the article/media batch proof in Core README, governance
    contract, operation classification contract, and next-stage plan.
  - Added Toolbox smoke coverage for
    `npcink-toolbox/build-article-media-batch-write-plan` through Core
    `/proposals/from-plan`.
  - Verified the high-risk batch proof does not create posts, upload
    attachments, or emit `local_admin_consent.*` audit events during proposal
    intake.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer validate --no-check-publish`
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-toolbox`:
    `composer smoke:article-media-batch-core`
- **Next steps**:
  - Use the same classifier contract for any future single high-impact write
    proof before considering strong local confirmation UX.
- **Boundary**:
  - No direct batch write, media import, metadata update, featured-image
    setting, proposal approval, preflight, or Core execution was added.

## 2026-06-09 — Local Admin Consent featured-image proof implemented

- **Module**: Core audit hook and Toolbox editor featured-image local consent.
- **Status**: One low-risk Local Admin Consent proof now sets an existing
  WordPress image attachment as the current post featured image with
  Core-owned audit and no proposal.
- **Completed**:
  - Added Core audit-only
    `npcink_governance_core_record_local_admin_consent` filter support for
    requested/completed/failed local consent events.
  - Added Toolbox `/local-admin-consent/featured-image`, restricted to one
    existing image attachment and one target post.
  - Wired the editor image inspector to use local consent only when the
    selected image already has an attachment id; external URLs still use
    Adapter/Core adoption.
  - Added static and WordPress smoke coverage.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-toolbox`:
    `composer smoke:local-featured-image`
- **Next steps**:
  - Prove the high-risk contrast path, for example batch image selection,
    batch SEO, or batch article edits, remains `core_proposal_required`.
- **Boundary**:
  - Existing attachment featured-image write only. No media import, metadata
    update, generated/external URL adoption, proposal creation, approval,
    preflight, batch action, or Core execution was added to the local-consent
    path.

## 2026-06-09 — Content Metadata Delta governed handoff implemented

- **Module**: Core plan-to-proposal intake for accepted Toolbox content metadata
  choices.
- **Status**: Accepted excerpt/category/tag choices can now travel from
  Toolbox as a dry-run apply plan and be admitted by Core as one reviewable
  batch proposal without Toolbox directly writing WordPress.
- **Completed**:
  - Added the `npcink-toolbox/build-content-metadata-apply-plan` handoff
    ability contract in Toolbox.
  - Added a Toolbox apply-plan REST flow that turns reviewed excerpt and
    existing category/tag selections into dry-run `update-post` and
    `set-post-terms` write actions.
  - Added Core fail-closed validation for the apply plan: same target post,
    batch approval, dry-run/no-commit actions, excerpt-only post updates, and
    existing category/tag assignment only.
  - Kept proposed new categories/tags as manual-review notes; no term creation
    or direct WordPress writes were introduced.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer smoke:metadata-delta`
  - In `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Next steps**:
  - Clean up any leftover direct-proposal editor helper code once the new
    apply-plan UI path is settled.
  - Defer persistent feedback, measurement, and self-learning until real usage
    data exists.
- **Boundary**:
  - Governed handoff only. Core still owns proposal intake, approval,
    preflight, and audit; Toolbox still owns editor recommendation UX; neither
    module executes the final WordPress write in this phase.

## 2026-06-09 — Content Metadata Delta ranking quality advanced in Toolbox

- **Module**: Toolbox editor Content Metadata Delta P0 recommendation quality.
- **Status**: The current-stage implementation now treats related Site
  Knowledge results as real ranking context for summary/category/tag
  suggestions while preserving suggestion-only behavior.
- **Completed**:
  - Kept Core as governance truth only; no vector search, recommendation
    generation, feedback persistence, or WordPress writes were added to Core.
  - Updated Toolbox so `summary_terms_optimization` first collects related Site
    Knowledge, passes bounded related context into hosted AI summary support,
    and boosts existing WordPress categories/tags that appear on related local
    posts.
  - Marked related terms as ranking evidence only: no term creation, term
    assignment, excerpt write, learning-store persistence, or index lifecycle
    ownership.
  - Added static and local smoke coverage for the new ranking contract.
- **Verification**:
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer test:all`
  - In `/Users/muze/gitee/magick-ai-toolbox`: `composer smoke:metadata-delta`
- **Next steps**:
  - After recommendation quality stabilizes, implement the governed handoff
    path for accepted metadata choices through Core/Adapter/Abilities.
  - Defer persistent feedback and self-learning until real usage data exists.
- **Boundary**:
  - Recommendation quality only. Toolbox still returns reviewable artifacts and
    does not persist feedback, approve proposals, execute writes, own taxonomy
    governance, or own Site Knowledge indexing.

## 2026-06-09 — Operation classifier policy helper implemented

- **Module**: Core governance operation classification.
- **Status**: Core now has a pure `Operation_Classifier` policy helper for
  deciding between suggestion-only, local admin consent, strong local
  confirmation, and Core proposal review.
- **Completed**:
  - Added `Npcink\GovernanceCore\Governance\Operation_Classifier` with stable
    `operation-classification-v1` results.
  - Exposed the classifier through the plugin container without wiring it into
    REST, proposal creation, or final execution paths.
  - Added static and executable contract coverage for suggestion-only, low-risk
    admin-visible single writes, high-impact single writes, and external batch
    writes.
  - Updated the operation classification and next-stage docs to mark the policy
    helper as implemented while leaving scenario proofs for Toolbox/Adapter.
- **Next steps**:
  - Prove `local_admin_consent` in Toolbox with single image candidate ->
    featured image.
  - Prove `core_proposal_required` with a high-risk batch contrast scenario.
- **Boundary**:
  - Classification only. Core still does not execute writes, own Toolbox UX, or
    replace Adapter/product-module authorization flows.

## 2026-06-08 — Plugin Check warnings cleared before upload

- **Module**: WordPress.org release package hygiene.
- **Status**: The release Plugin Check gate now completes with no errors or
  warnings before packaging.
- **Completed**:
  - Removed the manual `load_plugin_textdomain()` call so WordPress.org can
    load hosted translations automatically.
  - Added narrow PHPCS suppressions with custom-table ownership reasons around
    Core-owned proposal, audit, app-key, and rate-limit table writes.
  - Updated release/testing docs so Plugin Check warnings are treated as
    blockers unless they have a narrow, local, documented suppression.
- **Verification**:
  - `composer plugin-check:release`
- **Boundary**:
  - Release hygiene only. Core custom table ownership remains unchanged, and no
    final ability execution or workflow runtime behavior was added.

## 2026-06-08 — Governed AI feedback loop strategy recorded

- **Module**: Product strategy and implementation handoff documentation.
- **Status**: The first-principles product model now treats WordPress as an
  observable business interface and AI as a reasoning layer that turns vague
  site pain into diagnosed, governed, measured deltas.
- **Completed**:
  - Added the Governed AI Feedback Loop planning guide with the ladder from
    article writing to signal-driven closed loops.
  - Defined Issue Record, Outcome Contract, and Learning Store artifacts so
    diagnosis can become execution, measurement, and structured improvement.
  - Identified Content Metadata Delta as the first narrow closed-loop proof:
    one post, related-content vector context, excerpt/tag/category
    recommendations, correct authorization path, measurement, and learning.
  - Added a paste-ready implementation prompt for another AI agent to execute
    the P0 while preserving Core/Product/Abilities boundaries.
- **Verification**:
  - `git diff --check`
- **Boundary**:
  - Documentation only. Core remains proposal, approval, preflight, and audit
    truth; it does not own vector search, metadata recommendation generation,
    product workbench UX, learning-store behavior, or final WordPress writes.

## 2026-06-08 — WordPress.org transient prefix review gate tightened

- **Module**: Core approval policy evaluator and WordPress.org release gate.
- **Status**: Auto-approval quota transients now expose the
  `npcink_governance_core` prefix directly at each `get_transient()` and
  `set_transient()` call site, matching WordPress.org review expectations.
- **Completed**:
  - Reworked auto-approval quota metadata to store sanitized suffixes while
    composing the full transient key at the WordPress API call.
  - Tightened `composer check:wporg` so variable-only transient keys fail even
    when the file contains a prefix guard elsewhere.
  - Updated release/testing docs to record that Plugin Check can miss this
    reviewer-policy pattern and that transient prefixes must be visible at the
    call site.
- **Verification**:
  - `git diff --check`
  - `composer test:all`
  - `composer release:verify`
  - `composer smoke:wp`
- **Boundary**:
  - Release compliance and quota key hygiene only. Core still does not execute
    final ability writes or own workflow/runtime behavior.

## 2026-06-08 — Core independence and operation classification sequence accepted

- **Module**: Core/channel adapter boundary and local consent classification.
- **Status**: Current implementation direction is not to merge Core into
  today's OpenClaw Adapter. Core remains the independent governance kernel while
  adapters standardize against a shared contract.
- **Completed**:
  - Added ADR-005 to keep Core independent and standardize channel adapters.
  - Added the Operation Classification Contract with `suggestion_only`,
    `local_admin_consent`, `strong_local_confirmation`, and
    `core_proposal_required`.
  - Recorded the required proof sequence: first implement the classifier, then
    prove one low-risk Toolbox local consent scenario and one high-risk Core
    proposal scenario.
  - Updated README, Agent/MCP entry, strategy, next-stage, and static
    contracts so future adapter types do not inherit OpenClaw-specific
    assumptions.
- **Next steps**:
  - Implement the shared classifier before changing individual write flows.
  - Use single image candidate -> featured image as the first low-risk
    `local_admin_consent` proof.
  - Use batch image selection, batch SEO, or batch article edits as the first
    `core_proposal_required` proof.
- **Boundary**:
  - ADR-004 remains a future packaging option. ADR-005 controls the current
    implementation sequence: contract-first, classifier-first, not merge-first.

## 2026-06-08 — Gutenberg block proposal keys preserved

- **Module**: Core proposal persistence for `update-post-blocks` governance
  handoff.
- **Status**: New `npcink-abilities-toolkit/update-post-blocks` proposals keep
  case-sensitive Gutenberg block object keys through storage and preflight
  hashing.
- **Completed**:
  - Added ability-aware proposal input sanitization for direct
    `update-post-blocks` input and nested `plan_to_proposal_batch`
    `write_actions[]`.
  - Preserved `blockName`, `innerBlocks`, `innerHTML`, `innerContent`, and
    attrs camelCase such as `contentSize`, `fontSize`, `letterSpacing`, and
    `textTransform` while still sanitizing block values.
  - Routed block `innerHTML` and `innerContent` strings through WordPress safe
    post HTML filtering.
  - Updated REST, security, plan-to-proposal, and testing contracts.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - Core still records proposal truth only. Existing proposals that were already
    stored with lowercased block keys must be regenerated before Adapter
    execution.

## 2026-06-08 — Suite consolidation and local admin consent boundary accepted

- **Module**: Core/Adapter/Toolbox product packaging and governance boundary.
- **Status**: Product direction now allows one Npcink AI plugin or suite entry
  while keeping proposal, approval, preflight, and audit truth inside a
  distinct Governance module.
- **Completed**:
  - Added ADR-004 to record suite consolidation and local admin consent.
  - Defined the risk ladder: suggestion-only, single visible local write,
    single high-impact write, and batch/external/automated/destructive writes.
  - Documented that low-risk, single-object, fully previewed WordPress admin
    actions may use local admin consent with audit instead of Core proposal
    approval.
  - Preserved Core proposal review for external, automated, batch,
    destructive, high-impact, or insufficiently previewed AI writes.
- **Next steps**:
  - Add a shared operation-classification helper before migrating write flows
    across modules.
  - Draft the concrete plugin/suite consolidation plan, including namespaces,
    module boot order, compatibility redirects, and migration strategy.
- **Boundary**:
  - Packaging can consolidate; authority cannot. Co-location does not permit
    Adapter, Toolbox, or product modules to bypass Governance for high-risk
    operations.

## 2026-06-07 — Create-draft HTML proposal input preserved safely

- **Module**: Core proposal persistence and create-draft governance contract.
- **Status**: `npcink-abilities-toolkit/create-draft` proposal input now keeps
  reviewed safe post HTML when `content_format=html`.
- **Completed**:
  - Added ability-aware proposal input sanitization so default structured
    payloads remain plain-text sanitized while create-draft `content` uses
    WordPress safe post HTML filtering only when explicitly marked as HTML.
  - Applied the same safe HTML preservation to create-draft actions nested in
    plan-to-proposal batch `write_actions[]`.
  - Aligned pending proposal dedupe/input hashes with the persistence sanitizer
    so HTML-bearing inputs are not compared after accidental tag stripping.
  - Updated create-draft, REST, security, and testing contracts.
  - Added WordPress smoke coverage for safe HTML preservation and unsafe tag
    removal.
- **Verification**:
  - `git diff --check`
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - Core still does not generate content, execute create-draft, own article UX,
    or store raw unsanitized HTML. Existing pending proposals that were already
    stored as plain text must be regenerated to carry HTML.

## 2026-06-07 — Release-facing admin activity log tightened

- **Module**: Core admin activity log and navigation URLs.
- **Status**: The audit/admin surface now uses release-facing labels and short
  read-only admin URLs.
- **Completed**:
  - Renamed the full audit tab surface to `Activity Log`.
  - Removed automatic nonce parameters from read-only admin GET navigation,
    including tab, detail, pagination, archive, and filter links.
  - Kept nonces on POST forms that change approval, lifecycle, policy, or
    app-key state.
  - Reworked the activity table to lead with user-facing activity labels,
    request ID, and compact context instead of raw event/ability columns.
  - Moved event, ability, client, caller, and correlation lookup into a
    collapsed technical filter section.
  - Synced admin docs, static contracts, and Chinese translation catalogs.
- **Verification**:
  - `msgfmt --check --check-format -o languages/npcink-governance-core-zh_CN.mo languages/npcink-governance-core-zh_CN.po`
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - Admin presentation and read-only navigation only. Core remains proposal,
    approval/rejection, preflight, and audit truth; no ability execution,
    workflow runtime, provider settings, or product workflow ownership was
    added.

## 2026-06-07 — Review queue UI reapplied to active local branch

- **Module**: Core admin review queue.
- **Status**: The simplified pending review surface is now present on the
  currently active local WordPress branch.
- **Completed**:
  - Reapplied the queue-first admin changes from
    `codex/core-workflow-replay-proof` onto
    `codex/article-optimization-core-handoff`, which is the branch currently
    loaded by the local WordPress plugin symlink.
  - Removed the top review metrics strip from the active branch.
  - Kept `Proposal ID` visible by default and moved target ability/source trace
    fields behind per-row technical details.
  - Preserved user-facing request labels, decision-oriented row actions, and
    non-prefilled bulk rejection notes.
- **Verification**:
  - `msgfmt --check --check-format -o languages/npcink-governance-core-zh_CN.mo languages/npcink-governance-core-zh_CN.po`
  - `composer test:all`
- **Boundary**:
  - Admin presentation only. No workflow runtime, ability execution, provider
    settings, credential storage, or product workflow ownership was added.

## 2026-06-07 — Core review queue scan hierarchy tightened

- **Module**: Core admin review queue.
- **Status**: The default pending review list now keeps proposal lookup
  identity visible while hiding lower-frequency machine trace fields.
- **Completed**:
  - Kept the top statistics strip removed from the review surface.
  - Made each pending row lead with a user-facing request label and default
    visible `Proposal ID`.
  - Kept target ability and source/caller/app trace metadata behind per-row
    technical details.
  - Removed repeated generic row instructions and stopped pre-filling bulk
    rejection with technical cleanup copy.
  - Synced admin surface docs, static contracts, and Chinese translation
    catalogs.
- **Verification**:
  - `msgfmt --check --check-format -o languages/npcink-governance-core-zh_CN.mo languages/npcink-governance-core-zh_CN.po`
  - `composer test:all`
  - `composer smoke:wp`
- **Boundary**:
  - Admin presentation and review ergonomics only. Core remains proposal,
    approval/rejection, preflight, and audit truth; no ability execution,
    workflow runtime, provider settings, or product workflow ownership was
    added.

## 2026-06-07 — Workflow replay consumer proof tightened

- **Module**: Core/Abilities workflow recipe consumption boundary.
- **Status**: Core now has a cheap static proof for the shared
  `npcink-abilities-toolkit` workflow replay fixture, in addition to the real
  WordPress smoke proof.
- **Completed**:
  - Strengthened `npcink-abilities-toolkit` workflow consumer proof so natural
    task examples route unambiguously to read-only recipe entrypoints and write
    targets stay out of expanded read chains.
  - Added Core static contract coverage that reads the sibling Toolkit
    `agent-workflow-replay.json`, rejects runtime/governance ownership fields,
    checks declarative recipe shape, and verifies write targets remain
    proposal handoff targets instead of entrypoints.
  - Updated Core testing strategy to record shared replay fixture structure and
    host-owned write boundary semantics as static contract coverage.
- **Verification**:
  - `/Users/muze/gitee/npcink-abilities-toolkit`: `composer test:all`
  - `/Users/muze/gitee/magick-ai-core`: `composer test:contracts`
  - `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
- **Boundary**:
  - Toolkit still owns declarative workflow recipe definitions and reusable
    WordPress abilities. Core consumes the shared replay/definition shape for
    proposal governance proof only; it does not route natural-language tasks,
    create a workflow registry, execute abilities, or own final writes.

## 2026-06-07 — Media optimization execution closure tightened

- **Module**: Core/Adapter/Abilities media optimization governance closure.
- **Status**: Media optimization proposals now expose clearer review,
  readiness, execution, and verification signals while keeping Core out of
  final WordPress writes.
- **Completed**:
  - Added Adapter-derived proposal detail fields for execution status,
    executability, non-executable reason, cached/audited preflight state, and
    media optimization readiness checks.
  - Added Abilities media replacement/adoption/restore verification summaries,
    separated reference repair rule counts from actual replacements, and made
    restore-media-backup apply reverse post-content reference repairs.
  - Improved Core media optimization batch proposal summaries with
    attachment, derivative, metadata, reference repair, one-approval, and
    rollback details for reviewers.
  - Synced Adapter stale smoke contracts from old site-summary/test-cleanup
    ability names to current site-info/nonproduction cleanup contracts.
- **Verification**:
  - `/Users/muze/gitee/magick-ai-core`: `composer test:all`
  - `/Users/muze/gitee/magick-ai-core`: `composer smoke:wp`
  - `/Users/muze/gitee/magick-ai-adapter`: `composer test:all`
  - `/Users/muze/gitee/magick-ai-adapter`: `composer smoke:wp`
  - `/Users/muze/gitee/npcink-abilities-toolkit`: `composer test:all`
  - `/Users/muze/gitee/npcink-abilities-toolkit`: `WP_CLI=/tmp/wp-cli.phar WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" WP_CLI_ERROR_REPORTING=8191 WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer smoke:wp`
- **Boundary**:
  - Core remains proposal, approval, preflight, and audit truth only.
    Adapter owns derived execution/readiness status because it performs the
    post-Core execution handoff. Abilities owns local write verification and
    media reference repair behavior. Cloud Addon remains runtime/detail and is
    not used as a second artifact or approval truth.

## 2026-06-07 — Media defaults moved to Toolbox

- **Module**: Core/Toolbox media optimization boundary.
- **Status**: Media derivative defaults now belong to Toolbox's Optimize
  Existing Image surface. Core no longer exposes a Media Policy admin tab,
  media derivative settings helper, or media derivative ability-input helper.
- **Completed**:
  - Added Toolbox-owned media optimization defaults, sanitization, one-time
    import from the old Core option, admin editing, and handoff input building.
  - Updated Toolbox media derivative handoff output to carry Toolbox policy
    context instead of Core policy helper/fallback state.
  - Removed Core media derivative settings storage, helper functions, and admin
    Media Policy tab.
  - Updated Core/Toolbox docs and static contracts so Core remains proposal,
    approval, preflight, and audit truth only.
- **Verification**:
  - `/Users/muze/gitee/magick-ai-toolbox`: `composer test:all`
  - `/Users/muze/gitee/magick-ai-core`: `composer test:all`
- **Boundary**:
  - Toolbox owns product settings, operator UI defaults, one-run overrides, and
    Core proposal handoff. Core still owns media optimization plan intake,
    proposal records, approval, commit preflight, and audit. Cloud remains
    runtime/detail only; final WordPress writes stay local through Abilities
    and Core governance.

## 2026-06-07 — Simplified Chinese translation baseline added

- **Module**: Core translation and localization packaging.
- **Status**: Core now ships a bundled `zh_CN` translation baseline while
  keeping English source strings, the `npcink-governance-core` text domain,
  public slugs, REST namespace, ability ids, and governance contracts unchanged.
- **Completed**:
  - Added the plugin `Domain Path: /languages` header and explicit bundled
    translation loading.
  - Generated `languages/npcink-governance-core.pot`.
  - Added and compiled `languages/npcink-governance-core-zh_CN.po` and `.mo`.
  - Added a Chinese translation glossary for stable governance terminology.
  - Updated bilingual publishing notes and static contracts for the bundled
    translation baseline.
- **Verification**:
  - `msgfmt --check --check-format -o languages/npcink-governance-core-zh_CN.mo languages/npcink-governance-core-zh_CN.po`
  - PO empty-translation and placeholder checks.
  - `git diff --check`
  - `composer test:all`
  - `composer check:wporg`
- **Boundary**:
  - Translation and packaging only. No naming migration, REST contract,
    database schema, governance lifecycle, ability intake, execution, workflow
    runtime, provider, credential, or product workflow changes.

## 2026-06-06 — Public naming contract aligned

- **Module**: Core naming and release identity.
- **Status**: Core keeps the WordPress.org plugin identity
  `Npcink Governance Core` / `npcink-governance-core`, while the shared admin
  suite displays as `Npcink AI` and the public explanation reads
  `Npcink AI governance layer for WordPress operations`.
- **Completed**:
  - Updated the plugin header, WordPress readme, README, product positioning,
    WordPress.org release copy, admin menu standard, and Core admin parent
    labels.
  - Kept the submenu label `Core` and slug/text-domain
    `npcink-governance-core` unchanged.
- **Verification**:
  - `composer test:all`
  - `composer check:wporg`
  - `composer release:verify`
  - `WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" composer smoke:wp`
- **Boundary**:
  - Naming and release identity only. No governance lifecycle, REST contract,
    execution, workflow runtime, provider, or ability ownership changes.

## 2026-06-06 — Release gate passed for shared admin slug

- **Module**: Core admin menu release readiness.
- **Status**: Core now targets the shared `npcink-ai` parent menu slug for the
  local admin surface and documents the same slug in the admin menu standard.
- **Completed**:
  - Updated the Core admin page parent slug.
  - Updated the admin menu standard and static contract assertion.
- **Verification**:
  - `composer release:verify`
  - `WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" composer smoke:wp`
- **Boundary**:
  - Admin surface routing only. No governance lifecycle, REST contract,
    execution, workflow runtime, provider, or ability ownership changes.

## 2026-06-06 — Third-party ability provider boundary clarified

- **Module**: Core ability intake and public integration contracts.
- **Status**: Core now documents that `npcink-abilities-toolkit` is the
  reference provider, while the base proposal lifecycle can govern any
  currently discoverable WordPress Abilities API provider with stable ids,
  schemas, permission callbacks, risk metadata, and dry-run previews.
- **Completed**:
  - Added a third-party Ability Provider guide for direct proposals, dry-run
    previews, app-key scopes, and final execution outside Core.
  - Clarified that `POST /proposals/from-plan` remains explicitly allowlisted
    and is not a generic workflow runtime for third-party fan-out.
  - Linked the provider-neutral guidance from README, product positioning,
    ability intake, and WordPress.org reviewer copy.
- **Verification**:
  - `composer test:all`
  - `composer check:wporg`
  - `git diff --check`
- **Boundary**:
  - Documentation/static-contract update only. Core still owns proposal
    records, review, preflight, audit, and app-key governance; providers and
    adapters still own abilities, dry-run execution, final writes, credentials,
    model routing, and product workflows.

## 2026-06-06 — WordPress.org review blockers repaired

- **Module**: Core WordPress.org release/readiness gate.
- **Status**: Core now addresses the Plugin Directory review feedback for REST
  permission callbacks, transient/key naming, and prepared SQL review patterns.
- **Completed**:
  - Kept every Core REST route behind explicit permission callbacks and added a
    local release guard for missing route permissions.
  - Replaced legacy `mai_core`/`mai_` token and fallback id prefixes with the
    `npcink_governance_core` identity.
  - Changed custom-table read queries to use `$wpdb->prepare()` with identifier
    placeholders and fixed-clause WHERE assembly.
  - Added a visible auto-approval transient prefix guard.
  - Extended `check:wporg` to catch the review patterns before upload.
  - Synced Core smoke/contracts with the current
    `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan` and
    `include_unattached_nonproduction_media` Abilities Toolkit contracts.
- **Verification**:
  - `composer test:all`
  - `composer check:wporg`
  - `composer release:verify`
  - `WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - This is release-hardening and contract alignment only. Core still owns
    proposal records, review, preflight, audit, and app-key governance; final
    WordPress writes remain outside Core.

## 2026-06-05 — Media optimization reference repair contract tightened

- **Module**: Core media optimization plan-to-proposal contract.
- **Status**: Core now rejects media optimization plans that split inline
  post-content media reference repair into a separate post-content write
  action.
- **Completed**:
  - Kept `magick-ai/build-media-optimization-plan` as one single-attachment
    `plan_to_proposal_batch` with metadata and derivative adoption actions.
  - Added a fail-closed guard against separate `magick-ai/patch-post-content`,
    `magick-ai/update-post`, or `magick-ai/update-post-blocks` repair actions
    in the same media optimization user intent.
  - Documented that `content_reference_repairs` evidence belongs inside the
    derivative adoption ability preview/commit contract.
- **Verification**:
  - `composer test:all`
  - `WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - Core still only creates and governs proposal records. Cloud may provide
    derivative artifacts, but final approval, adoption, inline reference
    repair, and WordPress writes remain local and outside Core execution.

## 2026-06-04 — User-intent batch proposal contracts added

- **Module**: Core plan-to-proposal governance contracts.
- **Status**: Core now accepts explicit, bounded user-intent batch plan shapes
  for local article draft batches and single-attachment media optimization.
- **Completed**:
  - Added `magick-ai-toolbox/build-article-batch-write-plan` as a separate
    local Article Assistant Workbench handoff that can create one
    `plan_to_proposal_batch` with 2 to 5 draft-only
    `magick-ai/create-draft` actions.
  - Added `magick-ai/build-media-optimization-plan` as a single-attachment
    media optimization handoff that can combine `update-media-details` with
    derivative adoption into one batch proposal.
  - Kept the existing P0 `magick-ai-toolbox/build-article-write-plan`
    single-draft contract intact.
  - Added fail-closed coverage for valid batch proposals and rejection of
    missing explicit batch mode, publish requests, missing media derivative
    actions, and multi-attachment media optimization.
  - Updated the smoke gate to recognize the current
    `magick-ai-abilities/npcink-abilities-toolkit.php` plugin basename.
- **Verification**:
  - `composer test:all`
  - `WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - Core still only accepts plan output, creates proposal records, records
    approval/rejection/preflight/audit, and returns `commit_execution=false`.
    This does not add article generation, media processing, final WordPress
    writes, workflow/task queues, batch execution consoles, Cloud approval,
    Cloud writing, or automatic approval.
- **Next Steps**:
  - Implement the new plan abilities in the owning projects:
    `magick-ai-toolbox` for article batch draft plans and
    `magick-ai-abilities` or the local media product surface for media
    optimization plans.
  - Add Adapter execution profiles that validate each batch action after Core
    approval and commit preflight.

## 2026-06-03 — Article assistant complexity budget documented

- **Module**: Core cross-project article recipe contracts.
- **Status**: The article surface is now explicitly constrained as a local
  Article Assistant Workbench and Ability recipe, not an article generation
  product or Cloud writing feature.
- **Completed**:
  - Added a recipe complexity budget for `article_draft_v1`.
  - Added P0 product-budget language for one local article, one reviewed draft
    proposal, no Cloud writing, no batch writing, no workflow runtime, and no
    automatic approval.
  - Added rejected Cloud product language to prevent future drift back into
    hosted article generation or import.
- **Boundary**:
  - Documentation/contract update only. It does not add routes, execution,
    Cloud writing, queues, workflow runtime, approval automation, or WordPress
    writes inside Core.

## 2026-06-03 — Article writing reduced to local Ability recipe

- **Module**: Core recipe and Cloud writing boundary documentation.
- **Status**: Article drafting is now documented as the local
  `article_draft_v1` Ability recipe instead of a special Core feature or Cloud
  writing product.
- **Completed**:
  - Added `docs/ability-recipe-orchestration-contract.md` to define recipe
    orchestration over standard Abilities.
  - Reclassified `docs/cloud-bulk-article-run-contract.md` as a prohibited and
    deprecated planning contract so Cloud does not generate article drafts, SEO
    copy, bulk writing artifacts, or `article_write_plan` candidates.
  - Updated README, article writing, plan-to-proposal, and next-stage docs plus
    static contracts.
- **Verification**:
  - `composer test:all` passed.
  - `git diff --check` passed.
- **Boundary**:
  - This is documentation/contract reduction only. It does not add routes,
    execution, Cloud writing, queues, workflow runtime, approval automation, or
    WordPress writes inside Core.

## 2026-06-03 — Core observability trigger coverage hardened

- **Module**: Core local observability metadata for proposal and commit
  preflight REST operations.
- **Status**: Core now treats the real `core.proposal.*` and
  `core.commit.preflight` event names as the canonical local operation
  vocabulary, emits warning status for expected governance preflight blocks,
  and bounds observability payloads to safe scalar metadata.
- **Completed**:
  - Kept `core.proposal.create`, `core.proposal.plan_ingest`,
    `core.proposal.approve`, `core.proposal.reject`, and
    `core.commit.preflight` as canonical event kinds instead of adding alias
    events.
  - Added observability payload allowlisting so proposal input, preview,
    caller payloads, approval notes, generated content, and policy payloads
    cannot pass through Core's local event bridge.
  - Added fail-closed/controller coverage proving create, approve, reject,
    successful preflight, blocked preflight, and plan-ingest failure emit
    metadata-only events with stable status and error-code behavior.
  - Updated Cloud plugin-observability docs to consume the canonical Core
    event names already observed in real smoke.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check` in `magick-ai-core`
  - `git diff --check` in `magick-ai-cloud`
- **Boundary**:
  - Core still only emits local `magick_ai_observability_event` metadata.
    Cloud Addon remains the only uploader. This does not add Cloud approval,
    rejection, preflight mutation, proposal mutation, remote telemetry client
    code, workflow runtime, or WordPress writes inside Core.

## 2026-06-03 — Cloud bulk article boundary contract started

- **Module**: Core article workflow and Cloud boundary documentation.
- **Status**: Core now documents the intended split for large article work:
  Cloud may prepare bulk article artifacts and run evidence, but selected items
  must return to the local `article_write_plan` -> Core proposal -> approval
  -> commit-preflight -> WordPress Abilities API path for draft creation.
- **Completed**:
  - Added `docs/cloud-bulk-article-run-contract.md` with the
    `bulk_article_run_v1` planning shape, status vocabulary, local import flow,
    and hard guardrails against Cloud direct publishing.
  - Linked the contract from README, article workflow, plan-to-proposal, and
    next-stage docs.
  - Added static contracts for the Cloud bulk article boundary.
- **Verification**:
  - `composer test:all`
  - `git diff --check`
- **Boundary**:
  - This is contract/documentation only. It does not add Core REST routes,
    Cloud callbacks, queueing, worker state, workflow runtime, automatic
    approval, final execution, or WordPress writes inside Core.

## 2026-06-02 — Article writing plan review summary added

- **Module**: Core admin proposal detail.
- **Status**: Core now summarizes `preview.article_workflow` in the proposal
  review context so an approver can see the article title/topic, risk,
  readiness, blocked-claim count, final write ability, final write path,
  direct-write state, and required artifact availability before opening raw
  JSON.
- **Completed**:
  - Added a compact Article workflow summary to the existing proposal detail
    Review Context table.
  - Kept the complete caller/input/preview payload behind the existing raw
    proposal disclosure.
  - Documented the admin review behavior in Core governance operability docs
    and added static contracts.
- **Verification**:
  - `composer test:all`
  - `composer smoke:wp`
  - `git diff --check`
- **Boundary**:
  - This is review presentation only. It does not add article generation,
    auto approval, final execution, workflow runtime, queueing, Cloud control
    plane behavior, or direct WordPress writes inside Core.

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

## 2026-06-16 — Article template layout profile v0.3 intake

- **Module**: block theme site proposal intake / bounded template layout
  contract
- **Status**: Core now accepts `article_standard@0.3` from the Abilities
  Toolkit template profile compiler.
- **Completed**:
  - Updated the block theme layout contract allowlist from
    `article_standard@0.2` to `article_standard@0.3` after the v0.2 visual
    acceptance pass showed the `base-2` title/navigation bands were not visible
    enough in the active Twenty Twenty-Five palette.
  - Updated fail-closed fixtures and REST/intake docs so proposal creation
    matches the Toolkit compiler output.
  - Kept Core as proposal/intake authority only; no template generation,
    workflow runtime, proxy execution, or final write execution was added.
- **Verified**:
  - `composer test:all` passed.
  - `composer smoke:wp` passed against the LocalWP `magick-ai` site.
  - `composer validate --no-check-publish` passed.
- **Next recommended step**:
  - Use OpenClaw to regenerate an article template proposal with
    `article_standard@0.3`, execute it through Adapter commit intent, then run
    visual acceptance to confirm the article page no longer needs manual review
    for low background variety.
