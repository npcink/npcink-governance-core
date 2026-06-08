# Content Metadata Delta Implementation Prompt

Use this prompt when assigning another AI agent to implement the first
governed AI feedback-loop slice. The implementation agent should still inspect
the active repository, read its local `AGENTS.md`, and follow the repository's
own startup, testing, and commit rules.

```text
You are implementing the first Npcink AI governed feedback-loop slice:
Content Metadata Delta.

Objective:
Build or plan the narrow P0 that uses one existing WordPress post, related
content from vector context, and the site's existing taxonomy to recommend a
better excerpt/summary, tags, and categories. The feature must improve content
structure and discoverability without becoming an article generator or an
automatic taxonomy engine.

Read first:
- AGENTS.md in the active repo.
- README.md.
- docs/governed-ai-feedback-loop.md.
- docs/operation-classification-contract.md.
- docs/plan-to-proposal-governance.md.
- docs/taxonomy-terms-preview-governance-scenario.md.
- In magick-ai-core only: confirm Core remains proposal, approval, preflight,
  and audit truth. Do not move product workbench logic into Core.

Boundary rules:
- Do not implement article generation, Cloud writing, automatic publishing, or
  unattended batch metadata updates.
- Do not auto-create taxonomy terms in P0. New terms may be recommended, but
  must be separated from existing-term reuse and require explicit review.
- Prefer existing categories and tags over creating new taxonomy terms.
- If a write is triggered from a present WordPress admin screen, affects one
  post, has an exact/sufficient preview, and only updates excerpt or existing
  terms, classify it as eligible for local_admin_consent with audit/activity
  evidence.
- If the source is external, scheduled, batch, destructive, creates terms, or
  lacks full preview, classify it as core_proposal_required.
- Core must not execute final writes. Final writes belong to local abilities or
  the host after the correct authorization path.

P0 inputs:
- target post id;
- post title, body, excerpt, status, current categories, and current tags;
- top 5-20 vector-related posts with ids, titles, snippets, similarity scores,
  and existing terms;
- site taxonomy inventory;
- optional operator intent such as "improve summary", "improve discovery",
  "improve B2B knowledge structure", or "improve related-content matching";
- optional site learning-store hints.

P0 output shape:
Return a structured object with:
- issue_record: user expression, target post, context refs, observed signals;
- diagnosis: current metadata quality, taxonomy gaps, duplicate/noisy term
  warnings, evidence strength;
- delta: recommended excerpt, existing category recommendations, existing tag
  recommendations, optional new term candidates marked review_required=true;
- authorization: operation classification, reason, and required evidence;
- outcome_contract: what can be checked after apply;
- learning_candidates: accepted/rejected metadata patterns to record later.

Suggested JSON skeleton:
{
  "artifact_type": "content_metadata_delta",
  "version": 1,
  "target_post_id": 123,
  "issue_record": {
    "user_expression": "",
    "observed_signals": [],
    "context_refs": []
  },
  "diagnosis": {
    "summary_quality": "missing|weak|acceptable",
    "taxonomy_quality": "missing|noisy|acceptable",
    "hypotheses": [],
    "warnings": []
  },
  "delta": {
    "excerpt": {
      "recommended": "",
      "reason": "",
      "evidence_refs": []
    },
    "categories": [
      {
        "term_id": 0,
        "name": "",
        "confidence": 0.0,
        "reason": "",
        "evidence_refs": []
      }
    ],
    "tags": [
      {
        "term_id": 0,
        "name": "",
        "confidence": 0.0,
        "reason": "",
        "evidence_refs": []
      }
    ],
    "new_term_candidates": [
      {
        "taxonomy": "category|post_tag",
        "name": "",
        "reason": "",
        "review_required": true
      }
    ]
  },
  "authorization": {
    "classification": "suggestion_only|local_admin_consent|core_proposal_required",
    "reason": "",
    "required_evidence": []
  },
  "outcome_contract": {
    "checks": []
  },
  "learning_candidates": []
}

Implementation guidance:
- First inspect whether existing abilities already provide post update,
  taxonomy preview, set terms, or excerpt update behavior. Reuse those instead
  of inventing duplicate callbacks.
- If the active repo is Toolbox or a product module, implement the workbench
  UX/helper there, not in Core.
- If the active repo is npcink-abilities-toolkit, implement reusable read-only
  helper or write abilities with schemas, previews, permission callbacks, and
  dry-run behavior.
- If the active repo is magick-ai-core, only add contract support for a
  governed plan if needed and explicitly documented. Do not run vector search,
  generate recommendations, or execute metadata writes inside Core.
- Preserve evidence refs from vector-related posts so a human can understand
  why tags/categories were suggested.
- Add focused tests for schema shape, classification, no auto term creation in
  P0, and no direct write when classification requires Core proposal review.

Deliverables:
- Document where the work belongs in the module split.
- Implement the narrowest working P0 or write a contract-first patch if the
  active repo cannot own product UX.
- Update docs for public plan shape, authorization path, and non-goals.
- Run the repository's required verification gate and report exact results.
```
