=== Npcink Governance Core ===
Contributors: muze233
Tags: ai, governance, approval, audit, abilities
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Npcink AI governance layer for WordPress operations.

== Description ==

Npcink Governance Core is the Npcink AI governance layer for WordPress operations. It gives site administrators and trusted host plugins a reviewable approval layer before AI-assisted actions are committed to a WordPress site.

Core discovers available WordPress Abilities API operations, records proposed AI actions, supports approval and rejection, returns commit-preflight context, and stores audit evidence. It is designed for installations where AI tools, adapters, or product plugins may request WordPress changes, but the site still needs a clear governance record.

= What Core does =

* Discovers available WordPress abilities for governance intake.
* Stores proposals for AI-assisted WordPress operations.
* Supports approve and reject decisions with audit evidence.
* Provides commit-preflight context before a trusted host or adapter performs final writes.
* Manages scoped app keys for trusted governance clients.
* Records audit events for proposal creation, policy evaluation, approval, rejection, commit preflight, and execution-result handoff.
* Keeps governance separate from content generation, model routing, cloud service billing, and final write execution.

= Who should use this plugin =

Npcink Governance Core is for WordPress administrators, host plugins, adapters, and developers that need a local approval and audit boundary for AI-assisted WordPress operations. It is useful when AI tools can prepare or request changes, but a site owner or trusted governance client must review, approve, and track those changes.

This plugin is not a one-click AI writer, SEO assistant, image generator, chatbot, or workflow runtime. Product workflows and ability callbacks belong in separate provider, adapter, or product plugins.

= Requirements and integrations =

Core works best with WordPress 7.0 or later and WordPress Abilities API providers. The reference first-party provider is Npcink Abilities Toolkit, but the base governance lifecycle can also govern third-party WordPress Abilities API providers that expose stable ability ids, schemas, permission callbacks, risk metadata, and dry-run previews.

Core exposes governance REST endpoints under `/wp-json/npcink-governance-core/v1/`. Trusted adapters and host plugins can use those endpoints to create proposals, approve or reject proposals, request commit preflight, and record external execution results.

= Privacy and data =

Npcink Governance Core does not call external AI services, does not load remote assets, and does not send site data to third parties. It stores governance records locally in WordPress database tables, including proposal data, audit events, app-key metadata, and rate-limit state. App-key secrets are hashed before storage, and one-time bearer tokens are shown only when created.

= Boundaries =

Core does not generate content, route models, run MCP or workflow runtimes, store provider credentials, proxy ability execution, or perform final WordPress mutations. Final writes belong to a trusted host, adapter, or runtime outside Core after the governance step is complete.

== Installation ==

1. Upload the plugin to `wp-content/plugins/npcink-governance-core`.
2. Activate Npcink Governance Core in WordPress.
3. Open Npcink AI > Core to review governance status, proposals, audit entries, and advanced Core app-key controls.

== Frequently Asked Questions ==

= Who is this plugin for? =

It is for site administrators, host plugins, adapters, and developers that need reviewable governance for AI-assisted WordPress operations. It is especially useful when AI tools can prepare changes but the site still needs approval, commit preflight, and audit evidence before those changes are applied.

= Is this an AI content generator? =

No. Core does not write articles, generate media, create SEO copy, reply to comments, choose AI models, or store provider credentials. It governs proposed operations created by separate tools, adapters, or WordPress Abilities API providers.

= Does Core execute AI writes? =

No. Core records governance proposals and returns commit-preflight context. Final writes belong to a trusted host, Adapter, or runtime outside Core.

= What is a proposal? =

A proposal is a stored request for an AI-assisted WordPress operation. It includes the target ability, input summary, preview or dry-run evidence, status, caller metadata, and audit trail needed for review.

= What is commit preflight? =

Commit preflight is the governance check that runs after approval and before a trusted external component performs the final write. It returns bounded context, correlation data, and input binding so the downstream component can verify that it is acting on the approved request.

= Do I need another plugin for abilities? =

For full ability intake, yes. Core governs abilities exposed by WordPress Abilities API providers. Npcink Abilities Toolkit is the reference provider, and third-party providers can also integrate by exposing stable ability ids, schemas, permissions, risk metadata, and dry-run previews.

= Does this plugin send data to an external AI service? =

No. Core does not call external AI services and does not send site data to third parties. It stores governance records locally in WordPress database tables.

= What data does Core store? =

Core stores proposal records, approval and rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata, and rate-limit state. App-key secrets are stored as hashes, and one-time bearer tokens are only shown when created.

= What are scoped app keys used for? =

Scoped app keys allow trusted governance clients to call specific Core REST endpoints without giving them broad administrator access. They are intended for controlled hosts, adapters, and internal governance clients.

= Should OpenClaw connect directly to Core? =

Productized OpenClaw setup should connect through a trusted adapter. Direct Core app keys are only for internal governance clients and fallback testing.

= Can third-party ability providers use Core? =

Yes. The proposal lifecycle is provider-neutral at the base layer. Third-party providers can expose WordPress Abilities API definitions with schemas, permission callbacks, risk metadata, and dry-run previews, then submit write or destructive operations for Core review.

== Changelog ==

= 0.1.0 =

Initial governance plugin with ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting, and audit records.
