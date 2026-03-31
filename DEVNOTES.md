# Developer notes (internal)

This document is for contributors to this repository. Keep wording professional and avoid committing any client-specific information.

## Core vs sector-pack boundary

- **ContextualWP core** remains sector-agnostic and provides extension points, including pack registration and schema filters.
- **Sector packs** (this plugin) should:
  - Contain only sector-specific configuration and logic.
  - Avoid duplicating or forking core functionality.
  - Fail gracefully when core is missing or incompatible.

## Status: 0.2.0 (first functional enhancement pass)

Implemented:

- Composer PSR-4 autoloading for `ContextualWP\HousebuilderPack\`.
- Safe bootstrap and guarded registration flow.
- Pack registrar in `src/PackRegistrar.php` plus extension hookup after successful registration.
- **`RelationshipService`**: `contextualwp_manifest_schema_relationships` — development↔plot edges when CPTs exist; optional evidence from ACF `relationship` / `post_object` field definitions; taxonomy→plot rows under strict conditions (generic development-style taxonomy slug, applies to plots, single object type); future_developments→developments when both exist.
- **`InterpretationService`**: `contextualwp_schema` and `contextualwp_manifest_schema` — per post type `housebuilder_pack` block when slugs match known interpretations and the CPT is public; `contextualwp_schema_interpretation` — aggregate `housebuilder_pack` with post type and taxonomy maps.
- **`SchemaExtensionService`**: `contextualwp_acf_schema_field_groups` — per-field `semantic` (`group`, `basis`, `keywords_matched`) from keyword substrings; `semantic_groups` on the field group when assignments are unambiguous (no top-score ties).
- **`SiteStructureHints`**: public CPT detection, taxonomy applicability, generic “development family” slug heuristic (`development`, `developments`, or `/development(s)?/` segment), ACF graph walk for post type targets.

Intentionally not implemented yet:

- Content-level or value-level inference (no reading posts, terms, or field values for semantics).
- Automated test suite in this repo.
- Broad slug aliasing or site-configurable CPT maps.

## Assumptions (important)

- **Exact CPT slugs** for the primary model: `developments`, `plots`, `future_developments`. Sites that rename these CPTs will not get the matching interpretation or relationship rows until detection is generalized.
- **Taxonomy hints** require the taxonomy to apply to `plots` (where relevant), match **`SiteStructureHints::isGenericDevelopmentTaxonomy()`** (allowed slugs or naming pattern), and often require the taxonomy to be registered against **only one** post type for the strict plot-classifier path (multi-object taxonomies are skipped for that assertion).
- **ACF evidence** for dev↔plot relationships requires ACF loaded and field groups discoverable via `acf_get_field_groups` / `acf_get_fields`; link detection only considers `relationship` and `post_object` types whose `post_type` filter includes the target CPT.
- **Semantic tagging** is substring keyword matching on **name, label, instructions**; two groups with the same top score produce **no** semantic output for that field.
- Version and compatibility behaviour unchanged in spirit from 0.1.0: still depends on `contextualwp_register_sector_pack()` presence and optional `CONTEXTUALWP_VERSION` for strict checks.

## Testing workflow (local)

Recommended local loop:

- Use a real local WordPress site with ContextualWP core installed.
- Activate core, then activate this pack.
- Verify:
  - No fatals with core active/inactive.
  - Admin notices appear only for administrators when needed.
  - Pack registration and filters run only when core is available and compatible.
- Optionally run the **baseline vs with-pack** fixture capture (see below) and diff JSON exports.

## Fixture policy (non-negotiable)

- Never commit:
  - Real endpoint outputs
  - Real client identifiers, addresses, post content, or metadata
  - Any operational details that could reveal private environments
- `tests/local-fixtures/` is strictly local-only; JSON/NDJSON/TXT there are gitignored.
- `tests/fixtures/examples/` is reserved for future sanitised examples only.

## Local comparison loop

Use a simple baseline comparison during development:

- `tests/local-fixtures/baseline/`: captured outputs with core only
- `tests/local-fixtures/with-pack/`: captured outputs with core + this pack

Compare locally to confirm pack behaviour is additive (new keys/branches, not breaking changes to core-owned structure). Do not commit captured files.

## Next steps (likely)

- Broaden inference beyond exact CPT slugs (carefully), for example optional constants, filters, or config documented for integrators.
- Refine semantic keyword sets and tie-breaking; consider sector-specific allow/deny lists or confidence thresholds if core gains a standard shape for that metadata.
- Revisit taxonomy rules once more real site patterns are observed (without baking in client-specific names).
- Align with any new core hooks or interpretation merge conventions.
- Add a minimal smoke-test harness or sanitised fixtures when contracts stabilize.
