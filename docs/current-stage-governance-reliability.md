# Current Stage Governance Reliability

Status: active standard.

This document summarizes the current-stage reliability rules for Magick AI
Core. It consolidates the accepted boundary from ADR-003 and the implemented
fail-closed behavior that protects proposal, app-key, and audit integrity.

## Current Product Position

Core is the WordPress AI operation governance layer. In the current stage Core
owns:

- ability intake and target validation;
- proposal records;
- approval, rejection, expiration, archive, and reopen lifecycle state;
- commit preflight authorization context;
- audit logs;
- scoped governance app keys for trusted external clients;
- minimal REST and admin surfaces for governance.

Core does not own final execution. Adapter and product plugins execute approved
WordPress writes through WordPress Abilities API after Core approval and commit
preflight. Core must continue returning `commit_execution=false`,
`core_proxy_execute=false`, and `execution_handoff.executor=adapter_after_core_preflight`.

## App-Key Scope

Core app keys are a minimal external governance identity. They let trusted
clients call Core governance routes with scoped access and rate limits when
WordPress user authentication is not the right integration shape.

For the current stage, app-key rotation and expiry automation are deferred.
They are not the next priority until Adapter or another real external client
needs long-lived credential lifecycle management. Core must first keep the
existing credential path reliable: raw secrets are shown once, stored only as
hashes, scoped narrowly, rate limited, auditable, and revocable.

App keys must not become an OpenClaw onboarding product surface, provider
credential store, billing identity, cloud control plane, or workflow runtime
catalog.

## Fail-Closed Rules

Governance must fail closed when Core cannot persist the record needed to prove
what happened.

- If proposal row insert fails, return
  `magick_ai_core_proposal_insert_failed` and report no proposal success.
- If proposal creation succeeds but `proposal.created` audit cannot be written,
  delete the unaudited proposal and return
  `magick_ai_core_proposal_audit_failed`.
- If approve or reject changes proposal status but the decision audit cannot be
  written, roll the proposal back to its previous status and return
  `magick_ai_core_proposal_decision_audit_failed`.
- If archive or reopen changes proposal status but the lifecycle audit cannot
  be written, roll the proposal back to its previous status and return the
  matching stable audit failure error.
- If app-key row insert fails, return `magick_ai_core_app_insert_failed` and
  report no app-key success.
- If app-key creation succeeds but `app.created` audit cannot be written,
  revoke the new key, do not return the one-time token, and return
  `magick_ai_core_app_audit_failed`.

Read/list audit events may be best-effort. Write-like governance lifecycle
events must be auditable before Core reports success.

## Commit Preflight Binding

Commit preflight is authorization context, not execution. It binds Adapter
handoff to the approved proposal state by returning:

- `correlation_id`;
- `approved_input_hash`;
- `approved_preview_hash`;
- `policy_version`;
- `execution_handoff`.

Adapter should carry the correlation id and hashes forward when it executes the
approved ability through WordPress Abilities API. Core must not turn these
fields into a final execution token or proxy execution route.

## Test Requirements

Current-stage reliability is protected by three gates:

- `composer test:contracts` keeps documentation, routes, public lifecycle, and
  forbidden boundaries aligned.
- `composer test:fail-closed` injects database and audit persistence failures
  against Core repository/service/controller classes.
- `composer smoke:wp` proves activation, tables, REST behavior, app auth, and
  `magick-ai-abilities` integration in a real WordPress site when runtime
  behavior changes.

The fault-injection gate must cover both the returned error code and the local
cleanup or rollback effect. A test that only checks for a string in source code
is not sufficient for fail-closed behavior.
