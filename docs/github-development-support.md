# GitHub Development Support

Status: active.

GitHub is the development repository for `npcink-governance-core`. WordPress.org
SVN remains release-only.

## Enabled Repository Practices

- Pull requests use the repository template to record scope, Core boundary,
  verification, and release impact.
- Issues use focused templates for bugs, boundary reviews, release tasks, and
  WordPress.org review findings.
- Dependabot checks GitHub Actions and Composer metadata weekly.
- `Core CI` runs static verification on pull requests and pushes to `master`.
- `Release Package` is a manually triggered GitHub Actions workflow that builds
  a downloadable plugin zip artifact for review.

## Branch Protection

Protect `master` after the current setup commit is pushed:

- require pull requests before merging;
- require the `Static contracts` status check from `Core CI`;
- block force pushes and branch deletion;
- allow administrator bypass only for emergency release repair.

The protected branch gate is intentionally static. It does not replace the local
WordPress smoke gate, because `composer smoke:wp` depends on the LocalWP site,
WP-CLI runtime, database socket, and installed `npcink-abilities-toolkit`.

## Release Package Workflow

Use the manual GitHub workflow only to build and inspect a package artifact:

```text
Actions -> Release Package -> Run workflow -> version
```

The workflow checks:

- Composer metadata;
- static Core contracts;
- WordPress.org review guard;
- plugin header, version constant, and `readme.txt` Stable tag alignment;
- release zip root.

It does not run `composer smoke:wp`, `composer plugin-check:release`, or
WordPress.org SVN sync. Real WordPress.org releases must still run the local
release gate:

```sh
composer prepare:release -- --version <version>
```

After the local gate passes, sync WordPress.org SVN with the release helper
documented in `docs/wordpress-org-release-gate.md`.

## GitHub Releases

Create GitHub Releases as release records after the WordPress.org release is
published. The release notes should include:

- Git commit and WordPress.org SVN revision;
- public WordPress.org plugin URL;
- verification gates that passed;
- whether listing assets changed.

Do not use GitHub Releases as the WordPress.org publishing path.
