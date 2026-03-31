# Changelog

All notable changes to this project will be documented in this file.

## 0.2.0 - 2026-03-31

First functional enhancement pass focused on structure consumers (manifest, REST schema, interpretation aggregate, ACF schema).

- **Manifest relationships**: Extends `contextualwp_manifest_schema_relationships` with rule-based edges for common housebuilder CPTs (`developments`, `plots`, `future_developments`), optional ACF relationship/post_object evidence between developments and plots, and conservative taxonomy→plot rows when a taxonomy matches generic “development” naming and applies only to plots.
- **Post type interpretation**: When matching public CPTs exist, adds a `housebuilder_pack` block to manifest and REST `post_types` entries with `entity_kind`, `summary`, and `typical_use` (or equivalent taxonomy roles).
- **Aggregate interpretation**: Contributes a `housebuilder_pack` subtree via `contextualwp_schema_interpretation` (post type and taxonomy interpretation maps for consumers that read the combined interpretation object).
- **ACF semantics**: Extends exported ACF field group data with per-field `semantic` objects (keyword substring rules on name, label, instructions) and rolled-up `semantic_groups` per group when unambiguous; ambiguous or tied matches emit no semantic tag.

Conservative behaviour: post types and taxonomies are detected from registration data and naming heuristics, not from content values. Sites that use different CPT slugs or taxonomy patterns may receive fewer or no automatic hints until inference is broadened.

## 0.1.0 - 2026-03-31

- Initial public scaffold for the ContextualWP Housebuilder sector pack.
- Safe bootstrap with graceful handling when ContextualWP core is missing or incompatible.
- Placeholder pack registration and service structure for future iteration.
