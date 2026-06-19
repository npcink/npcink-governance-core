# Npcink Admin Menu Standard

Status: active for the local WordPress admin surfaces.

## Goal

Npcink plugins share one WordPress admin entry without merging ownership.
The top-level menu is a navigation shell only. Each plugin keeps its own
runtime, settings, data, and capability boundary.

## Top-Level Menu

All Npcink operator surfaces should use:

- top-level menu title: `Npcink AI`
- top-level slug: `npcink-ai`
- capability: `manage_options`
- icon: `dashicons-superhero`
- position: `58`

Each plugin that exposes a Npcink operator surface may ensure the parent
menu exists, but it must first check the global admin menu and avoid registering
a duplicate parent.

Host plugins should use stable `admin_menu` priorities so submenu order does
not depend on plugin activation order: Core at 10, Adapter at 20, Abilities at
40, and Cloud Addon at 50. `npcink-abilities-toolkit` keeps its standalone
`Tools -> Abilities API Packages` fallback when no shared parent menu exists.

The parent page is `Overview`. It must stay shallow: show orientation and point
operators to installed submenu entries. It must not duplicate governance,
OpenClaw handoff, Cloud configuration, or Abilities API test workflows.

## Submenu Order

| Position | Menu title | Owner | Responsibility |
| --- | --- | --- | --- |
| 10 | `Core` | `npcink-governance-core` | Proposal review, approval/rejection, commit preflight, audit, and client access tokens. |
| 20 | `Adapter` | `npcink-openclaw-adapter` | OpenClaw handoff, endpoint discovery, health, and client connection material. |
| 40 | `Abilities` | `npcink-abilities-toolkit` | Abilities API package test surface, route checks, and demo ability controls. |
| 50 | `Cloud Addon` | `npcink-cloud-addon` | Cloud Base URL/API key entry, signed verification, local connection state, and read-only entitlement summary. |

## Boundary Rules

- Core remains the governance authority. It does not execute abilities, run
  workflow runtime, or own productized OpenClaw onboarding.
- Adapter remains the OpenClaw channel. It does not store Cloud credentials,
  define abilities, or own proposal/approval truth.
- Cloud Addon remains a thin connector. It must not become a billing, router,
  prompt, preset, queue, scheduler, workflow, or WordPress write control plane.
- Abilities remains an independent WordPress Abilities API package plugin.
  When the Npcink parent menu exists, it may attach there. When installed
  alone, it should keep a `Tools -> Abilities API Packages` fallback.

## Documentation Rule

User-facing docs should refer to these admin paths:

- `Npcink AI -> Core`
- `Npcink AI -> Adapter`
- `Npcink AI -> Abilities`
- `Npcink AI -> Cloud Addon`

Only the standalone Abilities fallback should mention
`Tools -> Abilities API Packages`.
