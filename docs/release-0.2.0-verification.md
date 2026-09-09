# 0.2.0 Release Verification

Status: Git release published; WordPress.org SVN publication remains a
separately authorized release-owner operation.

## Candidate Revisions

| Repository | Version | Revision | Conventional tag |
| --- | --- | --- | --- |
| Governance Core | `0.2.0` | `7f2c6bf5b3f810f65f2e3f2d396ecfdf78c07ee6` | `v0.2.0` published and verified |
| AI Client Adapter | `0.3.3` | `8800b8e4c4651c5d4dff759c3050135d74968927` | `v0.3.3` published and verified |
| Abilities Toolkit | `0.5.5` | `3e237d91cbc1f135c559d47d1e20106aee62c1bb` | `0.5.5` published and verified |

## Verified

- Core `composer test:all`, real WordPress smoke, PHPStan, strict packaged
  Plugin Check, release packaging, and ZIP integrity passed.
- Core release packaging now normalizes timestamps and ZIP metadata, sorts
  archive entries, and has a default-gate regression proving two equivalent
  builds produce the same SHA-256.
- On M4 Docker 29.7.2, the exact Core ZIP was installed and exercised on
  WordPress 7.0/PHP 8.0 and WordPress 7.0/PHP 8.5; both profiles passed 1,426
  Core smoke assertions, with no duplicate-key or WordPress database errors.
- The signed Adapter fixture created, approved, executed, read back, and
  cleaned a real draft through the intended Adapter -> Core -> Toolkit chain.
- Toolkit `composer test:all` passed with 6743 assertions and PHPStan reported
  no errors.
- On M4 Docker 29.7.2, the Toolkit candidate passed 441 smoke assertions on
  WordPress 6.9.4/PHP 8.0 and 441 on WordPress 7.0/PHP 8.5.
- The central six-repository gate passed with
  `--run-gates --fail-on-dirty` for every configured repository root.
- The full cross-repository release acceptance passed in one invocation with
  exact Toolkit M4 evidence and the commit-enabled signed Adapter fixture.
- `composer rc:version-matrix` passed after publication and found all three
  conventional tags pointing at the exact candidate heads.

## Final Core Artifact

- Path: `build/npcink-governance-core.zip`
- SHA-256: `608676d35cc6ea1934513a9a76a822e3e62ca646339c67a62cd73845e5647e48`
- ZIP integrity: passed.
- Reproducibility: two equivalent builds passed with the same SHA-256.

## Post-Merge M4 Evidence

- Source revision: `7f2c6bf5b3f810f65f2e3f2d396ecfdf78c07ee6`
- Source archive SHA-256: `f68903cc6a2a0e865672b973a1c09eff8ba2a180f983d47316e4763c757ebaa6`
- Core package SHA-256: `608676d35cc6ea1934513a9a76a822e3e62ca646339c67a62cd73845e5647e48`
- Toolkit archive SHA-256: `00af0bf9c7775c6722b40a4ba05d060c39afd007da3acb96a656f6be71edc777`
- M4 Docker Server: `29.7.2`
- Profiles: WordPress 7.0/PHP 8.0 and 7.0/PHP 8.5, 1,426 assertions each,
  installed from ZIP.

## Remaining Operational Follow-Up

1. Additional historical worktrees outside the configured release roots retain
   unrelated owner-held changes. They do not change the tagged candidates, but
   global workspace cleanup must not be claimed until each owner resolves or
   archives them.
2. Local WP-CLI under PHP 8.5 emits repeated third-party color-library
   deprecation messages. They did not fail any product assertion, but they make
   release logs noisy and should be isolated or suppressed in CI tooling.
3. Toolkit packaged Plugin Check has no errors but retains known
   translation-loading and bounded database-query warnings. These should stay
   documented and be re-evaluated before a WordPress.org submission.
4. Dependency-update pull requests remain separate maintenance work and are
   not part of the tagged Core, Adapter, or Toolkit candidates.

## Publication Decision

The coordinated Git release is complete. The conventional annotated tags were
pushed and verified against the exact accepted commits. WordPress.org SVN
publication was not performed and remains an explicit release-owner action.

Use [Release Closeout Standard](release-closeout-standard.md) for future
releases. No product workflow, workflow runtime, queue, MCP runtime, provider
credential, or final write execution belongs in Core as part of publication or
workspace cleanup.
