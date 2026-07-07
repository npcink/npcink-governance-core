# AI Write Classification Regression Evidence

Status: active evidence template.

Use this template before release candidates and after changes that touch
AI-assisted WordPress write entrypoints. It records the real-site evidence for
the [Operation Classification Contract](operation-classification-contract.md)
without turning the evidence run into a new Core feature.

## Scope

Canonical local smoke target:

```text
/Users/muze/Local Sites/magick-ai/app/public
https://magick-ai.local/
```

Do not store local passwords, cookies, app tokens, bearer tokens, API keys, raw
provider payloads, or personal content in this file or in committed evidence.
Record only counts, command names, pass/fail results, proposal ids when useful,
audit event names, and whether fixtures were cleaned up.

## Evidence Record Template

Copy this section into the release pull request, release notes, or local
closeout note for the candidate under review.

```text
AI Write Classification Regression Evidence
Date:
Candidate commit(s):
Local WordPress target: /Users/muze/Local Sites/magick-ai/app/public
Site URL: https://magick-ai.local/
Operator:

Lane 1 - Native editor / generic AI plugin acceptance
- Action performed:
- Core proposal count before:
- Core proposal count after:
- Core audit count before:
- Core audit count after:
- Expected result: counts unchanged; no Core proposal; no Core audit row.
- Actual result:
- Pass/fail:

Lane 2 - Toolbox Local Admin Consent featured image
- Command:
  WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" WP_CLI_BIN=/opt/homebrew/bin/wp composer smoke:local-featured-image
- Expected result: no Core proposal; one local_admin_consent.requested event;
  one local_admin_consent.completed event; sampled featured image restored.
- Actual result:
- Proposal count delta:
- Audit events observed:
- Fixture cleanup/restoration:
- Pass/fail:

Lane 3 - High-risk article/media batch
- Command:
  WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" WP_CLI_BIN=/opt/homebrew/bin/wp NPCINK_TOOLBOX_ARTICLE_MEDIA_BATCH_SMOKE_PURGE=1 composer smoke:article-media-batch-core
- Expected result: core_proposal_required; Core proposal evidence created;
  no WordPress post/media writes; no local_admin_consent.* audit events;
  smoke proposal fixture purged.
- Actual result:
- Proposal id or fixture count:
- Audit events observed:
- Fixture cleanup:
- Pass/fail:

Conclusion:
- Release candidate accepted for AI write classification: yes/no
- Follow-up required:
```

## Pass Criteria

The regression passes only when all three lanes match the classification matrix:

- visible generic AI plugin or native editor acceptance remains ordinary
  author/editor action outside Core proposal review;
- the narrow Toolbox existing-attachment featured-image proof remains audited
  Local Admin Consent and does not create a proposal;
- high-risk, external, delegated, incomplete-preview, or batch writes remain
  Core proposal paths and do not emit `local_admin_consent.*` audit events.

If any lane fails, do not expand Core to make the evidence pass. First classify
the failure:

- environment or LocalWP targeting issue;
- inactive or mismatched plugin;
- product caller missing classification evidence;
- real boundary regression that needs a focused fix.

## Non-Goals

This evidence template must not become:

- first-party summary/category/tag generation;
- a workflow runtime, queue, scheduler, or batch execution console;
- a Cloud WordPress write path;
- Core final execution;
- a second approval store;
- a place to store credentials, raw provider payloads, or private content.
