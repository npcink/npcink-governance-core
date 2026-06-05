# ADR-001: Rebuild Core As A Governance Layer

## Status

Accepted.

## Date

2026-05-29

## Context

The previous `magick-ai` plugin accumulated several product directions in one
Core package:

- Open Platform runtime;
- Agent Gateway catalog surfaces;
- workflow definitions;
- content, media, comment, SEO, and operations products;
- Settings workbenches;
- batch and maintenance surfaces;
- confirmation compatibility behavior.

The current product positioning is narrower:

> Npcink Governance Core is the WordPress AI operation governance layer.

Continuing to delete old surfaces from the previous plugin was increasingly
risky. The old codebase remained useful as a source of contracts and lessons,
but it was no longer a clean implementation base for the new Core.

## Decision

Create a new standalone `npcink-governance-core` plugin that implements only governance
responsibilities:

- ability intake;
- proposal records;
- approval/rejection status;
- future approval-commit authorization;
- audit logs;
- minimal governance REST/admin surfaces.

The old `magick-ai` plugin is treated as a reference source, not as code to copy
into the rebuilt Core.

## Alternatives Considered

### Continue deleting old Core surfaces

Pros:

- preserves historical behavior while slimming;
- avoids a new repository.

Cons:

- keeps old architecture pressure;
- risks retaining hidden runtime ownership;
- makes new AI sessions more likely to reintroduce old Agent Gateway,
  workflow, batch, and Settings concepts.

Rejected because the desired Core is smaller than the old architecture can
cleanly become.

### Move all useful workflow code into new Core

Pros:

- faster reuse of existing logic.

Cons:

- repeats the same product/runtime mixing problem;
- conflicts with `magick-ai-abilities` and Content Assistant ownership;
- makes Core a workflow engine again.

Rejected because Core should govern operations, not own product workflows.

## Consequences

- New Core starts smaller and easier to reason about.
- Old Magick AI remains useful for contracts, smoke ideas, and boundary lessons.
- Product features must live in product plugins such as Content Assistant.
- Reusable WordPress abilities must live in `magick-ai-abilities`.
- Any future expansion of Core must prove it is governance, not product
  workflow ownership.

