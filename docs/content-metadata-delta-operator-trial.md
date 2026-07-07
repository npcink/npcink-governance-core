# Content Metadata Delta Operator Trial

Status: superseded as the current next-stage target; retained as a historical
trial protocol.

This document recorded the validation stage proposed after the P0 governed
loop was proven across Workflow Toolbox, Governance Core, AI Client Adapter,
and WordPress Abilities API execution. It remains useful as a historical trial
protocol and boundary guard, but it is not the current next-stage target and
must not be used to start a first-party summary/category/tag recommendation
product inside Core.

## Purpose

The historical goal was to prove whether Content Metadata Delta could produce
repeatable operator value on real posts before any product expanded surface
area.

The trial must answer:

1. Do operators accept, edit, or reject the excerpt/category/tag suggestions?
2. Are accepted changes still bounded to one selected post and existing terms?
3. Does the governed path preserve review, approval, execution, and audit
   evidence without adding Core workflow ownership?
4. What site-specific lessons should be retained for the next recommendation
   pass?

## Scope

If this historical protocol is revived by a later accepted decision, use 3 to
5 real posts from a local or staging WordPress site. Process one post at a
time.

Allowed P0 suggestion fields:

- reviewed excerpt;
- existing category assignment;
- existing tag assignment.

Required artifacts for every trial case:

- `Issue Record`;
- `Outcome Contract`;
- `Learning Entry`;
- Core proposal or local-consent evidence, depending on the operation
  classifier result;
- post-apply readback evidence when a write is approved and executed.

## Ownership

| Area | Owner |
| --- | --- |
| Operator-facing trial UX and fixed-button flow | Workflow Toolbox or another product plugin |
| Reusable recommendation and write ability definitions | `npcink-abilities-toolkit` or another ability provider |
| Proposal, approval, preflight, sensitive-read authorization, audit | Governance Core |
| Channel execution and status/readback projection | AI Client Adapter or another channel adapter |
| Optional recommendation-quality review | `npcink-eval-lab` |

Core must not own the product workbench, trial runner, content scoring model,
taxonomy policy engine, or learning store runtime.

## Trial Case Contract

Each case should be stored as a local development artifact by the product owner
or eval workspace. Core stores only normal proposal/audit records when the
case uses Core governance.

Recommended shape:

```json
{
  "contract": "content_metadata_delta_operator_trial.v1",
  "write_posture": "operator_review_required",
  "target": {
    "object_type": "post",
    "object_id": 0,
    "status": "publish"
  },
  "issue_record": {
    "user_expression": "",
    "observed_signals": [],
    "diagnostic_hypotheses": [],
    "selected_hypothesis": "",
    "delta_summary": "",
    "authorization_path": "core_proposal_required"
  },
  "outcome_contract": {
    "excerpt_length_range": [80, 180],
    "reuse_existing_terms_only": true,
    "no_new_taxonomy_terms": true,
    "no_batch_write": true,
    "no_publish": true
  },
  "review_decision": {
    "excerpt": "accepted|edited|rejected|not_applicable",
    "category": "accepted|edited|rejected|not_applicable",
    "tags": "accepted|edited|rejected|not_applicable",
    "operator_notes": ""
  },
  "governance_evidence": {
    "proposal_id": "",
    "audit_event_ids": [],
    "execution_status": "",
    "readback_status": ""
  },
  "learning_entry": {
    "accepted_patterns": [],
    "rejected_patterns": [],
    "site_terms": [],
    "excerpt_style_notes": [],
    "stronger_review_required": false
  }
}
```

## Eval-Lab Use

`npcink-eval-lab` may be used as a development-only assistant for sample
export, recommendation review, project boundary review, and operator trial
worksheets. It must remain optional and local.

Recommended uses:

```bash
NPCINK_EVAL_LAB_PATH=/Users/muze/gitee/npcink-eval-lab \
  composer eval:lab -- --list domain=recommendation provider=offline

NPCINK_EVAL_LAB_PATH=/Users/muze/gitee/npcink-eval-lab \
  composer eval:project:review -- dry_run=true
```

Use provider-backed eval-lab tasks only when local `.env.evaluation.local`
credentials are already configured inside eval-lab. Do not pass provider keys
through Core, Toolbox, Adapter, or command-line arguments.

Eval-lab output is development evidence only. It must not become:

- a Core proposal source of truth;
- a Core audit record;
- a CI-required gate;
- a WordPress write executor;
- a queue, workflow runtime, or durable learning-store service.

## Stop Rules

Stop the trial and record a boundary note instead of implementing inside Core
if the next request requires:

- automatic taxonomy term creation;
- more than one post per approval;
- automatic publishing;
- unattended scheduled runs;
- background queues or retry workers;
- Cloud writing WordPress;
- Core final execution;
- persistent learning-store runtime inside Core.

## Acceptance

The trial is complete when:

- at least 3 real single-post cases are recorded;
- each case has Issue Record, Outcome Contract, review decision, governance
  evidence, and Learning Entry;
- accepted writes, if any, use the existing Core proposal or approved local
  consent path;
- no case creates taxonomy terms automatically;
- no case performs batch metadata edits;
- no case makes Core the content product owner;
- the final report recommends one of: stop, repeat trial, add learning-record
  product surface, or expand to another single-object field.
