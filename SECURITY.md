# Security Policy

## Supported Versions

Only the current development branch and the latest WordPress.org release are
supported for security fixes.

## Reporting A Vulnerability

Report suspected vulnerabilities through GitHub private vulnerability reporting
when it is available for this repository. If private reporting is unavailable,
open a minimal issue that says a private security report is needed, but do not
include exploit details, secrets, tokens, site URLs, database dumps, or user
data in the public issue.

Useful private report details include:

- affected plugin version or commit;
- WordPress and PHP versions;
- exact route, capability, or lifecycle path involved;
- whether proposal, approval, commit preflight, audit, app-key, or sensitive
  read authorization state is affected;
- reproduction steps that avoid real secrets and production data.

Core must fail closed for governance persistence and authorization defects.
Security fixes should include the narrowest useful verification gate and, when
the issue is statically checkable, a regression guard.
