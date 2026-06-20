# WordPress.org Listing Draft - English

## Plugin Name

Npcink Governance Core

## Short Description

Governance, approval, commit preflight, and audit logs for AI-assisted
WordPress operations.

## Tags

ai, governance, approval, audit, abilities

## Description

Npcink Governance Core is the WordPress AI operation governance layer for
ability intake, proposal records, approval boundaries, commit preflight,
scoped app keys, and audit logs. It gives site administrators and trusted host
plugins a reviewable approval layer before AI-assisted actions are committed
to a WordPress site.

It discovers agent-callable abilities from WordPress and provider plugins, then
adds host-side policy and governance around proposed operations. Core records
proposals, supports approval and rejection, returns commit-preflight context,
and stores audit evidence so site administrators and trusted hosts can review
what was requested, approved, rejected, or prepared for commit.

Core is part of the Npcink plugin family, but it stays focused on governance.
Ability definitions belong in Npcink Abilities Toolkit or other provider plugins.
Productized OpenClaw connection belongs in trusted adapter. Cloud service
connection belongs in cloud connector.

Npcink Governance Core does not generate content, route models, run MCP or workflow
runtimes, store provider credentials, proxy ability execution, or perform final
WordPress mutations.

## Key Features

- Discover available WordPress abilities for governance intake.
- Store proposals for AI-assisted WordPress operations.
- Support approve and reject decisions with audit evidence.
- Return commit-preflight context before a trusted host or adapter performs final writes.
- Provide scoped app-key access for trusted governance clients.
- Record proposal creation, policy evaluation, approval, rejection,
  commit-preflight, and execution-result handoff events.
- Separate governance from ability definition, model routing, transport, cloud
  execution, and final write execution.

## Who This Is For

- WordPress administrators who need reviewable AI operation governance.
- Host plugins and adapters that need proposal approval and commit preflight.
- Developers separating ability execution from governance decisions.
- Npcink deployments that need a local WordPress control-plane boundary.

## Requirements

- WordPress 7.0 or later with the WordPress Abilities API available for full
  ability intake.
- PHP 8.0 or later.

## Series Boundary

In the Npcink plugin family:

- Npcink Abilities Toolkit owns ability definitions and callbacks.
- npcink-governance-core owns governance, approval, preflight, and audit.
- trusted adapter owns OpenClaw channel adaptation.
- cloud connector owns cloud service connection.

This separation keeps Core focused on governance and keeps execution,
transport, cloud services, and ability content in their own layers.

## Privacy and Data

Npcink Governance Core does not call external AI services, load remote assets,
or send site data to third parties. It stores governance records locally in
WordPress database tables, including proposals, audit events, app-key metadata,
and rate-limit state. App-key secrets are hashed before storage, and one-time
bearer tokens are shown only when created.

## FAQ Draft

### Who is this plugin for?

Site administrators, host plugins, adapters, and developers that need
reviewable governance for AI-assisted WordPress operations.

### Is this an AI content generator?

No. Core does not write articles, generate media, create SEO copy, reply to
comments, choose AI models, or store provider credentials.

### Does Core execute AI writes?

No. Core records governance proposals and returns commit-preflight context.
Final writes belong to a trusted host, adapter, or runtime outside Core.

### What is commit preflight?

Commit preflight is the governance check after approval and before final write
execution. It returns bounded context, correlation data, and input binding for
the trusted downstream component.

### Does Core send data to an external AI service?

No. Core does not call external AI services and does not send site data to
third parties.
