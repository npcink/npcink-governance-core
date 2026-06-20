# WordPress.org Listing Draft - English

## Plugin Name

Npcink Governance Core

## Short Description

Governance, approval, commit preflight, and audit logs for AI-assisted
WordPress operations.

## Tags

ai, governance, approval, audit, abilities

## Description

Npcink Governance Core is the WordPress AI operation governance layer for ability
intake, proposals, approval boundaries, commit preflight, scoped app keys, and
audit logs.

It discovers agent-callable abilities from WordPress and provider plugins, then
adds host-side policy and governance around proposed operations. Core records
proposals, supports approval and rejection, performs commit preflight, and
stores audit evidence so site administrators and trusted hosts can review what
was requested, approved, rejected, or prepared for commit.

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
- Run commit preflight before a trusted host or adapter performs final writes.
- Provide scoped app-key access for trusted governance clients.
- Keep recent governance audit records for review and troubleshooting.
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
