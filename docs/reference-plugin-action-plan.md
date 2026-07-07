# Reference Plugin Action Plan

Status: active next-stage action guide.

This plan turns the reference-plugin benchmark into the next concrete work
sequence. It is intentionally small: each project gets one benchmark-derived
improvement lane, and each lane must keep the current authority boundary
unchanged.

## Goal

Learn from mature WordPress plugins without copying their product shape into
the wrong module.

The next stage should produce better review, contract, connection, operator,
and runtime-evidence experiences across the current stack while avoiding new
runtime ownership, approval stores, workflow builders, provider routing, or
silent WordPress writes.

## Project Lanes

| Lane | Reference patterns | Next useful output | Success signal |
| --- | --- | --- | --- |
| Core governance review | PublishPress Revisions, WP Activity Log, Activity Log | One proposal or audit readability improvement. | Operators can see intent, status, actor, evidence, and next action faster without Core executing writes. |
| Abilities Toolkit contracts | WordPress Abilities API, WordPress AI plugin experiments | One metadata/schema compatibility note and one low-risk contract cleanup. | Core and Adapter can consume abilities with fewer private assumptions. |
| Adapter channel feedback | WP Webhooks, Uncanny Automator, AutomatorWP | One connection/help or blocked-action feedback checklist. | External clients understand what is allowed, what failed, and what to revise without Adapter owning approval truth. |
| Workflow Toolbox product flow | AI Engine, AI Power, PublishPress-style review/checklist patterns | One fixed-workflow UX checklist for preview, source labels, and present-admin confirmation. | Toolbox presents one reviewed artifact and one clear next action instead of becoming generic chat or automation. |
| Cloud Addon and Cloud runtime evidence | Connector setup screens, webhook delivery diagnostics, SaaS run logs | One diagnostics/evidence checklist for connection health, run detail, entitlement, and suggestion-only outputs. | Cloud and Addon explain runtime state while WordPress remains the local approval and write control plane. |

## Sequence

1. Close the contract baseline first.
   Run the cross-repo quality matrix and fix any drift before starting visual
   or product-surface changes.

2. Start with Core only if the change is readability.
   Improve proposal or audit comprehension using queue/log patterns. Do not add
   new lifecycle states, execution, queues, retry workers, or content UX.

3. Move to Toolkit contract shape.
   Compare ability ids, schemas, risk metadata, dry-run previews, and
   `implementation_posture` metadata against current WordPress Abilities API
   expectations. Keep shims thin and documented.

4. Move to Adapter feedback.
   Improve connection manifest, help text, and blocked execution feedback using
   integration-plugin patterns. Keep Adapter a channel and post-preflight
   executor only.

5. Move to Toolbox workflow ergonomics.
   Improve a fixed workflow's preview, evidence, source labels, or local
   confirmation copy. Do not build a generic workflow builder or broad AI
   dashboard.

6. Move to Cloud Addon and Cloud diagnostics.
   Improve read-only connection/runtime evidence. Keep runtime, entitlement,
   and provider detail in Cloud, and keep WordPress writes local and governed.

## Admission Rules

A benchmark-derived change may enter implementation when it satisfies all of
these:

- it names the reference pattern being learned from;
- it names the owning project and the exact boundary it must not cross;
- it reduces operator confusion or contract ambiguity;
- it uses the owning repository's existing gate;
- it does not add a new dependency unless a separate ADR accepts it.

A change must stop at a boundary note when it would:

- add jobs, leases, retry workers, schedulers, or workflow runtime to Core,
  Adapter, Toolbox, or Cloud Addon;
- turn fixed workflows into a generic automation builder;
- turn suggestions into silent WordPress writes;
- move approval truth, audit truth, provider keys, model routing, or prompt
  ownership into the wrong project;
- require a user to understand more surfaces before receiving value.

## Verification

Before closing any cross-project benchmark-derived slice, run the owning
repository's gate and then run the central matrix from
`/Users/muze/gitee/npcink-workflow-toolbox`:

```bash
composer quality:matrix
composer quality:matrix:run
```

The matrix is evidence that the stack still composes as governance, abilities,
channel, product workflow, local connector, and hosted runtime layers.
