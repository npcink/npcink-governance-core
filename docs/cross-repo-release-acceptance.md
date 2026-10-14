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

When the release-test site uses the temporary M4 Ollama acceptance path,
export the provider values in the gate process so the signed fixture does not
fall back to its default model:

```bash
MAA_ADAPTER_PROVIDER_LOG_PROVIDER=ollama \
MAA_ADAPTER_PROVIDER_LOG_MODEL=qwen2.5:1.5b \
composer acceptance:cross-repo-release
```

The one-time SDK loader and loopback tunnel are acceptance fixtures owned by
the Adapter operator. Remove them, the marker loader, and the draft after the
run; the normal LocalWP provider registry must return to its configured state.
This setup does not add a provider or credentials to Core.

The wrapper runs:

```bash
scripts/cross-repo-release-acceptance.sh
```

## Latest Matrix Refresh

On 2026-10-14, the Cloud Addon shared root was checked twice over a three
minute interval and remained clean and unchanged while it was one commit behind
`origin/master`. The remote release-record commit `122ac79` was therefore
checked out in a dedicated detached worktree. Running
`composer quality:matrix:run -- --fail-on-dirty` with that worktree supplied via
`--repo-path=npcink-cloud-addon:<absolute-worktree-path>` passed all 7/7
repositories at `2026-10-14T04:48:31Z`. The shared Addon root was not switched,
pulled, or edited.

The Addon root was subsequently fast-forwarded to `122ac79` with
`git merge --ff-only origin/master` and remains clean. The classification
regression then exposed two stale Toolbox smoke assumptions: the Local Admin
Consent fixture lacked the present-admin nonce/cookie/origin envelope, and the
batch fixture looked for Toolbox abilities in the Toolkit-only registry. Those
fixtures are corrected in the isolated Toolbox commit `da02356`; its local
classification lanes 2 and 3 and full `composer test:all` pass. A matrix run
using that Toolbox worktree plus a clean AI Cloud `origin/master` worktree
passed all 7/7 repositories at `2026-10-14T06:25:15Z`.

The default matrix remains blocked when the AI Cloud shared root contains its
owner's uncommitted `deploy/remote-refresh-providers.sh`; the clean detached
worktree proves the exact remote head instead. Lane 1 of the classification
regression still requires a real present administrator to perform the editor
action and cannot be replaced by a fixture.

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

When the AI write classification row is in scope, record the run with
[AI Write Classification Regression Evidence](history/ai-write-classification-regression-evidence.md)
instead of relying on memory or chat-only notes.

After this gate passes, run
[Release Candidate Version Matrix](release-candidate-version-matrix.md):

```bash
composer rc:version-matrix
```

That follow-up freezes which Core, Adapter, and Toolkit plugin versions and
commits the accepted candidate actually represents.

For the full order from candidate freeze through M4 evidence, tag verification,
and worktree audit, use
[Release Closeout Standard](release-closeout-standard.md). Acceptance success
does not by itself prove that tags were pushed or that every registered
worktree is clean.

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

If a signed status call returns 401 after the LocalWP database has been reset,
confirm that WordPress still contains the approved public-key record. A local
profile can remain syntactically valid after its server-side public key has
disappeared. Re-pair the profile instead of bypassing the fixture, and never
print private key material while diagnosing it.

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
