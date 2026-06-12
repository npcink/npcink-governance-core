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

List available eval-lab tasks:

```bash
composer eval:lab -- --list
```

Validate the local eval-lab wiring without provider calls:

```bash
composer eval:gutenberg:judge -- dry_run=true limit=3
```

Use a non-default checkout:

```bash
MAGICK_AI_EVAL_LAB_PATH=/Users/muze/gitee/magick-ai-eval-lab \
  composer eval:gutenberg:judge -- dry_run=true limit=3
```

Provider-backed runs are opt-in and read credentials only from eval-lab's local
environment, not from Core runtime code.

## When To Use

Use eval-lab after a product or provider feature can already export reviewable
samples or judge cases, and before widening the feature to more posts or more
operators.

Good first candidates:

- `npcink-toolbox/build-content-metadata-apply-plan`;
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
