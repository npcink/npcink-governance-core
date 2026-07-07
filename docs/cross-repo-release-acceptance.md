# Cross-Repo Release Acceptance

Status: active local release gate.

This gate freezes the current Npcink AI governance stack before release work.
It proves that the three repository roles still compose without moving
responsibility across boundaries.

## Purpose

Run this gate before publishing or tagging a release candidate that depends on
more than one Npcink AI repository.

The gate is intentionally cross-repository:

| Repository | Release role | Must prove |
| --- | --- | --- |
| `npcink-governance-core` | Governance layer | Core records proposals, approvals, preflights, execution outcomes, and audit truth without executing target abilities. |
| `npcink-ai-client-adapter` | Thin channel layer | Adapter exposes signed AI-client routes, delegates governance to Core, executes only after Core approval/preflight, and records outcomes back to Core. |
| `npcink-abilities-toolkit` | Ability implementation layer | Toolkit owns WordPress Abilities API definitions, schemas, metadata, dry-run previews, and host-governed callbacks without approval or audit truth. |

## Command

From `npcink-governance-core`:

```bash
composer acceptance:cross-repo-release
```

The wrapper runs:

```bash
scripts/cross-repo-release-acceptance.sh
```

Default sibling repository paths:

```text
/Users/muze/gitee/npcink-governance-core
/Users/muze/gitee/npcink-ai-client-adapter
/Users/muze/gitee/npcink-abilities-toolkit
```

Override paths only when the repositories are checked out elsewhere:

```bash
NPCINK_AI_CLIENT_ADAPTER_ROOT=/path/to/npcink-ai-client-adapter \
NPCINK_ABILITIES_TOOLKIT_ROOT=/path/to/npcink-abilities-toolkit \
composer acceptance:cross-repo-release
```

The default LocalWP target is the primary deployment and release-test site:

```text
/Users/muze/Local Sites/magick-ai/app/public
https://magick-ai.local/
```

The wrapper also forwards `WP_PATH`, `WP_CLI`, `WP_CLI_PHP`,
`WP_CLI_MYSQL_SOCKET`, and `WP_DB_SOCKET` to each repository's existing smoke
and Plugin Check scripts.

`/Users/muze/Local Sites/npcink/app/public` / `http://npcink.local/` may still be
used as an explicit `WP_PATH` override for temporary isolated rename tests, but
it is not the canonical release-test target.

## Acceptance Matrix

The release candidate is acceptable only when all rows pass.

| Layer | Gate |
| --- | --- |
| Core source contracts | `composer validate --no-check-publish`, `composer test:all` |
| Core WordPress integration | `composer smoke:wp` |
| Core release package scan | `composer release:verify`, `composer package:release` |
| Adapter source contracts | `composer test:all` |
| Adapter WordPress integration | `composer smoke:wp` |
| Adapter release package scan | `composer release:verify`, `composer package:release`, `composer smoke:package-install` |
| Adapter real-chain fixture | `MAA_ADAPTER_FIXTURE_ALLOW_COMMIT=1 composer accept:local-ai-client-fixture` |
| Toolkit source contracts | `composer test:all`, `composer analyse:phpstan` |
| Toolkit release and WordPress integration | `composer release:verify`, `composer smoke:wp` |
| AI write classification regression | Core `composer smoke:wp` plus the Toolbox local-consent and article/media batch smokes when AI-assisted write entrypoints are in scope |

After this gate passes, run
[Release Candidate Version Matrix](release-candidate-version-matrix.md):

```bash
composer rc:version-matrix
```

That follow-up freezes which Core, Adapter, and Toolkit plugin versions and
commits the accepted candidate actually represents.

## Real-Chain Proof

The Adapter fixture is the stack-level proof. It must show:

```text
AI client / Adapter
-> Core proposal
-> Core approval
-> Core commit preflight
-> Adapter execution profile
-> WordPress Abilities API
-> Core record-execution
-> Adapter status/readback
```

The fixture creates a local draft through the approved
`npcink-abilities-toolkit/create-draft` ability and then deletes that fixture
post through WP-CLI. A failure before the first status response is usually a
LocalWP setup problem, inactive Adapter plugin, or profile/signature mismatch;
classify that before changing code.

## Boundary Checks

Passing this gate does not authorize any boundary expansion.

- Core must not add final execution routes, workflow runtime, task queues,
  MCP runtime, Agent Gateway catalogs, provider credential storage, model
  routing, or product UX.
- Adapter must not define reusable abilities, own approval/audit truth, become
  a generic workflow executor, or store model/provider credentials.
- Toolkit must not store approval records, audit records, runtime execution
  state, billing, model routing, or Core app-key policy.
- Generic AI plugin output accepted through the WordPress editor must remain
  outside Core proposal review. Local Admin Consent must stay limited to
  bounded present-admin single-object proofs with audit evidence. External,
  automated, delegated, high-risk, incomplete-preview, or batch writes must
  stay in Core proposal review.

If a future release needs one of those responsibilities, stop and write a
boundary note or ADR in the owning repository before implementing it.

## Diagnostic Options

For local script diagnostics only:

```bash
scripts/cross-repo-release-acceptance.sh --allow-dirty
scripts/cross-repo-release-acceptance.sh --skip-packaging
scripts/cross-repo-release-acceptance.sh --skip-adapter-fixture
```

Do not use those skips for an actual release candidate.
