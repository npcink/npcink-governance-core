# Ability Intake Contract

Status: MVP contract.

Magick AI Core consumes abilities. It does not define product abilities.

## Discovery Order

1. Prefer `magick_ai_abilities_get_registered()` when the
   `magick-ai-abilities` package is active.
2. Fall back to WordPress Abilities API discovery with `wp_get_abilities()` when
   available.
3. Return an empty list with a diagnostic status when no ability source is
   available.

## Normalized Capability Row

Core normalizes each ability to:

- `ability_id`
- `label`
- `description`
- `risk_level`
- `requires_approval`
- `input_schema`
- `output_schema`
- `source`
- `raw`

## Runtime Boundary

Ability intake is read-only. It must not:

- execute abilities;
- register fallback abilities;
- project Agent Gateway tools;
- infer workflow ownership;
- approve or commit writes.

## Missing Dependencies

When no ability source exists, Core should report:

- `source`: `none`
- `available`: `false`
- `message`: human-readable missing dependency text

This is not a fatal plugin condition. Core can still record proposals and audit
events while ability providers are installed later.

