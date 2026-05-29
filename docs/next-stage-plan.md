# Next Stage Plan

Status: active planning guide.

This plan sequences the next work after the MVP scaffold and real WordPress
smoke baseline.

## Current Baseline

Implemented:

- plugin activation;
- proposal and audit tables;
- ability intake from `magick-ai-abilities`;
- REST capability list;
- proposal create/list;
- proposal approve/reject;
- audit list;
- static contracts;
- real WordPress smoke.

Not implemented:

- proposal detail endpoint;
- audit filtering;
- commit preflight;
- final commit execution;
- app key authentication;
- scope/rate-limit policy;
- admin approval UI beyond a read-only overview.

## Recommended Order

### 1. Proposal Detail Endpoint

Goal: fetch one proposal by id.

Routes:

- `GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}`

Acceptance:

- returns 200 for existing proposal;
- returns 404 for missing proposal;
- covered by static contract and WordPress smoke.

### 2. Audit Filters

Goal: make audit useful for proposal review.

Filters:

- `proposal_id`
- `event_name`
- `limit`

Acceptance:

- audit list can return only events for one proposal;
- audit list can return one event type;
- no raw secrets in metadata.

### 3. Commit Preflight Contract

Goal: prepare for final commit without executing writes yet.

Route:

- `POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight`

Acceptance:

- fails unless proposal is approved;
- confirms ability still exists;
- returns approval-commit context preview;
- does not execute the ability;
- never accepts `confirm_token` or `write_confirmed`.

### 4. Minimal Admin Approval UI

Goal: let humans review pending proposals inside WordPress.

Screen:

- Tools -> Magick AI Core

Acceptance:

- list pending proposals;
- show proposal detail;
- approve/reject with nonce and capability check;
- no content-generation UI.

### 5. App Auth, Scope, And Rate Policy

Goal: move beyond `manage_options`-only MVP.

Acceptance:

- app identity model documented first;
- scope rules are derived from ability metadata and Core policy;
- rate limits are stored in Core policy, not provider plugins;
- all decisions emit audit events.

## Stop Conditions

Stop and write a boundary note if a task tries to add:

- article/media/comment generation UX;
- model/provider settings;
- workflow runtime or queues;
- Agent Gateway or MCP surfaces;
- direct provider credential storage;
- final commit execution before preflight and idempotency contracts exist.

