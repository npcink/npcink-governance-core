# Magick AI Admin Menu Standard

Status: active for the local WordPress admin surfaces.

## Goal

Magick AI plugins share one WordPress admin entry without merging ownership.
The top-level menu is a navigation shell only. Each plugin keeps its own
runtime, settings, data, and capability boundary.

## Top-Level Menu

All Magick AI operator surfaces should use:

- top-level menu title: `Magick AI`
- top-level slug: `magick-ai`
- capability: `manage_options`
- icon: `dashicons-superhero`
- position: `58`

Each plugin that exposes a Magick AI operator surface may ensure the parent
menu exists, but it must first check the global admin menu and avoid registering
a duplicate parent.

Host plugins should use stable `admin_menu` priorities so submenu order does
not depend on plugin activation order: Core at 10, Adapter at 20, Abilities at
40, and Cloud Addon at 50. `magick-ai-abilities` keeps its standalone
`Tools -> Abilities API Packages` fallback when no shared parent menu exists.

The parent page is `Overview`. It must stay shallow: show orientation and point
operators to installed submenu entries. It must not duplicate governance,
OpenClaw handoff, Cloud configuration, or Abilities API test workflows.

## Submenu Order

| Position | Menu title | Owner | Responsibility |
| --- | --- | --- | --- |
| 10 | `Core` | `magick-ai-core` | Proposal review, approval/rejection, commit preflight, audit, and advanced Core app keys. |
| 20 | `Adapter` | `magick-ai-adapter` | OpenClaw handoff, endpoint discovery, health, and client connection material. |
| 40 | `Abilities` | `magick-ai-abilities` | Abilities API package test surface, route checks, and demo ability controls. |
| 50 | `Cloud` | `magick-ai-cloud-addon` | Cloud Base URL/API key entry, signed verification, local connection state, and read-only entitlement summary. |

## Boundary Rules

- Core remains the governance authority. It does not execute abilities, run
  workflow runtime, or own productized OpenClaw onboarding.
- Adapter remains the OpenClaw channel. It does not store Cloud credentials,
  define abilities, or own proposal/approval truth.
- Cloud Addon remains a thin connector. It must not become a billing, router,
  prompt, preset, queue, scheduler, workflow, or WordPress write control plane.
- Abilities remains an independent WordPress Abilities API package plugin.
  When the Magick AI parent menu exists, it may attach there. When installed
  alone, it should keep a `Tools -> Abilities API Packages` fallback.

## Documentation Rule

User-facing docs should refer to these admin paths:

- `Magick AI -> Core`
- `Magick AI -> Adapter`
- `Magick AI -> Abilities`
- `Magick AI -> Cloud`

Only the standalone Abilities fallback should mention
`Tools -> Abilities API Packages`.
