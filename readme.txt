=== Magick AI Core ===
Contributors: muze233
Tags: ai, governance, approval, audit, abilities
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress AI operation governance layer for ability intake, proposals, approval boundaries, and audit logs.

== Description ==

Magick AI Core is the governance layer for AI-assisted WordPress operations. It discovers ability metadata, records proposals, supports approval and rejection, performs commit preflight, and writes audit evidence.

Core does not generate content, route models, run MCP or workflow runtimes, store provider credentials, proxy ability execution, or perform final WordPress mutations.

== Installation ==

1. Upload the plugin to `wp-content/plugins/magick-ai-core`.
2. Activate Magick AI Core in WordPress.
3. Open Magick AI > Core to review governance status, proposals, audit entries, and advanced Core app-key controls.

== Frequently Asked Questions ==

= Does Core execute AI writes? =

No. Core records governance proposals and returns commit-preflight context. Final writes belong to a trusted host, Adapter, or runtime outside Core.

= Should OpenClaw connect directly to Core? =

Productized OpenClaw setup should connect through Magick AI Adapter. Direct Core app keys are only for internal governance clients and fallback testing.

== Changelog ==

= 0.1.0 =

Initial governance plugin with ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting, and audit records.
