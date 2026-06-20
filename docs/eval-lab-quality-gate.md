# Eval Lab Quality Gate

Status: optional local development gate.

Magick AI Evaluation Lab can be used beside Core to compare AI-generated
candidate quality before a product or ability provider submits a governed
handoff to Core. It is a development evidence tool only.

## Boundary

Core still owns:

- ability intake;
- proposal records;
- approval and rejection status;
- commit preflight and read authorization;
- audit evidence.

Eval-lab may help developers inspect:

- content metadata recommendations before they become a reviewed
  `content_metadata_apply_plan`;
- Gutenberg recipe or block-plan quality before a provider exports a plan;
- Site Knowledge review evidence before a blocked review proposal is submitted;
- image or media candidate quality before a product module builds a governed
  handoff.

Eval-lab must not:

- create Core proposals;
- approve, reject, preflight, or record execution in Core;
- write WordPress content, media, terms, comments, SEO metadata, or templates;
- store provider credentials, prompts, request logs, billing, quota, or app
  tokens in this repository;
- become part of `composer test:all`, release packaging, or production plugin
  behavior.

## Commands

Prerequisite: the Magick AI Evaluation Lab must be available as a sibling
checkout named `magick-ai-eval-lab`, or `MAGICK_AI_EVAL_LAB_PATH` must point to
another local checkout. If the checkout is missing, `scripts/eval-lab.sh` fails
with a clear local setup message before any provider call.

Accepted project review modes:

- `working_diff`: review the current uncommitted diff; this is the default.
  When no working diff exists, eval-lab falls back to `head`.
- `head`: review the most recent commit patch, equivalent to the HEAD vs
  HEAD~1 diff.

The project path is passed to eval-lab only so it can read git status and patch
context for local review. Eval-lab must not write into the project, persist the
project path as Core state, or treat the path as a runtime configuration value.
Provider prompts and generated reports must use a redacted local project label,
not the absolute filesystem path. Core's wrapper passes
`project_label=npcink-governance-core` and
`contract=project_boundary_review_triad.v1` explicitly.

List available eval-lab tasks:

```bash
composer eval:lab -- --list
```

Validate the local eval-lab wiring without provider calls:

```bash
composer eval:project:review -- dry_run=true
composer eval:gutenberg:judge -- dry_run=true limit=3
```

Composer's `--` separator passes trailing `key=value` arguments to the eval-lab
task. The Core wrapper provides `mode=working_diff` by default; later arguments
such as `mode=head` override that default.

Use a non-default checkout:

```bash
MAGICK_AI_EVAL_LAB_PATH=/Users/muze/gitee/magick-ai-eval-lab \
  composer eval:gutenberg:judge -- dry_run=true limit=3
```

Provider-backed runs are opt-in and read credentials only from eval-lab's local
environment, not from Core runtime code.

Run the project boundary triad against the current Core working diff:

```bash
composer eval:project:review
```

Run it against the latest Core commit instead:

```bash
composer eval:project:review -- mode=head
```

This writes local JSON and Markdown reports under
`$MAGICK_AI_EVAL_LAB_PATH/project-review/generated/` or the sibling eval-lab
checkout's `project-review/generated/` directory. These generated reports are
disposable local evidence only. They must never be imported as Core audit
truth, proposals, approvals, preflights, execution history, or CI-required
acceptance results.

## When To Use

Use eval-lab after a product or provider feature can already export reviewable
samples or judge cases, and before widening the feature to more posts or more
operators.

Good first candidates:

- Core boundary-sensitive diffs and documentation changes;
- Core audit, credential, proposal persistence, REST authorization, app scope,
  rate limit, eval-lab wrapper, or release packaging changes;
- `npcink-abilities-toolkit/build-content-metadata-apply-plan`;
- `npcink-abilities-toolkit/build-pattern-page-plan`;
- `npcink-abilities-toolkit/build-article-block-plan`;
- `npcink-abilities-toolkit/build-block-theme-site-plan`;
- `npcink-toolbox/build-site-knowledge-review-plan`;
- image candidate adoption plans.

Do not use eval-lab to prove Core persistence, lifecycle, authorization,
redaction, app scope, rate limit, or REST behavior. Those remain deterministic
Core gates covered by `composer test:all` and `composer smoke:wp`.

## Expected Evidence

Keep generated eval artifacts in eval-lab `generated/` directories or another
ignored local output path. Treat model scores and cross-model disagreement as
review aids, not final acceptance truth. Human spot checks still decide whether
prompt, ranking, or UI copy changes are needed.
