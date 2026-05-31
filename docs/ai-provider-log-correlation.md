# AI Provider Log Correlation

Status: adapter-owned productization contract.

This document defines how Core governance records should be correlated with
real AI provider request logs without moving provider execution or request-log
storage into Core.

## Positioning

Magick AI Core remains the governance authority:

- ability discovery guidance;
- proposal records;
- approval and rejection status;
- commit preflight;
- governance audit.

Core does not own:

- AI provider credentials;
- model routing;
- prompt or response storage;
- token accounting;
- provider request logs;
- final WordPress mutation;
- adapter runtime execution.

Provider request logs remain owned by the WordPress `ai` plugin and the active
provider connector. Productized OpenClaw and agent clients should use Magick AI
Adapter as the product connection. Adapter is responsible for carrying Core
governance identifiers into the provider request log context.

## Correlation Identifiers

Core emits two identifiers that external execution layers should preserve:

- `proposal_id`: the Core proposal being reviewed or executed by the adapter;
- `correlation_id`: the commit-preflight correlation id returned by Core and
  stored in the `commit.preflighted` audit metadata.

Adapter should inject these identifiers into the WordPress `ai` plugin request
log context before making a real provider call.

Minimum context fields for real AI provider calls:

```text
proposal_id
correlation_id
ability_id
adapter_request_id
adapter_route
ai_provider
ai_model
governance_source=magick-ai-core
```

Recommended nested context:

```json
{
  "proposal_id": "<core proposal id>",
  "correlation_id": "<core commit-preflight correlation id>",
  "ability_id": "<real ability id>",
  "adapter_request_id": "<adapter-generated request id>",
  "adapter_route": "<adapter route or tool name>",
  "ai_provider": "ollama",
  "ai_model": "qwen3.5:0.8b",
  "governance_source": "magick-ai-core",
  "magick_ai_core": {
    "proposal_id": "<core proposal id>",
    "correlation_id": "<core commit-preflight correlation id>"
  }
}
```

Do not include raw prompts, responses, bearer tokens, cookies, app secrets,
provider credentials, or authorization headers in Core proposal payloads or
Core audit metadata.

## Expected Flow

```text
Core capabilities discovery
-> Core proposal
-> Core approve/reject
-> Core commit-preflight
-> Adapter real AI provider call
-> WordPress ai plugin request log
-> Operator correlates Core audit and AI Request Logs by proposal_id/correlation_id
```

The provider call happens outside Core. Core still returns
`commit_execution=false` and `core_proxy_execute=false`.

## Operator Review

When investigating an operation:

1. Open Governance Audit and filter by `proposal_id` or `correlation_id`.
2. Open AI Request Logs from the WordPress `ai` plugin and search the same
   `proposal_id` or `correlation_id`.
3. Treat Core audit as the source of governance truth.
4. Treat AI Request Logs as the source of provider request truth.

If the AI Request Logs provider column is unavailable or blank for a local
connector, Adapter should still write `ai_provider` and `ai_model` into the log
context. The provider connector can improve provider detection later without
changing the Core contract.

## Productization Responsibility

Core should not add a provider request endpoint or merge AI Request Logs into
Core audit for this stage.

Magick AI Adapter should productize this contract by adding:

- automatic request-context injection for real AI provider calls;
- a smoke path that can call a local provider such as Ollama;
- assertions that AI Request Logs include `proposal_id` and `correlation_id`;
- documentation for OpenClaw operators that explains where to inspect Core
  audit and AI Request Logs.

## Local Proof

On 2026-05-30, a manual local smoke used WordPress AI Client with the Ollama
model `qwen3.5:0.8b`. The request created one AI Request Logs row with status
`success`, model `qwen3.5:0.8b`, and both Core identifiers in context:

- `proposal_id=5aa1c8fc-7421-429c-a6e4-7746c01cbe56`
- `correlation_id=b101ffd0-c1bc-4157-ab23-c54032f6f13f`

This proves the cross-log correlation pattern. It does not change Core's
runtime boundary.
