# External Owner Boundary Notes

Status: active boundary note.

This file replaces previously Core-local product and runtime planning
contracts. It is intentionally a pointer map, not an implementation contract.
Core keeps governance intake, approval, preflight, execution-result recording,
and audit truth only.

## Moved Out Of Core

| Surface | Owner | Core role |
| --- | --- | --- |
| Article recipe orchestration and Article Assistant UX | `npcink-abilities-toolkit` for reusable abilities and `magick-ai-toolbox` for product UX | Accept only documented, allowlisted plan output through `POST /proposals/from-plan`; do not store recipe runtime state. |
| Cloud or hosted article writing | Not Core; Cloud must remain runtime/detail only where used by product modules | Do not accept Cloud-generated article body jobs, Cloud-produced article plans, or Cloud bulk writing artifacts as Core truth. |
| Cloud Addon connection and optional telemetry collection | Cloud Addon or product module | Link to the external surface and keep optional local metadata pointers only; do not store billing truth, model routing, Cloud runtime state, provider credentials, or Cloud control-plane configuration. |
| Adapter channel execution and status readback | Adapter or host after Core approval and commit preflight | Issue commit-preflight handoff context and record external execution outcomes only; do not call target abilities, mutate WordPress content, or become the channel runtime. |
| Local automation runtime | Future `npcink-local-automation-runtime` repo or an isolated Toolbox-bundled module with its own namespace, tables, capabilities, kill switch, tests, and boundary docs | Provide proposal, approval, preflight, and audit APIs only; do not store job, lease, scheduler, worker, retry, or dead-letter state. |
| Content Metadata Delta product workbench | Toolbox or another product module | Govern reviewed apply plans only; do not own recommendation UX, learning truth, new-term creation policy, or direct writes. |

## Core Hard Blocks

Do not add these files or equivalents back to Core:

- `docs/ability-recipe-orchestration-contract.md`
- `docs/article-writing-workflow-contract.md`
- `docs/cloud-bulk-article-run-contract.md`
- `docs/local-automation-runtime-contract.md`
- `docs/local-automation-runtime-phase-1-schema.md`
- `docs/content-metadata-delta-implementation-prompt.md`
- `tests/fixtures/local-automation-runtime-dry-run-replay.json`

If one of those surfaces needs implementation details, create or update the
owning repo's docs and tests. Core docs may mention the surface only to define
the governance handoff and the forbidden runtime/product ownership.
